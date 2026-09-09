<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use App\Models\JobRequest;
use App\Models\User;
use App\Models\Location;
use App\Models\Service;
use App\Models\FreelancerServicePriceChangeRequest;
use App\Services\JobRequestFreelancerServiceSync;
use App\Services\FreelancerServicePriceChangeService;
use App\Models\WhatsappMsgGroup;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Imports\JobRequestImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use App\Services\FreelancerAttendantUniformService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class JobProcController extends Controller
{
    public function index() {
        $executives = User::where('role_id', 4)->get();
        $locations = Location::get();
        $services = Service::all();
        $pendingPriceChangeCount = FreelancerServicePriceChangeRequest::query()
            ->where('status', FreelancerServicePriceChangeRequest::STATUS_PENDING)
            ->count();

        return view('admin.jobproc.index', compact('executives', 'locations', 'services', 'pendingPriceChangeCount'));
    }
    public function getJobRequests(Request $request) {
        $jobRequests = $this->jobRequestsBaseQuery();
        $this->applyJobRequestFilters($jobRequests, $request);

        // Check if this is an API request (mobile app)
        if ($request->expectsJson() && !$request->has('draw')) {
            $rows = $jobRequests->orderBy('job_requests.date_time', 'desc')->get();

            $formattedRequests = $rows->map(function ($jobRequest) {
                $age = $jobRequest->age;
                $gender = null;
                if (strpos($age, '|') !== false) {
                    [$age, $gender] = explode('|', $age);
                }
                return [
                    'id' => $jobRequest->id,
                    'lead_id' => $jobRequest->lead_id,
                    'date_time' => $jobRequest->date_time,
                    'executive_id' => $jobRequest->executive_id,
                    'executive' => trim($jobRequest->f_name . ' ' . ($jobRequest->l_name ?? '')),
                    'customer_name' => $jobRequest->customer_name,
                    'contact_no' => $jobRequest->contact_no,
                    'name' => $jobRequest->name,
                    'age' => $age,
                    'gender' => $gender,
                    'expected_salary' => $jobRequest->expected_salary,
                    'shift' => $jobRequest->shift,
                    'total_experience' => $jobRequest->total_experience,
                    'job_title' => $jobRequest->job_title,
                    'other_remark' => $jobRequest->other_remark,
                    'city' => $jobRequest->city,
                    'remark' => $jobRequest->remark,
                    'status' => $jobRequest->status,
                    'pending_price_change_count' => (int) ($jobRequest->pending_price_change_count ?? 0),
                ];
            });

            return response()->json([
                'data' => $formattedRequests
            ]);
        }

        // Web request - return DataTables format
        return DataTables::of($jobRequests->orderBy('job_requests.date_time', 'desc'))
            ->addColumn('executive', function ($jobRequest) {
                return trim($jobRequest->f_name . ' ' . ($jobRequest->l_name ?? ''));
            })
            ->addColumn('agreement_status_label', function ($jobRequest) {
                $sig = $jobRequest->latestLeegalitySignature;
                return $sig ? ($sig->statusEmoji() . ' ' . $sig->statusLabel()) : '—';
            })
            ->addColumn('partner_id_display', function ($jobRequest) {
                return $jobRequest->partner_id ?? '—';
            })
            ->addColumn('agreement_number_display', function ($jobRequest) {
                return $jobRequest->agreement_number ?? '—';
            })
            ->editColumn('job_title', function ($jobRequest) {
                $title = e($jobRequest->job_title ?? '—');
                if ((int) ($jobRequest->pending_price_change_count ?? 0) > 0) {
                    $title = '<span class="jp-price-req-highlight">' . $title . '</span>';
                }

                return $title;
            })
            // Optionally, split age/gender for display
            ->editColumn('age', function ($jobRequest) {
                if (strpos($jobRequest->age, '|') !== false) {
                    [$age, $gender] = explode('|', $jobRequest->age);
                    return $age . ' (' . ucfirst($gender) . ')';
                }
                return $jobRequest->age;
            })
            ->rawColumns(['job_title'])
            ->make(true);
    }

    public function store(Request $request) {
        try {
            \Log::info('Job Request Store - Request Data:', $request->all());
            
            $request->validate([
                'date_time' => 'required|date',
                'executive_id' => 'nullable|exists:users,id',
                'customer_name' => 'required|string|max:255',
                'contact_no' => 'required|string|max:20',
                'name' => 'required|string|max:255',
                'age' => 'required',
                'gender' => 'required|in:male,female,other',
                'expected_salary' => 'nullable|numeric|between:0,99999999.99',
                'shift' => 'required|in:12,24,both,onetime',
                'total_experience' => 'nullable|string|max:50',
                'job_title' => 'required|string|max:255',
                'service_sub_services' => 'nullable',
                'service_price_overrides' => 'nullable',
                'other_remark' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'remark' => 'nullable|string',
                'status' => 'nullable|in:active,inactive,blacklist',
            ]);

            $serviceRow = Service::query()
                ->where('name', $request->job_title)
                ->with(['subServices' => fn ($q) => $q->where('is_active', true)])
                ->first();

            $normalizedSubServices = JobRequestFreelancerServiceSync::normalizeSubServices(
                JobRequestFreelancerServiceSync::decodeJsonArray($request->input('service_sub_services'))
            );

            if ($subErr = JobRequestFreelancerServiceSync::validateSubServices($serviceRow, $normalizedSubServices)) {
                return response()->json($subErr, 422);
            }

            $priceOverrides = JobRequestFreelancerServiceSync::normalizePriceOverrides(
                JobRequestFreelancerServiceSync::decodeJsonArray($request->input('service_price_overrides'))
            );

            $data = $request->except(['service_sub_services', 'service_price_overrides', 'gender']);
            \Log::info('Job Request Store - Data before processing:', $data);
            
            $data['age'] = $request->age . '|' . $request->gender;
            $data['location'] = $request->city;
            
            // Generate lead_id automatically if not provided (format: JR00000001, JR00000002, etc.)
            if (empty($data['lead_id'])) {
                $latestJobRequest = JobRequest::orderBy('id', 'desc')->first();
                $nextNumber = $latestJobRequest ? $latestJobRequest->id + 1 : 1;
                $data['lead_id'] = 'CLX-FRL-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            }
            
            \Log::info('Job Request Store - Data after processing:', $data);


            $number = $data['contact_no'];
            $execId = $data['executive_id'];
            $group = WhatsappMsgGroup::where('whatsapp_number', $number)->first();
            if ($group) {
                $ids = array_filter(explode(',', $group->executive_ids));
                if (!in_array($execId, $ids)) {
                    $ids[] = $execId;
                    $group->executive_ids = implode(',', $ids);
                    $group->save();
                }
            } else {
                WhatsappMsgGroup::create([
                    'whatsapp_number' => $number,
                    'executive_ids' => $execId,
                ]);
            }


            $jobRequest = JobRequest::create($data);

            JobRequestFreelancerServiceSync::sync(
                $jobRequest,
                (string) $request->job_title,
                $request->city,
                $normalizedSubServices,
                $priceOverrides
            );

            \Log::info('Job Request Store - Created successfully:', ['id' => $jobRequest->id, 'data' => $jobRequest->toArray()]);
            return response()->json(['success' => true, 'message' => 'Job request created successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Job Request Store - Validation failed:', ['errors' => $e->errors(), 'request_data' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Job Request Store - Exception occurred:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'request_data' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating job request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $jobRequest = JobRequest::with(['executive', 'servicePrices', 'latestLeegalitySignature'])->findOrFail($id);
        $age = $jobRequest->age;
        $gender = null;
        if (strpos($age, '|') !== false) {
            [$age, $gender] = explode('|', $age);
        }
        $uniformDressType = $jobRequest->getUniformDressType();
        $hasUniformProfile = $uniformDressType !== null;
        $uploadPath = $this->resolveFreelancerUploadPath($jobRequest);
        $status = (string) ($jobRequest->profile_image_status ?? 'none');

        $serviceRow = Service::query()->where('name', $jobRequest->job_title)->first();
        $serviceId = $serviceRow ? (int) $serviceRow->id : 0;
        $servicePricing = $this->buildServicePricingSummary($jobRequest, $serviceId);

        $leegalitySig = $jobRequest->latestLeegalitySignature;
        $leegalityHtml = view('admin.jobproc.partials.leegality_agreement_panel', [
            'jobRequest' => $jobRequest,
            'leegalitySignature' => $leegalitySig,
        ])->render();

        return response()->json([
            'id' => $jobRequest->id,
            'date_time' => $jobRequest->date_time,
            'executive_id' => $jobRequest->executive_id,
            'executive_name' => $jobRequest->executive ? $jobRequest->executive->name : 'N/A',
            'customer_name' => $jobRequest->customer_name,
            'contact_no' => $jobRequest->contact_no,
            'email' => $jobRequest->email,
            'name' => $jobRequest->name,
            'partner_id' => $jobRequest->partner_id ?? '—',
            'agreement_number' => $jobRequest->agreement_number ?? '—',
            'agreement_status' => $leegalitySig?->signature_status,
            'agreement_status_label' => $leegalitySig ? ($leegalitySig->statusEmoji().' '.$leegalitySig->statusLabel()) : '—',
            'leegality_html' => $leegalityHtml,
            'age' => $age,
            'gender' => $gender,
            'expected_salary' => $jobRequest->expected_salary,
            'shift' => $jobRequest->shift,
            'total_experience' => $jobRequest->total_experience,
            'job_title' => $jobRequest->job_title,
            'service_sub_services' => $jobRequest->service_sub_services ?? [],
            'service_pricing' => $servicePricing,
            'other_remark' => $jobRequest->other_remark,
            'city' => $jobRequest->city,
            'location' => $jobRequest->location,
            'remark' => $jobRequest->remark,
            'status' => $jobRequest->status,
            'recording_url' => $jobRequest->recording_url,
            'last_call_status' => $jobRequest->last_call_status,
            'lead_id' => $jobRequest->lead_id,
            'aadhar_card' => $jobRequest->aadhar_card,
            'pan_card' => $jobRequest->pan_card,
            'qualification_certificate' => $jobRequest->qualification_certificate,
            'bank_document' => $jobRequest->bank_document,
            'is_attendant' => $uniformDressType === 'attendant',
            'uniform_dress_type' => $uniformDressType,
            'has_uniform_profile' => $hasUniformProfile,
            'profile_image_status' => $status,
            'profile_image_upload_url' => $uploadPath ? $this->absolutePublicFileUrl($uploadPath) : null,
            'profile_image_pending_url' => $jobRequest->profile_image_pending
                ? $this->absolutePublicFileUrl($jobRequest->profile_image_pending)
                : null,
            'profile_image_approved_url' => ($status === 'approved' && $jobRequest->profile_image)
                ? $this->absolutePublicFileUrl($jobRequest->profile_image)
                : null,
            'profile_image_generate_url' => $hasUniformProfile
                ? route('admin.jobproc.profile_image_generate', $jobRequest)
                : null,
            'profile_image_approve_url' => $hasUniformProfile
                ? route('admin.jobproc.profile_image_approve', $jobRequest)
                : null,
        ]);
    }

    public function generateProfileImageUniform(JobRequest $job_request, FreelancerAttendantUniformService $uniformService): JsonResponse
    {
        $result = $uniformService->generateUniformPreview($job_request);
        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Generation failed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Preview ready.',
            'preview_url' => $result['preview_url'] ?? $this->absolutePublicFileUrl($job_request->fresh()->profile_image_pending),
        ]);
    }

    public function approveProfileImage(JobRequest $job_request, FreelancerAttendantUniformService $uniformService): JsonResponse
    {
        try {
            $uniformService->approvePending($job_request, Auth::id());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile photo approved and updated for this freelancer.',
            'profile_image_url' => $this->absolutePublicFileUrl($job_request->fresh()->profile_image),
        ]);
    }

    protected function resolveFreelancerUploadPath(JobRequest $freelancer): ?string
    {
        $upload = trim((string) ($freelancer->profile_image_upload ?? ''));
        if ($upload !== '' && Storage::disk('public')->exists($upload)) {
            return $upload;
        }
        if ($freelancer->requiresUniformApproval() && $freelancer->profile_image_status !== 'approved') {
            $legacy = trim((string) ($freelancer->profile_image ?? ''));
            if ($legacy !== '' && Storage::disk('public')->exists($legacy)) {
                return $legacy;
            }
        }

        return null;
    }

    protected function absolutePublicFileUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }
        $path = str_replace('\\', '/', trim((string) $path));
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $path = ltrim($path, '/');
        $relative = str_starts_with($path, 'storage/') ? '/'.$path : '/storage/'.$path;
        $host = rtrim((string) request()->getSchemeAndHttpHost(), '/');
        if ($host === '') {
            $host = rtrim((string) config('app.url', ''), '/');
        }

        return $host.$relative;
    }

    public function edit($id)
    {
        $jobRequest = JobRequest::with(['executive', 'servicePrices'])->findOrFail($id);
        $age = $jobRequest->age;
        $gender = null;
        if (strpos($age, '|') !== false) {
            [$age, $gender] = explode('|', $age);
        }
        $jobRequest->age = $age;
        $jobRequest->gender = $gender;
        $jobRequest->service_price_overrides = JobRequestFreelancerServiceSync::overridesForResponse($jobRequest);

        return response()->json($jobRequest);
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'date_time' => 'required|date',
                'executive_id' => 'nullable|exists:users,id',
                'customer_name' => 'required|string|max:255',
                'contact_no' => 'required|string|max:20',
                'name' => 'required|string|max:255',
                'age' => 'required',
                'gender' => 'required|in:male,female,other',
                'expected_salary' => 'nullable|numeric|between:0,99999999.99',
                'shift' => 'required|in:12,24,both,onetime',
                'total_experience' => 'nullable|string|max:50',
                'job_title' => 'required|string|max:255',
                'service_sub_services' => 'nullable',
                'service_price_overrides' => 'nullable',
                'other_remark' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'remark' => 'nullable|string',
                'status' => 'nullable|in:active,inactive,blacklist',
            ]);

            $serviceRow = Service::query()
                ->where('name', $request->job_title)
                ->with(['subServices' => fn ($q) => $q->where('is_active', true)])
                ->first();

            $normalizedSubServices = JobRequestFreelancerServiceSync::normalizeSubServices(
                JobRequestFreelancerServiceSync::decodeJsonArray($request->input('service_sub_services'))
            );

            if ($subErr = JobRequestFreelancerServiceSync::validateSubServices($serviceRow, $normalizedSubServices)) {
                return response()->json($subErr, 422);
            }

            $priceOverrides = JobRequestFreelancerServiceSync::normalizePriceOverrides(
                JobRequestFreelancerServiceSync::decodeJsonArray($request->input('service_price_overrides'))
            );

            $data = $request->except(['service_sub_services', 'service_price_overrides', 'gender', 'id', '_method', '_token']);
            $data['age'] = $request->age . '|' . $request->gender;
            $data['location'] = $request->city;

            $jobRequest = JobRequest::findOrFail($id);
            $jobRequest->update($data);

            JobRequestFreelancerServiceSync::sync(
                $jobRequest,
                (string) $request->job_title,
                $request->city,
                $normalizedSubServices,
                $priceOverrides
            );

            return response()->json(['success' => true, 'message' => 'Job request updated successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating job request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $jobRequest = JobRequest::findOrFail($id);
        $jobRequest->delete();
        return response()->json(['success' => true, 'message' => 'Job request deleted successfully']);
    }

    public function import(Request $request)
    {
        try {
            \Log::info('Import request received', ['request' => $request->all()]);
            
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
            ]);

            $file = $request->file('file');
            \Log::info('File validation passed', ['filename' => $file->getClientOriginalName(), 'size' => $file->getSize()]);
            
            // Create import instance
            $import = new JobRequestImport();
            \Log::info('Import instance created');
            
            // Import the file
            Excel::import($import, $file);
            \Log::info('Excel import completed');
            
            $successCount = $import->getSuccessCount();
            $errorCount = $import->getErrorCount();
            $errors = $import->getErrors();
            
            $message = "Import completed! Successfully imported {$successCount} job requests.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} rows had errors.";
            }
            
            \Log::info('Import results', ['success_count' => $successCount, 'error_count' => $errorCount]);
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Import validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error importing file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @return list<array{label: string, sub_service_id: int, tags: list<string>, price_12hr: mixed, price_24hr: mixed, price_onetime: mixed, is_override: bool}>
     */
    protected function buildServicePricingSummary(JobRequest $jobRequest, int $serviceId): array
    {
        if ($serviceId <= 0) {
            return [];
        }

        $subs = is_array($jobRequest->service_sub_services) ? $jobRequest->service_sub_services : [];
        $items = [];

        if ($subs !== []) {
            foreach ($subs as $entry) {
                $subId = (int) ($entry['sub_service_id'] ?? 0);
                if ($subId <= 0) {
                    continue;
                }
                $resolved = JobRequestFreelancerServiceSync::resolvePrice($jobRequest, $serviceId, $subId);
                $items[] = [
                    'label' => (string) ($entry['sub_service_name'] ?? 'Sub-service'),
                    'sub_service_id' => $subId,
                    'tags' => is_array($entry['tags'] ?? null) ? $entry['tags'] : [],
                    'price_12hr' => $resolved['price_12hr'],
                    'price_24hr' => $resolved['price_24hr'],
                    'price_onetime' => $resolved['price_onetime'],
                    'is_override' => $resolved['is_override'],
                ];
            }

            if ($items !== []) {
                return $items;
            }
        }

        $tags = [];
        foreach ($subs as $entry) {
            if ((int) ($entry['sub_service_id'] ?? -1) === 0) {
                $tags = is_array($entry['tags'] ?? null) ? $entry['tags'] : [];
                break;
            }
        }

        $resolved = JobRequestFreelancerServiceSync::resolvePrice($jobRequest, $serviceId, 0);
        $items[] = [
            'label' => (string) $jobRequest->job_title,
            'sub_service_id' => 0,
            'tags' => $tags,
            'price_12hr' => $resolved['price_12hr'],
            'price_24hr' => $resolved['price_24hr'],
            'price_onetime' => $resolved['price_onetime'],
            'is_override' => $resolved['is_override'],
        ];

        return $items;
    }

    public function priceChangeRequests(JobRequest $job_request): JsonResponse
    {
        $requests = $job_request->priceChangeRequests()
            ->where('status', FreelancerServicePriceChangeRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (FreelancerServicePriceChangeRequest $req) {
                return [
                    'id' => $req->id,
                    'service_name' => $req->service_name,
                    'service_sub_service_id' => (int) $req->service_sub_service_id,
                    'sub_service_name' => $req->sub_service_name,
                    'price_type' => $req->price_type,
                    'price_type_label' => $req->priceTypeLabel(),
                    'current_price' => $req->current_price,
                    'requested_price' => $req->requested_price,
                    'created_at' => $req->created_at?->format('d M Y, H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'freelancer' => [
                'id' => $job_request->id,
                'name' => $job_request->name,
                'job_title' => $job_request->job_title,
                'city' => $job_request->city,
            ],
            'requests' => $requests,
        ]);
    }

    public function approvePriceChangeRequest(FreelancerServicePriceChangeRequest $price_change_request): JsonResponse
    {
        try {
            app(FreelancerServicePriceChangeService::class)->approve($price_change_request, Auth::id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Could not approve request.';

            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Price change approved. Sirf is freelancer ki price update ho gayi.',
        ]);
    }

    public function rejectPriceChangeRequest(Request $request, FreelancerServicePriceChangeRequest $price_change_request): JsonResponse
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        try {
            app(FreelancerServicePriceChangeService::class)->reject(
                $price_change_request,
                (string) $request->input('admin_note'),
                Auth::id()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Could not reject request.';

            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Price change request rejected.',
        ]);
    }

    protected function jobRequestsBaseQuery()
    {
        return JobRequest::select([
            'job_requests.id',
            'job_requests.date_time',
            'job_requests.executive_id',
            'job_requests.customer_name',
            'job_requests.contact_no',
            'job_requests.name',
            'job_requests.age',
            'job_requests.expected_salary',
            'job_requests.shift',
            'job_requests.total_experience',
            'job_requests.job_title',
            'job_requests.other_remark',
            'job_requests.city',
            'job_requests.remark',
            'job_requests.status',
            'job_requests.lead_id',
            'job_requests.agreement_number',
            'users.f_name',
            'users.l_name',
        ])
            ->withCount(['pendingPriceChangeRequests as pending_price_change_count'])
            ->with('latestLeegalitySignature')
            ->leftJoin('users', 'job_requests.executive_id', '=', 'users.id');
    }

    protected function parseFilterArray(Request $request, string $key): array
    {
        $value = $request->input($key);
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));
        }
        if (is_string($value) && trim($value) !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return [];
    }

    protected function applyJobRequestFilters($query, Request $request): void
    {
        $genders = $this->parseFilterArray($request, 'gender');
        if (count($genders) > 0) {
            $query->where(function ($q) use ($genders) {
                foreach ($genders as $gender) {
                    $q->orWhere('job_requests.age', 'like', '%|' . $gender);
                }
            });
        }

        $shifts = $this->parseFilterArray($request, 'shift');
        if (count($shifts) > 0) {
            $query->whereIn('job_requests.shift', $shifts);
        }

        $cities = $this->parseFilterArray($request, 'city');
        if (count($cities) > 0) {
            $query->whereIn('job_requests.city', $cities);
        }

        $statuses = $this->parseFilterArray($request, 'status');
        if (count($statuses) > 0) {
            $query->whereIn('job_requests.status', $statuses);
        }
    }
}

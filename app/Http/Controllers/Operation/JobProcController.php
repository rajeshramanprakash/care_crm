<?php
namespace App\Http\Controllers\Operation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use App\Models\JobRequest;
use App\Models\User;
use App\Models\Location;
use App\Models\Service;
use App\Models\WhatsappMsgGroup;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
class JobProcController extends Controller
{
    public function index() {
        $executives = User::all();
        $locations = Location::get();
        $services = Service::all();
        $currentUser = Auth::user();
        return view('operation.jobproc.index', compact('executives', 'locations', 'services', 'currentUser'));
    }

        public function getJobRequestsForApp(Request $request) {
        $query = JobRequest::select(
                'job_requests.*',
                'users.f_name as executive'
            )
            ->leftJoin('users', 'job_requests.executive_id', '=', 'users.id')
            ->orderBy('job_requests.created_at', 'desc');

        if ($request->has('gender') && is_array($request->gender) && count($request->gender) > 0) {
            $query->where(function ($q) use ($request) {
                foreach ($request->gender as $gender) {
                    $q->orWhere('job_requests.age', 'like', '%|' . $gender);
                }
            });
        }

        if ($request->has('shift') && is_array($request->shift) && count($request->shift) > 0) {
            $query->whereIn('job_requests.shift', $request->shift);
        }

        if ($request->has('city') && is_array($request->city) && count($request->city) > 0) {
            $query->whereIn('job_requests.city', $request->city);
        }

        if ($request->has('status') && is_array($request->status) && count($request->status) > 0) {
            $query->whereIn('job_requests.status', $request->status);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function getAllJobRequestsForApp(Request $request) {
        $query = JobRequest::select(
                'job_requests.*',
                'users.f_name as executive'
            )
            ->leftJoin('users', 'job_requests.executive_id', '=', 'users.id')
            ->orderBy('job_requests.created_at', 'desc');

        if ($request->boolean('active_only')) {
            $query->where('job_requests.status', 'active');
        }

        if ($request->has('gender') && is_array($request->gender) && count($request->gender) > 0) {
            $query->where(function ($q) use ($request) {
                foreach ($request->gender as $gender) {
                    $q->orWhere('job_requests.age', 'like', '%|' . $gender);
                }
            });
        }

        if ($request->has('shift') && is_array($request->shift) && count($request->shift) > 0) {
            $query->whereIn('job_requests.shift', $request->shift);
        }

        if ($request->has('city') && is_array($request->city) && count($request->city) > 0) {
            $query->whereIn('job_requests.city', $request->city);
        }

        if ($request->has('status') && is_array($request->status) && count($request->status) > 0) {
            $query->whereIn('job_requests.status', $request->status);
        }

        $jobRequests = $query->get();

        return response()->json([
            'data' => $jobRequests
        ]);
    }
    public function getJobRequests(Request $request) {
        $auth_user = Auth::user();
        $jobRequests = JobRequest::select([
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
            'users.f_name',
            'users.l_name'
        ])
        ->leftJoin('users', 'job_requests.executive_id', '=', 'users.id')
        ->orderBy('job_requests.date_time', 'desc');

        // Gender filter
        if ($request->has('gender') && is_array($request->gender) && count($request->gender) > 0) {
            $jobRequests->where(function($query) use ($request) {
                foreach ($request->gender as $gender) {
                    $query->orWhere('job_requests.age', 'like', '%|' . $gender);
                }
            });
        }
        // Shift filter
        if ($request->has('shift') && is_array($request->shift) && count($request->shift) > 0) {
            $jobRequests->whereIn('job_requests.shift', $request->shift);
        }
        // City filter
        if ($request->has('city') && is_array($request->city) && count($request->city) > 0) {
            $jobRequests->whereIn('job_requests.city', $request->city);
        }
        // Status filter
        if ($request->has('status') && is_array($request->status) && count($request->status) > 0) {
            $jobRequests->whereIn('job_requests.status', $request->status);
        }

        return DataTables::of($jobRequests)
            ->addColumn('executive', function ($jobRequest) {
                return trim($jobRequest->f_name . ' ' . ($jobRequest->l_name ?? ''));
            })
            // Optionally, split age/gender for display
            ->editColumn('age', function ($jobRequest) {
                if (strpos($jobRequest->age, '|') !== false) {
                    [$age, $gender] = explode('|', $jobRequest->age);
                    return $age . ' (' . ucfirst($gender) . ')';
                }
                return $jobRequest->age;
            })
            ->make(true);
    }

    public function store(Request $request) {
        try {
            \Log::info('Operation Job Request Store - Request Data:', $request->all());
            
            $request->validate([
                'customer_name' => 'required|string|max:255',
                'contact_no' => 'required|string|max:20',
                'name' => 'required|string|max:255',
                'age' => 'required',
                'gender' => 'required|in:male,female,other',
                'expected_salary' => 'nullable|numeric|between:0,99999999.99',
                'shift' => 'required|in:12,24,both',
                'total_experience' => 'nullable|string|max:50',
                'job_title' => 'required|string|max:255',
                'other_remark' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'remark' => 'nullable|string',
                'status' => 'nullable|in:active,inactive,blacklist',
            ]);

            $data = $request->all();
            $data['date_time'] = now();
            $data['executive_id'] = Auth::id();
            $data['age'] = $data['age'] . '|' . $data['gender'];
            unset($data['gender']);
            
            // Generate lead_id automatically if not provided (format: JR00000001, JR00000002, etc.)
            if (empty($data['lead_id'])) {
                $latestJobRequest = JobRequest::orderBy('id', 'desc')->first();
                $nextNumber = $latestJobRequest ? $latestJobRequest->id + 1 : 1;
                $data['lead_id'] = 'CLX-FRL-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
            }
            
            \Log::info('Operation Job Request Store - Data after processing:', $data);

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
            \Log::info('Operation Job Request Store - Created successfully:', ['id' => $jobRequest->id, 'data' => $jobRequest->toArray()]);
            return response()->json(['success' => true, 'message' => 'Job request created successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Operation Job Request Store - Validation failed:', ['errors' => $e->errors(), 'request_data' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Operation Job Request Store - Exception occurred:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'request_data' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating job request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $jobRequest = JobRequest::with('executive')->findOrFail($id);
        $age = $jobRequest->age;
        $gender = null;
        if (strpos($age, '|') !== false) {
            [$age, $gender] = explode('|', $age);
        }
        return response()->json([
            'id' => $jobRequest->id,
            'date_time' => $jobRequest->date_time,
            'executive_id' => $jobRequest->executive_id,
            'executive_name' => $jobRequest->executive ? $jobRequest->executive->name : 'N/A',
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
            'recording_url' => $jobRequest->recording_url,
            'last_call_status' => $jobRequest->last_call_status,
            'lead_id' => $jobRequest->lead_id,
            'aadhar_card' => $jobRequest->aadhar_card,
            'pan_card' => $jobRequest->pan_card,
            'qualification_certificate' => $jobRequest->qualification_certificate,
            'bank_document' => $jobRequest->bank_document
        ]);
    }

    public function edit($id)
    {
        $jobRequest = JobRequest::with('executive')->findOrFail($id);
        $age = $jobRequest->age;
        $gender = null;
        if (strpos($age, '|') !== false) {
            [$age, $gender] = explode('|', $age);
        }
        $jobRequest->age = $age;
        $jobRequest->gender = $gender;
        return response()->json($jobRequest);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'contact_no' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'age' => 'required',
            'gender' => 'required|in:male,female,other',
            'expected_salary' => 'nullable|numeric|between:0,99999999.99',
            'shift' => 'required|in:12,24,both',
            'total_experience' => 'nullable|string|max:50',
            'job_title' => 'required|string|max:255',
            'other_remark' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'remark' => 'nullable|string',
            'status' => 'nullable|in:active,inactive,blacklist',
        ]);

        $data = $request->all();
        $data['age'] = $data['age'] . '|' . $data['gender'];
        unset($data['gender']);

        $jobRequest = JobRequest::findOrFail($id);
        $jobRequest->update($data);
        return response()->json(['success' => true, 'message' => 'Job request updated successfully']);
    }

    public function destroy($id)
    {
        $jobRequest = JobRequest::findOrFail($id);
        $jobRequest->delete();
        return response()->json(['success' => true, 'message' => 'Job request deleted successfully']);
    }


    public function all_index() {
        try {
            $locations = Location::get();
            $executives = User::all();
            return view('operation.all_job_req.index', compact('executives', 'locations'));
        } catch (\Exception $e) {
            \Log::error('Error in all_index: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while loading the page'], 500);
        }
    }

    public function allGetJobRequests(Request $request) {
        try {
            $jobRequests = JobRequest::select([
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
                'users.f_name',
                'users.l_name'
            ])
            ->leftJoin('users', 'job_requests.executive_id', '=', 'users.id')->where('job_requests.status', 'active');

              // Gender filter
        if ($request->has('gender') && is_array($request->gender) && count($request->gender) > 0) {
            $jobRequests->where(function($query) use ($request) {
                foreach ($request->gender as $gender) {
                    $query->orWhere('job_requests.age', 'like', '%|' . $gender);
                }
            });
        }
        // Shift filter
        if ($request->has('shift') && is_array($request->shift) && count($request->shift) > 0) {
            $jobRequests->whereIn('job_requests.shift', $request->shift);
        }
        // City filter
        if ($request->has('city') && is_array($request->city) && count($request->city) > 0) {
            $jobRequests->whereIn('job_requests.city', $request->city);
        }
        // Status filter
        if ($request->has('status') && is_array($request->status) && count($request->status) > 0) {
            $jobRequests->whereIn('job_requests.status', $request->status);
        }

            return DataTables::of($jobRequests)
                ->addColumn('executive', function ($jobRequest) {
                    return trim($jobRequest->f_name . ' ' . ($jobRequest->l_name ?? ''));
                })
                ->addColumn('action', function($row) {
                    return '<button class="btn btn-sm btn-info show-job" data-id="'.$row->id.'"><i class="fa fa-eye"></i></button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in allGetJobRequests: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching job requests'], 500);
        }
    }
}

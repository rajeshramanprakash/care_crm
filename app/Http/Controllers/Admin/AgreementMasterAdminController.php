<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorRequest;
use App\Models\JobRequest;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgreementMasterAdminController extends Controller
{
    public function index(): View
    {
        $totalDoctors = DoctorRequest::count();
        $totalFreelancers = JobRequest::count();
        $totalVendors = Vendor::count();
        $totalPartners = $totalDoctors + $totalFreelancers + $totalVendors;

        return view('admin.agreements.index', compact(
            'totalDoctors',
            'totalFreelancers',
            'totalVendors',
            'totalPartners'
        ));
    }

    public function data(Request $request): JsonResponse
    {
        $category = strtolower(trim((string) $request->input('category', 'all')));
        $search = strtolower(trim((string) $request->input('search_query', $request->input('search.value', ''))));
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Shortcut search term aliases
        if (in_array($search, ['doc', 'doctor', 'doctors'], true)) {
            $category = 'doctor';
            $search = '';
        } elseif (in_array($search, ['ven', 'vendor', 'vendors'], true)) {
            $category = 'vendor';
            $search = '';
        } elseif (in_array($search, ['frl', 'freelance', 'freelancer', 'freelancers'], true)) {
            $category = 'freelancer';
            $search = '';
        }

        $records = collect();

        // Fetch Doctors
        if (in_array($category, ['all', 'doctor', 'doc'], true)) {
            $docQuery = DoctorRequest::query();
            $this->applyDateFilter($docQuery, $fromDate, $toDate);

            $docQuery->get()->each(function (DoctorRequest $doctor) use ($records) {
                $partnerId = $doctor->lead_id ?: ('CLX-DOC-' . str_pad((string) $doctor->id, 6, '0', STR_PAD_LEFT));
                $agreementNo = $doctor->agreement_number ?: '(Will be generated after signature)';

                $records->push([
                    'id' => 'DOC-' . $doctor->id,
                    'raw_id' => $doctor->id,
                    'category' => 'Doctor',
                    'category_code' => 'doc',
                    'category_badge' => '<span class="badge badge-primary px-2 py-1"><i class="fas fa-user-md mr-1"></i>Doctor</span>',
                    'partner_id' => $partnerId,
                    'agreement_number' => $agreementNo,
                    'name' => $doctor->name ?: '—',
                    'contact_no' => $doctor->mobile ?: $doctor->contact_no ?: '—',
                    'email' => $doctor->email ?: '—',
                    'location' => $doctor->city ?: $doctor->location ?: '—',
                    'status' => $doctor->approval_status ?: 'pending',
                    'created_at' => optional($doctor->created_at)->format('d M Y, h:i A') ?: '—',
                    'created_at_timestamp' => optional($doctor->created_at)->timestamp ?? 0,
                    'detail_url' => route('admin.doctor_requests.index'),
                ]);
            });
        }

        // Fetch Freelancers (JobRequest)
        if (in_array($category, ['all', 'freelancer', 'frl'], true)) {
            $frlQuery = JobRequest::query();
            $this->applyDateFilter($frlQuery, $fromDate, $toDate);

            $frlQuery->get()->each(function (JobRequest $freelancer) use ($records) {
                $partnerId = $freelancer->lead_id ?: ('CLX-FRL-' . str_pad((string) $freelancer->id, 6, '0', STR_PAD_LEFT));
                $agreementNo = $freelancer->agreement_number ?: '(Will be generated after signature)';

                $records->push([
                    'id' => 'FRL-' . $freelancer->id,
                    'raw_id' => $freelancer->id,
                    'category' => 'Freelancer',
                    'category_code' => 'frl',
                    'category_badge' => '<span class="badge badge-info px-2 py-1"><i class="fas fa-user-tie mr-1"></i>Freelancer</span>',
                    'partner_id' => $partnerId,
                    'agreement_number' => $agreementNo,
                    'name' => $freelancer->name ?: '—',
                    'contact_no' => $freelancer->contact_no ?: $freelancer->mobile ?: '—',
                    'email' => $freelancer->email ?: '—',
                    'location' => $freelancer->city ?: $freelancer->location ?: '—',
                    'status' => $freelancer->status ?: 'inactive',
                    'created_at' => optional($freelancer->created_at)->format('d M Y, h:i A') ?: '—',
                    'created_at_timestamp' => optional($freelancer->created_at)->timestamp ?? 0,
                    'detail_url' => route('admin.jobproc.index'),
                ]);
            });
        }

        // Fetch Vendors
        if (in_array($category, ['all', 'vendor', 'ven'], true)) {
            $venQuery = Vendor::query();
            $this->applyDateFilter($venQuery, $fromDate, $toDate);

            $venQuery->get()->each(function (Vendor $vendor) use ($records) {
                $partnerId = $vendor->lead_id ?: ('CLX-VEN-' . str_pad((string) $vendor->id, 6, '0', STR_PAD_LEFT));
                $agreementNo = $vendor->agreement_number ?: '(Will be generated after signature)';

                $records->push([
                    'id' => 'VEN-' . $vendor->id,
                    'raw_id' => $vendor->id,
                    'category' => 'Vendor',
                    'category_code' => 'ven',
                    'category_badge' => '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-store mr-1"></i>Vendor</span>',
                    'partner_id' => $partnerId,
                    'agreement_number' => $agreementNo,
                    'name' => $vendor->name ?: '—',
                    'contact_no' => $vendor->contact_no ?: '—',
                    'email' => $vendor->email ?: '—',
                    'location' => $vendor->location ?: '—',
                    'status' => $vendor->status ?: 'active',
                    'created_at' => optional($vendor->created_at)->format('d M Y, h:i A') ?: '—',
                    'created_at_timestamp' => optional($vendor->created_at)->timestamp ?? 0,
                    'detail_url' => route('admin.vendors.index'),
                ]);
            });
        }

        // Filter by Search text if provided
        if ($search !== '') {
            $records = $records->filter(function ($item) use ($search) {
                return str_contains(strtolower($item['partner_id']), $search) ||
                    str_contains(strtolower($item['agreement_number']), $search) ||
                    str_contains(strtolower($item['name']), $search) ||
                    str_contains(strtolower($item['contact_no']), $search) ||
                    str_contains(strtolower($item['email']), $search) ||
                    str_contains(strtolower($item['location']), $search) ||
                    str_contains(strtolower($item['category']), $search);
            });
        }

        // Sort newest first
        $sorted = $records->sortByDesc('created_at_timestamp')->values();

        return response()->json(['data' => $sorted]);
    }

    private function applyDateFilter($query, ?string $fromDate, ?string $toDate): void
    {
        if ($fromDate) {
            try {
                $start = Carbon::parse($fromDate)->startOfDay();
                $query->where('created_at', '>=', $start);
            } catch (\Throwable $e) {
            }
        }
        if ($toDate) {
            try {
                $end = Carbon::parse($toDate)->endOfDay();
                $query->where('created_at', '<=', $end);
            } catch (\Throwable $e) {
            }
        }
    }
}

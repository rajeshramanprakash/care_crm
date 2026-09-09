@php
    /** @var \App\Models\DoctorRequest $doctor */
    $reviews = $doctor->websiteProfileReviews ?? collect();
    $modeLabels = ['online' => 'Online', 'home_visit' => 'Home visit', 'clinic_visit' => 'Clinic visit'];
@endphp
<div class="dr-website-reviews-manage p-2">
    <p class="small text-muted mb-2">
        These reviews are shown on the CareWeb <strong>consultation</strong> page when visitors open this doctor’s profile (approved doctors only).
    </p>

    <h6 class="font-weight-bold mb-2">Saved reviews ({{ $reviews->count() }})</h6>
    @forelse ($reviews as $r)
        <div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-start bg-light">
            <div class="d-flex flex-grow-1" style="min-width:0;">
                @if ($r->reviewer_photo_path)
                    <img src="{{ e($r->reviewerPhotoPublicUrl()) }}" alt="" class="rounded-circle mr-2 flex-shrink-0" style="width:48px;height:48px;object-fit:cover;">
                @else
                    @php $ini = strtoupper(mb_substr((string) $r->reviewer_display_name, 0, 1)); @endphp
                    <div class="rounded-circle mr-2 flex-shrink-0 bg-secondary text-white d-flex align-items-center justify-content-center font-weight-bold" style="width:48px;height:48px;">{{ $ini }}</div>
                @endif
                <div style="min-width:0;">
                    <div class="font-weight-bold">{{ e($r->reviewer_display_name) }}
                        <span class="badge badge-light border ml-1">{{ $modeLabels[$r->consultation_mode] ?? e($r->consultation_mode) }}</span>
                    </div>
                    <div class="small text-warning mb-1">★ {{ number_format((float) $r->rating, 1) }} / 5</div>
                    @if ($r->reviewed_on)
                        <div class="small text-muted mb-1">Visit / review date: {{ $r->reviewed_on->format('d M Y') }}</div>
                    @endif
                    <p class="small mb-0 text-break">{{ e($r->body) }}</p>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 dr-wr-delete" data-url="{{ route('admin.doctor_requests.website_reviews_destroy', ['doctor_request' => $doctor->id, 'review' => $r->id]) }}">Delete</button>
        </div>
    @empty
        <p class="text-muted small">No reviews yet — add one below.</p>
    @endforelse

    <hr class="my-3">
    <h6 class="font-weight-bold mb-2">Add another review</h6>
    <form id="drWebsiteReviewAddForm" class="dr-wr-add-form" enctype="multipart/form-data" data-store-url="{{ route('admin.doctor_requests.website_reviews_store', $doctor) }}">
        @csrf
        <div class="form-group mb-2">
            <label class="small font-weight-bold mb-0">Reviewer name <span class="text-danger">*</span></label>
            <input type="text" name="reviewer_display_name" class="form-control form-control-sm" required maxlength="255" placeholder="Patient name as shown on website">
        </div>
        <div class="form-row">
            <div class="form-group col-md-4 mb-2">
                <label class="small font-weight-bold mb-0">Rating (1–5) <span class="text-danger">*</span></label>
                <input type="number" name="rating" class="form-control form-control-sm" required min="1" max="5" step="0.1" placeholder="5">
            </div>
            <div class="form-group col-md-4 mb-2">
                <label class="small font-weight-bold mb-0">Consultation mode <span class="text-danger">*</span></label>
                <select name="consultation_mode" class="form-control form-control-sm" required>
                    <option value="online">Online</option>
                    <option value="home_visit">Home visit</option>
                    <option value="clinic_visit">Clinic visit</option>
                </select>
            </div>
            <div class="form-group col-md-4 mb-2">
                <label class="small font-weight-bold mb-0">Review / visit date</label>
                <input type="date" name="reviewed_on" class="form-control form-control-sm">
            </div>
        </div>
        <div class="form-group mb-2">
            <label class="small font-weight-bold mb-0">Reviewer photo <span class="text-muted font-weight-normal">(optional)</span></label>
            <input type="file" name="reviewer_photo" class="form-control-file small" accept="image/jpeg,image/png,image/webp,image/gif">
        </div>
        <div class="form-group mb-2">
            <label class="small font-weight-bold mb-0">Description <span class="text-danger">*</span></label>
            <textarea name="body" class="form-control form-control-sm" rows="3" required minlength="5" maxlength="8000" placeholder="What the patient said about the consultation…"></textarea>
        </div>
        <div class="form-group mb-2">
            <label class="small font-weight-bold mb-0">Sort order</label>
            <input type="number" name="sort_order" class="form-control form-control-sm" value="0" min="0" max="99999" placeholder="0 = first">
        </div>
        <button type="submit" class="btn btn-primary btn-sm dr-wr-save-btn">
            <i class="fas fa-plus mr-1"></i>Save review
        </button>
        <span class="small text-muted ml-2 dr-wr-form-msg"></span>
    </form>
</div>

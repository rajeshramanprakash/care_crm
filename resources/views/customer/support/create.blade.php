@extends('customer.layouts.app')
@section('title', 'New Support Ticket')

@section('content')
@php
    $type = old('type', request('type') === 'bug' ? 'bug' : 'support');
    $category = old('category', 'service_issue');
    $priority = old('priority', 'normal');
@endphp
<style>
    .st-form label.st-label { display:block; font-weight:600; color:#3d3a35; margin-bottom:8px; }
    .st-form .st-input { width:100%; border:1.5px solid #e2ddd3; border-radius:14px; padding:12px 18px; font-size:15px; color:#3d3a35; background:#fff; outline:none; transition:border-color .15s; }
    .st-form .st-input::placeholder { color:#9a958c; }
    .st-form .st-input:focus { border-color:#d9a27a; }
    .st-form textarea.st-input { min-height:120px; resize:vertical; }
    .st-chips { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:18px; }
    .st-chips input { position:absolute; opacity:0; pointer-events:none; }
    .st-chips label { margin:0; border:1.5px solid #e2ddd3; border-radius:999px; padding:9px 20px; font-weight:600; color:#3d3a35; background:#fff; cursor:pointer; user-select:none; transition:all .15s; }
    .st-chips label:hover { border-color:#d9a27a; }
    .st-chips input:checked + label { background:#fdf0e6; border-color:#d9a27a; color:#6b3f22; }
    .st-chips input:focus-visible + label { box-shadow:0 0 0 3px rgba(217,162,122,.35); }
    .st-type { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:22px; }
    .st-type input { position:absolute; opacity:0; pointer-events:none; }
    .st-type label { flex:1 1 240px; margin:0; border:1.5px solid #e2ddd3; border-radius:14px; padding:14px 16px; cursor:pointer; background:#fff; transition:all .15s; }
    .st-type label b { display:block; color:#3d3a35; }
    .st-type label small { color:#8a857c; }
    .st-type input:checked + label { background:#fdf0e6; border-color:#d9a27a; }
    .st-type i { color:#c9773f; margin-right:6px; }
    .st-group { margin-bottom:18px; }
</style>
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card" style="border-radius:12px;border:0;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-header" style="background: linear-gradient(90deg, #ff8a00 0%, #ff7a18 100%); color:#fff; border-radius:12px 12px 0 0;">
                            <h3 class="card-title mb-0"><i class="fas fa-life-ring mr-2"></i>Create Support Ticket</h3>
                        </div>
                        <div class="card-body st-form">
                            @if ($errors->any())
                                <div class="alert alert-danger py-2">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                            @endif
                            <form method="POST" action="{{ route('customer.support.store') }}" enctype="multipart/form-data" id="st-create">
                                @csrf
                                <label class="st-label">What do you need help with?</label>
                                <div class="st-type">
                                    <input type="radio" name="type" id="type_support" value="support" @checked($type === 'support')>
                                    <label for="type_support"><b><i class="fas fa-user-cog"></i>Account issue / support request</b><small>Problem with a service, staff, billing, scheduling or your account</small></label>
                                    <input type="radio" name="type" id="type_bug" value="bug" @checked($type === 'bug')>
                                    <label for="type_bug"><b><i class="fas fa-bug"></i>Report a bug / technical problem</b><small>Something is not working in the website or app</small></label>
                                </div>

                                <div class="st-group">
                                    <label class="st-label" for="st-title">Title</label>
                                    <input type="text" id="st-title" name="subject" class="st-input" maxlength="150" value="{{ old('subject') }}" required
                                           data-ph-support="e.g. Attendant did not arrive" data-ph-bug="e.g. Payment page is not opening"
                                           placeholder="{{ $type === 'bug' ? 'e.g. Payment page is not opening' : 'e.g. Attendant did not arrive' }}">
                                </div>

                                <div data-only="support" @if($type === 'bug') hidden @endif>
                                    <label class="st-label">Category</label>
                                    <div class="st-chips">
                                        @foreach (\App\Models\SupportTicket::SUPPORT_CATEGORIES as $key => $label)
                                            <input type="radio" name="category" id="cat_{{ $key }}" value="{{ $key }}" @checked($category === $key) @disabled($type === 'bug')>
                                            <label for="cat_{{ $key }}">{{ $label }}</label>
                                        @endforeach
                                    </div>
                                </div>

                                <label class="st-label">Priority</label>
                                <div class="st-chips">
                                    @foreach (\App\Models\SupportTicket::CUSTOMER_PRIORITIES as $key => $label)
                                        <input type="radio" name="priority" id="pri_{{ $key }}" value="{{ $key }}" @checked($priority === $key)>
                                        <label for="pri_{{ $key }}">{{ $label }}</label>
                                    @endforeach
                                </div>

                                <div class="st-group">
                                    <label class="st-label" for="st-message">What happened?</label>
                                    <textarea id="st-message" name="message" class="st-input" maxlength="5000" required>{{ old('message') }}</textarea>
                                </div>

                                <div class="st-group">
                                    <label class="st-label" for="st-file">Screenshot / file <span class="text-muted font-weight-normal">(optional)</span></label>
                                    <input type="file" id="st-file" name="attachment" class="form-control-file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt">
                                    <small class="text-muted">Max 5 MB — jpg, png, webp, pdf, doc, docx, txt</small>
                                </div>

                                <a href="{{ route('customer.support.index') }}" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane mr-1"></i> Submit Ticket</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<script>
    (function () {
        var form = document.getElementById('st-create');
        var title = document.getElementById('st-title');
        function sync() {
            var bug = form.querySelector('input[name="type"]:checked').value === 'bug';
            form.querySelectorAll('[data-only="support"]').forEach(function (el) {
                el.hidden = bug;
                el.querySelectorAll('input').forEach(function (i) { i.disabled = bug; });
            });
            title.placeholder = title.getAttribute(bug ? 'data-ph-bug' : 'data-ph-support');
        }
        form.querySelectorAll('input[name="type"]').forEach(function (r) { r.addEventListener('change', sync); });
        sync();
    })();
</script>
@endsection

@extends($submitter['layout'])
@section('title', 'Speak Up')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @include('speak_up._shell_top')
                    <div class="card su-card">
                        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-bullhorn mr-2 me-2"></i>Speak Up</h3></div>
                        <div class="card-body">
                            <div class="su-note mb-3">
                                Share a concern, complaint or suggestion safely. Only the Admin can read Speak Up — your manager, HR team and Sub Admins cannot see it.
                                If you tick <b>Submit anonymously</b>, your name, ID and IP address are <b>not saved at all</b>.
                            </div>
                            @if ($errors->any())
                                <div class="alert alert-danger py-2">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                            @endif
                            <form method="POST" action="{{ route('speak_up.store') }}">
                                @csrf
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold fw-bold">Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-control form-select" required>
                                        <option value="">Select</option>
                                        @foreach (\App\Models\SpeakUpSubmission::CATEGORIES as $key => $label)
                                            <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold fw-bold">Subject <span class="text-danger">*</span></label>
                                    <input type="text" name="subject" class="form-control" maxlength="150" value="{{ old('subject') }}" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold fw-bold">Details <span class="text-danger">*</span></label>
                                    <textarea name="message" class="form-control" rows="7" maxlength="5000" required>{{ old('message') }}</textarea>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="hidden" name="is_anonymous" value="0">
                                    <input type="checkbox" class="form-check-input" id="suAnon" name="is_anonymous" value="1" @checked(old('is_anonymous', '0') === '1')>
                                    <label class="form-check-label font-weight-bold fw-bold" for="suAnon">Submit anonymously</label>
                                    <div class="small text-muted" id="suAnonHelp"></div>
                                </div>
                                <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane"></i> Submit</button>
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
        var box = document.getElementById('suAnon'), help = document.getElementById('suAnonHelp');
        var name = @json($submitter['name']);
        function render() {
            help.textContent = box.checked
                ? 'Your identity will not be stored. It will be saved only on this device for tracking in My Submissions — also note the reference number and follow-up key.'
                : 'Admin will see your name: ' + name;
        }
        box.addEventListener('change', render);
        render();
    })();
</script>
@endsection

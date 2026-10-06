<div class="d-flex flex-wrap align-items-center mb-3" style="gap:6px;">
    <h4 class="mb-0 me-3 mr-3 fw-bold font-weight-bold"><i class="fas fa-user-shield"></i> Speak Up — Confidential</h4>
    <a href="{{ route('speak_up.review.cases', $slug) }}" class="btn btn-sm {{ request()->routeIs('speak_up.review.cases', 'speak_up.review.show') ? 'btn-dark' : 'btn-outline-dark' }}">Cases</a>
    <a href="{{ route('speak_up.review.access_log', $slug) }}" class="btn btn-sm {{ request()->routeIs('speak_up.review.access_log') ? 'btn-dark' : 'btn-outline-dark' }}">Access Log</a>
    <form method="POST" action="{{ route('speak_up.review.lock', $slug) }}" class="ms-auto ml-auto">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-lock"></i> Lock</button>
    </form>
</div>

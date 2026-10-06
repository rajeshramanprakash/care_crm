<style>
    .su-card { border: 0; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
    .su-card .card-header { background: #2f3e46; color: #fff; border-radius: 12px 12px 0 0; }
    .su-tabs a { margin-right: 6px; margin-bottom: 6px; }
    .su-note { background: #f1f8f4; border-left: 4px solid #2e7d32; padding: 10px 12px; border-radius: 6px; font-size: 14px; }
    .su-key { font-family: monospace; font-size: 22px; letter-spacing: 2px; background: #fff8e1; border: 1px dashed #f0a500; padding: 8px 14px; border-radius: 8px; display: inline-block; }
    .su-reply { background: #f1f3f5; border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; white-space: pre-wrap; word-break: break-word; }
</style>
<div class="su-tabs mb-3">
    <a href="{{ route('speak_up.create') }}" class="btn btn-sm {{ request()->routeIs('speak_up.create') ? 'btn-dark' : 'btn-outline-dark' }}"><i class="fas fa-bullhorn"></i> Speak Up</a>
    <a href="{{ route('speak_up.mine') }}" class="btn btn-sm {{ request()->routeIs('speak_up.mine', 'speak_up.mine.show') ? 'btn-dark' : 'btn-outline-dark' }}"><i class="fas fa-list"></i> My Submissions</a>
    <a href="{{ route('speak_up.status') }}" class="btn btn-sm {{ request()->routeIs('speak_up.status', 'speak_up.status.check') ? 'btn-dark' : 'btn-outline-dark' }}"><i class="fas fa-search"></i> Check Status</a>
</div>

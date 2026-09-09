@extends($layout ?? 'admin.layouts.app')

@section('header-css')
<style>
    .content-wrapper .content.bcc-crm-chat-section { padding-top: 0.5rem; padding-bottom: 0; }
</style>
@endsection

@section('main')
<div class="content-wrapper">
    <section class="content-header pb-2">
        <div class="container-fluid">
            <h1 class="m-0">B2B Corporate Chat</h1>
            <p class="text-muted small mb-0">Shared group chat with corporate partners you are assigned to — all group members see the same messages.</p>
        </div>
    </section>
    <section class="content bcc-crm-chat-section">
        <div class="container-fluid px-2">
            @include('partials.b2b-corporate-chat-shell', [
                'chatMode' => $chatMode,
                'chatLayout' => $chatLayout ?? 'group',
                'contacts' => $contacts,
                'themePrimary' => $themePrimary,
                'themePrimaryDark' => $themePrimaryDark,
                'routes' => $routes,
                'sidebarTitle' => $sidebarTitle,
                'sidebarHint' => $sidebarHint,
                'groupName' => $groupName,
                'groupMembers' => $groupMembers ?? [],
                'currentStaffUserId' => $currentStaffUserId ?? null,
            ])
        </div>
    </section>
</div>
@endsection

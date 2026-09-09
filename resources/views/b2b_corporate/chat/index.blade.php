@extends('b2b_corporate.layouts.app')

@section('page_title', 'Chat')

@section('header-css')
<style>
    .b2b-content-card.b2b-chat-fullpage {
        padding: 0 !important;
        overflow: hidden;
        border: none;
        box-shadow: none;
        background: transparent;
    }
</style>
@endsection

@section('content')
<div class="b2b-content-card b2b-chat-fullpage">
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
@endsection

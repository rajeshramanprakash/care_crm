@extends('broker.layouts.app')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
@include('partials.partner-portal-dashboard-details', ['user' => $user, 'portal_type' => 'broker'])
@endsection

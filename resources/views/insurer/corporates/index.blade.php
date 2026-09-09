@extends('insurer.layouts.app')
@section('title', 'Corporate Accounts')
@section('page_title', 'Corporate Accounts')

@section('content')
@include('partner-portal.corporates.index', ['items' => $items, 'partner_type' => 'insurer', 'login_url' => $login_url])
@endsection

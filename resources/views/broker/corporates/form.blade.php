@extends('broker.layouts.app')
@section('title', $item->exists ? 'Edit Corporate' : 'Add Corporate')
@section('page_title', $item->exists ? 'Edit Corporate' : 'Add Corporate')

@section('content')
@include('partner-portal.corporates.form', ['item' => $item, 'partner_type' => 'broker', 'login_url' => $login_url])
@endsection

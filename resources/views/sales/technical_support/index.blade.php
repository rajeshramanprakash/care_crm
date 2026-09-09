@extends('sales.layouts.app')

@section('title', 'Technical Support')

@section('main')
    <div class="content-wrapper" style="height: 100vh;">
        <iframe 
            src="https://crm.anohim.in/forms/project_tickets/5?name={{ Auth::user()->f_name }} {{ Auth::user()->l_name }}&email=singrohaashish420@gmail.com" 
            width="100%" 
            height="100%" 
            style="border: none;"
            title="Project Tickets"
        ></iframe>
    </div>
@endsection

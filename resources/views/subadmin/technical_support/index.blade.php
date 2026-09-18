@extends('admin.layouts.app')

@section('title', 'Technical Support')

@section('main')
    <div class="content-wrapper" style="height: 100vh; display: flex; flex-direction: column;">
        <div class="alert alert-warning m-3 d-flex justify-content-between align-items-center">
            <span><strong>Note:</strong> If the form below is blocked by your browser (shows "Content is blocked"), please open it in a new tab.</span>
            <a href="https://crm.anohim.in/forms/project_tickets/5?name={{ Auth::user()->f_name }} {{ Auth::user()->l_name }}&email=singrohaashish420@gmail.com" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-external-link-alt"></i> Open in New Tab
            </a>
        </div>
        <iframe 
            src="https://crm.anohim.in/forms/project_tickets/5?name={{ Auth::user()->f_name }} {{ Auth::user()->l_name }}&email=singrohaashish420@gmail.com" 
            width="100%" 
            style="border: none; flex-grow: 1;"
            title="Project Tickets"
        ></iframe>
    </div>
@endsection

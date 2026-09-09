@extends('operation.layouts.app')

@section('header-css')
<style>
    body {
        overflow-x: hidden;
        background: #f8fafc;
    }

    .content-wrapper {
        background: #f8fafc !important;
        padding: 20px 0;
        max-height: 100vh;
        overflow-y: hidden;
    }

    .dashboard-content-wrapper {
        max-height: calc(100vh - 100px);
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 10px;
    }

    /* Custom Scrollbar for Dashboard Content */
    .dashboard-content-wrapper::-webkit-scrollbar {
        width: 8px;
    }

    .dashboard-content-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .dashboard-content-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
        transition: background 0.3s ease;
    }

    .dashboard-content-wrapper::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .dashboard-content-wrapper::-webkit-scrollbar-corner {
        background: #f1f5f9;
    }

    .dashboard-header {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
    }

    .dashboard-title {
        color: #1F2937;
        font-size: 24px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .dashboard-subtitle {
        color: #6B7280;
        font-size: 14px;
        margin: 8px 0 0 0;
    }

    /* Search Bar Styling */
    .search-container .input-group {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .search-container .input-group-text {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-right: none;
        color: #6c757d;
    }

    .search-container .form-control {
        border: 1px solid #e9ecef;
        border-left: none;
        border-right: none;
        padding: 12px 16px;
        font-size: 14px;
        background: white;
    }

    .search-container .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        background: white;
    }

    .search-container .btn-outline-secondary {
        border: 1px solid #e9ecef;
        border-left: none;
        background: #f8f9fa;
        color: #6c757d;
        padding: 12px 16px;
    }

    .search-container .btn-outline-secondary:hover {
        background: #e9ecef;
        color: #495057;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    @media (min-width: 1200px) {
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    @media (min-width: 1400px) {
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    .dashboard-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        padding: 20px;
        border: none;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .dashboard-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .dashboard-card:hover::before {
        opacity: 1;
    }

    .card-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .dashboard-card:hover .card-icon {
        transform: scale(1.1);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    .card-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-info {
        display: flex;
        align-items: center;
        flex: 1;
    }

    .card-title {
        color: #6B7280;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .card-count {
        color: #1F2937;
        font-size: 13px;
        font-weight: 800;
        margin: 0;
        line-height: 1;
        letter-spacing: -0.5px;
    }

    .leads-section {
        height: 410px !important;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .table-header {
        background: #f8fafc;
        padding: 8px 14px;
        border-bottom: 1px solid #e5e7eb;
    }

    .table-title {
        color: #1F2937;
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
    }

    .table-scroll-container {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 375px;
        width: 100%;
        position: relative;
    }

    /* Custom Scrollbar Styling */
    .table-scroll-container::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .table-scroll-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .table-scroll-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
        transition: background 0.3s ease;
    }

    .table-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .table-scroll-container::-webkit-scrollbar-corner {
        background: #f1f5f9;
    }

    .custom-table th {
        background: #f9fafb;
        color: #374151;
        font-weight: 600;
        font-size: 12px;
        padding: 14px 20px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .custom-table td {
        padding: 14px 20px;
        border-bottom: 1px solid #f3f4f6;
        color: #1F2937;
        font-size: 13px;
        vertical-align: middle;
    }

    .custom-table tr:hover {
        background: #f9fafb;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .status-badge::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .badge-follow-up {
        color: #f39c12;
        border: 1.5px solid #f39c12 !important;
        background: #fbe9a8 !important;
    }

    .badge-closed {
        color: #e74c3c;
        border: 1.5px solid #e74c3c !important;
        background: #f0665738 !important;
    }

    .badge-profile-shared {
        color: #3498db;
        border: 1.5px solid #3498db !important;
        background: #bdd9ee !important;
    }

    .action-btn {
        padding: 8px;
        margin: 0 2px;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: white;
    }

    .action-btn:hover {
        border-color: #d1d5db;
        background: #f9fafb;
    }

    .action-btn i {
        font-size: 14px;
    }

    .view-btn {
        color: #6b7280;
    }

    .view-btn:hover {
        color: #374151;
        background: #f3f4f6;
    }

    .edit-btn {
        color: #6b7280;
    }

    .edit-btn:hover {
        color: #374151;
        background: #f3f4f6;
    }

    .delete-btn {
        color: #ef4444;
    }

    .delete-btn:hover {
        color: #dc2626;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .start-btn {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }

    .start-btn:hover {
        background: #059669;
        border-color: #059669;
    }

    .complete-btn {
        background: #059669;
        color: white;
        border-color: #059669;
    }

    .complete-btn:hover {
        background: #047857;
        border-color: #047857;
    }

    .cancel-btn {
        background: #dc2626;
        color: white;
        border-color: #dc2626;
    }

    .cancel-btn:hover {
        background: #b91c1c;
        border-color: #b91c1c;
    }

    .empty-state {
        padding: 3rem 1rem;
        text-align: center;
    }

    .empty-icon {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border: 2px solid #e2e8f0;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .btn {
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .modal-content {
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 1200px;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.2s ease;
    }

    .modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .modal-body {
        padding: 24px;
        max-height: calc(90vh - 120px);
        overflow-y: auto;
    }

    .stats-tabs {
        display: flex;
        background: #f8fafc;
        border-radius: 8px;
        padding: 4px;
        margin-bottom: 16px;
    }

    .stats-tab {
        flex: 1;
        padding: 7px 16px;
        background: transparent;
        border: none;
        border-radius: 6px;
        color: #6B7280;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .stats-tab.active {
        background: white;
        color: #667eea;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .period-selector {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px 16px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
        margin-bottom: 16px;
        display: none;
    }

    .period-selector.active {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .period-selector:hover {
        border-color: #667eea;
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.1);
    }

    .date-filter-label {
        color: #374151;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
    }

    .date-input {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        color: #374151;
        background: white;
        transition: border-color 0.2s ease;
        min-width: 150px;
    }

    .date-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .filter-btn {
        background: #667eea;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .filter-btn:hover {
        background: #5a67d8;
        transform: translateY(-1px);
    }

    .stats-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stats-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 8px;
        text-align: center;
    }

    .stats-card-title {
        color: #6B7280;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0px;
    }

    .stats-card-value {
        color: #1F2937;
        font-size: 28px;
        font-weight: 800;
        margin: 0;
    }

    .leads-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .leads-table th {
        background: #f9fafb;
        color: #374151;
        font-weight: 600;
        font-size: 12px;
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .leads-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f3f4f6;
        color: #1F2937;
        font-size: 13px;
        vertical-align: middle;
    }

    .leads-table tr:hover {
        background: #f9fafb;
    }

    /* Profile Pending Statistics Table Responsive */
    #profilePendingTableContainer {
        overflow-x: auto;
    }

    #profilePendingTableContainer .leads-table {
        min-width: 1000px;
    }

    /* Deployment Pending Details Table Responsive */
    #deploymentPendingTableContainer {
        overflow-x: auto;
    }

    #deploymentPendingTableContainer .leads-table {
        min-width: 1200px;
    }

    /* Pending Callbacks Table Responsive (same columns as Sales) */
    #pendingCallbacksTableContainer {
        overflow-x: auto;
        max-height: 400px;
        overflow-y: auto;
    }

    #pendingCallbacksTableContainer .leads-table {
        min-width: 1100px;
    }

    .action-column {
        white-space: nowrap;
        min-width: 120px;
    }

    .action-buttons-container {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        align-items: center;
    }

    .loading-spinner-modal {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid rgba(102, 126, 234, 0.3);
        border-radius: 50%;
        border-top-color: #667eea;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dashboard-card {
        animation: fadeInUp 0.6s ease forwards;
    }

    .dashboard-card:nth-child(1) { animation-delay: 0.1s; }
    .dashboard-card:nth-child(2) { animation-delay: 0.2s; }
    .dashboard-card:nth-child(3) { animation-delay: 0.3s; }
    .dashboard-card:nth-child(4) { animation-delay: 0.4s; }
    .dashboard-card:nth-child(5) { animation-delay: 0.5s; }
    .dashboard-card:nth-child(6) { animation-delay: 0.6s; }
    .dashboard-card:nth-child(7) { animation-delay: 0.7s; }
    .dashboard-card:nth-child(8) { animation-delay: 0.8s; }

    /* Calendar Styles */
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #e5e7eb;
        border-radius: 6px;
        overflow: hidden;
    }

    .calendar-day-header {
        background: #f8fafc;
        padding: 8px 4px;
        text-align: center;
        font-weight: 600;
        color: #6b7280;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .calendar-day {
        background: white;
        min-height: 45px;
        padding: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
    }

    .calendar-day:hover {
        background: #f8fafc;
        transform: scale(1.02);
    }

    .calendar-day.other-month {
        background: #f9fafb;
        color: #9ca3af;
    }

    .calendar-day.today {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 2px solid #f59e0b;
    }

    .calendar-day.selected {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        border: 2px solid #3b82f6;
    }

    .day-number {
        font-weight: 600;
        font-size: 0.75rem;
        margin-bottom: 1px;
    }

    .day-counts {
        display: flex;
        flex-direction: column;
        gap: 1px;
        width: 100%;
    }

    .count-badge {
        font-size: 0.6rem;
        padding: 1px 3px;
        border-radius: 6px;
        font-weight: 500;
        text-align: center;
        color: white;
        min-width: 14px;
    }

    .count-badge.follow-up {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    }

    /* Active Calls Table Styles */
    #activeCallsCard {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        animation: slideDown 0.5s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    #activeCallsTable {
        margin-bottom: 0;
    }

    #activeCallsTable thead {
        background: #f8fafc;
    }

    #activeCallsTable thead th {
        font-weight: 600;
        color: #374151;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e5e7eb;
        padding: 12px 10px;
    }

    #activeCallsTable tbody td {
        vertical-align: middle;
        padding: 12px 10px;
        font-size: 0.9rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .call-status-active {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        border-left: 4px solid #22c55e;
        animation: pulseRow 2s infinite;
    }

    .call-status-recent {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border-left: 4px solid #f59e0b;
    }

    @keyframes pulseRow {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
        }
        50% {
            box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
        }
    }

    .call-status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .call-status-indicator.active {
        background: #22c55e;
        color: white;
    }

    .call-status-indicator.recent {
        background: #f59e0b;
        color: white;
    }

    .call-status-indicator .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: white;
        animation: pulseDot 1.5s infinite;
    }

    @keyframes pulseDot {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.5;
            transform: scale(1.2);
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-card {
            padding: 20px;
        }

        .card-count {
            font-size: 24px;
        }

        .action-btn {
            padding: 6px;
            font-size: 12px;
            margin: 0 1px;
            width: 28px;
            height: 28px;
        }

        .action-btn i {
            font-size: 12px;
        }

        .action-column {
            min-width: 150px;
        }

        .action-buttons-container {
            flex-direction: column;
            gap: 2px;
        }
    }

    /* Verify Payment Checkbox Styling */
    .verify-payment-checkbox-dashboard {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #28a745;
    }

    .verify-payment-checkbox-dashboard:hover {
        transform: scale(1.1);
        transition: transform 0.2s ease;
    }
</style>
@endsection

@section('content')
<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Dashboard Header -->
        {{-- <div class="dashboard-header">
            <h1 class="dashboard-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 13H11V3H3V13ZM3 21H11V15H3V21ZM13 21H21V11H13V21ZM13 3V9H21V3H13Z" fill="#667eea"/>
                </svg>
                Operation Dashboard
            </h1>
            <p class="dashboard-subtitle">Welcome back, {{ $user->f_name }}! Here's your operation leads overview.</p>
        </div> --}}

        <!-- Dashboard Content Wrapper with Vertical Scroll -->
        <div class="dashboard-content-wrapper">
            <!-- Main Stats Card -->
            <div class="stats-grid">
                <div class="dashboard-card clickable-card" onclick="openOperationLeadsModal()" style="cursor: pointer;">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" fill="white"/>
                                    <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" fill="white"/>
                                    <path d="M12 7L12 11" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M9 10L12 7L15 10" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div>
                                <p class="card-title">Operation Leads</p>
                                <h3 class="card-count">{{ number_format($todayOperationLeads) }}</h3>
                            </div>
                        </div>
                        <div class="card-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                </div>


            <div class="dashboard-card clickable-card" onclick="openTasksModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">My Tasks</p>
                            <h3 class="card-count">{{ number_format($totalTasks) }}</h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card clickable-card" onclick="openCalendarModalOperation()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 2V5M16 2V5M3.5 9.09H20.5M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M15.6947 13.7H15.7037M15.6947 16.7H15.7037M11.9955 13.7H12.0045M11.9955 16.7H12.0045M8.29431 13.7H8.30329M8.29431 16.7H8.30329" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Follow-up Calendar</p>
                            <h3 class="card-count" id="operationCalendarCount" style="font-size: 18px; font-weight: 600;">
                                <i class="fas fa-spinner fa-spin"></i> Loading...
                            </h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card clickable-card" onclick="openOutstandingPaymentsModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="white"/>
                                <path d="M12 1C5.93 1 1 5.93 1 12s4.93 11 11 11 11-4.93 11-11S18.07 1 12 1zm0 20c-4.97 0-9-4.03-9-9s4.03-9 9-9 9 4.03 9 9-4.03 9-9 9z" stroke="white" stroke-width="2"/>
                                <path d="M12 6v6l4 2" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Outstanding Payments</p>
                            <h3 class="card-count">₹{{ number_format($outstandingPayments, 2) }}</h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card clickable-card" onclick="openDeploymentPendingModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Deployment Pending</p>
                            <h3 class="card-count" style="font-size: 18px; font-weight: 600;">
                                <div style="font-size: 12px; line-height: 1.3;">
                                    <div>Mine: <strong style="color: #10b981;">{{ number_format($myDeploymentPending) }}</strong></div>
                                    <div>Team: <strong style="color: #3b82f6;">{{ number_format($teamDeploymentPending) }}</strong></div>
                                </div>
                            </h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card clickable-card" onclick="openProfilePendingModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" fill="white"/>
                                <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" fill="white"/>
                                <path d="M12 7L12 11" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                <path d="M9 10L12 7L15 10" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Profile Pending</p>
                            <h3 class="card-count" style="font-size: 18px; font-weight: 600;">
                                <div style="font-size: 12px; line-height: 1.3;">
                                    <div>Mine: <strong style="color: #10b981;">{{ number_format($myProfilePending) }}</strong></div>
                                    <div>Team: <strong style="color: #3b82f6;">{{ number_format($teamProfilePending) }}</strong></div>
                                </div>
                            </h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card clickable-card" onclick="openOngoingStoppedModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" fill="white"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Ongoing/Stopped</p>
                            <h3 class="card-count">{{ number_format($ongoingLeads) }}</h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card clickable-card" onclick="openPaymentDueModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="white"/>
                                <path d="M12 1C5.93 1 1 5.93 1 12s4.93 11 11 11 11-4.93 11-11S18.07 1 12 1zm0 20c-4.97 0-9-4.03-9-9s4.03-9 9-9 9 4.03 9 9-4.03 9-9 9z" stroke="white" stroke-width="2"/>
                                <path d="M12 6v6l4 2" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Payment Due</p>
                            <h3 class="card-count">{{ number_format($unverifiedPayments) }}</h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
        </div>

            <div class="dashboard-card clickable-card" onclick="openJobRequestModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 6L9 17L4 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 2L20 6L16 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8 2L4 6L8 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Job Requests</p>
                            <h3 class="card-count">{{ number_format($nonActiveJobRequests) }}</h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card clickable-card" onclick="openVendorFreelancerPaymentModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="white"/>
                                <path d="M12 1C5.93 1 1 5.93 1 12s4.93 11 11 11 11-4.93 11-11S18.07 1 12 1zm0 20c-4.97 0-9-4.03-9-9s4.03-9 9-9 9 4.03 9 9-4.03 9-9 9z" stroke="white" stroke-width="2"/>
                                <path d="M12 6v6l4 2" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Vendor & Freelancer Payment</p>
                            <h3 class="card-count">{{ number_format($unverifiedVendorFreelancerPayments) }}</h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card clickable-card" onclick="openRecentLeadsModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #ec4899, #be185d);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="white"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Recent Leads</p>
                            <h3 class="card-count">25</h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card clickable-card" onclick="openUnverifiedDeploymentModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #ef4444, #b91c1c);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="white"/>
                                <path d="M12 8v4" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                <path d="M12 16h.01" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Unverified Payments</p>
                            <h3 class="card-count">{{ number_format($unverifiedPayments) }}</h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card clickable-card" onclick="openPendingCallbacksModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 2L14 6L18 8L16 2Z" fill="white"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Pending Callbacks</p>
                            <h3 class="card-count" id="pendingCallbacksCount">0</h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="dashboard-card clickable-card" onclick="openRecentCallsModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Recent Calls</p>
                            <h3 class="card-count" id="recentCallsCount">0</h3>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

            <!-- Active Calls Table -->
            <div class="card mb-5" id="activeCallsCard">
                <div class="card-body">
                    <h5 class="mb-3 d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-phone-volume text-success"></i>
                            Active & Recent Calls (Last 10 Minutes)
                        </span>
                        <span class="badge bg-success" id="activeCallsMonitorBadge">
                            <i class="fas fa-circle" style="font-size: 8px; animation: pulseDot 1.5s infinite;"></i>
                            Monitoring...
                        </span>
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="activeCallsTable">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Lead No</th>
                                    <th>Customer Name</th>
                                    <th>Contact No</th>
                                    <th>Location</th>
                                    <th>Query</th>
                                    <th>Lead Status</th>
                                    <th>Call Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="activeCallsTableBody">
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        <i class="fas fa-phone fa-2x mb-2"></i>
                                        <br>No active calls at the moment
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Dashboard Content Wrapper -->

    </div>
</div>

<!-- Operation Leads Modal -->
<div id="operationLeadsModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Operation Leads Statistics</h3>
            <button class="modal-close" onclick="closeModal('operationLeadsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="stats-tabs">
                <button class="stats-tab active" onclick="switchStatsTab('today')">Today</button>
                <button class="stats-tab" onclick="switchStatsTab('monthly')">Monthly</button>
                <button class="stats-tab" onclick="switchStatsTab('yearly')">Yearly</button>
                <button class="stats-tab" onclick="switchStatsTab('custom')">Custom Range</button>
            </div>

            <div class="period-selector active" id="todaySelector">
                <div class="date-filter-label">Today's Leads:</div>
                <div style="color: #667eea; font-weight: 600;">{{ date('d M Y') }}</div>
            </div>

            <div class="period-selector" id="monthlySelector">
                <div class="date-filter-label">Select Month:</div>
                <select id="monthSelect" class="date-input" onchange="loadOperationLeadsData('monthly', null, null, this.value)">
                    <option value="">Loading months...</option>
                </select>
            </div>

            <div class="period-selector" id="yearlySelector">
                <div class="date-filter-label">Select Year:</div>
                <select id="yearSelect" class="date-input" onchange="loadOperationLeadsData('yearly', null, null, this.value)">
                    <option value="">Loading years...</option>
                </select>
            </div>

            <div class="period-selector" id="customSelector">
                <div class="date-filter-label">Date Range:</div>
                <input type="date" id="startDate" class="date-input" placeholder="Start Date" max="">
                <span style="color: #6B7280;">to</span>
                <input type="date" id="endDate" class="date-input" placeholder="End Date" max="">
                <button class="filter-btn" onclick="applyDateFilter()">Apply Filter</button>
            </div>

            <div class="stats-summary" id="statsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Leads</div>
                    <div class="stats-card-value" id="totalLeads">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Average Per Day</div>
                    <div class="stats-card-value" id="avgPerDay">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Period</div>
                    <div class="stats-card-value" id="period">-</div>
                </div>
            </div>

            <div id="leadsTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading leads data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Management Modal -->
<div id="tasksModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Task Management</h3>
            <button class="modal-close" onclick="closeModal('tasksModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Add New Task Button -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">My Tasks</h5>
                <button type="button" class="btn btn-primary" onclick="openAddTaskModal()">
                    <i class="fas fa-plus"></i> Add New Task
                </button>
            </div>

            <!-- Task Stats Summary -->
            <div class="stats-summary mb-4">
                <div class="stats-card">
                    <div class="stats-card-title">Total Tasks</div>
                    <div class="stats-card-value">{{ number_format($totalTasks) }}</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Pending</div>
                    <div class="stats-card-value">{{ number_format($pendingTasks) }}</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Completed</div>
                    <div class="stats-card-value">{{ number_format($completedTasks) }}</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Overdue</div>
                    <div class="stats-card-value">{{ number_format($overdueTasks) }}</div>
                </div>
            </div>

            <!-- Task Tabs -->
            <div class="stats-tabs">
                <button class="stats-tab active" onclick="switchTasksTab('assigned_to_me')">My Tasks</button>
                <button class="stats-tab" onclick="switchTasksTab('assigned_by_me')">Created by Me</button>
                <button class="stats-tab" onclick="switchTasksTab('parent_manager_tasks')">Parent Manager Tasks</button>
                <button class="stats-tab" onclick="switchTasksTab('all')">All Tasks</button>
                <button class="stats-tab" onclick="switchTasksTab('history')">Task History</button>
            </div>

            <!-- Date Filter -->
            <div class="period-selector active" id="taskDateFilter">
                <div class="date-filter-label">Filter by Date:</div>
                <input type="date" id="taskStartDate" class="date-input" placeholder="From Date">
                <span style="color: #6B7280;">to</span>
                <input type="date" id="taskEndDate" class="date-input" placeholder="To Date">
                <button class="filter-btn" onclick="applyTaskDateFilter()">Apply Filter</button>
                <button class="filter-btn" onclick="clearTaskDateFilter()" style="background: #6b7280;">Clear</button>
            </div>

            <!-- Tasks Table -->
            <div id="tasksTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading tasks data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div id="addTaskModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">Add New Task</h3>
            <button class="modal-close" onclick="closeModal('addTaskModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addTaskForm">
                <div class="mb-3">
                    <label for="taskTitle" class="form-label">Task Title</label>
                    <input type="text" class="form-control" id="taskTitle" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="taskDescription" class="form-label">Description</label>
                    <textarea class="form-control" id="taskDescription" name="description" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="taskPriority" class="form-label">Priority</label>
                    <select class="form-control" id="taskPriority" name="priority" required>
                        <option value="">Select Priority</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="taskDueDate" class="form-label">Due Date</label>
                    <input type="date" class="form-control" id="taskDueDate" name="due_date" required>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="padding: 20px 24px; border-top: 1px solid #e5e7eb;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('addTaskModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitTaskForm()">Create Task</button>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div id="editTaskModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">Edit Task</h3>
            <button class="modal-close" onclick="closeModal('editTaskModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editTaskForm">
                <input type="hidden" id="editTaskId" name="task_id">
                <div class="mb-3">
                    <label for="editTaskTitle" class="form-label">Task Title</label>
                    <input type="text" class="form-control" id="editTaskTitle" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="editTaskDescription" class="form-label">Description</label>
                    <textarea class="form-control" id="editTaskDescription" name="description" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="editTaskPriority" class="form-label">Priority</label>
                    <select class="form-control" id="editTaskPriority" name="priority" required>
                        <option value="">Select Priority</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editTaskDueDate" class="form-label">Due Date</label>
                    <input type="date" class="form-control" id="editTaskDueDate" name="due_date" required>
                </div>
                <div class="mb-3">
                    <label for="editTaskStatus" class="form-label">Status</label>
                    <select class="form-control" id="editTaskStatus" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="padding: 20px 24px; border-top: 1px solid #e5e7eb;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('editTaskModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitEditTaskForm()">Update Task</button>
        </div>
    </div>
</div>

<!-- View Task Modal -->
<div id="viewTaskModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">Task Details</h3>
            <button class="modal-close" onclick="closeModal('viewTaskModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="viewTaskContent">
                <!-- Task details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Operation Calendar Modal -->
<div id="operationCalendarModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3 class="modal-title">Follow-up Calendar</h3>
            <button class="modal-close" onclick="closeModal('operationCalendarModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Calendar Navigation -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm" id="prevMonthOperation">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h5 class="mb-0" id="currentMonthYearOperation"></h5>
                <button type="button" class="btn btn-outline-primary btn-sm" id="nextMonthOperation">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <!-- Calendar Legend -->
            <div class="d-flex justify-content-center gap-4 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary" style="width: 14px; height: 14px; border-radius: 50%;"></span>
                    <span class="text-sm">Follow-up Leads</span>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-grid" id="calendarGridOperation">
                <!-- Calendar will be generated here -->
            </div>

            <!-- View Leads Button -->
            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary" id="viewLeadsBtnOperation" onclick="openCalendarLeadsModalOperation()" disabled>
                    <i class="fas fa-list"></i> View Leads for Selected Date
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Operation Calendar Leads Modal -->
<div id="operationCalendarLeadsModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 1000px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-list"></i>
                <span id="operationCalendarLeadsTitle">Leads for Selected Date</span>
            </h3>
            <button class="modal-close" onclick="closeModal('operationCalendarLeadsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="leads-table" id="operationCalendarLeadsTable">
                    <thead>
                        <tr>
                            <th>Lead ID</th>
                            <th>Customer</th>
                            <th>Contact No</th>
                            <th>Location</th>
                            <th>Query</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="operationCalendarLeadsTableBody">
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                <i class="fas fa-calendar-alt fa-2x mb-3"></i>
                                <br>Select a date from the calendar to view leads
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Outstanding Payments Modal -->
<div id="outstandingPaymentsModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Outstanding Payments Statistics</h3>
            <button class="modal-close" onclick="closeModal('outstandingPaymentsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="stats-tabs">
                <button class="stats-tab active" onclick="switchOutstandingPaymentsTab('today')">Today</button>
                <button class="stats-tab" onclick="switchOutstandingPaymentsTab('monthly')">Monthly</button>
                <button class="stats-tab" onclick="switchOutstandingPaymentsTab('yearly')">Yearly</button>
                <button class="stats-tab" onclick="switchOutstandingPaymentsTab('custom')">Custom Range</button>
            </div>

            <div class="period-selector active" id="outstandingTodaySelector">
                <div class="date-filter-label">Today's Outstanding Payments:</div>
                <div style="color: #667eea; font-weight: 600;">{{ date('d M Y') }}</div>
            </div>

            <div class="period-selector" id="outstandingMonthlySelector">
                <div class="date-filter-label">Select Month:</div>
                <select id="outstandingMonthSelect" class="date-input" onchange="loadOutstandingPaymentsData('monthly', null, null, this.value)">
                    <option value="">Loading months...</option>
                </select>
            </div>

            <div class="period-selector" id="outstandingYearlySelector">
                <div class="date-filter-label">Select Year:</div>
                <select id="outstandingYearSelect" class="date-input" onchange="loadOutstandingPaymentsData('yearly', null, null, this.value)">
                    <option value="">Loading years...</option>
                </select>
            </div>

            <div class="period-selector" id="outstandingCustomSelector">
                <div class="date-filter-label">Date Range:</div>
                <input type="date" id="outstandingStartDate" class="date-input" placeholder="Start Date" max="">
                <span style="color: #6B7280;">to</span>
                <input type="date" id="outstandingEndDate" class="date-input" placeholder="End Date" max="">
                <button class="filter-btn" onclick="applyOutstandingDateFilter()">Apply Filter</button>
            </div>

            <div class="stats-summary" id="outstandingStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Outstanding</div>
                    <div class="stats-card-value" id="totalOutstanding">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Total Invoices</div>
                    <div class="stats-card-value" id="totalPayments">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Average Outstanding</div>
                    <div class="stats-card-value" id="avgOutstanding">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Period</div>
                    <div class="stats-card-value" id="outstandingPeriod">-</div>
                </div>
            </div>

            <div id="outstandingPaymentsTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading outstanding payments data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deployment Pending Modal -->
<div id="deploymentPendingModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Deployment Pending Statistics</h3>
            <button class="modal-close" onclick="closeModal('deploymentPendingModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- User Filter Tabs -->
            <div class="stats-tabs">
                <button class="stats-tab active" onclick="switchDeploymentPendingUserTab('mine')">Mine</button>
                <button class="stats-tab" onclick="switchDeploymentPendingUserTab('team')">Team</button>
                <button class="stats-tab" onclick="switchDeploymentPendingUserTab('all')">All</button>
            </div>

            <div class="stats-summary" id="deploymentStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Pending</div>
                    <div class="stats-card-value" id="totalPending">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Total Deployments</div>
                    <div class="stats-card-value" id="totalDeployments">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Pending %</div>
                    <div class="stats-card-value" id="pendingPercentage">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Filter</div>
                    <div class="stats-card-value" id="deploymentFilter">Mine</div>
                </div>
            </div>

            <div id="deploymentPendingTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading deployment pending data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Pending Modal -->
<div id="profilePendingModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Profile Pending Statistics</h3>
            <button class="modal-close" onclick="closeModal('profilePendingModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- User Filter Tabs -->
            <div class="stats-tabs">
                <button class="stats-tab active" onclick="switchProfilePendingUserTab('mine')">Mine</button>
                <button class="stats-tab" onclick="switchProfilePendingUserTab('team')">Team</button>
                <button class="stats-tab" onclick="switchProfilePendingUserTab('all')">All</button>
            </div>

            <div class="stats-summary" id="profileStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Profile Pending</div>
                    <div class="stats-card-value" id="totalProfilePending">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Total Leads</div>
                    <div class="stats-card-value" id="totalLeads">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Profile Pending %</div>
                    <div class="stats-card-value" id="profilePendingPercentage">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Filter</div>
                    <div class="stats-card-value" id="profileFilter">Mine</div>
                </div>
            </div>

            <div id="profilePendingTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading profile pending data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ongoing/Stopped Modal -->
<div id="ongoingStoppedModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Ongoing/Stopped Statistics</h3>
            <button class="modal-close" onclick="closeModal('ongoingStoppedModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="stats-tabs">
                <button class="stats-tab active" onclick="switchOngoingStoppedTab('today')">Today</button>
                <button class="stats-tab" onclick="switchOngoingStoppedTab('monthly')">Monthly</button>
                <button class="stats-tab" onclick="switchOngoingStoppedTab('yearly')">Yearly</button>
                <button class="stats-tab" onclick="switchOngoingStoppedTab('custom')">Custom Range</button>
            </div>

            <div class="period-selector active" id="ongoingTodaySelector">
                <div class="date-filter-label">Today's Ongoing Leads:</div>
                <div style="color: #667eea; font-weight: 600;">{{ date('d M Y') }}</div>
            </div>

            <div class="period-selector" id="ongoingMonthlySelector">
                <div class="date-filter-label">Select Month:</div>
                <select id="ongoingMonthSelect" class="date-input" onchange="loadOngoingStoppedData('monthly', null, null, this.value)">
                    <option value="">Loading months...</option>
                </select>
            </div>

            <div class="period-selector" id="ongoingYearlySelector">
                <div class="date-filter-label">Select Year:</div>
                <select id="ongoingYearSelect" class="date-input" onchange="loadOngoingStoppedData('yearly', null, null, this.value)">
                    <option value="">Loading years...</option>
                </select>
            </div>

            <div class="period-selector" id="ongoingCustomSelector">
                <div class="date-filter-label">Date Range:</div>
                <input type="date" id="ongoingStartDate" class="date-input" placeholder="Start Date" max="">
                <span style="color: #6B7280;">to</span>
                <input type="date" id="ongoingEndDate" class="date-input" placeholder="End Date" max="">
                <button class="filter-btn" onclick="applyOngoingDateFilter()">Apply Filter</button>
            </div>

            <div class="stats-summary" id="ongoingStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Ongoing</div>
                    <div class="stats-card-value" id="totalOngoing">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Total Leads</div>
                    <div class="stats-card-value" id="totalLeads">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Ongoing %</div>
                    <div class="stats-card-value" id="ongoingPercentage">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Period</div>
                    <div class="stats-card-value" id="ongoingPeriod">-</div>
                </div>
            </div>

            <div id="ongoingStoppedTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading ongoing/stopped data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Due Modal -->
<div id="paymentDueModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Payment Due Statistics</h3>
            <button class="modal-close" onclick="closeModal('paymentDueModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="stats-tabs">
                <button class="stats-tab active" onclick="switchPaymentDueTab('today')">Today</button>
                <button class="stats-tab" onclick="switchPaymentDueTab('monthly')">Monthly</button>
                <button class="stats-tab" onclick="switchPaymentDueTab('yearly')">Yearly</button>
                <button class="stats-tab" onclick="switchPaymentDueTab('custom')">Custom Range</button>
            </div>

            <div class="period-selector active" id="paymentTodaySelector">
                <div class="date-filter-label">Today's Unverified Payments:</div>
                <div style="color: #f59e0b; font-weight: 600;">{{ date('d M Y') }}</div>
            </div>

            <div class="period-selector" id="paymentMonthlySelector">
                <div class="date-filter-label">Select Month:</div>
                <select id="paymentMonthSelect" class="date-input" onchange="loadPaymentDueData('monthly', null, null, this.value)">
                    <option value="">Loading months...</option>
                </select>
            </div>

            <div class="period-selector" id="paymentYearlySelector">
                <div class="date-filter-label">Select Year:</div>
                <select id="paymentYearSelect" class="date-input" onchange="loadPaymentDueData('yearly', null, null, this.value)">
                    <option value="">Loading years...</option>
                </select>
            </div>

            <div class="period-selector" id="paymentCustomSelector">
                <div class="date-filter-label">Date Range:</div>
                <input type="date" id="paymentStartDate" class="date-input" placeholder="Start Date" max="">
                <span style="color: #6B7280;">to</span>
                <input type="date" id="paymentEndDate" class="date-input" placeholder="End Date" max="">
                <button class="filter-btn" onclick="applyPaymentDateFilter()">Apply Filter</button>
            </div>

            <div class="stats-summary" id="paymentStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Unverified</div>
                    <div class="stats-card-value" id="totalUnverified">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Total Amount</div>
                    <div class="stats-card-value" id="totalAmount">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Period</div>
                    <div class="stats-card-value" id="paymentPeriod">-</div>
                </div>
            </div>

            <div id="paymentDueTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading payment due data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Job Request Modal -->
<div id="jobRequestModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Job Request Statistics</h3>
            <button class="modal-close" onclick="closeModal('jobRequestModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="stats-summary" id="jobRequestStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Job Requests</div>
                    <div class="stats-card-value" id="totalJobRequests">-</div>
                </div>
            </div>

            <div id="jobRequestTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading job request data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vendor & Freelancer Payment Modal -->
<div id="vendorFreelancerPaymentModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Vendor & Freelancer Payment Statistics</h3>
            <button class="modal-close" onclick="closeModal('vendorFreelancerPaymentModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="stats-summary" id="vendorFreelancerPaymentStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Unverified</div>
                    <div class="stats-card-value" id="totalUnverifiedPayments">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Total Amount</div>
                    <div class="stats-card-value" id="totalPaymentAmount">-</div>
                </div>
            </div>

            <div id="vendorFreelancerPaymentTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading vendor/freelancer payment data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Leads Modal -->
<div id="recentLeadsModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">25 Most Recent Leads</h3>
            <button class="modal-close" onclick="closeModal('recentLeadsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="stats-summary" id="recentLeadsStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Leads</div>
                    <div class="stats-card-value" id="totalRecentLeads">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Latest Lead</div>
                    <div class="stats-card-value" id="latestLeadDate" style="font-size: 18px;">-</div>
                </div>
            </div>

            <div id="recentLeadsTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading recent leads data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Unverified Deployment Payments Modal -->
<div id="unverifiedDeploymentModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Unverified Deployment Payments</h3>
            <button class="modal-close" onclick="closeModal('unverifiedDeploymentModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="stats-summary" id="unverifiedDeploymentStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Unverified</div>
                    <div class="stats-card-value" id="totalUnverifiedDeployments">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Total Amount</div>
                    <div class="stats-card-value" id="totalUnverifiedAmount">-</div>
                </div>
            </div>

            <div id="unverifiedDeploymentTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading unverified deployment payments...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Callbacks Modal -->
<div id="pendingCallbacksModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-phone-slash"></i>
                Pending Callbacks (Missed/Busy/Failed Calls)
            </h3>
            <button class="modal-close" onclick="closeModal('pendingCallbacksModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Summary Cards -->
            <div class="stats-summary" id="pendingCallbacksStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Pending</div>
                    <div class="stats-card-value" id="totalPendingCallbacks">-</div>
                </div>
            </div>

            <!-- Leads Table -->
            <div id="pendingCallbacksTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading pending callbacks...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Operation Lead Modal -->
<div class="modal fade" id="addOperationLeadModal" tabindex="-1" role="dialog"
    aria-labelledby="addOperationLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addOperationLeadModalLabel">Add Operation Lead</h5>
                <button type="button" class="btn" data-bs-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addOperationLeadForm" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="date_time" class="form-label">Date/Time</label>
                                <input type="datetime-local" class="form-control" id="date_time" name="date_time"
                                    required>
                            </div>
                            <div class="form-group mb-3 d-none">
                                <label for="executive" class="form-label">Executive</label>
                                <input type="text" class="form-control" id="executive" name="executive"
                                    value="{{ Auth::user()->id }}" readonly>
                            </div>
                            <div class="form-group mb-3">
                                <label for="customer_name" class="form-label">Customer Name</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name"
                                    required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="contact_no" class="form-label">Contact No</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="contact_no" name="contact_no"
                                        required>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="location" class="form-label">Location</label>
                                <select class="form-control" id="location" name="location" required>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Noida">Noida</option>
                                    <option value="Gurgaon">Gurgaon</option>
                                    <option value="Faridabad">Faridabad</option>
                                    <option value="Gaziabad">Gaziabad</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="query" class="form-label">Query</label>
                                <select class="form-control" id="query" name="query" required onchange="toggleQueryRemark()">
                                    <option value="">Select Query</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->name }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-3 d-none" id="query_remark_group">
                                <label for="query_remark" class="form-label">Query Remark</label>
                                <input type="text" class="form-control" id="query_remark" name="query_remark" placeholder="Enter remark for selected query">
                            </div>
                            <div class="form-group mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status" required
                                    onchange="toggleStatusRemark()">
                                    <option value="profile">Profile</option>
                                    <option value="profile pending">Profile Pending</option>
                                    <option value="profile shared">Profile Shared</option>
                                    <option value="closed">Closed</option>
                                    <option value="follow up">Follow Up</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="price issue">Price Issue</option>
                                </select>
                            </div>
                            <div class="form-group mb-3 d-none" id="closed_remark_group">
                                <label for="closed_remark" class="form-label">Closed Remark</label>
                                <input type="text" class="form-control" id="closed_remark"
                                    name="closed_remark" placeholder="Enter remark for closed status">
                            </div>
                            <div class="form-group mb-3">
                                <label for="shift_type" class="form-label">Shift Type</label>
                                <select class="form-control" id="shift_type" name="shift_type">
                                    <option value="">Select Shift Type</option>
                                    <option value="12hr">12hr</option>
                                    <option value="24hr">24hr</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                            <div class="form-group mb-3 d-none" id="price_issue_remark_group">
                                <label for="price_issue_remark" class="form-label">Price Issue Remark</label>
                                <input type="text" class="form-control" id="price_issue_remark"
                                    name="price_issue_remark" placeholder="Enter remark for price issue status">
                            </div>
                            <div class="form-group mb-3 d-none" id="inactive_remark_group">
                                <label for="inactive_remark" class="form-label">Inactive Remark</label>
                                <input type="text" class="form-control" id="inactive_remark"
                                    name="inactive_remark" placeholder="Enter remark for inactive status">
                            </div>
                            <div class="form-group mb-3">
                                <label for="patient_name" class="form-label">Patient Name</label>
                                <input type="text" class="form-control" id="patient_name" name="patient_name">
                            </div>
                            <div class="form-group mb-3">
                                <label for="age" class="form-label">Age</label>
                                <input type="number" class="form-control" id="age" name="age">
                            </div>
                            <div class="form-group mb-3">
                                <label for="closed_rate" class="form-label">Closed Rate</label>
                                <input type="number" step="0.01" class="form-control" id="closed_rate"
                                    name="closed_rate">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="vendor_name" class="form-label">Vendor / Freelance Staff</label>
                                <select class="form-control" id="vendor_id" name="vendor_id" required>
                                    <option value="">Select Vendor / Freelance Staff</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Vendors and active freelance staff will be filtered based on selected location and query</small>
                            </div>
                            <div class="form-group mb-3">
                                <label for="staff_name" class="form-label">Staff Name</label>
                                <input type="text" class="form-control" id="staff_name" name="staff_name" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="vendor_closed_rate" class="form-label">Vendor Closed Rate</label>
                                <input type="number" step="0.01" class="form-control" id="vendor_closed_rate"
                                    name="vendor_closed_rate">
                            </div>
                            <div class="form-group mb-3">
                                <label for="payment_plan" class="form-label">Payment Plan</label>
                                <select class="form-control" id="payment_plan" name="payment_plan" required>
                                    <option value="3 Days">3 Days</option>
                                    <option value="1 Week">1 Week</option>
                                    <option value="15 Days">15 Days</option>
                                    <option value="1 Month">1 Month</option>
                                </select>
                            </div>
                            <div class="form-group mb-3 d-none" id="ongoing_stopped_group">
                                <label for="ongoing_stopped" class="form-label">Ongoing/Stopped</label>
                                <select class="form-control" id="ongoing_stopped" name="ongoing_stopped">
                                    <option value="ongoing">Ongoing</option>
                                    <option value="stopped">Stopped</option>
                                </select>
                            </div>
                            <div class="form-group mb-3 d-none" id="stopped_details_group">
                                <label for="stopped_date_time" class="form-label">Stopped Date/Time</label>
                                <input type="datetime-local" class="form-control" id="stopped_date_time"
                                    name="stopped_date_time">
                                <label for="stopped_remark" class="form-label mt-2">Stopped Remark</label>
                                <input type="text" class="form-control" id="stopped_remark"
                                    name="stopped_remark">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveOperationLead">Save Lead</button>
            </div>
        </div>
    </div>
</div>

<!-- Recent Calls Modal -->
<div id="recentCallsModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-phone"></i>
                Recent Calls (Past Week)
            </h3>
            <button class="modal-close" onclick="closeModal('recentCallsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Summary Cards -->
            <div class="stats-summary" id="recentCallsStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Calls</div>
                    <div class="stats-card-value" id="totalRecentCalls">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Successful</div>
                    <div class="stats-card-value" id="successfulCalls">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Failed</div>
                    <div class="stats-card-value" id="failedCalls">-</div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="search-container" style="margin-bottom: 20px;">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                    </div>
                    <input type="text" id="recentCallsSearchInput" class="form-control" placeholder="Search by phone number, customer name, or lead number..." onkeyup="filterRecentCalls()">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearRecentCallsSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Calls Table -->
            <div id="recentCallsTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading recent calls...</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('footer-script')
<script>
    // Modal functions
    function openOperationLeadsModal() {
        document.getElementById('operationLeadsModal').style.display = 'flex';
        setupDateInputs();
        populateMonthYearDropdowns();
        loadOperationLeadsData('today');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    });

    // Stats tab switching
    function switchStatsTab(period) {
        // Update active tab
        const tabs = document.querySelectorAll('.stats-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        // Show/hide appropriate selectors
        const selectors = ['todaySelector', 'monthlySelector', 'yearlySelector', 'customSelector'];
        selectors.forEach(id => {
            document.getElementById(id).classList.remove('active');
        });

        if (period === 'today') {
            document.getElementById('todaySelector').classList.add('active');
            loadOperationLeadsData('today');
        } else if (period === 'monthly') {
            document.getElementById('monthlySelector').classList.add('active');
            const monthSelect = document.getElementById('monthSelect');
            if (monthSelect.value) {
                loadOperationLeadsData('monthly', null, null, monthSelect.value);
            }
        } else if (period === 'yearly') {
            document.getElementById('yearlySelector').classList.add('active');
            const yearSelect = document.getElementById('yearSelect');
            if (yearSelect.value) {
                loadOperationLeadsData('yearly', null, null, yearSelect.value);
            }
        } else if (period === 'custom') {
            document.getElementById('customSelector').classList.add('active');
        }
    }

    // Populate month and year dropdowns
    function populateMonthYearDropdowns() {
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        const currentMonth = currentDate.getMonth();

        // Populate months (last 12 months)
        const monthSelect = document.getElementById('monthSelect');
        monthSelect.innerHTML = '';

        for (let i = 0; i < 12; i++) {
            const date = new Date(currentYear, currentMonth - i, 1);
            const monthName = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const monthValue = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');

            const option = document.createElement('option');
            option.value = monthValue;
            option.textContent = monthName;
            if (i === 0) option.selected = true; // Current month
            monthSelect.appendChild(option);
        }

        // Populate years (last 5 years)
        const yearSelect = document.getElementById('yearSelect');
        yearSelect.innerHTML = '';

        for (let year = currentYear; year >= currentYear - 4; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            if (year === currentYear) option.selected = true; // Current year
            yearSelect.appendChild(option);
        }
    }

    // Setup date inputs with max date and validation
    function setupDateInputs() {
        const today = new Date().toISOString().split('T')[0];
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');

        startDateInput.max = today;
        endDateInput.max = today;

        // Add event listeners for date validation
        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            if (endDateInput.value && this.value > endDateInput.value) {
                endDateInput.value = this.value;
            }
        });

        endDateInput.addEventListener('change', function() {
            startDateInput.max = this.value;
        });
    }

    // Apply date filter
    function applyDateFilter() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!startDate || !endDate) {
            alert('Please select both start and end dates.');
            return;
        }

        if (startDate > endDate) {
            alert('Start date cannot be after end date.');
            return;
        }

        loadOperationLeadsData('custom', startDate, endDate);
    }

    // Load operation leads data
    function loadOperationLeadsData(period, startDate = null, endDate = null, selectedPeriod = null) {
        const container = document.getElementById('leadsTableContainer');
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading leads data...</p></div>';

        let url = `/operation/dashboard/leads-stats?period=${period}`;
        if (period === 'custom' && startDate && endDate) {
            url += `&start_date=${startDate}&end_date=${endDate}`;
        } else if (period === 'monthly' && selectedPeriod) {
            url += `&selected_month=${selectedPeriod}`;
        } else if (period === 'yearly' && selectedPeriod) {
            url += `&selected_year=${selectedPeriod}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateLeadsDisplay(data, period);
            })
            .catch(error => {
                console.error('Error loading operation leads data:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    // Update leads display
    function updateLeadsDisplay(data, period) {
        // Update summary cards
        const totalLeads = data.leads.length;
        const avgPerDay = data.stats.length > 0 ? (totalLeads / data.stats.length).toFixed(1) : 0;

        document.getElementById('totalLeads').textContent = totalLeads;
        document.getElementById('avgPerDay').textContent = avgPerDay;

        // Update period display
        if (period === 'custom') {
            const startDate = new Date(data.startDate).toLocaleDateString('en-GB');
            const endDate = new Date(data.endDate).toLocaleDateString('en-GB');
            document.getElementById('period').textContent = `${startDate} - ${endDate}`;
        } else {
            document.getElementById('period').textContent = period.charAt(0).toUpperCase() + period.slice(1);
        }

        // Generate table
        const container = document.getElementById('leadsTableContainer');
        if (data.leads.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No leads found for this period.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead ID</th>
                        <th>Date/Time</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.leads.forEach(lead => {
            const date = lead.date_time ? new Date(lead.date_time).toLocaleDateString('en-GB') + ' ' + new Date(lead.date_time).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) : 'N/A';

            tableHTML += `
                <tr>
                    <td><strong>#${lead.lead_id}</strong></td>
                    <td>${date}</td>
                    <td>${lead.customer_name || 'N/A'}</td>
                    <td>${lead.contact_no || 'N/A'}</td>
                    <td>${lead.location || 'N/A'}</td>
                    <td><span class="status-badge badge-${(lead.status || 'default').toLowerCase().replace(/\s+/g, '-')}">${lead.status || '-'}</span></td>
                    <td>
                        <a href="/operation/operation-leads/${lead.id}" class="btn action-btn view-btn" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Task Management Functions
    let currentTaskType = 'assigned_to_me';

    function openTasksModal() {
        document.getElementById('tasksModal').style.display = 'flex';
        loadTasksData();
    }

    function switchTasksTab(type) {
        currentTaskType = type;

        // Update active tab
        const tabs = document.querySelectorAll('#tasksModal .stats-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        loadTasksData();
    }

    function loadTasksData() {
        const container = document.getElementById('tasksTableContainer');
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading tasks data...</p></div>';

        const params = new URLSearchParams({
            type: currentTaskType
        });

        // Add date filters if they exist
        const startDate = document.getElementById('taskStartDate').value;
        const endDate = document.getElementById('taskEndDate').value;

        if (startDate) {
            params.append('start_date', startDate);
        }
        if (endDate) {
            params.append('end_date', endDate);
        }

        fetch(`/operation/dashboard/tasks?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading tasks. Please try again.</div>';
                    return;
                }
                updateTasksDisplay(data);
            })
            .catch(error => {
                console.error('Error loading tasks:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading tasks. Please try again.</div>';
            });
    }

    function updateTasksDisplay(data) {
        const container = document.getElementById('tasksTableContainer');
        if (data.tasks && data.tasks.length > 0) {
            let tableHTML = '';

            if (currentTaskType === 'history') {
                // Task History Table
                tableHTML = `
                    <table class="leads-table">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Priority</th>
                                <th>Assigned To</th>
                                <th>Assigned By</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.tasks.forEach(task => {
                    const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString('en-GB') : 'N/A';
                    const createdDate = task.created_at ? new Date(task.created_at).toLocaleDateString('en-GB') : 'N/A';
                    const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status !== 'completed';

                    tableHTML += `
                        <tr>
                            <td>
                                <div>
                                    <strong>${task.title}</strong>
                                    ${task.description ? `<br><small class="text-muted">${task.description}</small>` : ''}
                                </div>
                            </td>
                            <td><span class="badge bg-${getPriorityClass(task.priority)}">${task.priority}</span></td>
                            <td>${task.assigned_to_name || 'N/A'}</td>
                            <td>${task.assigned_by_name || 'N/A'}</td>
                            <td class="${isOverdue ? 'text-danger' : ''}">${dueDate}</td>
                            <td><span class="badge bg-${getStatusClass(task.status)}">${task.status}</span></td>
                            <td>${createdDate}</td>
                            <td class="action-column">
                                <div class="action-buttons-container">
                                    <button class="action-btn view-btn" onclick="viewTask(${task.id})" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    ${getTaskActionButtons(task)}
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } else {
                // Regular Tasks Table
                tableHTML = `
                    <table class="leads-table">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Priority</th>
                                <th>Assigned By</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.tasks.forEach(task => {
                    const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString('en-GB') : 'N/A';
                    const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status !== 'completed';

                    tableHTML += `
                        <tr>
                            <td>
                                <div>
                                    <strong>${task.title}</strong>
                                    ${task.description ? `<br><small class="text-muted">${task.description}</small>` : ''}
                                </div>
                            </td>
                            <td><span class="badge bg-${getPriorityClass(task.priority)}">${task.priority}</span></td>
                            <td>${task.assigned_by_name || 'N/A'}</td>
                            <td class="${isOverdue ? 'text-danger' : ''}">${dueDate}</td>
                            <td><span class="badge bg-${getStatusClass(task.status)}">${task.status}</span></td>
                            <td class="action-column">
                                <div class="action-buttons-container">
                                    ${getTaskActionButtons(task)}
                                </div>
                            </td>
                        </tr>
                    `;
                });
            }

            tableHTML += '</tbody></table>';
            container.innerHTML = tableHTML;
        } else {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No tasks found.</div>';
        }
    }

    function getPriorityClass(priority) {
        switch(priority) {
            case 'low': return 'info';
            case 'medium': return 'primary';
            case 'high': return 'warning';
            case 'urgent': return 'danger';
            default: return 'secondary';
        }
    }

    function getStatusClass(status) {
        switch(status) {
            case 'pending': return 'secondary';
            case 'in_progress': return 'warning';
            case 'completed': return 'success';
            case 'cancelled': return 'danger';
            default: return 'secondary';
        }
    }

    function getTaskActionButtons(task) {
        const currentUserId = {{ auth()->user()->id }};
        let buttons = '';

        // Add View button for all tasks
        buttons += `<button class="action-btn view-btn" onclick="viewTask(${task.id})" title="View Details">
            <i class="fas fa-eye"></i>
        </button>`;

        // Check if this is a parent manager assigned task
        const isParentManagerTask = task.assigned_by && task.assigned_by.id !== currentUserId;

        // Add Edit and Delete buttons if user assigned the task AND it's not a parent manager task
        if (task.assigned_by && task.assigned_by.id == currentUserId && !isParentManagerTask) {
            buttons += `<button class="action-btn edit-btn" onclick="editTask(${task.id})" title="Edit Task">
                <i class="fas fa-edit"></i>
            </button>`;
            buttons += `<button class="action-btn delete-btn" onclick="deleteTask(${task.id})" title="Delete Task">
                <i class="fas fa-trash"></i>
            </button>`;
        }

        // Add status update buttons if user is assigned to the task
        if (task.assigned_to && task.assigned_to.id == currentUserId) {
            if (task.status === 'pending') {
                buttons += `<button class="action-btn start-btn" onclick="updateTaskStatus(${task.id}, 'in_progress')" title="Start Task">
                    <i class="fas fa-play"></i>
                </button>`;
            }
            if (task.status === 'in_progress') {
                buttons += `<button class="action-btn complete-btn" onclick="updateTaskStatus(${task.id}, 'completed')" title="Complete Task">
                    <i class="fas fa-check"></i>
                </button>`;
            }
            if (task.status !== 'completed' && task.status !== 'cancelled') {
                buttons += `<button class="action-btn cancel-btn" onclick="updateTaskStatus(${task.id}, 'cancelled')" title="Cancel Task">
                    <i class="fas fa-times"></i>
                </button>`;
            }
        }

        return buttons;
    }

    function openAddTaskModal() {
        document.getElementById('addTaskModal').style.display = 'flex';
        // Set default due date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('taskDueDate').value = tomorrow.toISOString().split('T')[0];
    }

    function submitTaskForm() {
        const form = document.getElementById('addTaskForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        fetch('/operation/dashboard/tasks', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Task created successfully!');
                closeModal('addTaskModal');
                form.reset();
                loadTasksData();
            } else {
                alert('Error: ' + (data.error || 'Failed to create task'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error creating task');
        });
    }

    function editTask(taskId) {
        fetch(`/operation/dashboard/tasks/${taskId}/edit`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const task = data.task;
                    document.getElementById('editTaskId').value = task.id;
                    document.getElementById('editTaskTitle').value = task.title;
                    document.getElementById('editTaskDescription').value = task.description || '';
                    document.getElementById('editTaskPriority').value = task.priority;
                    document.getElementById('editTaskDueDate').value = task.due_date;
                    document.getElementById('editTaskStatus').value = task.status;

                    document.getElementById('editTaskModal').style.display = 'flex';
                } else {
                    alert('Error: ' + (data.error || 'Failed to load task'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading task');
            });
    }

    function submitEditTaskForm() {
        const form = document.getElementById('editTaskForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        const taskId = data.task_id;

        fetch(`/operation/dashboard/tasks/${taskId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Task updated successfully!');
                closeModal('editTaskModal');
                form.reset();
                loadTasksData();
            } else {
                alert('Error: ' + (data.error || 'Failed to update task'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating task');
        });
    }

    function viewTask(taskId) {
        fetch(`/operation/dashboard/tasks/${taskId}/edit`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const task = data.task;
                    const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString('en-GB') : 'N/A';
                    const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status !== 'completed';

                    document.getElementById('viewTaskContent').innerHTML = `
                        <div class="mb-3">
                            <label class="form-label fw-bold">Task Title:</label>
                            <p>${task.title}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description:</label>
                            <p>${task.description || 'No description provided'}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Priority:</label>
                            <span class="badge bg-${getPriorityClass(task.priority)}">${task.priority}</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status:</label>
                            <span class="badge bg-${getStatusClass(task.status)}">${task.status}</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Assigned To:</label>
                            <p>${task.assigned_to_name || 'N/A'}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Assigned By:</label>
                            <p>${task.assigned_by_name || 'N/A'}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Due Date:</label>
                            <p class="${isOverdue ? 'text-danger' : ''}">${dueDate} ${isOverdue ? '(Overdue)' : ''}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Created At:</label>
                            <p>${new Date(task.created_at).toLocaleDateString('en-GB')} ${new Date(task.created_at).toLocaleTimeString('en-GB')}</p>
                        </div>
                    `;

                    document.getElementById('viewTaskModal').style.display = 'flex';
                } else {
                    alert('Error: ' + (data.error || 'Failed to load task'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading task');
            });
    }

    function deleteTask(taskId) {
        if (confirm('Are you sure you want to delete this task?')) {
            fetch(`/operation/dashboard/tasks/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Task deleted successfully!');
                    loadTasksData();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete task'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting task');
            });
        }
    }

    function updateTaskStatus(taskId, status) {
        fetch(`/operation/dashboard/tasks/${taskId}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadTasksData();
            } else {
                alert('Error: ' + (data.error || 'Failed to update task status'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating task status');
        });
    }

    function applyTaskDateFilter() {
        const startDate = document.getElementById('taskStartDate').value;
        const endDate = document.getElementById('taskEndDate').value;

        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
            alert('Start date cannot be after end date!');
            return;
        }

        loadTasksData();
    }

    function clearTaskDateFilter() {
        document.getElementById('taskStartDate').value = '';
        document.getElementById('taskEndDate').value = '';
        loadTasksData();
    }

    // Operation Calendar Functions
    let currentDateOperation = new Date();
    let calendarDataOperation = {};
    let selectedDateOperation = null;

    function openCalendarModalOperation() {
        document.getElementById('operationCalendarModal').style.display = 'flex';

        // Initialize calendar data and render
        currentDateOperation = new Date();
        calendarDataOperation = {};

        // Render calendar immediately, then load data
        renderCalendarOperation();
        loadCalendarDataOperation();
    }

    function loadCalendarDataOperation() {
        const year = currentDateOperation.getFullYear();
        const month = currentDateOperation.getMonth() + 1;

        fetch(`/operation/dashboard/calendar-data?year=${year}&month=${month}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }
                calendarDataOperation = data.calendar_data || {};
                renderCalendarOperation();
            })
            .catch(error => {
                console.error('Error loading calendar data:', error);
            });
    }

    function renderCalendarOperation() {
        const year = currentDateOperation.getFullYear();
        const month = currentDateOperation.getMonth();

        document.getElementById('currentMonthYearOperation').textContent =
            currentDateOperation.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();

        const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        let calendarHTML = '';
        dayHeaders.forEach(day => {
            calendarHTML += `<div class="calendar-day-header">${day}</div>`;
        });

        for (let i = 0; i < startingDayOfWeek; i++) {
            const prevMonthDay = new Date(year, month, -startingDayOfWeek + i + 1);
            calendarHTML += `<div class="calendar-day other-month">
                <div class="day-number">${prevMonthDay.getDate()}</div>
            </div>`;
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayData = calendarDataOperation[dateStr] || { follow_up: 0 };
            const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();

            let dayClass = 'calendar-day';
            if (isToday) dayClass += ' today';

            calendarHTML += `<div class="${dayClass}" data-date="${dateStr}" onclick="selectDateOperation('${dateStr}')">
                <div class="day-number">${day}</div>
                <div class="day-counts">`;

            if (dayData.follow_up > 0) {
                calendarHTML += `<div class="count-badge follow-up">${dayData.follow_up} F</div>`;
            }

            calendarHTML += `</div></div>`;
        }

        const remainingCells = 42 - (startingDayOfWeek + daysInMonth);
        for (let i = 1; i <= remainingCells; i++) {
            calendarHTML += `<div class="calendar-day other-month">
                <div class="day-number">${i}</div>
            </div>`;
        }

        document.getElementById('calendarGridOperation').innerHTML = calendarHTML;
    }

    function selectDateOperation(dateStr) {
        document.querySelectorAll('#calendarGridOperation .calendar-day.selected').forEach(day => {
            day.classList.remove('selected');
        });

        const selectedDay = document.querySelector(`#calendarGridOperation [data-date="${dateStr}"]`);
        if (selectedDay) {
            selectedDay.classList.add('selected');
        }

        selectedDateOperation = dateStr;
        const date = new Date(dateStr);
        const formattedDate = date.toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        const btn = document.getElementById('viewLeadsBtnOperation');
        btn.disabled = false;
        btn.textContent = `View Leads for ${formattedDate}`;
    }

    function openCalendarLeadsModalOperation() {
        if (!selectedDateOperation) {
            alert('Please select a date first');
            return;
        }
        document.getElementById('operationCalendarLeadsModal').style.display = 'flex';
        loadDateLeadsOperation(selectedDateOperation);
    }

    function loadDateLeadsOperation(dateStr) {
        const date = new Date(dateStr);
        const formattedDate = date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        document.getElementById('operationCalendarLeadsTitle').textContent = `Leads for ${formattedDate}`;

        fetch(`/operation/dashboard/date-leads?date=${dateStr}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    const tableBody = document.getElementById('operationCalendarLeadsTableBody');
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <br>Error: ${data.error}
                            </td>
                        </tr>
                    `;
                    return;
                }

                const tableBody = document.getElementById('operationCalendarLeadsTableBody');
                if (data.leads && data.leads.length > 0) {
                    let tableHTML = '';
                    data.leads.forEach(lead => {
                        const leadNo = lead.lead_id || `#${lead.id}`;
                        tableHTML += `
                            <tr>
                                <td><strong>${leadNo}</strong></td>
                                <td>${lead.customer_name || 'N/A'}</td>
                                <td>${lead.contact_no || 'N/A'}</td>
                                <td>${lead.location || 'N/A'}</td>
                                <td>${lead.query || 'N/A'}</td>
                                <td><span class="badge bg-primary">${lead.status || 'N/A'}</span></td>
                                <td>
                                    <a href="/operation/operation-leads/${lead.id}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        `;
                    });
                    tableBody.innerHTML = tableHTML;
                } else {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-3"></i>
                                <br>No follow-up leads found for this date
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading date leads:', error);
                const tableBody = document.getElementById('operationCalendarLeadsTableBody');
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <br>Error loading leads: ${error.message}
                        </td>
                    </tr>
                `;
            });
    }

    // Calendar navigation
    document.getElementById('prevMonthOperation').addEventListener('click', function() {
        currentDateOperation.setMonth(currentDateOperation.getMonth() - 1);
        loadCalendarDataOperation();
    });

    document.getElementById('nextMonthOperation').addEventListener('click', function() {
        currentDateOperation.setMonth(currentDateOperation.getMonth() + 1);
        loadCalendarDataOperation();
    });

    // Load calendar data for the card count on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadOperationCalendarCardData();
        
        // Start monitoring active calls
        startActiveCallsMonitoring();
        
        // Load pending callbacks count
        loadPendingCallbacksCount();
        
        // Load recent calls count
        loadRecentCallsCount();
    });

    // Active Calls Monitoring
    let activeCallsInterval = null;

    function startActiveCallsMonitoring() {
        // Load immediately
        loadActiveCalls();
        
        // Then refresh every 5 seconds
        activeCallsInterval = setInterval(loadActiveCalls, 5000);
    }

    function loadActiveCalls() {
        fetch('{{ route("operation.dashboard.active-calls") }}')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }
                
                updateActiveCallsTable(data.calls || []);
            })
            .catch(error => {
                console.error('Error loading active calls:', error);
            });
    }

    function updateActiveCallsTable(calls) {
        const card = document.getElementById('activeCallsCard');
        const tableBody = document.getElementById('activeCallsTableBody');
        const monitorBadge = document.getElementById('activeCallsMonitorBadge');
        
        if (calls.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center text-muted" style="padding: 30px;">
                        <i class="fas fa-phone fa-3x mb-3" style="color: #d1d5db;"></i>
                        <br>
                        <strong>No active calls at the moment</strong>
                        <br>
                        <small>This table will automatically update when calls are received</small>
                    </td>
                </tr>
            `;
            monitorBadge.className = 'badge bg-secondary';
            monitorBadge.innerHTML = '<i class="fas fa-circle" style="font-size: 8px; animation: pulseDot 1.5s infinite;"></i> Monitoring...';
            return;
        }
        
        // Update monitor badge
        monitorBadge.className = 'badge bg-success';
        monitorBadge.innerHTML = `<i class="fas fa-circle" style="font-size: 8px; animation: pulseDot 1.5s infinite;"></i> ${calls.length} Active Call${calls.length > 1 ? 's' : ''}`;
        
        // Sort: active calls first, then by time (most recent first)
        calls.sort((a, b) => {
            if (a.is_active && !b.is_active) return -1;
            if (!a.is_active && b.is_active) return 1;
            return new Date(b.call_time) - new Date(a.call_time);
        });
        
        let tableHTML = '';
        calls.forEach(call => {
            const isActive = call.is_active;
            const rowClass = isActive ? 'call-status-active' : 'call-status-recent';
            const statusBadge = isActive 
                ? '<span class="call-status-indicator active"><span class="pulse-dot"></span> Active Call</span>'
                : '<span class="call-status-indicator recent">Recent</span>';
            
            const callTime = call.call_time ? new Date(call.call_time).toLocaleTimeString('en-GB', {hour: '2-digit', minute: '2-digit', second: '2-digit'}) : 'N/A';
            const timeAgo = call.minutes_ago !== undefined ? `${call.minutes_ago} min ago` : '';
            
            const leadStatusClass = call.lead_status ? call.lead_status.toLowerCase().replace(/\s+/g, '-') : 'default';
            
            tableHTML += `
                <tr class="${rowClass}">
                    <td>${statusBadge}</td>
                    <td><strong>${call.lead_id || 'N/A'}</strong></td>
                    <td>${call.customer_name || 'N/A'}</td>
                    <td>
                        <strong>${call.contact_no}</strong>
                        ${call.call_direction ? `<br><small class="text-muted">${call.call_direction}</small>` : ''}
                    </td>
                    <td>${call.location || '-'}</td>
                    <td>${call.query || '-'}</td>
                    <td><span class="status-badge badge-${leadStatusClass}">${call.lead_status || '-'}</span></td>
                    <td>
                        ${callTime}
                        ${timeAgo ? `<br><small class="text-muted">${timeAgo}</small>` : ''}
                    </td>
                    <td>
                        <a href="/operation/operation-leads/${call.operation_lead_id}" class="btn action-btn view-btn" title="View Lead">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button class="btn action-btn edit-btn edit-operation-lead-btn" data-call-data='${JSON.stringify(call)}' title="Edit Lead">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = tableHTML;
    }

    // Clean up interval when page is unloaded
    window.addEventListener('beforeunload', function() {
        if (activeCallsInterval) {
            clearInterval(activeCallsInterval);
        }
    });

    // Operation Calendar Card functionality
    function loadOperationCalendarCardData() {
        const year = new Date().getFullYear();
        const month = new Date().getMonth() + 1;

        fetch(`/operation/dashboard/calendar-data?year=${year}&month=${month}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    updateOperationCalendarCardCount(0);
                    return;
                }
                updateOperationCalendarCardCount(data.calendar_data);
            })
            .catch(error => {
                console.error('Error loading operation calendar data:', error);
                updateOperationCalendarCardCount(0);
            });
    }

    function updateOperationCalendarCardCount(calendarData) {
        let totalFollowUp = 0;

        if (calendarData && typeof calendarData === 'object') {
            Object.values(calendarData).forEach(dayData => {
                totalFollowUp += dayData.follow_up || 0;
            });
        }

        const countElement = document.getElementById('operationCalendarCount');

        if (totalFollowUp > 0) {
            countElement.innerHTML = `
                <div style="font-size: 0.9em; line-height: 1.3;">
                    <div>Follow-up: <strong style="color: #007bff;">${totalFollowUp}</strong></div>
                </div>
            `;
        } else {
            countElement.innerHTML = '<span style="color: #6c757d; font-size: 0.9em;">No scheduled leads</span>';
        }
    }

    // Outstanding Payments Functions
    function openOutstandingPaymentsModal() {
        document.getElementById('outstandingPaymentsModal').style.display = 'flex';
        setupOutstandingDateInputs();
        populateOutstandingMonthYearDropdowns();
        loadOutstandingPaymentsData('today');
    }

    function switchOutstandingPaymentsTab(period) {
        // Update active tab
        const tabs = document.querySelectorAll('#outstandingPaymentsModal .stats-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        // Show/hide appropriate selectors
        const selectors = ['outstandingTodaySelector', 'outstandingMonthlySelector', 'outstandingYearlySelector', 'outstandingCustomSelector'];
        selectors.forEach(id => {
            document.getElementById(id).classList.remove('active');
        });

        if (period === 'today') {
            document.getElementById('outstandingTodaySelector').classList.add('active');
            loadOutstandingPaymentsData('today');
        } else if (period === 'monthly') {
            document.getElementById('outstandingMonthlySelector').classList.add('active');
            const monthSelect = document.getElementById('outstandingMonthSelect');
            if (monthSelect.value) {
                loadOutstandingPaymentsData('monthly', null, null, monthSelect.value);
            }
        } else if (period === 'yearly') {
            document.getElementById('outstandingYearlySelector').classList.add('active');
            const yearSelect = document.getElementById('outstandingYearSelect');
            if (yearSelect.value) {
                loadOutstandingPaymentsData('yearly', null, null, yearSelect.value);
            }
        } else if (period === 'custom') {
            document.getElementById('outstandingCustomSelector').classList.add('active');
        }
    }

    function populateOutstandingMonthYearDropdowns() {
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        const currentMonth = currentDate.getMonth();

        // Populate months (last 12 months)
        const monthSelect = document.getElementById('outstandingMonthSelect');
        monthSelect.innerHTML = '';

        for (let i = 0; i < 12; i++) {
            const date = new Date(currentYear, currentMonth - i, 1);
            const monthName = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const monthValue = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');

            const option = document.createElement('option');
            option.value = monthValue;
            option.textContent = monthName;
            if (i === 0) option.selected = true; // Current month
            monthSelect.appendChild(option);
        }

        // Populate years (last 5 years)
        const yearSelect = document.getElementById('outstandingYearSelect');
        yearSelect.innerHTML = '';

        for (let year = currentYear; year >= currentYear - 4; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            if (year === currentYear) option.selected = true; // Current year
            yearSelect.appendChild(option);
        }
    }

    function setupOutstandingDateInputs() {
        const today = new Date().toISOString().split('T')[0];
        const startDateInput = document.getElementById('outstandingStartDate');
        const endDateInput = document.getElementById('outstandingEndDate');

        startDateInput.max = today;
        endDateInput.max = today;

        // Add event listeners for date validation
        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            if (endDateInput.value && this.value > endDateInput.value) {
                endDateInput.value = this.value;
            }
        });

        endDateInput.addEventListener('change', function() {
            startDateInput.max = this.value;
        });
    }

    function applyOutstandingDateFilter() {
        const startDate = document.getElementById('outstandingStartDate').value;
        const endDate = document.getElementById('outstandingEndDate').value;

        if (!startDate || !endDate) {
            alert('Please select both start and end dates.');
            return;
        }

        if (startDate > endDate) {
            alert('Start date cannot be after end date.');
            return;
        }

        loadOutstandingPaymentsData('custom', startDate, endDate);
    }

    function loadOutstandingPaymentsData(period, startDate = null, endDate = null, selectedPeriod = null) {
        const container = document.getElementById('outstandingPaymentsTableContainer');
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading outstanding payments data...</p></div>';

        let url = `/operation/dashboard/outstanding-payments-stats?period=${period}`;
        if (period === 'custom' && startDate && endDate) {
            url += `&start_date=${startDate}&end_date=${endDate}`;
        } else if (period === 'monthly' && selectedPeriod) {
            url += `&selected_month=${selectedPeriod}`;
        } else if (period === 'yearly' && selectedPeriod) {
            url += `&selected_year=${selectedPeriod}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
                    return;
                }
                updateOutstandingPaymentsDisplay(data, period);
            })
            .catch(error => {
                console.error('Error loading outstanding payments data:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    function updateOutstandingPaymentsDisplay(data, period) {
        // Update summary cards
        document.getElementById('totalOutstanding').textContent = `₹${data.total_outstanding.toFixed(2)}`;
        document.getElementById('totalPayments').textContent = data.total_payments;
        document.getElementById('avgOutstanding').textContent = `₹${data.avg_outstanding.toFixed(2)}`;

        // Update period display
        if (period === 'custom') {
            const startDate = new Date(data.start_date).toLocaleDateString('en-GB');
            const endDate = new Date(data.end_date).toLocaleDateString('en-GB');
            document.getElementById('outstandingPeriod').textContent = `${startDate} - ${endDate}`;
        } else {
            document.getElementById('outstandingPeriod').textContent = period.charAt(0).toUpperCase() + period.slice(1);
        }

        // Generate table
        const container = document.getElementById('outstandingPaymentsTableContainer');
        if (data.payment_details.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No outstanding payments found for this period.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Lead ID</th>
                        <th>Executive</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Invoice Period</th>
                        <th>Work Days</th>
                        <th>Invoice Amount</th>
                        <th>Received Amount</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.payment_details.forEach(invoice => {
            const lead = invoice.operation_lead;
            const invoiceAmount = parseFloat(invoice.payment_amount) || 0;
            const receivedAmount = parseFloat(invoice.received_amount) || 0;
            const outstanding = parseFloat(invoice.outstanding_amount) || 0;
            const fromDate = invoice.from_date ? new Date(invoice.from_date).toLocaleDateString('en-GB') : 'N/A';
            const toDate = invoice.to_date ? new Date(invoice.to_date).toLocaleDateString('en-GB') : 'N/A';
            const period = `${fromDate} - ${toDate}`;
            const isReceived = invoice.is_received;
            const statusBadge = isReceived 
                ? '<span style="color: #10b981; font-weight: bold;">Paid</span>' 
                : '<span style="color: #f59e0b; font-weight: bold;">Pending</span>';

            const outstandingColor = outstanding > 0 ? '#ef4444' : '#10b981';
            const outstandingText = outstanding > 0 ? `₹${outstanding.toFixed(2)}` : '₹0.00';

            tableHTML += `
                <tr>
                    <td><strong>${invoice.invoice_id}</strong></td>
                    <td><strong>#${lead.id}</strong></td>
                    <td>${lead.executive_name || 'N/A'}</td>
                    <td>${lead.customer_name || 'N/A'}</td>
                    <td>${lead.contact_no || 'N/A'}</td>
                    <td>${lead.location || 'N/A'}</td>
                    <td>${period}</td>
                    <td>${invoice.work_days || 0} days</td>
                    <td><strong>₹${invoiceAmount.toFixed(2)}</strong></td>
                    <td>₹${receivedAmount.toFixed(2)}</td>
                    <td><strong style="color: ${outstandingColor}">${outstandingText}</strong></td>
                    <td>${statusBadge}</td>
                    <td>
                        <a href="/operation/operation-leads/${lead.id}" class="btn action-btn view-btn" title="View Lead">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Deployment Pending Functions
    let currentDeploymentUserFilter = 'mine';

    function openDeploymentPendingModal() {
        document.getElementById('deploymentPendingModal').style.display = 'flex';
        currentDeploymentUserFilter = 'mine';
        loadDeploymentPendingData();
    }

    function switchDeploymentPendingUserTab(userFilter) {
        currentDeploymentUserFilter = userFilter;

        // Update active tab
        const userTabs = document.querySelectorAll('#deploymentPendingModal .stats-tabs .stats-tab');
        userTabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        // Reload data with new user filter
        loadDeploymentPendingData();
    }

    function loadDeploymentPendingData() {
        const container = document.getElementById('deploymentPendingTableContainer');
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading deployment pending data...</p></div>';

        let url = `/operation/dashboard/deployment-pending-stats?user_filter=${currentDeploymentUserFilter}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
                    return;
                }
                updateDeploymentPendingDisplay(data);
            })
            .catch(error => {
                console.error('Error loading deployment pending data:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    function updateDeploymentPendingDisplay(data) {
        // Update summary cards
        document.getElementById('totalPending').textContent = data.total_pending;
        document.getElementById('totalDeployments').textContent = data.total_deployments;
        document.getElementById('pendingPercentage').textContent = `${data.pending_percentage.toFixed(1)}%`;

        // Update filter display
        const filterText = currentDeploymentUserFilter.charAt(0).toUpperCase() + currentDeploymentUserFilter.slice(1);
        document.getElementById('deploymentFilter').textContent = filterText;

        // Generate table
        const container = document.getElementById('deploymentPendingTableContainer');
        if (data.deployment_details.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No pending deployments found.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead No</th>
                        <th>Executive Name</th>
                        <th>Deployment Date & Time</th>
                        <th>Customer Name</th>
                        <th>Customer Number</th>
                        <th>Vendor Name</th>
                        <th>Vendor Number</th>
                        <th>Staff Name</th>
                        <th>Staff Number</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.deployment_details.forEach(deployment => {
            const lead = deployment.operation_lead;
            
            // Format deployment date with time
            const deploymentDateTime = deployment.deployment_date ? 
                new Date(deployment.deployment_date).toLocaleDateString('en-GB') + ' ' + 
                new Date(deployment.deployment_date).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) : 
                'N/A';

            // Get vendor name
            let vendorName = 'N/A';
            let vendorNumber = 'N/A';
            
            if (deployment.freelance_staff_id && deployment.freelance_staff) {
                vendorName = `${deployment.freelance_staff.name} (Freelance)`;
                vendorNumber = deployment.freelance_staff.contact_no || 'N/A';
            } else if (deployment.vendor) {
                vendorName = deployment.vendor.name;
                vendorNumber = deployment.vendor.contact_no || 'N/A';
            }

            // Get executive name
            const executiveName = lead.executive ? (lead.executive.f_name + ' ' + lead.executive.l_name) : 'N/A';

            tableHTML += `
                <tr>
                    <td><strong>${lead.lead_id || '#' + lead.id}</strong></td>
                    <td>${executiveName}</td>
                    <td>${deploymentDateTime}</td>
                    <td>${lead.customer_name || 'N/A'}</td>
                    <td>${lead.contact_no || 'N/A'}</td>
                    <td>${vendorName}</td>
                    <td>${vendorNumber}</td>
                    <td>${deployment.staff_name || 'N/A'}</td>
                    <td>${deployment.staff_number || 'N/A'}</td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Profile Pending Functions
    let currentProfileUserFilter = 'mine';

    function openProfilePendingModal() {
        document.getElementById('profilePendingModal').style.display = 'flex';
        currentProfileUserFilter = 'mine';
        loadProfilePendingData();
    }

    function switchProfilePendingUserTab(userFilter) {
        currentProfileUserFilter = userFilter;

        // Update active tab
        const userTabs = document.querySelectorAll('#profilePendingModal .stats-tabs .stats-tab');
        userTabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        // Reload data with new user filter
        loadProfilePendingData();
    }

    function loadProfilePendingData() {
        const container = document.getElementById('profilePendingTableContainer');
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading profile pending data...</p></div>';

        let url = `/operation/dashboard/profile-pending-stats?user_filter=${currentProfileUserFilter}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
                    return;
                }
                updateProfilePendingDisplay(data);
            })
            .catch(error => {
                console.error('Error loading profile pending data:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    function updateProfilePendingDisplay(data) {
        // Update summary cards
        document.getElementById('totalProfilePending').textContent = data.total_profile_pending;
        document.getElementById('totalLeads').textContent = data.total_leads;
        document.getElementById('profilePendingPercentage').textContent = `${data.profile_pending_percentage.toFixed(1)}%`;

        // Update filter display
        const filterText = currentProfileUserFilter.charAt(0).toUpperCase() + currentProfileUserFilter.slice(1);
        document.getElementById('profileFilter').textContent = filterText;

        // Generate table
        const container = document.getElementById('profilePendingTableContainer');
        if (data.profile_pending_leads.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No profile pending leads found.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead ID</th>
                        <th>Executive Name</th>
                        <th>Lead Date & Time</th>
                        <th>Patient Name</th>
                        <th>Patient Gender</th>
                        <th>Location</th>
                        <th>Query</th>
                        <th>Query Remark</th>
                        <th>Shift Type</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.profile_pending_leads.forEach(lead => {
            const date = lead.date_time ? new Date(lead.date_time).toLocaleDateString('en-GB') + ' ' + new Date(lead.date_time).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) : 'N/A';
            const executiveName = lead.executive ? (lead.executive.f_name + ' ' + lead.executive.l_name) : 'N/A';

            tableHTML += `
                <tr>
                    <td><strong>${lead.lead_id || '#' + lead.id}</strong></td>
                    <td>${executiveName}</td>
                    <td>${date}</td>
                    <td>${lead.patient_name || 'N/A'}</td>
                    <td>${lead.patient_gender || 'N/A'}</td>
                    <td>${lead.location || 'N/A'}</td>
                    <td>${lead.query || 'N/A'}</td>
                    <td>${lead.query_remark || 'N/A'}</td>
                    <td>${lead.shift_type || 'N/A'}</td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Ongoing/Stopped Functions
    function openOngoingStoppedModal() {
        document.getElementById('ongoingStoppedModal').style.display = 'flex';
        setupOngoingDateInputs();
        populateOngoingMonthYearDropdowns();
        loadOngoingStoppedData('today');
    }

    function switchOngoingStoppedTab(period) {
        // Update active tab
        const tabs = document.querySelectorAll('#ongoingStoppedModal .stats-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        // Show/hide appropriate selectors
        const selectors = ['ongoingTodaySelector', 'ongoingMonthlySelector', 'ongoingYearlySelector', 'ongoingCustomSelector'];
        selectors.forEach(id => {
            document.getElementById(id).classList.remove('active');
        });

        if (period === 'today') {
            document.getElementById('ongoingTodaySelector').classList.add('active');
            loadOngoingStoppedData('today');
        } else if (period === 'monthly') {
            document.getElementById('ongoingMonthlySelector').classList.add('active');
            const monthSelect = document.getElementById('ongoingMonthSelect');
            if (monthSelect.value) {
                loadOngoingStoppedData('monthly', null, null, monthSelect.value);
            }
        } else if (period === 'yearly') {
            document.getElementById('ongoingYearlySelector').classList.add('active');
            const yearSelect = document.getElementById('ongoingYearSelect');
            if (yearSelect.value) {
                loadOngoingStoppedData('yearly', null, null, yearSelect.value);
            }
        } else if (period === 'custom') {
            document.getElementById('ongoingCustomSelector').classList.add('active');
        }
    }

    function populateOngoingMonthYearDropdowns() {
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        const currentMonth = currentDate.getMonth();

        // Populate months (last 12 months)
        const monthSelect = document.getElementById('ongoingMonthSelect');
        monthSelect.innerHTML = '';

        for (let i = 0; i < 12; i++) {
            const date = new Date(currentYear, currentMonth - i, 1);
            const monthName = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const monthValue = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');

            const option = document.createElement('option');
            option.value = monthValue;
            option.textContent = monthName;
            if (i === 0) option.selected = true; // Current month
            monthSelect.appendChild(option);
        }

        // Populate years (last 5 years)
        const yearSelect = document.getElementById('ongoingYearSelect');
        yearSelect.innerHTML = '';

        for (let year = currentYear; year >= currentYear - 4; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            if (year === currentYear) option.selected = true; // Current year
            yearSelect.appendChild(option);
        }
    }

    function setupOngoingDateInputs() {
        const today = new Date().toISOString().split('T')[0];
        const startDateInput = document.getElementById('ongoingStartDate');
        const endDateInput = document.getElementById('ongoingEndDate');

        startDateInput.max = today;
        endDateInput.max = today;

        // Add event listeners for date validation
        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            if (endDateInput.value && this.value > endDateInput.value) {
                endDateInput.value = this.value;
            }
        });

        endDateInput.addEventListener('change', function() {
            startDateInput.max = this.value;
        });
    }

    function applyOngoingDateFilter() {
        const startDate = document.getElementById('ongoingStartDate').value;
        const endDate = document.getElementById('ongoingEndDate').value;

        if (!startDate || !endDate) {
            alert('Please select both start and end dates.');
            return;
        }

        if (startDate > endDate) {
            alert('Start date cannot be after end date.');
            return;
        }

        loadOngoingStoppedData('custom', startDate, endDate);
    }

    function loadOngoingStoppedData(period, startDate = null, endDate = null, selectedPeriod = null) {
        const container = document.getElementById('ongoingStoppedTableContainer');
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading ongoing/stopped data...</p></div>';

        let url = `/operation/dashboard/ongoing-stopped-stats?period=${period}`;
        if (period === 'custom' && startDate && endDate) {
            url += `&start_date=${startDate}&end_date=${endDate}`;
        } else if (period === 'monthly' && selectedPeriod) {
            url += `&selected_month=${selectedPeriod}`;
        } else if (period === 'yearly' && selectedPeriod) {
            url += `&selected_year=${selectedPeriod}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
                    return;
                }
                updateOngoingStoppedDisplay(data, period);
            })
            .catch(error => {
                console.error('Error loading ongoing/stopped data:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    function updateOngoingStoppedDisplay(data, period) {
        // Update summary cards
        document.getElementById('totalOngoing').textContent = data.total_ongoing;
        document.getElementById('totalLeads').textContent = data.total_leads;
        document.getElementById('ongoingPercentage').textContent = `${data.ongoing_percentage.toFixed(1)}%`;

        // Update period display
        if (period === 'custom') {
            const startDate = new Date(data.start_date).toLocaleDateString('en-GB');
            const endDate = new Date(data.end_date).toLocaleDateString('en-GB');
            document.getElementById('ongoingPeriod').textContent = `${startDate} - ${endDate}`;
        } else {
            document.getElementById('ongoingPeriod').textContent = period.charAt(0).toUpperCase() + period.slice(1);
        }

        // Generate table
        const container = document.getElementById('ongoingStoppedTableContainer');
        if (data.ongoing_leads.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No ongoing leads found for this period.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead ID</th>
                        <th>Date/Time</th>
                        <th>Executive</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Query</th>
                        <th>Status</th>
                        <th>Ongoing/Stopped</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.ongoing_leads.forEach(lead => {
            const date = lead.date_time ? new Date(lead.date_time).toLocaleDateString('en-GB') + ' ' + new Date(lead.date_time).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) : 'N/A';

            tableHTML += `
                <tr>
                    <td><strong>${lead.lead_id || '#' + lead.id}</strong></td>
                    <td>${date}</td>
                    <td>${lead.executive_name || 'N/A'}</td>
                    <td>${lead.customer_name || 'N/A'}</td>
                    <td>${lead.contact_no || 'N/A'}</td>
                    <td>${lead.location || 'N/A'}</td>
                    <td>${lead.query || 'N/A'}</td>
                    <td><span class="status-badge badge-${(lead.status || 'default').toLowerCase().replace(/\s+/g, '-')}">${lead.status || '-'}</span></td>
                    <td><span class="status-badge badge-${(lead.ongoing_stopped || 'default').toLowerCase().replace(/\s+/g, '-')}">${lead.ongoing_stopped || '-'}</span></td>
                    <td>
                        <a href="/operation/operation-leads/${lead.id}" class="btn action-btn view-btn" title="View Lead">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Payment Due Functions
    function openPaymentDueModal() {
        document.getElementById('paymentDueModal').style.display = 'flex';
        setupPaymentDateInputs();
        populatePaymentMonthYearDropdowns();
        loadPaymentDueData('today');
    }

    function switchPaymentDueTab(period) {
        // Update active tab
        const tabs = document.querySelectorAll('#paymentDueModal .stats-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        // Show/hide appropriate selectors
        const selectors = ['paymentTodaySelector', 'paymentMonthlySelector', 'paymentYearlySelector', 'paymentCustomSelector'];
        selectors.forEach(id => {
            document.getElementById(id).classList.remove('active');
        });

        if (period === 'today') {
            document.getElementById('paymentTodaySelector').classList.add('active');
            loadPaymentDueData('today');
        } else if (period === 'monthly') {
            document.getElementById('paymentMonthlySelector').classList.add('active');
            const monthSelect = document.getElementById('paymentMonthSelect');
            if (monthSelect.value) {
                loadPaymentDueData('monthly', null, null, monthSelect.value);
            }
        } else if (period === 'yearly') {
            document.getElementById('paymentYearlySelector').classList.add('active');
            const yearSelect = document.getElementById('paymentYearSelect');
            if (yearSelect.value) {
                loadPaymentDueData('yearly', null, null, yearSelect.value);
            }
        } else if (period === 'custom') {
            document.getElementById('paymentCustomSelector').classList.add('active');
        }
    }

    function setupPaymentDateInputs() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('paymentStartDate').max = today;
        document.getElementById('paymentEndDate').max = today;
    }

    function populatePaymentMonthYearDropdowns() {
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        const currentMonth = currentDate.getMonth();

        // Populate months (last 12 months)
        const monthSelect = document.getElementById('paymentMonthSelect');
        monthSelect.innerHTML = '';

        for (let i = 0; i < 12; i++) {
            const date = new Date(currentYear, currentMonth - i, 1);
            const monthName = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const monthValue = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');

            const option = document.createElement('option');
            option.value = monthValue;
            option.textContent = monthName;
            if (i === 0) option.selected = true; // Current month
            monthSelect.appendChild(option);
        }

        // Populate years (last 5 years)
        const yearSelect = document.getElementById('paymentYearSelect');
        yearSelect.innerHTML = '';

        for (let year = currentYear; year >= currentYear - 4; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            if (year === currentYear) option.selected = true; // Current year
            yearSelect.appendChild(option);
        }
    }

    function applyPaymentDateFilter() {
        const startDate = document.getElementById('paymentStartDate').value;
        const endDate = document.getElementById('paymentEndDate').value;

        if (!startDate || !endDate) {
            alert('Please select both start and end dates.');
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            alert('Start date cannot be after end date.');
            return;
        }

        loadPaymentDueData('custom', startDate, endDate);
    }

    function loadPaymentDueData(period, startDate = null, endDate = null, selectedPeriod = null) {
        const container = document.getElementById('paymentDueTableContainer');
        container.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner-modal"></div>
                <p style="margin-top: 16px; color: #6B7280;">Loading payment due data...</p>
            </div>
        `;

        let url = '{{ route("operation.dashboard.payment-due-stats") }}?period=' + period;
        if (startDate) url += '&start_date=' + startDate;
        if (endDate) url += '&end_date=' + endDate;
        if (selectedPeriod) {
            if (period === 'monthly') {
                url += '&selected_month=' + selectedPeriod;
            } else if (period === 'yearly') {
                url += '&selected_year=' + selectedPeriod;
            }
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePaymentDueDisplay(data.data, period);
                } else {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error: ' + (data.message || 'Failed to load data') + '</div>';
                }
            })
            .catch(error => {
                console.error('Error loading payment due data:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    function updatePaymentDueDisplay(data, period) {
        // Update summary cards
        document.getElementById('totalUnverified').textContent = data.total_count;
        document.getElementById('totalAmount').textContent = '₹' + parseFloat(data.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2});

        // Update period display
        if (period === 'custom') {
            const startDate = new Date(data.start_date).toLocaleDateString('en-GB');
            const endDate = new Date(data.end_date).toLocaleDateString('en-GB');
            document.getElementById('paymentPeriod').textContent = `${startDate} - ${endDate}`;
        } else {
            document.getElementById('paymentPeriod').textContent = period.charAt(0).toUpperCase() + period.slice(1);
        }

        // Generate table
        const container = document.getElementById('paymentDueTableContainer');
        if (data.payments.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No unverified payments found for this period.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead ID</th>
                        <th>Customer</th>
                        <th>Vendor</th>
                        <th>Staff</th>
                        <th>Amount</th>
                        <th>Deployment Date</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.payments.forEach(payment => {
            tableHTML += `
                <tr>
                    <td><strong>${payment.lead_id || '#' + payment.operation_lead_id}</strong></td>
                    <td>${payment.customer_name}</td>
                    <td>${payment.vendor_name}</td>
                    <td>${payment.staff_name}</td>
                    <td><strong>₹${parseFloat(payment.vendor_payment).toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong></td>
                    <td>${payment.deployment_date}</td>
                    <td>${payment.created_at}</td>
                    <td>
                        <a href="${payment.view_url}" class="btn action-btn view-btn" title="View Lead">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Job Request Functions
    function openJobRequestModal() {
        document.getElementById('jobRequestModal').style.display = 'flex';
        loadJobRequestData();
    }

    function loadJobRequestData() {
        const container = document.getElementById('jobRequestTableContainer');
        container.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner-modal"></div>
                <p style="margin-top: 16px; color: #6B7280;">Loading job request data...</p>
            </div>
        `;

        fetch('{{ route("operation.dashboard.job-request-stats") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateJobRequestDisplay(data.data);
                } else {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error: ' + (data.message || 'Failed to load data') + '</div>';
                }
            })
            .catch(error => {
                console.error('Error loading job request data:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    function updateJobRequestDisplay(data) {
        // Update summary cards
        document.getElementById('totalJobRequests').textContent = data.total_job_requests;

        // Generate table
        const container = document.getElementById('jobRequestTableContainer');
        if (data.job_requests.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No job requests found.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Name</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Executive</th>
                        <th>Date/Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.job_requests.forEach(jobRequest => {
            const dateTime = jobRequest.date_time ? new Date(jobRequest.date_time).toLocaleDateString('en-GB') + ' ' + new Date(jobRequest.date_time).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) : 'N/A';

            tableHTML += `
                <tr>
                    <td><strong>${jobRequest.lead_id}</strong></td>
                    <td>${jobRequest.customer_name || 'N/A'}</td>
                    <td>${jobRequest.contact_no || 'N/A'}</td>
                    <td>${jobRequest.name || 'N/A'}</td>
                    <td>${jobRequest.city || 'N/A'}</td>
                    <td><span class="status-badge badge-${(jobRequest.status || 'default').toLowerCase().replace(/\s+/g, '-')}">${jobRequest.status || '-'}</span></td>
                    <td>${jobRequest.executive_name}</td>
                    <td>${dateTime}</td>
                    <td>
                        <a href="${jobRequest.view_url}" class="btn action-btn view-btn" title="View Job Request">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Vendor & Freelancer Payment Functions
    function openVendorFreelancerPaymentModal() {
        document.getElementById('vendorFreelancerPaymentModal').style.display = 'flex';
        loadVendorFreelancerPaymentData();
    }

    function loadVendorFreelancerPaymentData() {
        const container = document.getElementById('vendorFreelancerPaymentTableContainer');
        container.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner-modal"></div>
                <p style="margin-top: 16px; color: #6B7280;">Loading vendor/freelancer payment data...</p>
            </div>
        `;

        fetch('{{ route("operation.dashboard.vendor-freelancer-payment-stats") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateVendorFreelancerPaymentDisplay(data.data);
                } else {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error: ' + (data.message || 'Failed to load data') + '</div>';
                }
            })
            .catch(error => {
                console.error('Error loading vendor/freelancer payment data:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    function updateVendorFreelancerPaymentDisplay(data) {
        // Update summary cards
        document.getElementById('totalUnverifiedPayments').textContent = data.total_unverified;
        document.getElementById('totalPaymentAmount').textContent = '₹' + parseFloat(data.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2});

        // Generate table
        const container = document.getElementById('vendorFreelancerPaymentTableContainer');
        if (data.payments.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No unverified payments found.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead ID</th>
                        <th>Executive</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Vendor/Staff</th>
                        <th>Amount</th>
                        <th>Deployment Date</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.payments.forEach(payment => {
            tableHTML += `
                <tr>
                    <td><strong>${payment.lead_id}</strong></td>
                    <td>${payment.executive_name || 'N/A'}</td>
                    <td>${payment.customer_name}</td>
                    <td>${payment.contact_no}</td>
                    <td>${payment.location}</td>
                    <td>${payment.vendor_staff}</td>
                    <td><strong>₹${parseFloat(payment.vendor_payment).toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong></td>
                    <td>${payment.deployment_date}</td>
                    <td>${payment.created_at}</td>
                    <td>
                        <a href="${payment.view_url}" class="btn action-btn view-btn" title="View Lead">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Recent Leads Functions
    function openRecentLeadsModal() {
        document.getElementById('recentLeadsModal').style.display = 'flex';
        loadRecentLeadsData();
    }

    function loadRecentLeadsData() {
        const container = document.getElementById('recentLeadsTableContainer');
        container.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner-modal"></div>
                <p style="margin-top: 16px; color: #6B7280;">Loading recent leads data...</p>
            </div>
        `;

        fetch('{{ route("operation.dashboard.recent-leads-stats") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateRecentLeadsDisplay(data.data);
                } else {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error: ' + (data.message || 'Failed to load data') + '</div>';
                }
            })
            .catch(error => {
                console.error('Error loading recent leads data:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    function updateRecentLeadsDisplay(data) {
        // Update summary cards
        document.getElementById('totalRecentLeads').textContent = data.total_count;
        
        if (data.latest_lead_date) {
            const latestDate = new Date(data.latest_lead_date);
            document.getElementById('latestLeadDate').textContent = latestDate.toLocaleDateString('en-GB') + ' ' + latestDate.toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'});
        } else {
            document.getElementById('latestLeadDate').textContent = 'N/A';
        }

        // Generate table
        const container = document.getElementById('recentLeadsTableContainer');
        if (data.leads.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No recent leads found.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead ID</th>
                        <th>Date/Time</th>
                        <th>Executive</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Query</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.leads.forEach(lead => {
            const dateTime = lead.date_time ? new Date(lead.date_time).toLocaleDateString('en-GB') + ' ' + new Date(lead.date_time).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) : 'N/A';

            tableHTML += `
                <tr>
                    <td><strong>${lead.lead_id || '#' + lead.id}</strong></td>
                    <td>${dateTime}</td>
                    <td>${lead.executive_name || 'N/A'}</td>
                    <td>${lead.customer_name || 'N/A'}</td>
                    <td>${lead.contact_no || 'N/A'}</td>
                    <td>${lead.location || 'N/A'}</td>
                    <td>${lead.query || 'N/A'}</td>
                    <td><span class="status-badge badge-${(lead.status || 'default').toLowerCase().replace(/\s+/g, '-')}">${lead.status || '-'}</span></td>
                    <td>
                        <a href="/operation/operation-leads/${lead.id}" class="btn action-btn view-btn" title="View Lead">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Unverified Deployment Payments Functions
    function openUnverifiedDeploymentModal() {
        document.getElementById('unverifiedDeploymentModal').style.display = 'flex';
        loadUnverifiedDeploymentData();
    }

    function loadUnverifiedDeploymentData() {
        const container = document.getElementById('unverifiedDeploymentTableContainer');
        container.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner-modal"></div>
                <p style="margin-top: 16px; color: #6B7280;">Loading unverified deployment payments...</p>
            </div>
        `;

        fetch('{{ route("operation.dashboard.unverified-deployment-payments-stats") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateUnverifiedDeploymentDisplay(data.data);
                } else {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error: ' + (data.message || 'Failed to load data') + '</div>';
                }
            })
            .catch(error => {
                console.error('Error loading unverified deployment payments:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    function updateUnverifiedDeploymentDisplay(data) {
        // Update summary cards
        document.getElementById('totalUnverifiedDeployments').textContent = data.total_count;
        document.getElementById('totalUnverifiedAmount').textContent = '₹' + parseFloat(data.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2});

        // Generate table
        const container = document.getElementById('unverifiedDeploymentTableContainer');
        if (data.deployments.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No unverified deployment payments found.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead ID</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Vendor</th>
                        <th>Staff Name</th>
                        <th>Staff Number</th>
                        <th>Payment Term</th>
                        <th>Deployment Date</th>
                        <th>From Date</th>
                        <th>To Date</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Verify Payment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.deployments.forEach(deployment => {
            tableHTML += `
                <tr id="deployment-row-${deployment.id}">
                    <td><strong>${deployment.lead_id}</strong></td>
                    <td>${deployment.customer_name}</td>
                    <td>${deployment.contact_no}</td>
                    <td>${deployment.location}</td>
                    <td>${deployment.vendor_name}</td>
                    <td>${deployment.staff_name}</td>
                    <td>${deployment.staff_number}</td>
                    <td>${deployment.payment_term}</td>
                    <td>${deployment.deployment_date}</td>
                    <td>${deployment.deployment_from_date}</td>
                    <td>${deployment.deployment_to_date}</td>
                    <td><span class="badge bg-warning">${deployment.deployment_status}</span></td>
                    <td><strong>₹${parseFloat(deployment.vendor_payment).toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong></td>
                    <td style="text-align: center;">
                        <div class="form-check d-flex justify-content-center">
                            <input class="form-check-input verify-payment-checkbox-dashboard" 
                                   type="checkbox" 
                                   data-deployment-id="${deployment.id}"
                                   onchange="toggleVerifyPaymentDashboard(${deployment.id}, this.checked)"
                                   title="Verify Payment"
                                   style="cursor: pointer;">
                        </div>
                    </td>
                    <td>
                        <a href="${deployment.view_url}" class="btn action-btn view-btn" title="View Lead">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Toggle Verify Payment Function for Dashboard
    function toggleVerifyPaymentDashboard(deploymentId, isChecked) {
        if (!confirm('Are you sure you want to verify this payment?')) {
            // Revert checkbox if user cancels
            const checkbox = document.querySelector(`.verify-payment-checkbox-dashboard[data-deployment-id="${deploymentId}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
            return;
        }

        $.ajax({
            url: `/operation/operation-leads/deployment/${deploymentId}/toggle-verify-payment`,
            type: 'POST',
            data: {
                verify_payment: isChecked,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    
                    // Remove the row from the table with fade effect
                    const row = document.getElementById(`deployment-row-${deploymentId}`);
                    if (row) {
                        $(row).fadeOut(500, function() {
                            $(this).remove();
                            
                            // Check if table is now empty
                            const tableBody = document.querySelector('#unverifiedDeploymentTableContainer tbody');
                            if (tableBody && tableBody.children.length === 0) {
                                document.getElementById('unverifiedDeploymentTableContainer').innerHTML = 
                                    '<div style="text-align: center; padding: 40px; color: #6B7280;">No unverified deployment payments found.</div>';
                            }
                            
                            // Update the summary cards
                            const currentCount = parseInt(document.getElementById('totalUnverifiedDeployments').textContent);
                            const currentAmount = parseFloat(document.getElementById('totalUnverifiedAmount').textContent.replace('₹', '').replace(/,/g, ''));
                            const deploymentAmount = parseFloat(response.deployment_amount || 0);
                            
                            document.getElementById('totalUnverifiedDeployments').textContent = Math.max(0, currentCount - 1);
                            const newAmount = Math.max(0, currentAmount - deploymentAmount);
                            document.getElementById('totalUnverifiedAmount').textContent = '₹' + newAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});
                        });
                    }
                    
                    // Update the dashboard card count
                    const dashboardCardCount = document.querySelector('.dashboard-card.clickable-card[onclick="openUnverifiedDeploymentModal()"] .card-count');
                    if (dashboardCardCount) {
                        const currentDashboardCount = parseInt(dashboardCardCount.textContent.replace(/,/g, ''));
                        dashboardCardCount.textContent = (Math.max(0, currentDashboardCount - 1)).toLocaleString();
                    }
                } else {
                    toastr.error(response.message || 'Failed to update payment verification status');
                    // Revert checkbox state on error
                    const checkbox = document.querySelector(`.verify-payment-checkbox-dashboard[data-deployment-id="${deploymentId}"]`);
                    if (checkbox) {
                        checkbox.checked = false;
                    }
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                toastr.error('Error updating payment verification status. Please try again.');
                // Revert checkbox state on error
                const checkbox = document.querySelector(`.verify-payment-checkbox-dashboard[data-deployment-id="${deploymentId}"]`);
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        });
    }

    // Pending Callbacks Functions
    function openPendingCallbacksModal() {
        document.getElementById('pendingCallbacksModal').style.display = 'flex';
        loadPendingCallbacksData();
    }

    function loadPendingCallbacksData() {
        const container = document.getElementById('pendingCallbacksTableContainer');
        container.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner-modal"></div>
                <p style="margin-top: 16px; color: #6B7280;">Loading pending callbacks...</p>
            </div>
        `;

        fetch('{{ route("operation.dashboard.pending-callbacks") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePendingCallbacksDisplay(data);
                } else {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error: ' + (data.error || 'Failed to load data') + '</div>';
                }
            })
            .catch(error => {
                console.error('Error loading pending callbacks:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    function formatPendingCallbackDate(dateStr) {
        if (!dateStr) return '-';
        try {
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            const day = String(d.getDate()).padStart(2, '0');
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const mon = months[d.getMonth()];
            const year = d.getFullYear();
            const h = String(d.getHours()).padStart(2, '0');
            const m = String(d.getMinutes()).padStart(2, '0');
            return `${day}-${mon}-${year} ${h}:${m}`;
        } catch (e) { return dateStr || '-'; }
    }

    function updatePendingCallbacksDisplay(data) {
        // Update summary cards
        document.getElementById('totalPendingCallbacks').textContent = data.total_count || 0;

        const container = document.getElementById('pendingCallbacksTableContainer');
        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead No</th>
                        <th>Customer</th>
                        <th>Contact No</th>
                        <th>Location</th>
                        <th>Query</th>
                        <th>Status</th>
                        <th>Call Status</th>
                        <th>Lead Source</th>
                        <th>Call Response Time & Date</th>
                        <th>Unanswered Date & Time</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (!data.callbacks || data.callbacks.length === 0) {
            tableHTML += `
                <tr>
                    <td colspan="12" class="text-center text-muted" style="padding: 40px;">
                        <i class="fas fa-check-circle fa-3x mb-3" style="color: #10b981;"></i>
                        <br>
                        <strong>No pending callbacks found</strong>
                        <br>
                        <small>Callbacks will appear here when there are missed, busy, or failed calls</small>
                    </td>
                </tr>
            `;
        } else {
            data.callbacks.forEach(callback => {
                const contactNo = (callback.customer_number || callback.contact_no || '').toString().replace(/'/g, "\\'");
                const lastUpdated = callback.last_updated ? formatPendingCallbackDate(callback.last_updated) : 'N/A';
                const statusClass = (callback.status || '').toLowerCase().replace(/\s+/g, '-') || 'default';

                let callStatusClass = 'bg-danger';
                let callStatusText = callback.last_call_status || 'Unknown';
                switch ((callback.last_call_status || '').toLowerCase()) {
                    case 'no answer':
                    case 'noanswer':
                        callStatusClass = 'bg-danger';
                        callStatusText = 'Missed Call';
                        break;
                    case 'busy':
                        callStatusClass = 'bg-warning';
                        callStatusText = 'Busy';
                        break;
                    case 'failed':
                        callStatusClass = 'bg-secondary';
                        callStatusText = 'Failed';
                        break;
                    case 'switchedoff':
                    case 'switched off':
                        callStatusClass = 'bg-dark';
                        callStatusText = 'Switched Off';
                        break;
                }

                const leadNoDisplay = callback.lead_no === 'No Lead' ? '<span class="badge bg-secondary">No Lead</span>' : `<strong>${(callback.lead_no || callback.lead_code || '#' + (callback.call_log_id || callback.id || '')).toString().replace(/</g, '&lt;')}</strong>`;
                const customerName = (callback.customer_name || 'N/A').toString().replace(/</g, '&lt;');
                const patientSuffix = callback.patient_name ? '<br><small class="text-muted">(' + (callback.patient_name + '').replace(/</g, '&lt;') + ')</small>' : '';
                // Lead source: show IVR, WhatsApp or Web (from backend)
                const leadSourceRaw = (callback.lead_source || '').toString().toLowerCase();
                const leadSource = leadSourceRaw === 'whatsapp' ? 'WhatsApp' : (leadSourceRaw === 'web' ? 'Web' : (leadSourceRaw === 'ivr' ? 'IVR' : (callback.lead_source || 'IVR')));
                const callResponseDateTime = formatPendingCallbackDate(callback.call_response_datetime || callback.answered_at || callback.response_datetime);
                const unansweredDateTime = formatPendingCallbackDate(callback.unanswered_datetime) !== '-' ? formatPendingCallbackDate(callback.unanswered_datetime) : (callback.last_updated ? formatPendingCallbackDate(callback.last_updated) : '-');

                tableHTML += `
                    <tr>
                        <td>${leadNoDisplay}</td>
                        <td>${customerName}${patientSuffix}</td>
                        <td>
                            <strong>${(callback.customer_number || callback.contact_no || 'N/A').toString().replace(/</g, '&lt;')}</strong>
                            <div class="mt-1">
                                <button onclick="makeCallFromDashboard('${contactNo}')" class="btn btn-sm btn-success" title="Call Back">
                                    <i class="fas fa-phone"></i> Call Back
                                </button>
                            </div>
                        </td>
                        <td>${(callback.location || 'N/A').toString().replace(/</g, '&lt;')}</td>
                        <td>${(callback.query || 'N/A').toString().replace(/</g, '&lt;')}</td>
                        <td><span class="status-badge badge-${statusClass}">${(callback.status || '-').toString().replace(/</g, '&lt;')}</span></td>
                        <td><span class="badge ${callStatusClass}">${callStatusText}</span></td>
                        <td><span class="badge bg-info">${leadSource}</span></td>
                        <td>${callResponseDateTime}</td>
                        <td>${unansweredDateTime}</td>
                        <td><small class="text-muted">${lastUpdated}</small></td>
                        <td>
                            ${callback.view_url ? `<a href="${callback.view_url}" class="btn action-btn view-btn" title="View Lead"><i class="fas fa-eye"></i></a>` : '<span class="text-muted">-</span>'}
                        </td>
                    </tr>
                `;
            });
        }

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Make call from dashboard function
    function makeCallFromDashboard(contactNumber) {
        if (!confirm('Are you sure you want to make this call?')) return;
        
        $.ajax({
            url: `/call-outbound/${contactNumber}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    toastr.success('Call initiated successfully');
                } else {
                    toastr.error(response.message || 'Failed to initiate call');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error initiating call';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            }
        });
    }

    function loadPendingCallbacksCount() {
        fetch('{{ route("operation.dashboard.pending-callbacks") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('pendingCallbacksCount').textContent = data.total_count;
                }
            })
            .catch(error => {
                console.error('Error loading pending callbacks count:', error);
            });
    }

    // Recent Calls Modal Functions
    function openRecentCallsModal() {
        document.getElementById('recentCallsModal').style.display = 'flex';
        loadRecentCallsData();
    }

    function loadRecentCallsData() {
        const container = document.getElementById('recentCallsTableContainer');
        container.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner-modal"></div>
                <p style="margin-top: 16px; color: #6B7280;">Loading recent calls...</p>
            </div>
        `;

        fetch('{{ route("operation.dashboard.recent-calls") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Store all data for filtering
                    allRecentCallsData = data;
                    updateRecentCallsDisplay(data);
                } else {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error: ' + (data.error || 'Failed to load data') + '</div>';
                }
            })
            .catch(error => {
                console.error('Error loading recent calls:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    function updateRecentCallsDisplay(data) {
        // Update summary cards
        document.getElementById('totalRecentCalls').textContent = data.total_count || 0;
        document.getElementById('successfulCalls').textContent = data.successful_count || 0;
        document.getElementById('failedCalls').textContent = data.failed_count || 0;

        // Generate table
        const container = document.getElementById('recentCallsTableContainer');
        
        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Lead No</th>
                        <th>Call For</th>
                        <th>Customer Name</th>
                        <th>Customer Number</th>
                        <th>Call type</th>
                        <th>Call Status</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (!data.calls || data.calls.length === 0) {
            tableHTML += `
                <tr>
                    <td colspan="8" class="text-center text-muted" style="padding: 40px;">
                        <i class="fas fa-phone fa-3x mb-3" style="color: #d1d5db;"></i>
                        <br>
                        <strong>No recent calls found</strong>
                        <br>
                        <small>Calls from the past week will appear here</small>
                    </td>
                </tr>
            `;
        } else {
            data.calls.forEach(call => {
                const statusClass = call.call_status === 'answered' ? 'success' : 
                                   call.call_status === 'busy' ? 'warning' : 
                                   call.call_status === 'failed' ? 'danger' : 
                                   call.call_status === 'no-answer' ? 'secondary' : 'warning';
                
                const leadNoDisplay = call.lead_no === 'No Lead' ? '<span class="badge bg-secondary">No Lead</span>' : `<strong>${call.lead_no}</strong>`;
                
                // Determine call_for badge color
                const callForDisplay = call.call_for_display || call.call_for || 'N/A';
                const callForClass = call.call_for === 'operation_lead' ? 'primary' : 
                                    call.call_for === 'job_request' ? 'success' : 'secondary';
                
                tableHTML += `
                    <tr>
                        <td>${call.call_datetime}</td>
                        <td>${leadNoDisplay}</td>
                        <td><span class="badge bg-${callForClass}">${callForDisplay}</span></td>
                        <td>${call.customer_name || 'N/A'}</td>
                        <td>
                            <strong>${call.customer_number}</strong>
                            <br><button onclick="makeCallFromDashboard('${call.customer_number}')" class="btn btn-sm btn-success mt-1" title="Call Again">
                                <i class="fas fa-phone"></i> Call Again
                            </button>
                        </td>
                        <td><span class="badge bg-light text-dark border">${call.call_direction || '—'}</span></td>
                        <td><span class="badge bg-${statusClass}">${call.call_status}</span></td>
                        <td>${call.duration || 'N/A'}</td>
                    </tr>
                `;
            });
        }

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    function loadRecentCallsCount() {
        fetch('{{ route("operation.dashboard.recent-calls") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('recentCallsCount').textContent = data.total_count || 0;
                }
            })
            .catch(error => {
                console.error('Error loading recent calls count:', error);
            });
    }

    // Search functionality for Recent Calls
    let allRecentCallsData = null; // Store all calls data for filtering

    function filterRecentCalls() {
        const searchTerm = document.getElementById('recentCallsSearchInput').value.toLowerCase();
        
        if (!allRecentCallsData || !allRecentCallsData.calls) {
            return;
        }

        if (searchTerm === '') {
            // Show all calls if search is empty
            updateRecentCallsDisplay(allRecentCallsData);
            return;
        }

        // Filter calls based on search term
        const filteredCalls = allRecentCallsData.calls.filter(call => {
            const phoneNumber = (call.customer_number || '').toLowerCase();
            const customerName = (call.customer_name || '').toLowerCase();
            const leadNo = (call.lead_no || '').toLowerCase();
            const callDir = (call.call_direction || '').toLowerCase();
            
            return phoneNumber.includes(searchTerm) || 
                   customerName.includes(searchTerm) || 
                   leadNo.includes(searchTerm) ||
                   callDir.includes(searchTerm);
        });

        // Create filtered data object
        const filteredData = {
            ...allRecentCallsData,
            calls: filteredCalls,
            total_count: filteredCalls.length
        };

        // Update display with filtered data
        updateRecentCallsDisplay(filteredData);
    }

    function clearRecentCallsSearch() {
        document.getElementById('recentCallsSearchInput').value = '';
        if (allRecentCallsData) {
            updateRecentCallsDisplay(allRecentCallsData);
        }
    }

    // Add Operation Lead Modal Functions
    let addOperationLeadModal = null;

    // Initialize modal on first open
    function initAddOperationLeadModal() {
        if (!addOperationLeadModal) {
            const modalElement = document.getElementById('addOperationLeadModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                addOperationLeadModal = new bootstrap.Modal(modalElement);
            }
        }
        return addOperationLeadModal;
    }

    // Handle edit button click from Active Calls table
    $(document).on('click', '.edit-operation-lead-btn', function() {
        const callDataStr = $(this).data('call-data');
        if (!callDataStr) return;
        
        const callData = typeof callDataStr === 'string' ? JSON.parse(callDataStr) : callDataStr;
        
        // Initialize modal if not already done
        initAddOperationLeadModal();
        
        // Set date/time to current
        const now = new Date();
        const dateTimeStr = now.toISOString().slice(0, 16);
        $('#addOperationLeadForm #date_time').val(dateTimeStr);
        
        // Set executive (hidden)
        $('#addOperationLeadForm #executive').val({{ Auth::user()->id }});
        
        // Populate visible fields
        if (callData.customer_name) {
            $('#addOperationLeadForm #customer_name').val(callData.customer_name);
        }
        if (callData.contact_no) {
            $('#addOperationLeadForm #contact_no').val(callData.contact_no);
        }
        if (callData.location) {
            $('#addOperationLeadForm #location').val(callData.location);
        }
        if (callData.query) {
            $('#addOperationLeadForm #query').val(callData.query);
            toggleQueryRemark(); // Show/hide query remark field
        }
        if (callData.lead_status) {
            $('#addOperationLeadForm #status').val(callData.lead_status);
            toggleStatusRemark(); // Show/hide status remark fields
        }
        if (callData.shift_type) {
            $('#addOperationLeadForm #shift_type').val(callData.shift_type);
        }
        if (callData.patient_name) {
            $('#addOperationLeadForm #patient_name').val(callData.patient_name);
        }
        
        // Show the modal
        if (addOperationLeadModal) {
            addOperationLeadModal.show();
        } else {
            // Fallback to jQuery/bootstrap 4 if bootstrap 5 not available
            $('#addOperationLeadModal').modal('show');
        }
    });

    // Handle Save Lead button click
    $(document).on('click', '#saveOperationLead', function() {
        const form = $('#addOperationLeadForm')[0];
        if (!form) return;
        
        const formData = new FormData(form);
        
        $.ajax({
            url: '{{ route("operation.operation_leads.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message || 'Operation Lead saved successfully');
                } else {
                    alert(response.message || 'Operation Lead saved successfully');
                }
                
                // Close modal
                if (addOperationLeadModal) {
                    addOperationLeadModal.hide();
                } else {
                    $('#addOperationLeadModal').modal('hide');
                }
                
                // Reset form
                form.reset();
                
                // Hide all conditional fields
                $('#query_remark_group').addClass('d-none');
                $('#closed_remark_group').addClass('d-none');
                $('#price_issue_remark_group').addClass('d-none');
                $('#inactive_remark_group').addClass('d-none');
                $('#ongoing_stopped_group').addClass('d-none');
                $('#stopped_details_group').addClass('d-none');
                
                // Reload active calls table
                loadActiveCalls();
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'Could not save lead. Please try again.';
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg);
                } else {
                    alert('Error: ' + errorMsg);
                }
            }
        });
    });

    // Toggle query remark visibility
    function toggleQueryRemark() {
        const query = document.getElementById('query')?.value;
        const queryRemarkGroup = document.getElementById('query_remark_group');
        if (queryRemarkGroup) {
            queryRemarkGroup.classList.toggle('d-none', !query || query === '');
        }
    }

    // Toggle status remark visibility
    function toggleStatusRemark() {
        const status = document.getElementById('status')?.value;
        const priceIssueGroup = document.getElementById('price_issue_remark_group');
        const inactiveGroup = document.getElementById('inactive_remark_group');
        const closedGroup = document.getElementById('closed_remark_group');
        const ongoingStoppedGroup = document.getElementById('ongoing_stopped_group');
        const stoppedDetailsGroup = document.getElementById('stopped_details_group');
        
        if (priceIssueGroup) {
            priceIssueGroup.classList.toggle('d-none', status !== 'price issue');
        }
        if (inactiveGroup) {
            inactiveGroup.classList.toggle('d-none', status !== 'inactive');
        }
        if (closedGroup) {
            closedGroup.classList.toggle('d-none', status !== 'closed');
        }
        if (ongoingStoppedGroup) {
            ongoingStoppedGroup.classList.toggle('d-none', status !== 'closed');
        }
        if (stoppedDetailsGroup) {
            stoppedDetailsGroup.classList.toggle('d-none', status !== 'closed');
        }
    }

    // Setup CSRF token for AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection

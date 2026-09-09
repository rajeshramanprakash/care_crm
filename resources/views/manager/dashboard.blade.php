@extends('manager.layouts.app')
@section('title', 'Dashboard | Manager')

@section('header-css')
    <link rel="stylesheet" href="{{ asset('plugins/charts/chart.css') }}">
    <style>
        body {
            overflow-x: hidden;
            background: #f8fafc;
        }

        .content-wrapper {
            background: #f8fafc !important;
            padding: 20px 0;
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
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
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

        .clickable-card:hover .card-icon {
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
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .card-count {
            color: #1F2937;
            font-size: 28px;
            font-weight: 800;
            margin: 0;
            line-height: 1;
            letter-spacing: -0.5px;
        }

        .card-action {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateX(-10px);
        }

        .clickable-card:hover .card-action {
            opacity: 1;
            transform: translateX(0);
        }

        .clickable-card:hover .card-action svg {
            animation: slideRight 0.3s ease;
        }

        @keyframes slideRight {
            0% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
        }

        .card-icon.leads {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .card-icon.users {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .card-icon.tasks {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .modal-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 24px 32px;
            border: none;
        }

        .modal-title {
            color: #1F2937;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-body {
            padding: 32px;
            background: #f8fafc;
        }

        .modal-footer {
            border: none;
            padding: 24px 32px;
            background: white;
        }

        .btn-close {
            background: none;
            border: none;
            color: #6B7280;
            font-size: 24px;
            opacity: 1;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        /* Stats Tabs */
        .stats-tabs {
            display: flex;
            background: white;
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .stats-tab {
            flex: 1;
            padding: 12px 16px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #6B7280;
            font-size: 14px;
        }

        .stats-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* Period Selectors */
        .period-selectors {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            align-items: center;
            flex-wrap: wrap;
        }

        .period-selector {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .period-selector label {
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }

        .period-selector select,
        .period-selector input {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            background: white;
            color: #374151;
            transition: all 0.3s ease;
        }

        .period-selector select:focus,
        .period-selector input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Stats Summary */
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-item {
            background: white;
            padding: 8px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 18px;
            font-weight: 800;
            color: #1F2937;
            margin: 0;
            line-height: 1;
        }

        .stat-label {
            font-size: 12px;
            color: #6B7280;
            margin: 8px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 600;
        }

        /* Table Styles */
        .leads-table-container {
            max-height: 450px;
            overflow-y: auto;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .leads-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .leads-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 10;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .leads-table td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            color: #374151;
            font-size: 14px;
        }

        .leads-table tbody tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
            display: inline-block;
        }

        .status-follow-up { background: #dbeafe; color: #1e40af; }
        .status-future-prospect { background: #fef3c7; color: #d97706; }
        .status-prospect { background: #d1fae5; color: #059669; }
        .status-no-response { background: #fce7f3; color: #be185d; }
        .status-price-issue { background: #e9d5ff; color: #7c3aed; }
        .status-duplicate { background: #fee2e2; color: #dc2626; }
        .status-spam { background: #f3f4f6; color: #6b7280; }


        /* Action Button Styles */
        .action-btn {
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            transition: all 0.3s ease;
            font-size: 12px;
        }

        .action-btn:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            transform: translateY(-1px);
        }

        .action-btn i {
            font-size: 14px;
        }

        /* View Modal Styles */
        #viewLeadModal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        #viewLeadModal .modal-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 24px 32px;
            border: none;
        }

        #viewLeadModal .modal-title {
            color: #1F2937;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #viewLeadModal .modal-body {
            padding: 32px;
            background: #f8fafc;
        }

        #viewLeadModal .modal-footer {
            border: none;
            padding: 24px 32px;
            background: white;
        }

        #viewLeadModal .btn-close {
            background: none;
            border: none;
            color: #6B7280;
            font-size: 24px;
            opacity: 1;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        #viewLeadModal .btn-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        /* Users Modal Styles */
        #usersModal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        #usersModal .modal-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 24px 32px;
            border: none;
        }

        #usersModal .modal-title {
            color: #1F2937;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #usersModal .modal-body {
            padding: 32px;
            background: #f8fafc;
        }

        #usersModal .modal-footer {
            border: none;
            padding: 24px 32px;
            background: white;
        }

        #usersModal .btn-close {
            background: none;
            border: none;
            color: #6B7280;
            font-size: 24px;
            opacity: 1;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        #usersModal .btn-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        /* View User Modal Styles */
        #viewUserModal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        #viewUserModal .modal-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 24px 32px;
            border: none;
        }

        #viewUserModal .modal-title {
            color: #1F2937;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #viewUserModal .modal-body {
            padding: 32px;
            background: #f8fafc;
        }

        #viewUserModal .modal-footer {
            border: none;
            padding: 24px 32px;
            background: white;
        }

        #viewUserModal .btn-close {
            background: none;
            border: none;
            color: #6B7280;
            font-size: 24px;
            opacity: 1;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        #viewUserModal .btn-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        /* View Task Modal Styles */
        #viewTaskModal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        #viewTaskModal .modal-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 24px 32px;
            border: none;
        }

        #viewTaskModal .modal-title {
            color: #1F2937;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #viewTaskModal .modal-body {
            padding: 32px;
            background: #f8fafc;
        }

        #viewTaskModal .modal-footer {
            border: none;
            padding: 24px 32px;
            background: white;
        }

        #viewTaskModal .btn-close {
            background: none;
            border: none;
            color: #6B7280;
            font-size: 24px;
            opacity: 1;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        #viewTaskModal .btn-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        /* Profile Image Styles */
        .profile-image-container {
            display: inline-block;
            position: relative;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        /* Custom Scrollbar */
        .leads-table-container::-webkit-scrollbar {
            width: 6px;
        }

        .leads-table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .leads-table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .leads-table-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Calendar Modal Styles */
        .calendar-navigation {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .calendar-legend {
            background: white;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-block;
        }

        .legend-color.follow-up {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }

        .legend-color.prospect {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .legend-text {
            font-weight: 500;
            color: #374151;
            font-size: 0.9rem;
        }

        .calendar-container {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

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

        .count-badge.prospect {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        /* Calendar Leads Modal Styles */
        .leads-table-container {
            max-height: 500px;
            overflow-y: auto;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .leads-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .leads-table th {
            background: #f8fafc;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 10;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .leads-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            color: #374151;
            font-size: 0.85rem;
        }

        .leads-table tbody tr:hover {
            background: #f8fafc;
        }

        .leads-table .text-center {
            padding: 40px 20px;
        }

        .leads-table .text-center i {
            color: #d1d5db;
        }

        /* Edit Lead modal above Pending Callbacks modal when both open */
        #editLeadModal.modal { z-index: 1060 !important; }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .period-selectors {
                flex-direction: column;
                align-items: stretch;
            }

            .stats-summary {
                grid-template-columns: repeat(2, 1fr);
            }

            .modal-body {
                padding: 20px;
            }

            .dashboard-header {
                padding: 16px;
            }

            /* Calendar Modal Responsive */
            .calendar-navigation,
            .calendar-legend {
                padding: 12px;
            }

            .calendar-container {
                padding: 12px;
            }

            .calendar-day {
                min-height: 40px;
                padding: 3px;
            }

            .day-number {
                font-size: 0.7rem;
            }

            .count-badge {
                font-size: 0.55rem;
                padding: 1px 2px;
            }

            .leads-table th,
            .leads-table td {
                padding: 8px 6px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .calendar-day {
                min-height: 35px;
                padding: 2px;
            }

            .day-number {
                font-size: 0.65rem;
            }

            .count-badge {
                font-size: 0.5rem;
                padding: 1px 2px;
            }

            .leads-table th,
            .leads-table td {
                padding: 6px 4px;
                font-size: 0.75rem;
            }
        }
    </style>
@endsection

@section('main')
    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1 class="dashboard-title">
                    <i class="fas fa-tachometer-alt"></i>
                    {{ $page_heading }}
                </h1>
                <p class="dashboard-subtitle">Welcome back! Here's your team's today's leads overview.</p>
            </div>

            <!-- Dashboard Cards -->
            <div class="stats-grid">
                <div class="dashboard-card clickable-card" onclick="openLeadsModal()">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon leads">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <p class="card-title">Today's Leads</p>
                                <h3 class="card-count">{{ number_format($todayLeads) }}</h3>
                            </div>
                        </div>
                        <div class="card-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card clickable-card" onclick="openUsersModal()">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon users">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <p class="card-title">Team Members</p>
                                <h3 class="card-count">{{ number_format($activeUsers + $inactiveUsers) }}</h3>
                            </div>
                        </div>
                        <div class="card-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card clickable-card" onclick="openTasksModal()">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon tasks">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div>
                                <p class="card-title">Tasks</p>
                                <h3 class="card-count">{{ number_format($totalTasks) }}</h3>
                            </div>
                        </div>
                        <div class="card-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card clickable-card" onclick="openCalendarModalManager()">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon tasks">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <p class="card-title">Follow-up Calendar</p>
                                <h3 class="card-count" id="managerCalendarCount" style="font-size: 18px; font-weight: 600;">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </h3>
                            </div>
                        </div>
                        <div class="card-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card clickable-card" onclick="openRecentLeadsModalManager()" style="cursor: pointer;">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon" style="background: linear-gradient(135deg, #ec4899, #be185d);">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <p class="card-title">Recent Leads</p>
                                <h3 class="card-count">25</h3>
                            </div>
                        </div>
                        <div class="card-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Recent Calls Card -->
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

                <!-- Pending Callbacks Card -->
                <div class="dashboard-card clickable-card" onclick="openPendingCallbacksModal()" style="cursor: pointer;">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                                <i class="fas fa-phone-slash"></i>
                            </div>
                            <div>
                                <p class="card-title">Pending Callbacks</p>
                                <h3 class="card-count" id="pendingCallbacksCount">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </h3>
                            </div>
                        </div>
                        <div class="card-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leads Statistics Modal -->
    <div class="modal fade" id="leadsModal" tabindex="-1" aria-labelledby="leadsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-chart-bar"></i>
                        Team's Leads Statistics
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Stats Tabs -->
                    <div class="stats-tabs">
                        <div class="stats-tab active" onclick="switchStatsTab('today')">Today</div>
                        <div class="stats-tab" onclick="switchStatsTab('monthly')">Monthly</div>
                        <div class="stats-tab" onclick="switchStatsTab('yearly')">Yearly</div>
                        <div class="stats-tab" onclick="switchStatsTab('custom')">Custom Range</div>
                    </div>

                    <!-- Period Selectors -->
                    <div class="period-selectors">
                        <!-- Monthly Selector -->
                        <div class="period-selector" id="monthlySelector" style="display: none;">
                            <label>Select Month:</label>
                            <select id="selectedMonth" onchange="applyDateFilter()">
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                            <select id="selectedYear" onchange="applyDateFilter()">
                                @for($year = date('Y'); $year >= 2020; $year--)
                                    <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                        </div>

                        <!-- Yearly Selector -->
                        <div class="period-selector" id="yearlySelector" style="display: none;">
                            <label>Select Year:</label>
                            <select id="yearlySelect" onchange="applyDateFilter()">
                                @for($year = date('Y'); $year >= 2020; $year--)
                                    <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                        </div>

                        <!-- Custom Range Selector -->
                        <div class="period-selector" id="customSelector" style="display: none;">
                            <label>From:</label>
                            <input type="date" id="startDate" onchange="applyDateFilter()">
                            <label>To:</label>
                            <input type="date" id="endDate" onchange="applyDateFilter()">
                        </div>
                    </div>

                    <!-- Stats Summary -->
                    <div class="stats-summary" id="statsSummary">
                        <!-- Stats will be loaded here -->
                    </div>

                    <!-- Leads Table -->
                    <div class="leads-table-container">
                        <table class="leads-table" id="leadsTable">
                            <thead>
                                <tr>
                                    <th>Lead No.</th>
                                    <th>Date</th>
                                    <th>Executive</th>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Location</th>
                                    <th>Source</th>
                                    <th>Query</th>
                                    <th>Status</th>
                                    <th>Stage</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="leadsTableBody">
                                <!-- Leads data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- Calendar Modal (Manager) -->
<div class="modal fade" id="calendarModalManager" tabindex="-1" aria-labelledby="calendarModalManagerLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-calendar-alt"></i>
                    Follow-up Calendar
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Calendar Navigation -->
                <div class="calendar-navigation mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="prevMonthManager">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h5 class="mb-0" id="currentMonthYearManager"></h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="nextMonthManager">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Calendar Legend -->
                <div class="calendar-legend mb-3">
                    <div class="d-flex justify-content-center gap-4">
                        <div class="legend-item">
                            <span class="legend-color follow-up"></span>
                            <span class="legend-text">Follow-up</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color prospect"></span>
                            <span class="legend-text">Future Prospect</span>
                        </div>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="calendar-container">
                    <div class="calendar-grid" id="calendarGridManager">
                        <!-- Calendar will be generated here -->
                    </div>
                </div>

                <!-- View Leads Button -->
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-primary" id="viewLeadsBtnManager" onclick="openCalendarLeadsModalManager()" disabled>
                        <i class="fas fa-list"></i> View Leads for Selected Date
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Leads Modal (Manager) -->
<div class="modal fade" id="calendarLeadsModalManager" tabindex="-1" aria-labelledby="calendarLeadsModalManagerLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-list"></i>
                    <span id="calendarLeadsTitleManager">Leads for Selected Date</span>
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="leads-table-container">
                    <table class="leads-table" id="calendarLeadsTableManager">
                        <thead>
                            <tr>
                                <th>Lead No.</th>
                                <th>Executive</th>
                                <th>Status</th>
                                <th>Customer</th>
                                <th>Contact No.</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="calendarLeadsTableBodyManager">
                            <tr>
                                <td colspan="6" class="text-center text-muted">
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
</div>

<!-- View Lead Modal -->
<div class="modal fade" id="viewLeadModal" tabindex="-1" aria-labelledby="viewLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewLeadModalLabel">
                    <i class="fas fa-eye me-2"></i>Lead Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-hashtag me-1"></i> Lead No:</label>
                            <p id="view_lead_no"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-calendar-alt me-1"></i> Date:</label>
                            <p id="view_date"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-user-tie me-1"></i> Executive:</label>
                            <p id="view_executive"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-user me-1"></i> Customer Name:</label>
                            <p id="view_customer_name"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-address-book me-1"></i> Contact Type:</label>
                            <p id="view_contact_type"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-phone me-1"></i> Contact No:</label>
                            <p id="view_contact_no"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-map-marker-alt me-1"></i> Location:</label>
                            <p id="view_location"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-globe me-1"></i> Lead Source:</label>
                            <p id="view_lead_source"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-question-circle me-1"></i> Query:</label>
                            <p id="view_query"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-info-circle me-1"></i> Status:</label>
                            <p id="view_status"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-layer-group me-1"></i> Stage:</label>
                            <p id="view_stage"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded" id="view_future_date_group" style="display: none;">
                            <label class="fw-bold"><i class="fa fa-calendar-alt me-1"></i> Future Contact Date:</label>
                            <p id="view_future_prospect_date"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded" id="view_close_rate_group" style="display: none;">
                            <label class="fw-bold"><i class="fa fa-rupee-sign me-1"></i> Rate:</label>
                            <p id="view_prospect_rate"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Users Modal -->
<div class="modal fade" id="usersModal" tabindex="-1" aria-labelledby="usersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="usersModalLabel">
                    <i class="fas fa-users me-2"></i>Team Members
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Stats Summary -->
                <div class="stats-summary mb-4">
                    <div class="stat-item">
                        <h3 class="stat-value text-success">{{ number_format($activeUsers) }}</h3>
                        <p class="stat-label">Active Users</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="stat-value text-warning">{{ number_format($inactiveUsers) }}</h3>
                        <p class="stat-label">Inactive Users</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="stat-value text-primary">{{ number_format($activeUsers + $inactiveUsers) }}</h3>
                        <p class="stat-label">Total Team</p>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="leads-table-container">
                    <table class="leads-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Leads Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            @foreach($teamMembers as $member)
                            <tr>
                                <td><strong>{{ $member->f_name }} {{ $member->l_name }}</strong></td>
                                <td>{{ $member->email }}</td>
                                <td>{{ $member->mobile }}</td>
                                <td>
                                    @php
                                        $leadsType = match($member->role) {
                                            'sales', 'sales manager' => 'Sales Leads',
                                            'operation', 'operation manager' => 'Operation Leads',
                                            default => 'General Leads'
                                        };
                                        $leadsTypeClass = match($member->role) {
                                            'sales', 'sales manager' => 'bg-success',
                                            'operation', 'operation manager' => 'bg-warning',
                                            default => 'bg-info'
                                        };
                                    @endphp
                                    <span class="badge {{ $leadsTypeClass }}">{{ $leadsType }}</span>
                                </td>
                                <td>
                                    @if($member->status == 1)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-warning">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="action-btn" onclick="viewUser({{ $member->id }})" title="View User">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel">
                    <i class="fas fa-user me-2"></i>User Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Profile Image Section -->
                <div class="text-center mb-4">
                    <div class="profile-image-container">
                        <img id="view_user_image" src="" alt="Profile Image" class="profile-image" onerror="this.src='{{ asset('images/default-user.png') }}'">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-user me-1"></i> Full Name:</label>
                            <p id="view_user_name"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-envelope me-1"></i> Email:</label>
                            <p id="view_user_email"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-phone me-1"></i> Mobile:</label>
                            <p id="view_user_mobile"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-map-marker-alt me-1"></i> Location:</label>
                            <p id="view_user_location"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-user-tie me-1"></i> Parent Member:</label>
                            <p id="view_user_parent"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-briefcase me-1"></i> Role:</label>
                            <p id="view_user_role"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-chart-line me-1"></i> Leads Type:</label>
                            <p id="view_user_leads_type"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-info-circle me-1"></i> Status:</label>
                            <p id="view_user_status"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-calendar-alt me-1"></i> Join Date:</label>
                            <p id="view_user_join_date"></p>
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-id-card me-1"></i> User ID:</label>
                            <p id="view_user_id"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tasks Modal -->
<div class="modal fade" id="tasksModal" tabindex="-1" aria-labelledby="tasksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tasksModalLabel">
                    <i class="fas fa-tasks me-2"></i>Task Management
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Task Tabs -->
                <div class="stats-tabs">
                    <div class="stats-tab active" onclick="switchTaskTab('assigned-to-me')">Assigned to Me</div>
                    <div class="stats-tab" onclick="switchTaskTab('assigned-by-me')">Assigned by Me</div>
                    <div class="stats-tab" onclick="switchTaskTab('parent-manager-tasks')">Parent Manager Tasks</div>
                    <div class="stats-tab" onclick="switchTaskTab('all-tasks')">All Tasks</div>
                </div>

                <!-- Add Task Button -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="period-selectors">
                        <div class="period-selector">
                            <label>Filter by Date:</label>
                            <input type="date" id="taskFilterDate" onchange="filterTasksByDate()">
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="openAddTaskModal()">
                        <i class="fas fa-plus me-1"></i> Add New Task
                    </button>
                </div>

                <!-- Tasks Table -->
                <div class="leads-table-container">
                    <table class="leads-table" id="tasksTable">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Assigned To</th>
                                <th>Assigned By</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="tasksTableBody">
                            <!-- Tasks data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTaskModalLabel">
                    <i class="fas fa-plus me-2"></i>Add New Task
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="addTaskForm">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Task Title *</label>
                            <input type="text" class="form-control" id="taskTitle" name="title" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="taskDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign To *</label>
                            <select class="form-control" id="taskAssignedTo" name="assigned_to" required>
                                <option value="">Select User</option>
                                <option value="{{ auth()->user()->id }}">Myself</option>
                                @foreach($teamMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->f_name }} {{ $member->l_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority *</label>
                            <select class="form-control" id="taskPriority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date *</label>
                            <input type="date" class="form-control" id="taskDueDate" name="due_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="taskStatus" name="status">
                                <option value="pending" selected>Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="saveTask()">
                    <i class="fa fa-save me-1"></i> Save Task
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTaskModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Task
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editTaskForm">
                    <input type="hidden" id="editTaskId" name="task_id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Task Title *</label>
                            <input type="text" class="form-control" id="editTaskTitle" name="title" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="editTaskDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign To *</label>
                            <select class="form-control" id="editTaskAssignedTo" name="assigned_to" required>
                                <option value="">Select User</option>
                                <option value="{{ auth()->user()->id }}">Myself</option>
                                @foreach($teamMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->f_name }} {{ $member->l_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority *</label>
                            <select class="form-control" id="editTaskPriority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date *</label>
                            <input type="date" class="form-control" id="editTaskDueDate" name="due_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="editTaskStatus" name="status">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="updateTask()">
                    <i class="fa fa-save me-1"></i> Update Task
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Task Modal -->
<div class="modal fade" id="viewTaskModal" tabindex="-1" aria-labelledby="viewTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTaskModalLabel">
                    <i class="fas fa-eye me-2"></i>Task Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-tasks me-1"></i> Task Title:</label>
                            <p id="view_task_title"></p>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-align-left me-1"></i> Description:</label>
                            <p id="view_task_description"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-user me-1"></i> Assigned To:</label>
                            <p id="view_task_assigned_to"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-user-tie me-1"></i> Assigned By:</label>
                            <p id="view_task_assigned_by"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-flag me-1"></i> Priority:</label>
                            <p id="view_task_priority"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-info-circle me-1"></i> Status:</label>
                            <p id="view_task_status"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-calendar-alt me-1"></i> Due Date:</label>
                            <p id="view_task_due_date"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 p-3 bg-light rounded">
                            <label class="fw-bold"><i class="fa fa-clock me-1"></i> Created:</label>
                            <p id="view_task_created"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Recent Leads Modal (Manager) -->
<div class="modal fade" id="recentLeadsModalManager" tabindex="-1" aria-labelledby="recentLeadsModalManagerLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-star"></i>
                    Recent Leads (25 Most Recent)
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Recent Leads Table -->
                <div class="leads-table-container">
                    <table class="leads-table" id="recentLeadsTableManager">
                        <thead>
                            <tr>
                                <th>Lead No</th>
                                <th>Date</th>
                                <th>Executive</th>
                                <th>Customer</th>
                                <th>Contact No</th>
                                <th>Location</th>
                                <th>Query</th>
                                <th>Status</th>
                                <th>Stage</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="recentLeadsTableBodyManager">
                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                    <br>Loading recent leads...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Calls Modal -->
    <div class="modal fade" id="recentCallsModal" tabindex="-1" aria-labelledby="recentCallsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-phone"></i>
                        Recent Calls (Past Week)
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Summary Cards -->
                    <div class="stats-summary" id="recentCallsStatsSummary">
                        <div class="stat-item">
                            <h3 class="stat-value" id="totalRecentCalls">-</h3>
                            <p class="stat-label">Total Calls</p>
                        </div>
                        <div class="stat-item">
                            <h3 class="stat-value" id="successfulCalls">-</h3>
                            <p class="stat-label">Successful</p>
                        </div>
                        <div class="stat-item">
                            <h3 class="stat-value" id="failedCalls">-</h3>
                            <p class="stat-label">Failed</p>
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
                            <input type="text" id="recentCallsSearchInput" class="form-control" placeholder="Search by phone number, customer name, executive name, or lead number..." onkeyup="filterRecentCalls()">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="clearRecentCallsSearch()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Calls Table -->
                    <div class="leads-table-container">
                        <table class="leads-table" id="recentCallsTable">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Lead No</th>
                                    <th>Executive</th>
                                    <th>Customer Name</th>
                                    <th>Customer Number</th>
                                    <th>Call type</th>
                                    <th>Call Status</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody id="recentCallsTableBody">
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                        <br>Loading recent calls...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Callbacks Modal -->
    <div class="modal fade" id="pendingCallbacksModal" tabindex="-1" aria-labelledby="pendingCallbacksModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-phone-slash"></i>
                        Pending Callbacks (Missed/Busy/Failed Calls)
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Summary -->
                    <div class="stats-summary mb-3">
                        <div class="stat-item">
                            <h3 class="stat-value" id="totalPendingCallbacks" style="color: #dc2626;">-</h3>
                            <p class="stat-label">Total Pending</p>
                        </div>
                    </div>

                    <!-- Pending Callbacks Table -->
                    <div class="leads-table-container">
                        <table class="leads-table" id="pendingCallbacksTable">
                            <thead>
                                <tr>
                                    <th>Lead No</th>
                                    <th>Customer</th>
                                    <th>Contact No</th>
                                    <th>Location</th>
                                    <th>Query</th>
                                    <th>Status</th>
                                    <th>Call Status</th>
                                    <th>Last Updated</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="pendingCallbacksTableBody">
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                        <br>Loading pending callbacks...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Lead Modal (for Recent Leads inline edit) -->
    <div class="modal fade" id="editLeadModal" tabindex="-1" role="dialog" aria-labelledby="editLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #d97a1a">
                    <h5 class="modal-title" id="editLeadModalLabel" style="color: #ffffff">Edit Lead</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editLeadForm">
                        <input type="hidden" id="edit_lead_id" name="id">
                        <input type="hidden" id="edit_date" name="date">
                        <input type="hidden" id="edit_executive" name="executive">
                        <input type="hidden" id="edit_contact_type" name="contact_type">
                        <input type="hidden" id="edit_contact_no" name="contact_no">
                        <input type="hidden" id="edit_lead_source" name="lead_source">
                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_customer_name" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="edit_customer_name" name="customer_name" required>
                        </div>
                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_patient_name" class="form-label">Patient Name</label>
                            <input type="text" class="form-control" id="edit_patient_name" name="patient_name" placeholder="Enter patient name if different from customer">
                        </div>
                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_patient_gender" class="form-label">Patient Gender</label>
                            <select class="form-control" id="edit_patient_gender" name="patient_gender">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="edit_age" name="age" min="0" max="150" placeholder="Enter age">
                        </div>
                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_location" class="form-label">Location</label>
                            <select class="form-control location-select" id="edit_location" name="location" required>
                                <option value="">Select Location</option>
                                @foreach(($locations ?? collect()) as $location)
                                    <option value="{{ $location->name }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_query" class="form-label">Query</label>
                            <select class="form-control" id="edit_query" name="query" required>
                                <option value="">Select Query</option>
                                @foreach (($services ?? collect()) as $service)
                                    <option value="{{ $service->name }}">{{ $service->name }}</option>
                                @endforeach
                                <option value="job request">Job Request</option>
                            </select>
                        </div>
                        <div class="form-group mb-3" id="edit_query_remarks_group" style="display: none;">
                            <label for="edit_query_remarks" class="form-label">Query Remarks</label>
                            <textarea class="form-control" id="edit_query_remarks" name="query_remarks" rows="3" placeholder="Enter remarks for the selected query"></textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-control" id="edit_status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="follow-up">Follow-up</option>
                                <option value="future prospect">Future Prospect</option>
                                <option value="prospect">Prospect</option>
                                <option value="no response">No Response</option>
                                <option value="price issue">Price Issue</option>
                                <option value="duplicate">Duplicate</option>
                                <option value="spam">Spam</option>
                            </select>
                        </div>
                        @include('partials.lead-status-remarks-fields', [
                            'fieldPrefix' => 'edit',
                            'aiGenerateUrlTemplate' => url('/manager/leads/__ID__/status-remark/generate-ai'),
                        ])
                        <div class="form-group mb-3">
                            <label for="edit_stage" class="form-label">Stage</label>
                            <select class="form-control" id="edit_stage" name="stage" required>
                                <option value="">Select Stage</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="closed">Closed</option>
                                <option value="profile required">Profile Required</option>
                            </select>
                        </div>
                        <div class="form-group mb-3" id="edit_future_prospect_date_group" style="display: none;">
                            <label for="edit_future_prospect_date" class="form-label">Future Contact Date</label>
                            <input type="date" class="form-control" id="edit_future_prospect_date" name="future_prospect_date">
                        </div>
                        <div class="form-group mb-3" id="edit_prospect_close_rate_group" style="display: none;">
                            <label for="edit_prospect_rate" class="form-label">Rate (₹)</label>
                            <input type="number" class="form-control" id="edit_prospect_rate" name="prospect_rate" min="0" step="0.01">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateLead">Update Lead</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('footer-script')
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script>
        let currentPeriod = 'today';
        let currentTaskTab = 'assigned-to-me';

        function openLeadsModal() {
            const modal = new bootstrap.Modal(document.getElementById('leadsModal'));
            modal.show();
            loadLeadsData();
        }

        function openUsersModal() {
            const modal = new bootstrap.Modal(document.getElementById('usersModal'));
            modal.show();
        }

        function openTasksModal() {
            const modal = new bootstrap.Modal(document.getElementById('tasksModal'));
            modal.show();
            loadTasksData();
        }

        function openCalendarModalManager() {
            const modal = new bootstrap.Modal(document.getElementById('calendarModalManager'));
            modal.show();

            // Initialize calendar data and render
            currentDateManager = new Date();
            calendarDataManager = {};

            // Render calendar immediately, then load data
            renderCalendarManager();
            loadCalendarDataManager();
        }

        // Manager Calendar logic (mirrors sales)
        let currentDateManager = new Date();
        let calendarDataManager = {};
        let selectedDateManager = null;

        function loadCalendarDataManager() {
            const year = currentDateManager.getFullYear();
            const month = currentDateManager.getMonth() + 1;


            fetch(`{{ route('manager.dashboard.calendar-data') }}?year=${year}&month=${month}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    calendarDataManager = data.calendar_data || {};
                    renderCalendarManager();
                })
                .catch(error => {
                    console.error('Error loading calendar data:', error);
                });
        }

        function renderCalendarManager() {
            const year = currentDateManager.getFullYear();
            const month = currentDateManager.getMonth();


            document.getElementById('currentMonthYearManager').textContent =
                currentDateManager.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

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
                const dayData = calendarDataManager[dateStr] || { follow_up: 0, future_prospect: 0 };
                const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();

                let dayClass = 'calendar-day';
                if (isToday) dayClass += ' today';

                calendarHTML += `<div class="${dayClass}" data-date="${dateStr}" onclick=\"selectDateManager('${dateStr}')\">
                    <div class="day-number">${day}</div>
                    <div class="day-counts">`;

                if (dayData.follow_up > 0) {
                    calendarHTML += `<div class="count-badge follow-up">${dayData.follow_up} F</div>`;
                }
                if (dayData.future_prospect > 0) {
                    calendarHTML += `<div class="count-badge prospect">${dayData.future_prospect} FP</div>`;
                }

                calendarHTML += `</div></div>`;
            }

            const remainingCells = 42 - (startingDayOfWeek + daysInMonth);
            for (let i = 1; i <= remainingCells; i++) {
                calendarHTML += `<div class="calendar-day other-month">
                    <div class="day-number">${i}</div>
                </div>`;
            }

            document.getElementById('calendarGridManager').innerHTML = calendarHTML;
        }

        function selectDateManager(dateStr) {
            document.querySelectorAll('#calendarGridManager .calendar-day.selected').forEach(day => {
                day.classList.remove('selected');
            });

            const selectedDay = document.querySelector(`#calendarGridManager [data-date="${dateStr}"]`);
            if (selectedDay) {
                selectedDay.classList.add('selected');
            }

            selectedDateManager = dateStr;
            const date = new Date(dateStr);
            const formattedDate = date.toLocaleDateString('en-US', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });
            const btn = document.getElementById('viewLeadsBtnManager');
            btn.disabled = false;
            btn.textContent = `View Leads for ${formattedDate}`;
        }

        function openCalendarLeadsModalManager() {
            if (!selectedDateManager) {
                alert('Please select a date first');
                return;
            }
            const modal = new bootstrap.Modal(document.getElementById('calendarLeadsModalManager'));
            modal.show();
            loadDateLeadsManager(selectedDateManager);
        }

        function loadDateLeadsManager(dateStr) {
            const date = new Date(dateStr);
            const formattedDate = date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('calendarLeadsTitleManager').textContent = `Leads for ${formattedDate}`;

            fetch(`{{ route('manager.dashboard.date-leads') }}?date=${dateStr}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Date leads data:', data); // Debug log

                    if (data.error) {
                        console.error('Error:', data.error);
                        const tableBody = document.getElementById('calendarLeadsTableBodyManager');
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                    <br>Error: ${data.error}
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    const allLeads = [];
                    if (data.follow_up_leads && Array.isArray(data.follow_up_leads)) {
                        allLeads.push(...data.follow_up_leads.map(lead => ({...lead, status: 'follow-up'})));
                    }
                    if (data.prospect_leads && Array.isArray(data.prospect_leads)) {
                        allLeads.push(...data.prospect_leads.map(lead => ({...lead, status: 'future prospect'})));
                    }

                    console.log('All leads:', allLeads); // Debug log

                    const tableBody = document.getElementById('calendarLeadsTableBodyManager');
                    if (allLeads.length > 0) {
                        let tableHTML = '';
                        allLeads.forEach(lead => {
                            const statusClass = lead.status === 'follow-up' ? 'badge bg-primary' : 'badge bg-warning';
                            const statusText = lead.status === 'follow-up' ? 'Follow-up' : 'Future Prospect';
                            const leadNo = lead.formatted_id || `CH${String(lead.id).padStart(8, '0')}`;

                            tableHTML += `
                                <tr>
                                    <td><strong>${leadNo}</strong></td>
                                    <td>${lead.executive_name || 'N/A'}</td>
                                    <td><span class="${statusClass}">${statusText}</span></td>
                                    <td>${lead.customer_name || 'N/A'}</td>
                                    <td>${lead.contact_no || 'N/A'}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewLead(${lead.id})">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                        tableBody.innerHTML = tableHTML;
                    } else {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-3"></i>
                                    <br>No leads found for this date
                                </td>
                            </tr>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading date leads:', error);
                    const tableBody = document.getElementById('calendarLeadsTableBodyManager');
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <br>Error loading leads: ${error.message}
                            </td>
                        </tr>
                    `;
                });
        }

        // Calendar navigation
        document.getElementById('prevMonthManager').addEventListener('click', function() {
            currentDateManager.setMonth(currentDateManager.getMonth() - 1);
            loadCalendarDataManager();
        });

        document.getElementById('nextMonthManager').addEventListener('click', function() {
            currentDateManager.setMonth(currentDateManager.getMonth() + 1);
            loadCalendarDataManager();
        });

        // View User Function
        function viewUser(userId) {
            // Show loading state
            const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
            modal.show();

            // Find user data from the team members
            const teamMembers = @json($teamMembers);
            const user = teamMembers.find(member => member.id === userId);

            if (user) {
                // Set profile image
                const imageUrl = user.profile_image ? `{{ asset('storage/') }}/${user.profile_image}` : '{{ asset('images/default-user.png') }}';
                document.getElementById('view_user_image').src = imageUrl;

                // Populate modal fields
                document.getElementById('view_user_name').textContent = `${user.f_name} ${user.l_name}`;
                document.getElementById('view_user_email').textContent = user.email || 'N/A';
                document.getElementById('view_user_mobile').textContent = user.mobile || 'N/A';
                document.getElementById('view_user_id').textContent = user.id || 'N/A';

                // Set location
                let locationText = 'N/A';
                if (user.location_names) {
                    locationText = user.location_names;
                } else if (user.location_id) {
                    const locationIds = Array.isArray(user.location_id) ? user.location_id : user.location_id.split(',');
                    locationText = locationIds.join(', ');
                }
                document.getElementById('view_user_location').textContent = locationText;

                // Set parent member
                let parentText = 'N/A';
                if (user.parent_id) {
                    // Find parent user from team members or show ID
                    const parentUser = teamMembers.find(member => member.id === user.parent_id);
                    parentText = parentUser ? `${parentUser.f_name} ${parentUser.l_name}` : `ID: ${user.parent_id}`;
                }
                document.getElementById('view_user_parent').textContent = parentText;

                // Set role with proper formatting
                const roleDisplay = user.role === 'sales manager' ? 'Sales Manager' :
                                  user.role === 'operation manager' ? 'Operation Manager' :
                                  user.role.charAt(0).toUpperCase() + user.role.slice(1);
                document.getElementById('view_user_role').textContent = roleDisplay;

                // Set leads type
                const leadsType = user.role === 'sales' || user.role === 'sales manager' ? 'Sales Leads' :
                                user.role === 'operation' || user.role === 'operation manager' ? 'Operation Leads' :
                                'General Leads';
                document.getElementById('view_user_leads_type').textContent = leadsType;

                // Set status with badge
                const statusClass = user.status == 1 ? 'bg-success' : 'bg-warning';
                const statusText = user.status == 1 ? 'Active' : 'Inactive';
                document.getElementById('view_user_status').innerHTML = `<span class="badge ${statusClass}">${statusText}</span>`;

                // Set join date
                let joinDateText = 'N/A';
                if (user.created_at) {
                    const joinDate = new Date(user.created_at);
                    joinDateText = joinDate.toLocaleDateString('en-GB', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                }
                document.getElementById('view_user_join_date').textContent = joinDateText;
            } else {
                alert('User not found!');
            }
        }

        function closeModal() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('leadsModal'));
            modal.hide();
        }

        function switchStatsTab(period) {
            currentPeriod = period;

            // Update active tab
            document.querySelectorAll('.stats-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');

            // Show/hide period selectors
            document.getElementById('monthlySelector').style.display = period === 'monthly' ? 'flex' : 'none';
            document.getElementById('yearlySelector').style.display = period === 'yearly' ? 'flex' : 'none';
            document.getElementById('customSelector').style.display = period === 'custom' ? 'flex' : 'none';

            // Set default values
            if (period === 'monthly') {
                document.getElementById('selectedMonth').value = new Date().getMonth() + 1;
                document.getElementById('selectedYear').value = new Date().getFullYear();
            } else if (period === 'yearly') {
                document.getElementById('yearlySelect').value = new Date().getFullYear();
            } else if (period === 'custom') {
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('startDate').value = today;
                document.getElementById('endDate').value = today;
            }

            loadLeadsData();
        }

        function applyDateFilter() {
            loadLeadsData();
        }

        function loadLeadsData() {
            const params = new URLSearchParams({
                period: currentPeriod
            });

            if (currentPeriod === 'monthly') {
                const month = document.getElementById('selectedMonth').value;
                const year = document.getElementById('selectedYear').value;
                if (month) params.append('selected_month', month);
                if (year) params.append('selected_year', year);
            } else if (currentPeriod === 'yearly') {
                const year = document.getElementById('yearlySelect').value;
                if (year) params.append('selected_year', year);
            } else if (currentPeriod === 'custom') {
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;
                if (startDate) params.append('start_date', startDate);
                if (endDate) params.append('end_date', endDate);
            }

            fetch(`{{ route('manager.dashboard.leads-stats') }}?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    updateLeadsDisplay(data);
                })
                .catch(error => {
                    console.error('Error loading data:', error);
                });
        }

        function updateLeadsDisplay(data) {
            // Update stats summary
            const statsSummary = document.getElementById('statsSummary');
            if (data.stats) {
                statsSummary.innerHTML = `
                    <div class="stat-item">
                        <h3 class="stat-value">${data.stats.total_leads || 0}</h3>
                        <p class="stat-label">Total Leads</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="stat-value">${data.stats.follow_up || 0}</h3>
                        <p class="stat-label">Follow-up</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="stat-value">${data.stats.future_prospect || 0}</h3>
                        <p class="stat-label">Future Prospect</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="stat-value">${data.stats.prospect || 0}</h3>
                        <p class="stat-label">Prospect</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="stat-value">${data.stats.no_response || 0}</h3>
                        <p class="stat-label">No Response</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="stat-value">${data.stats.price_issue || 0}</h3>
                        <p class="stat-label">Price Issue</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="stat-value">${data.stats.duplicate || 0}</h3>
                        <p class="stat-label">Duplicate</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="stat-value">${data.stats.spam || 0}</h3>
                        <p class="stat-label">Spam</p>
                    </div>
                `;
            }

            // Update leads table
            const tableBody = document.getElementById('leadsTableBody');
            if (data.leads && data.leads.length > 0) {
                let tableHTML = '';
                data.leads.forEach(lead => {
                    const date = lead.date ? moment(lead.date).format('DD-MMM HH:mm') : 'N/A';
                    const statusClass = lead.status ? lead.status.toLowerCase().replace(/\s+/g, '-') : 'default';
                    const executiveName = lead.executive_name || 'N/A';
                    const leadNo = lead.formatted_id || lead.lead_code || `CH${String(lead.id).padStart(8, '0')}`;
                    const source = lead.lead_source ? lead.lead_source.toUpperCase() : 'N/A';

                    tableHTML += `
                        <tr>
                            <td><strong>${leadNo}</strong></td>
                            <td>${date}</td>
                            <td>${executiveName}</td>
                            <td>${lead.customer_name || 'N/A'}</td>
                            <td>${lead.contact_no || 'N/A'}</td>
                            <td>${lead.location || 'N/A'}</td>
                            <td><span class="badge bg-info">${source}</span></td>
                            <td>${lead.query || 'N/A'}</td>
                            <td><span class="status-badge status-${statusClass}">${lead.status || '-'}</span></td>
                            <td><span class="badge bg-${getStageClass(lead.stage)}">${lead.stage || '-'}</span></td>
                            <td>
                                <button class="action-btn" onclick="viewLead(${lead.id})" title="View Lead">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                tableBody.innerHTML = tableHTML;
            } else {
                tableBody.innerHTML = '<tr><td colspan="11" class="text-center">No leads found for the selected period.</td></tr>';
            }
        }

        function getStageClass(stage) {
            switch(stage) {
                case 'active': return 'success';
                case 'inactive': return 'warning';
                case 'closed': return 'danger';
                case 'profile required': return 'info';
                default: return 'secondary';
            }
        }

        // View Lead Function
        function viewLead(leadId) {
            // Show loading state
            const modal = new bootstrap.Modal(document.getElementById('viewLeadModal'));
            modal.show();

            // Fetch lead data using the correct route
            fetch(`{{ route('manager.dashboard.lead-details', '') }}/${leadId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        alert('Error loading lead data: ' + data.error);
                        return;
                    }

                    const lead = data.lead;

                    // Populate modal fields
                    document.getElementById('view_lead_no').textContent = lead.formatted_id || `CH${String(lead.id).padStart(8, '0')}`;
                    document.getElementById('view_date').textContent = lead.date_time ? moment(lead.date_time).format('DD-MM-YYYY') : 'N/A';
                    document.getElementById('view_executive').textContent = lead.executive_name || 'N/A';
                    document.getElementById('view_customer_name').textContent = lead.customer_name || 'N/A';
                    document.getElementById('view_contact_type').textContent = 'Primary'; // Default since we don't have this field
                    document.getElementById('view_contact_no').textContent = lead.contact_no || 'N/A';
                    document.getElementById('view_location').textContent = lead.location || 'N/A';
                    document.getElementById('view_lead_source').textContent = lead.source ? lead.source.toUpperCase() : 'N/A';
                    document.getElementById('view_query').textContent = lead.query ? lead.query.charAt(0).toUpperCase() + lead.query.slice(1) : 'N/A';

                    // Set status with badge
                    let statusClass = '';
                    switch (lead.status) {
                        case 'follow-up':
                            statusClass = 'bg-info';
                            break;
                        case 'future prospect':
                            statusClass = 'bg-warning';
                            break;
                        case 'prospect':
                            statusClass = 'bg-success';
                            break;
                        case 'price issue':
                            statusClass = 'bg-secondary';
                            break;
                        case 'no response':
                            statusClass = 'bg-secondary';
                            break;
                        case 'duplicate':
                            statusClass = 'bg-danger';
                            break;
                        case 'spam':
                            statusClass = 'bg-dark';
                            break;
                        default:
                            statusClass = 'bg-secondary';
                    }
                    document.getElementById('view_status').innerHTML = `<span class="badge ${statusClass}">${lead.status ? lead.status.charAt(0).toUpperCase() + lead.status.slice(1) : '-'}</span>`;

                    // Set stage with badge
                    let stageClass = '';
                    switch (lead.stage) {
                        case 'active':
                            stageClass = 'bg-success';
                            break;
                        case 'inactive':
                            stageClass = 'bg-warning';
                            break;
                        case 'closed':
                            stageClass = 'bg-danger';
                            break;
                        case 'profile required':
                            stageClass = 'bg-info';
                            break;
                        default:
                            stageClass = 'bg-secondary';
                    }
                    document.getElementById('view_stage').innerHTML = `<span class="badge ${stageClass}">${lead.stage ? lead.stage.charAt(0).toUpperCase() + lead.stage.slice(1) : '-'}</span>`;

                    // Handle conditional fields
                    if (lead.status === 'future prospect' && lead.future_prospect_date) {
                        document.getElementById('view_future_date_group').style.display = 'block';
                        document.getElementById('view_future_prospect_date').textContent = moment(lead.future_prospect_date).format('DD-MM-YYYY');
                    } else {
                        document.getElementById('view_future_date_group').style.display = 'none';
                    }

                    if (lead.status === 'prospect' && lead.prospect_rate !== null) {
                        document.getElementById('view_close_rate_group').style.display = 'block';
                        document.getElementById('view_prospect_rate').textContent = '₹' + parseFloat(lead.prospect_rate).toLocaleString('en-IN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    } else {
                        document.getElementById('view_close_rate_group').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading lead data:', error);
                    alert('Error loading lead data. Please try again.');
                });
        }

        // Initialize date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('startDate').value = today;
            document.getElementById('endDate').value = today;
            document.getElementById('taskFilterDate').value = today;

            // Load calendar data for the card count
            loadManagerCalendarData();
        });

        // Task Management Functions
        function switchTaskTab(tab) {
            currentTaskTab = tab;

            // Update active tab
            document.querySelectorAll('#tasksModal .stats-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');

            loadTasksData();
        }

        function loadTasksData() {
            const params = new URLSearchParams({
                tab: currentTaskTab
            });

            const filterDate = document.getElementById('taskFilterDate').value;
            if (filterDate) {
                params.append('filter_date', filterDate);
            }

            fetch(`/manager/dashboard/tasks?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    updateTasksDisplay(data);
                })
                .catch(error => {
                    console.error('Error loading tasks:', error);
                });
        }

        function updateTasksDisplay(data) {
            const tableBody = document.getElementById('tasksTableBody');
            if (data.tasks && data.tasks.length > 0) {
                let tableHTML = '';
                data.tasks.forEach(task => {
                    const dueDate = task.due_date ? moment(task.due_date).format('DD-MMM-YYYY') : 'N/A';
                    const createdDate = task.created_at ? moment(task.created_at).format('DD-MMM-YYYY') : 'N/A';
                    const priorityClass = getPriorityClass(task.priority);
                    const statusClass = getStatusClass(task.status);
                    const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status !== 'completed';

                    tableHTML += `
                        <tr>
                            <td>
                                <div>
                                    <strong>${task.title}</strong>
                                    ${task.description ? `<br><small class="text-muted">${task.description}</small>` : ''}
                                </div>
                            </td>
                            <td>${task.assigned_to_name || 'N/A'}</td>
                            <td>${task.assigned_by_name || 'N/A'}</td>
                            <td><span class="badge ${priorityClass}">${task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'N/A'}</span></td>
                            <td><span class="badge ${statusClass}">${task.status ? task.status.replace('_', ' ').charAt(0).toUpperCase() + task.status.replace('_', ' ').slice(1) : 'N/A'}</span></td>
                            <td class="${isOverdue ? 'text-danger' : ''}">${dueDate} ${isOverdue ? '<i class="fas fa-exclamation-triangle text-danger"></i>' : ''}</td>
                            <td>${createdDate}</td>
                            <td>
                                <button class="action-btn me-1" onclick="viewTask(${task.id})" title="View Task">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${getTaskActionButtons(task)}
                            </td>
                        </tr>
                    `;
                });
                tableBody.innerHTML = tableHTML;
            } else {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center">No tasks found.</td></tr>';
            }
        }

        function getPriorityClass(priority) {
            switch(priority) {
                case 'high': return 'bg-danger';
                case 'medium': return 'bg-warning';
                case 'low': return 'bg-success';
                default: return 'bg-secondary';
            }
        }

        function getStatusClass(status) {
            switch(status) {
                case 'completed': return 'bg-success';
                case 'in_progress': return 'bg-info';
                case 'pending': return 'bg-warning';
                default: return 'bg-secondary';
            }
        }

        function getTaskActionButtons(task) {
            const currentUserId = {{ auth()->user()->id }};
            let buttons = '';

            // Show edit and delete buttons based on current tab and task ownership
            if (currentTaskTab === 'assigned-by-me') {
                // In "Assigned by Me" tab, show edit and delete for all tasks
                buttons += `<button class="action-btn me-1" onclick="editTask(${task.id})" title="Edit Task">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn me-1" onclick="deleteTask(${task.id})" title="Delete Task">
                    <i class="fas fa-trash"></i>
                </button>`;
            } else if (currentTaskTab === 'assigned-to-me') {
                // In "Assigned to Me" tab, show edit and delete only for tasks assigned by current user
                if (task.assigned_by == currentUserId) {
                    buttons += `<button class="action-btn me-1" onclick="editTask(${task.id})" title="Edit Task">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn me-1" onclick="deleteTask(${task.id})" title="Delete Task">
                        <i class="fas fa-trash"></i>
                    </button>`;
                }
            } else if (currentTaskTab === 'parent-manager-tasks') {
                // In "Parent Manager Tasks" tab, don't show edit/delete buttons
                // Users can only view parent manager assigned tasks
            } else if (currentTaskTab === 'all-tasks') {
                // In "All Tasks" tab, show edit and delete for tasks assigned by current user
                if (task.assigned_by == currentUserId) {
                    buttons += `<button class="action-btn me-1" onclick="editTask(${task.id})" title="Edit Task">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn me-1" onclick="deleteTask(${task.id})" title="Delete Task">
                        <i class="fas fa-trash"></i>
                    </button>`;
                }
            }

            return buttons;
        }

        function filterTasksByDate() {
            loadTasksData();
        }

        function openAddTaskModal() {
            const modal = new bootstrap.Modal(document.getElementById('addTaskModal'));
            modal.show();

            // Set default due date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('taskDueDate').value = tomorrow.toISOString().split('T')[0];
        }

        function saveTask() {
            const formData = new FormData(document.getElementById('addTaskForm'));

            fetch(`/manager/dashboard/tasks`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Task created successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addTaskModal')).hide();
                    document.getElementById('addTaskForm').reset();
                    loadTasksData();
                } else {
                    alert('Error creating task: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating task. Please try again.');
            });
        }

        function editTask(taskId) {
            fetch(`/manager/dashboard/tasks/${taskId}/edit`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const task = data.task;
                        document.getElementById('editTaskId').value = task.id;
                        document.getElementById('editTaskTitle').value = task.title;
                        document.getElementById('editTaskDescription').value = task.description || '';
                        document.getElementById('editTaskAssignedTo').value = task.assigned_to;
                        document.getElementById('editTaskPriority').value = task.priority;
                        document.getElementById('editTaskDueDate').value = task.due_date;
                        document.getElementById('editTaskStatus').value = task.status;

                        const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
                        modal.show();
                    } else {
                        alert('Error loading task: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading task. Please try again.');
                });
        }

        function updateTask() {
            const formData = new FormData(document.getElementById('editTaskForm'));

            fetch(`/manager/dashboard/tasks/${document.getElementById('editTaskId').value}/update`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Task updated successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('editTaskModal')).hide();
                    loadTasksData();
                } else {
                    alert('Error updating task: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating task. Please try again.');
            });
        }

        function deleteTask(taskId) {
            if (!confirm('Are you sure you want to delete this task?')) {
                return;
            }

            fetch(`/manager/dashboard/tasks/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Task deleted successfully!');
                    loadTasksData();
                } else {
                    alert('Error deleting task: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting task. Please try again.');
            });
        }

        function viewTask(taskId) {
            const modal = new bootstrap.Modal(document.getElementById('viewTaskModal'));
            modal.show();

            fetch(`/manager/dashboard/tasks/${taskId}/edit`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const task = data.task;

                        // Populate modal fields
                        document.getElementById('view_task_title').textContent = task.title || 'N/A';
                        document.getElementById('view_task_description').textContent = task.description || 'No description provided';
                        document.getElementById('view_task_assigned_to').textContent = task.assigned_to_name || 'N/A';
                        document.getElementById('view_task_assigned_by').textContent = task.assigned_by_name || 'N/A';

                        // Set priority with badge
                        const priorityClass = getPriorityClass(task.priority);
                        const priorityText = task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'N/A';
                        document.getElementById('view_task_priority').innerHTML = `<span class="badge ${priorityClass}">${priorityText}</span>`;

                        // Set status with badge
                        const statusClass = getStatusClass(task.status);
                        const statusText = task.status ? task.status.replace('_', ' ').charAt(0).toUpperCase() + task.status.replace('_', ' ').slice(1) : 'N/A';
                        document.getElementById('view_task_status').innerHTML = `<span class="badge ${statusClass}">${statusText}</span>`;

                        // Set dates
                        const dueDate = task.due_date ? moment(task.due_date).format('DD-MMM-YYYY') : 'N/A';
                        const createdDate = task.created_at ? moment(task.created_at).format('DD-MMM-YYYY HH:mm') : 'N/A';
                        document.getElementById('view_task_due_date').textContent = dueDate;
                        document.getElementById('view_task_created').textContent = createdDate;

                        // Check if overdue
                        if (task.due_date && new Date(task.due_date) < new Date() && task.status !== 'completed') {
                            document.getElementById('view_task_due_date').innerHTML = `${dueDate} <i class="fas fa-exclamation-triangle text-danger ms-1"></i>`;
                        }
                    } else {
                        alert('Error loading task: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading task. Please try again.');
                });
        }

        // Manager Calendar functionality
        let managerCurrentDate = new Date();
        let managerCalendarData = {};

        function loadManagerCalendarData() {
            const year = managerCurrentDate.getFullYear();
            const month = managerCurrentDate.getMonth() + 1;

            fetch(`{{ route('manager.dashboard.calendar-data') }}?year=${year}&month=${month}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        updateManagerCalendarCardCount(0, 0);
                        return;
                    }
                    managerCalendarData = data.calendar_data || {};
                    updateManagerCalendarCardCount(data.calendar_data);
                })
                .catch(error => {
                    console.error('Error loading manager calendar data:', error);
                    updateManagerCalendarCardCount(0, 0);
                });
        }

        function updateManagerCalendarCardCount(calendarData) {
            let totalFollowUp = 0;
            let totalFutureProspect = 0;

            if (calendarData && typeof calendarData === 'object') {
                Object.values(calendarData).forEach(dayData => {
                    totalFollowUp += dayData.follow_up || 0;
                    totalFutureProspect += dayData.future_prospect || 0;
                });
            }

            const totalCount = totalFollowUp + totalFutureProspect;
            const countElement = document.getElementById('managerCalendarCount');

            if (totalCount > 0) {
                countElement.innerHTML = `
                    <div style="font-size: 0.85em; line-height: 1.3;">
                        <div style="margin-bottom: 2px;">Follow-up: <strong style="color: #007bff;">${totalFollowUp}</strong></div>
                        <div>Future Prospect: <strong style="color: #28a745;">${totalFutureProspect}</strong></div>
                    </div>
                `;
            } else {
                countElement.innerHTML = '<span style="color: #6c757d; font-size: 0.9em;">No scheduled leads</span>';
            }
        }

        // Recent Leads Functions (Manager)
        function openRecentLeadsModalManager() {
            const modal = new bootstrap.Modal(document.getElementById('recentLeadsModalManager'));
            modal.show();
            loadRecentLeadsDataManager();
        }

        function loadRecentLeadsDataManager() {
            const tableBody = document.getElementById('recentLeadsTableBodyManager');
            tableBody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                        <br>Loading recent leads...
                    </td>
                </tr>
            `;

            fetch(`{{ route('manager.dashboard.recent-leads') }}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                    <br>Error: ${data.error}
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    if (data.leads && data.leads.length > 0) {
                        let tableHTML = '';
                        data.leads.forEach(lead => {
                            const date = lead.date ? moment(lead.date).format('DD-MMM HH:mm') : 'N/A';
                            const statusClass = lead.status ? lead.status.toLowerCase().replace(/\s+/g, '-') : 'default';
                            const stageClass = getStageClass(lead.stage);
                            const leadNo = lead.formatted_id || lead.lead_code || `CH${String(lead.id).padStart(8, '0')}`;
                            
                            tableHTML += `
                                <tr>
                                    <td><strong>${leadNo}</strong></td>
                                    <td>${date}</td>
                                    <td>${lead.executive || 'N/A'}</td>
                                    <td>${lead.customer_name || 'N/A'}</td>
                                    <td>${lead.contact_no || 'N/A'}</td>
                                    <td>${lead.location || 'N/A'}</td>
                                    <td>${lead.query || 'N/A'}</td>
                                    <td><span class="status-badge status-${statusClass}">${lead.status || '-'}</span></td>
                                    <td><span class="badge bg-${stageClass}">${lead.stage || '-'}</span></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="action-btn" onclick="viewLead(${lead.id})" title="View Lead">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn" onclick="editLeadFromRecentModal(${lead.id})" title="Edit Lead">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        tableBody.innerHTML = tableHTML;
                    } else {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-3"></i>
                                    <br>No recent leads found
                                </td>
                            </tr>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading recent leads:', error);
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <br>Error loading recent leads: ${error.message}
                            </td>
                        </tr>
                    `;
                });
        }

        // Edit lead from recent modal - open Edit Lead modal on top, load data via AJAX
        var editLeadModal = null;
        function editLeadFromRecentModal(leadId) {
            if (!leadId) return;
            $.ajax({
                url: '/manager/leads/' + leadId + '/edit',
                type: 'GET',
                success: function(response) {
                    $('#edit_lead_id').val(response.id);
                    $('#edit_date').val(response.date);
                    $('#edit_executive').val(response.executive || '');
                    $('#edit_contact_type').val(response.contact_type || '');
                    $('#edit_contact_no').val(response.contact_no || '');
                    $('#edit_lead_source').val(response.lead_source || '');
                    $('#edit_customer_name').val(response.customer_name || '');
                    $('#edit_patient_name').val(response.patient_name || '');
                    $('#edit_patient_gender').val(response.patient_gender || '');
                    $('#edit_age').val(response.age || '');
                    $('#edit_location').val(response.location || '').trigger('change');
                    $('#edit_query').val(response.query || '');
                    $('#edit_status').val(response.status || '');
                    $('#edit_stage').val(response.stage || 'active');
                    $('#edit_query_remarks').val(response.query_remarks || '');
                    if (window.LeadStatusRemarks) {
                        LeadStatusRemarks.onEditLeadLoaded('edit', response, { leadId: response.id });
                    }
                    $('#edit_future_prospect_date_group').hide();
                    $('#edit_prospect_close_rate_group').hide();
                    if (response.status === 'future prospect' && response.future_prospect_date) {
                        $('#edit_future_prospect_date_group').show();
                        $('#edit_future_prospect_date').val(response.future_prospect_date);
                    }
                    if (response.status === 'prospect' && response.prospect_rate) {
                        $('#edit_prospect_close_rate_group').show();
                        $('#edit_prospect_rate').val(response.prospect_rate);
                    }
                    if (response.query && response.query !== 'job request') {
                        $('#edit_query_remarks_group').show();
                    } else {
                        $('#edit_query_remarks_group').hide();
                    }
                    if (response.status) {
                        $('#edit_status_remarks_group').show();
                    } else {
                        $('#edit_status_remarks_group').hide();
                    }
                    $('#edit_query').trigger('change');
                    if (!editLeadModal) {
                        editLeadModal = new bootstrap.Modal(document.getElementById('editLeadModal'));
                    }
                    editLeadModal.show();
                },
                error: function(xhr) {
                    var errorMessage = 'Error loading lead data';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        }

        // Recent Calls Modal Functions
        let allRecentCallsData = null; // Store all calls data for filtering

        function openRecentCallsModal() {
            const modal = new bootstrap.Modal(document.getElementById('recentCallsModal'));
            modal.show();
            loadRecentCallsData();
        }

        function loadRecentCallsData() {
            const tableBody = document.getElementById('recentCallsTableBody');
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                        <br>Loading recent calls...
                    </td>
                </tr>
            `;

            fetch('{{ route("manager.dashboard.recent-calls") }}', { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(response) {
                    if (!response.ok) {
                        return response.text().then(function(text) {
                            try {
                                const err = JSON.parse(text);
                                throw new Error(err.error || err.message || 'Request failed');
                            } catch (e) {
                                if (e instanceof Error && e.message !== 'Request failed') throw e;
                                throw new Error('Request failed (' + response.status + '). Please try again.');
                            }
                        });
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        allRecentCallsData = data;
                        updateRecentCallsDisplay(data);
                    } else {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                    <br>Error: ${(data.error || 'Failed to load data').toString()}
                                </td>
                            </tr>
                        `;
                    }
                })
                .catch(function(error) {
                    console.error('Error loading recent calls:', error);
                    const msg = (error && error.message) ? error.message : 'Error loading data. Please try again.';
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <br>${msg}
                            </td>
                        </tr>
                    `;
                });
        }

        function updateRecentCallsDisplay(data) {
            // Update summary cards
            document.getElementById('totalRecentCalls').textContent = data.total_count || 0;
            document.getElementById('successfulCalls').textContent = data.successful_count || 0;
            document.getElementById('failedCalls').textContent = data.failed_count || 0;

            // Generate table
            const tableBody = document.getElementById('recentCallsTableBody');
            
            if (!data.calls || data.calls.length === 0) {
                tableBody.innerHTML = `
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
                return;
            }

            let tableHTML = '';
            data.calls.forEach(call => {
                const statusClass = call.call_status === 'answered' ? 'success' : 
                                   call.call_status === 'busy' ? 'warning' : 
                                   call.call_status === 'failed' ? 'danger' : 
                                   call.call_status === 'no-answer' ? 'secondary' : 'warning';
                
                const leadNoDisplay = call.lead_no === 'No Lead' ? '<span class="badge bg-secondary">No Lead</span>' : `<strong>${call.lead_no}</strong>`;
                
                tableHTML += `
                    <tr>
                        <td>${call.call_datetime}</td>
                        <td>${leadNoDisplay}</td>
                        <td>${call.executive_name || 'N/A'}</td>
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

            tableBody.innerHTML = tableHTML;
        }

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
                const executiveName = (call.executive_name || '').toLowerCase();
                const callDir = (call.call_direction || '').toLowerCase();
                
                return phoneNumber.includes(searchTerm) || 
                       customerName.includes(searchTerm) || 
                       leadNo.includes(searchTerm) ||
                       executiveName.includes(searchTerm) ||
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

        function loadRecentCallsCount() {
            fetch('{{ route("manager.dashboard.recent-calls") }}', { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(response) {
                    if (!response.ok) return response.json().catch(function() { return { success: false }; });
                    return response.json();
                })
                .then(function(data) {
                    const el = document.getElementById('recentCallsCount');
                    if (el) el.textContent = (data && data.success) ? (data.total_count || 0) : '0';
                })
                .catch(function(error) {
                    console.error('Error loading recent calls count:', error);
                    const el = document.getElementById('recentCallsCount');
                    if (el) el.textContent = '0';
                });
        }

        function makeCallFromDashboard(contactNo) {
            if (!confirm('Are you sure you want to call ' + contactNo + '?')) {
                return;
            }

            const $btn = $(event.target).closest('button');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Calling...');

            $.ajax({
                url: `/call-outbound/${contactNo}`,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Call initiated successfully');
                        } else {
                            alert('Call initiated successfully');
                        }
                        // Refresh the recent calls after a short delay
                        setTimeout(function() {
                            loadRecentCallsData();
                            loadRecentCallsCount();
                        }, 2000);
                    } else {
                        if (typeof toastr !== 'undefined') {
                            toastr.error(response.message || 'Failed to initiate call');
                        } else {
                            alert(response.message || 'Failed to initiate call');
                        }
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Error initiating call';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-phone"></i> Call Again');
                }
            });
        }

        // Load recent calls count on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentCallsCount();
            loadPendingCallbacksCount();
            
            // Auto-refresh pending callbacks every 30 seconds
            setInterval(loadPendingCallbacksCount, 30000);
        });

        // Pending Callbacks Functions
        function openPendingCallbacksModal() {
            const modal = new bootstrap.Modal(document.getElementById('pendingCallbacksModal'));
            modal.show();
            loadPendingCallbacksData();
        }

        function loadPendingCallbacksData() {
            const tableBody = document.getElementById('pendingCallbacksTableBody');
            tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><br>Loading pending callbacks...</td></tr>';

            fetch('{{ route("manager.dashboard.pending-callbacks") }}', { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(response) {
                    if (!response.ok) {
                        return response.text().then(function(text) {
                            try {
                                const err = JSON.parse(text);
                                throw new Error(err.error || err.message || 'Request failed');
                            } catch (e) {
                                if (e instanceof Error && e.message !== 'Request failed') throw e;
                                throw new Error('Request failed (' + response.status + '). Please try again.');
                            }
                        });
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.error || !data.success) {
                        tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted"><i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>' + (data.error || 'Error loading data') + '</td></tr>';
                        return;
                    }
                    updatePendingCallbacksDisplay(data.data || { total_pending: 0, leads: [] });
                })
                .catch(function(error) {
                    console.error('Error loading pending callbacks:', error);
                    const msg = (error && error.message) ? error.message : 'Error loading pending callbacks.';
                    tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted"><i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>' + msg + '</td></tr>';
                });
        }

        function updatePendingCallbacksDisplay(data) {
            data = data || {};
            document.getElementById('totalPendingCallbacks').textContent = data.total_pending || 0;
            const cardCount = document.getElementById('pendingCallbacksCount');
            if (cardCount) cardCount.textContent = data.total_pending || 0;

            const tableBody = document.getElementById('pendingCallbacksTableBody');
            if (!data.leads || data.leads.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted"><i class="fas fa-check-circle fa-2x mb-3" style="color: #10b981;"></i><br>No pending callbacks!</td></tr>';
                return;
            }

            let tableHTML = '';
            data.leads.forEach(lead => {
                const lastUpdated = lead.last_updated ? moment(lead.last_updated).fromNow() : 'N/A';
                const statusClass = lead.status ? lead.status.toLowerCase().replace(/\s+/g, '-') : 'default';
                let callStatusClass = 'bg-danger';
                let callStatusText = lead.last_call_status || 'Unknown';
                
                switch((lead.last_call_status || '').toLowerCase()) {
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

                const actionBtn = (lead.id) ? `<button type="button" class="btn btn-sm btn-outline-primary" onclick="editLeadFromRecentModal(${lead.id})" title="Edit Lead"><i class="fas fa-edit"></i> Edit</button>` : '<span class="text-muted">N/A</span>';
                tableHTML += `<tr>
                    <td><strong>${lead.lead_code || '#' + lead.id}</strong></td>
                    <td>${lead.customer_name || 'N/A'}${lead.patient_name ? '<br><small class="text-muted">(' + lead.patient_name + ')</small>' : ''}</td>
                    <td><strong>${lead.contact_no}</strong><div class="mt-1"><button class="btn btn-sm btn-success" onclick="makeCallFromCallback('${lead.contact_no}')"><i class="fas fa-phone"></i> Call Back</button></div></td>
                    <td>${lead.location || 'N/A'}</td>
                    <td>${lead.query || 'N/A'}</td>
                    <td><span class="status-badge status-${statusClass}">${lead.status || '-'}</span></td>
                    <td><span class="badge ${callStatusClass}">${callStatusText}</span></td>
                    <td><small class="text-muted">${lastUpdated}</small></td>
                    <td>${actionBtn}</td>
                </tr>`;
            });
            tableBody.innerHTML = tableHTML;
        }

        function makeCallFromCallback(contactNo) {
            if (!confirm('Call ' + contactNo + '?')) return;
            const $btn = $(event.target).closest('button');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Calling...');
            $.ajax({
                url: `/call-outbound/${contactNo}`,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        if (typeof toastr !== 'undefined') toastr.success('Call initiated');
                        else alert('Call initiated');
                        setTimeout(() => { loadPendingCallbacksData(); loadPendingCallbacksCount(); }, 2000);
                    } else {
                        if (typeof toastr !== 'undefined') toastr.error(response.message || 'Failed');
                        else alert(response.message || 'Failed');
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || 'Error initiating call';
                    if (typeof toastr !== 'undefined') toastr.error(msg);
                    else alert(msg);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-phone"></i> Call Back');
                }
            });
        }

        function loadPendingCallbacksCount() {
            fetch('{{ route("manager.dashboard.pending-callbacks") }}', { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(response) {
                    if (!response.ok) return response.json().catch(function() { return { success: false }; });
                    return response.json();
                })
                .then(function(data) {
                    const cardCount = document.getElementById('pendingCallbacksCount');
                    if (cardCount) cardCount.textContent = (data && data.success && data.data) ? (data.data.total_pending || 0) : '0';
                })
                .catch(function(error) {
                    console.error('Error loading pending callbacks count:', error);
                    const cardCount = document.getElementById('pendingCallbacksCount');
                    if (cardCount) cardCount.textContent = '0';
                });
        }

        // Edit Lead modal: Update button and form handlers
        $(function() {
            $('#updateLead').on('click', function() {
                if (window.LeadStatusRemarks && !window.LeadStatusRemarks.validateBeforeSave('edit')) {
                    return;
                }
                var $btn = $(this);
                var formData = $('#editLeadForm').serializeArray();
                var data = {};
                $(formData).each(function(index, obj) {
                    data[obj.name] = obj.value;
                });
                if (data.status !== 'future prospect') {
                    delete data.future_prospect_date;
                }
                if (data.status !== 'prospect') {
                    delete data.prospect_rate;
                }
                var leadId = $('#edit_lead_id').val();
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
                $.ajax({
                    url: '/manager/leads/' + leadId,
                    type: 'PUT',
                    data: data,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message || 'Lead updated successfully');
                        } else {
                            alert(response.message || 'Lead updated successfully');
                        }
                        if (editLeadModal) {
                            editLeadModal.hide();
                        }
                        loadRecentLeadsDataManager();
                    },
                    error: function(xhr) {
                        var errorMessage = 'Error updating lead';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
                        }
                        if (typeof toastr !== 'undefined') {
                            toastr.error(errorMessage);
                        } else {
                            alert(errorMessage);
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('Update Lead');
                    }
                });
            });

            $('#edit_status').on('change', function() {
                var selectedStatus = $(this).val();
                $('#edit_future_prospect_date_group').hide();
                $('#edit_prospect_close_rate_group').hide();
                $('#edit_status_remarks_group').hide();
                if (selectedStatus === 'future prospect') {
                    $('#edit_future_prospect_date_group').show();
                    $('#edit_future_prospect_date').prop('required', true);
                } else if (selectedStatus === 'prospect') {
                    $('#edit_prospect_close_rate_group').show();
                    $('#edit_prospect_rate').prop('required', true);
                }
                if (selectedStatus) {
                    $('#edit_status_remarks_group').show();
                } else {
                    $('#edit_status_remarks_group').hide();
                }
            });

            $('#edit_query').on('change', function() {
                var selectedQuery = $(this).val();
                if (selectedQuery && selectedQuery !== 'job request') {
                    $('#edit_query_remarks_group').show();
                } else {
                    $('#edit_query_remarks_group').hide();
                }
            });

            $('#editLeadModal').on('hidden.bs.modal', function() {
                $('#editLeadForm')[0].reset();
                $('#edit_query_remarks_group').hide();
                $('#edit_status_remarks_group').hide();
                $('#edit_future_prospect_date_group').hide();
                $('#edit_prospect_close_rate_group').hide();
            });
        });
    </script>
@endsection

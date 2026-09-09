@extends('operation_manager.layouts.app')

@section('title', $page_heading)

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
        overflow-y: auto;
    }

    .dashboard-header {
        display: none;
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

    .clickable-card {
        cursor: pointer;
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
        font-size: 18px;
        font-weight: 800;
        margin: 0;
        line-height: 1;
        letter-spacing: -0.5px;
    }

    .card-action {
        color: #9CA3AF;
        transition: color 0.3s ease;
    }

    .dashboard-card:hover .card-action {
        color: #667eea;
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
        padding: 10px;
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

    /* Profile Pending Details Table Responsive */
    .profile-pending-details-container {
        overflow-x: auto;
    }

    .profile-pending-details-container .leads-table {
        min-width: 1000px;
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

    /* Calendar Styles */
    .calendar-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 16px;
        background: #f8fafc;
        border-radius: 8px;
    }

    .calendar-legend {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
        justify-content: center;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
    }

    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: 4px;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }

    .calendar-day {
        background: white;
        padding: 12px 8px;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .calendar-day:hover {
        background: #f3f4f6;
    }

    .calendar-day.other-month {
        background: #f9fafb;
        color: #9ca3af;
    }

    .calendar-day.today {
        background: #dbeafe;
        color: #1e40af;
        font-weight: 600;
    }

    .calendar-day.selected {
        background: #8b5cf6;
        color: white;
    }

    .day-number {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 4px;
    }

    .count-badge {
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        margin: 1px;
    }

    .badge-follow-up-calendar {
        background: #f59e0b;
    }

    .badge-future-prospect-calendar {
        background: #10b981;
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

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-card {
            padding: 20px;
        }

        .card-count {
            font-size: 18px;
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
            min-width: 100px;
        }

        .action-buttons-container {
            flex-direction: column;
            gap: 2px;
        }
    }

    /* Payment Due Modal Styles */
    .payment-due-modal {
        border-radius: 16px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .payment-due-header {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-bottom: 2px solid #e5e7eb;
        padding: 24px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title-section {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .modal-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .modal-icon i {
        font-size: 20px;
        color: white;
    }

    .modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
        line-height: 1.2;
    }

    .modal-subtitle {
        font-size: 14px;
        color: #6b7280;
        margin: 4px 0 0 0;
        font-weight: 500;
    }

    .modal-close {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #f3f4f6;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #6b7280;
    }

    .modal-close:hover {
        background: #e5e7eb;
        color: #374151;
        transform: scale(1.05);
    }

    .payment-due-body {
        padding: 0;
        max-height: calc(90vh - 120px);
        overflow-y: auto;
    }

    /* Filter Section */
    .payment-due-filters {
        background: white;
        border-bottom: 1px solid #e5e7eb;
        padding: 24px 32px;
    }

    .payment-due-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        background: #f8fafc;
        padding: 4px;
        border-radius: 12px;
    }

    .payment-due-tabs .filter-tab {
        flex: 1;
        padding: 12px 16px;
        border: none;
        background: transparent;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-weight: 500;
        color: #6b7280;
    }

    .payment-due-tabs .filter-tab:hover {
        background: #e2e8f0;
        color: #374151;
    }

    .payment-due-tabs .filter-tab.active {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
    }

    .payment-due-tabs .filter-tab i {
        font-size: 16px;
    }

    .payment-due-filter-content {
        margin-top: 16px;
    }

    .filter-input-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-width: 300px;
    }

    .filter-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }

    .filter-label i {
        color: #6b7280;
        width: 16px;
    }

    .filter-select,
    .filter-input {
        padding: 10px 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s ease;
        background: white;
    }

    .filter-select:focus,
    .filter-input:focus {
        outline: none;
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    }

    /* Summary Cards */
    .payment-due-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        padding: 32px;
        background: #f8fafc;
    }

    .payment-summary-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .payment-summary-card::before {
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

    .payment-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .payment-summary-card:hover::before {
        opacity: 1;
    }

    .summary-card-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .summary-card-icon i {
        font-size: 24px;
        color: white;
    }

    .summary-number {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 8px 0;
        line-height: 1;
    }

    .summary-label {
        font-size: 14px;
        color: #6b7280;
        margin: 0 0 12px 0;
        font-weight: 500;
    }

    .summary-trend {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #6b7280;
        font-weight: 500;
    }

    /* Table Section */
    .payment-due-table-section {
        background: white;
        padding: 32px;
    }

    .payment-due-table-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f3f4f6;
    }

    .table-title-section {
        flex: 1;
    }

    .table-title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 4px 0;
    }

    .table-title i {
        color: #f59e0b;
        font-size: 18px;
    }

    .table-subtitle {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
        font-weight: 500;
    }

    .table-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 8px;
        border: none;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .btn-outline {
        background: transparent;
        color: #6b7280;
        border: 2px solid #e5e7eb;
    }

    .btn-outline:hover {
        background: #f8fafc;
        border-color: #d1d5db;
        color: #374151;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 13px;
    }

    .table-container {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .payment-due-table-responsive {
        overflow-x: auto;
    }

    .payment-due-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }

    .payment-due-thead {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    }

    .payment-due-thead th {
        padding: 16px 12px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e5e7eb;
        white-space: nowrap;
    }

    .payment-due-thead th i {
        margin-right: 6px;
        color: #6b7280;
        font-size: 12px;
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .payment-due-table tbody tr {
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.2s ease;
    }

    .payment-due-table tbody tr:hover {
        background: #f8fafc;
    }

    .payment-due-table tbody td {
        padding: 16px 12px;
        font-size: 14px;
        color: #374151;
        vertical-align: middle;
    }

    .loading-row {
        text-align: center;
        padding: 60px 20px;
        background: #f8fafc;
    }

    .loading-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16px;
    }

    .loading-content p {
        margin: 0;
        color: #6b7280;
        font-weight: 500;
    }

    .action-btn {
        padding: 6px 10px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 12px;
    }

    .view-btn {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .view-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        color: white;
    }

    /* Table Content Styling */
    .lead-id-badge {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
        display: inline-block;
    }

    .customer-info strong {
        color: #1f2937;
        font-weight: 600;
    }

    .vendor-info,
    .staff-info {
        display: flex;
        align-items: center;
        color: #374151;
    }

    .amount-badge {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: white;
        padding: 6px 10px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13px;
        display: inline-block;
    }

    .date-info {
        color: #6b7280;
        font-size: 13px;
        font-weight: 500;
    }

    .action-btn span {
        margin-left: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .payment-due-modal {
            width: 98vw;
            max-width: none;
            margin: 1vh;
        }

        .payment-due-header {
            padding: 20px;
            flex-direction: column;
            gap: 16px;
            align-items: flex-start;
        }

        .payment-due-filters,
        .payment-due-summary,
        .payment-due-table-section {
            padding: 20px;
        }

        .payment-due-tabs {
            flex-direction: column;
            gap: 4px;
        }

        .payment-due-tabs .filter-tab {
            justify-content: flex-start;
        }

        .payment-due-summary {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .payment-due-table-header {
            flex-direction: column;
            gap: 16px;
            align-items: flex-start;
        }

        .table-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }
</style>
@endsection

@section('content')
<div class="content-wrapper">
        <div class="container-fluid">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 13H11V3H3V13ZM3 21H11V15H3V21ZM13 21H21V11H13V21ZM13 3V9H21V3H13Z" fill="#667eea"/>
                </svg>
                Operation Manager Dashboard
            </h1>
            <p class="dashboard-subtitle">Welcome back! Here's your team's today's new leads overview.</p>
                </div>

        <!-- Main Stats Cards -->
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
                            <p class="card-title">Team's Today Leads</p>
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

            <div class="dashboard-card clickable-card" onclick="openUsersModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" fill="white"/>
                                <path d="M12 14C7.58172 14 4 17.5817 4 22H20C20 17.5817 16.4183 14 12 14Z" fill="white"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Team Members</p>
                            <h3 class="card-count">{{ number_format($totalUsers) }}</h3>
                            <div style="display: flex; gap: 12px; margin-top: 4px; justify-content: flex-end;">
                                <span style="color: #10b981; font-size: 12px; font-weight: 600;">{{ $activeUsers }} Active</span>
                                <span style="color: #ef4444; font-size: 12px; font-weight: 600;">{{ $inactiveUsers }} Inactive</span>
                        </div>
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
                        <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">My Tasks</p>
                            <h3 class="card-count">{{ number_format($totalTasks) }}</h3>
                            <div style="display: flex; gap: 16px; margin-top: 8px; justify-content: flex-end;">
                                <span style="color: #f59e0b; font-size: 13px; font-weight: 500;">{{ $pendingTasks }} Pending</span>
                                <span style="color: #10b981; font-size: 13px; font-weight: 500;">{{ $completedTasks }} Done</span>
                                @if($overdueTasks > 0)
                                <span style="color: #ef4444; font-size: 13px; font-weight: 500;">{{ $overdueTasks }} Overdue</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-action">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Outstanding Amount Card -->
            <div class="dashboard-card clickable-card" onclick="openOutstandingAmountModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" fill="white"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Outstanding Amount</p>
                            <h3 class="card-count" style="color: {{ $totalOutstandingAmount >= 0 ? '#dc2626' : '#059669' }};">
                                ₹{{ number_format($totalOutstandingAmount, 2) }}
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

            <!-- Deployment Pending Card -->
            <div class="dashboard-card clickable-card" onclick="openDeploymentPendingModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Deployment Pending</p>
                            <h3 class="card-count" style="color: #f59e0b;">
                                {{ number_format($deploymentPendingCount) }}
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

            <!-- Profile Pending Card -->
            <div class="dashboard-card clickable-card" onclick="openProfilePendingModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #fd7e14, #e67e22);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 2L12 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 18L12 22" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Profile Pending</p>
                            <h3 class="card-count" style="color: #fd7e14;">
                                {{ number_format($profilePendingCount) }}
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

            <!-- Ongoing/Stop Card -->
            <div class="dashboard-card clickable-card" onclick="openOngoingModal()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Ongoing/Stop</p>
                            <h3 class="card-count" style="color: #10b981;">
                                {{ number_format($ongoingCount) }}
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

            <!-- Payment Due Card -->
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

            <div class="dashboard-card clickable-card" onclick="openCalendarModalOperationManager()" style="cursor: pointer;">
                <div class="card-content">
                    <div class="card-info">
                        <div class="card-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 2V5M16 2V5M3.5 9.09H20.5M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M15.6947 13.7H15.7037M15.6947 16.7H15.7037M11.9955 13.7H12.0045M11.9955 16.7H12.0045M8.29431 13.7H8.30329M8.29431 16.7H8.30329" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="card-title">Follow-up Calendar</p>
                            <h3 class="card-count" id="operationManagerCalendarCount" style="font-size: 18px; font-weight: 600;">
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

            <!-- Vendor & Freelancer Payment Card -->
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

            <!-- Recent Leads Card -->
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

            <!-- Unverified Deployment Payments Card -->
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

            <!-- Pending Callbacks Card -->
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
        </div>
    </div>
</div>

<!-- Operation Leads Modal -->
<div id="operationLeadsModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Team's Leads Statistics</h3>
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

<!-- Users Modal -->
<div id="usersModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Team Members</h3>
            <button class="modal-close" onclick="closeModal('usersModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="stats-tabs">
                <button class="stats-tab active" onclick="switchUsersTab('all')">All Team Members</button>
                <button class="stats-tab" onclick="switchUsersTab('active')">Active Members</button>
                <button class="stats-tab" onclick="switchUsersTab('inactive')">Inactive Members</button>
            </div>

            <div class="stats-summary" id="usersStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Team Members</div>
                    <div class="stats-card-value" id="totalUsersCount">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Active Members</div>
                    <div class="stats-card-value" id="activeUsersCount">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Inactive Members</div>
                    <div class="stats-card-value" id="inactiveUsersCount">-</div>
                </div>
            </div>

            <div id="usersTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading users data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tasks Modal -->
<div id="tasksModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Task Management</h3>
            <button class="modal-close" onclick="closeModal('tasksModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Add Task Button -->
            <div style="margin-bottom: 20px; display: flex; gap: 12px;">
                <button class="filter-btn" onclick="openAddTaskModal()" style="background: #10b981;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px;">
                        <path d="M12 5V19M5 12H19" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Add New Task
                </button>
                <button class="filter-btn" onclick="openTaskHistoryModal()" style="background: #6B7280;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px;">
                        <path d="M12 8V16M8 12H16M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Task History
                </button>
            </div>

            <div class="stats-tabs">
                <button class="stats-tab active" onclick="switchTasksTab('assigned_to_me')">Assigned to Me</button>
                <button class="stats-tab" onclick="switchTasksTab('assigned_by_me')">Assigned by Me</button>
                <button class="stats-tab" onclick="switchTasksTab('parent_manager_tasks')">Parent Manager Tasks</button>
                <button class="stats-tab" onclick="switchTasksTab('all')">All Tasks</button>
            </div>

            <div class="stats-summary" id="tasksStatsSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Tasks</div>
                    <div class="stats-card-value" id="totalTasksCount">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Pending</div>
                    <div class="stats-card-value" id="pendingTasksCount">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Completed</div>
                    <div class="stats-card-value" id="completedTasksCount">-</div>
                </div>
            </div>

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
                <div style="margin-bottom: 16px;">
                    <label class="date-filter-label" for="taskTitle">Task Title *</label>
                    <input type="text" id="taskTitle" class="date-input" style="width: 100%;" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="date-filter-label" for="taskDescription">Description</label>
                    <textarea id="taskDescription" class="date-input" style="width: 100%; height: 80px; resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="date-filter-label" for="taskPriority">Priority *</label>
                    <select id="taskPriority" class="date-input" style="width: 100%;" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="date-filter-label" for="taskDueDate">Due Date *</label>
                    <input type="date" id="taskDueDate" class="date-input" style="width: 100%;" required>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="date-filter-label" for="taskAssignedTo">Assign To *</label>
                    <select id="taskAssignedTo" class="date-input" style="width: 100%;" required>
                        <option value="">Loading team members...</option>
                    </select>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="filter-btn" onclick="closeModal('addTaskModal')" style="background: #6B7280;">Cancel</button>
                    <button type="submit" class="filter-btn" style="background: #10b981;">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Task History Modal -->
<div id="taskHistoryModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Task History</h3>
            <button class="modal-close" onclick="closeModal('taskHistoryModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Filter Section -->
            <div style="background: #f8fafc; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="date-filter-label" for="historyDateFrom">Date From:</label>
                        <input type="date" id="historyDateFrom" class="date-input" style="width: 100%;">
                    </div>
                    <div>
                        <label class="date-filter-label" for="historyDateTo">Date To:</label>
                        <input type="date" id="historyDateTo" class="date-input" style="width: 100%;">
                    </div>
                    <div>
                        <label class="date-filter-label" for="historyAssignedTo">Assigned To:</label>
                        <select id="historyAssignedTo" class="date-input" style="width: 100%;">
                            <option value="">All Users</option>
                        </select>
                    </div>
                    <div>
                        <label class="date-filter-label" for="historyAssignedBy">Assigned By:</label>
                        <select id="historyAssignedBy" class="date-input" style="width: 100%;">
                            <option value="">All Users</option>
                        </select>
                    </div>
                    <div>
                        <label class="date-filter-label" for="historyStatus">Status:</label>
                        <select id="historyStatus" class="date-input" style="width: 100%;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="date-filter-label" for="historyPriority">Priority:</label>
                        <select id="historyPriority" class="date-input" style="width: 100%;">
                            <option value="">All Priority</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button class="filter-btn" onclick="applyTaskHistoryFilter()">Apply Filter</button>
                    <button class="filter-btn" onclick="clearTaskHistoryFilter()" style="background: #6B7280;">Clear Filter</button>
                </div>
            </div>

            <div id="taskHistoryTableContainer">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading task history...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div id="taskDetailsModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3 class="modal-title">Task Details</h3>
            <button class="modal-close" onclick="closeModal('taskDetailsModal')">&times;</button>
        </div>
        <div class="modal-body" id="taskDetailsContent">
            <!-- Task details will be loaded here -->
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
                <input type="hidden" id="editTaskId">
                <div style="margin-bottom: 16px;">
                    <label class="date-filter-label" for="editTaskTitle">Task Title *</label>
                    <input type="text" id="editTaskTitle" class="date-input" style="width: 100%;" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="date-filter-label" for="editTaskDescription">Description</label>
                    <textarea id="editTaskDescription" class="date-input" style="width: 100%; height: 80px; resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="date-filter-label" for="editTaskPriority">Priority *</label>
                    <select id="editTaskPriority" class="date-input" style="width: 100%;" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="date-filter-label" for="editTaskDueDate">Due Date *</label>
                    <input type="date" id="editTaskDueDate" class="date-input" style="width: 100%;" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="date-filter-label" for="editTaskAssignedTo">Assign To *</label>
                    <select id="editTaskAssignedTo" class="date-input" style="width: 100%;" required>
                        <option value="">Loading team members...</option>
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="date-filter-label" for="editTaskStatus">Status *</label>
                    <select id="editTaskStatus" class="date-input" style="width: 100%;" required>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="filter-btn" onclick="closeModal('editTaskModal')" style="background: #6B7280;">Cancel</button>
                    <button type="submit" class="filter-btn" style="background: #10b981;">Update Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Operation Manager Calendar Modal -->
<div id="operationManagerCalendarModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-calendar-alt"></i>
                Follow-up Calendar
            </h3>
            <button class="modal-close" onclick="closeModal('operationManagerCalendarModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Calendar Navigation -->
            <div class="calendar-navigation">
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="prevMonthOperationManager">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h5 class="mb-0" id="currentMonthYearOperationManager"></h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="nextMonthOperationManager">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Calendar Legend -->
            <div class="calendar-legend">
                <div class="legend-item">
                    <div class="legend-color badge-follow-up-calendar"></div>
                    <span>Follow-up Leads</span>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-grid" id="calendarGridOperationManager">
                <!-- Calendar will be generated here -->
            </div>
        </div>
    </div>
</div>

<!-- Operation Manager Calendar Leads Modal -->
<div id="operationManagerCalendarLeadsModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-list"></i>
                Leads for <span id="selectedDateOperationManager"></span>
            </h3>
            <button class="modal-close" onclick="closeModal('operationManagerCalendarLeadsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="leadsTableContainerOperationManager">
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading leads...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div id="userDetailsModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-user"></i>
                User Details
            </h3>
            <button class="modal-close" onclick="closeModal('userDetailsModal')">&times;</button>
        </div>
        <div class="modal-body" id="userDetailsContent">
            <!-- User details will be loaded here -->
        </div>
    </div>
</div>

<!-- Outstanding Amount Modal -->
<div id="outstandingAmountModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 1200px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-rupee-sign"></i>
                Outstanding Amount Details
            </h3>
            <button class="modal-close" onclick="closeModal('outstandingAmountModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Outstanding Amount Tabs -->
            <div class="stats-tabs">
                <button class="stats-tab active" onclick="switchOutstandingTab('all')">All Time</button>
                <button class="stats-tab" onclick="switchOutstandingTab('today')">Today</button>
                <button class="stats-tab" onclick="switchOutstandingTab('monthly')">Monthly</button>
                <button class="stats-tab" onclick="switchOutstandingTab('yearly')">Yearly</button>
                <button class="stats-tab" onclick="switchOutstandingTab('custom')">Custom Range</button>
            </div>

            <!-- Period Selectors -->
            <div class="period-selector active" id="outstandingAllSelector" style="display: flex; margin-bottom: 16px;">
                <div class="date-filter-label">All Outstanding Amounts:</div>
                <div style="color: #dc2626; font-weight: 600; margin-left: 8px;">All Time</div>
            </div>

            <div class="period-selector" id="outstandingTodaySelector" style="display: none; margin-bottom: 16px;">
                <div class="date-filter-label">Today's Outstanding:</div>
                <div style="color: #dc2626; font-weight: 600; margin-left: 8px;">{{ date('d M Y') }}</div>
            </div>

            <div class="period-selector" id="outstandingMonthlySelector" style="display: none; margin-bottom: 16px;">
                <div class="date-filter-label">Select Month:</div>
                <select id="outstandingMonthSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadOutstandingData('monthly', null, null, this.value)">
                    <option value="">Loading months...</option>
                </select>
            </div>

            <div class="period-selector" id="outstandingYearlySelector" style="display: none; margin-bottom: 16px;">
                <div class="date-filter-label">Select Year:</div>
                <select id="outstandingYearSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadOutstandingData('yearly', null, null, this.value)">
                    <option value="">Loading years...</option>
                </select>
            </div>

            <div class="period-selector" id="outstandingCustomSelector" style="display: none; margin-bottom: 16px;">
                <div class="date-filter-label">Date Range:</div>
                <input type="date" id="outstandingStartDate" class="date-input" style="margin-left: 8px; min-width: 150px;" placeholder="Start Date" max="">
                <span style="color: #6B7280; margin: 0 8px;">to</span>
                <input type="date" id="outstandingEndDate" class="date-input" style="min-width: 150px;" placeholder="End Date" max="">
                <button class="filter-btn" style="margin-left: 8px;" onclick="applyOutstandingDateFilter()">Apply Filter</button>
            </div>

            <!-- Outstanding Amount Summary Cards -->
            <div class="stats-summary" id="outstandingSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Outstanding</div>
                    <div class="stats-card-value" id="totalOutstanding" style="color: #dc2626;">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Positive Outstanding</div>
                    <div class="stats-card-value" id="positiveOutstanding" style="color: #059669;">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Negative Outstanding</div>
                    <div class="stats-card-value" id="negativeOutstanding" style="color: #dc2626;">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Total Records</div>
                    <div class="stats-card-value" id="totalRecords" style="color: #667eea;">-</div>
                </div>
            </div>

            <!-- Outstanding Amount Details Table -->
            <div class="outstanding-details-container" style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                <h5 style="margin-bottom: 20px; color: #1F2937;">Outstanding Amount Details</h5>
                <div id="outstandingDetailsTable">
                    <div style="text-align: center; padding: 40px;">
                        <div class="loading-spinner-modal"></div>
                        <p style="margin-top: 16px; color: #6B7280;">Loading outstanding amount details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deployment Pending Modal -->
<div id="deploymentPendingModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 1200px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-layer-group"></i>
                Deployment Pending Details
            </h3>
            <button class="modal-close" onclick="closeModal('deploymentPendingModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Deployment Pending Filter -->
            <div class="period-selector" style="display: flex; margin-bottom: 16px;">
                <div class="date-filter-label">All Deployment Pending:</div>
                <div style="color: #f59e0b; font-weight: 600; margin-left: 8px;">All Leads</div>
            </div>

            <!-- Deployment Pending Summary Cards -->
            <div class="stats-summary" id="deploymentPendingSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Pending</div>
                    <div class="stats-card-value" id="totalDeploymentPending" style="color: #f59e0b;">-</div>
                </div>
            </div>

            <!-- Deployment Pending Details Table -->
            <div class="deployment-pending-details-container" style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                <h5 style="margin-bottom: 20px; color: #1F2937;">Deployment Pending Details</h5>
                <div id="deploymentPendingDetailsTable">
                    <div style="text-align: center; padding: 40px;">
                        <div class="loading-spinner-modal"></div>
                        <p style="margin-top: 16px; color: #6B7280;">Loading deployment pending details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Pending Modal -->
<div id="profilePendingModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 1200px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-user-clock"></i>
                Profile Pending Details
            </h3>
            <button class="modal-close" onclick="closeModal('profilePendingModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Profile Pending Filter -->
            <div class="period-selector" style="display: flex; margin-bottom: 16px;">
                <div class="date-filter-label">All Profile Pending:</div>
                <div style="color: #fd7e14; font-weight: 600; margin-left: 8px;">All Leads</div>
            </div>

            <!-- Profile Pending Summary Cards -->
            <div class="stats-summary" id="profilePendingSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Pending</div>
                    <div class="stats-card-value" id="totalProfilePending" style="color: #fd7e14;">-</div>
                </div>
            </div>

            <!-- Profile Pending Details Table -->
            <div class="profile-pending-details-container" style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                <h5 style="margin-bottom: 20px; color: #1F2937;">Profile Pending Details</h5>
                <div id="profilePendingDetailsTable">
                    <div style="text-align: center; padding: 40px;">
                        <div class="loading-spinner-modal"></div>
                        <p style="margin-top: 16px; color: #6B7280;">Loading profile pending details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ongoing/Stop Modal -->
<div id="ongoingModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 1200px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-play-circle"></i>
                Ongoing/Stop Details
            </h3>
            <button class="modal-close" onclick="closeModal('ongoingModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Ongoing/Stop Tabs -->
            <div class="stats-tabs">
                <button class="stats-tab active" onclick="switchOngoingTab('all')">All Time</button>
                <button class="stats-tab" onclick="switchOngoingTab('today')">Today</button>
                <button class="stats-tab" onclick="switchOngoingTab('monthly')">Monthly</button>
                <button class="stats-tab" onclick="switchOngoingTab('yearly')">Yearly</button>
                <button class="stats-tab" onclick="switchOngoingTab('custom')">Custom Range</button>
            </div>

            <!-- Period Selectors -->
            <div class="period-selector active" id="ongoingAllSelector" style="display: flex; margin-bottom: 16px;">
                <div class="date-filter-label">All Ongoing Leads:</div>
                <div style="color: #10b981; font-weight: 600; margin-left: 8px;">All Time</div>
            </div>

            <div class="period-selector" id="ongoingTodaySelector" style="display: none; margin-bottom: 16px;">
                <div class="date-filter-label">Today's Ongoing Leads:</div>
                <div style="color: #10b981; font-weight: 600; margin-left: 8px;">{{ date('d M Y') }}</div>
            </div>

            <div class="period-selector" id="ongoingMonthlySelector" style="display: none; margin-bottom: 16px;">
                <div class="date-filter-label">Select Month:</div>
                <select id="ongoingMonthSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadOngoingData('monthly', null, null, this.value)">
                    <option value="">Loading months...</option>
                </select>
            </div>

            <div class="period-selector" id="ongoingYearlySelector" style="display: none; margin-bottom: 16px;">
                <div class="date-filter-label">Select Year:</div>
                <select id="ongoingYearSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadOngoingData('yearly', null, null, this.value)">
                    <option value="">Loading years...</option>
                </select>
            </div>

            <div class="period-selector" id="ongoingCustomSelector" style="display: none; margin-bottom: 16px;">
                <div class="date-filter-label">Date Range:</div>
                <input type="date" id="ongoingStartDate" class="date-input" style="margin-left: 8px; min-width: 150px;" placeholder="Start Date" max="">
                <span style="color: #6B7280; margin: 0 8px;">to</span>
                <input type="date" id="ongoingEndDate" class="date-input" style="min-width: 150px;" placeholder="End Date" max="">
                <button class="filter-btn" style="margin-left: 8px;" onclick="applyOngoingDateFilter()">Apply Filter</button>
            </div>

            <!-- Ongoing/Stop Summary Cards -->
            <div class="stats-summary" id="ongoingSummary">
                <div class="stats-card">
                    <div class="stats-card-title">Total Ongoing</div>
                    <div class="stats-card-value" id="totalOngoing" style="color: #10b981;">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">Today's Ongoing</div>
                    <div class="stats-card-value" id="todayOngoing" style="color: #dc2626;">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">This Month</div>
                    <div class="stats-card-value" id="monthOngoing" style="color: #667eea;">-</div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-title">This Year</div>
                    <div class="stats-card-value" id="yearOngoing" style="color: #059669;">-</div>
                </div>
            </div>

            <!-- Ongoing/Stop Details Table -->
            <div class="ongoing-details-container" style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                <h5 style="margin-bottom: 20px; color: #1F2937;">Ongoing/Stop Details</h5>
                <div id="ongoingDetailsTable">
                    <div style="text-align: center; padding: 40px;">
                        <div class="loading-spinner-modal"></div>
                        <p style="margin-top: 16px; color: #6B7280;">Loading ongoing details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Due Modal -->
<div id="paymentDueModal" class="modal-overlay">
    <div class="modal-content payment-due-modal" style="max-width: 1400px; width: 95vw; max-height: 90vh;">
        <div class="modal-header payment-due-header">
            <div class="modal-title-section">
                <div class="modal-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h3 class="modal-title">Payment Due Statistics</h3>
                    <p class="modal-subtitle">Monitor unverified payments across your team</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal('paymentDueModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body payment-due-body">

            <!-- Summary Cards -->
            <div class="payment-due-summary">
                <div class="summary-card payment-summary-card">
                    <div class="summary-card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div class="summary-card-content">
                        <h4 class="summary-number" id="paymentDueTotalCount">0</h4>
                        <p class="summary-label">Total Unverified Payments</p>
                        <div class="summary-trend">
                            <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                            <span>Requires verification</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card payment-summary-card">
                    <div class="summary-card-icon" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="summary-card-content">
                        <h4 class="summary-number" id="paymentDueTotalAmount">₹0</h4>
                        <p class="summary-label">Total Amount Due</p>
                        <div class="summary-trend">
                            <i class="fas fa-clock" style="color: #dc2626;"></i>
                            <span>Pending verification</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card payment-summary-card">
                    <div class="summary-card-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="summary-card-content">
                        <h4 class="summary-number" id="paymentDueTeamCount">0</h4>
                        <p class="summary-label">Team Members Affected</p>
                        <div class="summary-trend">
                            <i class="fas fa-user-friends" style="color: #8b5cf6;"></i>
                            <span>Across your team</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table Section -->
            <div class="payment-due-table-section">
                <div class="table-header payment-due-table-header">
                    <div class="table-title-section">
                        <h4 class="table-title">
                            <i class="fas fa-table"></i>
                            Unverified Payment Details
                        </h4>
                        <p class="table-subtitle">Click on any payment to view the full lead details</p>
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-primary btn-sm" onclick="refreshPaymentDueData()">
                            <i class="fas fa-sync-alt"></i>
                            <span>Refresh Data</span>
                        </button>
                        <button class="btn btn-outline btn-sm" onclick="exportPaymentDueData()">
                            <i class="fas fa-download"></i>
                            <span>Export</span>
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-responsive payment-due-table-responsive">
                        <table class="data-table payment-due-table" id="paymentDueTable">
                            <thead class="payment-due-thead">
                                <tr>
                                    <th class="text-center">
                                        <i class="fas fa-hashtag"></i>
                                        Lead ID
                                    </th>
                                    <th>
                                        <i class="fas fa-user"></i>
                                        Customer
                                    </th>
                                    <th>
                                        <i class="fas fa-building"></i>
                                        Vendor
                                    </th>
                                    <th>
                                        <i class="fas fa-user-tie"></i>
                                        Staff
                                    </th>
                                    <th class="text-right">
                                        <i class="fas fa-rupee-sign"></i>
                                        Amount
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-calendar-alt"></i>
                                        Deployment Date
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-clock"></i>
                                        Created
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-cog"></i>
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="paymentDueTableBody">
                                <tr>
                                    <td colspan="8" class="loading-row">
                                        <div class="loading-content">
                                            <div class="loading-spinner-modal"></div>
                                            <p>Loading payment due data...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
                    <input type="text" id="recentCallsSearchInput" class="form-control" placeholder="Search by phone number, customer name, or executive name..." onkeyup="filterRecentCalls()">
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
    // Make Call Function
    function makeCall(phoneNumber) {
        if (!phoneNumber || phoneNumber === 'N/A') {
            alert('Invalid phone number');
            return;
        }
        
        if (!confirm('Are you sure you want to make this call?')) {
            return;
        }

        // Show loading state
        const event_target = event.target;
        const originalContent = event_target.closest('button').innerHTML;
        event_target.closest('button').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        event_target.closest('button').disabled = true;

        fetch(`/call-outbound/${phoneNumber}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Call initiated successfully');
                } else {
                    alert(data.message || 'Failed to initiate call');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error initiating call');
            })
            .finally(() => {
                event_target.closest('button').innerHTML = originalContent;
                event_target.closest('button').disabled = false;
            });
    }

    // Modal functions
    function openOperationLeadsModal() {
        document.getElementById('operationLeadsModal').style.display = 'flex';
        setupDateInputs();
        populateMonthYearDropdowns();
        loadOperationLeadsData('today');
    }

    function openUsersModal() {
        document.getElementById('usersModal').style.display = 'flex';
        loadUsersData('all');
    }

    function openTasksModal() {
        document.getElementById('tasksModal').style.display = 'flex';
        loadTasksData('assigned_to_me');
    }

    function openAddTaskModal() {
        document.getElementById('addTaskModal').style.display = 'flex';
        loadTeamMembers();
        setupTaskForm();
    }

    function openTaskHistoryModal() {
        document.getElementById('taskHistoryModal').style.display = 'flex';
        loadTaskHistory();
        loadTeamMembersForHistory();
    }

    function openPaymentDueModal() {
        document.getElementById('paymentDueModal').style.display = 'flex';
        loadPaymentDueData();
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

        let url = `/operation-manager/dashboard/leads-stats?period=${period}`;
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
                        <th>Executive</th>
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
            const executiveName = lead.executive ? `${lead.executive.f_name || ''} ${lead.executive.l_name || ''}`.trim() : 'N/A';

            tableHTML += `
                <tr>
                    <td><strong>${lead.lead_id || '#' + lead.id}</strong></td>
                    <td>${date}</td>
                    <td>${executiveName}</td>
                    <td>${lead.customer_name || 'N/A'}</td>
                    <td>${lead.contact_no || 'N/A'}</td>
                    <td>${lead.location || 'N/A'}</td>
                    <td><span class="status-badge badge-${(lead.status || 'default').toLowerCase().replace(/\s+/g, '-')}">${lead.status || '-'}</span></td>
                    <td>
                        <a href="/operation-manager/operation-leads/${lead.id}" class="btn action-btn view-btn" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Users modal functions
    function switchUsersTab(status) {
        // Update active tab
        const tabs = document.querySelectorAll('#usersModal .stats-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        loadUsersData(status);
    }

    // Load users data
    function loadUsersData(status) {
        const container = document.getElementById('usersTableContainer');
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading users data...</p></div>';

        let url = `/operation-manager/dashboard/users-stats?status=${status}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateUsersDisplay(data, status);
            })
            .catch(error => {
                console.error('Error loading users data:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    // Update users display
    function updateUsersDisplay(data, status) {
        // Check if data and users exist
        if (!data || !data.users) {
            console.error('Invalid data structure:', data);
            document.getElementById('usersTableContainer').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error: Invalid data received</div>';
            return;
        }

        // Update summary cards
        const totalUsers = data.users.length;
        const activeUsers = data.users.filter(user => user.is_active == 1).length;
        const inactiveUsers = data.users.filter(user => user.is_active == 0).length;

        document.getElementById('totalUsersCount').textContent = totalUsers;
        document.getElementById('activeUsersCount').textContent = activeUsers;
        document.getElementById('inactiveUsersCount').textContent = inactiveUsers;

        // Generate table
        const container = document.getElementById('usersTableContainer');
        if (data.users.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No users found.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Location</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.users.forEach(user => {
            const imageUrl = user.profile_image ? `/storage/${user.profile_image}` : '{{ asset('images/default-user.png') }}';
            const fullName = `${user.f_name || ''} ${user.l_name || ''}`.trim();
            const locationName = user.location_name || 'N/A';
            const roleName = user.roles || 'N/A';
            const statusClass = user.is_active == 1 ? 'badge-follow-up' : 'badge-closed';
            const statusText = user.is_active == 1 ? 'Active' : 'Inactive';
            const createdDate = user.created_at ? new Date(user.created_at).toLocaleDateString('en-GB') : 'N/A';

            tableHTML += `
                <tr data-user-id="${user.id}">
                    <td><strong>#${user.id}</strong></td>
                    <td>
                        <img class="img-thumbnail" src="${imageUrl}" style="width: 40px; height: 40px; border-radius: 50%;" onerror="this.onerror=null; this.src='{{ asset('images/default-user.png') }}'">
                    </td>
                    <td>${fullName || 'N/A'}</td>
                    <td>${user.email || 'N/A'}</td>
                    <td>${user.mobile || 'N/A'}</td>
                    <td>${locationName}</td>
                    <td>${roleName}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>${createdDate}</td>
                    <td>
                        <button class="action-btn view-btn" onclick="viewUserDetails(${user.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Tasks modal functions
    function switchTasksTab(type) {
        // Update active tab
        const tabs = document.querySelectorAll('#tasksModal .stats-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        loadTasksData(type);
    }

    // Load tasks data
    function loadTasksData(type) {
        const container = document.getElementById('tasksTableContainer');
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading tasks data...</p></div>';

        let url = `/operation-manager/dashboard/tasks?type=${type}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateTasksDisplay(data, type);
            })
            .catch(error => {
                console.error('Error loading tasks data:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
            });
    }

    // Update tasks display
    function updateTasksDisplay(data, type) {
        // Check if data and tasks exist
        if (!data || !data.tasks) {
            console.error('Invalid data structure:', data);
            document.getElementById('tasksTableContainer').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error: Invalid data received</div>';
            return;
        }

        // Update summary cards
        const totalTasks = data.tasks.length;
        const pendingTasks = data.tasks.filter(task => task.status === 'pending').length;
        const completedTasks = data.tasks.filter(task => task.status === 'completed').length;

        document.getElementById('totalTasksCount').textContent = totalTasks;
        document.getElementById('pendingTasksCount').textContent = pendingTasks;
        document.getElementById('completedTasksCount').textContent = completedTasks;

        // Generate table
        const container = document.getElementById('tasksTableContainer');
        if (data.tasks.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No tasks found.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Assigned By</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.tasks.forEach(task => {
            const priorityClass = getPriorityClass(task.priority);
            const statusClass = getStatusClass(task.status);
            const dueDate = new Date(task.due_date).toLocaleDateString('en-GB');
            const isOverdue = new Date(task.due_date) < new Date() && task.status !== 'completed';
            const dueDateDisplay = isOverdue ? `<span style="color: #ef4444; font-weight: 600;">${dueDate} (Overdue)</span>` : dueDate;

            tableHTML += `
                <tr>
                    <td>
                        <div>
                            <strong>${task.title}</strong>
                            ${task.description ? `<br><small style="color: #6B7280;">${task.description}</small>` : ''}
                    </div>
                    </td>
                    <td><span class="status-badge ${priorityClass}">${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}</span></td>
                    <td><span class="status-badge ${statusClass}">${task.status.charAt(0).toUpperCase() + task.status.slice(1)}</span></td>
                    <td>${task.assigned_to ? task.assigned_to.f_name + ' ' + task.assigned_to.l_name : 'N/A'}</td>
                    <td>${task.assigned_by ? task.assigned_by.f_name + ' ' + task.assigned_by.l_name : 'N/A'}</td>
                    <td>${dueDateDisplay}</td>
                    <td class="action-column">
                        <div class="action-buttons-container">
                            ${getStatusButtons(task)}
                </div>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Helper functions for task display
    function getPriorityClass(priority) {
        switch(priority) {
            case 'low': return 'badge-info';
            case 'medium': return 'badge-primary';
            case 'high': return 'badge-warning';
            case 'urgent': return 'badge-danger';
            default: return 'badge-primary';
        }
    }

    function getStatusClass(status) {
        switch(status) {
            case 'pending': return 'badge-secondary';
            case 'in_progress': return 'badge-warning';
            case 'completed': return 'badge-success';
            case 'cancelled': return 'badge-danger';
            default: return 'badge-secondary';
        }
    }

    function getStatusButtons(task) {
        const currentUserId = {{ auth()->user()->id }};
        let buttons = '';

        // Add View button for all tasks
        buttons += `<button class="action-btn view-btn" onclick="viewTaskDetails(${task.id})" title="View Details">
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

    // Update task status
    function updateTaskStatus(taskId, status) {
        if (!confirm(`Are you sure you want to ${status.replace('_', ' ')} this task?`)) {
            return;
        }

        fetch(`/operation-manager/dashboard/tasks/${taskId}/status`, {
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
                // Reload current tasks
                const activeTab = document.querySelector('#tasksModal .stats-tab.active');
                const currentType = activeTab ? activeTab.textContent.toLowerCase().replace(' ', '_') : 'assigned_to_me';
                loadTasksData(currentType);
            } else {
                alert('Error: ' + (data.error || 'Failed to update task status'));
            }
        })
        .catch(error => {
            console.error('Error updating task status:', error);
            alert('Error updating task status. Please try again.');
        });
    }

    // Load team members for task assignment
    function loadTeamMembers() {
        fetch('/operation-manager/dashboard/team-members')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('taskAssignedTo');
                select.innerHTML = '';

                data.team_members.forEach(member => {
                    const option = document.createElement('option');
                    option.value = member.id;
                    option.textContent = `${member.f_name} ${member.l_name}`;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading team members:', error);
            });
    }

    // Setup task form
    function setupTaskForm() {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('taskDueDate').min = today;
        document.getElementById('taskDueDate').value = today;
    }

    // Handle add task form submission
    document.getElementById('addTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = {
            title: document.getElementById('taskTitle').value,
            description: document.getElementById('taskDescription').value,
            priority: document.getElementById('taskPriority').value,
            due_date: document.getElementById('taskDueDate').value,
            assigned_to: document.getElementById('taskAssignedTo').value
        };

        fetch('/operation-manager/dashboard/tasks', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Task created successfully!');
                closeModal('addTaskModal');
                // Reset form
                document.getElementById('addTaskForm').reset();
                setupTaskForm();
                // Reload tasks
                const activeTab = document.querySelector('#tasksModal .stats-tab.active');
                const currentType = activeTab ? activeTab.textContent.toLowerCase().replace(' ', '_') : 'assigned_to_me';
                loadTasksData(currentType);
            } else {
                alert('Error: ' + (data.error || 'Failed to create task'));
            }
        })
        .catch(error => {
            console.error('Error creating task:', error);
            alert('Error creating task. Please try again.');
        });
    });

    // Task History Functions
    function loadTaskHistory() {
        const container = document.getElementById('taskHistoryTableContainer');
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading task history...</p></div>';

        const params = new URLSearchParams();
        const dateFrom = document.getElementById('historyDateFrom').value;
        const dateTo = document.getElementById('historyDateTo').value;
        const assignedTo = document.getElementById('historyAssignedTo').value;
        const assignedBy = document.getElementById('historyAssignedBy').value;
        const status = document.getElementById('historyStatus').value;
        const priority = document.getElementById('historyPriority').value;

        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        if (assignedTo) params.append('assigned_to', assignedTo);
        if (assignedBy) params.append('assigned_by', assignedBy);
        if (status) params.append('status', status);
        if (priority) params.append('priority', priority);

        fetch(`/operation-manager/dashboard/task-history?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                updateTaskHistoryDisplay(data);
            })
            .catch(error => {
                console.error('Error loading task history:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading task history. Please try again.</div>';
            });
    }

    function updateTaskHistoryDisplay(data) {
        const container = document.getElementById('taskHistoryTableContainer');

        if (!data || !data.tasks) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error: Invalid data received</div>';
            return;
        }

        if (data.tasks.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No tasks found for the selected filters.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Task ID</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Assigned By</th>
                        <th>Due Date</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.tasks.forEach(task => {
            const priorityClass = getPriorityClass(task.priority);
            const statusClass = getStatusClass(task.status);
            const dueDate = new Date(task.due_date).toLocaleDateString('en-GB');
            const createdDate = new Date(task.created_at).toLocaleDateString('en-GB');
            const isOverdue = new Date(task.due_date) < new Date() && task.status !== 'completed';
            const dueDateDisplay = isOverdue ? `<span style="color: #ef4444; font-weight: 600;">${dueDate} (Overdue)</span>` : dueDate;

            tableHTML += `
                <tr>
                    <td><strong>#${task.id}</strong></td>
                    <td>
                        <div>
                            <strong>${task.title}</strong>
                            ${task.description ? `<br><small style="color: #6B7280;">${task.description.substring(0, 50)}${task.description.length > 50 ? '...' : ''}</small>` : ''}
            </div>
                    </td>
                    <td><span class="status-badge ${priorityClass}">${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}</span></td>
                    <td><span class="status-badge ${statusClass}">${task.status.charAt(0).toUpperCase() + task.status.slice(1)}</span></td>
                    <td>${task.assigned_to ? task.assigned_to.f_name + ' ' + task.assigned_to.l_name : 'N/A'}</td>
                    <td>${task.assigned_by ? task.assigned_by.f_name + ' ' + task.assigned_by.l_name : 'N/A'}</td>
                    <td>${dueDateDisplay}</td>
                    <td>${createdDate}</td>
                    <td class="action-column">
                        <div class="action-buttons-container">
                            <button class="action-btn view-btn" onclick="viewTaskDetails(${task.id})" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${getTaskActionButtons(task)}
        </div>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    function getTaskActionButtons(task) {
        const currentUserId = {{ auth()->user()->id }};
        let buttons = '';

        // Only show edit/delete buttons if user assigned the task
        if (task.assigned_by && task.assigned_by.id == currentUserId) {
            buttons += `<button class="action-btn edit-btn" onclick="editTask(${task.id})" title="Edit Task">
                <i class="fas fa-edit"></i>
            </button>`;
            buttons += `<button class="action-btn delete-btn" onclick="deleteTask(${task.id})" title="Delete Task">
                <i class="fas fa-trash"></i>
            </button>`;
        }

        return buttons;
    }

    function applyTaskHistoryFilter() {
        loadTaskHistory();
    }

    function clearTaskHistoryFilter() {
        document.getElementById('historyDateFrom').value = '';
        document.getElementById('historyDateTo').value = '';
        document.getElementById('historyAssignedTo').value = '';
        document.getElementById('historyAssignedBy').value = '';
        document.getElementById('historyStatus').value = '';
        document.getElementById('historyPriority').value = '';
        loadTaskHistory();
    }

    function loadTeamMembersForHistory() {
        fetch('/operation-manager/dashboard/team-members')
            .then(response => response.json())
            .then(data => {
                const assignedToSelect = document.getElementById('historyAssignedTo');
                const assignedBySelect = document.getElementById('historyAssignedBy');

                // Clear existing options except "All Users"
                assignedToSelect.innerHTML = '<option value="">All Users</option>';
                assignedBySelect.innerHTML = '<option value="">All Users</option>';

                data.team_members.forEach(member => {
                    const option1 = document.createElement('option');
                    option1.value = member.id;
                    option1.textContent = `${member.f_name} ${member.l_name}`;
                    assignedToSelect.appendChild(option1);

                    const option2 = document.createElement('option');
                    option2.value = member.id;
                    option2.textContent = `${member.f_name} ${member.l_name}`;
                    assignedBySelect.appendChild(option2);
                });
            })
            .catch(error => {
                console.error('Error loading team members for history:', error);
            });
    }

    function viewTaskDetails(taskId) {
        fetch(`/operation-manager/dashboard/tasks/${taskId}`)
            .then(response => response.json())
            .then(data => {
                if (data.task) {
                    showTaskDetails(data.task);
                } else {
                    alert('Error loading task details');
                }
            })
            .catch(error => {
                console.error('Error loading task details:', error);
                alert('Error loading task details');
            });
    }

    function showTaskDetails(task) {
        const content = document.getElementById('taskDetailsContent');
        const createdDate = new Date(task.created_at).toLocaleDateString('en-GB');
        const dueDate = new Date(task.due_date).toLocaleDateString('en-GB');
        const completedDate = task.completed_at ? new Date(task.completed_at).toLocaleDateString('en-GB') : 'N/A';
        const priorityClass = getPriorityClass(task.priority);
        const statusClass = getStatusClass(task.status);

        content.innerHTML = `
            <div style="display: grid; gap: 16px;">
                <div style="background: #f8fafc; padding: 16px; border-radius: 8px;">
                    <h4 style="margin: 0 0 8px 0; color: #1F2937;">${task.title}</h4>
                    <p style="margin: 0; color: #6B7280;">${task.description || 'No description provided'}</p>
</div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                    <div>
                        <label style="font-weight: 600; color: #374151;">Priority:</label>
                        <div><span class="status-badge ${priorityClass}">${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}</span></div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #374151;">Status:</label>
                        <div><span class="status-badge ${statusClass}">${task.status.charAt(0).toUpperCase() + task.status.slice(1)}</span></div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #374151;">Assigned To:</label>
                        <div>${task.assigned_to ? task.assigned_to.f_name + ' ' + task.assigned_to.l_name : 'N/A'}</div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #374151;">Assigned By:</label>
                        <div>${task.assigned_by ? task.assigned_by.f_name + ' ' + task.assigned_by.l_name : 'N/A'}</div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #374151;">Due Date:</label>
                        <div>${dueDate}</div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #374151;">Created At:</label>
                        <div>${createdDate}</div>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #374151;">Completed At:</label>
                        <div>${completedDate}</div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('taskDetailsModal').style.display = 'flex';
    }

    function editTask(taskId) {
        fetch(`/operation-manager/dashboard/tasks/${taskId}`)
            .then(response => response.json())
            .then(data => {
                if (data.task) {
                    populateEditForm(data.task);
                    document.getElementById('editTaskModal').style.display = 'flex';
                } else {
                    alert('Error loading task details');
                }
            })
            .catch(error => {
                console.error('Error loading task for edit:', error);
                alert('Error loading task details');
            });
    }

    function populateEditForm(task) {
        document.getElementById('editTaskId').value = task.id;
        document.getElementById('editTaskTitle').value = task.title;
        document.getElementById('editTaskDescription').value = task.description || '';
        document.getElementById('editTaskPriority').value = task.priority;
        document.getElementById('editTaskDueDate').value = task.due_date;
        document.getElementById('editTaskStatus').value = task.status;

        // Load team members and set selected value
        loadTeamMembersForEdit(task.assigned_to.id);
    }

    function loadTeamMembersForEdit(selectedUserId) {
        fetch('/operation-manager/dashboard/team-members')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('editTaskAssignedTo');
                select.innerHTML = '';

                data.team_members.forEach(member => {
                    const option = document.createElement('option');
                    option.value = member.id;
                    option.textContent = `${member.f_name} ${member.l_name}`;
                    if (member.id == selectedUserId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading team members for edit:', error);
            });
    }

    function deleteTask(taskId) {
        if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
            return;
        }

        fetch(`/operation-manager/dashboard/tasks/${taskId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Task deleted successfully!');
                // Refresh both current tasks and history if history modal is open
                const activeTab = document.querySelector('#tasksModal .stats-tab.active');
                if (activeTab) {
                    const currentType = activeTab.textContent.toLowerCase().replace(' ', '_');
                    loadTasksData(currentType);
                }
                // Also refresh history if it's open
                if (document.getElementById('taskHistoryModal').style.display === 'flex') {
                    loadTaskHistory();
                }
            } else {
                alert('Error: ' + (data.error || 'Failed to delete task'));
            }
        })
        .catch(error => {
            console.error('Error deleting task:', error);
            alert('Error deleting task. Please try again.');
        });
    }

    // Handle edit task form submission
    document.getElementById('editTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const taskId = document.getElementById('editTaskId').value;
        const formData = {
            title: document.getElementById('editTaskTitle').value,
            description: document.getElementById('editTaskDescription').value,
            priority: document.getElementById('editTaskPriority').value,
            due_date: document.getElementById('editTaskDueDate').value,
            assigned_to: document.getElementById('editTaskAssignedTo').value,
            status: document.getElementById('editTaskStatus').value
        };

        fetch(`/operation-manager/dashboard/tasks/${taskId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Task updated successfully!');
                closeModal('editTaskModal');
                // Refresh both current tasks and history
                const activeTab = document.querySelector('#tasksModal .stats-tab.active');
                if (activeTab) {
                    const currentType = activeTab.textContent.toLowerCase().replace(' ', '_');
                    loadTasksData(currentType);
                }
                // Also refresh history if it's open
                if (document.getElementById('taskHistoryModal').style.display === 'flex') {
                    loadTaskHistory();
                }
            } else {
                alert('Error: ' + (data.error || 'Failed to update task'));
            }
        })
        .catch(error => {
            console.error('Error updating task:', error);
            alert('Error updating task. Please try again.');
        });
    });

    // Operation Manager Calendar Functions
    let currentDateOperationManager = new Date();
    let calendarDataOperationManager = {};
    let selectedDateOperationManager = null;

    function openCalendarModalOperationManager() {
        document.getElementById('operationManagerCalendarModal').style.display = 'flex';
        currentDateOperationManager = new Date();
        calendarDataOperationManager = {};
        renderCalendarOperationManager();
        loadCalendarDataOperationManager();
    }

    function loadCalendarDataOperationManager() {
        const year = currentDateOperationManager.getFullYear();
        const month = currentDateOperationManager.getMonth() + 1;

        fetch(`/operation-manager/dashboard/calendar-data?year=${year}&month=${month}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }
                calendarDataOperationManager = data.calendar_data || {};
                renderCalendarOperationManager();
            })
            .catch(error => {
                console.error('Error loading calendar data:', error);
            });
    }

    function renderCalendarOperationManager() {
        const year = currentDateOperationManager.getFullYear();
        const month = currentDateOperationManager.getMonth();

        // Update month/year display
        document.getElementById('currentMonthYearOperationManager').textContent =
            currentDateOperationManager.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

        // Get first day of month and number of days
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();

        // Create calendar HTML
        let calendarHTML = '';

        // Add day headers
        const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayHeaders.forEach(day => {
            calendarHTML += `<div style="background: #f3f4f6; padding: 8px; text-align: center; font-weight: 600; font-size: 12px; color: #6b7280;">${day}</div>`;
        });

        // Add empty cells for days before month starts
        for (let i = 0; i < startingDayOfWeek; i++) {
            calendarHTML += '<div class="calendar-day other-month"></div>';
        }

        // Add days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();
            const isSelected = selectedDateOperationManager === dateStr;

            let dayClass = 'calendar-day';
            if (isToday) dayClass += ' today';
            if (isSelected) dayClass += ' selected';

            let countsHTML = '';
            if (calendarDataOperationManager[dateStr]) {
                const data = calendarDataOperationManager[dateStr];
                if (data.follow_up > 0) {
                    countsHTML += `<div class="count-badge badge-follow-up-calendar">${data.follow_up}</div>`;
                }
            }

            calendarHTML += `
                <div class="${dayClass}" onclick="selectDateOperationManager('${dateStr}')">
                    <div class="day-number">${day}</div>
                    ${countsHTML}
                </div>
            `;
        }

        document.getElementById('calendarGridOperationManager').innerHTML = calendarHTML;
    }

    function selectDateOperationManager(dateStr) {
        selectedDateOperationManager = dateStr;
        renderCalendarOperationManager();
        openCalendarLeadsModalOperationManager();
        loadDateLeadsOperationManager(dateStr);
    }

    function openCalendarLeadsModalOperationManager() {
        const date = new Date(selectedDateOperationManager);
        document.getElementById('selectedDateOperationManager').textContent =
            date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        document.getElementById('operationManagerCalendarLeadsModal').style.display = 'flex';
    }

    function loadDateLeadsOperationManager(dateStr) {
        const container = document.getElementById('leadsTableContainerOperationManager');
        container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading leads...</p></div>';

        fetch(`/operation-manager/dashboard/date-leads?date=${dateStr}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    container.innerHTML = `<div style="text-align: center; padding: 40px; color: #ef4444;">Error: ${data.error}</div>`;
                    return;
                }

                if (data.leads.length === 0) {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No leads found for this date.</div>';
                    return;
                }

                let tableHTML = `
                    <table class="leads-table">
                        <thead>
                            <tr>
                                <th>Lead ID</th>
                                <th>Customer Name</th>
                                <th>Contact No</th>
                                <th>Location</th>
                                <th>Query</th>
                                <th>Status</th>
                                <th>Date/Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.leads.forEach(lead => {
                    const dateTime = lead.date_time ? new Date(lead.date_time).toLocaleString('en-GB') : 'N/A';
                    tableHTML += `
                        <tr>
                            <td><strong>${lead.lead_id || '#' + lead.id}</strong></td>
                            <td>${lead.customer_name || 'N/A'}</td>
                            <td>${lead.contact_no || 'N/A'}</td>
                            <td>${lead.location || 'N/A'}</td>
                            <td>${lead.query || 'N/A'}</td>
                            <td><span class="status-badge badge-follow-up">${lead.status || 'N/A'}</span></td>
                            <td>${dateTime}</td>
                            <td>
                                <a href="/operation-manager/operation-leads/${lead.id}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    `;
                });

                tableHTML += '</tbody></table>';
                container.innerHTML = tableHTML;
            })
            .catch(error => {
                console.error('Error loading date leads:', error);
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading leads. Please try again.</div>';
            });
    }

    // View user details function
    function viewUserDetails(userId) {
        // Find the user data from the current users list
        const usersContainer = document.getElementById('usersTableContainer');
        const table = usersContainer.querySelector('table');

        if (!table) {
            alert('User data not found. Please refresh the users list.');
            return;
        }

        // Find the user row by ID
        const userRow = table.querySelector(`tr[data-user-id="${userId}"]`);
        if (!userRow) {
            alert('User not found.');
            return;
        }

        // Extract user data from the row
        const cells = userRow.querySelectorAll('td');
        const userData = {
            id: cells[0].textContent.replace('#', ''),
            image: cells[1].querySelector('img').src,
            name: cells[2].textContent,
            email: cells[3].textContent,
            mobile: cells[4].textContent,
            location: cells[5].textContent,
            role: cells[6].textContent,
            status: cells[7].textContent.trim(),
            created_at: cells[8].textContent
        };

        // Show user details in modal
        showUserDetails(userData);
    }

    function showUserDetails(user) {
        const content = document.getElementById('userDetailsContent');
        const statusClass = user.status === 'Active' ? 'badge-follow-up' : 'badge-closed';

        content.innerHTML = `
            <div style="display: grid; gap: 20px;">
                <!-- User Profile Section -->
                <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 12px;">
                    <img src="${user.image}" style="width: 80px; height: 80px; border-radius: 50%; margin-bottom: 16px; border: 3px solid #e5e7eb;" onerror="this.onerror=null; this.src='{{ asset('images/default-user.png') }}'">
                    <h3 style="margin: 0 0 8px 0; color: #1F2937;">${user.name}</h3>
                    <span class="status-badge ${statusClass}">${user.status}</span>
                </div>

                <!-- User Information Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">User ID:</label>
                        <div style="color: #1F2937; font-size: 16px;">#${user.id}</div>
                    </div>

                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">Email:</label>
                        <div style="color: #1F2937; font-size: 16px;">${user.email}</div>
                    </div>

                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">Mobile:</label>
                        <div style="color: #1F2937; font-size: 16px;">${user.mobile}</div>
                    </div>

                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">Location:</label>
                        <div style="color: #1F2937; font-size: 16px;">${user.location}</div>
                    </div>

                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">Role:</label>
                        <div style="color: #1F2937; font-size: 16px;">${user.role}</div>
                    </div>

                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">Created At:</label>
                        <div style="color: #1F2937; font-size: 16px;">${user.created_at}</div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('userDetailsModal').style.display = 'flex';
    }

    // Event listeners for calendar navigation
    document.addEventListener('DOMContentLoaded', function() {
        const prevBtn = document.getElementById('prevMonthOperationManager');
        const nextBtn = document.getElementById('nextMonthOperationManager');

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                currentDateOperationManager.setMonth(currentDateOperationManager.getMonth() - 1);
                loadCalendarDataOperationManager();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                currentDateOperationManager.setMonth(currentDateOperationManager.getMonth() + 1);
                loadCalendarDataOperationManager();
            });
        }

        // Load calendar data for the card count on page load
        loadOperationManagerCalendarCardData();
    });

    // Operation Manager Calendar Card functionality
    function loadOperationManagerCalendarCardData() {
        const year = new Date().getFullYear();
        const month = new Date().getMonth() + 1;

        fetch(`/operation-manager/dashboard/calendar-data?year=${year}&month=${month}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    updateOperationManagerCalendarCardCount(0);
                    return;
                }
                updateOperationManagerCalendarCardCount(data.calendar_data);
            })
            .catch(error => {
                console.error('Error loading operation manager calendar data:', error);
                updateOperationManagerCalendarCardCount(0);
            });
    }

    function updateOperationManagerCalendarCardCount(calendarData) {
        let totalFollowUp = 0;

        if (calendarData && typeof calendarData === 'object') {
            Object.values(calendarData).forEach(dayData => {
                totalFollowUp += dayData.follow_up || 0;
            });
        }

        const countElement = document.getElementById('operationManagerCalendarCount');

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

    // Outstanding Amount Modal Functions
    function openOutstandingAmountModal() {
        document.getElementById('outstandingAmountModal').style.display = 'flex';
        setupOutstandingDateInputs();
        populateOutstandingMonthYearDropdowns();
        loadOutstandingData('all');
    }

    function switchOutstandingTab(period) {
        // Update active tab
        const tabs = document.querySelectorAll('#outstandingAmountModal .stats-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        // Hide all selectors first
        const selectors = ['outstandingAllSelector', 'outstandingTodaySelector', 'outstandingMonthlySelector', 'outstandingYearlySelector', 'outstandingCustomSelector'];
        selectors.forEach(id => {
            document.getElementById(id).style.display = 'none';
        });

        if (period === 'all') {
            document.getElementById('outstandingAllSelector').style.display = 'flex';
            loadOutstandingData('all');
        } else if (period === 'today') {
            document.getElementById('outstandingTodaySelector').style.display = 'flex';
            loadOutstandingData('today');
        } else if (period === 'monthly') {
            document.getElementById('outstandingMonthlySelector').style.display = 'flex';
            const monthSelect = document.getElementById('outstandingMonthSelect');
            if (monthSelect.value) {
                loadOutstandingData('monthly', null, null, monthSelect.value);
            }
        } else if (period === 'yearly') {
            document.getElementById('outstandingYearlySelector').style.display = 'flex';
            const yearSelect = document.getElementById('outstandingYearSelect');
            if (yearSelect.value) {
                loadOutstandingData('yearly', null, null, yearSelect.value);
            }
        } else if (period === 'custom') {
            document.getElementById('outstandingCustomSelector').style.display = 'flex';
        }
    }

    function populateOutstandingMonthYearDropdowns() {
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        const currentMonth = currentDate.getMonth();

        // Populate month dropdown
        const monthSelect = document.getElementById('outstandingMonthSelect');
        monthSelect.innerHTML = '<option value="">Select Month</option>';

        for (let i = 0; i < 12; i++) {
            const date = new Date(currentYear, i, 1);
            const monthYear = date.toISOString().slice(0, 7);
            const monthName = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const option = document.createElement('option');
            option.value = monthYear;
            option.textContent = monthName;
            if (i === currentMonth) option.selected = true;
            monthSelect.appendChild(option);
        }

        // Populate year dropdown
        const yearSelect = document.getElementById('outstandingYearSelect');
        yearSelect.innerHTML = '<option value="">Select Year</option>';

        for (let year = currentYear; year >= currentYear - 5; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            if (year === currentYear) option.selected = true;
            yearSelect.appendChild(option);
        }
    }

    function setupOutstandingDateInputs() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('outstandingStartDate').max = today;
        document.getElementById('outstandingEndDate').max = today;
    }

    function applyOutstandingDateFilter() {
        const startDate = document.getElementById('outstandingStartDate').value;
        const endDate = document.getElementById('outstandingEndDate').value;

        if (!startDate || !endDate) {
            alert('Please select both start and end dates');
            return;
        }

        if (startDate > endDate) {
            alert('Start date cannot be after end date');
            return;
        }

        loadOutstandingData('custom', startDate, endDate);
    }

    function loadOutstandingData(period, startDate, endDate, selectedPeriod) {
        // Show loading state
        document.getElementById('outstandingDetailsTable').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner-modal"></div>
                <p style="margin-top: 16px; color: #6B7280;">Loading outstanding amount details...</p>
            </div>
        `;

        let url = '/operation-manager/dashboard/outstanding-amount-stats?period=' + period;

        if (period === 'monthly' && selectedPeriod) {
            url += '&selected_month=' + selectedPeriod;
        } else if (period === 'yearly' && selectedPeriod) {
            url += '&selected_year=' + selectedPeriod;
        } else if (period === 'custom' && startDate && endDate) {
            url += '&start_date=' + startDate + '&end_date=' + endDate;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateOutstandingDisplay(data);
            })
            .catch(error => {
                console.error('Error loading outstanding amount data:', error);
                document.getElementById('outstandingDetailsTable').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc2626;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                        <p>Error loading outstanding amount data.</p>
                    </div>
                `;
            });
    }

    function updateOutstandingDisplay(data) {
        // Update summary cards
        document.getElementById('totalOutstanding').textContent = '₹' + (data.total_outstanding || 0).toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('positiveOutstanding').textContent = '₹' + (data.positive_outstanding || 0).toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('negativeOutstanding').textContent = '₹' + (data.negative_outstanding || 0).toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('totalRecords').textContent = data.count || 0;

        // Update details table
        updateOutstandingDetailsTable(data.outstanding_details || []);
    }

    function updateOutstandingDetailsTable(outstandingDetails) {
        const container = document.getElementById('outstandingDetailsTable');

        if (outstandingDetails.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No outstanding amount records found for this period.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead ID</th>
                        <th>Customer Name</th>
                        <th>Contact No</th>
                        <th>Executive</th>
                        <th>Received Date</th>
                        <th>Outstanding Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        outstandingDetails.forEach(detail => {
            const receivedDate = detail.received_date ? new Date(detail.received_date).toLocaleDateString() : 'N/A';
            const outstandingAmount = parseFloat(detail.outstanding_payment || 0);
            const amountColor = outstandingAmount >= 0 ? '#dc2626' : '#059669';
            const amountSign = outstandingAmount >= 0 ? '+' : '';

            tableHTML += `
                <tr>
                    <td><strong>${detail.lead_id || '#' + (detail.operation_lead_id || 'N/A')}</strong></td>
                    <td>${detail.customer_name || 'N/A'}</td>
                    <td>${detail.contact_no || 'N/A'}</td>
                    <td>${detail.executive_name || 'N/A'}</td>
                    <td>${receivedDate}</td>
                    <td><strong style="color: ${amountColor};">${amountSign}₹${outstandingAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong></td>
                    <td>
                        <a href="/operation-manager/operation-leads/${detail.operation_lead_id}" class="btn action-btn view-btn" title="View Lead">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Deployment Pending Modal Functions
    function openDeploymentPendingModal() {
        document.getElementById('deploymentPendingModal').style.display = 'flex';
        loadDeploymentPendingData();
    }





    function loadDeploymentPendingData() {
        // Show loading state
        document.getElementById('deploymentPendingDetailsTable').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner-modal"></div>
                <p style="margin-top: 16px; color: #6B7280;">Loading deployment pending details...</p>
            </div>
        `;

        let url = '/operation-manager/dashboard/deployment-pending-stats';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateDeploymentPendingDisplay(data);
            })
            .catch(error => {
                console.error('Error loading deployment pending data:', error);
                document.getElementById('deploymentPendingDetailsTable').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc2626;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                        <p>Error loading deployment pending data.</p>
                    </div>
                `;
            });
    }

    function updateDeploymentPendingDisplay(data) {
        // Update summary cards
        document.getElementById('totalDeploymentPending').textContent = data.total_deployment_pending || 0;

        // Update details table
        updateDeploymentPendingDetailsTable(data.deployment_pending_details || []);
    }

    function updateDeploymentPendingDetailsTable(deploymentPendingDetails) {
        const container = document.getElementById('deploymentPendingDetailsTable');

        if (deploymentPendingDetails.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No deployment pending records found for this period.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead No</th>
                        <th>Executive Name</th>
                        <th>Deployment Date and Time</th>
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

        deploymentPendingDetails.forEach(detail => {
            // Format deployment date with time
            const deploymentDateTime = detail.deployment_date ? 
                new Date(detail.deployment_date).toLocaleDateString('en-GB') + ' ' + 
                new Date(detail.deployment_date).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) : 
                'N/A';

            // Get vendor contact number - check multiple possible fields
            const vendorNumber = detail.vendor_contact_no || detail.vendor_number || detail.vendor_phone || 'N/A';

            tableHTML += `
                <tr>
                    <td><strong>${detail.lead_id || '#' + (detail.operation_lead_id || 'N/A')}</strong></td>
                    <td>${detail.executive_name || 'N/A'}</td>
                    <td>${deploymentDateTime}</td>
                    <td>${detail.customer_name || 'N/A'}</td>
                    <td>${detail.contact_no || detail.customer_number || 'N/A'}</td>
                    <td>${detail.vendor_name || 'N/A'}</td>
                    <td>
                        ${vendorNumber !== 'N/A' ? `
                            <div style="display: flex; align-items: center; gap: 8px; justify-content: center;">
                                <span style="font-weight: 500;">${vendorNumber}</span>
                                <button onclick="makeCall('${vendorNumber}')" 
                                        class="btn btn-sm" 
                                        style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; padding: 4px 8px; border-radius: 6px; box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);"
                                        title="Call Vendor">
                                    <i class="fas fa-phone" style="font-size: 10px;"></i>
                                </button>
                            </div>
                        ` : 'N/A'}
                    </td>
                    <td>${detail.staff_name || 'N/A'}</td>
                    <td>${detail.staff_number || 'N/A'}</td>
                </tr>
            `;
        });

        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    }

    // Profile Pending Modal Functions
    function openProfilePendingModal() {
        document.getElementById('profilePendingModal').style.display = 'flex';
        loadProfilePendingData();
    }





    function loadProfilePendingData() {
        // Show loading state
        document.getElementById('profilePendingDetailsTable').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner-modal"></div>
                <p style="margin-top: 16px; color: #6B7280;">Loading profile pending details...</p>
            </div>
        `;

        let url = '/operation-manager/dashboard/profile-pending-stats';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateProfilePendingDisplay(data);
            })
            .catch(error => {
                console.error('Error loading profile pending data:', error);
                document.getElementById('profilePendingDetailsTable').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc2626;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                        <p>Error loading profile pending data.</p>
                    </div>
                `;
            });
    }

    function updateProfilePendingDisplay(data) {
        // Update summary cards
        document.getElementById('totalProfilePending').textContent = data.total_profile_pending || 0;

        // Update details table
        updateProfilePendingDetailsTable(data.profile_pending_leads || []);
    }

    function updateProfilePendingDetailsTable(profilePendingLeads) {
        const container = document.getElementById('profilePendingDetailsTable');

        if (profilePendingLeads.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No profile pending records found for this period.</div>';
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

        profilePendingLeads.forEach(lead => {
            const leadDateTime = lead.date_time ? 
                new Date(lead.date_time).toLocaleDateString('en-GB') + ' ' + 
                new Date(lead.date_time).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) : 
                'N/A';

            tableHTML += `
                <tr>
                    <td><strong>${lead.lead_id || '#' + (lead.id || 'N/A')}</strong></td>
                    <td>${lead.executive_name || 'N/A'}</td>
                    <td>${leadDateTime}</td>
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

    // Ongoing/Stop Modal Functions
    function openOngoingModal() {
        document.getElementById('ongoingModal').style.display = 'flex';
        setupOngoingDateInputs();
        populateOngoingMonthYearDropdowns();
        loadOngoingData('all');
    }

    function switchOngoingTab(period) {
        // Update active tab
        const tabs = document.querySelectorAll('#ongoingModal .stats-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');

        // Hide all selectors first
        const selectors = ['ongoingAllSelector', 'ongoingTodaySelector', 'ongoingMonthlySelector', 'ongoingYearlySelector', 'ongoingCustomSelector'];
        selectors.forEach(id => {
            document.getElementById(id).style.display = 'none';
        });

        if (period === 'all') {
            document.getElementById('ongoingAllSelector').style.display = 'flex';
            loadOngoingData('all');
        } else if (period === 'today') {
            document.getElementById('ongoingTodaySelector').style.display = 'flex';
            loadOngoingData('today');
        } else if (period === 'monthly') {
            document.getElementById('ongoingMonthlySelector').style.display = 'flex';
            const monthSelect = document.getElementById('ongoingMonthSelect');
            if (monthSelect.value) {
                loadOngoingData('monthly', null, null, monthSelect.value);
            }
        } else if (period === 'yearly') {
            document.getElementById('ongoingYearlySelector').style.display = 'flex';
            const yearSelect = document.getElementById('ongoingYearSelect');
            if (yearSelect.value) {
                loadOngoingData('yearly', null, null, yearSelect.value);
            }
        } else if (period === 'custom') {
            document.getElementById('ongoingCustomSelector').style.display = 'flex';
        }
    }

    function populateOngoingMonthYearDropdowns() {
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        const currentMonth = currentDate.getMonth();

        // Populate month dropdown
        const monthSelect = document.getElementById('ongoingMonthSelect');
        monthSelect.innerHTML = '<option value="">Select Month</option>';

        for (let i = 0; i < 12; i++) {
            const date = new Date(currentYear, i, 1);
            const monthYear = date.toISOString().slice(0, 7);
            const monthName = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const option = document.createElement('option');
            option.value = monthYear;
            option.textContent = monthName;
            if (i === currentMonth) option.selected = true;
            monthSelect.appendChild(option);
        }

        // Populate year dropdown
        const yearSelect = document.getElementById('ongoingYearSelect');
        yearSelect.innerHTML = '<option value="">Select Year</option>';

        for (let year = currentYear; year >= currentYear - 5; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            if (year === currentYear) option.selected = true;
            yearSelect.appendChild(option);
        }
    }

    function setupOngoingDateInputs() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('ongoingStartDate').max = today;
        document.getElementById('ongoingEndDate').max = today;
    }

    function applyOngoingDateFilter() {
        const startDate = document.getElementById('ongoingStartDate').value;
        const endDate = document.getElementById('ongoingEndDate').value;

        if (!startDate || !endDate) {
            alert('Please select both start and end dates');
            return;
        }

        if (startDate > endDate) {
            alert('Start date cannot be after end date');
            return;
        }

        loadOngoingData('custom', startDate, endDate);
    }

    function loadOngoingData(period, startDate, endDate, selectedPeriod) {
        // Show loading state
        document.getElementById('ongoingDetailsTable').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loading-spinner-modal"></div>
                <p style="margin-top: 16px; color: #6B7280;">Loading ongoing details...</p>
            </div>
        `;

        let url = '/operation-manager/dashboard/ongoing-stats?period=' + period;

        if (period === 'monthly' && selectedPeriod) {
            url += '&selected_month=' + selectedPeriod;
        } else if (period === 'yearly' && selectedPeriod) {
            url += '&selected_year=' + selectedPeriod;
        } else if (period === 'custom' && startDate && endDate) {
            url += '&start_date=' + startDate + '&end_date=' + endDate;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateOngoingDisplay(data);
            })
            .catch(error => {
                console.error('Error loading ongoing data:', error);
                document.getElementById('ongoingDetailsTable').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc2626;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                        <p>Error loading ongoing data.</p>
                    </div>
                `;
            });
    }

    function updateOngoingDisplay(data) {
        // Update summary cards
        document.getElementById('totalOngoing').textContent = data.total_ongoing || 0;
        document.getElementById('todayOngoing').textContent = data.today_ongoing || 0;
        document.getElementById('monthOngoing').textContent = data.month_ongoing || 0;
        document.getElementById('yearOngoing').textContent = data.year_ongoing || 0;

        // Update details table
        updateOngoingDetailsTable(data.ongoing_leads || []);
    }

    function updateOngoingDetailsTable(ongoingLeads) {
        const container = document.getElementById('ongoingDetailsTable');

        if (ongoingLeads.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No ongoing records found for this period.</div>';
            return;
        }

        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead ID</th>
                        <th>Customer Name</th>
                        <th>Contact No</th>
                        <th>Executive</th>
                        <th>Location</th>
                        <th>Query</th>
                        <th>Status</th>
                        <th>Ongoing/Stopped</th>
                        <th>Created Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        ongoingLeads.forEach(lead => {
            const createdDate = lead.created_at ? new Date(lead.created_at).toLocaleDateString() : 'N/A';

            tableHTML += `
                <tr>
                    <td><strong>${lead.lead_id || '#' + (lead.id || 'N/A')}</strong></td>
                    <td>${lead.customer_name || 'N/A'}</td>
                    <td>${lead.contact_no || 'N/A'}</td>
                    <td>${lead.executive_name || 'N/A'}</td>
                    <td>${lead.location || 'N/A'}</td>
                    <td>${lead.query || 'N/A'}</td>
                    <td><span class="status-badge badge-${(lead.status || 'default').toLowerCase().replace(/\s+/g, '-')}">${lead.status || 'N/A'}</span></td>
                    <td><span class="badge badge-success" style="color:black;">Ongoing</span></td>
                    <td>${createdDate}</td>
                    <td>
                        <a href="/operation-manager/operation-leads/${lead.id}" class="btn action-btn view-btn" title="View Lead">
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

    function loadPaymentDueData() {
        const container = document.getElementById('paymentDueTableBody');
        container.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading payment due data...</p></td></tr>';

        const url = '{{ route("operation-manager.dashboard.payment-due-stats") }}';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePaymentDueDisplay(data.data);
                } else {
                    container.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #ef4444;">Error: ' + (data.message || 'Failed to load data') + '</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading payment due data:', error);
                container.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #ef4444;">Error loading payment due data</td></tr>';
            });
    }

    function updatePaymentDueDisplay(data) {
        // Update summary cards
        document.getElementById('paymentDueTotalCount').textContent = data.total_count || 0;
        document.getElementById('paymentDueTotalAmount').textContent = '₹' + (data.total_amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });

        // Calculate unique team members affected
        const uniqueStaff = new Set();
        if (data.payments && data.payments.length > 0) {
            data.payments.forEach(payment => {
                if (payment.staff_name && payment.staff_name !== 'N/A') {
                    uniqueStaff.add(payment.staff_name);
                }
            });
        }
        document.getElementById('paymentDueTeamCount').textContent = uniqueStaff.size;

        // Update table
        const container = document.getElementById('paymentDueTableBody');

        if (!data.payments || data.payments.length === 0) {
            container.innerHTML = `
                <tr>
                    <td colspan="8" class="loading-row">
                        <div class="loading-content">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                            <p>No unverified payments found for the selected period</p>
                            <small style="color: #9ca3af;">Try selecting a different time period or check back later</small>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        let tableHTML = '';
        data.payments.forEach(payment => {
            tableHTML += `
                <tr>
                    <td class="text-center">
                        <span class="lead-id-badge">${payment.lead_id || 'N/A'}</span>
                    </td>
                    <td>
                        <div class="customer-info">
                            <strong>${payment.customer_name || 'N/A'}</strong>
                        </div>
                    </td>
                    <td>
                        <div class="vendor-info">
                            <i class="fas fa-building" style="color: #6b7280; margin-right: 6px;"></i>
                            ${payment.vendor_name || 'N/A'}
                        </div>
                    </td>
                    <td>
                        <div class="staff-info">
                            <i class="fas fa-user-tie" style="color: #6b7280; margin-right: 6px;"></i>
                            ${payment.staff_name || 'N/A'}
                        </div>
                    </td>
                    <td class="text-right">
                        <span class="amount-badge">₹${(payment.vendor_payment || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
                    </td>
                    <td class="text-center">
                        <span class="date-info">${payment.deployment_date || 'N/A'}</span>
                    </td>
                    <td class="text-center">
                        <span class="date-info">${payment.created_at || 'N/A'}</span>
                    </td>
                    <td class="text-center">
                        <a href="${payment.view_url || '#'}" class="action-btn view-btn" title="View Lead Details" target="_blank">
                            <i class="fas fa-eye"></i>
                            <span>View</span>
                        </a>
                    </td>
                </tr>
            `;
        });

        container.innerHTML = tableHTML;
    }

    function refreshPaymentDueData() {
        loadPaymentDueData();
    }

    function exportPaymentDueData() {
        const table = document.getElementById('paymentDueTable');
        const rows = table.querySelectorAll('tbody tr');

        if (rows.length === 0 || rows[0].querySelector('.loading-content')) {
            alert('No data available to export');
            return;
        }

        let csvContent = 'Lead ID,Customer,Vendor,Staff,Amount,Deployment Date,Created\n';

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 7) {
                const leadId = cells[0].textContent.trim();
                const customer = cells[1].textContent.trim();
                const vendor = cells[2].textContent.trim();
                const staff = cells[3].textContent.trim();
                const amount = cells[4].textContent.trim();
                const deploymentDate = cells[5].textContent.trim();
                const created = cells[6].textContent.trim();

                csvContent += `"${leadId}","${customer}","${vendor}","${staff}","${amount}","${deploymentDate}","${created}"\n`;
            }
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'payment_due_report_' + new Date().toISOString().slice(0, 10) + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
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

        fetch('{{ route("operation-manager.dashboard.job-request-stats") }}')
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
                    <td><strong>${jobRequest.lead_id || '#' + jobRequest.id}</strong></td>
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

        fetch('{{ route("operation-manager.dashboard.vendor-freelancer-payment-stats") }}')
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

        fetch('{{ route("operation-manager.dashboard.recent-leads-stats") }}')
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
                        <a href="/operation-manager/operation-leads/${lead.id}" class="btn action-btn view-btn" title="View Lead">
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

        fetch('{{ route("operation-manager.dashboard.unverified-deployment-payments-stats") }}')
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
                    <td><span class="badge badge-warning">${deployment.deployment_status}</span></td>
                    <td><strong>₹${parseFloat(deployment.vendor_payment).toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong></td>
                    <td style="text-align: center;">
                        <div class="form-check d-flex justify-content-center">
                            <input class="form-check-input verify-payment-checkbox-dashboard" 
                                   type="checkbox" 
                                   data-deployment-id="${deployment.id}"
                                   onchange="toggleVerifyPaymentDashboard(${deployment.id}, this.checked)"
                                   title="Verify Payment"
                                   style="cursor: pointer; width: 20px; height: 20px; accent-color: #28a745;">
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
            url: `/operation-manager/operation-leads/deployment/${deploymentId}/toggle-verify-payment`,
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

        fetch('{{ route("operation-manager.dashboard.pending-callbacks") }}')
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

    function updatePendingCallbacksDisplay(data) {
        // Update summary cards
        document.getElementById('totalPendingCallbacks').textContent = data.total_count;

        // Generate table - always show table structure
        const container = document.getElementById('pendingCallbacksTableContainer');
        
        let tableHTML = `
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Lead No</th>
                        <th>Call For</th>
                        <th>Executive</th>
                        <th>Unanswered Date & Time</th>
                        <th>Call Response Time & Date</th>
                        <th>Customer Name</th>
                        <th>Customer Number</th>
                        <th>Lead Source</th>
                        <th>Last Call Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (!data.callbacks || data.callbacks.length === 0) {
            tableHTML += `
                <tr>
                    <td colspan="10" class="text-center text-muted" style="padding: 40px;">
                        <i class="fas fa-phone-slash fa-3x mb-3" style="color: #d1d5db;"></i>
                        <br>
                        <strong>No pending callbacks found</strong>
                        <br>
                        <small>Callbacks will appear here when there are missed, busy, or failed calls from your team</small>
                    </td>
                </tr>
            `;
        } else {
            data.callbacks.forEach(callback => {
                const callStatusClass = callback.last_call_status === 'busy' ? 'warning' : 
                                       callback.last_call_status === 'failed' ? 'danger' : 
                                       callback.last_call_status === 'switchedoff' || callback.last_call_status === 'switched off' ? 'secondary' : 'warning';
                
                const leadNoDisplay = callback.lead_no === 'No Lead' ? '<span class="badge bg-secondary">No Lead</span>' : `<strong>${callback.lead_no}</strong>`;
                
                const responseTimeDisplay = callback.call_response_datetime ? 
                    `<span class="badge bg-success">${callback.call_response_datetime}</span>` : 
                    '<span class="text-muted">No Response Yet</span>';
                
                // Determine call_for badge color
                const callForDisplay = callback.call_for_display || callback.call_for || 'N/A';
                const callForClass = callback.call_for === 'operation_lead' ? 'primary' : 
                                    callback.call_for === 'job_request' ? 'success' : 'secondary';
                
                tableHTML += `
                    <tr>
                        <td>${leadNoDisplay}</td>
                        <td><span class="badge bg-${callForClass}">${callForDisplay}</span></td>
                        <td>${callback.executive_name}</td>
                        <td>${callback.unanswered_datetime}</td>
                        <td>${responseTimeDisplay}</td>
                        <td>${callback.customer_name}</td>
                        <td>
                            <strong>${callback.customer_number}</strong>
                            <br><button onclick="makeCall('${callback.customer_number}')" class="btn btn-sm btn-success mt-1" title="Call Now">
                                <i class="fas fa-phone"></i> Call Back
                            </button>
                        </td>
                        <td><span class="badge bg-info">${callback.lead_source}</span></td>
                        <td><span class="badge bg-${callStatusClass}">${callback.last_call_status}</span></td>
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

    function loadPendingCallbacksCount() {
        fetch('{{ route("operation-manager.dashboard.pending-callbacks") }}')
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

        fetch('{{ route("operation-manager.dashboard.recent-calls") }}')
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
                        <th>Executive</th>
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
                    <td colspan="9" class="text-center text-muted" style="padding: 40px;">
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
                        <td>${call.executive_name || 'N/A'}</td>
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
        fetch('{{ route("operation-manager.dashboard.recent-calls") }}')
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

    function makeCallFromDashboard(phoneNumber) {
        if (!phoneNumber || phoneNumber === 'N/A') {
            alert('Invalid phone number');
            return;
        }
        
        if (!confirm('Are you sure you want to make this call?')) {
            return;
        }

        // Show loading state
        const event_target = event.target;
        const originalContent = event_target.closest('button').innerHTML;
        event_target.closest('button').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        event_target.closest('button').disabled = true;

        fetch(`/call-outbound/${phoneNumber}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Call initiated successfully');
                } else {
                    alert(data.message || 'Failed to initiate call');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error initiating call');
            })
            .finally(() => {
                event_target.closest('button').innerHTML = originalContent;
                event_target.closest('button').disabled = false;
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
            const executiveName = (call.executive_name || '').toLowerCase();
            const leadNo = (call.lead_no || '').toLowerCase();
            const callDir = (call.call_direction || '').toLowerCase();
            
            return phoneNumber.includes(searchTerm) || 
                   customerName.includes(searchTerm) || 
                   executiveName.includes(searchTerm) ||
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

    // Load pending callbacks count on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadPendingCallbacksCount();
        loadRecentCallsCount();
    });
</script>
@endsection

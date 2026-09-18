@extends('admin.layouts.app')
@section('title', 'Dashboard | Admin')
{{-- header code --}}
@section('header-css')
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
        display: none;
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

    /* Tab Styling for Recent Calls */
    .calls-tabs {
        display: flex;
        background: white;
        border-radius: 12px;
        padding: 4px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
    }

    .calls-tab {
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

    .calls-tab.active {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    /* Filter Tab Styling */
    .filter-tabs {
        display: flex;
        background: white;
        border-radius: 12px;
        padding: 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
    }

    .filter-tab {
        flex: 1;
        padding: 10px 16px;
        text-align: center;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        color: #6B7280;
        font-size: 14px;
        border: none;
        background: transparent;
    }

    .filter-tab:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .filter-tab.active {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
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
        padding: 12px;
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
        font-size: 11px;
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
        0% {
            transform: translateX(-5px);
        }
        100% {
            transform: translateX(0);
        }
    }

    .clickable-card {
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .clickable-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }

    .clickable-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    }

    .clickable-card:hover::after {
        left: 100%;
    }

    .clickable-card:active {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
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
        backdrop-filter: blur(4px);
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

    .date-filter-section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .date-filter-section:hover {
        border-color: #667eea;
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.1);
    }

    .period-selector {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px 16px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
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

    .date-input-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .date-input {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        color: #374151;
        background: white;
        transition: border-color 0.2s ease;
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

    .filter-btn:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        transform: none;
    }

    .quick-date-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .quick-date-btn {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .quick-date-btn:hover {
        background: #e5e7eb;
        border-color: #9ca3af;
    }

    .quick-date-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .stats-tab {
        flex: 1;
        padding: 12px 16px;
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
        padding: 20px;
        text-align: center;
    }

    .stats-card-title {
        color: #6B7280;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
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

    /* Pending Deployments Table Responsive */
    .pending-deployments-container {
        overflow-x: auto;
    }

    .pending-deployments-container .leads-table {
        min-width: 1200px;
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



    .leads-section {
        height: 500px !important;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .tabs-header {
        display: flex;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }

    .tab-button {
        flex: 1;
        padding: 16px 24px;
        background: transparent;
        border: none;
        color: #6B7280;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .tab-button.active {
        color: #667eea;
        background: white;
    }

    .tab-button.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: #667eea;
    }

    .tab-button:hover:not(.active) {
        background: #f3f4f6;
        color: #374151;
    }

    .tab-content {
        display: none;
        padding: 0;
    }

    .tab-content.active {
        display: block;
    }

    .table-container {
        background: white;
        overflow: hidden;
    }

    .table-header {
        background: #f8fafc;
        padding: 16px 24px;
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
        max-height: 500px;
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
    .table-scroll-container {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 443px;
        width: 100%;
        position: relative;
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

    .status-new {
        background: #ecfdf5;
        color: #059669;
    }

    .status-new::before {
        background: #059669;
    }

    /* Operation Leads Status Badges */
    .badge-profile-shared {
        color: #3498db;
        border: 1.5px solid #3498db !important;
        background: #bdd9ee !important;
    }
    .badge-closed {
        color: #e74c3c;
        border: 1.5px solid #e74c3c !important;
        background: #f0665738 !important;
    }
    .badge-follow-up {
        color: #f39c12;
        border: 1.5px solid #f39c12 !important;
        background: #fbe9a8 !important;
    }
    .badge-inactive {
        color: #95a5a6;
        border: 1.5px solid #95a5a6 !important;
        background: #b9d9fa !important;
    }
    .badge-future-prospect {
        color: #9b59b6;
        border: 1.5px solid #9b59b6 !important;
        background: #e8d5f0 !important;
    }
    .badge-prospect {
        color: #27ae60;
        border: 1.5px solid #27ae60 !important;
        background: #d5f4e6 !important;
    }
    .badge-no-response {
        color: #34495e;
        border: 1.5px solid #34495e !important;
        background: #ecf0f1 !important;
    }
    .badge-price-issue {
        color: #e67e22;
        border: 1.5px solid #e67e22 !important;
        background: #fad7a0 !important;
    }
    .badge-duplicate {
        color: #8e44ad;
        border: 1.5px solid #8e44ad !important;
        background: #e8d5f0 !important;
    }
    .badge-spam {
        color: #c0392b;
        border: 1.5px solid #c0392b !important;
        background: #f1948a !important;
    }

    /* Sales Leads Status Badges */
    .status-active {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .status-inactive {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    .status-closed {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .status-profile-required {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    .status-default {
        background: #e2e3e5;
        color: #383d41;
        border: 1px solid #d6d8db;
    }

    /* Query Badges */
    .badge-attendant,
    .badge-nurse,
    .badge-on-call-nurse,
    .badge-doctor {
        color: #16a085;
        border: 1.5px solid #16a085 !important;
        background: #97fae7;
    }
    .badge-medicine {
        color: #8e44ad;
        border: 1.5px solid #8e44ad !important;
        background: #e4b5f8;
    }
    .badge-job-request {
        color: #2980b9;
        border: 1.5px solid #2980b9 !important;
        background: #a1d8fd;
    }
    .badge-physiotherapy {
        color: #d35400;
        border: 1.5px solid #d35400 !important;
        background: #f6b991;
    }
    .badge-default {
        color: #7f8c8d;
        border: 1.5px solid #7f8c8d !important;
        background: #9cf3f9;
    }

    .action-icons {
        display: flex;
        gap: 6px;
    }

    .action-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6B7280;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
    }

    .action-icon:hover {
        background: #667eea;
        color: white;
        transform: scale(1.05);
    }

    /* Action Buttons for Operation Leads */
    .action-btn {
        padding: 4px 8px;
        margin: 0 2px;
        border: none;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .view-btn {
        background: #17a2b8;
        color: white;
    }

    .view-btn:hover {
        background: #138496;
        color: white;
    }

    .edit-btn {
        background: #ffc107;
        color: #212529;
    }

    .edit-btn:hover {
        background: #e0a800;
        color: #212529;
    }

    .delete-btn {
        background: #dc3545;
        color: white;
    }

    .delete-btn:hover {
        background: #c82333;
        color: white;
    }



    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #667eea;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 14px;
        margin-right: 10px;
    }

    .user-info {
        display: flex;
        align-items: center;
    }

    .user-name {
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 2px;
        font-size: 13px;
    }

    .user-email {
        font-size: 11px;
        color: #6B7280;
    }

    /* Contact Information Styling */
    .contact-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .contact-number {
        font-weight: 600;
        color: #1F2937;
        font-size: 13px;
    }

    .contact-actions {
        display: flex;
        gap: 6px;
    }

    .contact-btn {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 12px;
        position: relative;
        overflow: hidden;
    }

    .contact-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: currentColor;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .contact-btn:hover::before {
        opacity: 0.1;
    }

    .call-btn {
        background: #f0f9ff;
        color: #0ea5e9;
        border: 1px solid #e0f2fe;
    }

    .call-btn:hover {
        background: #0ea5e9;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
    }

    .whatsapp-btn {
        background: #f0fdf4;
        color: #22c55e;
        border: 1px solid #dcfce7;
    }

    .whatsapp-btn:hover {
        background: #22c55e;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
    }

    .contact-btn i {
        position: relative;
        z-index: 1;
    }
.main-footer{
    margin-top: 0px !important;
}
    .refresh-btn {
        position: fixed;
        bottom: 60px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #667eea;
        color: white;
        border: none;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .refresh-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.02);
        }
    }

    .clickable-card {
        animation: pulse 3s ease-in-out infinite;
    }

    .clickable-card:hover {
        animation: none;
    }

    /* Dashboard Content Wrapper Scrollbar Styling */
    .dashboard-content-wrapper {
        scrollbar-width: thin;
        scrollbar-color: #667eea #f1f3f5;
    }

    /* Horizontal Scrollbar */
    .dashboard-content-wrapper::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }

    .dashboard-content-wrapper::-webkit-scrollbar-track {
        background: #f1f3f5;
        border-radius: 10px;
    }

    .dashboard-content-wrapper::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 10px;
        transition: background 0.3s ease;
    }

    .dashboard-content-wrapper::-webkit-scrollbar-thumb:hover {
        background: #5a67d8;
    }

    /* Scrollbar corner (where horizontal and vertical meet) */
    .dashboard-content-wrapper::-webkit-scrollbar-corner {
        background: #f1f3f5;
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

        .tabs-header {
            flex-direction: column;
        }

        .tab-button {
            text-align: center;
        }
    }
</style>
@endsection

@section('main')
    <div class="content-wrapper">
            <div class="container-fluid" >
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1 class="dashboard-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 13H11V3H3V13ZM3 21H11V15H3V21ZM13 21H21V11H13V21ZM13 3V9H21V3H13Z" fill="#667eea"/>
                    </svg>
                    Admin Dashboard
                </h1>
                <p class="dashboard-subtitle">Welcome back! Here's what's happening with your business today.</p>
            </div>

            <!-- Dashboard Content Wrapper with Horizontal Scroll -->
            <div class="dashboard-content-wrapper" style="height: calc(100vh - 120px); overflow-x: auto; overflow-y: auto; width: 100%; padding-bottom: 20px;">
                <div style="min-width: 1200px;">
                    <!-- Main Stats Cards -->
                    <div class="stats-grid">
                <div class="dashboard-card clickable-card" onclick="openSalesLeadsModal()" style="cursor: pointer;">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" fill="white"/>
                                    <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" fill="white"/>
                                </svg>
                            </div>
                            <div>
                                <p class="card-title">Sales Leads</p>
                                <h3 class="card-count">{{ number_format($newSalesLeadsCount) }}</h3>
                            </div>
                        </div>
                        <div class="card-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card clickable-card" onclick="openOperationLeadsModal()" style="cursor: pointer;">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" fill="white"/>
                                    <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" fill="white"/>
                                    <path d="M12 7L12 11" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M9 10L12 7L15 10" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div>
                                <p class="card-title">Operation Leads</p>
                                <h3 class="card-count">{{ number_format($newOperationLeadsCount) }}</h3>
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
                            <div class="card-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" fill="white"/>
                                    <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" fill="white"/>
                                </svg>
                            </div>
                            <div>
                                <p class="card-title">System Users</p>
                                <h3 class="card-count">{{ number_format($activeUsersCount + $inactiveUsersCount) }}</h3>
                                <div style="display: flex; gap: 12px; align-items: center; margin-top: 4px; justify-content: flex-end;">
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e;"></div>
                                        <span style="color: #22c55e; font-size: 12px; font-weight: 600;">{{ number_format($activeUsersCount) }} Active</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></div>
                                        <span style="color: #ef4444; font-size: 12px; font-weight: 600;">{{ number_format($inactiveUsersCount) }} Inactive</span>
                                    </div>
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
                            <div class="card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div>
                                <p class="card-title">Today's Tasks</p>
                                <h3 class="card-count">{{ number_format($todayTasksCount) }}</h3>
                            </div>
                        </div>
                        <div class="card-action">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card clickable-card" onclick="openAdminCalendarModal()" style="cursor: pointer;">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 2V5M16 2V5M3.5 9.09H20.5M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15.6947 14.7H15.7037M15.6947 17.7H15.7037M11.9955 14.7H12.0045M11.9955 17.7H12.0045M8.29431 14.7H8.30329M8.29431 17.7H8.30329" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div>
                                <p class="card-title">Follow-up Calendar</p>
                                <h3 class="card-count" id="adminCalendarCount" style="font-size: 12px; font-weight: 600;">
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

                <div class="dashboard-card clickable-card" onclick="openRevenueModal()" style="cursor: pointer;">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon" style="background: linear-gradient(135deg, #059669, #047857);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" fill="white"/>
                                </svg>
                            </div>
                            <div>
                                <p class="card-title">Today's Revenue</p>
                                <h3 class="card-count" style="color: {{ $todayRevenue >= 0 ? '#059669' : '#dc2626' }};">
                                    ₹{{ number_format($todayRevenue, 2) }}
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

                <div class="dashboard-card clickable-card" onclick="openPendingDeploymentsModal()" style="cursor: pointer;">
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
                                <p class="card-title">Pending Deployments</p>
                                <h3 class="card-count" style="color: #f59e0b;">
                                    {{ number_format($pendingDeploymentsCount) }}
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
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="7" r="4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 11v6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 14h6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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

                <!-- Vendor Payment Card -->
                <div class="dashboard-card clickable-card" onclick="openVendorPaymentModal()" style="cursor: pointer;">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" fill="white"/>
                                </svg>
                            </div>
                            <div>
                                <p class="card-title">Vendor Payment</p>
                                <h3 class="card-count" style="color: {{ $totalVendorRemainingAmount >= 0 ? '#8b5cf6' : '#ef4444' }};">
                                    ₹{{ number_format($totalVendorRemainingAmount, 2) }}
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
                                <p class="card-title">Total Outstanding</p>
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

                <!-- Unverified Deployment Payments Card -->
                <div class="dashboard-card clickable-card" onclick="openUnverifiedDeploymentModal()" style="cursor: pointer;">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 11L12 14L22 4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div>
                                <p class="card-title">Unverified Payments</p>
                                <h3 class="card-count" style="color: #dc2626;">
                                    <i class="fas fa-spinner fa-spin"></i>
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

                <!-- Pending Callbacks Card -->
                <div class="dashboard-card clickable-card" onclick="openPendingCallbacksModal()" style="cursor: pointer;">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="card-icon" style="background: linear-gradient(135deg, #f97316, #ea580c);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 5a2 2 0 0 1 2-2h3.28a1 1 0 0 1 .948.684l1.498 4.493a1 1 0 0 1-.502 1.21l-2.257 1.13a11.042 11.042 0 0 0 5.516 5.516l1.13-2.257a1 1 0 0 1 1.21-.502l4.493 1.498a1 1 0 0 1 .684.949V19a2 2 0 0 1-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 2h7" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M18 2v7" stroke="white" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div>
                                <p class="card-title">Pending Callbacks</p>
                                <h3 class="card-count" style="color: #f97316;">
                                    <i class="fas fa-spinner fa-spin"></i>
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

            <!-- Leads Section with Tabs -->
            <div class="leads-section">
                <div class="tabs-header">
                    <button class="tab-button active" onclick="switchTab('sales')">
                        Sales Leads
                    </button>
                    <button class="tab-button" onclick="switchTab('operation')">
                        Operation Leads
                    </button>
                </div>

                <!-- Sales Leads Tab -->
                <div id="sales-tab" class="tab-content active">
                    <div class="table-container">
                        <div class="table-scroll-container">
                            <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Lead ID</th>
                                    <th>Date</th>
                                    <th>Executive</th>
                                    <th>Customer</th>
                                    <th>Mobile</th>
                                    <th>Last Call</th>
                                    <th>Location</th>
                                    <th>Source</th>
                                    <th>Query</th>
                                    <th>Status</th>
                                    <th>Stage</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestSalesLeads as $lead)
                                <tr>
                                    <td><strong>#{{ $lead->id }}</strong></td>
                                    <td>{{ $lead->date ? \Carbon\Carbon::parse($lead->date)->format('d-M-Y H:i') : 'N/A' }}</td>
                                    <td>{{ $lead->executive ?? 'N/A' }}</td>
                                    <td>{{ $lead->customer_name ?? 'N/A' }}</td>
                                    <td>{{ $lead->contact_no ?? 'N/A' }}</td>
                                    <td>
                                        @if($lead->last_call_status)
                                            @php
                                                $callStatusClass = match(strtolower($lead->last_call_status)) {
                                                    'answered' => 'badge bg-success',
                                                    'busy' => 'badge bg-warning',
                                                    'no answer', 'noanswer' => 'badge bg-danger',
                                                    'failed' => 'badge bg-secondary',
                                                    default => 'badge bg-info'
                                                };
                                                $callStatusText = match(strtolower($lead->last_call_status)) {
                                                    'answered' => 'Answered',
                                                    'busy' => 'Busy',
                                                    'no answer', 'noanswer' => 'Missed',
                                                    'failed' => 'Failed',
                                                    default => ucfirst($lead->last_call_status)
                                                };
                                            @endphp
                                            <span class="{{ $callStatusClass }}">{{ $callStatusText }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $lead->location ?? 'N/A' }}</td>
                                    <td>{{ strtoupper($lead->lead_source ?? 'N/A') }}</td>
                                    <td>
                                        @if($lead->query)
                                            <span class="status-badge badge-{{ strtolower(str_replace(' ', '-', $lead->query)) }}">{{ ucfirst($lead->query) }}</span>
                                        @else
                                            <span class="status-badge badge-default">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($lead->status)
                                            @php
                                                $statusClass = match($lead->status) {
                                                    'follow-up' => 'badge bg-info',
                                                    'future prospect' => 'badge bg-warning',
                                                    'prospect' => 'badge bg-success',
                                                    'no response' => 'badge bg-secondary',
                                                    'price issue' => 'badge bg-secondary',
                                                    'duplicate' => 'badge bg-danger',
                                                    'spam' => 'badge bg-dark',
                                                    default => 'badge bg-secondary'
                                                };
                                            @endphp
                                            <span class="{{ $statusClass }}">{{ ucfirst($lead->status) }}</span>
                                        @else
                                            <span class="badge bg-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($lead->stage)
                                            @php
                                                $stageClass = match($lead->stage) {
                                                    'active' => 'status-badge status-active',
                                                    'inactive' => 'status-badge status-inactive',
                                                    'closed' => 'status-badge status-closed',
                                                    'profile required' => 'status-badge status-profile-required',
                                                    default => 'status-badge status-default'
                                                };
                                            @endphp
                                            <span class="{{ $stageClass }}">{{ ucfirst($lead->stage) }}</span>
                                        @else
                                            <span class="status-badge status-default">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button onclick="openLeadDetailsModal({{ $lead->id }}, 'sales')" class="btn action-btn view-btn" data-id="{{ $lead->id }}" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="12" style="text-align: center; padding: 40px; color: #6B7280;">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-bottom: 12px;">
                                            <path d="M20 6L9 17L4 12" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <div>No sales leads found</div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <!-- Operation Leads Tab -->
                <div id="operation-tab" class="tab-content">
                    <div class="table-container">
                        <div class="table-scroll-container">
                            <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Lead ID</th>
                                    <th>Date/Time</th>
                                    <th>Executive</th>
                                    <th>Customer</th>
                                    <th>Contact No</th>
                                    <th>Location</th>
                                    <th>Vendor</th>
                                    <th>Query</th>
                                    <th>Status</th>
                                    <th>Ongoing/Stopped</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestOperationLeads as $lead)
                                <tr>
                                    <td><strong>#{{ $lead->id }}</strong></td>
                                    <td>{{ $lead->date_time ? \Carbon\Carbon::parse($lead->date_time)->format('d-M-Y H:i') : 'N/A' }}</td>
                                    <td>{{ $lead->executive_name ?: 'N/A' }}</td>
                                    <td>
                                        @if($lead->patient_name)
                                            {{ $lead->customer_name ?? 'N/A' }} ({{ $lead->patient_name }})
                                        @else
                                            {{ $lead->customer_name ?? 'N/A' }}
                                        @endif
                                    </td>
                                    <td>{{ $lead->contact_no ?? 'N/A' }}</td>
                                    <td>{{ $lead->location ?? 'N/A' }}</td>
                                    <td>{{ $lead->vendor_name ?: 'N/A' }}</td>
                                    <td>
                                        @if($lead->query)
                                            <span class="status-badge badge-{{ strtolower(str_replace(' ', '-', $lead->query)) }}">{{ $lead->query }}</span>
                                        @else
                                            <span class="status-badge badge-default">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($lead->status)
                                            <span class="status-badge badge-{{ strtolower(str_replace(' ', '-', $lead->status)) }}">{{ $lead->status }}</span>
                                        @else
                                            <span class="status-badge badge-default">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $lead->ongoing_stopped ?? 'N/A' }}</td>
                                    <td>
                                        <button onclick="openLeadDetailsModal({{ $lead->id }}, 'operation')" class="btn action-btn view-btn" data-id="{{ $lead->id }}" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 40px; color: #6B7280;">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-bottom: 12px;">
                                            <path d="M20 6L9 17L4 12" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <div>No operation leads found</div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
                </div>
            </div>
            <!-- End Dashboard Content Wrapper -->
        </div>
    </div>

    <!-- Floating Refresh Button -->
    <button class="refresh-btn" id="refreshBtn" title="Refresh Dashboard">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M1 4V10H7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M23 20V14H17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10M23 14L18.36 18.36A9 9 0 0 1 3.51 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>

    <!-- Sales Leads Modal -->
    <div id="salesLeadsModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Sales Leads Statistics</h3>
                <button class="modal-close" onclick="closeModal('salesLeadsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="stats-tabs">
                    <button class="stats-tab active" onclick="switchStatsTab('sales', 'today')">Today</button>
                    <button class="stats-tab" onclick="switchStatsTab('sales', 'monthly')">Monthly</button>
                    <button class="stats-tab" onclick="switchStatsTab('sales', 'yearly')">Yearly</button>
                    <button class="stats-tab" onclick="switchStatsTab('sales', 'custom')">Custom Range</button>
                </div>

                <div class="period-selector" id="salesTodaySelector" style="display: flex; margin-bottom: 16px;">
                    <div class="date-filter-label">Today's Leads:</div>
                    <div style="color: #667eea; font-weight: 600; margin-left: 8px;">{{ date('d M Y') }}</div>
                    <div class="date-filter-label" style="margin-left: 20px;">Filter by Executive:</div>
                    <select id="salesRolesSelectToday" class="date-input" style="margin-left: 8px; min-width: 200px;" onchange="loadSalesLeadsData('today', null, null, null, this.value)">
                        <option value="">All Executives</option>
                        <option value="">Loading executives...</option>
                    </select>
                </div>

                <div class="period-selector" id="salesPeriodSelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Select Month:</div>
                    <select id="salesMonthSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadSalesLeadsData('monthly', null, null, this.value, document.getElementById('salesRolesSelectMonth') ? document.getElementById('salesRolesSelectMonth').value : '')">
                        <option value="">Loading months...</option>
                    </select>
                    <div class="date-filter-label" style="margin-left: 20px;">Filter by Executive:</div>
                    <select id="salesRolesSelectMonth" class="date-input" style="margin-left: 8px; min-width: 200px;" onchange="loadSalesLeadsData('monthly', null, null, document.getElementById('salesMonthSelect') ? document.getElementById('salesMonthSelect').value : '', this.value)">
                        <option value="">All Executives</option>
                        <option value="">Loading executives...</option>
                    </select>
                </div>

                <div class="period-selector" id="salesYearSelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Select Year:</div>
                    <select id="salesYearSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadSalesLeadsData('yearly', null, null, this.value, document.getElementById('salesRolesSelectYear') ? document.getElementById('salesRolesSelectYear').value : '')">
                        <option value="">Loading years...</option>
                    </select>
                    <div class="date-filter-label" style="margin-left: 20px;">Filter by Executive:</div>
                    <select id="salesRolesSelectYear" class="date-input" style="margin-left: 8px; min-width: 200px;" onchange="loadSalesLeadsData('yearly', null, null, document.getElementById('salesYearSelect') ? document.getElementById('salesYearSelect').value : '', this.value)">
                        <option value="">All Executives</option>
                        <option value="">Loading executives...</option>
                    </select>
                </div>

                <div class="date-filter-section" id="salesDateFilter" style="display: none;">
                    <div class="date-filter-label">Date Range:</div>
                    <div class="date-input-group">
                        <input type="date" id="salesStartDate" class="date-input" placeholder="Start Date" max="">
                        <span style="color: #6B7280;">to</span>
                        <input type="date" id="salesEndDate" class="date-input" placeholder="End Date" max="">
                        <button class="filter-btn" onclick="applySalesDateFilter()">Apply Filter</button>
                    </div>
                    <div class="date-filter-label" style="margin-left: 20px;">Filter by Executive:</div>
                    <select id="salesRolesSelectCustom" class="date-input" style="margin-left: 8px; min-width: 200px;" onchange="loadSalesLeadsData('custom', document.getElementById('salesStartDate') ? document.getElementById('salesStartDate').value : '', document.getElementById('salesEndDate') ? document.getElementById('salesEndDate').value : '', null, this.value)">
                        <option value="">All Executives</option>
                        <option value="">Loading executives...</option>
                    </select>
                </div>


                <div class="stats-summary" id="salesStatsSummary">
                    <div class="stats-card">
                        <div class="stats-card-title">Total Leads</div>
                        <div class="stats-card-value" id="salesTotalLeads">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Average Per Day</div>
                        <div class="stats-card-value" id="salesAvgPerDay">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Period</div>
                        <div class="stats-card-value" id="salesPeriod">-</div>
                    </div>
                </div>

                <div id="salesLeadsTableContainer">
                    <div style="text-align: center; padding: 40px;">
                        <div class="loading-spinner-modal"></div>
                        <p style="margin-top: 16px; color: #6B7280;">Loading leads data...</p>
                    </div>
                </div>
            </div>
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
                    <button class="stats-tab active" onclick="switchStatsTab('operation', 'today')">Today</button>
                    <button class="stats-tab" onclick="switchStatsTab('operation', 'monthly')">Monthly</button>
                    <button class="stats-tab" onclick="switchStatsTab('operation', 'yearly')">Yearly</button>
                    <button class="stats-tab" onclick="switchStatsTab('operation', 'custom')">Custom Range</button>
                </div>

                <div class="period-selector" id="operationTodaySelector" style="display: flex; margin-bottom: 16px;">
                    <div class="date-filter-label">Today's Leads:</div>
                    <div style="color: #667eea; font-weight: 600; margin-left: 8px;">{{ date('d M Y') }}</div>
                    <div class="date-filter-label" style="margin-left: 20px;">Filter by Executive:</div>
                    <select id="operationRolesSelectToday" class="date-input" style="margin-left: 8px; min-width: 200px;" onchange="loadOperationLeadsData('today', null, null, null, this.value)">
                        <option value="">All Executives</option>
                        <option value="">Loading executives...</option>
                    </select>
                </div>

                <div class="period-selector" id="operationPeriodSelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Select Month:</div>
                    <select id="operationMonthSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadOperationLeadsData('monthly', null, null, this.value, document.getElementById('operationRolesSelectMonth') ? document.getElementById('operationRolesSelectMonth').value : '')">
                        <option value="">Loading months...</option>
                    </select>
                    <div class="date-filter-label" style="margin-left: 20px;">Filter by Executive:</div>
                    <select id="operationRolesSelectMonth" class="date-input" style="margin-left: 8px; min-width: 200px;" onchange="loadOperationLeadsData('monthly', null, null, document.getElementById('operationMonthSelect') ? document.getElementById('operationMonthSelect').value : '', this.value)">
                        <option value="">All Executives</option>
                        <option value="">Loading executives...</option>
                    </select>
                </div>

                <div class="period-selector" id="operationYearSelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Select Year:</div>
                    <select id="operationYearSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadOperationLeadsData('yearly', null, null, this.value, document.getElementById('operationRolesSelectYear') ? document.getElementById('operationRolesSelectYear').value : '')">
                        <option value="">Loading years...</option>
                    </select>
                    <div class="date-filter-label" style="margin-left: 20px;">Filter by Executive:</div>
                    <select id="operationRolesSelectYear" class="date-input" style="margin-left: 8px; min-width: 200px;" onchange="loadOperationLeadsData('yearly', null, null, document.getElementById('operationYearSelect') ? document.getElementById('operationYearSelect').value : '', this.value)">
                        <option value="">All Executives</option>
                        <option value="">Loading executives...</option>
                    </select>
                </div>

                <div class="date-filter-section" id="operationDateFilter" style="display: none;">
                    <div class="date-filter-label">Date Range:</div>
                    <div class="date-input-group">
                        <input type="date" id="operationStartDate" class="date-input" placeholder="Start Date" max="">
                        <span style="color: #6B7280;">to</span>
                        <input type="date" id="operationEndDate" class="date-input" placeholder="End Date" max="">
                        <button class="filter-btn" onclick="applyOperationDateFilter()">Apply Filter</button>
                    </div>
                    <div class="date-filter-label" style="margin-left: 20px;">Filter by Executive:</div>
                    <select id="operationRolesSelectCustom" class="date-input" style="margin-left: 8px; min-width: 200px;" onchange="loadOperationLeadsData('custom', document.getElementById('operationStartDate') ? document.getElementById('operationStartDate').value : '', document.getElementById('operationEndDate') ? document.getElementById('operationEndDate').value : '', null, this.value)">
                        <option value="">All Executives</option>
                        <option value="">Loading executives...</option>
                    </select>
                </div>


                <div class="stats-summary" id="operationStatsSummary">
                    <div class="stats-card">
                        <div class="stats-card-title">Total Leads</div>
                        <div class="stats-card-value" id="operationTotalLeads">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Average Per Day</div>
                        <div class="stats-card-value" id="operationAvgPerDay">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Period</div>
                        <div class="stats-card-value" id="operationPeriod">-</div>
                    </div>
                </div>

                <div id="operationLeadsTableContainer">
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
                <h3 class="modal-title">System Users</h3>
                <button class="modal-close" onclick="closeModal('usersModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="stats-tabs">
                    <button class="stats-tab active" onclick="switchUsersTab('all')">All Users</button>
                    <button class="stats-tab" onclick="switchUsersTab('active')">Active Users</button>
                    <button class="stats-tab" onclick="switchUsersTab('inactive')">Inactive Users</button>
                </div>

                <div class="stats-summary" id="usersStatsSummary">
                    <div class="stats-card">
                        <div class="stats-card-title">Total Users</div>
                        <div class="stats-card-value" id="usersTotalCount">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Active Users</div>
                        <div class="stats-card-value" id="usersActiveCount">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Inactive Users</div>
                        <div class="stats-card-value" id="usersInactiveCount">-</div>
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

    <!-- User Details Modal -->
    <div id="userDetailsModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">User Details</h3>
                <button class="modal-close" onclick="closeModal('userDetailsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="userDetailsContent">
                    <div style="text-align: center; padding: 40px;">
                        <div class="loading-spinner-modal"></div>
                        <p style="margin-top: 16px; color: #6B7280;">Loading user details...</p>
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
                    <h5 class="mb-0">All Tasks</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" onclick="openAddTaskModal()">
                            <i class="fas fa-plus"></i> Add New Task
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="openTaskHistoryModal()">
                            <i class="fas fa-history"></i> Task History
                        </button>
                    </div>
                </div>

                <!-- Task Stats Summary -->
                <div class="stats-summary mb-4">
                    <div class="stats-card">
                        <div class="stats-card-title">Total Tasks</div>
                        <div class="stats-card-value">{{ number_format($totalTasksCount) }}</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Pending</div>
                        <div class="stats-card-value">{{ number_format($pendingTasksCount) }}</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Completed</div>
                        <div class="stats-card-value">{{ number_format($completedTasksCount) }}</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Overdue</div>
                        <div class="stats-card-value">{{ number_format($overdueTasksCount) }}</div>
                    </div>
                </div>

                <!-- Task Tabs -->
                <div class="stats-tabs">
                    <button class="stats-tab active" onclick="switchTasksTab('all')">All Tasks</button>
                    <button class="stats-tab" onclick="switchTasksTab('today')">Today's Tasks</button>
                    <button class="stats-tab" onclick="switchTasksTab('pending')">Pending Tasks</button>
                    <button class="stats-tab" onclick="switchTasksTab('completed')">Completed Tasks</button>
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
                        <label for="taskAssignedTo" class="form-label">Assign To</label>
                        <select class="form-control" id="taskAssignedTo" name="assigned_to" required>
                            <option value="">Select User</option>
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
                        <label for="editTaskAssignedTo" class="form-label">Assign To</label>
                        <select class="form-control" id="editTaskAssignedTo" name="assigned_to" required>
                            <option value="">Select User</option>
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

    <!-- Task History Modal -->
    <div id="taskHistoryModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Task History</h3>
                <button class="modal-close" onclick="closeModal('taskHistoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Task History Tabs -->
                <div class="stats-tabs">
                    <button class="stats-tab active" onclick="switchTaskHistoryTab('all')">All Tasks</button>
                    <button class="stats-tab" onclick="switchTaskHistoryTab('assigned_by_me')">Assigned by Me</button>
                    <button class="stats-tab" onclick="switchTaskHistoryTab('assigned_to_me')">Assigned to Me</button>
                    <button class="stats-tab" onclick="switchTaskHistoryTab('completed')">Completed Tasks</button>
                </div>

                <!-- Date Filter -->
                <div class="period-selector active" id="taskHistoryDateFilter">
                    <div class="date-filter-label">Filter by Date:</div>
                    <input type="date" id="taskHistoryStartDate" class="date-input" placeholder="From Date">
                    <span style="color: #6B7280;">to</span>
                    <input type="date" id="taskHistoryEndDate" class="date-input" placeholder="To Date">
                    <button class="filter-btn" onclick="applyTaskHistoryDateFilter()">Apply Filter</button>
                    <button class="filter-btn" onclick="clearTaskHistoryDateFilter()" style="background: #6b7280;">Clear</button>
                </div>

                <!-- Task History Table -->
                <div id="taskHistoryTableContainer">
                    <div style="text-align: center; padding: 40px;">
                        <div class="loading-spinner-modal"></div>
                        <p style="margin-top: 16px; color: #6B7280;">Loading task history...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Follow-up Calendar Modal -->
    <div id="adminCalendarModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-calendar-alt"></i>
                    Follow-up Calendar
                </h3>
                <button class="modal-close" onclick="closeModal('adminCalendarModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Calendar Navigation -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 16px; background: #f8fafc; border-radius: 8px;">
                    <button id="prevMonthAdmin" class="btn btn-outline-primary" style="padding: 8px 16px; border-radius: 6px;">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <h4 id="currentMonthAdmin" style="margin: 0; color: #1F2937; font-weight: 600;"></h4>
                    <button id="nextMonthAdmin" class="btn btn-outline-primary" style="padding: 8px 16px; border-radius: 6px;">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <!-- Calendar Grid -->
                <div id="adminCalendarGrid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #e5e7eb; border-radius: 8px; overflow: hidden;">
                    <!-- Calendar will be rendered here -->
                </div>

                <!-- Legend -->
                <div style="display: flex; justify-content: center; gap: 20px; margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="font-size: 10px; font-weight: 600; color: white; background: #f39c12; border-radius: 8px; padding: 2px 6px; min-width: 20px; text-align: center;">F</div>
                        <span style="font-size: 12px; color: #374151;">Sales Follow-up</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="font-size: 10px; font-weight: 600; color: white; background: #9b59b6; border-radius: 8px; padding: 2px 6px; min-width: 20px; text-align: center;">P</div>
                        <span style="font-size: 12px; color: #374151;">Sales Future Prospect</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="font-size: 10px; font-weight: 600; color: white; background: #22c55e; border-radius: 8px; padding: 2px 6px; min-width: 20px; text-align: center;">O</div>
                        <span style="font-size: 12px; color: #374151;">Operation Follow-up</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Calendar Leads Modal -->
    <div id="adminCalendarLeadsModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-list"></i>
                    Leads for <span id="selectedDateAdmin"></span>
                </h3>
                <button class="modal-close" onclick="closeModal('adminCalendarLeadsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="adminCalendarLeadsContent">
                    <div style="text-align: center; padding: 40px;">
                        <div class="loading-spinner-modal"></div>
                        <p style="margin-top: 16px; color: #6B7280;">Loading leads...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Details Modal -->
    <div id="leadDetailsModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user"></i>
                    Lead Details
                </h3>
                <button class="modal-close" onclick="closeModal('leadDetailsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="leadDetailsContent">
                    <div style="text-align: center; padding: 40px;">
                        <div class="loading-spinner-modal"></div>
                        <p style="margin-top: 16px; color: #6B7280;">Loading lead details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Modal -->
    <div id="revenueModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 1200px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-chart-line"></i>
                    Revenue Analytics
                </h3>
                <button class="modal-close" onclick="closeModal('revenueModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Revenue Tabs -->
                <div class="stats-tabs">
                    <button class="stats-tab active" onclick="switchRevenueTab('today')">Today</button>
                    <button class="stats-tab" onclick="switchRevenueTab('monthly')">Monthly</button>
                    <button class="stats-tab" onclick="switchRevenueTab('yearly')">Yearly</button>
                    <button class="stats-tab" onclick="switchRevenueTab('custom')">Custom Range</button>
                </div>

                <!-- Period Selectors -->
                <div class="period-selector active" id="revenueTodaySelector" style="display: flex; margin-bottom: 16px;">
                    <div class="date-filter-label">Today's Revenue:</div>
                    <div style="color: #667eea; font-weight: 600; margin-left: 8px;">{{ date('d M Y') }}</div>
                </div>

                <div class="period-selector" id="revenueMonthlySelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Select Month:</div>
                    <select id="revenueMonthSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadRevenueData('monthly', null, null, this.value)">
                        <option value="">Loading months...</option>
                    </select>
                </div>

                <div class="period-selector" id="revenueYearlySelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Select Year:</div>
                    <select id="revenueYearSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadRevenueData('yearly', null, null, this.value)">
                        <option value="">Loading years...</option>
                    </select>
                </div>

                <div class="period-selector" id="revenueCustomSelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Date Range:</div>
                    <input type="date" id="revenueStartDate" class="date-input" style="margin-left: 8px; min-width: 150px;" placeholder="Start Date" max="">
                    <span style="color: #6B7280; margin: 0 8px;">to</span>
                    <input type="date" id="revenueEndDate" class="date-input" style="min-width: 150px;" placeholder="End Date" max="">
                    <button class="filter-btn" style="margin-left: 8px;" onclick="applyRevenueDateFilter()">Apply Filter</button>
                </div>

                <!-- Revenue Summary Cards -->
                <div class="stats-summary" id="revenueSummary">
                    <div class="stats-card">
                        <div class="stats-card-title">Total Received</div>
                        <div class="stats-card-value" id="totalReceived" style="color: #059669;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Vendor Payments</div>
                        <div class="stats-card-value" id="vendorPayments" style="color: #dc2626;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Net Revenue</div>
                        <div class="stats-card-value" id="netRevenue" style="color: #667eea;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Profit Margin</div>
                        <div class="stats-card-value" id="profitMargin" style="color: #f59e0b;">-</div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="revenue-chart-container" style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <h5 style="margin-bottom: 20px; color: #1F2937;">Revenue Breakdown</h5>
                    <div id="revenueChart" style="height: 300px; position: relative;">
                        <div style="text-align: center; padding: 40px;">
                            <div class="loading-spinner-modal"></div>
                            <p style="margin-top: 16px; color: #6B7280;">Loading revenue data...</p>
                        </div>
                    </div>
                </div>

                <!-- Revenue Details Table -->
                <div class="revenue-details-container" style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <h5 style="margin-bottom: 20px; color: #1F2937;">Revenue Details</h5>
                    <div id="revenueDetailsTable">
                        <div style="text-align: center; padding: 40px;">
                            <div class="loading-spinner-modal"></div>
                            <p style="margin-top: 16px; color: #6B7280;">Loading revenue details...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Deployments Modal -->
    <div id="pendingDeploymentsModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 1200px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-clock"></i>
                    Pending Deployments
                </h3>
                <button class="modal-close" onclick="closeModal('pendingDeploymentsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Pending Deployments Summary -->
                <div class="stats-summary">
                    <div class="stats-card">
                        <div class="stats-card-title">Total Pending</div>
                        <div class="stats-card-value" style="color: #f59e0b;">{{ number_format($pendingDeploymentsCount) }}</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Today's Pending</div>
                        <div class="stats-card-value" id="todayPendingDeployments" style="color: #dc2626;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">This Week</div>
                        <div class="stats-card-value" id="weekPendingDeployments" style="color: #667eea;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Overdue</div>
                        <div class="stats-card-value" id="overdueDeployments" style="color: #dc2626;">-</div>
                    </div>
                </div>

                <!-- Pending Deployments Table -->
                <div class="pending-deployments-container" style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <h5 style="margin-bottom: 20px; color: #1F2937;">Pending Deployment Details</h5>
                    <div id="pendingDeploymentsTable">
                        <div style="text-align: center; padding: 40px;">
                            <div class="loading-spinner-modal"></div>
                            <p style="margin-top: 16px; color: #6B7280;">Loading pending deployments...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding Amount Modal -->
    <div id="outstandingAmountModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 1200px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Outstanding Amount Analytics
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
                    <div style="color: #667eea; font-weight: 600; margin-left: 8px;">All Time</div>
                </div>

                <div class="period-selector" id="outstandingTodaySelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Today's Outstanding:</div>
                    <div style="color: #667eea; font-weight: 600; margin-left: 8px;">{{ date('d M Y') }}</div>
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
                        <div class="stats-card-value" id="positiveOutstanding" style="color: #dc2626;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Negative Outstanding</div>
                        <div class="stats-card-value" id="negativeOutstanding" style="color: #059669;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Total Records</div>
                        <div class="stats-card-value" id="outstandingCount" style="color: #667eea;">-</div>
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

    <!-- Profile Pending Modal -->
    <div id="profilePendingModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 1200px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-clock"></i>
                    Profile Pending Leads
                </h3>
                <button class="modal-close" onclick="closeModal('profilePendingModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Profile Pending Tabs -->
                <div class="stats-tabs">
                    <button class="stats-tab active" onclick="switchProfilePendingTab('all')">All Time</button>
                    <button class="stats-tab" onclick="switchProfilePendingTab('today')">Today</button>
                    <button class="stats-tab" onclick="switchProfilePendingTab('monthly')">Monthly</button>
                    <button class="stats-tab" onclick="switchProfilePendingTab('yearly')">Yearly</button>
                    <button class="stats-tab" onclick="switchProfilePendingTab('custom')">Custom Range</button>
                </div>

                <!-- Period Selectors -->
                <div class="period-selector active" id="profilePendingAllSelector" style="display: flex; margin-bottom: 16px;">
                    <div class="date-filter-label">All Profile Pending Leads:</div>
                    <div style="color: #fd7e14; font-weight: 600; margin-left: 8px;">All Time</div>
                </div>

                <div class="period-selector" id="profilePendingTodaySelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Today's Profile Pending:</div>
                    <div style="color: #fd7e14; font-weight: 600; margin-left: 8px;">{{ date('d M Y') }}</div>
                </div>

                <div class="period-selector" id="profilePendingMonthlySelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Select Month:</div>
                    <select id="profilePendingMonthSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadProfilePendingData('monthly', null, null, this.value)">
                        <option value="">Loading months...</option>
                    </select>
                </div>

                <div class="period-selector" id="profilePendingYearlySelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Select Year:</div>
                    <select id="profilePendingYearSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadProfilePendingData('yearly', null, null, this.value)">
                        <option value="">Loading years...</option>
                    </select>
                </div>

                <div class="period-selector" id="profilePendingCustomSelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Date Range:</div>
                    <input type="date" id="profilePendingStartDate" class="date-input" style="margin-left: 8px; min-width: 150px;" placeholder="Start Date" max="">
                    <span style="color: #6B7280; margin: 0 8px;">to</span>
                    <input type="date" id="profilePendingEndDate" class="date-input" style="min-width: 150px;" placeholder="End Date" max="">
                    <button class="filter-btn" style="margin-left: 8px;" onclick="applyProfilePendingDateFilter()">Apply Filter</button>
                </div>

                <!-- Profile Pending Summary Cards -->
                <div class="stats-summary" id="profilePendingSummary">
                    <div class="stats-card">
                        <div class="stats-card-title">Total Profile Pending</div>
                        <div class="stats-card-value" id="totalProfilePending" style="color: #fd7e14;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Today's Pending</div>
                        <div class="stats-card-value" id="todayProfilePending" style="color: #dc2626;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">This Month</div>
                        <div class="stats-card-value" id="monthProfilePending" style="color: #667eea;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">This Year</div>
                        <div class="stats-card-value" id="yearProfilePending" style="color: #059669;">-</div>
                    </div>
                </div>

                <!-- Profile Pending Details Table -->
                <div class="profile-pending-details-container" style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <h5 style="margin-bottom: 20px; color: #1F2937;">Profile Pending Leads Details</h5>
                    <div id="profilePendingDetailsTable">
                        <div style="text-align: center; padding: 40px;">
                            <div class="loading-spinner-modal"></div>
                            <p style="margin-top: 16px; color: #6B7280;">Loading profile pending leads...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Payment Modal -->
    <div id="vendorPaymentModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 1200px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-rupee-sign"></i>
                    Vendor Payment Details
                </h3>
                <button class="modal-close" onclick="closeModal('vendorPaymentModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Vendor Payment Tabs -->
                <div class="stats-tabs">
                    <button class="stats-tab active" onclick="switchVendorPaymentTab('all')">All Time</button>
                    <button class="stats-tab" onclick="switchVendorPaymentTab('today')">Today</button>
                    <button class="stats-tab" onclick="switchVendorPaymentTab('monthly')">Monthly</button>
                    <button class="stats-tab" onclick="switchVendorPaymentTab('yearly')">Yearly</button>
                    <button class="stats-tab" onclick="switchVendorPaymentTab('custom')">Custom Range</button>
                </div>

                <!-- Period Selectors -->
                <div class="period-selector active" id="vendorPaymentAllSelector" style="display: flex; margin-bottom: 16px;">
                    <div class="date-filter-label">All Vendor Payments:</div>
                    <div style="color: #8b5cf6; font-weight: 600; margin-left: 8px;">All Time</div>
                </div>

                <div class="period-selector" id="vendorPaymentTodaySelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Today's Vendor Payments:</div>
                    <div style="color: #8b5cf6; font-weight: 600; margin-left: 8px;">{{ date('d M Y') }}</div>
                </div>

                <div class="period-selector" id="vendorPaymentMonthlySelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Select Month:</div>
                    <select id="vendorPaymentMonthSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadVendorPaymentData('monthly', null, null, this.value)">
                        <option value="">Loading months...</option>
                    </select>
                </div>

                <div class="period-selector" id="vendorPaymentYearlySelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Select Year:</div>
                    <select id="vendorPaymentYearSelect" class="date-input" style="margin-left: 8px; min-width: 150px;" onchange="loadVendorPaymentData('yearly', null, null, this.value)">
                        <option value="">Loading years...</option>
                    </select>
                </div>

                <div class="period-selector" id="vendorPaymentCustomSelector" style="display: none; margin-bottom: 16px;">
                    <div class="date-filter-label">Date Range:</div>
                    <input type="date" id="vendorPaymentStartDate" class="date-input" style="margin-left: 8px; min-width: 150px;" placeholder="Start Date" max="">
                    <span style="color: #6B7280; margin: 0 8px;">to</span>
                    <input type="date" id="vendorPaymentEndDate" class="date-input" style="min-width: 150px;" placeholder="End Date" max="">
                    <button class="filter-btn" style="margin-left: 8px;" onclick="applyVendorPaymentDateFilter()">Apply Filter</button>
                </div>

                <!-- Vendor Payment Summary Cards -->
                <div class="stats-summary" id="vendorPaymentSummary">
                    <div class="stats-card">
                        <div class="stats-card-title">Total Earned</div>
                        <div class="stats-card-value" id="totalEarned" style="color: #059669;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Payments Made</div>
                        <div class="stats-card-value" id="totalPaymentsMade" style="color: #dc2626;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Remaining Amount</div>
                        <div class="stats-card-value" id="totalRemaining" style="color: #8b5cf6;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Total Vendors</div>
                        <div class="stats-card-value" id="totalVendors" style="color: #667eea;">-</div>
                    </div>
                </div>

                <!-- Vendor Payment Details Table -->
                <div class="vendor-payment-details-container" style="background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <h5 style="margin-bottom: 20px; color: #1F2937;">Vendor Payment Details</h5>
                    <div id="vendorPaymentDetailsTable">
                        <div style="text-align: center; padding: 40px;">
                            <div class="loading-spinner-modal"></div>
                            <p style="margin-top: 16px; color: #6B7280;">Loading vendor payment details...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Unverified Deployment Payments Modal -->
    <div id="unverifiedDeploymentModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 1400px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-exclamation-circle"></i>
                    Unverified Deployment Payments
                </h3>
                <button class="modal-close" onclick="closeModal('unverifiedDeploymentModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Summary Cards -->
                <div class="stats-summary" id="unverifiedDeploymentStatsSummary">
                    <div class="stats-card">
                        <div class="stats-card-title">Total Unverified</div>
                        <div class="stats-card-value" id="totalUnverified" style="color: #dc2626;">-</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-card-title">Total Amount</div>
                        <div class="stats-card-value" id="totalUnverifiedAmount" style="color: #dc2626;">-</div>
                    </div>
                </div>

                <!-- Unverified Deployment Payments Table -->
                <div id="unverifiedDeploymentTableContainer">
                    <div style="text-align: center; padding: 40px;">
                        <div class="loading-spinner-modal"></div>
                        <p style="margin-top: 16px; color: #6B7280;">Loading unverified deployment payments...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Lead Modal (for Pending Callbacks - Sales Lead) -->
    <div id="editLeadModalDashboard" class="modal-overlay">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa fa-pen-to-square me-2"></i> Edit Lead</h3>
                <button class="modal-close" onclick="closeModal('editLeadModalDashboard')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editLeadFormDashboard">
                    <input type="hidden" id="edit_lead_id_dashboard" name="id">
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="edit_executive_dashboard" class="form-label">Executive</label>
                            <select class="form-control" id="edit_executive_dashboard" name="executive" required>
                                <option value="">Select Executive</option>
                                @foreach(($executives ?? collect()) as $executive)
                                    <option value="{{ $executive->id }}">{{ $executive->f_name }} {{ $executive->l_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_customer_name_dashboard" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="edit_customer_name_dashboard" name="customer_name" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="edit_patient_name_dashboard" class="form-label">Patient Name</label>
                            <input type="text" class="form-control" id="edit_patient_name_dashboard" name="patient_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_patient_gender_dashboard" class="form-label">Patient Gender</label>
                            <select class="form-control" id="edit_patient_gender_dashboard" name="patient_gender">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="edit_age_dashboard" class="form-label">Age</label>
                            <input type="number" class="form-control" id="edit_age_dashboard" name="age" min="0" max="150">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_contact_type_dashboard" class="form-label">Contact Type</label>
                            <select class="form-control" id="edit_contact_type_dashboard" name="contact_type" required>
                                <option value="">Select</option>
                                <option value="call">Call</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="email">Email</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="edit_contact_no_dashboard" class="form-label">Contact No</label>
                            <input type="text" class="form-control" id="edit_contact_no_dashboard" name="contact_no" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_lead_source_dashboard" class="form-label">Lead Source</label>
                            <select class="form-control" id="edit_lead_source_dashboard" name="lead_source" required>
                                <option value="">Select</option>
                                <option value="web">Web</option>
                                <option value="ivrs">IVRS</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="edit_location_dashboard" class="form-label">Location</label>
                            <select class="form-control location-select" id="edit_location_dashboard" name="location" required>
                                <option value="">Select Location</option>
                                @foreach(($locations ?? collect()) as $location)
                                    <option value="{{ $location->name }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_query_dashboard" class="form-label">Query</label>
                            <select class="form-control" id="edit_query_dashboard" name="query" required>
                                <option value="">Select Query</option>
                                @foreach(($services ?? collect()) as $service)
                                    <option value="{{ $service->name }}">{{ $service->name }}</option>
                                @endforeach
                                <option value="job request">Job Request</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3" id="edit_query_remarks_group_dashboard" style="display: none;">
                            <label for="edit_query_remarks_dashboard" class="form-label">Query Remarks</label>
                            <textarea class="form-control" id="edit_query_remarks_dashboard" name="query_remarks" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status_dashboard" class="form-label">Status</label>
                            <select class="form-control" id="edit_status_dashboard" name="status" required>
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
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="edit_stage_dashboard" class="form-label">Stage</label>
                            <select class="form-control" id="edit_stage_dashboard" name="stage" required>
                                <option value="">Select Stage</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="closed">Closed</option>
                                <option value="profile required">Profile Required</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_shift_type_dashboard" class="form-label">Shift Type</label>
                            <select class="form-control" id="edit_shift_type_dashboard" name="shift_type">
                                <option value="">Select</option>
                                <option value="12hr">12hr</option>
                                <option value="24hr">24hr</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3" id="edit_future_prospect_date_group_dashboard" style="display: none;">
                            <label for="edit_future_prospect_date_dashboard" class="form-label">Future Contact Date</label>
                            <input type="date" class="form-control" id="edit_future_prospect_date_dashboard" name="future_prospect_date">
                        </div>
                        <div class="col-md-6 mb-3" id="edit_prospect_close_rate_group_dashboard" style="display: none;">
                            <label for="edit_prospect_rate_dashboard" class="form-label">Rate (₹)</label>
                            <input type="number" class="form-control" id="edit_prospect_rate_dashboard" name="prospect_rate" min="0" step="0.01">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="padding: 20px 24px; border-top: 1px solid #e5e7eb;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editLeadModalDashboard')">Close</button>
                <button type="button" class="btn btn-primary" id="updateLeadDashboard"><i class="fas fa-save me-1"></i> Update Lead</button>
            </div>
        </div>
    </div>

    <!-- Pending Callbacks Modal -->
    <div id="pendingCallbacksModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 1400px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-phone-slash"></i>
                    Pending Callbacks (Missed/Busy/Failed Calls)
                </h3>
                <button class="modal-close" onclick="closeModal('pendingCallbacksModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Filter Tabs -->
                <div class="filter-tabs" style="margin-bottom: 20px;">
                    <button class="filter-tab active" onclick="filterPendingCallbacksByType('all')" id="filterTabAll" data-filter="all">
                        <i class="fas fa-list"></i> All
                    </button>
                    <button class="filter-tab" onclick="filterPendingCallbacksByType('lead')" id="filterTabLead" data-filter="lead">
                        <i class="fas fa-user"></i> Lead
                    </button>
                    <button class="filter-tab" onclick="filterPendingCallbacksByType('operation_lead')" id="filterTabOperationLead" data-filter="operation_lead">
                        <i class="fas fa-briefcase"></i> Operation Lead
                    </button>
                    <button class="filter-tab" onclick="filterPendingCallbacksByType('job_request')" id="filterTabJobRequest" data-filter="job_request">
                        <i class="fas fa-tasks"></i> Job Request
                    </button>
                </div>

                <!-- Summary Cards -->
                <div class="stats-summary" id="pendingCallbacksStatsSummary">
                    <div class="stats-card">
                        <div class="stats-card-title">Total Pending Callbacks</div>
                        <div class="stats-card-value" id="totalPendingCallbacks" style="color: #f97316;">-</div>
                    </div>
                </div>

                <!-- Pending Callbacks Table -->
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
        <div class="modal-content" style="max-width: 1600px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-phone"></i>
                    Recent Calls (Past Week)
                </h3>
                <button class="modal-close" onclick="closeModal('recentCallsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Tabs for Sales and Operation -->
                <div class="calls-tabs">
                    <div class="calls-tab active" onclick="switchCallsTab('sales')">
                        Sales Calls
                    </div>
                    <div class="calls-tab" onclick="switchCallsTab('operation')">
                        Operation Calls
                    </div>
                </div>

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
                        <input type="text" id="recentCallsSearchInput" class="form-control" placeholder="Search by phone number, customer name, executive name, or lead number..." onkeyup="filterRecentCalls()">
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

    {{-- footer code --}}
@section('footer-script')
    <script>
        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');

            // Add active class to clicked button
            event.target.classList.add('active');
        }

        // Modal functions
        function openSalesLeadsModal() {
            document.getElementById('salesLeadsModal').style.display = 'flex';
            setupDateInputs('sales');
            populateMonthYearDropdowns('sales');
            populateSalesRolesDropdown();
            loadSalesLeadsData('today');
        }

        function openOperationLeadsModal() {
            document.getElementById('operationLeadsModal').style.display = 'flex';
            setupDateInputs('operation');
            populateMonthYearDropdowns('operation');
            populateOperationRolesDropdown();
            loadOperationLeadsData('today');
        }

        function openUsersModal() {
            document.getElementById('usersModal').style.display = 'flex';
            loadUsersData('all');
        }

        function openTasksModal() {
            document.getElementById('tasksModal').style.display = 'flex';
            loadTasksData();
        }

        // Populate sales roles dropdown
        function populateSalesRolesDropdown() {
            const dropdownIds = ['salesRolesSelectToday', 'salesRolesSelectMonth', 'salesRolesSelectYear', 'salesRolesSelectCustom'];

            dropdownIds.forEach(id => {
                const rolesSelect = document.getElementById(id);
                if (rolesSelect) {
                    rolesSelect.innerHTML = '<option value="">Loading executives...</option>';
                }
            });

            fetch('/subadmin/dashboard/sales-executives')
                .then(response => response.json())
                .then(data => {
                    dropdownIds.forEach(id => {
                        const rolesSelect = document.getElementById(id);
                        if (rolesSelect) {
                            rolesSelect.innerHTML = '<option value="">All Executives</option>';
                            data.executives.forEach(executive => {
                                const option = document.createElement('option');
                                option.value = executive;
                                option.textContent = executive;
                                rolesSelect.appendChild(option);
                            });
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading sales executives:', error);
                    dropdownIds.forEach(id => {
                        const rolesSelect = document.getElementById(id);
                        if (rolesSelect) {
                            rolesSelect.innerHTML = '<option value="">All Executives</option>';
                        }
                    });
                });
        }

        // Populate operation roles dropdown
        function populateOperationRolesDropdown() {
            const dropdownIds = ['operationRolesSelectToday', 'operationRolesSelectMonth', 'operationRolesSelectYear', 'operationRolesSelectCustom'];

            dropdownIds.forEach(id => {
                const rolesSelect = document.getElementById(id);
                if (rolesSelect) {
                    rolesSelect.innerHTML = '<option value="">Loading executives...</option>';
                }
            });

            fetch('/subadmin/dashboard/operation-executives')
                .then(response => response.json())
                .then(data => {
                    dropdownIds.forEach(id => {
                        const rolesSelect = document.getElementById(id);
                        if (rolesSelect) {
                            rolesSelect.innerHTML = '<option value="">All Executives</option>';
                            data.executives.forEach(executive => {
                                const option = document.createElement('option');
                                option.value = executive;
                                option.textContent = executive;
                                rolesSelect.appendChild(option);
                            });
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading operation executives:', error);
                    dropdownIds.forEach(id => {
                        const rolesSelect = document.getElementById(id);
                        if (rolesSelect) {
                            rolesSelect.innerHTML = '<option value="">All Executives</option>';
                        }
                    });
                });
        }

        // Populate month and year dropdowns
        function populateMonthYearDropdowns(type) {
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth();

            // Populate months (last 12 months)
            const monthSelect = document.getElementById(type === 'sales' ? 'salesMonthSelect' : 'operationMonthSelect');
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
            const yearSelect = document.getElementById(type === 'sales' ? 'salesYearSelect' : 'operationYearSelect');
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
        function setupDateInputs(type) {
            const today = new Date().toISOString().split('T')[0];

            if (type === 'sales') {
                const startDateInput = document.getElementById('salesStartDate');
                const endDateInput = document.getElementById('salesEndDate');

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
            } else {
                const startDateInput = document.getElementById('operationStartDate');
                const endDateInput = document.getElementById('operationEndDate');

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
        }

        // Enhanced close modal function
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                console.log('Modal closed:', modalId);
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        });

        // Stats tab switching
        function switchStatsTab(type, period) {
            // Update active tab
            const modalId = type === 'sales' ? 'salesLeadsModal' : 'operationLeadsModal';
            const modal = document.getElementById(modalId);
            const tabs = modal.querySelectorAll('.stats-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            // Show/hide appropriate selectors
            const todaySelectorId = type === 'sales' ? 'salesTodaySelector' : 'operationTodaySelector';
            const periodSelectorId = type === 'sales' ? 'salesPeriodSelector' : 'operationPeriodSelector';
            const yearSelectorId = type === 'sales' ? 'salesYearSelector' : 'operationYearSelector';
            const dateFilterId = type === 'sales' ? 'salesDateFilter' : 'operationDateFilter';

            const todaySelector = document.getElementById(todaySelectorId);
            const periodSelector = document.getElementById(periodSelectorId);
            const yearSelector = document.getElementById(yearSelectorId);
            const dateFilter = document.getElementById(dateFilterId);

            // Hide all selectors first
            todaySelector.style.display = 'none';
            periodSelector.style.display = 'none';
            yearSelector.style.display = 'none';
            dateFilter.style.display = 'none';

            // Show appropriate selector based on period
            if (period === 'today') {
                todaySelector.style.display = 'flex';
                // Load today's data
                if (type === 'sales') {
                    loadSalesLeadsData('today');
                } else {
                    loadOperationLeadsData('today');
                }
            } else if (period === 'monthly') {
                periodSelector.style.display = 'flex';
                // Load data with selected month
                const monthSelect = document.getElementById(type === 'sales' ? 'salesMonthSelect' : 'operationMonthSelect');
                if (type === 'sales') {
                    loadSalesLeadsData('monthly', null, null, monthSelect.value);
                } else {
                    loadOperationLeadsData('monthly', null, null, monthSelect.value);
                }
            } else if (period === 'yearly') {
                yearSelector.style.display = 'flex';
                // Load data with selected year
                const yearSelect = document.getElementById(type === 'sales' ? 'salesYearSelect' : 'operationYearSelect');
                if (type === 'sales') {
                    loadSalesLeadsData('yearly', null, null, yearSelect.value);
                } else {
                    loadOperationLeadsData('yearly', null, null, yearSelect.value);
                }
            } else if (period === 'custom') {
                dateFilter.style.display = 'flex';
                // Add visual indicator
                dateFilter.style.borderColor = '#667eea';
                dateFilter.style.boxShadow = '0 4px 8px rgba(102, 126, 234, 0.1)';
            }
        }



        // Apply sales date filter
        function applySalesDateFilter() {
            const startDate = document.getElementById('salesStartDate').value;
            const endDate = document.getElementById('salesEndDate').value;
            const executive = document.getElementById('salesRolesSelectCustom') ? document.getElementById('salesRolesSelectCustom').value : '';

            if (!startDate || !endDate) {
                alert('Please select both start and end dates.');
                return;
            }

            if (startDate > endDate) {
                alert('Start date cannot be after end date.');
                return;
            }

            // Clear any active quick date buttons
            const dateFilter = document.getElementById('salesDateFilter');
            const quickButtons = dateFilter.querySelectorAll('.quick-date-btn');
            quickButtons.forEach(btn => btn.classList.remove('active'));

            // Show loading state on button
            const filterBtn = document.querySelector('#salesDateFilter .filter-btn');
            const originalText = filterBtn.textContent;
            filterBtn.textContent = 'Loading...';
            filterBtn.disabled = true;

            loadSalesLeadsData('custom', startDate, endDate, null, executive);

            // Reset button after a short delay
            setTimeout(() => {
                filterBtn.textContent = originalText;
                filterBtn.disabled = false;
            }, 1000);
        }

        // Apply operation date filter
        function applyOperationDateFilter() {
            const startDate = document.getElementById('operationStartDate').value;
            const endDate = document.getElementById('operationEndDate').value;
            const executive = document.getElementById('operationRolesSelectCustom') ? document.getElementById('operationRolesSelectCustom').value : '';

            if (!startDate || !endDate) {
                alert('Please select both start and end dates.');
                return;
            }

            if (startDate > endDate) {
                alert('Start date cannot be after end date.');
                return;
            }

            // Clear any active quick date buttons
            const dateFilter = document.getElementById('operationDateFilter');
            const quickButtons = dateFilter.querySelectorAll('.quick-date-btn');
            quickButtons.forEach(btn => btn.classList.remove('active'));

            // Show loading state on button
            const filterBtn = document.querySelector('#operationDateFilter .filter-btn');
            const originalText = filterBtn.textContent;
            filterBtn.textContent = 'Loading...';
            filterBtn.disabled = true;

            loadOperationLeadsData('custom', startDate, endDate, null, executive);

            // Reset button after a short delay
            setTimeout(() => {
                filterBtn.textContent = originalText;
                filterBtn.disabled = false;
            }, 1000);
        }

        // Load sales leads data
        function loadSalesLeadsData(period, startDate = null, endDate = null, selectedPeriod = null, executive = null) {
            const container = document.getElementById('salesLeadsTableContainer');
            container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading leads data...</p></div>';

            let url = `/subadmin/dashboard/sales-leads-stats?period=${period}`;
            if (period === 'custom' && startDate && endDate) {
                url += `&start_date=${startDate}&end_date=${endDate}`;
            } else if (period === 'monthly' && selectedPeriod) {
                url += `&selected_month=${selectedPeriod}`;
            } else if (period === 'yearly' && selectedPeriod) {
                url += `&selected_year=${selectedPeriod}`;
            }

            // Add executive filter if provided
            if (executive) {
                url += `&executive=${encodeURIComponent(executive)}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    updateSalesLeadsDisplay(data, period);
                })
                .catch(error => {
                    console.error('Error loading sales leads data:', error);
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
                });
        }

        // Load operation leads data
        function loadOperationLeadsData(period, startDate = null, endDate = null, selectedPeriod = null, executive = null) {
            const container = document.getElementById('operationLeadsTableContainer');
            container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading leads data...</p></div>';

            let url = `/subadmin/dashboard/operation-leads-stats?period=${period}`;
            if (period === 'custom' && startDate && endDate) {
                url += `&start_date=${startDate}&end_date=${endDate}`;
            } else if (period === 'monthly' && selectedPeriod) {
                url += `&selected_month=${selectedPeriod}`;
            } else if (period === 'yearly' && selectedPeriod) {
                url += `&selected_year=${selectedPeriod}`;
            }

            // Add executive filter if provided
            if (executive) {
                url += `&executive=${encodeURIComponent(executive)}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    updateOperationLeadsDisplay(data, period);
                })
                .catch(error => {
                    console.error('Error loading operation leads data:', error);
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data. Please try again.</div>';
                });
        }

        // Update sales leads display
        function updateSalesLeadsDisplay(data, period) {
            // Update summary cards
            const totalLeads = data.leads.length;
            const avgPerDay = data.stats.length > 0 ? (totalLeads / data.stats.length).toFixed(1) : 0;

            document.getElementById('salesTotalLeads').textContent = totalLeads;
            document.getElementById('salesAvgPerDay').textContent = avgPerDay;

            // Update period display
            if (period === 'custom') {
                const startDate = new Date(data.startDate).toLocaleDateString('en-GB');
                const endDate = new Date(data.endDate).toLocaleDateString('en-GB');
                document.getElementById('salesPeriod').textContent = `${startDate} - ${endDate}`;
            } else {
                document.getElementById('salesPeriod').textContent = period.charAt(0).toUpperCase() + period.slice(1);
            }

            // Generate table
            const container = document.getElementById('salesLeadsTableContainer');
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
                            <th>Source</th>
                            <th>Query</th>
                            <th>Status</th>
                            <th>Stage</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            data.leads.forEach(lead => {
                const date = lead.date ? new Date(lead.date).toLocaleDateString('en-GB') + ' ' + new Date(lead.date).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) : 'N/A';
                const contact = lead.contact_no ? `
                    <div class="contact-info">
                        <span class="contact-number">${lead.contact_no}</span>
                        <div class="contact-actions">
                            <button class="contact-btn call-btn" title="Call ${lead.contact_no}">
                                <i class="fas fa-phone"></i>
                            </button>
                            <button class="contact-btn whatsapp-btn" title="WhatsApp ${lead.contact_no}">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                        </div>
                    </div>
                ` : 'N/A';

                tableHTML += `
                    <tr>
                        <td><strong>#${lead.id}</strong></td>
                        <td>${date}</td>
                        <td>${lead.executive || 'N/A'}</td>
                        <td>${lead.customer_name || 'N/A'}</td>
                        <td>${contact}</td>
                        <td>${lead.location || 'N/A'}</td>
                        <td>${(lead.lead_source || 'N/A').toUpperCase()}</td>
                        <td><span class="status-badge badge-${(lead.query || 'default').toLowerCase().replace(/\s+/g, '-')}">${lead.query ? lead.query.charAt(0).toUpperCase() + lead.query.slice(1) : '-'}</span></td>
                        <td><span class="badge bg-${getStatusClass(lead.status)}">${lead.status ? lead.status.charAt(0).toUpperCase() + lead.status.slice(1) : '-'}</span></td>
                        <td><span class="status-badge status-${lead.stage || 'default'}">${lead.stage ? lead.stage.charAt(0).toUpperCase() + lead.stage.slice(1) : '-'}</span></td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            container.innerHTML = tableHTML;
        }

        // Update operation leads display
        function updateOperationLeadsDisplay(data, period) {
            // Update summary cards
            const totalLeads = data.leads.length;
            const avgPerDay = data.stats.length > 0 ? (totalLeads / data.stats.length).toFixed(1) : 0;

            document.getElementById('operationTotalLeads').textContent = totalLeads;
            document.getElementById('operationAvgPerDay').textContent = avgPerDay;

            // Update period display
            if (period === 'custom') {
                const startDate = new Date(data.startDate).toLocaleDateString('en-GB');
                const endDate = new Date(data.endDate).toLocaleDateString('en-GB');
                document.getElementById('operationPeriod').textContent = `${startDate} - ${endDate}`;
            } else {
                document.getElementById('operationPeriod').textContent = period.charAt(0).toUpperCase() + period.slice(1);
            }

            // Generate table
            const container = document.getElementById('operationLeadsTableContainer');
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
                            <th>Vendor</th>
                            <th>Query</th>
                            <th>Status</th>
                            <th>Ongoing/Stopped</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            data.leads.forEach(lead => {
                const date = lead.date_time ? new Date(lead.date_time).toLocaleDateString('en-GB') + ' ' + new Date(lead.date_time).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) : 'N/A';
                const contact = lead.contact_no ? `
                    <div class="contact-info">
                        <span class="contact-number">${lead.contact_no}</span>
                        <div class="contact-actions">
                            <button class="contact-btn call-btn" title="Call ${lead.contact_no}">
                                <i class="fas fa-phone"></i>
                            </button>
                            <button class="contact-btn whatsapp-btn" title="WhatsApp ${lead.contact_no}">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                        </div>
                    </div>
                ` : 'N/A';

                const customerName = lead.patient_name ?
                    `${lead.customer_name || 'N/A'} (${lead.patient_name})` :
                    (lead.customer_name || 'N/A');

                tableHTML += `
                    <tr>
                        <td><strong>#${lead.id}</strong></td>
                        <td>${date}</td>
                        <td>${lead.executive_name || 'N/A'}</td>
                        <td>${customerName}</td>
                        <td>${contact}</td>
                        <td>${lead.location || 'N/A'}</td>
                        <td>${lead.vendor_name || 'N/A'}</td>
                        <td><span class="status-badge badge-${(lead.query || 'default').toLowerCase().replace(/\s+/g, '-')}">${lead.query || '-'}</span></td>
                        <td><span class="status-badge badge-${(lead.status || 'default').toLowerCase().replace(/\s+/g, '-')}">${lead.status || '-'}</span></td>
                        <td>${lead.ongoing_stopped || 'N/A'}</td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            container.innerHTML = tableHTML;
        }

        // Helper functions to get current period
        function getCurrentSalesPeriod() {
            const activeTab = document.querySelector('#salesLeadsModal .stats-tab.active');
            if (!activeTab) return 'today';

            const text = activeTab.textContent.toLowerCase().trim();
            if (text === 'today') return 'today';
            if (text === 'monthly') return 'monthly';
            if (text === 'yearly') return 'yearly';
            if (text === 'custom range') return 'custom';
            return 'today';
        }

        function getCurrentOperationPeriod() {
            const activeTab = document.querySelector('#operationLeadsModal .stats-tab.active');
            if (!activeTab) return 'today';

            const text = activeTab.textContent.toLowerCase().trim();
            if (text === 'today') return 'today';
            if (text === 'monthly') return 'monthly';
            if (text === 'yearly') return 'yearly';
            if (text === 'custom range') return 'custom';
            return 'today';
        }

        // Helper function to get status class
        function getStatusClass(status) {
            if (!status) return 'secondary';
            const statusLower = status.toLowerCase();
            switch (statusLower) {
                case 'follow-up': return 'info';
                case 'future prospect': return 'warning';
                case 'prospect': return 'success';
                case 'no response': return 'secondary';
                case 'price issue': return 'secondary';
                case 'duplicate': return 'danger';
                case 'spam': return 'dark';
                default: return 'secondary';
            }
        }

        // Users tab switching
        function switchUsersTab(filter) {
            // Update active tab
            const modal = document.getElementById('usersModal');
            const tabs = modal.querySelectorAll('.stats-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            // Load users data based on filter
            loadUsersData(filter);
        }

        // Load users data
        function loadUsersData(filter) {
            const container = document.getElementById('usersTableContainer');
            container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading users data...</p></div>';

            let url = `/subadmin/dashboard/users-stats?filter=${filter}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    updateUsersDisplay(data, filter);
                })
                .catch(error => {
                    console.error('Error loading users data:', error);
                    container.innerHTML = `<div style="text-align: center; padding: 40px; color: #ef4444;">
                        <div style="margin-bottom: 16px;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div style="font-weight: 600; margin-bottom: 8px;">Error loading data</div>
                        <div style="font-size: 14px; color: #6B7280;">${error.message}</div>
                        <button onclick="loadUsersData('${filter}')" style="margin-top: 16px; padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">Try Again</button>
                    </div>`;
                });
        }

        // Update users display
        function updateUsersDisplay(data, filter) {
            // Update summary cards
            document.getElementById('usersTotalCount').textContent = data.totalUsers;
            document.getElementById('usersActiveCount').textContent = data.activeUsers;
            document.getElementById('usersInactiveCount').textContent = data.inactiveUsers;

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
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            data.users.forEach(user => {
                const statusBadge = user.is_active ?
                    '<span class="badge bg-success">Active</span>' :
                    '<span class="badge bg-danger">Inactive</span>';

                const createdDate = user.created_at ? new Date(user.created_at).toLocaleDateString('en-GB') : 'N/A';

                tableHTML += `
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">${user.f_name ? user.f_name.charAt(0).toUpperCase() : 'U'}</div>
                                <div>
                                    <div class="user-name">${user.f_name || 'N/A'}</div>
                                    <div class="user-email">${user.l_name || ''}</div>
                                </div>
                            </div>
                        </td>
                        <td>${user.email || 'N/A'}</td>
                        <td>${user.mobile || 'N/A'}</td>
                        <td><span class="badge bg-info">${user.role_name || 'User'}</span></td>
                        <td>${statusBadge}</td>
                        <td>${createdDate}</td>
                        <td>
                            <button class="btn action-btn view-btn" data-id="${user.id}" title="View" onclick="viewUserDetails(${user.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            container.innerHTML = tableHTML;
        }

        // Manual refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            this.innerHTML = '<div class="loading-spinner"></div>';
            setTimeout(() => {
                location.reload();
            }, 500);
        });

        // Add hover effects to cards
        document.querySelectorAll('.dashboard-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                if (!this.classList.contains('clickable-card')) {
                this.style.transform = 'translateY(-4px)';
                }
            });

            card.addEventListener('mouseleave', function() {
                if (!this.classList.contains('clickable-card')) {
                this.style.transform = 'translateY(0)';
                }
            });
        });

        // Add click effects to action icons
        document.querySelectorAll('.action-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                this.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });

        // Smooth scroll to top when clicking refresh
        document.getElementById('refreshBtn').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // View user details function
        function viewUserDetails(userId) {
            // Show the user details modal
            document.getElementById('userDetailsModal').style.display = 'flex';

            // Show loading state
            const content = document.getElementById('userDetailsContent');
            content.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading user details...</p></div>';

            // Fetch user details
            fetch(`/subadmin/dashboard/user-details/${userId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    displayUserDetails(data);
                })
                .catch(error => {
                    console.error('Error loading user details:', error);
                    content.innerHTML = `<div style="text-align: center; padding: 40px; color: #ef4444;">
                        <div style="margin-bottom: 16px;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div style="font-weight: 600; margin-bottom: 8px;">Error loading user details</div>
                        <div style="font-size: 14px; color: #6B7280;">${error.message}</div>
                        <button onclick="viewUserDetails(${userId})" style="margin-top: 16px; padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">Try Again</button>
                    </div>`;
                });
        }

        // Display user details in modal
        function displayUserDetails(user) {
            const content = document.getElementById('userDetailsContent');

            const statusBadge = user.is_active ?
                '<span class="badge bg-success">Active</span>' :
                '<span class="badge bg-danger">Inactive</span>';

            const createdDate = user.created_at ? new Date(user.created_at).toLocaleDateString('en-GB') : 'N/A';
            const updatedDate = user.updated_at ? new Date(user.updated_at).toLocaleDateString('en-GB') : 'N/A';

            content.innerHTML = `
                <div style="display: flex; align-items: center; margin-bottom: 24px; padding: 20px; background: #f8fafc; border-radius: 12px;">
                    <div class="user-avatar" style="width: 60px; height: 60px; font-size: 24px; margin-right: 20px;">
                        ${user.f_name ? user.f_name.charAt(0).toUpperCase() : 'U'}
                    </div>
                    <div>
                        <h3 style="margin: 0; color: #1F2937; font-size: 24px; font-weight: 700;">
                            ${user.f_name || 'N/A'} ${user.l_name || ''}
                        </h3>
                        <p style="margin: 4px 0 0 0; color: #6B7280; font-size: 16px;">${user.role_name || 'User'}</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Email Address</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${user.email || 'N/A'}</div>
                    </div>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Phone Number</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${user.mobile || 'N/A'}</div>
                    </div>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">User Role</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${user.role_name || 'User'}</div>
                    </div>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Status</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${statusBadge}</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Created Date</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${createdDate}</div>
                    </div>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Last Updated</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${updatedDate}</div>
                    </div>
                </div>
            `;
        }

        // Task Management Functions
        let currentTaskType = 'all';

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

            fetch(`/subadmin/dashboard/tasks?${params}`)
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
                let tableHTML = `
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
                            <td>${task.assigned_to_name || 'N/A'}${task.assigned_to_parent_id ? ' (Parent Member)' : ''}</td>
                            <td>${task.assigned_by_name || 'N/A'}${task.assigned_by_parent_id ? ' (Parent Member)' : ''}</td>
                            <td class="${isOverdue ? 'text-danger' : ''}">${dueDate}</td>
                            <td><span class="badge bg-${getStatusClass(task.status)}">${task.status}</span></td>
                            <td>${createdDate}</td>
                            <td>
                                <div style="display: flex; gap: 4px;">
                                    <button class="action-btn view-btn" onclick="viewTask(${task.id})" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" onclick="editTask(${task.id})" title="Edit Task">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteTask(${task.id})" title="Delete Task">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });

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

        function openAddTaskModal() {
            document.getElementById('addTaskModal').style.display = 'flex';
            populateUserDropdowns();
            // Set default due date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('taskDueDate').value = tomorrow.toISOString().split('T')[0];
        }

        function populateUserDropdowns() {
            fetch('/subadmin/dashboard/users')
                .then(response => response.json())
                .then(data => {
                    const assignedToSelect = document.getElementById('taskAssignedTo');
                    const editAssignedToSelect = document.getElementById('editTaskAssignedTo');

                    if (assignedToSelect) {
                        assignedToSelect.innerHTML = '<option value="">Select User</option>';
                        data.users.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.id;
                            // Show parent member indicator if user has parent_id
                            const parentIndicator = user.parent_id ? ' (Parent Member)' : '';
                            option.textContent = `${user.f_name} ${user.l_name} (${user.role_name})${parentIndicator}`;
                            assignedToSelect.appendChild(option);
                        });
                    }

                    if (editAssignedToSelect) {
                        editAssignedToSelect.innerHTML = '<option value="">Select User</option>';
                        data.users.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.id;
                            // Show parent member indicator if user has parent_id
                            const parentIndicator = user.parent_id ? ' (Parent Member)' : '';
                            option.textContent = `${user.f_name} ${user.l_name} (${user.role_name})${parentIndicator}`;
                            editAssignedToSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                });
        }

        function submitTaskForm() {
            const form = document.getElementById('addTaskForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            fetch('/subadmin/dashboard/tasks', {
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
            fetch(`/subadmin/dashboard/tasks/${taskId}/edit`)
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
                        document.getElementById('editTaskAssignedTo').value = task.assigned_to;

                        populateUserDropdowns();
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

            fetch(`/subadmin/dashboard/tasks/${taskId}`, {
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
            fetch(`/subadmin/dashboard/tasks/${taskId}/edit`)
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
                                <p>${task.assigned_to_name || 'N/A'}${task.assigned_to_parent_id ? ' (Parent Member)' : ''}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Assigned By:</label>
                                <p>${task.assigned_by_name || 'N/A'}${task.assigned_by_parent_id ? ' (Parent Member)' : ''}</p>
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
                fetch(`/subadmin/dashboard/tasks/${taskId}`, {
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

        // Task History Functions
        let currentTaskHistoryType = 'all';

        function openTaskHistoryModal() {
            document.getElementById('taskHistoryModal').style.display = 'flex';
            loadTaskHistoryData();
        }

        function switchTaskHistoryTab(type) {
            currentTaskHistoryType = type;

            // Update active tab
            const tabs = document.querySelectorAll('#taskHistoryModal .stats-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            loadTaskHistoryData();
        }

        function loadTaskHistoryData() {
            const container = document.getElementById('taskHistoryTableContainer');
            container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading task history...</p></div>';

            const params = new URLSearchParams({
                type: currentTaskHistoryType
            });

            // Add date filters if they exist
            const startDate = document.getElementById('taskHistoryStartDate').value;
            const endDate = document.getElementById('taskHistoryEndDate').value;

            if (startDate) {
                params.append('start_date', startDate);
            }
            if (endDate) {
                params.append('end_date', endDate);
            }

            fetch(`/subadmin/dashboard/task-history?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading task history. Please try again.</div>';
                        return;
                    }
                    updateTaskHistoryDisplay(data);
                })
                .catch(error => {
                    console.error('Error loading task history:', error);
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading task history. Please try again.</div>';
                });
        }

        function updateTaskHistoryDisplay(data) {
            const container = document.getElementById('taskHistoryTableContainer');
            if (data.tasks && data.tasks.length > 0) {
                let tableHTML = `
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
                            <td>${task.assigned_to_name || 'N/A'}${task.assigned_to_parent_id ? ' (Parent Member)' : ''}</td>
                            <td>${task.assigned_by_name || 'N/A'}${task.assigned_by_parent_id ? ' (Parent Member)' : ''}</td>
                            <td class="${isOverdue ? 'text-danger' : ''}">${dueDate}</td>
                            <td><span class="badge bg-${getStatusClass(task.status)}">${task.status}</span></td>
                            <td>${createdDate}</td>
                            <td>
                                <div style="display: flex; gap: 4px;">
                                    <button class="action-btn view-btn" onclick="viewTask(${task.id})" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" onclick="editTask(${task.id})" title="Edit Task">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteTask(${task.id})" title="Delete Task">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });

                tableHTML += '</tbody></table>';
                container.innerHTML = tableHTML;
            } else {
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No task history found.</div>';
            }
        }

        function applyTaskHistoryDateFilter() {
            const startDate = document.getElementById('taskHistoryStartDate').value;
            const endDate = document.getElementById('taskHistoryEndDate').value;

            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be after end date!');
                return;
            }

            loadTaskHistoryData();
        }

        function clearTaskHistoryDateFilter() {
            document.getElementById('taskHistoryStartDate').value = '';
            document.getElementById('taskHistoryEndDate').value = '';
            loadTaskHistoryData();
        }

        // Admin Calendar Functions
        let currentDateAdmin = new Date();
        let calendarDataAdmin = {};

        function openAdminCalendarModal() {
            document.getElementById('adminCalendarModal').style.display = 'flex';
            currentDateAdmin = new Date();
            loadCalendarDataAdmin();
        }

        function loadCalendarDataAdmin() {
            const year = currentDateAdmin.getFullYear();
            const month = currentDateAdmin.getMonth() + 1;

            fetch(`/subadmin/dashboard/calendar-data?year=${year}&month=${month}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            })
                .then(response => {
                    console.log('Calendar data response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Calendar data received:', data);
                    calendarDataAdmin = data;
                    renderCalendarAdmin();
                })
                .catch(error => {
                    console.error('Error loading calendar data:', error);
                    // Show error message in calendar grid
                    const calendarGrid = document.getElementById('adminCalendarGrid');
                    calendarGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #ef4444;">Error loading calendar data. Please refresh the page and try again.</div>';
                });
        }

        function renderCalendarAdmin() {
            const year = currentDateAdmin.getFullYear();
            const month = currentDateAdmin.getMonth();

            // Update month display
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('currentMonthAdmin').textContent = `${monthNames[month]} ${year}`;

            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();

            // Create calendar grid
            const calendarGrid = document.getElementById('adminCalendarGrid');
            calendarGrid.innerHTML = '';

            // Add day headers
            const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayHeaders.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.style.cssText = 'padding: 12px; text-align: center; background: #f3f4f6; font-weight: 600; color: #374151; font-size: 12px;';
                dayHeader.textContent = day;
                calendarGrid.appendChild(dayHeader);
            });

            // Add empty cells for days before month starts
            for (let i = 0; i < startingDayOfWeek; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.style.cssText = 'height: 60px; background: #f9fafb;';
                calendarGrid.appendChild(emptyCell);
            }

            // Add days of month
            for (let day = 1; day <= daysInMonth; day++) {
                const dayCell = document.createElement('div');
                dayCell.style.cssText = 'height: 60px; background: white; border: 1px solid #e5e7eb; padding: 4px; cursor: pointer; position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center;';

                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dayData = calendarDataAdmin[dateStr] || { sales_followup: 0, sales_future_prospect: 0, operation_followup: 0 };

                // Day number
                const dayNumber = document.createElement('div');
                dayNumber.style.cssText = 'font-weight: 600; color: #1F2937; font-size: 14px; margin-bottom: 2px;';
                dayNumber.textContent = day;
                dayCell.appendChild(dayNumber);

                // Counts
                const countsContainer = document.createElement('div');
                countsContainer.style.cssText = 'display: flex; flex-direction: column; gap: 1px; align-items: center; width: 100%;';

                if (dayData.sales_followup > 0) {
                    const salesFollowupBadge = document.createElement('div');
                    salesFollowupBadge.style.cssText = 'font-size: 9px; font-weight: 600; color: white; background: #f39c12; border-radius: 8px; padding: 1px 4px; min-width: 16px; text-align: center; line-height: 1.2;';
                    salesFollowupBadge.textContent = `${dayData.sales_followup}F`;
                    salesFollowupBadge.title = `${dayData.sales_followup} Sales Follow-up`;
                    countsContainer.appendChild(salesFollowupBadge);
                }

                if (dayData.sales_future_prospect > 0) {
                    const salesFutureBadge = document.createElement('div');
                    salesFutureBadge.style.cssText = 'font-size: 9px; font-weight: 600; color: white; background: #9b59b6; border-radius: 8px; padding: 1px 4px; min-width: 16px; text-align: center; line-height: 1.2;';
                    salesFutureBadge.textContent = `${dayData.sales_future_prospect}P`;
                    salesFutureBadge.title = `${dayData.sales_future_prospect} Sales Future Prospect`;
                    countsContainer.appendChild(salesFutureBadge);
                }

                if (dayData.operation_followup > 0) {
                    const operationFollowupBadge = document.createElement('div');
                    operationFollowupBadge.style.cssText = 'font-size: 9px; font-weight: 600; color: white; background: #22c55e; border-radius: 8px; padding: 1px 4px; min-width: 16px; text-align: center; line-height: 1.2;';
                    operationFollowupBadge.textContent = `${dayData.operation_followup}O`;
                    operationFollowupBadge.title = `${dayData.operation_followup} Operation Follow-up`;
                    countsContainer.appendChild(operationFollowupBadge);
                }

                dayCell.appendChild(countsContainer);

                // Add click event
                dayCell.addEventListener('click', () => {
                    selectDateAdmin(dateStr);
                });

                // Highlight today
                const today = new Date();
                if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
                    dayCell.style.background = '#fef3c7';
                    dayCell.style.borderColor = '#f59e0b';
                }

                calendarGrid.appendChild(dayCell);
            }
        }

        function selectDateAdmin(dateStr) {
            const date = new Date(dateStr);
            const formattedDate = date.toLocaleDateString('en-GB', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            document.getElementById('selectedDateAdmin').textContent = formattedDate;
            openCalendarLeadsModalAdmin(dateStr);
        }

        function openCalendarLeadsModalAdmin(dateStr) {
            document.getElementById('adminCalendarLeadsModal').style.display = 'flex';
            loadDateLeadsAdmin(dateStr);
        }

        function loadDateLeadsAdmin(dateStr) {
            const content = document.getElementById('adminCalendarLeadsContent');
            content.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading leads...</p></div>';

            fetch(`/subadmin/dashboard/calendar-leads?date=${dateStr}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            })
                .then(response => {
                    console.log('Calendar leads response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Calendar leads data received:', data);
                    displayDateLeadsAdmin(data, dateStr);
                })
                .catch(error => {
                    console.error('Error loading date leads:', error);
                    content.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading leads. Please try again.<br><small>Error: ' + error.message + '</small></div>';
                });
        }

        function displayDateLeadsAdmin(data, dateStr) {
            const content = document.getElementById('adminCalendarLeadsContent');

            if (data.sales_leads.length === 0 && data.operation_leads.length === 0) {
                content.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No leads found for this date.</div>';
                return;
            }

            let html = '<div style="display: grid; gap: 20px;">';

            // Sales Leads Section
            if (data.sales_leads.length > 0) {
                html += `
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                        <div style="background: #f39c12; color: white; padding: 12px; font-weight: 600;">
                            <i class="fas fa-users"></i> Sales Leads (${data.sales_leads.length})
                        </div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <table class="leads-table">
                                <thead>
                                    <tr>
                                        <th>Lead ID</th>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Executive</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

                data.sales_leads.forEach(lead => {
                    const contact = lead.contact_no ? `
                        <div class="contact-info">
                            <span class="contact-number">${lead.contact_no}</span>
                            <div class="contact-actions">
                                <button class="contact-btn call-btn" title="Call ${lead.contact_no}">
                                    <i class="fas fa-phone"></i>
                                </button>
                                <button class="contact-btn whatsapp-btn" title="WhatsApp ${lead.contact_no}">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                            </div>
                        </div>
                    ` : 'N/A';

                    html += `
                        <tr>
                            <td><strong>#${lead.id}</strong></td>
                            <td>${lead.customer_name || 'N/A'}</td>
                            <td>${contact}</td>
                            <td>${lead.executive || 'N/A'}</td>
                            <td><span class="badge bg-${getStatusClass(lead.status)}">${lead.status || '-'}</span></td>
                            <td>
                                <button onclick="openLeadDetailsModal(${lead.id}, 'sales')" class="btn action-btn view-btn" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div></div>';
            }

            // Operation Leads Section
            if (data.operation_leads.length > 0) {
                html += `
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                        <div style="background: #22c55e; color: white; padding: 12px; font-weight: 600;">
                            <i class="fas fa-cogs"></i> Operation Leads (${data.operation_leads.length})
                        </div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <table class="leads-table">
                                <thead>
                                    <tr>
                                        <th>Lead ID</th>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Executive</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

                data.operation_leads.forEach(lead => {
                    const contact = lead.contact_no ? `
                        <div class="contact-info">
                            <span class="contact-number">${lead.contact_no}</span>
                            <div class="contact-actions">
                                <button class="contact-btn call-btn" title="Call ${lead.contact_no}">
                                    <i class="fas fa-phone"></i>
                                </button>
                                <button class="contact-btn whatsapp-btn" title="WhatsApp ${lead.contact_no}">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                            </div>
                        </div>
                    ` : 'N/A';

                    html += `
                        <tr>
                            <td><strong>#${lead.id}</strong></td>
                            <td>${lead.customer_name || 'N/A'}</td>
                            <td>${contact}</td>
                            <td>${lead.executive_name || 'N/A'}</td>
                            <td><span class="status-badge badge-${(lead.status || 'default').toLowerCase().replace(/\s+/g, '-')}">${lead.status || '-'}</span></td>
                            <td>
                                <button onclick="openLeadDetailsModal(${lead.id}, 'operation')" class="btn action-btn view-btn" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div></div>';
            }

            html += '</div>';
            content.innerHTML = html;
        }

        // Open lead details modal function
        function openLeadDetailsModal(leadId, leadType) {
            console.log('Opening lead details modal for:', leadId, leadType);

            // Close any existing modals first
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.style.display = 'none';
            });

            // Show the lead details modal
            const modal = document.getElementById('leadDetailsModal');
            if (modal) {
                modal.style.display = 'flex';
                console.log('Modal displayed');
            } else {
                console.error('Lead details modal not found');
                return;
            }

            // Show loading state
            const content = document.getElementById('leadDetailsContent');
            if (content) {
                content.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading lead details...</p></div>';
            }

            // Determine the correct URL based on lead type
            const url = leadType === 'sales' ? `/subadmin/leads/${leadId}` : `/subadmin/operation-leads/${leadId}`;
            console.log('Fetching from URL:', url);

            // Fetch lead details
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Lead data received:', data);
                    displayLeadDetails(data, leadType);
                })
                .catch(error => {
                    console.error('Error loading lead details:', error);
                    if (content) {
                        content.innerHTML = `<div style="text-align: center; padding: 40px; color: #ef4444;">
                            <div style="margin-bottom: 16px;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M2 17L12 22L22 17" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M2 12L12 17L22 12" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div style="font-weight: 600; margin-bottom: 8px;">Error loading lead details</div>
                            <div style="font-size: 14px; color: #6B7280;">${error.message}</div>
                            <button onclick="openLeadDetailsModal(${leadId}, '${leadType}')" style="margin-top: 16px; padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">Try Again</button>
                        </div>`;
                    }
                });
        }

        // Display lead details in modal
        function displayLeadDetails(lead, leadType) {
            const content = document.getElementById('leadDetailsContent');

            const createdDate = lead.created_at ? new Date(lead.created_at).toLocaleDateString('en-GB') : 'N/A';
            const updatedDate = lead.updated_at ? new Date(lead.updated_at).toLocaleDateString('en-GB') : 'N/A';

            let html = `
                <div style="display: flex; align-items: center; margin-bottom: 24px; padding: 20px; background: #f8fafc; border-radius: 12px;">
                    <div style="width: 60px; height: 60px; background: ${leadType === 'sales' ? '#f39c12' : '#22c55e'}; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                        <i class="fas ${leadType === 'sales' ? 'fa-users' : 'fa-cogs'}" style="color: white; font-size: 24px;"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; color: #1F2937; font-size: 24px; font-weight: 700;">
                            ${lead.customer_name || 'N/A'}
                        </h3>
                        <p style="margin: 4px 0 0 0; color: #6B7280; font-size: 16px;">Lead ID: #${lead.id}</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
            `;

            // Common fields for both lead types
            html += `
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                    <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Customer Name</div>
                    <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${lead.customer_name || 'N/A'}</div>
                </div>
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                    <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Contact Number</div>
                    <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${lead.contact_no || 'N/A'}</div>
                </div>
            `;

            if (leadType === 'sales') {
                html += `
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Executive</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${lead.executive_name || 'N/A'}</div>
                    </div>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Status</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;"><span class="badge bg-${getStatusClass(lead.status)}">${lead.status || '-'}</span></div>
                    </div>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Location</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${lead.location || 'N/A'}</div>
                    </div>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Lead Source</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${(lead.lead_source || 'N/A').toUpperCase()}</div>
                    </div>
                `;
            } else {
                html += `
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Executive</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${lead.executive_name || 'N/A'}</div>
                    </div>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Status</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;"><span class="status-badge badge-${(lead.status || 'default').toLowerCase().replace(/\s+/g, '-')}">${lead.status || '-'}</span></div>
                    </div>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Vendor</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${lead.vendor_name || 'N/A'}</div>
                    </div>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Date/Time</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${lead.date_time ? new Date(lead.date_time).toLocaleDateString('en-GB') + ' ' + new Date(lead.date_time).toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'}) : 'N/A'}</div>
                    </div>
                `;
            }

            html += `
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Created Date</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${createdDate}</div>
                    </div>
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                        <div style="color: #6B7280; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Last Updated</div>
                        <div style="color: #1F2937; font-size: 14px; font-weight: 500;">${updatedDate}</div>
                    </div>
                </div>
            `;

            content.innerHTML = html;
        }

        // Event listeners for month navigation
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('prevMonthAdmin').addEventListener('click', function() {
                currentDateAdmin.setMonth(currentDateAdmin.getMonth() - 1);
                loadCalendarDataAdmin();
            });

            document.getElementById('nextMonthAdmin').addEventListener('click', function() {
                currentDateAdmin.setMonth(currentDateAdmin.getMonth() + 1);
                loadCalendarDataAdmin();
            });

            // Load calendar data for the card count on page load
            loadAdminCalendarCardData();
        });

        // Admin Calendar Card functionality
        function loadAdminCalendarCardData() {
            const year = new Date().getFullYear();
            const month = new Date().getMonth() + 1;

            fetch(`/subadmin/dashboard/calendar-data?year=${year}&month=${month}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                updateAdminCalendarCardCount(data);
            })
            .catch(error => {
                console.error('Error loading admin calendar data:', error);
                updateAdminCalendarCardCount({});
            });
        }

        function updateAdminCalendarCardCount(calendarData) {
            let totalFollowUp = 0;
            let totalFutureProspect = 0;

            if (calendarData && typeof calendarData === 'object') {
                Object.values(calendarData).forEach(dayData => {
                    totalFollowUp += (dayData.sales_followup || 0) + (dayData.operation_followup || 0);
                    totalFutureProspect += dayData.sales_future_prospect || 0;
                });
            }

            const countElement = document.getElementById('adminCalendarCount');

            if (totalFollowUp > 0 || totalFutureProspect > 0) {
                countElement.innerHTML = `
                    <div style="font-size: 11px; line-height: 1.3;">
                        <div style="margin-bottom: 2px;">Follow-up: <strong style="color: #007bff;">${totalFollowUp}</strong></div>
                        <div>Future Prospect: <strong style="color: #28a745;">${totalFutureProspect}</strong></div>
                    </div>
                `;
            } else {
                countElement.innerHTML = '<span style="color: #6c757d; font-size: 0.9em;">No scheduled leads</span>';
            }
        }

        // Revenue Modal Functions
        function openRevenueModal() {
            document.getElementById('revenueModal').style.display = 'flex';
            setupRevenueDateInputs();
            populateRevenueMonthYearDropdowns();
            loadRevenueData('today');
        }

        function switchRevenueTab(period) {
            // Update active tab
            const tabs = document.querySelectorAll('#revenueModal .stats-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            // Hide all selectors first
            const selectors = ['revenueTodaySelector', 'revenueMonthlySelector', 'revenueYearlySelector', 'revenueCustomSelector'];
            selectors.forEach(id => {
                document.getElementById(id).style.display = 'none';
            });

            if (period === 'today') {
                document.getElementById('revenueTodaySelector').style.display = 'flex';
                loadRevenueData('today');
            } else if (period === 'monthly') {
                document.getElementById('revenueMonthlySelector').style.display = 'flex';
                const monthSelect = document.getElementById('revenueMonthSelect');
                if (monthSelect.value) {
                    loadRevenueData('monthly', null, null, monthSelect.value);
                }
            } else if (period === 'yearly') {
                document.getElementById('revenueYearlySelector').style.display = 'flex';
                const yearSelect = document.getElementById('revenueYearSelect');
                if (yearSelect.value) {
                    loadRevenueData('yearly', null, null, yearSelect.value);
                }
            } else if (period === 'custom') {
                document.getElementById('revenueCustomSelector').style.display = 'flex';
            }
        }

        function populateRevenueMonthYearDropdowns() {
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth();

            // Populate months (last 12 months)
            const monthSelect = document.getElementById('revenueMonthSelect');
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
            const yearSelect = document.getElementById('revenueYearSelect');
            yearSelect.innerHTML = '';

            for (let year = currentYear; year >= currentYear - 4; year--) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                if (year === currentYear) option.selected = true; // Current year
                yearSelect.appendChild(option);
            }
        }

        function setupRevenueDateInputs() {
            const today = new Date().toISOString().split('T')[0];
            const startDateInput = document.getElementById('revenueStartDate');
            const endDateInput = document.getElementById('revenueEndDate');

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

        function applyRevenueDateFilter() {
            const startDate = document.getElementById('revenueStartDate').value;
            const endDate = document.getElementById('revenueEndDate').value;

            if (!startDate || !endDate) {
                alert('Please select both start and end dates.');
                return;
            }

            if (startDate > endDate) {
                alert('Start date cannot be after end date.');
                return;
            }

            loadRevenueData('custom', startDate, endDate);
        }

        function loadRevenueData(period, startDate = null, endDate = null, selectedPeriod = null) {
            // Show loading state
            document.getElementById('revenueChart').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading revenue data...</p></div>';
            document.getElementById('revenueDetailsTable').innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading revenue details...</p></div>';

            let url = `/subadmin/dashboard/revenue-stats?period=${period}`;
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
                    updateRevenueDisplay(data);
                })
                .catch(error => {
                    console.error('Error loading revenue data:', error);
                    document.getElementById('revenueChart').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading revenue data. Please try again.</div>';
                    document.getElementById('revenueDetailsTable').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading revenue details. Please try again.</div>';
                });
        }

        function updateRevenueDisplay(data) {
            // Update summary cards
            const totalReceived = data.total_received || 0;
            const vendorPayments = data.vendor_payments || 0;
            const netRevenue = totalReceived - vendorPayments;
            const profitMargin = totalReceived > 0 ? ((netRevenue / totalReceived) * 100).toFixed(1) : 0;

            document.getElementById('totalReceived').textContent = `₹${totalReceived.toLocaleString()}`;
            document.getElementById('vendorPayments').textContent = `₹${vendorPayments.toLocaleString()}`;
            document.getElementById('netRevenue').textContent = `₹${netRevenue.toLocaleString()}`;
            document.getElementById('profitMargin').textContent = `${profitMargin}%`;

            // Update colors based on values
            document.getElementById('netRevenue').style.color = netRevenue >= 0 ? '#059669' : '#dc2626';
            document.getElementById('profitMargin').style.color = profitMargin >= 0 ? '#059669' : '#dc2626';

            // Create revenue chart
            createRevenueChart(totalReceived, vendorPayments, netRevenue);

            // Update revenue details table
            updateRevenueDetailsTable(data.revenue_details || []);
        }

        function createRevenueChart(totalReceived, vendorPayments, netRevenue) {
            const chartContainer = document.getElementById('revenueChart');

            // Create a simple bar chart using HTML/CSS
            const maxValue = Math.max(totalReceived, vendorPayments, Math.abs(netRevenue));
            const scale = maxValue > 0 ? 250 / maxValue : 1;

            const receivedHeight = (totalReceived * scale);
            const vendorHeight = (vendorPayments * scale);
            const netHeight = (Math.abs(netRevenue) * scale);

            chartContainer.innerHTML = `
                <div style="display: flex; align-items: end; justify-content: center; gap: 40px; height: 250px; padding: 20px;">
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="width: 60px; height: ${receivedHeight}px; background: linear-gradient(135deg, #059669, #047857); border-radius: 8px 8px 0 0; margin-bottom: 10px; position: relative;">
                            <div style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); color: #059669; font-weight: 600; font-size: 12px;">₹${totalReceived.toLocaleString()}</div>
                        </div>
                        <div style="color: #374151; font-weight: 600; font-size: 14px;">Total Received</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="width: 60px; height: ${vendorHeight}px; background: linear-gradient(135deg, #dc2626, #b91c1c); border-radius: 8px 8px 0 0; margin-bottom: 10px; position: relative;">
                            <div style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); color: #dc2626; font-weight: 600; font-size: 12px;">₹${vendorPayments.toLocaleString()}</div>
                        </div>
                        <div style="color: #374151; font-weight: 600; font-size: 14px;">Vendor Payments</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="width: 60px; height: ${netHeight}px; background: linear-gradient(135deg, ${netRevenue >= 0 ? '#667eea, #5a67d8' : '#ef4444, #dc2626'}); border-radius: 8px 8px 0 0; margin-bottom: 10px; position: relative;">
                            <div style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); color: ${netRevenue >= 0 ? '#667eea' : '#ef4444'}; font-weight: 600; font-size: 12px;">₹${netRevenue.toLocaleString()}</div>
                        </div>
                        <div style="color: #374151; font-weight: 600; font-size: 14px;">Net Revenue</div>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 20px; color: #6B7280; font-size: 14px;">
                    Revenue breakdown showing total received payments, vendor payments, and net revenue
                </div>
            `;
        }

        function updateRevenueDetailsTable(revenueDetails) {
            const container = document.getElementById('revenueDetailsTable');

            if (revenueDetails.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No revenue details found for this period.</div>';
                return;
            }

            let tableHTML = `
                <table class="leads-table">
                    <thead>
                        <tr>
                            <th>Lead ID</th>
                            <th>Executive</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total Received</th>
                            <th>Vendor Payment</th>
                            <th>Net Revenue</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            revenueDetails.forEach(detail => {
                const netRevenue = (detail.total_received || 0) - (detail.vendor_payment || 0);
                const date = detail.date ? new Date(detail.date).toLocaleDateString('en-GB') : 'N/A';

                tableHTML += `
                    <tr>
                        <td><strong>#${detail.lead_id || 'N/A'}</strong></td>
                        <td>${detail.executive_name || 'N/A'}</td>
                        <td>${detail.customer_name || 'N/A'}</td>
                        <td>${date}</td>
                        <td style="color: #059669; font-weight: 600;">₹${(detail.total_received || 0).toLocaleString()}</td>
                        <td style="color: #dc2626; font-weight: 600;">₹${(detail.vendor_payment || 0).toLocaleString()}</td>
                        <td style="color: ${netRevenue >= 0 ? '#667eea' : '#ef4444'}; font-weight: 600;">₹${netRevenue.toLocaleString()}</td>
                        <td>
                            <a href="/subadmin/operation-leads/${detail.lead_id}" class="btn action-btn view-btn" title="View Lead">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            container.innerHTML = tableHTML;
        }

        // Pending Deployments Modal Functions
        function openPendingDeploymentsModal() {
            document.getElementById('pendingDeploymentsModal').style.display = 'flex';
            loadPendingDeploymentsData();
        }

        function loadPendingDeploymentsData() {
            // Show loading state
            document.getElementById('pendingDeploymentsTable').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading pending deployments...</p>
                </div>
            `;

            fetch('/subadmin/dashboard/pending-deployments')
                .then(response => response.json())
                .then(data => {
                    updatePendingDeploymentsDisplay(data);
                })
                .catch(error => {
                    console.error('Error loading pending deployments:', error);
                    document.getElementById('pendingDeploymentsTable').innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #dc2626;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                            <p>Error loading pending deployments data.</p>
                        </div>
                    `;
                });
        }

        function updatePendingDeploymentsDisplay(data) {
            // Update summary cards
            document.getElementById('todayPendingDeployments').textContent = data.today_pending || 0;
            document.getElementById('weekPendingDeployments').textContent = data.week_pending || 0;
            document.getElementById('overdueDeployments').textContent = data.overdue || 0;

            // Update details table
            updatePendingDeploymentsTable(data.deployments || []);
        }

        function updatePendingDeploymentsTable(deployments) {
            const container = document.getElementById('pendingDeploymentsTable');

            if (deployments.length === 0) {
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

            deployments.forEach(deployment => {
                const deploymentDate = deployment.deployment_date ? new Date(deployment.deployment_date).toLocaleDateString() : 'N/A';
                const statusClass = deployment.deployment_status === 'pending' ? 'badge-warning' : 'badge-secondary';

                // Create call buttons for vendor and staff numbers
                const vendorCallButton = deployment.vendor_contact_no ? 
                    `<a href="tel:${deployment.vendor_contact_no}" class="btn action-btn call-btn" title="Call Vendor" style="margin-left: 5px;">
                        <i class="fas fa-phone"></i>
                    </a>` : '';
                
                const staffCallButton = deployment.staff_number ? 
                    `<a href="tel:${deployment.staff_number}" class="btn action-btn call-btn" title="Call Staff" style="margin-left: 5px;">
                        <i class="fas fa-phone"></i>
                    </a>` : '';

                const deploymentDateTime = deployment.deployment_date ? new Date(deployment.deployment_date).toLocaleString('en-GB', {
                    day: '2-digit',
                    month: '2-digit', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'N/A';

                tableHTML += `
                    <tr>
                        <td><strong>${deployment.lead_id || ('#' + deployment.operation_lead_id) || 'N/A'}</strong></td>
                        <td>${deployment.executive_name || 'N/A'}</td>
                        <td>${deploymentDateTime}</td>
                        <td>${deployment.customer_name || 'N/A'}</td>
                        <td>${deployment.contact_no || 'N/A'}</td>
                        <td>${deployment.vendor_name || 'N/A'}</td>
                        <td>${deployment.vendor_contact_no || 'N/A'}</td>
                        <td>${deployment.staff_name || 'N/A'}</td>
                        <td>${deployment.staff_number || 'N/A'}</td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            container.innerHTML = tableHTML;
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
                    <p style="margin-top: 16px; color: #6B7280;">Loading outstanding amount data...</p>
                </div>
            `;

            let url = '/subadmin/dashboard/outstanding-amount-stats?period=' + period;

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
            document.getElementById('totalOutstanding').textContent = '₹' + (data.total_outstanding || 0).toLocaleString();
            document.getElementById('positiveOutstanding').textContent = '₹' + (data.positive_outstanding || 0).toLocaleString();
            document.getElementById('negativeOutstanding').textContent = '₹' + (data.negative_outstanding || 0).toLocaleString();
            document.getElementById('outstandingCount').textContent = data.count || 0;

            // Update details table
            updateOutstandingDetailsTable(data.outstanding_details || []);
        }

        function updateOutstandingDetailsTable(outstandingDetails) {
            const container = document.getElementById('outstandingDetailsTable');

            if (outstandingDetails.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No outstanding amounts found for this period.</div>';
                return;
            }

            let tableHTML = `
                <table class="leads-table">
                    <thead>
                        <tr>
                            <th>Lead ID</th>
                            <th>Executive</th>
                            <th>Customer</th>
                            <th>Contact No</th>
                            <th>Payment Received</th>
                            <th>Refund Amount</th>
                            <th>Net Amount</th>
                            <th>Outstanding Amount</th>
                            <th>Received Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            outstandingDetails.forEach(detail => {
                const paymentReceived = parseFloat(detail.payment_received || 0);
                const refundAmount = parseFloat(detail.refund_amount || 0);
                const netAmount = paymentReceived - refundAmount;
                const outstandingAmount = parseFloat(detail.outstanding_payment || 0);
                const receivedDate = detail.received_date ? new Date(detail.received_date).toLocaleDateString() : 'N/A';

                const outstandingColor = outstandingAmount > 0 ? '#dc2626' : (outstandingAmount < 0 ? '#059669' : '#6B7280');

                tableHTML += `
                    <tr>
                        <td><strong>${detail.lead_id || ('#' + detail.operation_lead_id) || 'N/A'}</strong></td>
                        <td>${detail.executive_name || 'N/A'}</td>
                        <td>${detail.customer_name || 'N/A'}</td>
                        <td>${detail.contact_no || 'N/A'}</td>
                        <td style="color: #059669; font-weight: 600;">₹${paymentReceived.toLocaleString()}</td>
                        <td style="color: #dc2626; font-weight: 600;">₹${refundAmount.toLocaleString()}</td>
                        <td style="color: #667eea; font-weight: 600;">₹${netAmount.toLocaleString()}</td>
                        <td style="color: ${outstandingColor}; font-weight: 600;">₹${outstandingAmount.toLocaleString()}</td>
                        <td>${receivedDate}</td>
                        <td>
                            <a href="/subadmin/operation-leads/${detail.operation_lead_id}" class="btn action-btn view-btn" title="View Lead">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            container.innerHTML = tableHTML;
        }

        // Profile Pending Modal Functions
        function openProfilePendingModal() {
            document.getElementById('profilePendingModal').style.display = 'flex';
            setupProfilePendingDateInputs();
            populateProfilePendingMonthYearDropdowns();
            loadProfilePendingData('all');
        }

        function switchProfilePendingTab(period) {
            // Update active tab
            const tabs = document.querySelectorAll('#profilePendingModal .stats-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            // Hide all selectors first
            const selectors = ['profilePendingAllSelector', 'profilePendingTodaySelector', 'profilePendingMonthlySelector', 'profilePendingYearlySelector', 'profilePendingCustomSelector'];
            selectors.forEach(id => {
                document.getElementById(id).style.display = 'none';
            });

            if (period === 'all') {
                document.getElementById('profilePendingAllSelector').style.display = 'flex';
                loadProfilePendingData('all');
            } else if (period === 'today') {
                document.getElementById('profilePendingTodaySelector').style.display = 'flex';
                loadProfilePendingData('today');
            } else if (period === 'monthly') {
                document.getElementById('profilePendingMonthlySelector').style.display = 'flex';
                const monthSelect = document.getElementById('profilePendingMonthSelect');
                if (monthSelect.value) {
                    loadProfilePendingData('monthly', null, null, monthSelect.value);
                }
            } else if (period === 'yearly') {
                document.getElementById('profilePendingYearlySelector').style.display = 'flex';
                const yearSelect = document.getElementById('profilePendingYearSelect');
                if (yearSelect.value) {
                    loadProfilePendingData('yearly', null, null, yearSelect.value);
                }
            } else if (period === 'custom') {
                document.getElementById('profilePendingCustomSelector').style.display = 'flex';
            }
        }

        function populateProfilePendingMonthYearDropdowns() {
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth();

            // Populate month dropdown
            const monthSelect = document.getElementById('profilePendingMonthSelect');
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
            const yearSelect = document.getElementById('profilePendingYearSelect');
            yearSelect.innerHTML = '<option value="">Select Year</option>';

            for (let year = currentYear; year >= currentYear - 5; year--) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                if (year === currentYear) option.selected = true;
                yearSelect.appendChild(option);
            }
        }

        function setupProfilePendingDateInputs() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('profilePendingStartDate').max = today;
            document.getElementById('profilePendingEndDate').max = today;
        }

        function applyProfilePendingDateFilter() {
            const startDate = document.getElementById('profilePendingStartDate').value;
            const endDate = document.getElementById('profilePendingEndDate').value;

            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }

            if (startDate > endDate) {
                alert('Start date cannot be after end date');
                return;
            }

            loadProfilePendingData('custom', startDate, endDate);
        }

        function loadProfilePendingData(period, startDate, endDate, selectedPeriod) {
            // Show loading state
            document.getElementById('profilePendingDetailsTable').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading profile pending leads...</p>
                </div>
            `;

            let url = '/subadmin/dashboard/profile-pending-stats?period=' + period;

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
            document.getElementById('todayProfilePending').textContent = data.today_profile_pending || 0;
            document.getElementById('monthProfilePending').textContent = data.month_profile_pending || 0;
            document.getElementById('yearProfilePending').textContent = data.year_profile_pending || 0;

            // Update details table
            updateProfilePendingDetailsTable(data.profile_pending_leads || []);
        }

        function updateProfilePendingDetailsTable(profilePendingLeads) {
            const container = document.getElementById('profilePendingDetailsTable');

            if (profilePendingLeads.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No profile pending leads found for this period.</div>';
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
                        <td><strong>${lead.lead_id || ('#' + lead.id) || 'N/A'}</strong></td>
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

        // Vendor Payment Modal Functions
        function openVendorPaymentModal() {
            document.getElementById('vendorPaymentModal').style.display = 'flex';
            setupVendorPaymentDateInputs();
            populateVendorPaymentMonthYearDropdowns();
            loadVendorPaymentData('all');
        }

        function switchVendorPaymentTab(period) {
            // Update active tab
            const tabs = document.querySelectorAll('#vendorPaymentModal .stats-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            // Hide all selectors first
            const selectors = ['vendorPaymentAllSelector', 'vendorPaymentTodaySelector', 'vendorPaymentMonthlySelector', 'vendorPaymentYearlySelector', 'vendorPaymentCustomSelector'];
            selectors.forEach(id => {
                document.getElementById(id).style.display = 'none';
            });

            if (period === 'all') {
                document.getElementById('vendorPaymentAllSelector').style.display = 'flex';
                loadVendorPaymentData('all');
            } else if (period === 'today') {
                document.getElementById('vendorPaymentTodaySelector').style.display = 'flex';
                loadVendorPaymentData('today');
            } else if (period === 'monthly') {
                document.getElementById('vendorPaymentMonthlySelector').style.display = 'flex';
                const monthSelect = document.getElementById('vendorPaymentMonthSelect');
                if (monthSelect.value) {
                    loadVendorPaymentData('monthly', null, null, monthSelect.value);
                }
            } else if (period === 'yearly') {
                document.getElementById('vendorPaymentYearlySelector').style.display = 'flex';
                const yearSelect = document.getElementById('vendorPaymentYearSelect');
                if (yearSelect.value) {
                    loadVendorPaymentData('yearly', null, null, yearSelect.value);
                }
            } else if (period === 'custom') {
                document.getElementById('vendorPaymentCustomSelector').style.display = 'flex';
            }
        }

        function populateVendorPaymentMonthYearDropdowns() {
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth();

            // Populate month dropdown
            const monthSelect = document.getElementById('vendorPaymentMonthSelect');
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
            const yearSelect = document.getElementById('vendorPaymentYearSelect');
            yearSelect.innerHTML = '<option value="">Select Year</option>';

            for (let year = currentYear; year >= currentYear - 5; year--) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                if (year === currentYear) option.selected = true;
                yearSelect.appendChild(option);
            }
        }

        function setupVendorPaymentDateInputs() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('vendorPaymentStartDate').max = today;
            document.getElementById('vendorPaymentEndDate').max = today;
        }

        function applyVendorPaymentDateFilter() {
            const startDate = document.getElementById('vendorPaymentStartDate').value;
            const endDate = document.getElementById('vendorPaymentEndDate').value;

            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }

            if (startDate > endDate) {
                alert('Start date cannot be after end date');
                return;
            }

            loadVendorPaymentData('custom', startDate, endDate);
        }

        function loadVendorPaymentData(period, startDate, endDate, selectedPeriod) {
            // Show loading state
            document.getElementById('vendorPaymentDetailsTable').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading vendor payment details...</p>
                </div>
            `;

            let url = '/subadmin/dashboard/vendor-payment-stats?period=' + period;

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
                    updateVendorPaymentDisplay(data);
                })
                .catch(error => {
                    console.error('Error loading vendor payment data:', error);
                    document.getElementById('vendorPaymentDetailsTable').innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #dc2626;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                            <p>Error loading vendor payment data.</p>
                        </div>
                    `;
                });
        }

        function updateVendorPaymentDisplay(data) {
            // Update summary cards
            document.getElementById('totalEarned').textContent = '₹' + (parseFloat(data.total_earned || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
            document.getElementById('totalPaymentsMade').textContent = '₹' + (parseFloat(data.total_payments_made || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
            document.getElementById('totalRemaining').textContent = '₹' + (parseFloat(data.total_remaining || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
            document.getElementById('totalVendors').textContent = data.total_vendors || 0;

            // Update details table
            updateVendorPaymentDetailsTable(data.vendor_details || []);
        }

        function updateVendorPaymentDetailsTable(vendorDetails) {
            const container = document.getElementById('vendorPaymentDetailsTable');

            if (vendorDetails.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #6B7280;">No vendor payment details found for this period.</div>';
                return;
            }

            let tableHTML = `
                <table class="leads-table">
                    <thead>
                        <tr>
                            <th>Vendor Name</th>
                            <th>Contact</th>
                            <th>Total Earned</th>
                            <th>Payments Made</th>
                            <th>Remaining Amount</th>
                            <th>Deployments</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            vendorDetails.forEach(vendor => {
                const remainingColor = vendor.remaining_amount >= 0 ? '#8b5cf6' : '#ef4444';
                const remainingText = vendor.remaining_amount >= 0 ? '₹' + parseFloat(vendor.remaining_amount).toLocaleString('en-IN', {minimumFractionDigits: 2}) : '₹' + parseFloat(vendor.remaining_amount).toLocaleString('en-IN', {minimumFractionDigits: 2});

                tableHTML += `
                    <tr>
                        <td><strong>${vendor.vendor_name || 'N/A'}</strong></td>
                        <td>${vendor.vendor_contact || 'N/A'}</td>
                        <td><span style="color: #059669;">₹${parseFloat(vendor.total_earned || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</span></td>
                        <td><span style="color: #dc2626;">₹${parseFloat(vendor.total_payments_made || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</span></td>
                        <td><span style="color: ${remainingColor};">${remainingText}</span></td>
                        <td><span class="badge bg-primary">${vendor.deployments_count || 0}</span></td>
                        <td>
                            <a href="/admin/vendor-payments" class="btn action-btn view-btn" title="View Payment Details">
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

            fetch('{{ route('subadmin.dashboard.unverified-deployment-payments-stats') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateUnverifiedDeploymentDisplay(data.data);
                    } else {
                        container.innerHTML = `
                            <div style="text-align: center; padding: 40px; color: #dc2626;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                                <p>Error loading unverified deployment payments.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading unverified deployment payments:', error);
                    container.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #dc2626;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                            <p>Error loading unverified deployment payments.</p>
                        </div>
                    `;
                });
        }

        function updateUnverifiedDeploymentDisplay(data) {
            // Update summary cards
            document.getElementById('totalUnverified').textContent = data.total_unverified || 0;
            document.getElementById('totalUnverifiedAmount').textContent = '₹' + (parseFloat(data.total_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));

            // Update the card count on dashboard
            const cardCount = document.querySelector('.dashboard-card[onclick="openUnverifiedDeploymentModal()"] .card-count');
            if (cardCount) {
                cardCount.textContent = data.total_unverified || 0;
            }

            // Generate table
            const container = document.getElementById('unverifiedDeploymentTableContainer');
            if (!data.deployments || data.deployments.length === 0) {
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
                const deploymentDate = deployment.deployment_date ? new Date(deployment.deployment_date).toLocaleDateString('en-GB') : 'N/A';
                const fromDate = deployment.deployment_from_date ? new Date(deployment.deployment_from_date).toLocaleDateString('en-GB') : 'N/A';
                const toDate = deployment.deployment_to_date ? new Date(deployment.deployment_to_date).toLocaleDateString('en-GB') : 'N/A';

                tableHTML += `
                    <tr>
                        <td><strong>#${deployment.lead_id}</strong></td>
                        <td>${deployment.customer_name}</td>
                        <td>${deployment.contact_no}</td>
                        <td>${deployment.location}</td>
                        <td>${deployment.vendor_name}</td>
                        <td>${deployment.staff_name}</td>
                        <td>${deployment.staff_number}</td>
                        <td>${deployment.payment_term}</td>
                        <td>${deploymentDate}</td>
                        <td>${fromDate}</td>
                        <td>${toDate}</td>
                        <td><span class="badge bg-warning">${deployment.deployment_status}</span></td>
                        <td style="color: #dc2626; font-weight: 600;">₹${parseFloat(deployment.vendor_payment || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                        <td class="text-center">
                            <input type="checkbox" 
                                   class="verify-payment-checkbox-dashboard"
                                   data-deployment-id="${deployment.id}"
                                   ${deployment.verify_payment ? 'checked' : ''}
                                   onchange="toggleVerifyPaymentDashboard(${deployment.id}, this.checked)">
                        </td>
                        <td>
                            <a href="/subadmin/operation-leads/${deployment.lead_id}" class="btn action-btn view-btn" title="View Lead" target="_blank">
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
                url: `/subadmin/operation-leads/deployment/${deploymentId}/toggle-verify-payment`,
                type: 'POST',
                data: {
                    verify_payment: isChecked,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        // Reload the data to update the table and remove verified payments
                        loadUnverifiedDeploymentData();
                    } else {
                        toastr.error(response.message || 'Failed to update payment verification status');
                        // Revert checkbox state on error
                        const checkbox = document.querySelector(`.verify-payment-checkbox-dashboard[data-deployment-id="${deploymentId}"]`);
                        if (checkbox) {
                            checkbox.checked = !isChecked;
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    toastr.error('Error updating payment verification status. Please try again.');
                    // Revert checkbox state on error
                    const checkbox = document.querySelector(`.verify-payment-checkbox-dashboard[data-deployment-id="${deploymentId}"]`);
                    if (checkbox) {
                        checkbox.checked = !isChecked;
                    }
                }
            });
        }

        // Load unverified deployment count on page load
        document.addEventListener('DOMContentLoaded', function() {
            fetch('{{ route('subadmin.dashboard.unverified-deployment-payments-stats') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cardCount = document.querySelector('.dashboard-card[onclick="openUnverifiedDeploymentModal()"] .card-count');
                        if (cardCount) {
                            cardCount.textContent = data.data.total_unverified || 0;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading unverified deployment count:', error);
                });

            // Load pending callbacks count on page load
            loadPendingCallbacksCount();
        });

        // Load pending callbacks count
        function loadPendingCallbacksCount() {
            fetch('{{ route('subadmin.dashboard.pending-callbacks') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cardCount = document.querySelector('.dashboard-card[onclick="openPendingCallbacksModal()"] .card-count');
                        if (cardCount) {
                            cardCount.textContent = data.total_count || 0;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading pending callbacks count:', error);
                    const cardCount = document.querySelector('.dashboard-card[onclick="openPendingCallbacksModal()"] .card-count');
                    if (cardCount) {
                        cardCount.textContent = '0';
                    }
                });
        }

        // Pending Callbacks Functions
        function openPendingCallbacksModal() {
            document.getElementById('pendingCallbacksModal').style.display = 'flex';
            loadPendingCallbacksData();
        }

        // Store all pending callbacks data globally
        let allPendingCallbacksData = null;
        let currentPendingCallbacksFilter = 'all';

        function loadPendingCallbacksData() {
            const container = document.getElementById('pendingCallbacksTableContainer');
            container.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner-modal"></div>
                    <p style="margin-top: 16px; color: #6B7280;">Loading pending callbacks...</p>
                </div>
            `;

            fetch('{{ route('subadmin.dashboard.pending-callbacks') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allPendingCallbacksData = data;
                        updatePendingCallbacksDisplay(data, currentPendingCallbacksFilter);
                    } else {
                        container.innerHTML = `
                            <div style="text-align: center; padding: 40px; color: #dc2626;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                                <p>Error loading pending callbacks.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading pending callbacks:', error);
                    container.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #dc2626;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                            <p>Error loading pending callbacks.</p>
                        </div>
                    `;
                });
        }

        function filterPendingCallbacksByType(filterType) {
            currentPendingCallbacksFilter = filterType;
            
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`[data-filter="${filterType}"]`).classList.add('active');
            
            if (allPendingCallbacksData) {
                updatePendingCallbacksDisplay(allPendingCallbacksData, filterType);
            }
        }

        function updatePendingCallbacksDisplay(data, filterType = 'all') {
            // Filter callbacks based on call_for
            let filteredCallbacks = data.callbacks || [];
            if (filterType !== 'all') {
                filteredCallbacks = filteredCallbacks.filter(callback => callback.call_for === filterType);
            }

            // Update summary cards
            document.getElementById('totalPendingCallbacks').textContent = filteredCallbacks.length;

            // Generate table
            const container = document.getElementById('pendingCallbacksTableContainer');
            
            let tableHTML = `
                <table class="leads-table">
                    <thead>
                        <tr>
                            <th>Lead No</th>
                            <th>Call For</th>
                            <th>Executive Name</th>
                            <th>Unanswered Date & Time</th>
                            <th>Customer Name</th>
                            <th>Customer Number</th>
                            <th>Lead Source</th>
                            <th>Call Status</th>
                            <th>Call Response Time & Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            if (!filteredCallbacks || filteredCallbacks.length === 0) {
                tableHTML += `
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 60px 20px;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 16px;">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 6L9 17L4 12" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="10" stroke="#059669" stroke-width="2" fill="none"/>
                                </svg>
                                <div>
                                    <div style="font-size: 18px; font-weight: 600; color: #1F2937; margin-bottom: 8px;">
                                        No pending callbacks found${filterType !== 'all' ? ' for ' + (filterType === 'operation_lead' ? 'Operation Lead' : filterType === 'job_request' ? 'Job Request' : 'Lead') : ''}
                                    </div>
                                    <div style="font-size: 14px; color: #6B7280;">
                                        Callbacks will appear here when there are missed, busy, or failed calls
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                filteredCallbacks.forEach(callback => {
                    const unansweredDateTime = callback.call_datetime ? new Date(callback.call_datetime).toLocaleString('en-GB', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    }) : 'N/A';

                    const responseDateTime = callback.response_datetime ? new Date(callback.response_datetime).toLocaleString('en-GB', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    }) : 'N/A';

                    const viewUrl = callback.view_url || '#';
                    const leadId = callback.operation_lead_id || callback.lead_id; // For lead type this is sales lead id
                    
                    // Determine call_for badge color
                    const callForDisplay = callback.call_for_display || callback.call_for || 'N/A';
                    const callForClass = callback.call_for === 'operation_lead' ? 'primary' : 
                                        callback.call_for === 'job_request' ? 'success' : 
                                        callback.call_for === 'lead' ? 'info' : 'secondary';
                    
                    // Action button: Lead = Edit (opens modal), Operation Lead = View, Job Request = no button (or View if view_url set)
                    let actionCellHtml = '';
                    if (callback.call_for === 'lead' && leadId) {
                        actionCellHtml = `<button type="button" class="btn action-btn edit-btn" onclick="openEditLeadModalFromPendingCallbacks(${leadId})" title="Edit Lead">
                            <i class="fas fa-edit"></i>
                        </button>`;
                    } else if (callback.call_for === 'operation_lead') {
                        actionCellHtml = `<a href="${viewUrl}" class="btn action-btn view-btn" title="View Lead" target="_blank">
                            <i class="fas fa-eye"></i>
                        </a>`;
                    } else if (callback.call_for === 'job_request' && viewUrl && viewUrl !== '#') {
                        actionCellHtml = `<a href="${viewUrl}" class="btn action-btn view-btn" title="View" target="_blank">
                            <i class="fas fa-eye"></i>
                        </a>`;
                    } else {
                        actionCellHtml = '<span class="text-muted">-</span>';
                    }
                    
                    // Original call status (Missed/Busy/Failed) - always show; after callback backend may send original_call_status
                    const rawStatus = (callback.call_status || '').toString().toLowerCase();
                    const isCallbackStatus = rawStatus === 'callback';
                    const originalStatusText = (callback.original_call_status || (isCallbackStatus ? 'Missed' : callback.call_status) || 'N/A').toString();
                    const originalStatus = originalStatusText.toLowerCase();
                    const originalStatusDisplay = originalStatusText.charAt(0).toUpperCase() + originalStatusText.slice(1);
                    const originalStatusClass = originalStatus === 'missed' || originalStatus === 'no answer' || originalStatus === 'noanswer' ? 'danger' :
                                              originalStatus === 'busy' ? 'warning' :
                                              originalStatus === 'failed' ? 'secondary' : 'warning';
                    const originalBadgeHtml = `<span class="badge bg-${originalStatusClass}" style="text-transform: capitalize;">${originalStatusDisplay}</span>`;
                    // If already callback done, show BOTH original status AND Callback status
                    const isCallbackDone = isCallbackStatus || (callback.response_datetime && callback.response_datetime !== '');
                    const callStatusBadge = isCallbackDone
                        ? originalBadgeHtml + ' <span class="badge bg-success ms-1" style="text-transform: capitalize;"><i class="fas fa-phone-volume"></i> Callback</span>'
                        : originalBadgeHtml;
                    const customerNumberCellContent = isCallbackDone
                        ? `<div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                <span style="font-weight: 500;">${callback.customer_number}</span>
                                <span class="badge bg-secondary" style="font-size: 11px;"><i class="fas fa-check"></i> Called back</span>
                           </div>`
                        : `<div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                <span style="font-weight: 500;">${callback.customer_number}</span>
                                <button onclick="makeCallFromDashboard('${callback.customer_number}')" 
                                        class="btn btn-sm btn-success" 
                                        style="padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                    <i class="fas fa-phone"></i> Call Back
                                </button>
                           </div>`;

                    tableHTML += `
                        <tr data-customer-number="${(callback.customer_number || '').replace(/"/g, '&quot;')}" data-original-call-status="${(originalStatusDisplay || '').replace(/"/g, '&quot;')}">
                            <td><strong>${callback.lead_no}</strong></td>
                            <td><span class="badge bg-${callForClass}">${callForDisplay}</span></td>
                            <td>${callback.executive_name || 'N/A'}</td>
                            <td>${unansweredDateTime}</td>
                            <td>${callback.customer_name}</td>
                            <td>${customerNumberCellContent}</td>
                            <td>${callback.lead_source || 'N/A'}</td>
                            <td>${callStatusBadge}</td>
                            <td>${responseDateTime}</td>
                            <td>${actionCellHtml}</td>
                        </tr>
                    `;
                });
            }

            tableHTML += '</tbody></table>';
            container.innerHTML = tableHTML;
        }

        // Open Edit Lead modal from Pending Callbacks (Sales Lead) - fetch lead data and show modal
        function openEditLeadModalFromPendingCallbacks(leadId) {
            if (!leadId) return;
            document.getElementById('editLeadModalDashboard').style.display = 'flex';
            const body = document.querySelector('#editLeadModalDashboard .modal-body');
            const origContent = body.innerHTML;
            body.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner-modal"></div><p style="margin-top: 16px; color: #6B7280;">Loading lead...</p></div>';

            fetch('/subadmin/leads/' + leadId + '/edit', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load lead');
                    return response.json();
                })
                .then(function(response) {
                    body.innerHTML = origContent;
                    populateEditLeadFormDashboard(response);
                })
                .catch(function(err) {
                    console.error('Error loading lead:', err);
                    if (typeof toastr !== 'undefined') toastr.error('Error loading lead');
                    else alert('Error loading lead');
                    closeModal('editLeadModalDashboard');
                });
        }

        // Populate edit lead modal body (restore form if needed) - called after fetch
        function populateEditLeadFormDashboard(response) {
            document.getElementById('edit_lead_id_dashboard').value = response.id;
            document.getElementById('edit_executive_dashboard').value = response.executive || '';
            document.getElementById('edit_customer_name_dashboard').value = response.customer_name || '';
            document.getElementById('edit_patient_name_dashboard').value = response.patient_name || '';
            document.getElementById('edit_patient_gender_dashboard').value = response.patient_gender || '';
            document.getElementById('edit_age_dashboard').value = response.age || '';
            document.getElementById('edit_contact_type_dashboard').value = response.contact_type || '';
            document.getElementById('edit_contact_no_dashboard').value = response.contact_no || '';
            document.getElementById('edit_lead_source_dashboard').value = response.lead_source || '';
            $('#edit_location_dashboard').val(response.location || '').trigger('change');
            document.getElementById('edit_query_dashboard').value = response.query || '';
            document.getElementById('edit_query_remarks_dashboard').value = response.query_remarks || '';
            document.getElementById('edit_status_dashboard').value = response.status || '';
            document.getElementById('edit_stage_dashboard').value = response.stage || '';
            document.getElementById('edit_shift_type_dashboard').value = response.shift_type || '';
            document.getElementById('edit_future_prospect_date_dashboard').value = response.future_prospect_date || '';
            document.getElementById('edit_prospect_rate_dashboard').value = response.prospect_rate || '';
            var qrGroup = document.getElementById('edit_query_remarks_group_dashboard');
            var fpGroup = document.getElementById('edit_future_prospect_date_group_dashboard');
            var prGroup = document.getElementById('edit_prospect_close_rate_group_dashboard');
            if (qrGroup) qrGroup.style.display = (response.query && response.query !== 'job request') ? 'block' : 'none';
            if (fpGroup) fpGroup.style.display = (response.status === 'future prospect') ? 'block' : 'none';
            if (prGroup) prGroup.style.display = (response.status === 'prospect') ? 'block' : 'none';
        }

        // Update Lead submit (dashboard edit modal) - bind once on DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            var updateBtn = document.getElementById('updateLeadDashboard');
            if (updateBtn) {
                updateBtn.addEventListener('click', function() {
                    var form = document.getElementById('editLeadFormDashboard');
                    if (!form) return;
                    var leadId = document.getElementById('edit_lead_id_dashboard').value;
                    if (!leadId) return;
                    var formData = new FormData(form);
                    var data = {};
                    formData.forEach(function(value, key) { data[key] = value; });
                    if (data.status !== 'future prospect') data.future_prospect_date = '';
                    if (data.status !== 'prospect') data.prospect_rate = '';
                    updateBtn.disabled = true;
                    updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                    fetch('/subadmin/leads/' + leadId, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data),
                        credentials: 'same-origin'
                    })
                        .then(function(r) { return r.json().catch(function() { return {}; }); })
                        .then(function(res) {
                            if (typeof toastr !== 'undefined') toastr.success(res.message || 'Lead updated successfully');
                            else alert(res.message || 'Lead updated successfully');
                            closeModal('editLeadModalDashboard');
                            if (allPendingCallbacksData) loadPendingCallbacksData();
                        })
                        .catch(function(err) {
                            if (typeof toastr !== 'undefined') toastr.error('Error updating lead');
                            else alert('Error updating lead');
                        })
                        .finally(function() {
                            updateBtn.disabled = false;
                            updateBtn.innerHTML = '<i class="fas fa-save me-1"></i> Update Lead';
                        });
                });
            }
            // Show/hide conditional fields in dashboard edit form
            var statusSel = document.getElementById('edit_status_dashboard');
            var querySel = document.getElementById('edit_query_dashboard');
            if (statusSel) {
                statusSel.addEventListener('change', function() {
                    var v = this.value;
                    var fp = document.getElementById('edit_future_prospect_date_group_dashboard');
                    var pr = document.getElementById('edit_prospect_close_rate_group_dashboard');
                    if (fp) fp.style.display = (v === 'future prospect') ? 'block' : 'none';
                    if (pr) pr.style.display = (v === 'prospect') ? 'block' : 'none';
                });
            }
            if (querySel) {
                querySel.addEventListener('change', function() {
                    var qr = document.getElementById('edit_query_remarks_group_dashboard');
                    if (qr) qr.style.display = (this.value && this.value !== 'job request') ? 'block' : 'none';
                });
            }
        });

        // Make call from dashboard - update row in place (don't remove), show Callback status and response time
        function makeCallFromDashboard(customerNumber) {
            if (!confirm('Are you sure you want to call this number?')) return;
            
            const event = window.event;
            const btn = event.target.closest('.btn-success');
            if (!btn) return;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calling...';
            
            const row = btn.closest('tr');
            
            fetch(`/call-outbound/${customerNumber}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Call initiated successfully');
                    // Update row in place: show BOTH original status AND Callback, and Call Response Time & Date (do not remove row)
                    if (row && row.cells && row.cells.length >= 10) {
                        const now = new Date();
                        const callbackDateTime = now.toLocaleString('en-GB', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        const originalStatus = (row.getAttribute('data-original-call-status') || 'Missed').toLowerCase();
                        const origClass = originalStatus === 'missed' || originalStatus === 'no answer' || originalStatus === 'noanswer' ? 'danger' :
                                         originalStatus === 'busy' ? 'warning' :
                                         originalStatus === 'failed' ? 'secondary' : 'warning';
                        const origDisplay = (row.getAttribute('data-original-call-status') || 'Missed').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
                        // Cell index 7 = Call Status (show old + new), 8 = Call Response Time & Date, 5 = Customer Number
                        row.cells[7].innerHTML = '<span class="badge bg-' + origClass + '" style="text-transform: capitalize;">' + origDisplay + '</span> <span class="badge bg-success ms-1" style="text-transform: capitalize;"><i class="fas fa-phone-volume"></i> Callback</span>';
                        row.cells[8].textContent = callbackDateTime;
                        // Replace Call Back button with "Called back" badge
                        row.cells[5].innerHTML = '<div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">' +
                            '<span style="font-weight: 500;">' + customerNumber + '</span>' +
                            '<span class="badge bg-secondary" style="font-size: 11px;"><i class="fas fa-check"></i> Called back</span>' +
                            '</div>';
                    }
                } else {
                    toastr.error(data.message || 'Failed to initiate call');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('Error initiating call. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            })
            .finally(() => {
                // Restore button only if call failed (row was not updated)
                if (row && row.cells[5] && row.cells[5].querySelector('button.btn-success')) {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            });
        }

        // Recent Calls Modal Functions
        let currentCallsTab = 'sales';
        let allRecentCallsData = null; // Store all calls data for filtering

        function openRecentCallsModal() {
            document.getElementById('recentCallsModal').style.display = 'flex';
            loadRecentCallsData();
        }

        function switchCallsTab(tabName) {
            currentCallsTab = tabName;
            
            // Update active tab
            document.querySelectorAll('.calls-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
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

            const route = currentCallsTab === 'sales' ? 
                '{{ route("admin.dashboard.recent-calls.sales") }}' : 
                '{{ route("admin.dashboard.recent-calls.operation") }}';

            fetch(route)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Store all data for filtering
                        allRecentCallsData = data;
                        updateRecentCallsDisplay(data);
                    } else {
                        container.innerHTML = `
                            <div style="text-align: center; padding: 40px; color: #dc2626;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                                <p>Error: ${data.error || 'Failed to load data'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading recent calls:', error);
                    container.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #dc2626;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 16px;"></i>
                            <p>Error loading data. Please try again.</p>
                        </div>
                    `;
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
                            <th>Executive</th>
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
                                        call.call_for === 'job_request' ? 'success' : 
                                        call.call_for === 'lead' ? 'info' : 'secondary';
                    
                    tableHTML += `
                        <tr>
                            <td>${call.call_datetime}</td>
                            <td>${leadNoDisplay}</td>
                            <td><span class="badge bg-${callForClass}">${callForDisplay}</span></td>
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
            }

            tableHTML += '</tbody></table>';
            container.innerHTML = tableHTML;
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
            // Load count for both sales and operation calls
            Promise.all([
                fetch('{{ route("admin.dashboard.recent-calls.sales") }}'),
                fetch('{{ route("admin.dashboard.recent-calls.operation") }}')
            ])
            .then(responses => Promise.all(responses.map(r => r.json())))
            .then(data => {
                // Get unique phone numbers from both sales and operation calls
                const allCalls = [];
                if (data[0].success && data[0].calls) {
                    allCalls.push(...data[0].calls);
                }
                if (data[1].success && data[1].calls) {
                    allCalls.push(...data[1].calls);
                }
                
                // Count unique phone numbers
                const uniqueNumbers = new Set();
                allCalls.forEach(call => {
                    if (call.customer_number) {
                        uniqueNumbers.add(call.customer_number);
                    }
                });
                
                document.getElementById('recentCallsCount').textContent = uniqueNumbers.size;
            })
            .catch(error => {
                console.error('Error loading recent calls count:', error);
            });
        }

        // Load recent calls count on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentCallsCount();
        });
    </script>
@endsection
@endsection

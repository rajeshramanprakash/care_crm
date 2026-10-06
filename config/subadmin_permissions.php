<?php

/*
|--------------------------------------------------------------------------
| Sub Admin permissions
|--------------------------------------------------------------------------
|
| `modules` drives the permission matrix on Admin > Users (Sub Admin role) and
| the list of permissions that must exist in the database.
|
| `routes` maps every `subadmin.*` route name to the permission it needs.
| Patterns use Str::is() and the FIRST match wins, so keep specific names
| above wildcards. A subadmin route that matches nothing is denied.
|
*/

return [

    'modules' => [
        'Dashboard' => ['access' => 'view_dashboard'],
        'Payments' => ['access' => 'view_payments', 'create' => 'create_payment'],
        'Leads' => [
            'access' => 'view_leads',
            'view_details' => 'view_lead_details',
            'create' => 'create_lead',
            'edit' => 'edit_lead',
            'delete' => 'delete_lead',
        ],
        'Sales & Operation Referral Leads' => ['access' => 'view_referral_leads', 'edit' => 'edit_referral_leads'],
        'Users' => ['access' => 'view_user', 'create' => 'create_user', 'edit' => 'edit_user', 'delete' => 'delete_user'],
        'B2B Users' => ['access' => 'view_b2b_users', 'create' => 'create_b2b_users', 'edit' => 'edit_b2b_users', 'delete' => 'delete_b2b_users'],
        'Corporate / Individual Hub' => ['access' => 'view_b2b_hub'],
        'B2B Corporate' => ['access' => 'view_b2b_corporate', 'create' => 'create_b2b_corporate', 'edit' => 'edit_b2b_corporate', 'delete' => 'delete_b2b_corporate'],
        'Individual' => ['access' => 'view_b2b_individual', 'create' => 'create_b2b_individual', 'edit' => 'edit_b2b_individual', 'delete' => 'delete_b2b_individual'],
        'Corporate Accounts & Employees' => ['access' => 'view_corporate_accounts'],
        'Insurers' => ['access' => 'view_insurers', 'create' => 'create_insurer', 'edit' => 'edit_insurer', 'delete' => 'delete_insurer'],
        'Brokers' => ['access' => 'view_brokers', 'create' => 'create_broker', 'edit' => 'edit_broker', 'delete' => 'delete_broker'],
        'Break Logs' => ['access' => 'view_break_logs'],
        'Duty Logs' => ['access' => 'view_duty_logs'],
        'Operation Leads' => [
            'access' => 'view_operation_leads',
            'view_details' => 'view_operation_leads_details',
            'create' => 'create_operation_leads',
            'edit' => 'edit_operation_leads',
            'delete' => 'delete_operation_leads',
        ],
        'Locations' => ['access' => 'view_locations', 'create' => 'create_locations', 'edit' => 'edit_locations', 'delete' => 'delete_locations'],
        'Services' => ['access' => 'view_services', 'create' => 'create_services', 'edit' => 'edit_services', 'delete' => 'delete_services'],
        'Doctor Verify No.' => ['access' => 'view_doctor_otp_logs'],
        'Doctor Requests' => ['access' => 'view_doctor_requests', 'edit' => 'edit_doctor_requests'],
        'Doctor Consultation Services' => [
            'access' => 'view_doctor_consultation_services',
            'create' => 'create_doctor_consultation_services',
            'edit' => 'edit_doctor_consultation_services',
            'delete' => 'delete_doctor_consultation_services',
        ],
        'Whatsapp' => ['access' => 'view_whatsapp'],
        'Vendor Verify No.' => ['access' => 'view_vendor_otp_logs'],
        'Vendors' => ['access' => 'view_vendors', 'create' => 'create_vendors', 'edit' => 'edit_vendors', 'delete' => 'delete_vendors'],
        'Vendor Payments' => ['access' => 'view_vendor_payments', 'create' => 'create_vendor_payments', 'edit' => 'edit_vendor_payments', 'delete' => 'delete_vendor_payments'],
        'Freelancer Verify No.' => ['access' => 'view_freelancer_otp_logs'],
        'Freelancer Payments' => ['access' => 'view_freelancer_payments', 'create' => 'create_freelancer_payments', 'edit' => 'edit_freelancer_payments', 'delete' => 'delete_freelancer_payments'],
        'Freelancer & Vendor Attendance' => ['access' => 'view_location_attendance'],
        'Job Request' => ['access' => 'view_job_requests', 'create' => 'create_job_requests', 'edit' => 'edit_job_requests', 'delete' => 'delete_job_requests'],
        'Bulk Price / Reg' => ['access' => 'view_bulk_registration', 'create' => 'create_bulk_registration'],
        'Technical Support' => ['access' => 'view_technical_support'],
        'Customer Feedback' => ['access' => 'view_customer_feedback', 'edit' => 'edit_customer_feedback'],
        'Support Tickets' => ['access' => 'view_support_tickets', 'edit' => 'edit_support_tickets'],
    ],

    'routes' => [
        // Dashboard (page + all widget data)
        'subadmin.dashboard' => 'view_dashboard',
        'subadmin.dashboard.*' => 'view_dashboard',

        // Payments
        'subadmin.payments.create' => 'create_payment',
        'subadmin.payments.store' => 'create_payment',
        'subadmin.payments.*' => 'view_payments',

        // Leads
        'subadmin.leads.show' => 'view_lead_details',
        'subadmin.leads.store' => 'create_lead',
        'subadmin.leads.edit' => 'edit_lead',
        'subadmin.leads.update' => 'edit_lead',
        'subadmin.leads.status-remarks.update' => 'edit_lead',
        'subadmin.leads.destroy' => 'delete_lead',
        'subadmin.leads.*' => 'view_leads',

        // Referral leads
        'subadmin.referral_leads.commission' => 'edit_referral_leads',
        'subadmin.referral_leads.*' => 'view_referral_leads',

        // Operation leads
        'subadmin.operation_leads.index' => 'view_operation_leads',
        'subadmin.operation_leads.getLeads' => 'view_operation_leads',
        'subadmin.operation_leads.filtered_vendors' => 'view_operation_leads',
        'subadmin.operation_leads.vendor_details' => 'view_operation_leads',
        'subadmin.operation_leads.updated_vendor_count' => 'view_operation_leads',
        'subadmin.operation_leads.show' => 'view_operation_leads_details',
        'subadmin.operation_leads.payment_invoice.show' => 'view_operation_leads_details',
        'subadmin.operation_leads.deployment.dates' => 'view_operation_leads_details',
        'subadmin.operation_leads.store' => 'create_operation_leads',
        'subadmin.operation_leads.destroy' => 'delete_operation_leads',
        'subadmin.operation_leads.*' => 'edit_operation_leads',

        // B2B users (+ their reference users)
        'subadmin.b2b_users.store' => 'create_b2b_users',
        'subadmin.b2b_users.update' => 'edit_b2b_users',
        'subadmin.b2b_users.destroy' => 'delete_b2b_users',
        'subadmin.b2b_users.*' => 'view_b2b_users',
        'subadmin.b2b_reference_users.store' => 'create_b2b_users',
        'subadmin.b2b_reference_users.destroy' => 'delete_b2b_users',

        // B2B corporate / individual / hub
        'subadmin.b2b_corporate.store' => 'create_b2b_corporate',
        'subadmin.b2b_corporate.update' => 'edit_b2b_corporate',
        'subadmin.b2b_corporate.destroy' => 'delete_b2b_corporate',
        'subadmin.b2b_corporate.*' => 'view_b2b_corporate',
        'subadmin.b2b_individual.store' => 'create_b2b_individual',
        'subadmin.b2b_individual.update' => 'edit_b2b_individual',
        'subadmin.b2b_individual.destroy' => 'delete_b2b_individual',
        'subadmin.b2b_individual.*' => 'view_b2b_individual',
        'subadmin.corporate_individual.*' => 'view_b2b_hub',
        'subadmin.corporate-accounts.*' => 'view_corporate_accounts',
        'subadmin.corporate-employees.*' => 'view_corporate_accounts',

        // Users (manage_process is resolved in the middleware: create vs edit)
        'subadmin.users.create' => 'create_user',
        'subadmin.users.store' => 'create_user',
        'subadmin.users.manage' => 'edit_user',
        'subadmin.users.edit' => 'edit_user',
        'subadmin.users.update' => 'edit_user',
        'subadmin.users.destroy' => 'delete_user',
        'subadmin.users.index' => 'view_user',
        'subadmin.users.getUsers' => 'view_user',
        'subadmin.users.show' => 'view_user',

        // Insurers / brokers
        'subadmin.insurers.create' => 'create_insurer',
        'subadmin.insurers.store' => 'create_insurer',
        'subadmin.insurers.edit' => 'edit_insurer',
        'subadmin.insurers.update' => 'edit_insurer',
        'subadmin.insurers.destroy' => 'delete_insurer',
        'subadmin.insurers.*' => 'view_insurers',
        'subadmin.brokers.create' => 'create_broker',
        'subadmin.brokers.store' => 'create_broker',
        'subadmin.brokers.edit' => 'edit_broker',
        'subadmin.brokers.update' => 'edit_broker',
        'subadmin.brokers.destroy' => 'delete_broker',
        'subadmin.brokers.*' => 'view_brokers',

        // Services
        'subadmin.services.create' => 'create_services',
        'subadmin.services.store' => 'create_services',
        'subadmin.services.edit' => 'edit_services',
        'subadmin.services.update' => 'edit_services',
        'subadmin.services.destroy' => 'delete_services',
        'subadmin.services.*' => 'view_services',

        // Locations
        'subadmin.locations.create' => 'create_locations',
        'subadmin.locations.store' => 'create_locations',
        'subadmin.locations.bulk-import' => 'create_locations',
        'subadmin.locations.edit' => 'edit_locations',
        'subadmin.locations.update' => 'edit_locations',
        'subadmin.locations.destroy' => 'delete_locations',
        'subadmin.locations.*' => 'view_locations',

        // Logs / support
        'subadmin.break_logs.*' => 'view_break_logs',
        'subadmin.duty_logs.*' => 'view_duty_logs',
        'subadmin.technical-support.*' => 'view_technical_support',
        'subadmin.doctor_registration_otp_logs.*' => 'view_doctor_otp_logs',
        'subadmin.vendor_registration_otp_logs.*' => 'view_vendor_otp_logs',
        'subadmin.freelancer_registration_otp_logs.*' => 'view_freelancer_otp_logs',
        'subadmin.location_attendance.*' => 'view_location_attendance',
        'subadmin.all_whatsapp_chats.*' => 'view_whatsapp',

        // Bulk registration
        'subadmin.bulk_registration.store' => 'create_bulk_registration',
        'subadmin.bulk_registration.*' => 'view_bulk_registration',

        // Doctor requests
        'subadmin.doctor_requests.index' => 'view_doctor_requests',
        'subadmin.doctor_requests.data' => 'view_doctor_requests',
        'subadmin.doctor_requests.view_modal' => 'view_doctor_requests',
        'subadmin.doctor_requests.show' => 'view_doctor_requests',
        'subadmin.doctor_requests.website_reviews_panel' => 'view_doctor_requests',
        'subadmin.doctor_requests.price_change_requests' => 'view_doctor_requests',
        'subadmin.doctor_requests.pricing_logs' => 'view_doctor_requests',
        'subadmin.doctor_requests.leegality_preview_agreement' => 'view_doctor_requests',
        'subadmin.doctor_requests.leegality_view_signed' => 'view_doctor_requests',
        'subadmin.doctor_requests.leegality_download_signed' => 'view_doctor_requests',
        'subadmin.doctor_requests.leegality_download_audit' => 'view_doctor_requests',
        'subadmin.doctor_requests.*' => 'edit_doctor_requests',
        'subadmin.doctor_referral_users.*' => 'edit_doctor_requests',

        // Doctor consultation services
        'subadmin.doctor_consultation_services.create' => 'create_doctor_consultation_services',
        'subadmin.doctor_consultation_services.store' => 'create_doctor_consultation_services',
        'subadmin.doctor_consultation_services.edit' => 'edit_doctor_consultation_services',
        'subadmin.doctor_consultation_services.update' => 'edit_doctor_consultation_services',
        'subadmin.doctor_consultation_services.destroy' => 'delete_doctor_consultation_services',
        'subadmin.doctor_consultation_services.*' => 'view_doctor_consultation_services',

        // Vendors
        'subadmin.vendors.store' => 'create_vendors',
        'subadmin.vendors.edit' => 'edit_vendors',
        'subadmin.vendors.update' => 'edit_vendors',
        'subadmin.vendors.price_change_approve' => 'edit_vendors',
        'subadmin.vendors.price_change_reject' => 'edit_vendors',
        'subadmin.vendors.leegality_send' => 'edit_vendors',
        'subadmin.vendors.leegality_refresh' => 'edit_vendors',
        'subadmin.vendors.leegality_add_my_signature' => 'edit_vendors',
        'subadmin.vendors.destroy' => 'delete_vendors',
        'subadmin.vendors.*' => 'view_vendors',

        // Vendor payments
        'subadmin.vendor_payments.store' => 'create_vendor_payments',
        'subadmin.vendor_payments.edit' => 'edit_vendor_payments',
        'subadmin.vendor_payments.update' => 'edit_vendor_payments',
        'subadmin.vendor_payments.destroy' => 'delete_vendor_payments',
        'subadmin.vendor_payments.*' => 'view_vendor_payments',

        // Freelancer payments
        'subadmin.freelancer_payments.store' => 'create_freelancer_payments',
        'subadmin.freelancer_payments.edit' => 'edit_freelancer_payments',
        'subadmin.freelancer_payments.update' => 'edit_freelancer_payments',
        'subadmin.freelancer_payments.destroy' => 'delete_freelancer_payments',
        'subadmin.freelancer_payments.*' => 'view_freelancer_payments',

        // Job requests (+ freelancer Leegality agreements)
        'subadmin.jobproc.store' => 'create_job_requests',
        'subadmin.jobproc.import' => 'create_job_requests',
        'subadmin.jobproc.destroy' => 'delete_job_requests',
        'subadmin.jobproc.index' => 'view_job_requests',
        'subadmin.jobproc.jobrequests' => 'view_job_requests',
        'subadmin.jobproc.prospects' => 'view_job_requests',
        'subadmin.jobproc.show' => 'view_job_requests',
        'subadmin.jobproc.price_change_requests' => 'view_job_requests',
        'subadmin.jobproc.leegality_preview_agreement' => 'view_job_requests',
        'subadmin.jobproc.leegality_view_signed' => 'view_job_requests',
        'subadmin.jobproc.leegality_download_signed' => 'view_job_requests',
        'subadmin.jobproc.leegality_download_audit' => 'view_job_requests',
        'subadmin.jobproc.*' => 'edit_job_requests',

        // Customer feedback / support tickets
        'subadmin.customer_feedback.update' => 'edit_customer_feedback',
        'subadmin.customer_feedback.*' => 'view_customer_feedback',
        'subadmin.support_tickets.reply' => 'edit_support_tickets',
        'subadmin.support_tickets.update' => 'edit_support_tickets',
        'subadmin.support_tickets.desk_sync' => 'edit_support_tickets',
        'subadmin.support_tickets.*' => 'view_support_tickets',
    ],

    // Sidebar order used to send a Sub Admin without dashboard access to the first page they may open.
    'landing_routes' => [
        'view_dashboard' => 'subadmin.dashboard',
        'view_payments' => 'subadmin.payments.index',
        'view_leads' => 'subadmin.leads.index',
        'view_referral_leads' => 'subadmin.referral_leads.index',
        'view_user' => 'subadmin.users.index',
        'view_b2b_users' => 'subadmin.b2b_users.index',
        'view_b2b_hub' => 'subadmin.corporate_individual.hub',
        'view_b2b_corporate' => 'subadmin.b2b_corporate.index',
        'view_b2b_individual' => 'subadmin.b2b_individual.index',
        'view_insurers' => 'subadmin.insurers.index',
        'view_brokers' => 'subadmin.brokers.index',
        'view_corporate_accounts' => 'subadmin.corporate-accounts.index',
        'view_break_logs' => 'subadmin.break_logs.index',
        'view_duty_logs' => 'subadmin.duty_logs.index',
        'view_operation_leads' => 'subadmin.operation_leads.index',
        'view_locations' => 'subadmin.locations.index',
        'view_services' => 'subadmin.services.index',
        'view_doctor_otp_logs' => 'subadmin.doctor_registration_otp_logs.index',
        'view_doctor_requests' => 'subadmin.doctor_requests.index',
        'view_doctor_consultation_services' => 'subadmin.doctor_consultation_services.index',
        'view_whatsapp' => 'subadmin.all_whatsapp_chats.index',
        'view_vendor_otp_logs' => 'subadmin.vendor_registration_otp_logs.index',
        'view_vendors' => 'subadmin.vendors.index',
        'view_vendor_payments' => 'subadmin.vendor_payments.index',
        'view_freelancer_otp_logs' => 'subadmin.freelancer_registration_otp_logs.index',
        'view_freelancer_payments' => 'subadmin.freelancer_payments.index',
        'view_location_attendance' => 'subadmin.location_attendance.index',
        'view_job_requests' => 'subadmin.jobproc.index',
        'view_bulk_registration' => 'subadmin.bulk_registration.index',
        'view_technical_support' => 'subadmin.technical-support.index',
        'view_customer_feedback' => 'subadmin.customer_feedback.index',
        'view_support_tickets' => 'subadmin.support_tickets.index',
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Leegality Document Execution
    |--------------------------------------------------------------------------
    | Auth token: Leegality Dashboard → Settings / API.
    | Profile ID: published Workflow ID used for doctor agreements.
    | Webhook URL to set on the workflow invitee:
    |   {APP_URL}/api/leegality/webhook
    */
    'auth_token' => env('LEEGALITY_AUTH_TOKEN', ''),
    'base_url' => rtrim(env('LEEGALITY_BASE_URL', 'https://app1.leegality.com/api'), '/'),
    'profile_id' => env('LEEGALITY_PROFILE_ID', ''),
    'private_salt' => env('LEEGALITY_PRIVATE_SALT', ''),
    'verify_mac' => filter_var(env('LEEGALITY_VERIFY_MAC', false), FILTER_VALIDATE_BOOLEAN),

    // Optional static PDF (absolute or storage-relative). Empty = generate DomPDF agreement.
    'agreement_pdf_path' => env('LEEGALITY_AGREEMENT_PDF_PATH', ''),

    // When true, do not attach PDF bytes (use template/workflow document only).
    'template_only' => filter_var(env('LEEGALITY_TEMPLATE_ONLY', false), FILTER_VALIDATE_BOOLEAN),

    'document_name_prefix' => env('LEEGALITY_DOCUMENT_NAME_PREFIX', 'Doctor_Agreement'),
    'timeout_seconds' => (int) env('LEEGALITY_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Authorised Signatory Configuration
    |--------------------------------------------------------------------------
    */
    'authorised_signatory_name' => env('LEEGALITY_AUTHORISED_SIGNATORY_NAME', env('LEEGALITY_AUTHORISED_NAME', 'Ashish Ramniwas')),
    'authorised_signatory_email' => env('LEEGALITY_AUTHORISED_SIGNATORY_EMAIL', env('LEEGALITY_AUTHORISED_EMAIL', 'it1.carelix@gmail.com')),
    'authorised_signatory_phone' => env('LEEGALITY_AUTHORISED_SIGNATORY_PHONE', env('LEEGALITY_AUTHORISED_PHONE', '9654639319')),

    'automated_signer_id' => env('LEEGALITY_AUTOMATED_SIGNER_ID', ''),
    'automated_signer_passkey' => env('LEEGALITY_AUTOMATED_SIGNER_PASSKEY', ''),
    'auth_profile_id' => env('LEEGALITY_AUTH_PROFILE_ID', ''),

    'appearance_x1' => (int) env('LEEGALITY_APPEARANCE_X1', 40),
    'appearance_y1' => (int) env('LEEGALITY_APPEARANCE_Y1', 40),
    'appearance_x2' => (int) env('LEEGALITY_APPEARANCE_X2', 200),
    'appearance_y2' => (int) env('LEEGALITY_APPEARANCE_Y2', 100),
];

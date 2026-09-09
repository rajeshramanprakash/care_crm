<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'mcube' => [
        'token' => env('MCUBE_TOKEN'),
    ],

    'whatsapp' => [
        'status' => env('WHATSAPP_MSG_STATUS', false),
        'auth_key' => env('WHATSAPP_AUTH_KEY'),
    ],

    /** WABA — login OTP via WhatsApp template (api.waba.anohim.in). */
    'waba' => [
        'api_url' => env('WABA_API_URL', 'https://api.waba.anohim.in/api/v1/send-template'),
        'api_key' => env('WABA_API_KEY'),
        'login_template' => env('WABA_LOGIN_OTP_TEMPLATE', 'login_otpp'),
        'login_template_language' => env('WABA_LOGIN_OTP_LANGUAGE', 'en_US'),
        'country_code' => env('WABA_DEFAULT_COUNTRY_CODE', '91'),
    ],

    'tata' => [
        'token' => env('TATA_API_TOKEN'),
        'caller_ids' => explode(',', env('TATA_CALLER_IDS', '')),
        'user_role' => env('TATA_USER_ROLE'),
    ],

    'api_relay' => [
        'enabled' => (bool) env('API_RELAY_ENABLED', false),
        'url' => env('API_RELAY_URL'),
        'target_param' => env('API_RELAY_TARGET_PARAM', 'target_url'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        /** Use a current model id; fallbacks are tried automatically in App\Services\GeminiService */
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    'chat_ws' => [
        'crm_enabled' => (bool) env('CRM_CHAT_WS', true),
        'whatsapp_enabled' => (bool) env('WHATSAPP_CHAT_WS', true),
        /*
         * Browser Reverb/Pusher client. Off by default on local so the console is not
         * flooded when `php artisan reverb:start` is not running. Set REVERB_CLIENT_ENABLED=true
         * when the Reverb server is up (or in production with BROADCAST_DRIVER=reverb).
         */
        'reverb_client_enabled' => filter_var(
            env(
                'REVERB_CLIENT_ENABLED',
                env('BROADCAST_DRIVER', 'log') === 'reverb' && env('APP_ENV', 'production') !== 'local'
            ),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],

    'missed_callback' => [
        'enabled' => (bool) env('MISSED_CALLBACK_ENABLED', true),
        /** Local/testing: log only — do not call Tata click-to-call (OutboundCall). */
        'dry_run' => (bool) env('MISSED_CALLBACK_DRY_RUN', false),
        'min_answer_duration_seconds' => (int) env('MISSED_CALLBACK_MIN_ANSWER_SECONDS', 10),
        /** Minimum talk seconds on outbound MCB click-to-call to count as answered and stop the sequence (separate from general CRM thresholds). */
        'flow_min_answer_seconds' => (int) env('MISSED_CALLBACK_FLOW_MIN_ANSWER_SECONDS', 10),
        'stale_disposition_minutes' => (int) env('MISSED_CALLBACK_STALE_MINUTES', 25),
        'queue' => env('MISSED_CALLBACK_QUEUE'),
    ],

    /** Website consultation booking — calendar / checkout. */
    'consultation' => [
        /** Fresh pending_payment rows block the slot for this many minutes during checkout. */
        'pending_payment_hold_minutes' => (int) env('CONSULTATION_PENDING_PAYMENT_HOLD_MINUTES', 15),
        /** IVR / helpline for CareWeb “Call RM” (not operation executive’s personal mobile). */
        'website_ivr_phone' => env('CONSULTATION_WEBSITE_IVR_PHONE', '7666426664'),
    ],

    /** Website consultation booking — Easebuzz hosted pay (test: testpay.easebuzz.in). */
    'easebuzz' => [
        'key' => env('EASEBUZZ_KEY'),
        'salt' => env('EASEBUZZ_SALT'),
        'base_url' => env('EASEBUZZ_BASE_URL', 'https://testpay.easebuzz.in/'),
        /** Valid email for gateway validation when the patient form has no email. */
        'placeholder_email' => env('EASEBUZZ_PLACEHOLDER_EMAIL', 'payments@example.com'),
    ],

    /** CRM EasyCollect payment links (sidebar Payments). Toggle demo vs live independently of consultation. */
    'easycollect' => (static function (): array {
        $demo = filter_var(env('EASEBUZZ_EASYCOLLECT_DEMO', true), FILTER_VALIDATE_BOOL);

        $key = $demo
            ? trim((string) env('EASEBUZZ_KEY_DEMO', ''))
            : trim((string) env('EASEBUZZ_KEY', ''));
        $salt = $demo
            ? trim((string) env('EASEBUZZ_SALT_DEMO', ''))
            : trim((string) env('EASEBUZZ_SALT', ''));

        $dashboardBase = trim((string) env('EASEBUZZ_EASYCOLLECT_BASE_URL', ''));
        if ($dashboardBase === '') {
            $dashboardBase = $demo
                ? 'https://testdashboard.easebuzz.in'
                : (static function (): string {
                    $payBase = strtolower(rtrim((string) env('EASEBUZZ_BASE_URL', 'https://testpay.easebuzz.in/'), '/'));
                    if (str_contains($payBase, 'testpay')) {
                        return 'https://testdashboard.easebuzz.in';
                    }
                    if (str_contains($payBase, 'pay.easebuzz.in')) {
                        return 'https://dashboard.easebuzz.in';
                    }

                    return 'https://testdashboard.easebuzz.in';
                })();
        }

        return [
            'demo' => $demo,
            'key' => $key,
            'salt' => $salt,
            'dashboard_base_url' => rtrim($dashboardBase, '/'),
            'link_validity_days' => (int) env('EASEBUZZ_EASYCOLLECT_LINK_DAYS', 7),
        ];
    })(),

    /** MSG91 — doctor registration OTP via Flow API (DLT template). */
    'msg91' => [
        'auth_key' => env('MSG91_AUTH_KEY'),
        'flow_template_id' => env('MSG91_FLOW_TEMPLATE_ID', env('MSG91_OTP_TEMPLATE_ID')),
        'flow_otp_variable' => env('MSG91_FLOW_OTP_VARIABLE', 'otp'),
        'sender' => env('MSG91_SENDER', 'CRLXHC'),
        'otp_expiry_minutes' => (int) env('MSG91_OTP_EXPIRY_MINUTES', 10),
    ],

];

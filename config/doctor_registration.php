<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fluent language options (doctor registration)
    |--------------------------------------------------------------------------
    | Keys are stored in doctor_requests.fluent_languages; labels for UI.
    */
    'fluent_languages' => [
        'english' => 'English',
        'hindi' => 'Hindi',
        'telugu' => 'Telugu',
        'tamil' => 'Tamil',
        'marathi' => 'Marathi',
    ],

    'fluent_languages_note' => 'Select only languages you are fluent/confident in.',

    /*
    |--------------------------------------------------------------------------
    | “Generate with AI” for about section uses GEMINI_API_KEY (config/services.php)
    |--------------------------------------------------------------------------
    */
    'about_ai_min_details_chars' => 20,
];

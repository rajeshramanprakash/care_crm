<?php

return [
    'attendant_uniform_template_path' => env('FREELANCER_ATTENDANT_UNIFORM_PATH', 'assets/carelix-attendant-uniform-template.png'),

    'nurse_uniform_template_path' => env('FREELANCER_NURSE_UNIFORM_PATH', 'assets/carelix-nurse-uniform-template.png'),

    'image_model' => env('GEMINI_IMAGE_MODEL', 'gemini-2.5-flash-image'),

    'image_model_fallbacks' => [
        'gemini-2.5-flash-image',
        'gemini-3.1-flash-image-preview',
        'gemini-3-pro-image-preview',
    ],

    'attendant_service_match' => ['attendant'],

    'nurse_service_match' => ['nurse'],
];

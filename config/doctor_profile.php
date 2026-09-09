<?php

return [
    /** Storage path on public disk for the branded lab coat reference image. */
    'coat_template_path' => env('DOCTOR_COAT_TEMPLATE_PATH', 'assets/carelix-doctor-coat-template.png'),

    /** Gemini model for image generation / virtual coat overlay. */
    'image_model' => env('GEMINI_IMAGE_MODEL', 'gemini-2.5-flash-image'),

    'image_model_fallbacks' => [
        'gemini-2.5-flash-image',
        'gemini-3.1-flash-image-preview',
        'gemini-3-pro-image-preview',
    ],
];

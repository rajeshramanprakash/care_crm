<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice Company Address
    |--------------------------------------------------------------------------
    | Shown on payment invoice (top right sender block).
    */
    'company_address' => env('INVOICE_COMPANY_ADDRESS', '141, GMD Galleria, Sector 48, Sohna Road, Gurgaon, Haryana, India'),

    /*
    |--------------------------------------------------------------------------
    | GSTIN
    |--------------------------------------------------------------------------
    | Shown on payment invoice when set.
    */
    'gstin' => env('INVOICE_GSTIN', null),

];

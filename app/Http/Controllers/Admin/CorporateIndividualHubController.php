<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class CorporateIndividualHubController extends Controller
{
    public function index()
    {
        return view('admin.corporate_individual.hub', [
            'page_heading' => 'Corporate / Individual (B2B)',
        ]);
    }
}

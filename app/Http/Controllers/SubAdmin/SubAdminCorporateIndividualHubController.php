<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;

class SubAdminCorporateIndividualHubController extends Controller
{
    public function index()
    {
        return view('subadmin.corporate_individual.hub', [
            'page_heading' => 'Corporate / Individual (B2B)',
        ]);
    }
}

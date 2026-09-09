<?php

namespace App\Http\Controllers\B2B;

use App\Http\Controllers\Controller;
use App\Models\B2BUser;

class B2BController extends Controller
{
    public function dashboard()
    {
        return redirect()->route('b2b.leads.index');
    }
}


<?php

namespace App\Http\Controllers\TPA;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TpaController extends Controller
{
    public function dashboard(){
        return view('tpa.dashboard');
    }
}

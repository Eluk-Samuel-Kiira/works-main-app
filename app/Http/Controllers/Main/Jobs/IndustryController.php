<?php

namespace App\Http\Controllers\Main\Jobs;

use App\Http\Controllers\Controller;

class IndustryController extends Controller
{
    public function index()
    {
        return view('jobs.industries.index');
    }
}

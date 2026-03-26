<?php

namespace App\Http\Controllers\Main\Jobs;

use App\Http\Controllers\Controller;

class JobLocationController extends Controller
{
    public function index()
    {
        return view('jobs.job-locations.index');
    }
}

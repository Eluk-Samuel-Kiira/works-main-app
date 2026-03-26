<?php

namespace App\Http\Controllers\Main\Jobs;

use App\Http\Controllers\Controller;

class SalaryRangeController extends Controller
{
    public function index()
    {
        return view('jobs.salary-ranges.index');
    }
}

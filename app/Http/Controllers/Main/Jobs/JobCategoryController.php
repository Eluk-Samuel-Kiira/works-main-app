<?php

namespace App\Http\Controllers\Main\Jobs;

use App\Http\Controllers\Controller;

class JobCategoryController extends Controller
{
    public function index()
    {
        return view('jobs.job-categories.index');
    }
}

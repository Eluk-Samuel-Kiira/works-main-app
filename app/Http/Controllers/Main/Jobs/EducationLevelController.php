<?php

namespace App\Http\Controllers\Main\Jobs;

use App\Http\Controllers\Controller;

class EducationLevelController extends Controller
{
    public function index()
    {
        return view('jobs.education-levels.index');
    }
}

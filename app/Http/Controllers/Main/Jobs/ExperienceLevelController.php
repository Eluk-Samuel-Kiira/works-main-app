<?php

namespace App\Http\Controllers\Main\Jobs;

use App\Http\Controllers\Controller;

class ExperienceLevelController extends Controller
{
    public function index()
    {
        return view('jobs.experience-levels.index');
    }
}

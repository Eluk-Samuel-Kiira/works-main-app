<?php

namespace App\Http\Controllers\Main\Jobs;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index()
    {
        return view('jobs.users.index');
    }
}

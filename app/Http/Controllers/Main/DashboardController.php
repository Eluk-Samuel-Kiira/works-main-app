<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request) {
        return view('auth.login');
    }

    public function dashboard(Request $request) {
        return view('home.dashboard');
    }

    public function socialMediaPlatform()
    {
        return view('home.social-media.index');
    }

    public function aiPosting()
    {
        return view('jobs.job-posts.ai-posting');
    }
}

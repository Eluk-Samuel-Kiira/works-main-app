<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Job\JobPost;
use App\Models\Job\Company;
use App\Models\Job\JobCategory;
use App\Models\Job\JobLocation;

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

    public function whatsappDocs()
    {
        return view('home.whatsapp-docs.index');
    }

    public function blogs()
    {
        return view('blog.index');
    }

    public function createBlog()
    {
        return view('blog.create');
    }

    public function editBlog($id)
    {
        \Log::info('it is here sir');
        return view('blog.edit', ['blogId' => $id]);
    }

    /**
     * Get job counts by category for filter dropdown
     */
    public function getCategoryJobCounts(): JsonResponse
    {
        try {
            $counts = JobPost::where('job_posts.is_active', true)
                ->where('job_posts.deadline', '>=', now())
                ->join('job_categories', 'job_posts.job_category_id', '=', 'job_categories.id')
                ->select('job_categories.id', 'job_categories.name', DB::raw('count(*) as job_count'))
                ->groupBy('job_categories.id', 'job_categories.name')
                ->orderBy('job_categories.name', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $counts,
                'message' => 'Category counts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job counts by country for filter dropdown
     */
    public function getCountryJobCounts(): JsonResponse
    {
        try {
            $counts = JobPost::where('job_posts.is_active', true)
                ->where('job_posts.deadline', '>=', now())
                ->join('job_locations', 'job_posts.job_location_id', '=', 'job_locations.id')
                ->select('job_locations.country', DB::raw('count(*) as job_count'))
                ->groupBy('job_locations.country')
                ->orderBy('job_locations.country', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $counts,
                'message' => 'Country counts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
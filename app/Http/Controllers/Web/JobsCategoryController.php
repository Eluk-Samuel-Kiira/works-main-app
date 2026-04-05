<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job\{ JobPost, Company };
use App\Models\{ Notification };
use Illuminate\Support\Facades\{ Log, Http, DB, Mail  };

class JobsCategoryController extends Controller
{
    
    public function companyJobs(Request $request)
    {
        $query = Company::where('is_active', true)
            ->withCount([
                'jobPosts' => fn($q) => $q
                    ->where('is_active', true)
                    ->where('deadline', '>=', now())
            ])
            ->with('industry:id,name');

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // Only companies with active jobs (optional filter)
        if ($request->boolean('with_jobs_only')) {
            $query->whereHas('jobPosts', fn($q) => $q
                ->where('is_active', true)
                ->where('deadline', '>=', now())
            );
        }

        $perPage = min((int) $request->get('per_page', 24), 100);

        $companies = $query
            ->orderByDesc('job_posts_count')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'data'         => $companies->map(fn($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'slug'        => $c->slug,
                'logo'        => $c->logo_url,
                'description' => $c->description,
                'website'     => $c->website,
                'company_size'=> $c->company_size,
                'is_verified' => $c->is_verified,
                'jobs_count'  => $c->job_posts_count,
                'industry'    => $c->industry ? ['name' => $c->industry->name] : null,
            ]),
            'total'        => $companies->total(),
            'current_page' => $companies->currentPage(),
            'last_page'    => $companies->lastPage(),
            'per_page'     => $companies->perPage(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function getUserData()
    {
        // Get data from database
        $data = [
            'user' => 'Eluk',
            'active' => 1,
            'email' => 'eluk@example.com',
            'stats' => [
                'orders' => 5,
                'users' => 7
            ]
        ];

        // Send as JSON
        return response()->json($data);
    }
}
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Main\Jobs\{ CompanyController, JobPostsController };



// Routes
Route::middleware('auth')->group(function () {
    Route::resource('job-post', JobPostsController::class);

    Route::resource('company', CompanyController::class);
});





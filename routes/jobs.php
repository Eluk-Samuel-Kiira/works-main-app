<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Main\Jobs\{
    CompanyController,
    EducationLevelController,
    ExperienceLevelController,
    IndustryController,
    JobCategoryController,
    JobLocationController,
    JobPostsController,
    JobTypeController,
    SalaryRangeController,
    UserController,
};



// Routes
Route::middleware('auth')->group(function () {
    Route::resource('job-post', JobPostsController::class);

    Route::resource('company', CompanyController::class);
    Route::resource('industry', IndustryController::class)->only(['index']);
    Route::resource('job-category', JobCategoryController::class)->only(['index']);
    Route::resource('job-type', JobTypeController::class)->only(['index']);
    Route::resource('job-location', JobLocationController::class)->only(['index']);
    Route::resource('experience-level', ExperienceLevelController::class)->only(['index']);
    Route::resource('education-level', EducationLevelController::class)->only(['index']);
    Route::resource('salary-range', SalaryRangeController::class)->only(['index']);
    Route::resource('user', UserController::class)->only(['index']);
});





<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Main\Jobs\{ CompanyController };



// Routes
Route::middleware('auth')->group(function () {
    Route::resource('company', CompanyController::class);
});





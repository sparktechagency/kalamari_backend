<?php

use App\Http\Controllers\Admin\ProviderCompanyController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\AuthController;
use App\Models\ProviderCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// public route for user
// Route::post('/register', [AuthController::class, 'register']);
// Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
// Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
// Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// private route for user
Route::middleware('auth:api')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    // Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/profile', [AuthController::class, 'profile']);
    // Route::post('/update-password', [AuthController::class, 'updatePassword']);

    // ADMIN
    Route::middleware('admin')->group(function () {
        // service
        Route::post('/create-service', [ServiceController::class, 'createService']);
        Route::post('/create-page', [ServiceController::class, 'createPage']);

        Route::post('/add-field', [ServiceController::class, 'addField']);
        Route::post('/add-button', [ServiceController::class, 'addButton']);
        Route::post('/button-action', [ServiceController::class, 'buttonAction']);
        Route::post('/add-selection', [ServiceController::class, 'addSelection']);
        Route::post('/add-select-area-item', [ServiceController::class, 'addSelectAreaItem']);

        Route::get('/get-services', [ServiceController::class, 'getServices']);
        Route::get('/get-service-lists', [ServiceController::class, 'getServiceLists']);

        // provider
        Route::post('/create-provider-company',[ProviderCompanyController::class,'createProviderCompany']);

        Route::get('/get-provider-companies',[ProviderCompanyController::class,'getProviderCompanies']);

        // settings
        Route::post('edit-profile',[SettingsController::class,'editProfile']);
        Route::post('change-password',[SettingsController::class,'changePassword']);
        Route::post('change-avatar',[SettingsController::class,'changeAvatar']);
    });

    // COMPANY
    Route::middleware('company')->group(function () {
        // Route::post('/update-company-provider',[ProviderCompany::class,'updateCompanyProvider']);
    });

    // USER
    Route::middleware('user')->group(function () {});
});

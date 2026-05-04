<?php

use App\Http\Controllers\Api\V1\AdminCompanyController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/register/client', [AuthController::class, 'registerClient'])
        ->middleware('throttle:registration');
    Route::post('/register/company', [AuthController::class, 'registerCompany'])
        ->middleware('throttle:registration');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:password-reset');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:password-reset');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::middleware('role:admin')->prefix('admin')->group(function (): void {
            Route::post('/users', [AdminUserController::class, 'store']);
            Route::get('/companies/pending', [AdminCompanyController::class, 'pending']);
            Route::put('/companies/{company}/approve', [AdminCompanyController::class, 'approve']);
            Route::put('/companies/{company}/reject', [AdminCompanyController::class, 'reject']);
        });
    });
});

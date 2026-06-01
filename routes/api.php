<?php

use App\Http\Controllers\Api\V1\AdminCompanyController;
use App\Http\Controllers\Api\V1\AdminDashboardController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\OfferController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('health')->controller(HealthController::class)->group(function (): void {
        Route::get('/', 'check');
    });

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/offers/featured', [OfferController::class, 'featured']);
    Route::get('/offers/search', [OfferController::class, 'search']);

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:registration');
        Route::post('/register-company', [AuthController::class, 'registerCompany'])
            ->middleware('throttle:registration');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:login');
        Route::post('/refresh-token', [AuthController::class, 'refreshToken'])
            ->middleware('throttle:refresh');
        Route::post('/forgot-password/request-token', [AuthController::class, 'requestResetToken'])
            ->middleware('throttle:password-reset');
        Route::post('/forgot-password/verify-token', [AuthController::class, 'verifyResetToken'])
            ->middleware('throttle:password-reset');
        Route::post('/forgot-password/reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:password-reset');

        Route::middleware('auth:jwt')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/profile', [AuthController::class, 'profile']);
        });
    });

    Route::middleware('auth:jwt')->group(function (): void {
        Route::middleware('role:ADMIN')->prefix('admin')->group(function (): void {
            Route::get('/users', [AdminUserController::class, 'index']);
            Route::post('/users', [AdminUserController::class, 'store']);
            Route::get('/companies/pending', [AdminCompanyController::class, 'pending']);
            Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
            Route::get('/dashboard/reports', [AdminDashboardController::class, 'reports']);
            Route::put('/companies/{company}/approve', [AdminCompanyController::class, 'approve']);
            Route::put('/companies/{company}/reject', [AdminCompanyController::class, 'reject']);
        });
    });
});

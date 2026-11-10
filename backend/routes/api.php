<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MerchantAgreementController;
use App\Http\Controllers\Api\V1\MerchantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| IntentGuard API Version 1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Module A: Authentication & Session
    Route::post('/auth/login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/user', [AuthController::class, 'me']);
    });

    // Module B: Merchant Registration & Management
    Route::post('/merchants/register', [MerchantController::class, 'register']);
    Route::get('/merchants', [MerchantController::class, 'index']);
    Route::get('/merchants/{id}', [MerchantController::class, 'show']);
    Route::post('/merchants/{id}/status', [MerchantController::class, 'updateStatus']);
    Route::get('/merchants/{id}/audit-log', [MerchantController::class, 'auditLogs']);

    // Module C: Merchant Agreements & Compliance Policies
    Route::get('/agreements/active', [MerchantAgreementController::class, 'active']);
    Route::post('/merchants/{id}/agreements/accept', [MerchantAgreementController::class, 'accept']);
    Route::get('/merchants/{id}/agreements', [MerchantAgreementController::class, 'merchantAgreements']);
});

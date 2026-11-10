<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AdminAuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MerchantAgreementController;
use App\Http\Controllers\Api\V1\MerchantController;
use App\Http\Controllers\Api\V1\IntegrityVerificationController;
use App\Http\Controllers\Api\V1\PurchaseEvidenceController;
use App\Http\Controllers\Api\V1\CustomerDisputeController;
use App\Http\Controllers\Api\V1\MerchantComplianceController;
use App\Http\Controllers\Api\V1\PayPalPaymentController;
use App\Http\Controllers\Api\V1\PayPalWebhookController;
use App\Http\Controllers\Api\V1\PurchaseIntentController;
use App\Http\Controllers\Api\V1\TransactionDecisionController;
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

    // Module D: Purchase Intent Management (requires authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/intents', [PurchaseIntentController::class, 'store']);
        Route::get('/intents', [PurchaseIntentController::class, 'index']);
        Route::get('/intents/{identifier}', [PurchaseIntentController::class, 'show']);
        Route::post('/intents/{id}/cancel', [PurchaseIntentController::class, 'cancel']);
        Route::get('/intents/{id}/verify-integrity', [PurchaseIntentController::class, 'verifyIntegrity']);

        // Module E: Evidence & Provenance
        Route::post('/intents/{intentId}/evidence', [PurchaseEvidenceController::class, 'store']);
        Route::get('/intents/{intentId}/evidence', [PurchaseEvidenceController::class, 'index']);
        Route::get('/intents/{intentId}/evidence/provenance-chain', [PurchaseEvidenceController::class, 'provenanceChain']);
        Route::get('/evidence/{id}', [PurchaseEvidenceController::class, 'show']);
        Route::get('/evidence/{id}/verify-integrity', [PurchaseEvidenceController::class, 'verifyIntegrity']);

        // Module F: Semantic Purchase Integrity Engine (SPIE)
        Route::post('/intents/{intentId}/verify', [IntegrityVerificationController::class, 'verify']);
        Route::get('/intents/{intentId}/verifications', [IntegrityVerificationController::class, 'index']);

        // Module G: Transaction Decision Engine (TDE) & Pre-Capture Hold Resolution
        Route::get('/verifications/pending-holds', [TransactionDecisionController::class, 'pendingHolds']);
        Route::get('/verifications/{id}', [IntegrityVerificationController::class, 'show']);
        Route::post('/verifications/{id}/confirm', [TransactionDecisionController::class, 'confirm']);
        Route::post('/verifications/{id}/reject', [TransactionDecisionController::class, 'reject']);
        Route::get('/verifications/{id}/audit-log', [TransactionDecisionController::class, 'auditLog']);

        // Module H: Merchant Compliance Telemetry & Dispute Adjudication
        Route::get('/merchants/{merchantId}/compliance/events', [MerchantComplianceController::class, 'events']);
        Route::post('/merchants/{merchantId}/compliance/events', [MerchantComplianceController::class, 'recordEvent']);
        Route::post('/compliance/events/{id}/appeal', [MerchantComplianceController::class, 'submitAppeal']);
        Route::post('/compliance/events/{id}/adjudicate', [MerchantComplianceController::class, 'adjudicateAppeal']);

        Route::post('/intents/{intentId}/disputes', [CustomerDisputeController::class, 'store']);
        Route::get('/disputes', [CustomerDisputeController::class, 'index']);
        Route::get('/disputes/{id}', [CustomerDisputeController::class, 'show']);
        Route::post('/disputes/{id}/respond', [CustomerDisputeController::class, 'respond']);
        Route::post('/disputes/{id}/resolve', [CustomerDisputeController::class, 'resolve']);

        // Module I: PayPal Gateway Integration (Orders v2 & Payments v2)
        Route::post('/intents/{intentId}/paypal/order', [PayPalPaymentController::class, 'createOrder']);
        Route::post('/intents/{intentId}/paypal/authorize', [PayPalPaymentController::class, 'authorizeOrder']);
        Route::get('/payments/{id}', [PayPalPaymentController::class, 'show']);
        Route::post('/payments/{id}/capture-held', [PayPalPaymentController::class, 'captureHeld']);
        Route::post('/payments/{id}/void-held', [PayPalPaymentController::class, 'voidHeld']);

        // Module J: System-Wide Audit, Provenance & Administrative Reporting
        Route::get('/admin/audit-logs', [AdminAuditController::class, 'index']);
        Route::get('/admin/audit-logs/verify-chain', [AdminAuditController::class, 'verifyChain']);
        Route::get('/admin/metrics', [AdminAuditController::class, 'metrics']);
    });

    // Inbound Webhook Ingestion (Unauthenticated, verified via cryptographic digital signatures)
    Route::post('/webhooks/paypal', [PayPalWebhookController::class, 'handle']);
});


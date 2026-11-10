<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Engines\TransactionDecisionEngine\TransactionDecisionEngine;
use App\Enums\CaptureAction;
use App\Enums\DecisionResolutionStatus;
use App\Enums\PurchaseIntentStatus;
use App\Enums\ResolutionAuditAction;
use App\Enums\VerificationVerdict;
use App\Models\PurchaseIntent;
use App\Models\User;
use App\Models\VerificationDecision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionDecisionEngineTest extends TestCase
{
    use RefreshDatabase;

    private TransactionDecisionEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new TransactionDecisionEngine();
    }

    public function test_clean_match_determines_immediate_capture(): void
    {
        $action = $this->engine->determineCaptureAction(
            VerificationVerdict::MATCH,
            merchantComplianceScore: 98.50
        );

        $this->assertSame(CaptureAction::CAPTURE_IMMEDIATE, $action);
    }

    public function test_mismatch_determines_void_capture(): void
    {
        $action = $this->engine->determineCaptureAction(
            VerificationVerdict::MISMATCH,
            merchantComplianceScore: 100.00
        );

        $this->assertSame(CaptureAction::CAPTURE_VOID, $action);
    }

    public function test_uncertain_determines_hold_capture(): void
    {
        $action = $this->engine->determineCaptureAction(
            VerificationVerdict::UNCERTAIN,
            merchantComplianceScore: 95.00
        );

        $this->assertSame(CaptureAction::CAPTURE_HOLD, $action);
    }

    public function test_require_confirmation_determines_hold_capture(): void
    {
        $action = $this->engine->determineCaptureAction(
            VerificationVerdict::REQUIRE_CUSTOMER_CONFIRMATION,
            merchantComplianceScore: 95.00
        );

        $this->assertSame(CaptureAction::CAPTURE_HOLD, $action);
    }

    public function test_low_compliance_score_merchant_escalates_match_to_hold(): void
    {
        // Merchant has compliance score below threshold (< 70.00)
        $action = $this->engine->determineCaptureAction(
            VerificationVerdict::MATCH,
            merchantComplianceScore: 65.00
        );

        $this->assertSame(CaptureAction::CAPTURE_HOLD, $action);
    }

    public function test_resolve_customer_confirmation_releases_hold_and_updates_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create([
            'customer_id' => $customer->id,
            'status'      => PurchaseIntentStatus::PENDING,
        ]);

        $decision = VerificationDecision::factory()->requireConfirmation()->create([
            'purchase_intent_id'   => $intent->id,
            'hold_payment_capture' => true,
            'resolution_status'    => DecisionResolutionStatus::UNRESOLVED,
        ]);

        $resolved = $this->engine->resolveCustomerConfirmation(
            $decision,
            $customer,
            notes: 'I accept the silver color variation.'
        );

        $this->assertSame(DecisionResolutionStatus::CUSTOMER_CONFIRMED, $resolved->resolution_status);
        $this->assertFalse($resolved->hold_payment_capture);
        $this->assertSame($customer->id, $resolved->resolved_by);
        $this->assertNotNull($resolved->resolved_at);

        // Check intent status updated
        $this->assertSame(PurchaseIntentStatus::CONFIRMED, $intent->fresh()->status);

        // Check 2 audit logs recorded: customer_confirmed and capture_released
        $this->assertDatabaseHas('decision_resolution_audit_logs', [
            'verification_decision_id' => $decision->id,
            'action'                   => ResolutionAuditAction::CUSTOMER_CONFIRMED->value,
            'actor_id'                 => $customer->id,
        ]);
        $this->assertDatabaseHas('decision_resolution_audit_logs', [
            'verification_decision_id' => $decision->id,
            'action'                   => ResolutionAuditAction::CAPTURE_RELEASED->value,
        ]);
    }

    public function test_resolve_customer_rejection_voids_capture_and_updates_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create([
            'customer_id' => $customer->id,
            'status'      => PurchaseIntentStatus::PENDING,
        ]);

        $decision = VerificationDecision::factory()->requireConfirmation()->create([
            'purchase_intent_id'   => $intent->id,
            'hold_payment_capture' => true,
            'resolution_status'    => DecisionResolutionStatus::UNRESOLVED,
        ]);

        $resolved = $this->engine->resolveCustomerRejection(
            $decision,
            $customer,
            reason: 'I specifically requested black, silver is unacceptable.'
        );

        $this->assertSame(DecisionResolutionStatus::CUSTOMER_REJECTED, $resolved->resolution_status);
        $this->assertTrue($resolved->hold_payment_capture);
        $this->assertSame(PurchaseIntentStatus::MISMATCHED, $intent->fresh()->status);

        $this->assertDatabaseHas('decision_resolution_audit_logs', [
            'verification_decision_id' => $decision->id,
            'action'                   => ResolutionAuditAction::CUSTOMER_REJECTED->value,
            'actor_id'                 => $customer->id,
        ]);
        $this->assertDatabaseHas('decision_resolution_audit_logs', [
            'verification_decision_id' => $decision->id,
            'action'                   => ResolutionAuditAction::CAPTURE_VOIDED->value,
        ]);
    }

    public function test_resolving_already_resolved_decision_throws_exception(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $intent = PurchaseIntent::factory()->create(['customer_id' => $customer->id]);

        $decision = VerificationDecision::factory()->create([
            'purchase_intent_id'   => $intent->id,
            'hold_payment_capture' => false,
            'resolution_status'    => DecisionResolutionStatus::CUSTOMER_CONFIRMED,
        ]);

        $this->expectException(\DomainException::class);

        $this->engine->resolveCustomerConfirmation($decision, $customer);
    }
}

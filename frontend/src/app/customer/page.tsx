'use client';

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { authService, User } from '@/services/authService';
import {
  listIntents,
  recordIntent,
  cancelIntent,
  verifyIntentIntegrity,
  formatPrice,
} from '@/services/intentService';
import {
  verifyCheckout,
  listPendingHolds,
  confirmResolution,
  rejectResolution,
} from '@/services/verificationService';
import { openDispute } from '@/services/complianceService';
import type { PurchaseIntent, IntegrityCheckResult } from '@/types/intent';
import type { VerificationDecision } from '@/types/verification';
import type { DisputeCategory } from '@/types/compliance';

export default function CustomerPortalPage() {
  const [user, setUser] = useState<User | null>(null);
  const [activeTab, setActiveTab] = useState<'intents' | 'new_intent' | 'verifier' | 'holds'>('intents');
  const [intents, setIntents] = useState<PurchaseIntent[]>([]);
  const [loadingIntents, setLoadingIntents] = useState(false);
  const [pendingHolds, setPendingHolds] = useState<VerificationDecision[]>([]);
  const [loadingHolds, setLoadingHolds] = useState(false);

  // New Intent Form State
  const [merchantDbId, setMerchantDbId] = useState<number>(1);
  const [productName, setProductName] = useState('Sony WH-1000XM5 Wireless Headphones');
  const [sku, setSku] = useState('SONY-WH1000XM5-BLK');
  const [brand, setBrand] = useState('Sony');
  const [model, setModel] = useState('WH-1000XM5');
  const [unitPriceDollars, setUnitPriceDollars] = useState('399.00');
  const [currency, setCurrency] = useState('USD');
  const [quantity, setQuantity] = useState(1);
  const [condition, setCondition] = useState<'new' | 'refurbished' | 'used' | 'open_box'>('new');
  const [maxShippingDays, setMaxShippingDays] = useState(3);
  const [maxShippingFeeDollars, setMaxShippingFeeDollars] = useState('15.00');
  const [allowColorVariants, setAllowColorVariants] = useState(true);
  const [allowStorageUpgrades, setAllowStorageUpgrades] = useState(false);
  const [creatingIntent, setCreatingIntent] = useState(false);
  const [createError, setCreateError] = useState<string | null>(null);

  // Verification Simulator State
  const [selectedIntentId, setSelectedIntentId] = useState<number | null>(null);
  const [simMerchantId, setSimMerchantId] = useState<number>(1);
  const [simPriceDollars, setSimPriceDollars] = useState('399.00');
  const [simShippingDollars, setSimShippingDollars] = useState('15.00');
  const [simCondition, setSimCondition] = useState('new');
  const [simBrand, setSimBrand] = useState('Sony');
  const [simModel, setSimModel] = useState('WH-1000XM5');
  const [simQuantity, setSimQuantity] = useState(1);
  const [verificationResult, setVerificationResult] = useState<VerificationDecision | null>(null);
  const [verifying, setVerifying] = useState(false);
  const [verifyError, setVerifyError] = useState<string | null>(null);

  // Dispute Modal State
  const [disputeIntent, setDisputeIntent] = useState<PurchaseIntent | null>(null);
  const [disputeCategory, setDisputeCategory] = useState<DisputeCategory>('item_not_as_described');
  const [disputeClaimDollars, setDisputeClaimDollars] = useState('399.00');
  const [disputeDescription, setDisputeDescription] = useState('');
  const [submittingDispute, setSubmittingDispute] = useState(false);
  const [disputeMessage, setDisputeMessage] = useState<string | null>(null);

  // Cryptographic check state
  const [integrityResults, setIntegrityResults] = useState<Record<number, IntegrityCheckResult>>({});

  useEffect(() => {
    const currentUser = authService.getUser();
    setUser(currentUser);
    if (currentUser) {
      loadCustomerIntents();
      loadHolds();
    }
  }, []);

  const loadCustomerIntents = async () => {
    try {
      setLoadingIntents(true);
      const res = await listIntents(1);
      setIntents(res.data || []);
      if (res.data?.length && !selectedIntentId) {
        setSelectedIntentId(res.data[0].id);
        fillSimulatorFromIntent(res.data[0]);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoadingIntents(false);
    }
  };

  const loadHolds = async () => {
    try {
      setLoadingHolds(true);
      const res = await listPendingHolds();
      setPendingHolds(res.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoadingHolds(false);
    }
  };

  const handleQuickLogin = async () => {
    try {
      const res = await authService.login('customer@intentguard.internal', 'CustomerPass!2026');
      if (res.data?.user) {
        setUser(res.data.user);
        await loadCustomerIntents();
        await loadHolds();
      }
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Login failed');
    }
  };

  const fillSimulatorFromIntent = (intent: PurchaseIntent) => {
    setSelectedIntentId(intent.id);
    setSimMerchantId(intent.merchant_id);
    setSimPriceDollars((intent.unit_price_cents / 100).toFixed(2));
    setSimShippingDollars(
      intent.shipping_conditions?.shipping_cost_cents
        ? (intent.shipping_conditions.shipping_cost_cents / 100).toFixed(2)
        : '0.00'
    );
    setSimCondition(intent.specification?.condition || 'new');
    setSimBrand(intent.specification?.brand || '');
    setSimModel(intent.specification?.model || '');
    setSimQuantity(intent.quantity);
  };

  const handleCreateIntent = async (e: React.FormEvent) => {
    e.preventDefault();
    setCreatingIntent(true);
    setCreateError(null);

    try {
      const unitPriceCents = Math.round(parseFloat(unitPriceDollars) * 100);
      const shippingCents = Math.round(parseFloat(maxShippingFeeDollars) * 100);

      await recordIntent({
        merchant_id: merchantDbId,
        product_name: productName,
        product_sku: sku,
        unit_price_cents: unitPriceCents,
        currency,
        quantity,
        shipping_conditions_json: {
          method: 'Standard Tracked Shipping',
          estimated_days_min: 1,
          estimated_days_max: maxShippingDays,
          shipping_cost_cents: shippingCents,
        },
        specification_json: {
          brand,
          model,
          condition,
        },
        allowed_substitutions_json: {
          colour_variants: allowColorVariants,
          storage_upgrade: allowStorageUpgrades,
        },
      });

      await loadCustomerIntents();
      setActiveTab('intents');
    } catch (err: unknown) {
      setCreateError(err instanceof Error ? err.message : 'Failed to record purchase intent.');
    } finally {
      setCreatingIntent(false);
    }
  };

  const handleVerifySimulator = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedIntentId) return;

    try {
      setVerifying(true);
      setVerifyError(null);
      setVerificationResult(null);

      const priceCents = Math.round(parseFloat(simPriceDollars) * 100);
      const shippingCents = Math.round(parseFloat(simShippingDollars) * 100);

      const res = await verifyCheckout(selectedIntentId, {
        merchant_id: simMerchantId,
        currency,
        unit_price_cents: priceCents,
        shipping_cost_cents: shippingCents,
        total_price_cents: priceCents * simQuantity + shippingCents,
        quantity: simQuantity,
        specification_json: {
          brand: simBrand,
          model: simModel,
          condition: simCondition,
        },
      });

      setVerificationResult(res);
      await loadHolds();
    } catch (err: unknown) {
      setVerifyError(err instanceof Error ? err.message : 'Verification failed.');
    } finally {
      setVerifying(false);
    }
  };

  const handleTamperCheck = async (intentId: number) => {
    try {
      const res = await verifyIntentIntegrity(intentId);
      setIntegrityResults((prev) => ({
        ...prev,
        [intentId]: res,
      }));
    } catch (err) {
      console.error(err);
    }
  };

  const handleCancelIntent = async (id: number) => {
    if (!confirm('Are you sure you want to cancel this purchase intent?')) return;
    try {
      await cancelIntent(id);
      await loadCustomerIntents();
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Cancellation failed.');
    }
  };

  const handleConfirmHold = async (decisionId: number) => {
    try {
      await confirmResolution(decisionId, { notes: 'Customer approved checkout deviation.' });
      alert('Deviation approved. Payment capture has been released!');
      await loadHolds();
      await loadCustomerIntents();
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Confirmation failed.');
    }
  };

  const handleRejectHold = async (decisionId: number) => {
    const reason = prompt('Please specify rejection reason:', 'Price or specification mismatch not approved.');
    if (!reason) return;
    try {
      await rejectResolution(decisionId, { reason });
      alert('Checkout rejected. Payment capture has been voided.');
      await loadHolds();
      await loadCustomerIntents();
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Rejection failed.');
    }
  };

  const handleSubmitDispute = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!disputeIntent) return;

    try {
      setSubmittingDispute(true);
      const claimCents = Math.round(parseFloat(disputeClaimDollars) * 100);
      await openDispute(disputeIntent.id, {
        dispute_category: disputeCategory,
        claimed_amount_cents: claimCents,
        description: disputeDescription,
      });
      setDisputeMessage('Dispute registered successfully. It has been routed to the merchant and administrator.');
      setTimeout(() => {
        setDisputeIntent(null);
        setDisputeMessage(null);
        setDisputeDescription('');
      }, 2000);
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Failed to submit dispute.');
    } finally {
      setSubmittingDispute(false);
    }
  };

  return (
    <main className="min-h-screen bg-[#F3F6FB] flex flex-col text-[#101D35] pb-16">
      {/* Header Banner */}
      <section className="bg-[#101D35] text-white py-10 px-4 sm:px-6 lg:px-8 border-b border-[#1C2C4E]">
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div className="inline-flex items-center gap-2 px-3 py-0.5 rounded-full text-xs font-semibold bg-[#176BEB]/20 text-[#60A5FA] border border-[#176BEB]/30 uppercase tracking-wider">
              👤 Customer Protection Console
            </div>
            <h1 className="text-2xl sm:text-3xl font-black tracking-tight mt-2 text-white">
              Purchase Intent &amp; Pre-Capture Verification
            </h1>
            <p className="text-xs sm:text-sm text-slate-300 mt-1 max-w-xl">
              Lock in your exact purchase specifications cryptographically. Verify checkouts in real-time before payment capture and release or void held transactions with 1-click.
            </p>
          </div>

          {!user && (
            <div className="bg-[#1A2C4E] p-4 rounded-xl border border-[#2B416C] flex flex-col sm:flex-row items-center gap-3">
              <div className="text-xs text-slate-300">
                <span className="font-semibold block text-white">Not Signed In</span>
                Sign in to manage intents and verify checkouts.
              </div>
              <button
                onClick={handleQuickLogin}
                className="px-4 py-2 bg-[#176BEB] hover:bg-[#1258C2] text-white text-xs font-bold rounded-lg shadow-sm whitespace-nowrap cursor-pointer transition-colors"
              >
                ⚡ Sign In as Alex Customer
              </button>
            </div>
          )}
        </div>
      </section>

      {/* Main Container */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6 flex-1">
        {/* Navigation Tabs */}
        <div className="flex flex-wrap gap-2 border-b border-slate-200/80 pb-3">
          <button
            onClick={() => setActiveTab('intents')}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer ${
              activeTab === 'intents'
                ? 'bg-[#176BEB] text-white shadow-xs'
                : 'bg-white text-slate-600 hover:bg-[#F3F6FB] hover:text-[#101D35] border border-slate-200/80'
            }`}
          >
            <span>📋 My Purchase Intents</span>
            <span className="px-1.5 py-0.5 bg-black/20 rounded-full text-[10px]">
              {intents.length}
            </span>
          </button>

          <button
            onClick={() => setActiveTab('new_intent')}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer ${
              activeTab === 'new_intent'
                ? 'bg-[#176BEB] text-white shadow-xs'
                : 'bg-white text-slate-600 hover:bg-[#F3F6FB] hover:text-[#101D35] border border-slate-200/80'
            }`}
          >
            <span>➕ Record New Purchase Intent</span>
          </button>

          <button
            onClick={() => setActiveTab('verifier')}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer ${
              activeTab === 'verifier'
                ? 'bg-[#176BEB] text-white shadow-xs'
                : 'bg-white text-slate-600 hover:bg-[#F3F6FB] hover:text-[#101D35] border border-slate-200/80'
            }`}
          >
            <span>🔬 Pre-Capture SPIE Verifier</span>
          </button>

          <button
            onClick={() => setActiveTab('holds')}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer ${
              activeTab === 'holds'
                ? 'bg-[#EAA33A] text-white shadow-xs'
                : 'bg-white text-slate-600 hover:bg-[#F3F6FB] hover:text-[#101D35] border border-slate-200/80'
            }`}
          >
            <span>⚠️ Held Capture Authorizations</span>
            {pendingHolds.length > 0 && (
              <span className="px-1.5 py-0.5 bg-white text-[#B47318] font-black rounded-full text-[10px] animate-pulse">
                {pendingHolds.length}
              </span>
            )}
          </button>
        </div>

        {/* TAB 1: INTENTS LIST */}
        {activeTab === 'intents' && (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-base font-bold text-[#101D35]">Your Active Purchase Intents</h2>
                <p className="text-xs text-slate-500">Each intent is protected by a SHA-256 cryptographic digest.</p>
              </div>
              <button
                onClick={loadCustomerIntents}
                className="text-xs text-[#176BEB] hover:text-[#1258C2] font-semibold cursor-pointer"
              >
                🔄 Refresh
              </button>
            </div>

            {loadingIntents ? (
              <div className="p-12 text-center text-sm text-slate-500 bg-white rounded-2xl border border-slate-200/80">
                Loading intents...
              </div>
            ) : intents.length === 0 ? (
              <div className="p-12 text-center text-sm text-slate-500 bg-white rounded-2xl border border-slate-200/80 space-y-3">
                <p>No purchase intents recorded yet.</p>
                <button
                  onClick={() => setActiveTab('new_intent')}
                  className="px-4 py-2 bg-[#176BEB] hover:bg-[#1258C2] text-white text-xs font-bold rounded-lg shadow-sm cursor-pointer"
                >
                  Create Your First Intent →
                </button>
              </div>
            ) : (
              <div className="grid grid-cols-1 gap-4">
                {intents.map((intent) => {
                  const check = integrityResults[intent.id];
                  return (
                    <div
                      key={intent.id}
                      className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4 hover:border-slate-300 transition-all"
                    >
                      <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                        <div>
                          <div className="flex items-center gap-2">
                            <h3 className="font-bold text-[#101D35] text-base">
                              {intent.product_name}
                            </h3>
                            <span className="px-2 py-0.5 bg-[#F3F6FB] text-[#101D35] rounded-md text-[11px] font-mono border border-slate-200">
                              SKU: {intent.product_sku || 'N/A'}
                            </span>
                            <span
                              className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                                intent.status === 'verified'
                                  ? 'bg-[#12B981]/10 text-[#0E9B6C] border border-[#12B981]/30'
                                  : intent.status === 'pending'
                                  ? 'bg-[#EAA33A]/10 text-[#A06813] border border-[#EAA33A]/30'
                                  : intent.status === 'mismatched'
                                  ? 'bg-red-50 text-red-700 border border-red-200'
                                  : intent.status === 'confirmed'
                                  ? 'bg-[#176BEB]/10 text-[#176BEB] border border-[#176BEB]/30'
                                  : 'bg-slate-100 text-slate-600'
                              }`}
                            >
                              {intent.status}
                            </span>
                          </div>
                          <div className="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-3">
                            <span>Merchant: <strong className="font-mono text-[#101D35]">{intent.merchant_intentguard_id || `ID #${intent.merchant_id}`}</strong></span>
                            <span>•</span>
                            <span>Condition: <strong className="capitalize text-[#101D35]">{intent.specification?.condition || 'New'}</strong></span>
                            <span>•</span>
                            <span>Brand: <strong className="text-[#101D35]">{intent.specification?.brand || 'N/A'}</strong></span>
                            <span>•</span>
                            <span>Qty: <strong className="text-[#101D35]">{intent.quantity}</strong></span>
                          </div>
                        </div>

                        <div className="text-right">
                          <div className="text-xl font-black text-[#176BEB]">
                            {formatPrice(intent.unit_price_cents, intent.currency)}
                          </div>
                          <div className="text-[11px] text-slate-500 font-mono">
                            {intent.currency} per unit
                          </div>
                        </div>
                      </div>

                      {/* Cryptographic SHA-256 Digest Bar */}
                      <div className="bg-[#F3F6FB] p-3 rounded-xl border border-slate-200/80 text-xs flex flex-col md:flex-row md:items-center justify-between gap-2">
                        <div className="font-mono text-[11px] text-slate-600 truncate max-w-xl">
                          <span className="font-bold text-[#101D35]">SHA-256 Intent Hash:</span> {intent.intent_hash}
                        </div>

                        <div className="flex items-center gap-2">
                          {check ? (
                            check.integrity_ok ? (
                              <span className="inline-flex items-center gap-1 text-[11px] font-bold text-[#0E9B6C] bg-[#12B981]/10 px-2 py-0.5 rounded border border-[#12B981]/30">
                                ✓ Digest Intact
                              </span>
                            ) : (
                              <span className="inline-flex items-center gap-1 text-[11px] font-bold text-red-700 bg-red-50 px-2 py-0.5 rounded border border-red-200">
                                ⚠ Tampering Detected!
                              </span>
                            )
                          ) : (
                            <button
                              onClick={() => handleTamperCheck(intent.id)}
                              className="px-2 py-1 bg-white hover:bg-slate-100 text-[#101D35] border border-slate-200 rounded text-[11px] font-semibold cursor-pointer shadow-2xs"
                            >
                              Verify Cryptographic Hash
                            </button>
                          )}
                        </div>
                      </div>

                      {/* Action Bar */}
                      <div className="flex items-center justify-between pt-1">
                        <button
                          onClick={() => {
                            fillSimulatorFromIntent(intent);
                            setActiveTab('verifier');
                          }}
                          className="px-3 py-1.5 bg-[#EEF5FF] hover:bg-[#DBEAFE] text-[#176BEB] text-xs font-semibold rounded-lg border border-[#BFDBFE] flex items-center gap-1.5 cursor-pointer transition-colors"
                        >
                          <span>🔬 Simulate Checkout Verification</span>
                          <span>→</span>
                        </button>

                        <div className="flex items-center gap-2">
                          <button
                            onClick={() => {
                              setDisputeIntent(intent);
                              setDisputeClaimDollars((intent.unit_price_cents / 100).toFixed(2));
                            }}
                            className="px-2.5 py-1 text-[#EAA33A] hover:text-[#B47318] text-xs font-semibold cursor-pointer"
                          >
                            File Integrity Dispute
                          </button>

                          {intent.status === 'pending' && (
                            <button
                              onClick={() => handleCancelIntent(intent.id)}
                              className="px-2.5 py-1 text-red-600 hover:text-red-800 text-xs font-semibold cursor-pointer"
                            >
                              Cancel Intent
                            </button>
                          )}
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        )}

        {/* TAB 2: CREATE NEW INTENT */}
        {activeTab === 'new_intent' && (
          <div className="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs max-w-3xl mx-auto space-y-6">
            <div>
              <h2 className="text-lg font-bold text-[#101D35]">Record Purchase Intent</h2>
              <p className="text-xs text-slate-500 mt-1">
                Establish the cryptographic baseline specifications for what you intend to purchase. Any deviation at checkout will trigger an integrity alert.
              </p>
            </div>

            {createError && (
              <div className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg font-medium">
                {createError}
              </div>
            )}

            <form onSubmit={handleCreateIntent} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-[#101D35] mb-1">
                    Merchant Record ID (Numeric) *
                  </label>
                  <input
                    type="number"
                    min="1"
                    required
                    value={merchantDbId}
                    onChange={(e) => setMerchantDbId(Number(e.target.value))}
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none font-mono"
                  />
                  <span className="text-[10px] text-slate-400 mt-0.5 block">
                    e.g. 1 (Apex Electronics Ltd — IG-MCH-APEX0001)
                  </span>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-[#101D35] mb-1">
                    Product SKU *
                  </label>
                  <input
                    type="text"
                    required
                    value={sku}
                    onChange={(e) => setSku(e.target.value)}
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none font-mono"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Product Name *
                </label>
                <input
                  type="text"
                  required
                  value={productName}
                  onChange={(e) => setProductName(e.target.value)}
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-[#101D35] mb-1">
                    Brand *
                  </label>
                  <input
                    type="text"
                    required
                    value={brand}
                    onChange={(e) => setBrand(e.target.value)}
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-[#101D35] mb-1">
                    Model *
                  </label>
                  <input
                    type="text"
                    required
                    value={model}
                    onChange={(e) => setModel(e.target.value)}
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-[#101D35] mb-1">
                    Condition *
                  </label>
                  <select
                    value={condition}
                    onChange={(e) => setCondition(e.target.value as 'new' | 'refurbished' | 'used' | 'open_box')}
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none bg-white"
                  >
                    <option value="new">Brand New (Factory Sealed)</option>
                    <option value="refurbished">Certified Refurbished</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-[#101D35] mb-1">
                    Unit Price ($) *
                  </label>
                  <input
                    type="number"
                    step="0.01"
                    required
                    value={unitPriceDollars}
                    onChange={(e) => setUnitPriceDollars(e.target.value)}
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-[#101D35] mb-1">
                    Currency *
                  </label>
                  <input
                    type="text"
                    required
                    value={currency}
                    onChange={(e) => setCurrency(e.target.value)}
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none font-mono uppercase"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-[#101D35] mb-1">
                    Quantity *
                  </label>
                  <input
                    type="number"
                    min="1"
                    required
                    value={quantity}
                    onChange={(e) => setQuantity(Number(e.target.value))}
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                <div>
                  <label className="block text-xs font-semibold text-[#101D35] mb-1">
                    Maximum Shipping SLA (Days)
                  </label>
                  <input
                    type="number"
                    value={maxShippingDays}
                    onChange={(e) => setMaxShippingDays(Number(e.target.value))}
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-[#101D35] mb-1">
                    Maximum Allowed Shipping Fee ($)
                  </label>
                  <input
                    type="number"
                    step="0.01"
                    value={maxShippingFeeDollars}
                    onChange={(e) => setMaxShippingFeeDollars(e.target.value)}
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                  />
                </div>
              </div>

              <div className="space-y-2 pt-2 border-t border-slate-100">
                <span className="block text-xs font-semibold text-[#101D35]">
                  Acceptable Substitution Boundaries
                </span>
                <div className="space-y-1.5">
                  <label className="flex items-center gap-2 text-xs text-slate-600">
                    <input
                      type="checkbox"
                      checked={allowColorVariants}
                      onChange={(e) => setAllowColorVariants(e.target.checked)}
                      className="rounded accent-[#176BEB] focus:ring-[#176BEB]"
                    />
                    Allow non-material colour variant substitutions (e.g. Silver instead of Black)
                  </label>
                  <label className="flex items-center gap-2 text-xs text-slate-600">
                    <input
                      type="checkbox"
                      checked={allowStorageUpgrades}
                      onChange={(e) => setAllowStorageUpgrades(e.target.checked)}
                      className="rounded accent-[#176BEB] focus:ring-[#176BEB]"
                    />
                    Allow free capacity/storage upgrades at no extra cost (e.g. 512GB instead of 256GB)
                  </label>
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-4">
                <button
                  type="button"
                  onClick={() => setActiveTab('intents')}
                  className="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={creatingIntent}
                  className="px-5 py-2 text-xs font-semibold text-white bg-[#176BEB] hover:bg-[#1258C2] rounded-lg disabled:opacity-50 shadow-xs transition-colors cursor-pointer"
                >
                  {creatingIntent ? 'Hashing & Recording...' : 'Record Cryptographic Intent'}
                </button>
              </div>
            </form>
          </div>
        )}

        {/* TAB 3: VERIFICATION SIMULATOR */}
        {activeTab === 'verifier' && (
          <div className="space-y-6 max-w-4xl mx-auto">
            <div className="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
              <div>
                <h2 className="text-lg font-bold text-[#101D35]">
                  Pre-Capture Semantic Purchase Integrity Verifier (SPIE)
                </h2>
                <p className="text-xs text-slate-500 mt-1">
                  Test checkout payloads against an existing intent to observe real-time mismatch detection, confidence scoring, and payment capture directives.
                </p>
              </div>

              {/* Target Intent Selector */}
              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Select Purchase Intent Baseline:
                </label>
                <select
                  value={selectedIntentId || ''}
                  onChange={(e) => {
                    const id = Number(e.target.value);
                    const found = intents.find((i) => i.id === id);
                    if (found) fillSimulatorFromIntent(found);
                  }}
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none bg-white font-mono"
                >
                  {intents.map((i) => (
                    <option key={i.id} value={i.id}>
                      Intent #{i.id} — {i.product_name} ({formatPrice(i.unit_price_cents, i.currency)})
                    </option>
                  ))}
                </select>
              </div>

              {/* Quick Attack Vector Presets */}
              <div className="p-3.5 bg-[#F3F6FB] border border-slate-200 rounded-xl space-y-2">
                <span className="text-xs font-bold text-[#101D35] block">
                  Quick Scenario Presets:
                </span>
                <div className="flex flex-wrap gap-2 text-xs">
                  <button
                    type="button"
                    onClick={() => {
                      setSimPriceDollars('399.00');
                      setSimCondition('new');
                      setSimShippingDollars('15.00');
                    }}
                    className="px-2.5 py-1 bg-white hover:bg-[#12B981]/10 text-[#0E9B6C] font-semibold border border-[#12B981]/30 rounded-lg shadow-2xs cursor-pointer transition-colors"
                  >
                    ✓ Perfect Match ($399.00 New)
                  </button>

                  <button
                    type="button"
                    onClick={() => {
                      setSimPriceDollars('449.00'); // $50 hidden fee surge
                    }}
                    className="px-2.5 py-1 bg-white hover:bg-red-50 text-red-700 font-semibold border border-red-200 rounded-lg shadow-2xs cursor-pointer transition-colors"
                  >
                    ⚡ Bait-and-Switch Price Surge ($449.00)
                  </button>

                  <button
                    type="button"
                    onClick={() => {
                      setSimCondition('refurbished'); // Downgraded condition
                    }}
                    className="px-2.5 py-1 bg-white hover:bg-[#EAA33A]/10 text-[#A06813] font-semibold border border-[#EAA33A]/30 rounded-lg shadow-2xs cursor-pointer transition-colors"
                  >
                    ⚡ Refurbished Substitution Downgrade
                  </button>

                  <button
                    type="button"
                    onClick={() => {
                      setSimShippingDollars('45.00'); // Shipping fee surge
                    }}
                    className="px-2.5 py-1 bg-white hover:bg-red-50 text-red-700 font-semibold border border-red-200 rounded-lg shadow-2xs cursor-pointer transition-colors"
                  >
                    ⚡ Hidden Shipping Fee Surge ($45.00)
                  </button>
                </div>
              </div>

              {verifyError && (
                <div className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg font-medium">
                  {verifyError}
                </div>
              )}

              {/* Simulation Payload Form */}
              <form onSubmit={handleVerifySimulator} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-xs font-semibold text-[#101D35] mb-1">
                      Checkout Merchant ID (Numeric)
                    </label>
                    <input
                      type="number"
                      min="1"
                      required
                      value={simMerchantId}
                      onChange={(e) => setSimMerchantId(Number(e.target.value))}
                      className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] font-mono"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-[#101D35] mb-1">
                      Checkout Unit Price ($)
                    </label>
                    <input
                      type="number"
                      step="0.01"
                      required
                      value={simPriceDollars}
                      onChange={(e) => setSimPriceDollars(e.target.value)}
                      className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] font-semibold text-[#101D35]"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-[#101D35] mb-1">
                      Shipping Amount ($)
                    </label>
                    <input
                      type="number"
                      step="0.01"
                      required
                      value={simShippingDollars}
                      onChange={(e) => setSimShippingDollars(e.target.value)}
                      className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB]"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-xs font-semibold text-[#101D35] mb-1">
                      Condition at Checkout
                    </label>
                    <select
                      value={simCondition}
                      onChange={(e) => setSimCondition(e.target.value)}
                      className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] bg-white"
                    >
                      <option value="new">Brand New</option>
                      <option value="refurbished">Refurbished / Used</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-[#101D35] mb-1">
                      Brand at Checkout
                    </label>
                    <input
                      type="text"
                      value={simBrand}
                      onChange={(e) => setSimBrand(e.target.value)}
                      className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB]"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-[#101D35] mb-1">
                      Model at Checkout
                    </label>
                    <input
                      type="text"
                      value={simModel}
                      onChange={(e) => setSimModel(e.target.value)}
                      className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB]"
                    />
                  </div>
                </div>

                <div className="flex justify-end pt-2">
                  <button
                    type="submit"
                    disabled={verifying}
                    className="px-6 py-2.5 bg-[#176BEB] hover:bg-[#1258C2] text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-2 disabled:opacity-50 cursor-pointer"
                  >
                    {verifying ? 'Evaluating Semantic Rules...' : 'Execute Pre-Capture Verification →'}
                  </button>
                </div>
              </form>
            </div>

            {/* Verification Result Display */}
            {verificationResult && (
              <div className="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden space-y-6 p-6 sm:p-8 animate-in fade-in">
                {/* Verdict Banner */}
                <div
                  className={`p-5 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4 ${
                    verificationResult.verdict === 'MATCH'
                      ? 'bg-[#12B981]/10 border-[#12B981]/30 text-[#0E9B6C]'
                      : verificationResult.verdict === 'MISMATCH'
                      ? 'bg-red-50 border-red-200 text-red-900'
                      : 'bg-[#EAA33A]/10 border-[#EAA33A]/30 text-[#8C580A]'
                  }`}
                >
                  <div className="flex items-center gap-4">
                    <div
                      className={`w-12 h-12 rounded-xl flex items-center justify-center text-xl font-bold ${
                        verificationResult.verdict === 'MATCH'
                          ? 'bg-[#12B981] text-white'
                          : verificationResult.verdict === 'MISMATCH'
                          ? 'bg-red-600 text-white'
                          : 'bg-[#EAA33A] text-white'
                      }`}
                    >
                      {verificationResult.verdict === 'MATCH' ? '✓' : verificationResult.verdict === 'MISMATCH' ? '✗' : '⚠'}
                    </div>

                    <div>
                      <div className="text-xs font-bold uppercase tracking-wider opacity-75">
                        Integrity Verdict
                      </div>
                      <div className="text-xl font-black">
                        {verificationResult.verdict}
                      </div>
                      <div className="text-xs mt-0.5 opacity-90">
                        {verificationResult.verdict === 'MATCH'
                          ? 'Specifications and pricing verified. Pre-capture payment authorized for immediate capture.'
                          : verificationResult.verdict === 'MISMATCH'
                          ? 'Critical checkout discrepancy detected! Immediate payment void recommended.'
                          : 'Discrepancy detected. Payment capture held pending customer confirmation.'}
                      </div>
                    </div>
                  </div>

                  <div className="text-right sm:border-l sm:border-black/10 sm:pl-6">
                    <div className="text-xs font-bold uppercase tracking-wider opacity-75">
                      Confidence Score
                    </div>
                    <div className="text-2xl font-black">
                      {(verificationResult.confidence_score * 100).toFixed(1)}%
                    </div>
                    <div className="text-[11px] font-mono mt-0.5">
                      Capture Held: <strong>{verificationResult.hold_payment_capture ? 'YES' : 'NO'}</strong>
                    </div>
                  </div>
                </div>

                {/* Discrepancies Table */}
                <div className="space-y-3">
                  <h3 className="font-bold text-[#101D35] text-sm">
                    Discrepancy Analysis Breakdown ({verificationResult.discrepancies?.length || 0})
                  </h3>

                  {(!verificationResult.discrepancies || verificationResult.discrepancies.length === 0) ? (
                    <div className="p-4 bg-[#12B981]/10 rounded-xl border border-[#12B981]/20 text-xs text-[#0E9B6C] flex items-center gap-2 font-medium">
                      <span>✓</span>
                      Zero discrepancies detected across price, condition, currency, merchant identity, and shipping SLA.
                    </div>
                  ) : (
                    <div className="overflow-x-auto">
                      <table className="w-full text-left text-xs">
                        <thead className="bg-[#F3F6FB] text-slate-500 uppercase font-semibold border-b border-slate-200">
                          <tr>
                            <th className="px-4 py-2.5">Severity</th>
                            <th className="px-4 py-2.5">Category</th>
                            <th className="px-4 py-2.5">Description</th>
                            <th className="px-4 py-2.5">Expected</th>
                            <th className="px-4 py-2.5">Actual Checkout</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {verificationResult.discrepancies.map((flag, idx) => (
                            <tr key={idx} className="hover:bg-slate-50/80">
                              <td className="px-4 py-3">
                                <span
                                  className={`px-2 py-0.5 rounded-full font-bold uppercase text-[10px] ${
                                    flag.severity === 'critical'
                                      ? 'bg-red-100 text-red-800'
                                      : flag.severity === 'warning'
                                      ? 'bg-[#EAA33A]/15 text-[#8C580A]'
                                      : 'bg-[#176BEB]/10 text-[#176BEB]'
                                  }`}
                                >
                                  {flag.severity}
                                </span>
                              </td>
                              <td className="px-4 py-3 font-semibold uppercase text-[#101D35]">
                                {flag.category}
                              </td>
                              <td className="px-4 py-3 text-slate-800">
                                {flag.message}
                              </td>
                              <td className="px-4 py-3 font-mono text-slate-600">
                                {typeof flag.intended_value === 'object' ? JSON.stringify(flag.intended_value) : String(flag.intended_value ?? '—')}
                              </td>
                              <td className="px-4 py-3 font-mono font-bold text-[#101D35]">
                                {typeof flag.actual_value === 'object' ? JSON.stringify(flag.actual_value) : String(flag.actual_value ?? '—')}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>

                {/* Pre-capture Hold Resolution Actions */}
                {verificationResult.hold_payment_capture && (
                  <div className="p-4 bg-[#EAA33A]/10 border border-[#EAA33A]/30 rounded-xl space-y-3">
                    <div className="text-xs text-[#8C580A] font-semibold">
                      Action Required: This payment is held at the gateway. Choose how to proceed:
                    </div>
                    <div className="flex gap-3">
                      <button
                        onClick={() => handleConfirmHold(verificationResult.id)}
                        className="px-4 py-2 bg-[#12B981] hover:bg-[#0E9B6C] text-white text-xs font-bold rounded-lg shadow-xs cursor-pointer transition-colors"
                      >
                        ✓ Accept Discrepancy &amp; Release Capture
                      </button>
                      <button
                        onClick={() => handleRejectHold(verificationResult.id)}
                        className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg shadow-xs cursor-pointer transition-colors"
                      >
                        ✗ Reject Checkout &amp; Void Payment
                      </button>
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        )}

        {/* TAB 4: HELD AUTHORIZATIONS */}
        {activeTab === 'holds' && (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-base font-bold text-[#101D35]">Held Capture Authorizations</h2>
                <p className="text-xs text-slate-500">
                  Transactions held by IntentGuard awaiting your explicit confirmation or rejection.
                </p>
              </div>
              <button
                onClick={loadHolds}
                className="text-xs text-[#176BEB] hover:text-[#1258C2] font-semibold cursor-pointer"
              >
                🔄 Refresh
              </button>
            </div>

            {loadingHolds ? (
              <div className="p-12 text-center text-sm text-slate-500 bg-white rounded-2xl border border-slate-200/80">
                Loading held transactions...
              </div>
            ) : pendingHolds.length === 0 ? (
              <div className="p-12 text-center text-sm text-slate-500 bg-white rounded-2xl border border-slate-200/80 space-y-2">
                <p className="font-semibold text-[#101D35]">No transactions currently on capture hold.</p>
                <p className="text-xs text-slate-400">
                  When a checkout deviates from your intent, you will see a prompt here to approve or void it.
                </p>
              </div>
            ) : (
              <div className="grid grid-cols-1 gap-4">
                {pendingHolds.map((hold) => (
                  <div
                    key={hold.id}
                    className="bg-white p-5 rounded-2xl border border-[#EAA33A]/40 shadow-xs space-y-4"
                  >
                    <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="px-2 py-0.5 bg-[#EAA33A]/15 text-[#8C580A] font-bold rounded text-xs">
                            Hold #{hold.id}
                          </span>
                          <span className="text-xs font-semibold text-slate-600">
                            Intent Reference #{hold.purchase_intent_id}
                          </span>
                          <span className="text-xs font-bold text-red-600 uppercase">
                            Verdict: {hold.verdict}
                          </span>
                        </div>
                        <div className="text-xs text-slate-600 mt-2">
                          Confidence Score: <strong className="text-[#101D35]">{(hold.confidence_score * 100).toFixed(1)}%</strong>
                        </div>
                        <div className="text-xs text-slate-500 mt-1">
                          Discrepancies: {hold.discrepancies?.length || 0} flagged item(s)
                        </div>
                      </div>

                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => handleConfirmHold(hold.id)}
                          className="px-4 py-2 bg-[#12B981] hover:bg-[#0E9B6C] text-white text-xs font-bold rounded-lg shadow-xs cursor-pointer transition-colors"
                        >
                          ✓ Confirm &amp; Capture
                        </button>
                        <button
                          onClick={() => handleRejectHold(hold.id)}
                          className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg shadow-xs cursor-pointer transition-colors"
                        >
                          ✗ Reject &amp; Void
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}
      </div>

      {/* Dispute Modal */}
      {disputeIntent && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-[#101D35]/70 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden text-[#101D35]">
            <div className="px-6 py-4 border-b border-slate-200 bg-[#F3F6FB] flex items-center justify-between">
              <div>
                <h3 className="font-bold text-[#101D35] text-sm">
                  Open Integrity Dispute: Intent #{disputeIntent.id}
                </h3>
                <p className="text-[11px] text-slate-500 mt-0.5">
                  {disputeIntent.product_name}
                </p>
              </div>
              <button
                onClick={() => setDisputeIntent(null)}
                className="text-slate-400 hover:text-slate-600 text-xl font-bold p-1 cursor-pointer leading-none"
              >
                ×
              </button>
            </div>

            <form onSubmit={handleSubmitDispute} className="p-6 space-y-4">
              {disputeMessage && (
                <div className="p-3 bg-[#12B981]/10 border border-[#12B981]/30 text-[#0E9B6C] text-xs rounded-lg font-medium">
                  {disputeMessage}
                </div>
              )}

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Dispute Category *
                </label>
                <select
                  value={disputeCategory}
                  onChange={(e) => setDisputeCategory(e.target.value as DisputeCategory)}
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none bg-white"
                >
                  <option value="item_not_as_described">Item Not As Described (Spec Drift)</option>
                  <option value="pricing_discrepancy">Pricing Discrepancy / Hidden Fee Surge</option>
                  <option value="unauthorized_substitution">Unauthorized Substitution</option>
                  <option value="excessive_shipping_delay">Excessive Shipping Delay (SLA Breach)</option>
                  <option value="counterfeit_or_altered">Counterfeit or Altered</option>
                  <option value="other">Other Violation</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Claimed Refund Amount ($) *
                </label>
                <input
                  type="number"
                  step="0.01"
                  required
                  value={disputeClaimDollars}
                  onChange={(e) => setDisputeClaimDollars(e.target.value)}
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Dispute Description &amp; Evidence Details *
                </label>
                <textarea
                  required
                  rows={3}
                  value={disputeDescription}
                  onChange={(e) => setDisputeDescription(e.target.value)}
                  placeholder="Explain exactly how the fulfilled item or checkout deviated from your intent..."
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                />
              </div>

              <div className="flex justify-end gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => setDisputeIntent(null)}
                  className="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={submittingDispute}
                  className="px-5 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg disabled:opacity-50 shadow-xs transition-colors cursor-pointer"
                >
                  {submittingDispute ? 'Filing Dispute...' : 'Submit Formal Dispute'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </main>
  );
}

'use client';

import React, { useState } from 'react';
import { IntentGuardSeal } from '@/components/IntentGuardSeal';

function SparklesIcon({ className = 'w-4 h-4' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z" />
    </svg>
  );
}

function ShoppingBagIcon({ className = 'w-5 h-5' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
      <path d="M3 6h18" />
      <path d="M16 10a4 4 0 0 1-8 0" />
    </svg>
  );
}

function CreditCardIcon({ className = 'w-4 h-4' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect width="20" height="14" x="2" y="5" rx="2" />
      <line x1="2" x2="22" y1="10" y2="10" />
    </svg>
  );
}

function LockIcon({ className = 'w-3 h-3' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
      <path d="M7 11V7a5 5 0 0 1 10 0v4" />
    </svg>
  );
}

function CodeIcon({ className = 'w-5 h-5' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <polyline points="16 18 22 12 16 6" />
      <polyline points="8 6 2 12 8 18" />
    </svg>
  );
}

function CopyIcon({ className = 'w-3.5 h-3.5' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
      <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
    </svg>
  );
}

function CheckIcon({ className = 'w-3.5 h-3.5' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <polyline points="20 6 9 17 4 12" />
    </svg>
  );
}

export default function SealDemoPage() {
  const [selectedMerchantId, setSelectedMerchantId] = useState('IG-MCH-APEX0001');
  const [copiedType, setCopiedType] = useState<string | null>(null);

  const scriptTagCode = `<!-- IntentGuard Trust Seal Script -->
<script src="https://cdn.intentguard.internal/widget/intentguard-seal.js" async></script>

<!-- Embeddable Web Component -->
<intentguard-seal merchant-id="${selectedMerchantId}"></intentguard-seal>`;

  const shopifyLiquidCode = `{% comment %} IntentGuard Trust Badge for Shopify Checkout {% endcomment %}
<div class="intentguard-seal-badge" data-merchant-id="${selectedMerchantId}"></div>
<script src="https://cdn.intentguard.internal/widget/intentguard-seal.js" async></script>`;

  const reactSnippetCode = `import { IntentGuardSeal } from '@/components/IntentGuardSeal';

export function CheckoutFooter() {
  return (
    <div className="flex items-center justify-between">
      <IntentGuardSeal merchantId="${selectedMerchantId}" variant="standard" />
    </div>
  );
}`;

  const copyToClipboard = (text: string, type: string) => {
    navigator.clipboard.writeText(text);
    setCopiedType(type);
    setTimeout(() => setCopiedType(null), 2500);
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 py-10 px-4 sm:px-6 lg:px-8">
      <div className="max-w-6xl mx-auto space-y-10">
        {/* Header */}
        <div className="text-center space-y-3">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-950/70 border border-cyan-800/60 text-cyan-400 text-xs font-semibold">
            <SparklesIcon className="w-3.5 h-3.5" /> Commercial Adoption &amp; Merchant Integration
          </div>
          <h1 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
            IntentGuard Trust Seal Widget
          </h1>
          <p className="text-slate-400 text-sm max-w-2xl mx-auto">
            Embed cryptographic purchase integrity and pre-capture payment protection directly into your checkout page.
            Displays live merchant verification and 100-point compliance telemetry.
          </p>
        </div>

        {/* Live Widget Showcase */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Card 1: Standard Badge */}
          <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Standard Badge</span>
              <span className="text-[11px] text-cyan-400 font-mono">variant="standard"</span>
            </div>
            <p className="text-xs text-slate-400">
              Ideal beside payment buttons or in checkout summary footers.
            </p>
            <div className="p-4 bg-slate-950/70 rounded-xl border border-slate-800/80 flex items-center justify-center min-h-[90px]">
              <IntentGuardSeal merchantId={selectedMerchantId} variant="standard" />
            </div>
          </div>

          {/* Card 2: Compact Badge */}
          <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Compact Pill</span>
              <span className="text-[11px] text-cyan-400 font-mono">variant="compact"</span>
            </div>
            <p className="text-xs text-slate-400">
              Designed for navigation bars, mobile checkout bars, or product pages.
            </p>
            <div className="p-4 bg-slate-950/70 rounded-xl border border-slate-800/80 flex items-center justify-center min-h-[90px]">
              <IntentGuardSeal merchantId={selectedMerchantId} variant="compact" />
            </div>
          </div>

          {/* Card 3: Expanded Card */}
          <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
            <div className="flex items-center justify-between">
              <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Expanded Summary</span>
              <span className="text-[11px] text-cyan-400 font-mono">variant="expanded"</span>
            </div>
            <p className="text-xs text-slate-400">
              Comprehensive trust badge for cart drawers and checkout sidebars.
            </p>
            <div className="p-2 bg-slate-950/70 rounded-xl border border-slate-800/80">
              <IntentGuardSeal merchantId={selectedMerchantId} variant="expanded" />
            </div>
          </div>
        </div>

        {/* Interactive Simulated E-Commerce Checkout Flow */}
        <div className="p-6 sm:p-8 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-6">
          <div className="flex items-center justify-between pb-4 border-b border-slate-800">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-cyan-950/80 border border-cyan-700/60 flex items-center justify-center">
                <ShoppingBagIcon className="w-5 h-5 text-cyan-400" />
              </div>
              <div>
                <h3 className="text-lg font-bold text-white">Live E-Commerce Checkout Simulation</h3>
                <p className="text-xs text-slate-400">Experience how customers see pre-capture intent verification at checkout.</p>
              </div>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-xs text-slate-400">Merchant ID:</span>
              <input
                type="text"
                value={selectedMerchantId}
                onChange={(e) => setSelectedMerchantId(e.target.value)}
                className="px-2.5 py-1 text-xs font-mono bg-slate-950 border border-slate-700 rounded-lg text-cyan-400 focus:outline-none focus:border-cyan-500"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {/* Order Summary */}
            <div className="space-y-4 bg-slate-950/60 p-5 rounded-xl border border-slate-800/80">
              <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400">Items in Cart</h4>
              <div className="flex items-center justify-between py-2 border-b border-slate-800/60 text-sm">
                <div>
                  <div className="font-semibold text-white">Sony WH-1000XM5 Wireless Headphones</div>
                  <div className="text-xs text-slate-400">Condition: New &bull; Colour: Black &bull; Qty: 1</div>
                </div>
                <div className="font-mono text-cyan-400 font-bold">$349.99</div>
              </div>
              <div className="space-y-1.5 text-xs text-slate-400 pt-2">
                <div className="flex justify-between">
                  <span>Subtotal:</span>
                  <span className="text-slate-200 font-mono">$349.99</span>
                </div>
                <div className="flex justify-between">
                  <span>Shipping (Standard):</span>
                  <span className="text-slate-200 font-mono">$0.00</span>
                </div>
                <div className="flex justify-between">
                  <span>Estimated Tax:</span>
                  <span className="text-slate-200 font-mono">$28.00</span>
                </div>
                <div className="flex justify-between text-sm font-bold text-white pt-2 border-t border-slate-800">
                  <span>Total Due:</span>
                  <span className="text-emerald-400 font-mono">$377.99</span>
                </div>
              </div>
            </div>

            {/* Payment & Intent Protection Section */}
            <div className="space-y-5 flex flex-col justify-between">
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-semibold text-slate-300">Payment Authorization</span>
                  <span className="flex items-center gap-1 text-[11px] text-emerald-400">
                    <LockIcon className="w-3 h-3" /> TLS 1.3 / OpenSSL
                  </span>
                </div>
                <button
                  type="button"
                  className="w-full py-3 px-4 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-sm transition-all flex items-center justify-center gap-2 shadow-md cursor-pointer"
                >
                  <CreditCardIcon className="w-4 h-4 text-slate-950" /> Authorize with PayPal Two-Step
                </button>
              </div>

              {/* Embedded Trust Seal in Checkout */}
              <div className="p-4 rounded-xl bg-slate-950 border border-cyan-900/40 space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-[11px] text-slate-400">Integrity Assurance:</span>
                  <span className="text-[10px] text-cyan-400 font-mono">IG-TDE-INTERCEPT-ON</span>
                </div>
                <IntentGuardSeal merchantId={selectedMerchantId} variant="standard" />
                <p className="text-[11px] text-slate-400 leading-tight">
                  Protected by IntentGuard SPIE: payment capture will be held automatically if the merchant charges hidden fees or substitutes unapproved items.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Embed Code Snippets */}
        <div className="p-6 sm:p-8 rounded-2xl bg-slate-900 border border-slate-800 space-y-6">
          <div className="flex items-center gap-2.5">
            <CodeIcon className="w-5 h-5 text-cyan-400" />
            <h3 className="text-lg font-bold text-white">Merchant Embed Code Snippets</h3>
          </div>
          <p className="text-xs text-slate-400">
            Copy and paste into your Shopify liquid template, WooCommerce checkout footer, or modern React / Next.js app.
          </p>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Snippet 1: HTML / Web Component */}
            <div className="space-y-2">
              <div className="flex items-center justify-between text-xs text-slate-300 font-semibold">
                <span>HTML / Web Component</span>
                <button
                  onClick={() => copyToClipboard(scriptTagCode, 'html')}
                  className="inline-flex items-center gap-1 text-[11px] text-cyan-400 hover:text-cyan-300"
                >
                  {copiedType === 'html' ? <CheckIcon className="w-3.5 h-3.5 text-emerald-400" /> : <CopyIcon className="w-3.5 h-3.5" />}
                  {copiedType === 'html' ? 'Copied!' : 'Copy'}
                </button>
              </div>
              <pre className="p-3 bg-slate-950 rounded-xl border border-slate-800 text-[11px] text-slate-300 font-mono overflow-x-auto">
                {scriptTagCode}
              </pre>
            </div>

            {/* Snippet 2: Shopify */}
            <div className="space-y-2">
              <div className="flex items-center justify-between text-xs text-slate-300 font-semibold">
                <span>Shopify Liquid</span>
                <button
                  onClick={() => copyToClipboard(shopifyLiquidCode, 'shopify')}
                  className="inline-flex items-center gap-1 text-[11px] text-cyan-400 hover:text-cyan-300"
                >
                  {copiedType === 'shopify' ? <CheckIcon className="w-3.5 h-3.5 text-emerald-400" /> : <CopyIcon className="w-3.5 h-3.5" />}
                  {copiedType === 'shopify' ? 'Copied!' : 'Copy'}
                </button>
              </div>
              <pre className="p-3 bg-slate-950 rounded-xl border border-slate-800 text-[11px] text-slate-300 font-mono overflow-x-auto">
                {shopifyLiquidCode}
              </pre>
            </div>

            {/* Snippet 3: React / Next.js */}
            <div className="space-y-2">
              <div className="flex items-center justify-between text-xs text-slate-300 font-semibold">
                <span>React / Next.js</span>
                <button
                  onClick={() => copyToClipboard(reactSnippetCode, 'react')}
                  className="inline-flex items-center gap-1 text-[11px] text-cyan-400 hover:text-cyan-300"
                >
                  {copiedType === 'react' ? <CheckIcon className="w-3.5 h-3.5 text-emerald-400" /> : <CopyIcon className="w-3.5 h-3.5" />}
                  {copiedType === 'react' ? 'Copied!' : 'Copy'}
                </button>
              </div>
              <pre className="p-3 bg-slate-950 rounded-xl border border-slate-800 text-[11px] text-slate-300 font-mono overflow-x-auto">
                {reactSnippetCode}
              </pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

import React from 'react';
import Link from 'next/link';
import { MerchantRegistrationForm } from '../features/merchants/components/MerchantRegistrationForm';
import { MerchantLookupWidget } from '../features/merchants/components/MerchantLookupWidget';

export default function Home() {
  return (
    <main className="min-h-screen bg-[#F3F6FB] flex flex-col text-[#101D35]">

      {/* Hero Section */}
      <section className="bg-white border-b border-slate-200/80 py-16 px-4 sm:px-6 lg:px-8 shadow-xs">
        <div className="max-w-4xl mx-auto text-center space-y-4">
          <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-semibold bg-[#176BEB]/10 text-[#176BEB] border border-[#176BEB]/25">
            🛡️ Pre-Capture Purchase Integrity &amp; Merchant Governance
          </div>
          <h1 className="text-3xl sm:text-5xl font-black tracking-tight text-[#101D35] leading-tight">
            Verifying what you pay for matches what you intended to buy.
          </h1>
          <p className="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
            Eliminating bait-and-switch checkout manipulation, hidden fee surges, and unauthorized item substitutions through cryptographic evidence and contractually accountable merchants.
          </p>

          <div className="flex flex-wrap items-center justify-center gap-3 pt-3">
            <Link
              href="/customer"
              className="px-5 py-2.5 bg-[#176BEB] hover:bg-[#1258C2] text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-2"
            >
              <span>👤 Customer Portal</span>
              <span>→</span>
            </Link>
            <Link
              href="/merchant"
              className="px-5 py-2.5 bg-[#12B981] hover:bg-[#0E9B6C] text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-2"
            >
              <span>🏬 Merchant Portal</span>
              <span>→</span>
            </Link>
            <Link
              href="/admin"
              className="px-5 py-2.5 bg-[#101D35] hover:bg-[#1C2C4E] text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-2"
            >
              <span>🛡️ Admin Console</span>
              <span>→</span>
            </Link>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-8 max-w-3xl mx-auto text-left">
            <div className="p-5 bg-[#F3F6FB] rounded-2xl border border-slate-200/80 shadow-xs">
              <span className="text-xs font-bold text-[#176BEB] uppercase tracking-wider block">Pillar 1</span>
              <h3 className="font-bold text-[#101D35] text-sm mt-1">Purchase Integrity</h3>
              <p className="text-xs text-slate-600 mt-1.5 leading-normal">
                Compares original product specifications and pricing against checkout payloads prior to payment capture.
              </p>
            </div>

            <div className="p-5 bg-[#F3F6FB] rounded-2xl border border-slate-200/80 shadow-xs">
              <span className="text-xs font-bold text-[#12B981] uppercase tracking-wider block">Pillar 2</span>
              <h3 className="font-bold text-[#101D35] text-sm mt-1">Merchant Accountability</h3>
              <p className="text-xs text-slate-600 mt-1.5 leading-normal">
                Contractually binding digital agreements governing product accuracy, shipping SLAs, and substitutions.
              </p>
            </div>

            <div className="p-5 bg-[#F3F6FB] rounded-2xl border border-slate-200/80 shadow-xs">
              <span className="text-xs font-bold text-[#EAA33A] uppercase tracking-wider block">Pillar 3</span>
              <h3 className="font-bold text-[#101D35] text-sm mt-1">Cryptographic Provenance</h3>
              <p className="text-xs text-slate-600 mt-1.5 leading-normal">
                SHA-256 tamper-evident digital digests of policy agreements and purchase intent snapshots.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Main Interactive Sections */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full space-y-12 flex-1">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
          {/* Left: Merchant Registration Form (2 cols) */}
          <div className="lg:col-span-2">
            <MerchantRegistrationForm />
          </div>

          {/* Right: Merchant Verification Lookup & Trust Architecture (1 col) */}
          <div className="space-y-6">
            <MerchantLookupWidget />

            {/* Framework Specs Card */}
            <div className="bg-[#101D35] text-white rounded-2xl p-6 shadow-xl space-y-4 border border-[#1C2C4E]">
              <h4 className="text-xs font-bold tracking-wider text-[#176BEB] uppercase">
                Active Architecture
              </h4>
              <div className="space-y-3 text-xs">
                <div className="flex justify-between py-1.5 border-b border-[#1C2C4E]">
                  <span className="text-slate-400">Framework Stage</span>
                  <span className="font-semibold text-slate-200">16: Commercial Pilot Ready</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-[#1C2C4E]">
                  <span className="text-slate-400">Governance Engine</span>
                  <span className="font-semibold text-[#12B981]">SPIE + TDE Interception</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-[#1C2C4E]">
                  <span className="text-slate-400">Backend API</span>
                  <span className="font-mono text-slate-200">Laravel 11+ REST API</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-[#1C2C4E]">
                  <span className="text-slate-400">Frontend Client</span>
                  <span className="font-mono text-slate-200">Next.js 16 (App Router)</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-[#1C2C4E]">
                  <span className="text-slate-400">Integrity Digest</span>
                  <span className="font-mono text-slate-200">SHA-256 Hash Chaining</span>
                </div>
                <div className="flex justify-between py-1.5">
                  <span className="text-slate-400">Gateway Model</span>
                  <span className="font-semibold text-[#60A5FA]">PayPal Two-Step Auth &amp; Capture</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-slate-200 bg-white py-8 px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500">
        <p className="font-semibold text-[#101D35]">IntentGuard Platform &copy; 2026. Confidential &amp; Proprietary.</p>
        <p className="mt-1">Governance, Purchase Integrity, and Merchant Accountability Architecture.</p>
      </footer>
    </main>
  );
}

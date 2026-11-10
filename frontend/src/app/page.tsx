import React from 'react';
import Link from 'next/link';
import { MerchantRegistrationForm } from '../features/merchants/components/MerchantRegistrationForm';
import { MerchantLookupWidget } from '../features/merchants/components/MerchantLookupWidget';

export default function Home() {
  return (
    <main className="min-h-screen bg-slate-50 flex flex-col">

      {/* Hero Section */}
      <section className="bg-gradient-to-b from-white to-slate-50 border-b border-slate-200 py-16 px-4 sm:px-6 lg:px-8">
        <div className="max-w-4xl mx-auto text-center space-y-4">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
            🛡️ Pre-Capture Purchase Integrity & Merchant Governance
          </div>
          <h1 className="text-3xl sm:text-5xl font-black tracking-tight text-slate-900 leading-tight">
            Verifying what you pay for matches what you intended to buy.
          </h1>
          <p className="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
            Eliminating bait-and-switch checkout manipulation, hidden fee surges, and unauthorized item substitutions through cryptographic evidence and contractually accountable merchants.
          </p>

          <div className="flex flex-wrap items-center justify-center gap-3 pt-2">
            <Link
              href="/customer"
              className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2"
            >
              <span>👤 Customer Portal</span>
              <span>→</span>
            </Link>
            <Link
              href="/merchant"
              className="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2"
            >
              <span>🏬 Merchant Portal</span>
              <span>→</span>
            </Link>
            <Link
              href="/admin"
              className="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2"
            >
              <span>🛡️ Admin Console</span>
              <span>→</span>
            </Link>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6 max-w-3xl mx-auto text-left">
            <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
              <span className="text-xs font-bold text-indigo-600 uppercase tracking-wider block">Pillar 1</span>
              <h3 className="font-bold text-slate-900 text-sm mt-1">Purchase Integrity</h3>
              <p className="text-xs text-slate-500 mt-1 leading-normal">
                Compares original product specifications and pricing against checkout payloads prior to payment capture.
              </p>
            </div>

            <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
              <span className="text-xs font-bold text-emerald-600 uppercase tracking-wider block">Pillar 2</span>
              <h3 className="font-bold text-slate-900 text-sm mt-1">Merchant Accountability</h3>
              <p className="text-xs text-slate-500 mt-1 leading-normal">
                Contractually binding digital agreements governing product accuracy, shipping SLAs, and substitutions.
              </p>
            </div>

            <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
              <span className="text-xs font-bold text-purple-600 uppercase tracking-wider block">Pillar 3</span>
              <h3 className="font-bold text-slate-900 text-sm mt-1">Cryptographic Provenance</h3>
              <p className="text-xs text-slate-500 mt-1 leading-normal">
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
            <div className="bg-slate-900 text-white rounded-2xl p-6 shadow-xl space-y-4">
              <h4 className="text-xs font-bold tracking-wider text-indigo-400 uppercase">
                Active Architecture
              </h4>
              <div className="space-y-3 text-xs">
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Framework Stage</span>
                  <span className="font-semibold text-slate-200">01: Project Foundation</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Active Module</span>
                  <span className="font-semibold text-emerald-400">Module B: Merchant Registration</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Backend API</span>
                  <span className="font-mono text-slate-200">Laravel 11+ REST API</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Frontend Client</span>
                  <span className="font-mono text-slate-200">Next.js 14+ (App Router)</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Integrity Digest</span>
                  <span className="font-mono text-slate-200">SHA-256 Hash Chaining</span>
                </div>
                <div className="flex justify-between py-1.5">
                  <span className="text-slate-400">Gateway Model</span>
                  <span className="font-semibold text-indigo-300">PayPal Auth & Capture</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-slate-200 bg-white py-8 px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500">
        <p>IntentGuard Platform &copy; 2026. Confidential & Proprietary.</p>
        <p className="mt-1">Governance, Purchase Integrity, and Merchant Accountability Architecture.</p>
      </footer>
    </main>
  );
}

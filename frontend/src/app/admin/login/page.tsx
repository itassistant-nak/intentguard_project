'use client';

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { authService } from '../../../services/authService';

export default function AdminLoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState('admin@intentguard.internal');
  const [password, setPassword] = useState('IntentGuardAdminPass!2026');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const res = await authService.login(email, password);
      if (res.success) {
        router.push('/admin');
      }
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Authentication failed.');
    } finally {
      setLoading(false);
    }
  };

  const fillDefaults = () => {
    setEmail('admin@intentguard.internal');
    setPassword('IntentGuardAdminPass!2026');
  };

  return (
    <div className="min-h-screen bg-[#F3F6FB] flex flex-col justify-center py-12 sm:px-6 lg:px-8 text-[#101D35]">
      <div className="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <Link href="/" className="inline-flex items-center gap-2 mb-4 group">
          <div className="w-10 h-10 rounded-xl bg-[#176BEB] flex items-center justify-center text-white font-bold text-xl shadow-md group-hover:scale-105 transition-transform">
            IG
          </div>
          <span className="text-2xl font-black tracking-tight text-[#101D35]">
            INTENT<span className="text-[#176BEB]">GUARD</span>
          </span>
        </Link>
        <h2 className="text-xl font-bold tracking-tight text-[#101D35]">
          System Administration Console
        </h2>
        <p className="mt-1 text-xs text-slate-500">
          Restricted platform management, merchant vetting, and compliance enforcement.
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div className="bg-white py-8 px-6 shadow-sm rounded-2xl sm:px-10 border border-slate-200/80 space-y-6">
          {error && (
            <div className="p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl font-medium">
              {error}
            </div>
          )}

          {/* Quick Credential Helper Banner */}
          <div className="p-3.5 bg-[#EEF5FF] border border-[#BFDBFE] rounded-xl text-xs text-[#101D35] space-y-1">
            <div className="flex items-center justify-between">
              <span className="font-bold">Seeded Admin Account:</span>
              <button
                type="button"
                onClick={fillDefaults}
                className="text-[11px] underline font-semibold text-[#176BEB] hover:text-[#1258C2] cursor-pointer"
              >
                Auto-Fill
              </button>
            </div>
            <div className="font-mono text-[11px] text-[#176BEB] font-semibold">
              admin@intentguard.internal
            </div>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-[#101D35] mb-1">
                Administrator Email
              </label>
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="admin@intentguard.internal"
                className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-[#101D35] mb-1">
                Password
              </label>
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••••••"
                className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full mt-2 py-3 px-4 bg-[#176BEB] hover:bg-[#1258C2] text-white font-semibold text-sm rounded-xl shadow-xs focus:ring-4 focus:ring-blue-100 disabled:opacity-50 transition-colors cursor-pointer"
            >
              {loading ? 'Authenticating...' : 'Sign In to Admin Console →'}
            </button>
          </form>

          <div className="pt-4 border-t border-slate-100 text-center">
            <Link
              href="/"
              className="text-xs text-slate-500 hover:text-[#101D35] font-medium"
            >
              ← Return to IntentGuard Home Portal
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}

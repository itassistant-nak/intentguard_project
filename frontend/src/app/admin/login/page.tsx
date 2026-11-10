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
    <div className="min-h-screen bg-slate-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <Link href="/" className="inline-flex items-center gap-2 mb-4">
          <div className="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
            IG
          </div>
          <span className="text-2xl font-black tracking-tight text-white">
            INTENT<span className="text-indigo-400">GUARD</span>
          </span>
        </Link>
        <h2 className="text-xl font-bold tracking-tight text-white">
          System Administration Console
        </h2>
        <p className="mt-1 text-xs text-slate-400">
          Restricted platform management, merchant vetting, and compliance enforcement.
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div className="bg-white py-8 px-6 shadow-2xl rounded-2xl sm:px-10 border border-slate-700/50 space-y-6">
          {error && (
            <div className="p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl font-medium">
              {error}
            </div>
          )}

          {/* Quick Credential Helper Banner */}
          <div className="p-3.5 bg-indigo-50 border border-indigo-200 rounded-xl text-xs text-indigo-900 space-y-1">
            <div className="flex items-center justify-between">
              <span className="font-bold">Seeded Admin Account:</span>
              <button
                type="button"
                onClick={fillDefaults}
                className="text-[11px] underline font-semibold text-indigo-700 hover:text-indigo-900"
              >
                Auto-Fill
              </button>
            </div>
            <div className="font-mono text-[11px] text-indigo-800">
              admin@intentguard.internal
            </div>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Administrator Email
              </label>
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="admin@intentguard.internal"
                className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Password
              </label>
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••••••"
                className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full mt-2 py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl shadow-md focus:ring-4 focus:ring-indigo-100 disabled:opacity-50 transition-all"
            >
              {loading ? 'Authenticating...' : 'Sign In to Admin Console →'}
            </button>
          </form>

          <div className="pt-4 border-t border-slate-100 text-center">
            <Link
              href="/"
              className="text-xs text-slate-500 hover:text-slate-800 font-medium"
            >
              ← Return to IntentGuard Home Portal
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}

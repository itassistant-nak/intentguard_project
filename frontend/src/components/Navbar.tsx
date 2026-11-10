'use client';

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { authService, User } from '@/services/authService';

export function Navbar() {
  const pathname = usePathname();
  const [user, setUser] = useState<User | null>(null);
  const [showAuthModal, setShowAuthModal] = useState(false);
  const [isRegister, setIsRegister] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
  const [authError, setAuthError] = useState<string | null>(null);
  const [authLoading, setAuthLoading] = useState(false);

  useEffect(() => {
    setUser(authService.getUser());
  }, []);

  const handleLogout = () => {
    authService.logout();
    setUser(null);
  };

  const handleAuthSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setAuthLoading(true);
    setAuthError(null);

    try {
      if (isRegister) {
        const res = await authService.register(name, email, password, 'customer');
        if (res.data?.user) {
          setUser(res.data.user);
          setShowAuthModal(false);
        }
      } else {
        const res = await authService.login(email, password);
        if (res.data?.user) {
          setUser(res.data.user);
          setShowAuthModal(false);
        }
      }
    } catch (err: unknown) {
      setAuthError(err instanceof Error ? err.message : 'Authentication failed.');
    } finally {
      setAuthLoading(false);
    }
  };

  const navLinks = [
    { href: '/', label: 'Overview' },
    { href: '/customer', label: 'Customer Portal' },
    { href: '/merchant', label: 'Merchant Portal' },
    { href: '/admin', label: 'Admin Console' },
  ];

  return (
    <>
      <header className="border-b border-slate-200 bg-white/90 backdrop-blur sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          {/* Logo & Protocol Identity */}
          <div className="flex items-center gap-6">
            <Link href="/" className="flex items-center gap-3">
              <div className="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold text-lg shadow-md">
                IG
              </div>
              <div className="flex items-center gap-2">
                <span className="text-lg font-black tracking-tight text-slate-900">
                  INTENT<span className="text-indigo-600">GUARD</span>
                </span>
                <span className="hidden md:inline-block px-2 py-0.5 text-[10px] font-bold bg-indigo-50 text-indigo-700 rounded-full border border-indigo-200">
                  Trust Protocol
                </span>
              </div>
            </Link>

            {/* Main Navigation Links */}
            <nav className="hidden sm:flex items-center gap-1">
              {navLinks.map((link) => {
                const isActive = pathname === link.href || (link.href !== '/' && pathname.startsWith(link.href));
                return (
                  <Link
                    key={link.href}
                    href={link.href}
                    className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                      isActive
                        ? 'bg-slate-100 text-slate-900 shadow-xs'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                    }`}
                  >
                    {link.label}
                  </Link>
                );
              })}
            </nav>
          </div>

          {/* Right Session / Auth Actions */}
          <div className="flex items-center gap-3 text-xs">
            <span className="hidden lg:flex items-center gap-1.5 text-slate-500 font-medium mr-2">
              <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
              REST API v1
            </span>

            {user ? (
              <div className="flex items-center gap-3">
                <div className="text-right">
                  <div className="font-semibold text-slate-800 leading-tight">{user.name}</div>
                  <div className="text-[10px] text-slate-500 font-mono capitalize">{user.role}</div>
                </div>
                <button
                  onClick={handleLogout}
                  className="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition-colors border border-slate-200"
                >
                  Sign Out
                </button>
              </div>
            ) : (
              <div className="flex items-center gap-2">
                <button
                  onClick={() => {
                    setIsRegister(false);
                    setShowAuthModal(true);
                  }}
                  className="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-all"
                >
                  Sign In / Register
                </button>
              </div>
            )}
          </div>
        </div>
      </header>

      {/* Authentication Modal */}
      {showAuthModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
              <div>
                <h3 className="font-bold text-slate-900 text-sm">
                  {isRegister ? 'Create IntentGuard Account' : 'Sign In to IntentGuard'}
                </h3>
                <p className="text-[11px] text-slate-500 mt-0.5">
                  {isRegister ? 'Register as customer to manage purchase intents' : 'Access your purchases or admin console'}
                </p>
              </div>
              <button
                onClick={() => setShowAuthModal(false)}
                className="text-slate-400 hover:text-slate-600 text-xl font-bold p-1"
              >
                ×
              </button>
            </div>

            <form onSubmit={handleAuthSubmit} className="p-6 space-y-4">
              {authError && (
                <div className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg">
                  {authError}
                </div>
              )}

              {isRegister && (
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
                  <input
                    type="text"
                    required
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="e.g. Alex Customer"
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                  />
                </div>
              )}

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="e.g. customer@intentguard.internal"
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                <input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                />
              </div>

              {!isRegister && (
                <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg text-[11px] text-slate-600 space-y-1">
                  <div className="font-semibold text-slate-800">Quick Demo Credentials:</div>
                  <div className="flex justify-between">
                    <span>Customer:</span>
                    <button
                      type="button"
                      onClick={() => {
                        setEmail('customer@intentguard.internal');
                        setPassword('CustomerPass!2026');
                      }}
                      className="text-indigo-600 font-semibold hover:underline"
                    >
                      Fill Customer
                    </button>
                  </div>
                  <div className="flex justify-between">
                    <span>Admin:</span>
                    <button
                      type="button"
                      onClick={() => {
                        setEmail('admin@intentguard.internal');
                        setPassword('IntentGuardAdminPass!2026');
                      }}
                      className="text-indigo-600 font-semibold hover:underline"
                    >
                      Fill Admin
                    </button>
                  </div>
                </div>
              )}

              <div className="flex items-center justify-between pt-2">
                <button
                  type="button"
                  onClick={() => setIsRegister(!isRegister)}
                  className="text-xs text-indigo-600 font-semibold hover:underline"
                >
                  {isRegister ? 'Already registered? Sign In' : 'New user? Register here'}
                </button>

                <button
                  type="submit"
                  disabled={authLoading}
                  className="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 shadow-sm"
                >
                  {authLoading ? 'Authenticating...' : isRegister ? 'Register' : 'Sign In'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  );
}

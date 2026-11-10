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
    { href: '/seal-demo', label: 'Trust Seal Demo' },
  ];

  return (
    <>
      <header className="border-b border-[#1C2C4E] bg-[#101D35] text-white sticky top-0 z-40 shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          {/* Logo & Protocol Identity */}
          <div className="flex items-center gap-6">
            <Link href="/" className="flex items-center gap-3 group">
              <div className="w-9 h-9 rounded-xl bg-[#176BEB] flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:scale-105 transition-transform">
                IG
              </div>
              <div className="flex items-center gap-2">
                <span className="text-lg font-black tracking-tight text-white">
                  INTENT<span className="text-[#176BEB]">GUARD</span>
                </span>
                <span className="hidden md:inline-block px-2.5 py-0.5 text-[10px] font-bold bg-[#176BEB]/20 text-[#60A5FA] rounded-full border border-[#176BEB]/40 uppercase tracking-wider">
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
                        ? 'bg-[#176BEB] text-white shadow-xs'
                        : 'text-slate-300 hover:text-white hover:bg-[#1A2C4E]'
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
            <span className="hidden lg:flex items-center gap-1.5 text-slate-300 font-medium mr-2">
              <span className="w-2 h-2 rounded-full bg-[#12B981] animate-pulse" />
              REST API v1
            </span>

            {user ? (
              <div className="flex items-center gap-3">
                <div className="text-right">
                  <div className="font-semibold text-white leading-tight">{user.name}</div>
                  <div className="text-[10px] text-slate-400 font-mono capitalize">{user.role}</div>
                </div>
                <button
                  onClick={handleLogout}
                  className="px-3 py-1.5 bg-[#1A2C4E] hover:bg-[#223862] text-slate-200 font-semibold rounded-lg transition-colors border border-[#2B416C]"
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
                  className="px-3.5 py-1.5 bg-[#176BEB] hover:bg-[#1258C2] text-white font-semibold rounded-lg shadow-sm transition-all cursor-pointer"
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
        <div className="fixed inset-0 z-50 overflow-y-auto bg-[#101D35]/70 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden text-[#101D35]">
            <div className="px-6 py-4 border-b border-slate-200 bg-[#F3F6FB] flex items-center justify-between">
              <div>
                <h3 className="font-bold text-[#101D35] text-sm">
                  {isRegister ? 'Create IntentGuard Account' : 'Sign In to IntentGuard'}
                </h3>
                <p className="text-[11px] text-slate-500 mt-0.5">
                  {isRegister ? 'Register as customer to manage purchase intents' : 'Access your purchases or admin console'}
                </p>
              </div>
              <button
                onClick={() => setShowAuthModal(false)}
                className="text-slate-400 hover:text-slate-600 text-xl font-bold p-1 leading-none"
              >
                ×
              </button>
            </div>

            <form onSubmit={handleAuthSubmit} className="p-6 space-y-4">
              {authError && (
                <div className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg font-medium">
                  {authError}
                </div>
              )}

              {isRegister && (
                <div>
                  <label className="block text-xs font-semibold text-[#101D35] mb-1">Full Name</label>
                  <input
                    type="text"
                    required
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="e.g. Alex Customer"
                    className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                  />
                </div>
              )}

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">Email Address</label>
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="e.g. customer@intentguard.internal"
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">Password</label>
                <input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                />
              </div>

              {!isRegister && (
                <div className="p-3 bg-[#F3F6FB] border border-[#CBD5E1] rounded-xl text-[11px] text-slate-600 space-y-1">
                  <div className="font-semibold text-[#101D35]">Quick Demo Credentials:</div>
                  <div className="flex justify-between">
                    <span>Customer:</span>
                    <button
                      type="button"
                      onClick={() => {
                        setEmail('customer@intentguard.internal');
                        setPassword('CustomerPass!2026');
                      }}
                      className="text-[#176BEB] font-semibold hover:underline"
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
                      className="text-[#176BEB] font-semibold hover:underline"
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
                  className="text-xs text-[#176BEB] font-semibold hover:underline"
                >
                  {isRegister ? 'Already registered? Sign In' : 'New user? Register here'}
                </button>

                <button
                  type="submit"
                  disabled={authLoading}
                  className="px-5 py-2 text-xs font-semibold text-white bg-[#176BEB] rounded-lg hover:bg-[#1258C2] disabled:opacity-50 shadow-sm transition-colors cursor-pointer"
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

export default Navbar;

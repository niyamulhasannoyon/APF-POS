'use client';

import React from 'react';
import { useSession, signOut } from 'next-auth/react';
import Link from 'next/navigation';
import { usePathname, useRouter } from 'next/navigation';
import {
  LayoutDashboard,
  ShoppingBag,
  Users,
  Receipt,
  Network,
  LogOut,
  User,
  ShieldCheck,
  ChevronRight,
  MonitorPlay,
} from 'lucide-react';

interface SidebarLinkProps {
  href: string;
  icon: React.ReactNode;
  label: string;
  active: boolean;
}

function SidebarLink({ href, icon, label, active }: SidebarLinkProps) {
  return (
    <a
      href={href}
      className={`flex items-center justify-between px-4 py-3 rounded-xl text-sm font-bold tracking-wide transition-all ${
        active
          ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10'
          : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/50'
      }`}
    >
      <div className="flex items-center gap-3">
        {icon}
        <span>{label}</span>
      </div>
      <ChevronRight className={`h-4 w-4 opacity-50 ${active ? 'block' : 'hidden'}`} />
    </a>
  );
}

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const { data: session, status } = useSession();
  const pathname = usePathname();
  const router = useRouter();

  if (status === 'loading') {
    return (
      <div className="flex-1 flex items-center justify-center bg-slate-950 min-h-screen">
        <div className="h-8 w-8 border-2 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin" />
      </div>
    );
  }

  if (!session) {
    router.push('/auth/login');
    return null;
  }

  // Links definitions
  const links = [
    { href: '/dashboard', icon: <LayoutDashboard className="h-5 w-5" />, label: 'Dashboard' },
    { href: '/admin/products', icon: <ShoppingBag className="h-5 w-5" />, label: 'Products & Inventory' },
    { href: '/admin/customers', icon: <Users className="h-5 w-5" />, label: 'Customers CRM' },
    { href: '/admin/orders', icon: <Receipt className="h-5 w-5" />, label: 'Sales History' },
    { href: '/admin/branches', icon: <Network className="h-5 w-5" />, label: 'Branch Manager' },
  ];

  return (
    <div className="flex min-h-screen bg-slate-950 text-slate-100 relative overflow-hidden">
      {/* Background blur effects */}
      <div className="absolute top-0 right-0 w-[40%] h-[40%] rounded-full bg-indigo-950/5 blur-[120px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[40%] h-[40%] rounded-full bg-emerald-950/5 blur-[120px] pointer-events-none" />

      {/* 1. Sidebar */}
      <aside className="w-64 border-r border-slate-900 bg-slate-950/60 backdrop-blur-xl shrink-0 flex flex-col justify-between p-4 z-10 relative">
        <div className="space-y-8">
          {/* Logo Header */}
          <div className="flex items-center gap-3 px-2 py-3">
            <div className="bg-indigo-600 p-2 rounded-xl text-white">
              <ShieldCheck className="h-5 w-5" />
            </div>
            <span className="font-extrabold text-lg tracking-tight">
              APF <span className="text-indigo-400">POS</span>
            </span>
          </div>

          {/* Navigation Links */}
          <nav className="space-y-2">
            {links.map((link) => (
              <SidebarLink
                key={link.href}
                href={link.href}
                icon={link.icon}
                label={link.label}
                active={pathname === link.href}
              />
            ))}
          </nav>
        </div>

        {/* User Card / Logout */}
        <div className="space-y-4 pt-4 border-t border-slate-900">
          <a
            href="/pos"
            className="w-full flex items-center justify-center gap-2 py-3 px-4 bg-emerald-600/10 hover:bg-emerald-600/20 active:bg-emerald-600/30 text-emerald-400 border border-emerald-500/20 rounded-xl text-sm font-bold tracking-wide transition-all shadow-lg shadow-emerald-500/5"
          >
            <MonitorPlay className="h-4 w-4" /> Open Cashier POS
          </a>

          <div className="flex items-center gap-3 px-2 py-1">
            <div className="h-9 w-9 rounded-xl bg-slate-800 flex items-center justify-center text-slate-350 shrink-0 border border-slate-700/50">
              <User className="h-5 w-5" />
            </div>
            <div className="min-w-0 flex-1">
              <p className="text-xs font-bold text-slate-200 truncate">{session.user.name}</p>
              <p className="text-[10px] font-semibold text-slate-500 truncate capitalize">
                {session.user.role} Account
              </p>
            </div>
            <button
              onClick={() => signOut({ callbackUrl: '/auth/login' })}
              className="text-slate-500 hover:text-rose-400 p-1.5 hover:bg-slate-900 rounded-lg transition-all"
              title="Sign Out"
            >
              <LogOut className="h-4 w-4" />
            </button>
          </div>
        </div>
      </aside>

      {/* 2. Main Content Window */}
      <div className="flex-1 flex flex-col min-w-0 overflow-y-auto z-10 relative">
        {/* Top Header */}
        <header className="h-16 border-b border-slate-900 px-8 flex items-center justify-between bg-slate-950/20 backdrop-blur-xl">
          <div className="text-xs font-bold text-indigo-400 uppercase tracking-widest">
            Enterprise Management Panel
          </div>
          <div className="flex items-center gap-4">
            <div className="px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-xs font-bold text-slate-400">
              Active Terminal ID: <span className="text-slate-200 font-mono">001</span>
            </div>
          </div>
        </header>

        {/* Dashboard Views Render */}
        <main className="flex-1 p-8">{children}</main>
      </div>
    </div>
  );
}

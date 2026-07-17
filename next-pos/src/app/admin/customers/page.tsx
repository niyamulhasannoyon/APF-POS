import React from 'react';
import AdminLayout from '@/components/AdminLayout';
import { adminDb } from '@/lib/firebase-admin';

export const dynamic = 'force-dynamic';

export default async function AdminCustomersPage() {
  const customersSnapshot = await adminDb.collection('customers').get();
  const customersList = customersSnapshot.docs.map((doc) => ({
    id: doc.id,
    ...doc.data(),
  })) as any[];

  return (
    <AdminLayout>
      <div className="space-y-8">
        <div className="flex justify-between items-center">
          <h1 className="text-3xl font-black text-slate-100 tracking-tight">Customer Directory</h1>
          <button className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-lg shadow-indigo-600/10 text-xs transition-all">
            + Register Customer
          </button>
        </div>

        <div className="p-6 bg-slate-900/20 border border-slate-900 rounded-3xl backdrop-blur-md">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="border-b border-slate-900 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                  <th className="pb-3">Customer Name</th>
                  <th className="pb-3">Phone Number</th>
                  <th className="pb-3">Email Address</th>
                  <th className="pb-3">Address</th>
                  <th className="pb-3 text-right">Loyalty Points</th>
                  <th className="pb-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-900/50">
                {customersList.length > 0 ? (
                  customersList.map((cust) => (
                    <tr key={cust.id} className="hover:bg-slate-900/10">
                      <td className="py-4 text-sm font-semibold text-slate-200">{cust.name}</td>
                      <td className="py-4 text-xs text-slate-400 font-mono">{cust.phone || 'N/A'}</td>
                      <td className="py-4 text-xs text-slate-400">{cust.email || 'N/A'}</td>
                      <td className="py-4 text-xs text-slate-400">{cust.address || 'N/A'}</td>
                      <td className="py-4 text-sm font-bold text-right text-indigo-400 font-mono">
                        {cust.loyaltyPoints || 0} pts
                      </td>
                      <td className="py-4 text-center">
                        <span className="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                          {cust.status}
                        </span>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={6} className="py-8 text-center text-xs text-slate-500">
                      No customers registered.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}

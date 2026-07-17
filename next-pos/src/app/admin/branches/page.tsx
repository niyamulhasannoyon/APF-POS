import React from 'react';
import AdminLayout from '@/components/AdminLayout';
import { adminDb } from '@/lib/firebase-admin';

export const dynamic = 'force-dynamic';

export default async function AdminBranchesPage() {
  const branchesSnapshot = await adminDb.collection('branches').get();
  const branchesList = branchesSnapshot.docs.map((doc) => ({
    id: doc.id,
    ...doc.data(),
  })) as any[];

  return (
    <AdminLayout>
      <div className="space-y-8">
        <div className="flex justify-between items-center">
          <h1 className="text-3xl font-black text-slate-100 tracking-tight">Active Branches</h1>
          <button className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-lg shadow-indigo-600/10 text-xs transition-all">
            + Create New Branch
          </button>
        </div>

        <div className="p-6 bg-slate-900/20 border border-slate-900 rounded-3xl backdrop-blur-md">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="border-b border-slate-900 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                  <th className="pb-3">Branch Name</th>
                  <th className="pb-3">Code</th>
                  <th className="pb-3">Phone</th>
                  <th className="pb-3">Email Address</th>
                  <th className="pb-3">Location Address</th>
                  <th className="pb-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-900/50">
                {branchesList.length > 0 ? (
                  branchesList.map((branch) => (
                    <tr key={branch.id} className="hover:bg-slate-900/10">
                      <td className="py-4 text-sm font-semibold text-slate-200">{branch.name}</td>
                      <td className="py-4 text-xs text-indigo-400 font-mono font-bold">{branch.code}</td>
                      <td className="py-4 text-xs text-slate-400 font-mono">{branch.phone || 'N/A'}</td>
                      <td className="py-4 text-xs text-slate-400">{branch.email || 'N/A'}</td>
                      <td className="py-4 text-xs text-slate-400">{branch.address || 'N/A'}</td>
                      <td className="py-4 text-center">
                        <span className="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                          {branch.status}
                        </span>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={6} className="py-8 text-center text-xs text-slate-500">
                      No branches registered.
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

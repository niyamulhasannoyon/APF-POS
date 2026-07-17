import React from 'react';
import AdminLayout from '@/components/AdminLayout';
import { adminDb } from '@/lib/firebase-admin';

export const dynamic = 'force-dynamic';

export default async function AdminOrdersPage() {
  const ordersSnapshot = await adminDb.collection('orders').get();
  const branchesSnapshot = await adminDb.collection('branches').get();
  const customersSnapshot = await adminDb.collection('customers').get();

  const branchesMap = new Map<string, string>();
  branchesSnapshot.docs.forEach((doc) => branchesMap.set(doc.id, doc.data().name));

  const customersMap = new Map<string, string>();
  customersSnapshot.docs.forEach((doc) => customersMap.set(doc.id, doc.data().name));

  const ordersList = ordersSnapshot.docs
    .map((doc) => {
      const data = doc.data();
      return {
        id: doc.id,
        subtotal: data.subtotal || 0,
        discountAmount: data.discountAmount || 0,
        taxAmount: data.taxAmount || 0,
        totalAmount: data.totalAmount || 0,
        paidAmount: data.paidAmount || 0,
        paymentStatus: data.paymentStatus || 'unpaid',
        createdAt: data.createdAt,
        branchName: branchesMap.get(data.branchId) || 'N/A',
        customerName: customersMap.get(data.customerId) || 'Walk-in Customer',
      };
    })
    .sort((a, b) => {
      const aTime = a.createdAt?.toMillis ? a.createdAt.toMillis() : 0;
      const bTime = b.createdAt?.toMillis ? b.createdAt.toMillis() : 0;
      return bTime - aTime;
    });

  return (
    <AdminLayout>
      <div className="space-y-8">
        <div className="flex justify-between items-center">
          <h1 className="text-3xl font-black text-slate-100 tracking-tight">Sales History</h1>
        </div>

        <div className="p-6 bg-slate-900/20 border border-slate-900 rounded-3xl backdrop-blur-md">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="border-b border-slate-900 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                  <th className="pb-3">Transaction UUID</th>
                  <th className="pb-3">Branch</th>
                  <th className="pb-3">Customer</th>
                  <th className="pb-3 text-right">Subtotal</th>
                  <th className="pb-3 text-right">Discount</th>
                  <th className="pb-3 text-right">VAT (15%)</th>
                  <th className="pb-3 text-right">Total Due</th>
                  <th className="pb-3 text-center">Status</th>
                  <th className="pb-3">Timestamp</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-900/50">
                {ordersList.length > 0 ? (
                  ordersList.map((order) => (
                    <tr key={order.id} className="hover:bg-slate-900/10">
                      <td className="py-4 text-xs font-semibold text-slate-400 font-mono">{order.id}</td>
                      <td className="py-4 text-sm text-slate-300">{order.branchName}</td>
                      <td className="py-4 text-sm text-slate-400">{order.customerName}</td>
                      <td className="py-4 text-sm text-slate-400 text-right font-mono">${order.subtotal.toFixed(2)}</td>
                      <td className="py-4 text-sm text-emerald-400 text-right font-mono">
                        -${order.discountAmount.toFixed(2)}
                      </td>
                      <td className="py-4 text-sm text-slate-400 text-right font-mono">${order.taxAmount.toFixed(2)}</td>
                      <td className="py-4 text-sm font-bold text-slate-100 text-right font-mono">${order.totalAmount.toFixed(2)}</td>
                      <td className="py-4 text-center">
                        <span className="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                          {order.paymentStatus}
                        </span>
                      </td>
                      <td className="py-4 text-xs text-slate-500">
                        {order.createdAt
                          ? order.createdAt.toDate
                            ? order.createdAt.toDate().toISOString().slice(0, 19).replace('T', ' ')
                            : 'N/A'
                          : 'N/A'}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={9} className="py-8 text-center text-xs text-slate-500">
                      No transactions found.
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

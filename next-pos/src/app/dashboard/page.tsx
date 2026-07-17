import React from 'react';
import AdminLayout from '@/components/AdminLayout';
import { adminDb } from '@/lib/firebase-admin';
import { DollarSign, ShoppingCart, TrendingUp, AlertCircle } from 'lucide-react';

export const dynamic = 'force-dynamic';

export default async function DashboardPage() {
  let totalSales = 0;
  let ordersCount = 0;
  let totalProfit = 0;
  let salesByBranch: { branchName: string; ordersCount: number; totalSales: number }[] = [];
  let topProducts: { productName: string; sku: string; totalQty: number; totalSales: number }[] = [];
  let lowStockAlerts: { productName: string; stockQuantity: number }[] = [];
  let recentOrders: { id: string; branchName: string; totalAmount: number; paymentStatus: string }[] = [];
  let error: string | null = null;

  try {
    // Fetch all orders
    const ordersSnapshot = await adminDb.collection('orders').get();
    const allOrders = ordersSnapshot.docs.map((doc) => ({ id: doc.id, ...doc.data() })) as any[];

    // 1. Total Sales
    totalSales = allOrders.reduce((sum: number, order: any) => sum + (order.totalAmount || 0), 0);

    // 2. Transactions Count
    ordersCount = allOrders.length;

    // 3. Estimated Profit (fetch order items and products)
    const orderItemsSnapshot = await adminDb.collection('orderItems').get();
    const orderItems = orderItemsSnapshot.docs.map((doc) => ({ id: doc.id, ...doc.data() })) as any[];

    for (const item of orderItems) {
      const productDoc = await adminDb.collection('products').doc(String(item.productId)).get();
      if (productDoc.exists) {
        const product = productDoc.data()!;
        const cost = product.cost || 0;
        totalProfit += (item.price - cost) * item.quantity;
      }
    }

    // 4. Sales by Branch
    const branchesSnapshot = await adminDb.collection('branches').get();
    const branchesMap = new Map<string, string>();
    branchesSnapshot.docs.forEach((doc) => {
      const data = doc.data();
      branchesMap.set(doc.id, data.name);
    });

    const branchSalesMap = new Map<string, { ordersCount: number; totalSales: number }>();
    for (const order of allOrders) {
      const branchId = order.branchId;
      if (!branchSalesMap.has(branchId)) {
        branchSalesMap.set(branchId, { ordersCount: 0, totalSales: 0 });
      }
      const entry = branchSalesMap.get(branchId)!;
      entry.ordersCount += 1;
      entry.totalSales += order.totalAmount || 0;
    }

    salesByBranch = Array.from(branchSalesMap.entries()).map(([branchId, data]) => ({
      branchName: branchesMap.get(branchId) || `Branch ${branchId}`,
      ordersCount: data.ordersCount,
      totalSales: data.totalSales,
    }));

    // 5. Top Selling Products
    const productSalesMap = new Map<string, { productName: string; sku: string; totalQty: number; totalSales: number }>();
    for (const item of orderItems) {
      const productDoc = await adminDb.collection('products').doc(String(item.productId)).get();
      const productName = productDoc.exists ? productDoc.data()?.name || 'Unknown' : 'Unknown';
      const sku = productDoc.exists ? productDoc.data()?.sku || 'N/A' : 'N/A';

      if (!productSalesMap.has(item.productId)) {
        productSalesMap.set(item.productId, { productName, sku, totalQty: 0, totalSales: 0 });
      }
      const entry = productSalesMap.get(item.productId)!;
      entry.totalQty += item.quantity || 0;
      entry.totalSales += item.total || 0;
    }

    topProducts = Array.from(productSalesMap.entries())
      .map(([_, data]) => data)
      .sort((a, b) => b.totalQty - a.totalQty)
      .slice(0, 5);

    // 6. Low Stock Alerts
    const productsSnapshot = await adminDb.collection('products').get();
    lowStockAlerts = productsSnapshot.docs
      .map((doc) => ({ productName: doc.data().name, stockQuantity: doc.data().stockQuantity || 0 }))
      .filter((p) => p.stockQuantity <= 20)
      .slice(0, 5);

    // 7. Recent Orders
    recentOrders = allOrders
      .sort((a: any, b: any) => {
        const aTime = a.createdAt?.toMillis ? a.createdAt.toMillis() : 0;
        const bTime = b.createdAt?.toMillis ? b.createdAt.toMillis() : 0;
        return bTime - aTime;
      })
      .slice(0, 5)
      .map((order: any) => ({
        id: order.id || order.offlineId,
        branchName: branchesMap.get(order.branchId) || 'N/A',
        totalAmount: order.totalAmount,
        paymentStatus: order.paymentStatus,
      }));
  } catch (e: any) {
    console.error('Dashboard data fetch failed:', e);
    error = e.message || 'Failed to load dashboard data';
  }

  if (error) {
    return (
      <AdminLayout>
        <div className="flex flex-col items-center justify-center py-24 text-center">
          <AlertCircle className="h-12 w-12 text-rose-400 mb-4" />
          <h2 className="text-xl font-bold text-slate-100 mb-2">Failed to Load Dashboard</h2>
          <p className="text-sm text-slate-400 max-w-md">
            Could not connect to the database. Please check your Firebase configuration and ensure the
            environment variables are set correctly.
          </p>
          <p className="text-xs text-rose-400/70 mt-4 font-mono">{error}</p>
        </div>
      </AdminLayout>
    );
  }

  return (
    <AdminLayout>
      <div className="space-y-8">
        {/* Page title */}
        <div className="flex justify-between items-center">
          <h1 className="text-3xl font-black text-slate-100 tracking-tight">Dashboard Overview</h1>
          <a
            href="/pos"
            className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white rounded-xl font-bold shadow-lg shadow-emerald-600/10 transition-all text-sm"
          >
            Open POS Terminal ➔
          </a>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="p-6 bg-slate-900/40 border border-slate-900 rounded-2xl flex justify-between items-center relative overflow-hidden group hover:border-slate-800/80 transition-all">
            <div className="absolute top-0 left-0 w-1.5 h-full bg-indigo-500" />
            <div>
              <p className="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Sales Revenue</p>
              <h3 className="text-3xl font-black text-slate-100 mt-2 font-mono">${totalSales.toFixed(2)}</h3>
            </div>
            <div className="p-3.5 bg-indigo-500/10 text-indigo-400 rounded-xl border border-indigo-500/20">
              <DollarSign className="h-6 w-6" />
            </div>
          </div>

          <div className="p-6 bg-slate-900/40 border border-slate-900 rounded-2xl flex justify-between items-center relative overflow-hidden group hover:border-slate-800/80 transition-all">
            <div className="absolute top-0 left-0 w-1.5 h-full bg-emerald-500" />
            <div>
              <p className="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Synced Sales</p>
              <h3 className="text-3xl font-black text-slate-100 mt-2 font-mono">{ordersCount}</h3>
            </div>
            <div className="p-3.5 bg-emerald-500/10 text-emerald-400 rounded-xl border border-emerald-500/20">
              <ShoppingCart className="h-6 w-6" />
            </div>
          </div>

          <div className="p-6 bg-slate-900/40 border border-slate-900 rounded-2xl flex justify-between items-center relative overflow-hidden group hover:border-slate-800/80 transition-all">
            <div className="absolute top-0 left-0 w-1.5 h-full bg-amber-500" />
            <div>
              <p className="text-xs font-bold text-slate-400 uppercase tracking-wider">Estimated Sales Profit</p>
              <h3 className="text-3xl font-black text-slate-100 mt-2 font-mono">${totalProfit.toFixed(2)}</h3>
            </div>
            <div className="p-3.5 bg-amber-500/10 text-amber-400 rounded-xl border border-amber-500/20">
              <TrendingUp className="h-6 w-6" />
            </div>
          </div>
        </div>

        {/* Sales by Branch & Top Products */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div className="p-6 bg-slate-900/20 border border-slate-900 rounded-3xl backdrop-blur-md">
            <h4 className="text-base font-bold text-slate-100 mb-6 flex items-center gap-2">🏢 Sales by Branch</h4>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="border-b border-slate-900 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    <th className="pb-3">Branch</th>
                    <th className="pb-3 text-right">Transactions</th>
                    <th className="pb-3 text-right">Sales Amount</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-900/50">
                  {salesByBranch.length > 0 ? (
                    salesByBranch.map((branch, i) => (
                      <tr key={i} className="hover:bg-slate-900/10">
                        <td className="py-4 text-sm font-semibold text-slate-200">{branch.branchName}</td>
                        <td className="py-4 text-sm text-slate-400 text-right">{branch.ordersCount}</td>
                        <td className="py-4 text-sm font-bold text-right text-slate-100 font-mono">
                          ${branch.totalSales.toFixed(2)}
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan={3} className="py-6 text-center text-xs text-slate-500">
                        No branch sales registered yet.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>

          <div className="p-6 bg-slate-900/20 border border-slate-900 rounded-3xl backdrop-blur-md">
            <h4 className="text-base font-bold text-slate-100 mb-6 flex items-center gap-2">🔥 Top Selling Products</h4>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="border-b border-slate-900 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    <th className="pb-3">Product</th>
                    <th className="pb-3">SKU</th>
                    <th className="pb-3 text-right">Qty Sold</th>
                    <th className="pb-3 text-right">Revenue</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-900/50">
                  {topProducts.length > 0 ? (
                    topProducts.map((prod, i) => (
                      <tr key={i} className="hover:bg-slate-900/10">
                        <td className="py-4 text-sm font-semibold text-slate-200">{prod.productName}</td>
                        <td className="py-4 text-xs text-slate-400 font-mono">{prod.sku}</td>
                        <td className="py-4 text-sm text-slate-400 text-right">{prod.totalQty}</td>
                        <td className="py-4 text-sm font-bold text-right text-slate-100 font-mono">
                          ${prod.totalSales.toFixed(2)}
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan={4} className="py-6 text-center text-xs text-slate-500">
                        No sales data available.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Low Stock Warnings & Recent Synced Orders */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="p-6 bg-slate-900/20 border border-slate-900 rounded-3xl border-t-4 border-t-rose-500 lg:col-span-1">
            <h4 className="text-base font-bold text-slate-100 mb-6 flex items-center gap-2">
              <AlertCircle className="h-5 w-5 text-rose-400" />
              Low Stock Warnings
            </h4>
            <div className="space-y-3.5 max-h-96 overflow-y-auto pr-1">
              {lowStockAlerts.length > 0 ? (
                lowStockAlerts.map((alert, i) => (
                  <div
                    key={i}
                    className="p-3.5 bg-rose-500/5 rounded-xl flex justify-between items-center border border-rose-500/10"
                  >
                    <div>
                      <p className="text-sm font-bold text-slate-200">{alert.productName}</p>
                    </div>
                    <span className="px-2.5 py-1 text-xs font-black bg-rose-500/10 text-rose-400 rounded-lg border border-rose-500/20 font-mono">
                      {alert.stockQuantity} left
                    </span>
                  </div>
                ))
              ) : (
                <p className="text-xs text-slate-500 text-center py-8">All stocks healthy! No alerts.</p>
              )}
            </div>
          </div>

          <div className="p-6 bg-slate-900/20 border border-slate-900 rounded-3xl lg:col-span-2">
            <h4 className="text-base font-bold text-slate-100 mb-6 flex items-center gap-2">⏰ Recent Transactions</h4>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="border-b border-slate-900 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    <th className="pb-3">Transaction ID</th>
                    <th className="pb-3">Branch</th>
                    <th className="pb-3 text-right">Total Amount</th>
                    <th className="pb-3 text-center">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-900/50">
                  {recentOrders.length > 0 ? (
                    recentOrders.map((order, i) => (
                      <tr key={i} className="hover:bg-slate-900/10">
                        <td className="py-4 text-xs text-slate-400 font-mono">{order.id}</td>
                        <td className="py-4 text-sm text-slate-300">{order.branchName}</td>
                        <td className="py-4 text-sm font-bold text-right text-slate-100 font-mono">
                          ${Number(order.totalAmount || 0).toFixed(2)}
                        </td>
                        <td className="py-4 text-center">
                          <span className="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            {order.paymentStatus}
                          </span>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan={4} className="py-6 text-center text-xs text-slate-500">
                        No transactions registered yet.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}

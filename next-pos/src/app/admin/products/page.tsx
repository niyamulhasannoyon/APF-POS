import React from 'react';
import AdminLayout from '@/components/AdminLayout';
import { adminDb } from '@/lib/firebase-admin';

export const dynamic = 'force-dynamic';

export default async function AdminProductsPage() {
  const productsSnapshot = await adminDb.collection('products').get();
  const categoriesSnapshot = await adminDb.collection('categories').get();

  const categoriesMap = new Map<string, string>();
  categoriesSnapshot.docs.forEach((doc) => {
    categoriesMap.set(doc.id, doc.data().name);
  });

  const productsList = productsSnapshot.docs.map((doc) => {
    const data = doc.data();
    return {
      id: doc.id,
      name: data.name,
      sku: data.sku,
      barcode: data.barcode,
      price: data.price,
      cost: data.cost,
      stockQuantity: data.stockQuantity || 0,
      status: data.status,
      categoryName: categoriesMap.get(data.categoryId) || 'Uncategorized',
    };
  });

  return (
    <AdminLayout>
      <div className="space-y-8">
        <div className="flex justify-between items-center">
          <h1 className="text-3xl font-black text-slate-100 tracking-tight">Products Catalog</h1>
          <button className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-lg shadow-indigo-600/10 text-xs transition-all">
            + Add New Product
          </button>
        </div>

        <div className="p-6 bg-slate-900/20 border border-slate-900 rounded-3xl backdrop-blur-md">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="border-b border-slate-900 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                  <th className="pb-3">Product Name</th>
                  <th className="pb-3">SKU</th>
                  <th className="pb-3">Category</th>
                  <th className="pb-3 text-right">Cost Price</th>
                  <th className="pb-3 text-right">Selling Price</th>
                  <th className="pb-3 text-right font-mono">Stock level</th>
                  <th className="pb-3 text-center">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-900/50">
                {productsList.length > 0 ? (
                  productsList.map((prod) => (
                    <tr key={prod.id} className="hover:bg-slate-900/10">
                      <td className="py-4 text-sm font-semibold text-slate-200">{prod.name}</td>
                      <td className="py-4 text-xs text-slate-400 font-mono">{prod.sku}</td>
                      <td className="py-4 text-xs text-slate-400">{prod.categoryName}</td>
                      <td className="py-4 text-sm text-slate-400 text-right font-mono">${prod.cost.toFixed(2)}</td>
                      <td className="py-4 text-sm font-bold text-slate-100 text-right font-mono">${prod.price.toFixed(2)}</td>
                      <td className="py-4 text-sm font-bold text-right font-mono text-slate-200">
                        {prod.stockQuantity}
                      </td>
                      <td className="py-4 text-center">
                        <span className="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                          {prod.status}
                        </span>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={7} className="py-8 text-center text-xs text-slate-500">
                      No products registered.
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

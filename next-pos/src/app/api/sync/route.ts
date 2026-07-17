import { NextRequest, NextResponse } from 'next/server';
import { adminDb } from '@/lib/firebase-admin';

export const dynamic = 'force-dynamic';

// 1. GET: Pull fresh database catalog
export async function GET(request: NextRequest) {
  try {
    const categoriesSnapshot = await adminDb.collection('categories').get();
    const customersSnapshot = await adminDb.collection('customers').get();
    const productsSnapshot = await adminDb.collection('products').get();

    const categoriesData = categoriesSnapshot.docs.map((doc) => ({ id: doc.id, ...doc.data() }));
    const customersData = customersSnapshot.docs.map((doc) => ({ id: doc.id, ...doc.data() }));
    const productsData = productsSnapshot.docs.map((doc) => ({ id: doc.id, ...doc.data() }));

    return NextResponse.json({
      categories: categoriesData,
      customers: customersData,
      products: productsData,
      server_timestamp: new Date().toISOString(),
    });
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

// 2. POST: Push offline queue sales
export async function POST(request: NextRequest) {
  try {
    const { orders: pendingOrders } = await request.json();
    const syncedOfflineIds: string[] = [];

    if (Array.isArray(pendingOrders)) {
      for (const order of pendingOrders) {
        try {
          // Check if order already exists
          const orderRef = adminDb.collection('orders').doc(order.offline_id);
          const existingOrder = await orderRef.get();

          if (!existingOrder.exists) {
            const timestamp = new Date();

            // Insert Order
            await orderRef.set({
              offlineId: order.offline_id,
              branchId: order.branch_id,
              userId: order.user_id,
              customerId: order.customer_id || null,
              subtotal: order.subtotal,
              discountAmount: order.discount_amount || 0,
              taxAmount: order.tax_amount || 0,
              totalAmount: order.total_amount,
              paidAmount: order.paid_amount || order.total_amount,
              changeDue: order.change_due || 0,
              paymentStatus: order.payment_status || 'paid',
              syncStatus: 'synced',
              createdAt: order.created_at ? new Date(order.created_at) : timestamp,
              updatedAt: timestamp,
            });

            // Insert Order Items
            if (Array.isArray(order.items)) {
              for (const item of order.items) {
                const itemRef = adminDb.collection('orderItems').doc();
                await itemRef.set({
                  orderId: order.offline_id,
                  productId: String(item.product_id),
                  price: item.price,
                  quantity: item.quantity,
                  discount: item.discount || 0,
                  total: item.subtotal || item.price * item.quantity,
                });

                // Deduct stock in Firestore
                const productRef = adminDb.collection('products').doc(String(item.product_id));
                const productDoc = await productRef.get();
                if (productDoc.exists) {
                  const currentStock = productDoc.data()?.stockQuantity || 0;
                  await productRef.update({
                    stockQuantity: Math.max(0, currentStock - item.quantity),
                  });
                }
              }
            }

            // Insert Sale Payments
            if (order.payment_details) {
              const details = order.payment_details;
              const paymentMethods = [
                { method: 'cash', amount: details.cash },
                { method: 'card', amount: details.card, trx: details.card_trx_id },
                { method: 'mobile', amount: details.mobile, trx: details.mobile_trx_id },
                { method: 'loyalty', amount: details.loyalty },
              ];

              for (const pm of paymentMethods) {
                if (pm.amount > 0) {
                  await adminDb.collection('salePayments').add({
                    orderId: order.offline_id,
                    paymentMethod: pm.method,
                    amount: pm.amount,
                    transactionId: pm.trx || null,
                    status: 'completed',
                    createdAt: timestamp,
                  });
                }
              }
            }
          }

          syncedOfflineIds.push(order.offline_id);
        } catch (itemErr) {
          console.error(`Failed to sync order: ${order.offline_id}`, itemErr);
        }
      }
    }

    return NextResponse.json({
      success: true,
      synced_offline_ids: syncedOfflineIds,
      synced_offline_return_ids: [],
    });
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

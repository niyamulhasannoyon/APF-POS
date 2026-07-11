<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    /**
     * Pull data from the server.
     * Returns products (with branch stock levels), categories, and customers.
     */
    public function pull(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'last_sync' => 'nullable|string', // ISO timestamp
        ]);

        $branchId = $request->query('branch_id');
        $lastSync = $request->query('last_sync');

        // Parse last sync time if provided
        $lastSyncTime = null;
        if ($lastSync) {
            try {
                $lastSyncTime = \Carbon\Carbon::parse($lastSync);
            } catch (\Exception $e) {
                $lastSyncTime = null;
            }
        }

        // 1. Categories
        $categoriesQuery = Category::query();
        if ($lastSyncTime) {
            $categoriesQuery->where('updated_at', '>', $lastSyncTime);
        }
        $categories = $categoriesQuery->get();

        // 2. Customers
        $customersQuery = Customer::query();
        if ($lastSyncTime) {
            $customersQuery->where('updated_at', '>', $lastSyncTime);
        }
        $customers = $customersQuery->get();

        // 3. Products with branch stock
        $productsQuery = Product::where('status', true);
        if ($lastSyncTime) {
            $productsQuery->where('updated_at', '>', $lastSyncTime);
        }
        
        $products = $productsQuery->get()->map(function ($product) use ($branchId) {
            $stock = DB::table('branch_product')
                ->where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->value('stock_quantity') ?? 0.00;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'price' => (float)$product->price,
                'cost' => (float)$product->cost,
                'category_id' => $product->category_id,
                'image_url' => $product->image_url,
                'status' => $product->status,
                'stock_quantity' => (float)$stock,
            ];
        });

        return response()->json([
            'categories' => $categories,
            'customers' => $customers,
            'products' => $products,
            'server_timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Push orders from the client to the server.
     * Enforces idempotency using the unique offline_id UUID.
     */
    public function push(Request $request)
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.offline_id' => 'required|string',
            'orders.*.branch_id' => 'required|exists:branches,id',
            'orders.*.user_id' => 'required|exists:users,id',
            'orders.*.subtotal' => 'required|numeric',
            'orders.*.tax_amount' => 'required|numeric',
            'orders.*.discount_amount' => 'required|numeric',
            'orders.*.total_amount' => 'required|numeric',
            'orders.*.payment_method' => 'required|string',
            'orders.*.items' => 'required|array|min:1',
            'orders.*.items.*.product_id' => 'required|exists:products,id',
            'orders.*.items.*.quantity' => 'required|numeric|min:0.01',
            'orders.*.items.*.price' => 'required|numeric',
            'orders.*.items.*.cost' => 'required|numeric',
            'orders.*.items.*.subtotal' => 'required|numeric',
        ]);

        $syncedIds = [];
        $errors = [];

        foreach ($request->orders as $orderData) {
            $offlineId = $orderData['offline_id'];

            try {
                // Idempotency check: if order already exists, mark as synced and skip
                $existingOrder = Order::where('offline_id', $offlineId)->first();
                if ($existingOrder) {
                    $syncedIds[] = $offlineId;
                    continue;
                }

                // Insert order and adjust inventory within database transaction
                DB::transaction(function () use ($orderData, $offlineId, &$syncedIds) {
                    // Create Order
                    $order = Order::create([
                        'offline_id' => $offlineId,
                        'branch_id' => $orderData['branch_id'],
                        'user_id' => $orderData['user_id'],
                        'customer_id' => $orderData['customer_id'] ?? null,
                        'subtotal' => $orderData['subtotal'],
                        'tax_amount' => $orderData['tax_amount'],
                        'discount_amount' => $orderData['discount_amount'],
                        'total_amount' => $orderData['total_amount'],
                        'payment_method' => $orderData['payment_method'],
                        'payment_status' => $orderData['payment_status'] ?? 'paid',
                        'status' => $orderData['status'] ?? 'completed',
                        'notes' => $orderData['notes'] ?? null,
                        'synced_at' => now(),
                        'created_at' => isset($orderData['created_at']) ? \Carbon\Carbon::parse($orderData['created_at']) : now(),
                    ]);

                    // Create Line Items & Deduct Inventory
                    foreach ($orderData['items'] as $itemData) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $itemData['product_id'],
                            'quantity' => $itemData['quantity'],
                            'price' => $itemData['price'],
                            'cost' => $itemData['cost'],
                            'discount' => $itemData['discount'] ?? 0.00,
                            'subtotal' => $itemData['subtotal'],
                        ]);

                        // Decrement branch stock
                        DB::table('branch_product')
                            ->where('branch_id', $orderData['branch_id'])
                            ->where('product_id', $itemData['product_id'])
                            ->decrement('stock_quantity', $itemData['quantity']);
                    }

                    // Award Loyalty Points (e.g. 1 point per $10 spent)
                    if (!empty($orderData['customer_id'])) {
                        $points = floor($orderData['total_amount'] / 10);
                        if ($points > 0) {
                            Customer::where('id', $orderData['customer_id'])
                                ->increment('loyalty_points', $points);
                        }
                    }

                    $syncedIds[] = $offlineId;
                });

            } catch (\Exception $e) {
                Log::error("Failed to sync order {$offlineId}: " . $e->getMessage());
                $errors[$offlineId] = $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'synced_offline_ids' => $syncedIds,
            'errors' => $errors,
        ]);
    }
}

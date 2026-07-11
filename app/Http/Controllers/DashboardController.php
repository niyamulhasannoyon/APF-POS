<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Core KPIs
        $totalSales = Order::where('status', 'completed')->sum('total_amount');
        $ordersCount = Order::count();
        
        // Calculate Profit: Sum of (quantity * (price - cost))
        $totalProfit = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->sum(DB::raw('quantity * (price - cost)'));

        // 2. Sales by Branch
        $salesByBranch = DB::table('orders')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->select('branches.name as branch_name', DB::raw('SUM(orders.total_amount) as total_sales'), DB::raw('COUNT(orders.id) as orders_count'))
            ->where('orders.status', 'completed')
            ->groupBy('branches.id', 'branches.name')
            ->get();

        // 3. Top Products
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->select('products.name as product_name', 'products.sku', DB::raw('SUM(order_items.quantity) as total_qty'), DB::raw('SUM(order_items.subtotal) as total_sales'))
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_qty', 'desc')
            ->limit(5)
            ->get();

        // 4. Low Stock Alerts (Stock < 20 in any branch)
        $lowStockAlerts = DB::table('branch_product')
            ->join('products', 'branch_product.product_id', '=', 'products.id')
            ->join('branches', 'branch_product.branch_id', '=', 'branches.id')
            ->select('products.name as product_name', 'products.sku', 'branches.name as branch_name', 'branch_product.stock_quantity')
            ->where('branch_product.stock_quantity', '<', 20)
            ->orderBy('branch_product.stock_quantity', 'asc')
            ->limit(10)
            ->get();

        // 5. Recent Synced Orders
        $recentOrders = Order::with(['branch', 'user', 'customer'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalSales',
            'ordersCount',
            'totalProfit',
            'salesByBranch',
            'topProducts',
            'lowStockAlerts',
            'recentOrders'
        ));
    }
}

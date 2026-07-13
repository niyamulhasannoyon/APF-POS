<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Supplier;
use App\Models\StaffShift;
use App\Models\Expense;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportDashboard extends Component
{
    public $timeframe = 'this_month'; // this_month, last_30_days, this_year, all_time

    public function render()
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        if ($this->timeframe === 'last_30_days') {
            $startDate = now()->subDays(30);
            $endDate = now();
        } elseif ($this->timeframe === 'this_year') {
            $startDate = now()->startOfYear();
            $endDate = now()->endOfYear();
        } elseif ($this->timeframe === 'all_time') {
            $startDate = now()->subYears(10);
            $endDate = now()->addYears(10);
        }

        // 1. Core KPIs
        $totalSales = Order::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount');
        $totalSubtotal = Order::whereBetween('created_at', [$startDate, $endDate])->sum('subtotal');
        $totalDiscount = Order::whereBetween('created_at', [$startDate, $endDate])->sum('discount_amount');

        // Gross Profit Calculation
        // sum of quantity * (price - cost) - discounts
        $grossProfit = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->selectRaw('SUM(order_items.quantity * (order_items.price - order_items.cost) - order_items.discount) as profit')
            ->value('profit') ?? 0.00;

        $totalExpenses = Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');
        $activeShifts = StaffShift::where('status', 'open')->count();
        $supplierOutstanding = Supplier::sum('outstanding_balance');

        // 2. Sales by Branch
        $salesByBranch = DB::table('branches')
            ->leftJoin('orders', function($join) use ($startDate, $endDate) {
                $join->on('branches.id', '=', 'orders.branch_id')
                     ->whereBetween('orders.created_at', [$startDate, $endDate]);
            })
            ->select('branches.name as branch_name', DB::raw('COUNT(orders.id) as txn_count'), DB::raw('SUM(orders.total_amount) as total_sales'))
            ->groupBy('branches.id', 'branches.name')
            ->orderBy('total_sales', 'desc')
            ->get();

        // 3. Top Selling Products
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select('products.name as product_name', 'products.sku', DB::raw('SUM(order_items.quantity) as qty_sold'), DB::raw('SUM(order_items.subtotal) as total_revenue'))
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('qty_sold', 'desc')
            ->limit(5)
            ->get();

        // 4. Staff Performance & Commission Settle
        $staffPerformance = DB::table('users')
            ->leftJoin('orders', function($join) use ($startDate, $endDate) {
                $join->on('users.id', '=', 'orders.user_id')
                     ->whereBetween('orders.created_at', [$startDate, $endDate]);
            })
            ->leftJoin('sales_commissions', 'orders.id', '=', 'sales_commissions.order_id')
            ->whereIn('users.role', ['cashier', 'manager'])
            ->select('users.name as cashier_name', DB::raw('COUNT(orders.id) as orders_count'), DB::raw('SUM(orders.total_amount) as total_sales'), DB::raw('SUM(sales_commissions.commission_amount) as earned_commission'))
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_sales', 'desc')
            ->get();

        // 5. Expense by Category
        $expenseCategories = DB::table('expense_categories')
            ->leftJoin('expenses', function($join) use ($startDate, $endDate) {
                $join->on('expense_categories.id', '=', 'expenses.expense_category_id')
                     ->whereBetween('expenses.date', [$startDate, $endDate]);
            })
            ->select('expense_categories.name as category_name', DB::raw('SUM(expenses.amount) as total_spent'))
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderBy('total_spent', 'desc')
            ->get()
            ->filter(fn($c) => $c->total_spent > 0);

        return view('livewire.report-dashboard', compact(
            'totalSales',
            'grossProfit',
            'totalExpenses',
            'activeShifts',
            'supplierOutstanding',
            'salesByBranch',
            'topProducts',
            'staffPerformance',
            'expenseCategories'
        ));
    }
}

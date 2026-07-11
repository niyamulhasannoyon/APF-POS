<?php

use Livewire\Component;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $dateRange = 'this_month'; // today, this_week, this_month, custom
    public $startDate = '';
    public $endDate = '';
    public $selectedBranchId = ''; // empty means all branches

    public $branches = [];

    public function mount()
    {
        $this->branches = Branch::all();
        $this->setPresetDates();
    }

    public function updatedDateRange()
    {
        $this->setPresetDates();
    }

    public function setPresetDates()
    {
        if ($this->dateRange === 'today') {
            $this->startDate = now()->startOfDay()->toDateTimeString();
            $this->endDate = now()->endOfDay()->toDateTimeString();
        } elseif ($this->dateRange === 'this_week') {
            $this->startDate = now()->startOfWeek()->toDateTimeString();
            $this->endDate = now()->endOfWeek()->toDateTimeString();
        } elseif ($this->dateRange === 'this_month') {
            $this->startDate = now()->startOfMonth()->toDateTimeString();
            $this->endDate = now()->endOfMonth()->toDateTimeString();
        }
    }

    public function getReportData()
    {
        $query = Order::where('status', 'completed')
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->selectedBranchId) {
            $query->where('branch_id', $this->selectedBranchId);
        }

        $orders = $query->get();

        $totalSales = $orders->sum('total_amount');
        $ordersCount = $orders->count();
        $taxCollected = $orders->sum('tax_amount');
        $discountGiven = $orders->sum('discount_amount');

        // Calculate Cost of Goods Sold (COGS)
        $orderIds = $orders->pluck('id');
        $cogs = DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->sum(DB::raw('quantity * cost'));

        $profit = $totalSales - $taxCollected - $cogs; // sales without tax - cogs
        $aov = $ordersCount > 0 ? $totalSales / $ordersCount : 0.00;

        // Top 5 Best-Sellers
        $bestSellers = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereIn('order_items.order_id', $orderIds)
            ->select('products.name as product_name', 'products.sku', DB::raw('SUM(order_items.quantity) as total_qty'), DB::raw('SUM(order_items.subtotal) as total_sales'))
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_qty', 'desc')
            ->limit(5)
            ->get();

        // Slow-Movers
        $slowMovers = DB::table('products')
            ->leftJoin('order_items', function($join) use ($orderIds) {
                $join->on('products.id', '=', 'order_items.product_id')
                    ->whereIn('order_items.order_id', $orderIds);
            })
            ->select('products.name as product_name', 'products.sku', DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_qty'))
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_qty', 'asc')
            ->limit(5)
            ->get();

        return [
            'totalSales' => $totalSales,
            'ordersCount' => $ordersCount,
            'taxCollected' => $taxCollected,
            'discountGiven' => $discountGiven,
            'cogs' => $cogs,
            'profit' => $profit,
            'aov' => $aov,
            'bestSellers' => $bestSellers,
            'slowMovers' => $slowMovers
        ];
    }

    public function exportCSV()
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=apf_sales_report_" . date('Y-m-d') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = Order::with(['branch', 'user', 'items.product'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->selectedBranchId) {
            $query->where('branch_id', $this->selectedBranchId);
        }

        $orders = $query->get();

        $callback = function() use($orders) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, ['Order ID', 'Branch', 'Cashier', 'Subtotal', 'Tax', 'Discount', 'Total Paid', 'Payment Method', 'Date']);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->offline_id,
                    $order->branch->name ?? 'N/A',
                    $order->user->name ?? 'N/A',
                    $order->subtotal,
                    $order->tax_amount,
                    $order->discount_amount,
                    $order->total_amount,
                    $order->payment_method,
                    $order->created_at->toDateTimeString()
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
};
?>

@php
    $data = $this->getReportData();
@endphp

<div class="space-y-6">
    <!-- Filters Toolbar -->
    <div class="bg-white p-4 shadow-sm sm:rounded-lg border border-gray-150 flex flex-wrap gap-4 items-center justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase">Date Filter</label>
                <select wire:model.live="dateRange" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-xs">
                    <option value="today">Today</option>
                    <option value="this_week">This Week</option>
                    <option value="this_month">This Month</option>
                    <option value="custom">Custom Date Range</option>
                </select>
            </div>

            @if($dateRange === 'custom')
                <div class="flex items-center gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase">Start Date</label>
                        <input type="date" wire:model.live="startDate" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase">End Date</label>
                        <input type="date" wire:model.live="endDate" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-xs">
                    </div>
                </div>
            @endif

            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase">Branch / Store</label>
                <select wire:model.live="selectedBranchId" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 text-xs">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button wire:click="exportCSV" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold transition text-xs shadow-sm flex items-center gap-1.5 self-end">
            📥 Download CSV Report
        </button>
    </div>

    <!-- Core Metrics Dashboard Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Revenue Card -->
        <div class="bg-white p-5 rounded-lg border border-gray-150 shadow-sm space-y-2">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Gross Revenue</span>
            <div class="text-2xl font-black text-gray-900">${{ number_format($data['totalSales'], 2) }}</div>
            <div class="text-xs text-gray-500 font-semibold">Total revenue generated (incl. tax)</div>
        </div>

        <!-- Orders Card -->
        <div class="bg-white p-5 rounded-lg border border-gray-150 shadow-sm space-y-2">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Transactions count</span>
            <div class="text-2xl font-black text-gray-900">{{ number_format($data['ordersCount']) }} sales</div>
            <div class="text-xs text-gray-500 font-semibold">Average Ticket Value: ${{ number_format($data['aov'], 2) }}</div>
        </div>

        <!-- Cost Card (COGS) -->
        <div class="bg-white p-5 rounded-lg border border-gray-150 shadow-sm space-y-2">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Cost of Goods (COGS)</span>
            <div class="text-2xl font-black text-rose-600">${{ number_format($data['cogs'], 2) }}</div>
            <div class="text-xs text-gray-500 font-semibold">Procurement cost value of sold stock</div>
        </div>

        <!-- Net Profit Card -->
        <div class="bg-white p-5 rounded-lg border border-gray-150 shadow-sm space-y-2">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Estimated Net Profit</span>
            <div class="text-2xl font-black text-emerald-600">${{ number_format($data['profit'], 2) }}</div>
            <div class="text-xs text-gray-500 font-semibold">Excluding tax liabilities</div>
        </div>
    </div>

    <!-- Product Analytics Split Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Best Sellers -->
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-150 overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-100 font-bold text-xs text-gray-600 uppercase">
                🔥 Top 5 Best-Sellers
            </div>
            <table class="min-w-full text-left text-xs divide-y divide-gray-150">
                <thead class="bg-gray-100 text-gray-550 font-bold uppercase text-[9px]">
                    <tr>
                        <th class="px-4 py-2">Product Name</th>
                        <th class="px-4 py-2">Quantity Sold</th>
                        <th class="px-4 py-2">Revenue contribution</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($data['bestSellers'] as $best)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $best->product_name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $best->total_qty }} pcs</td>
                            <td class="px-4 py-3 font-bold text-indigo-900">${{ number_format($best->total_sales, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-gray-400 py-6">No sales logs found in this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Slow Movers -->
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-150 overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-100 font-bold text-xs text-gray-600 uppercase">
                🐢 Slow-Moving Products
            </div>
            <table class="min-w-full text-left text-xs divide-y divide-gray-150">
                <thead class="bg-gray-100 text-gray-550 font-bold uppercase text-[9px]">
                    <tr>
                        <th class="px-4 py-2">Product Name</th>
                        <th class="px-4 py-2">SKU</th>
                        <th class="px-4 py-2">Units Sold</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($data['slowMovers'] as $slow)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $slow->product_name }}</td>
                            <td class="px-4 py-3 text-gray-700 font-mono">{{ $slow->sku }}</td>
                            <td class="px-4 py-3 font-bold text-rose-700">{{ $slow->total_qty }} pcs</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-gray-400 py-6">No catalog items cataloged.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tax Report Details -->
    <div class="bg-white p-5 rounded-lg border border-gray-150 shadow-sm max-w-md space-y-3">
        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">📋 Sales Tax Liability Summary</h4>
        <div class="flex justify-between items-center text-xs border-b border-gray-100 pb-2">
            <span class="text-gray-600">Taxable Sales base:</span>
            <span class="font-bold text-gray-800">${{ number_format($data['totalSales'] - $data['taxCollected'], 2) }}</span>
        </div>
        <div class="flex justify-between items-center text-xs">
            <span class="text-gray-600">Total Tax Collected (15%):</span>
            <span class="font-black text-indigo-700 text-sm">${{ number_format($data['taxCollected'], 2) }}</span>
        </div>
        <div class="text-[10px] text-gray-400">
            For filing purposes, matching orders were query filtered across dates: {{ $startDate }} to {{ $endDate }}.
        </div>
    </div>
</div>
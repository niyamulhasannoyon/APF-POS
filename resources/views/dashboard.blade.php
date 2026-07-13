<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-extrabold text-2xl text-slate-100 tracking-tight">
                {{ __('messages.dashboard') }}
            </h2>
            <a href="{{ route('pos.index') }}" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white rounded-lg font-bold shadow-lg shadow-emerald-600/10 transition-all duration-150 text-sm">
                {{ __('messages.open_pos') }} ➔
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Sales -->
                <div class="glass-card border-l-4 border-indigo-500 p-6 flex justify-between items-center glass-card-hover">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('messages.total_sales') }}</p>
                        <h3 class="text-3xl font-black text-slate-100 mt-2 font-mono">${{ number_format($totalSales, 2) }}</h3>
                    </div>
                    <div class="p-3.5 bg-indigo-500/10 text-indigo-400 rounded-xl border border-indigo-500/20">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Transactions Count -->
                <div class="glass-card border-l-4 border-emerald-500 p-6 flex justify-between items-center glass-card-hover">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('messages.transactions') }}</p>
                        <h3 class="text-3xl font-black text-slate-100 mt-2 font-mono">{{ number_format($ordersCount) }}</h3>
                    </div>
                    <div class="p-3.5 bg-emerald-500/10 text-emerald-400 rounded-xl border border-emerald-500/20">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Estimated Profit -->
                <div class="glass-card border-l-4 border-amber-500 p-6 flex justify-between items-center glass-card-hover">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('messages.estimated_profit') }}</p>
                        <h3 class="text-3xl font-black text-slate-100 mt-2 font-mono">${{ number_format($totalProfit, 2) }}</h3>
                    </div>
                    <div class="p-3.5 bg-amber-500/10 text-amber-400 rounded-xl border border-amber-500/20">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Two Column Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Sales by Branch -->
                <div class="glass-card">
                    <h4 class="text-base font-bold text-slate-100 mb-4 flex items-center gap-2">🏢 {{ __('messages.sales_by_branch') }}</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="py-2.5">Branch</th>
                                    <th class="py-2.5 text-right">Transactions</th>
                                    <th class="py-2.5 text-right font-mono">Sales Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/40">
                                @forelse($salesByBranch as $branch)
                                    <tr class="hover:bg-slate-900/20 transition-colors duration-150">
                                        <td class="py-3.5 text-sm text-slate-200 font-semibold">{{ $branch->branch_name }}</td>
                                        <td class="py-3.5 text-sm text-slate-400 text-right">{{ number_format($branch->orders_count) }}</td>
                                        <td class="py-3.5 text-sm text-slate-100 text-right font-bold font-mono">${{ number_format($branch->total_sales, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-4 text-center text-xs text-slate-500">No branch sales found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Selling Products -->
                <div class="glass-card">
                    <h4 class="text-base font-bold text-slate-100 mb-4 flex items-center gap-2">🔥 {{ __('messages.top_selling_products') }}</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="py-2.5">Product</th>
                                    <th class="py-2.5">SKU</th>
                                    <th class="py-2.5 text-right">Qty Sold</th>
                                    <th class="py-2.5 text-right font-mono">Sales Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/40">
                                @forelse($topProducts as $prod)
                                    <tr class="hover:bg-slate-900/20 transition-colors duration-150">
                                        <td class="py-3.5 text-sm text-slate-200 font-semibold">{{ $prod->product_name }}</td>
                                        <td class="py-3.5 text-xs text-slate-400 font-mono">{{ $prod->sku }}</td>
                                        <td class="py-3.5 text-sm text-slate-400 text-right">{{ number_format($prod->total_qty) }}</td>
                                        <td class="py-3.5 text-sm text-slate-100 text-right font-bold font-mono">${{ number_format($prod->total_sales, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-center text-xs text-slate-500">No product sales found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Low Stock Warnings & Recent Synced Orders -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Low Stock Warnings -->
                <div class="glass-card lg:col-span-1 border-t-4 border-rose-500">
                    <h4 class="text-base font-bold text-slate-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-rose-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        {{ __('messages.low_stock_warnings') }}
                    </h4>
                    <div class="space-y-3.5 max-h-96 overflow-y-auto pr-1">
                        @forelse($lowStockAlerts as $alert)
                            <div class="p-3.5 bg-rose-500/5 rounded-lg flex justify-between items-center border border-rose-500/10">
                                <div>
                                    <p class="text-sm font-bold text-slate-200">{{ $alert->product_name }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $alert->branch_name }}</p>
                                </div>
                                <span class="px-2.5 py-1 text-xs font-extrabold bg-rose-500/10 text-rose-400 rounded-lg border border-rose-500/20 font-mono">
                                    {{ number_format($alert->stock_quantity) }} left
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 text-center py-6">All stocks healthy! No alerts.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="glass-card lg:col-span-2">
                    <h4 class="text-base font-bold text-slate-100 mb-4">⏰ {{ __('messages.recent_transactions') }}</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="py-2.5">Transaction ID</th>
                                    <th class="py-2.5">Branch</th>
                                    <th class="py-2.5">Cashier</th>
                                    <th class="py-2.5 text-right font-mono">Total</th>
                                    <th class="py-2.5 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/40">
                                @forelse($recentOrders as $order)
                                    <tr class="hover:bg-slate-900/20 transition-colors duration-150">
                                        <td class="py-3.5 text-xs text-slate-400 font-mono">
                                            <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-400 hover:text-indigo-300 font-semibold hover:underline">
                                                {{ Str::limit($order->offline_id, 16) }}
                                            </a>
                                        </td>
                                        <td class="py-3.5 text-sm text-slate-300">{{ $order->branch->name }}</td>
                                        <td class="py-3.5 text-sm text-slate-350">{{ $order->user->name }}</td>
                                        <td class="py-3.5 text-sm text-slate-100 text-right font-bold font-mono">${{ number_format($order->total_amount, 2) }}</td>
                                        <td class="py-3.5 text-center select-none">
                                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                {{ $order->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-xs text-slate-500">No synced orders found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

<div class="space-y-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="text-lg font-bold text-slate-100">Analytics & Sales Reports</h3>
        <div class="flex gap-2 bg-slate-900/60 p-1 rounded-lg border border-slate-800">
            <button wire:click="$set('timeframe', 'this_month')" class="px-3 py-1.5 rounded text-xs font-bold transition {{ $timeframe === 'this_month' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200' }}">This Month</button>
            <button wire:click="$set('timeframe', 'last_30_days')" class="px-3 py-1.5 rounded text-xs font-bold transition {{ $timeframe === 'last_30_days' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200' }}">Last 30 Days</button>
            <button wire:click="$set('timeframe', 'this_year')" class="px-3 py-1.5 rounded text-xs font-bold transition {{ $timeframe === 'this_year' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200' }}">This Year</button>
            <button wire:click="$set('timeframe', 'all_time')" class="px-3 py-1.5 rounded text-xs font-bold transition {{ $timeframe === 'all_time' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200' }}">All Time</button>
        </div>
    </div>

    <!-- Core KPI summary cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 select-none">
        <div class="glass-card border-l-4 border-emerald-500 p-5 glass-card-hover">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Sales</p>
            <h4 class="text-xl font-black text-slate-100 mt-2 font-mono">${{ number_format($totalSales, 2) }}</h4>
        </div>
        <div class="glass-card border-l-4 border-indigo-500 p-5 glass-card-hover">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Gross Profit</p>
            <h4 class="text-xl font-black text-slate-100 mt-2 font-mono">${{ number_format($grossProfit, 2) }}</h4>
        </div>
        <div class="glass-card border-l-4 border-rose-500 p-5 glass-card-hover">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Expenses</p>
            <h4 class="text-xl font-black text-slate-100 mt-2 font-mono">${{ number_format($totalExpenses, 2) }}</h4>
        </div>
        <div class="glass-card border-l-4 border-blue-500 p-5 glass-card-hover">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Active Shifts</p>
            <h4 class="text-xl font-black text-slate-100 mt-2 font-mono">{{ $activeShifts }} open</h4>
        </div>
        <div class="glass-card border-l-4 border-amber-500 p-5 glass-card-hover">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Supplier Balances</p>
            <h4 class="text-xl font-black text-slate-100 mt-2 font-mono">${{ number_format($supplierOutstanding, 2) }}</h4>
        </div>
    </div>

    <!-- Second Row Detail Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Sales by Branch -->
        <div class="glass-card">
            <h4 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4">🏢 Sales by Branch</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-2.5">Branch</th>
                            <th class="py-2.5 text-right">Transactions</th>
                            <th class="py-2.5 text-right font-mono">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @forelse ($salesByBranch as $branch)
                            <tr class="hover:bg-slate-900/10 transition-colors duration-150">
                                <td class="py-3 text-sm font-semibold text-slate-200">{{ $branch->branch_name }}</td>
                                <td class="py-3 text-sm text-slate-400 text-right">{{ number_format($branch->txn_count) }}</td>
                                <td class="py-3 text-sm font-bold text-slate-100 text-right font-mono">${{ number_format($branch->total_sales ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-xs text-slate-500">No branch sales.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Selling Products with relative progress bars -->
        <div class="glass-card">
            <h4 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4">🔥 Top Selling Products</h4>
            <div class="space-y-4">
                @php
                    $maxQty = $topProducts->max('qty_sold') ?: 1;
                @endphp
                @forelse ($topProducts as $prod)
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-semibold text-slate-200">{{ $prod->product_name }}</span>
                            <span class="text-slate-400 font-mono">{{ number_format($prod->qty_sold) }} units (${{ number_format($prod->total_revenue, 2) }})</span>
                        </div>
                        <div class="w-full h-2 bg-slate-950 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full" style="width: {{ ($prod->qty_sold / $maxQty) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 py-6 text-center">No sales recorded.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Third Row Leaderboards & Expenses -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Staff Performance leaderboard -->
        <div class="glass-card lg:col-span-2">
            <h4 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4">🥇 Cashier Leaderboard</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-2.5">Cashier</th>
                            <th class="py-2.5 text-right">Transactions</th>
                            <th class="py-2.5 text-right font-mono">Total Sales</th>
                            <th class="py-2.5 text-right font-mono">Commission Earned</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @forelse ($staffPerformance as $staff)
                            <tr class="hover:bg-slate-900/10 transition-colors duration-150">
                                <td class="py-3 text-sm font-semibold text-slate-200">{{ $staff->cashier_name }}</td>
                                <td class="py-3 text-sm text-slate-400 text-right">{{ number_format($staff->orders_count) }}</td>
                                <td class="py-3 text-sm font-bold text-slate-100 text-right font-mono">${{ number_format($staff->total_sales ?? 0, 2) }}</td>
                                <td class="py-3 text-sm font-bold text-emerald-400 text-right font-mono">${{ number_format($staff->earned_commission ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-xs text-slate-500">No staff stats.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expense breakdown by category -->
        <div class="glass-card lg:col-span-1">
            <h4 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4">💸 Expenses by Category</h4>
            <div class="space-y-3.5">
                @forelse ($expenseCategories as $cat)
                    <div class="p-3.5 bg-slate-950/20 border border-slate-800/80 rounded-lg flex justify-between items-center">
                        <span class="text-xs font-semibold text-slate-300">{{ $cat->category_name }}</span>
                        <span class="text-xs font-extrabold text-slate-100 font-mono">${{ number_format($cat->total_spent, 2) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 py-6 text-center">No expenses logged.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

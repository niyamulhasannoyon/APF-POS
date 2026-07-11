<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('messages.dashboard') }}
            </h2>
            <a href="{{ route('pos.index') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold shadow-md transition duration-150 ease-in-out">
                {{ __('messages.open_pos') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Sales -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-600">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.total_sales') }}</p>
                            <h3 class="text-3xl font-extrabold text-gray-900 mt-1">${{ number_format($totalSales, 2) }}</h3>
                        </div>
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-full">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Transactions Count -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-emerald-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.transactions') }}</p>
                            <h3 class="text-3xl font-extrabold text-gray-900 mt-1">{{ number_format($ordersCount) }}</h3>
                        </div>
                        <div class="p-3 bg-emerald-50 text-emerald-600 rounded-full">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Estimated Profit -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-amber-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.estimated_profit') }}</p>
                            <h3 class="text-3xl font-extrabold text-gray-900 mt-1">${{ number_format($totalProfit, 2) }}</h3>
                        </div>
                        <div class="p-3 bg-amber-50 text-amber-600 rounded-full">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Sales by Branch -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-lg font-bold text-gray-900 mb-4">{{ __('messages.sales_by_branch') }}</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 text-sm font-bold text-gray-500">Branch</th>
                                    <th class="py-2 text-sm font-bold text-gray-500 text-right">Transactions</th>
                                    <th class="py-2 text-sm font-bold text-gray-500 text-right">Sales Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesByBranch as $branch)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                        <td class="py-3 text-sm text-gray-800 font-semibold">{{ $branch->branch_name }}</td>
                                        <td class="py-3 text-sm text-gray-600 text-right">{{ number_format($branch->orders_count) }}</td>
                                        <td class="py-3 text-sm text-gray-900 text-right font-semibold">${{ number_format($branch->total_sales, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-4 text-center text-sm text-gray-500">No branch sales found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Selling Products -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-lg font-bold text-gray-900 mb-4">{{ __('messages.top_selling_products') }}</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 text-sm font-bold text-gray-500">Product</th>
                                    <th class="py-2 text-sm font-bold text-gray-500">SKU</th>
                                    <th class="py-2 text-sm font-bold text-gray-500 text-right">Quantity Sold</th>
                                    <th class="py-2 text-sm font-bold text-gray-500 text-right">Sales Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $prod)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                        <td class="py-3 text-sm text-gray-800 font-semibold">{{ $prod->product_name }}</td>
                                        <td class="py-3 text-sm text-gray-600 font-mono">{{ $prod->sku }}</td>
                                        <td class="py-3 text-sm text-gray-600 text-right">{{ number_format($prod->total_qty, 2) }}</td>
                                        <td class="py-3 text-sm text-gray-900 text-right font-semibold">${{ number_format($prod->total_sales, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-center text-sm text-gray-500">No product sales found.</td>
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
                <div class="bg-white shadow-sm sm:rounded-lg p-6 lg:col-span-1 border-t-4 border-rose-500">
                    <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-rose-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        {{ __('messages.low_stock_warnings') }}
                    </h4>
                    <div class="space-y-4 max-h-96 overflow-y-auto pr-1">
                        @forelse($lowStockAlerts as $alert)
                            <div class="p-3 bg-rose-50 rounded-lg flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-bold text-gray-800">{{ $alert->product_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $alert->branch_name }}</p>
                                </div>
                                <span class="px-2.5 py-1 text-xs font-bold bg-rose-200 text-rose-800 rounded-full">
                                    {{ number_format($alert->stock_quantity) }} Left
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 text-center py-4">All stocks healthy! No alerts.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6 lg:col-span-2">
                    <h4 class="text-lg font-bold text-gray-900 mb-4">{{ __('messages.recent_transactions') }}</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="py-2 text-sm font-bold text-gray-500">Transaction UUID</th>
                                    <th class="py-2 text-sm font-bold text-gray-500">Branch</th>
                                    <th class="py-2 text-sm font-bold text-gray-500">Cashier</th>
                                    <th class="py-2 text-sm font-bold text-gray-500 text-right">Total</th>
                                    <th class="py-2 text-sm font-bold text-gray-500 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                        <td class="py-3 text-sm text-gray-600 font-mono">
                                            <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 hover:underline">
                                                {{ Str::limit($order->offline_id, 13) }}
                                            </a>
                                        </td>
                                        <td class="py-3 text-sm text-gray-600">{{ $order->branch->name }}</td>
                                        <td class="py-3 text-sm text-gray-600">{{ $order->user->name }}</td>
                                        <td class="py-3 text-sm text-gray-900 text-right font-semibold">${{ number_format($order->total_amount, 2) }}</td>
                                        <td class="py-3 text-sm text-center">
                                            <span class="px-2 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-800 rounded-full">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-sm text-gray-500">No synced orders found.</td>
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

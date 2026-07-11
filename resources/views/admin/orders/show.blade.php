<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Transaction Invoice') }}
            </h2>
            <a href="{{ route('admin.orders.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-semibold transition text-sm">
                ➔ Back to list
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Invoice Meta Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Transaction Info -->
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Order Metadata</h4>
                    <div class="space-y-1.5 text-sm text-gray-700">
                        <div><span class="text-gray-400">Offline ID:</span></div>
                        <div class="font-mono font-bold text-xs bg-gray-50 p-1.5 rounded break-all border border-gray-100 select-all">{{ $order->offline_id }}</div>
                        <div><span class="text-gray-400">Date Logged:</span> <span class="font-semibold">{{ $order->created_at->format('Y-m-d H:i:s') }}</span></div>
                        <div><span class="text-gray-400">Sync Date:</span> <span class="font-semibold">{{ $order->synced_at ? $order->synced_at->format('Y-m-d H:i:s') : 'Offline Only' }}</span></div>
                    </div>
                </div>

                <!-- Branch & Cashier -->
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Branch & Cashier</h4>
                    <div class="space-y-1.5 text-sm text-gray-700">
                        <div><span class="text-gray-400">Branch Name:</span> <span class="font-bold text-gray-900">{{ $order->branch->name }}</span></div>
                        <div><span class="text-gray-400">Cashier:</span> <span class="font-semibold text-gray-800">{{ $order->user->name }}</span></div>
                        <div><span class="text-gray-400">Email:</span> <span class="font-mono text-xs">{{ $order->user->email }}</span></div>
                    </div>
                </div>

                <!-- Customer Details -->
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Customer Info</h4>
                    <div class="space-y-1.5 text-sm text-gray-700">
                        @if($order->customer)
                            <div><span class="text-gray-400">Name:</span> <span class="font-bold text-gray-900">{{ $order->customer->name }}</span></div>
                            <div><span class="text-gray-400">Phone:</span> <span class="font-mono">{{ $order->customer->phone ?? 'N/A' }}</span></div>
                            <div><span class="text-gray-400">Email:</span> <span class="text-xs">{{ $order->customer->email ?? 'N/A' }}</span></div>
                        @else
                            <div class="text-gray-500 font-semibold italic py-2">Walk-in Customer</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h4 class="text-lg font-bold text-gray-900 mb-4">Invoice Items</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-sm font-bold text-gray-500">
                                <th class="py-2.5">Item Description</th>
                                <th class="py-2.5">SKU / Category</th>
                                <th class="py-2.5 text-right">Quantity</th>
                                <th class="py-2.5 text-right">Unit Price</th>
                                <th class="py-2.5 text-right">Discount</th>
                                <th class="py-2.5 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="py-3 text-sm font-bold text-gray-900">{{ $item->product->name }}</td>
                                    <td class="py-3 text-sm">
                                        <div class="text-gray-500 font-mono text-xs">{{ $item->product->sku }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $item->product->category->name }}</div>
                                    </td>
                                    <td class="py-3 text-sm text-right font-mono text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="py-3 text-sm text-right font-mono text-gray-700">${{ number_format($item->price, 2) }}</td>
                                    <td class="py-3 text-sm text-right font-mono text-rose-500">
                                        @if($item->discount > 0)
                                            -${{ number_format($item->discount, 2) }}
                                        @else
                                            $0.00
                                        @endif
                                    </td>
                                    <td class="py-3 text-sm text-right font-mono text-gray-950 font-bold">${{ number_format($item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Back-Office Margin Audit Card -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                    <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Back-Office Margin Audit</h4>
                    <div class="space-y-3 text-sm">
                        @php
                            $totalCost = $order->items->sum(fn($i) => $i->quantity * $i->cost);
                            $grossMargin = $order->total_amount - $totalCost;
                            $marginPercent = $order->total_amount > 0 ? ($grossMargin / $order->total_amount) * 100 : 0;
                        @endphp
                        <div class="flex justify-between">
                            <span class="text-gray-500">Cost of Goods Sold (COGS):</span>
                            <span class="font-mono font-semibold text-gray-700">${{ number_format($totalCost, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Gross Sale Value:</span>
                            <span class="font-mono font-semibold text-gray-700">${{ number_format($order->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-3">
                            <span class="font-bold text-gray-900">Gross Profit Margin:</span>
                            <span class="font-mono font-bold text-emerald-600">${{ number_format($grossMargin, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Profit Percentage:</span>
                            <span class="font-bold text-emerald-600">{{ number_format($marginPercent, 2) }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Invoice Total Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100 flex flex-col justify-between">
                    <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Financial Summary</h4>
                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal:</span>
                            <span class="font-mono font-semibold text-gray-700">${{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tax Amount:</span>
                            <span class="font-mono font-semibold text-gray-700">${{ number_format($order->tax_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Discount Applied:</span>
                            <span class="font-mono font-semibold text-rose-500">-${{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-3">
                            <span class="font-bold text-gray-900">Grand Total:</span>
                            <span class="font-mono font-extrabold text-indigo-600 text-lg">${{ number_format($order->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between mt-4 bg-indigo-50/50 p-2.5 rounded text-xs">
                            <span class="text-gray-500">Payment Method:</span>
                            <span class="font-bold text-indigo-800 capitalize">{{ $order->payment_method }} ({{ $order->payment_status }})</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

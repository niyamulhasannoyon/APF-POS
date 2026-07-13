<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-xl text-slate-100 leading-tight">
                {{ __('Transaction Invoice') }}
            </h2>
            <a href="{{ route('admin.orders.index') }}" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 active:bg-slate-750 text-slate-200 border border-slate-700 rounded-lg font-semibold transition text-xs">
                ➔ Back to list
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Invoice Meta Information -->
            <div class="glass-card grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Transaction Info -->
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Order Metadata</h4>
                    <div class="space-y-1.5 text-sm text-slate-300">
                        <div><span class="text-slate-500">Offline ID:</span></div>
                        <div class="font-mono font-bold text-xs bg-slate-950/60 p-2 rounded break-all border border-slate-800/80 select-all text-indigo-400">{{ $order->offline_id }}</div>
                        <div class="mt-2"><span class="text-slate-500">Date Logged:</span> <span class="font-semibold text-slate-200">{{ $order->created_at->format('Y-m-d H:i:s') }}</span></div>
                        <div><span class="text-slate-500">Sync Date:</span> <span class="font-semibold text-slate-200">{{ $order->synced_at ? $order->synced_at->format('Y-m-d H:i:s') : 'Offline Only' }}</span></div>
                    </div>
                </div>

                <!-- Branch & Cashier -->
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Branch & Cashier</h4>
                    <div class="space-y-1.5 text-sm text-slate-300">
                        <div><span class="text-slate-500">Branch Name:</span> <span class="font-bold text-slate-100">{{ $order->branch->name }}</span></div>
                        <div><span class="text-slate-500">Cashier:</span> <span class="font-semibold text-slate-200">{{ $order->user->name }}</span></div>
                        <div><span class="text-slate-500">Email:</span> <span class="font-mono text-xs text-slate-450">{{ $order->user->email }}</span></div>
                    </div>
                </div>

                <!-- Customer Details -->
                <div>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Customer Info</h4>
                    <div class="space-y-1.5 text-sm text-slate-300">
                        @if($order->customer)
                            <div><span class="text-slate-500">Name:</span> <span class="font-bold text-slate-100">{{ $order->customer->name }}</span></div>
                            <div><span class="text-slate-500">Phone:</span> <span class="font-mono text-slate-250">{{ $order->customer->phone ?? 'N/A' }}</span></div>
                            <div><span class="text-slate-500">Email:</span> <span class="text-xs text-slate-350">{{ $order->customer->email ?? 'N/A' }}</span></div>
                        @else
                            <div class="text-slate-500 font-semibold italic py-2">Walk-in Customer</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="glass-card">
                <h4 class="text-base font-bold text-slate-100 mb-4">Invoice Items</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-2.5">Item Description</th>
                                <th class="py-2.5">SKU / Category</th>
                                <th class="py-2.5 text-right">Quantity</th>
                                <th class="py-2.5 text-right font-mono">Unit Price</th>
                                <th class="py-2.5 text-right font-mono">Discount</th>
                                <th class="py-2.5 text-right font-mono">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40">
                            @foreach($order->items as $item)
                                <tr class="hover:bg-slate-900/10 transition-colors">
                                    <td class="py-3 text-sm font-bold text-slate-200">{{ $item->product->name }}</td>
                                    <td class="py-3 text-sm">
                                        <div class="text-slate-400 font-mono text-xs">{{ $item->product->sku }}</div>
                                        <div class="text-xs text-slate-500 mt-1">{{ $item->product->category->name }}</div>
                                    </td>
                                    <td class="py-3 text-sm text-right font-mono text-slate-300">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="py-3 text-sm text-right font-mono text-slate-300">${{ number_format($item->price, 2) }}</td>
                                    <td class="py-3 text-sm text-right font-mono text-rose-400">
                                        @if($item->discount > 0)
                                            -${{ number_format($item->discount, 2) }}
                                        @else
                                            $0.00
                                        @endif
                                    </td>
                                    <td class="py-3 text-sm text-right font-mono text-slate-100 font-bold">${{ number_format($item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Back-Office Margin Audit Card -->
                <div class="glass-card border-slate-800/80 bg-slate-900/30">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Back-Office Margin Audit</h4>
                    <div class="space-y-3.5 text-sm text-slate-300">
                        @php
                            $totalCost = $order->items->sum(fn($i) => $i->quantity * $i->cost);
                            $grossMargin = $order->total_amount - $totalCost;
                            $marginPercent = $order->total_amount > 0 ? ($grossMargin / $order->total_amount) * 100 : 0;
                        @endphp
                        <div class="flex justify-between">
                            <span class="text-slate-500">Cost of Goods Sold (COGS):</span>
                            <span class="font-mono font-semibold text-slate-300">${{ number_format($totalCost, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Gross Sale Value:</span>
                            <span class="font-mono font-semibold text-slate-300">${{ number_format($order->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-800/80 pt-3">
                            <span class="font-bold text-slate-200">Gross Profit Margin:</span>
                            <span class="font-mono font-bold text-emerald-400">${{ number_format($grossMargin, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-550">Profit Percentage:</span>
                            <span class="font-bold text-emerald-400">{{ number_format($marginPercent, 2) }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Invoice Total Card -->
                <div class="glass-card flex flex-col justify-between">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Financial Summary</h4>
                    <div class="space-y-3.5 text-sm text-slate-300">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Subtotal:</span>
                            <span class="font-mono font-semibold text-slate-300">${{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Tax Amount:</span>
                            <span class="font-mono font-semibold text-slate-300">${{ number_format($order->tax_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Discount Applied:</span>
                            <span class="font-mono font-semibold text-rose-450">-${{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-800/80 pt-3">
                            <span class="font-bold text-slate-200">Grand Total:</span>
                            <span class="font-mono font-extrabold text-indigo-400 text-lg">${{ number_format($order->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between mt-4 bg-indigo-500/5 p-3 rounded-lg border border-indigo-500/10 text-xs items-center">
                            <span class="text-slate-400">Payment Method:</span>
                            <span class="font-bold text-indigo-300 capitalize">{{ $order->payment_method }} ({{ $order->payment_status }})</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

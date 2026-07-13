<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-xl text-slate-100 leading-tight">
            {{ __('Sales Reports / Synced Orders') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Filters -->
            <div class="glass-card p-4.5">
                <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label for="search" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Search Order ID</label>
                        <input type="text" name="search" id="search" placeholder="Enter offline UUID..." value="{{ request('search') }}" class="block w-full">
                    </div>

                    <!-- Branch Filter -->
                    <div>
                        <label for="branch_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Branch</label>
                        <select name="branch_id" id="branch_id" class="block w-full">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition shadow-lg shadow-indigo-500/10">
                            Apply Filters
                        </button>
                        @if(request('search') || request('branch_id'))
                            <a href="{{ route('admin.orders.index') }}" class="py-2.5 px-4 bg-slate-800 border border-slate-700 text-slate-350 hover:text-slate-100 rounded-lg text-xs font-semibold transition flex items-center justify-center">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="glass-card">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Transaction ID / UUID</th>
                                <th class="py-3 px-4">Branch Name</th>
                                <th class="py-3 px-4">Cashier / Staff</th>
                                <th class="py-3 px-4 text-right">Total Amount</th>
                                <th class="py-3 px-4">Date / Time Synced</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40">
                            @forelse($orders as $order)
                                <tr class="hover:bg-slate-900/20 transition-colors duration-150">
                                    <td class="py-4 px-4 text-xs font-mono text-indigo-400 font-bold select-all">
                                        {{ $order->offline_id }}
                                    </td>
                                    <td class="py-4 px-4 text-sm text-slate-200">
                                        {{ $order->branch->name }}
                                    </td>
                                    <td class="py-4 px-4 text-sm text-slate-350">
                                        {{ $order->user->name }}
                                    </td>
                                    <td class="py-4 px-4 text-sm text-right text-slate-100 font-bold font-mono">
                                        ${{ number_format($order->total_amount, 2) }}
                                    </td>
                                    <td class="py-4 px-4 text-xs text-slate-400 font-mono">
                                        {{ $order->created_at->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td class="py-4 px-4 text-sm text-center">
                                        <span class="px-2.5 py-1 text-[9px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-md">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-center">
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-xs font-semibold transition">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-xs text-slate-500">
                                        No sales transactions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 border-t border-slate-800/60 pt-4 px-4">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

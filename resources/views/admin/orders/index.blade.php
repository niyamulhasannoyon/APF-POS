<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sales Reports / Transactions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Filters -->
            <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
                    <!-- Search -->
                    <div class="w-full md:w-1/3">
                        <label for="search" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Search ID or Customer</label>
                        <input type="text" name="search" id="search" placeholder="Search by UUID or Customer..." value="{{ request('search') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                    </div>
                    
                    <!-- Branch Filter -->
                    <div class="w-full md:w-1/4">
                        <label for="branch_id" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Branch</label>
                        <select name="branch_id" id="branch_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                            <option value="">All Branches</option>
                            @foreach(App\Models\Branch::all() as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-md text-sm font-semibold transition">
                            Apply Filters
                        </button>
                        @if(request('search') || request('branch_id'))
                            <a href="{{ route('admin.orders.index') }}" class="px-3 py-2 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-md text-sm transition flex items-center">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-sm font-bold text-gray-500 uppercase tracking-wider">
                                <th class="py-3 px-4">Offline ID / UUID</th>
                                <th class="py-3 px-4">Branch</th>
                                <th class="py-3 px-4">Cashier</th>
                                <th class="py-3 px-4">Customer</th>
                                <th class="py-3 px-4">Payment</th>
                                <th class="py-3 px-4 text-right">Total</th>
                                <th class="py-3 px-4 text-center">Sync Date</th>
                                <th class="py-3 px-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($orders as $order)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 px-4 text-sm font-mono text-gray-600">
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 hover:underline font-semibold">
                                            {{ Str::limit($order->offline_id, 16) }}
                                        </a>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-gray-950 font-semibold">{{ $order->branch->name }}</td>
                                    <td class="py-4 px-4 text-sm text-gray-600">{{ $order->user->name }}</td>
                                    <td class="py-4 px-4 text-sm text-gray-900">
                                        {{ $order->customer->name ?? 'Walk-in Customer' }}
                                    </td>
                                    <td class="py-4 px-4 text-sm">
                                        <div class="capitalize font-bold text-gray-700">{{ $order->payment_method }}</div>
                                        <div class="text-xs text-emerald-600 font-semibold uppercase tracking-wider">{{ $order->payment_status }}</div>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-right text-gray-950 font-bold font-mono">
                                        ${{ number_format($order->total_amount, 2) }}
                                    </td>
                                    <td class="py-4 px-4 text-sm text-center text-gray-500 font-mono">
                                        {{ $order->synced_at ? $order->synced_at->format('Y-m-d H:i:s') : 'Offline' }}
                                    </td>
                                    <td class="py-4 px-4 text-sm text-center">
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded font-semibold text-xs border border-gray-200 transition">
                                            View Invoice
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-sm text-gray-500">
                                        No sales records matching selection.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

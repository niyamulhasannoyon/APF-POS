<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Customers Directory') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Search -->
            <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.customers.index') }}" class="w-full md:w-1/3 flex gap-2">
                    <input type="text" name="search" placeholder="Search by name, phone, or email..." value="{{ request('search') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                    <button type="submit" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-md text-sm font-semibold transition">
                        Search
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.customers.index') }}" class="px-3 py-2 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-md text-sm transition flex items-center">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Customer Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-sm font-bold text-gray-500 uppercase tracking-wider">
                                <th class="py-3 px-4">Customer Name</th>
                                <th class="py-3 px-4">Phone Number</th>
                                <th class="py-3 px-4">Email Address</th>
                                <th class="py-3 px-4 text-center">Total Orders</th>
                                <th class="py-3 px-4 text-center">Loyalty Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($customers as $cust)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-4 px-4 text-sm font-bold text-gray-900">{{ $cust->name }}</td>
                                    <td class="py-4 px-4 text-sm text-gray-600 font-mono">{{ $cust->phone ?? 'N/A' }}</td>
                                    <td class="py-4 px-4 text-sm text-gray-600">{{ $cust->email ?? 'N/A' }}</td>
                                    <td class="py-4 px-4 text-sm text-center text-gray-600 font-semibold">{{ number_format($cust->orders_count) }} sales</td>
                                    <td class="py-4 px-4 text-sm text-center">
                                        <span class="px-3 py-1 text-xs font-bold bg-amber-100 text-amber-800 rounded-full">
                                            🌟 {{ number_format($cust->loyalty_points) }} pts
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-sm text-gray-500">
                                        No customers registered yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $customers->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

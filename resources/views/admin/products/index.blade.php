<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Inventory Management') }}
            </h2>
            <div class="flex flex-wrap items-center gap-3">
                <!-- CSV Export -->
                <a href="{{ route('admin.products.export') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-700 rounded-lg font-semibold transition text-xs flex items-center gap-1.5">
                    📥 Export CSV
                </a>

                <!-- CSV Import Form -->
                <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 border border-slate-300 bg-slate-50 px-2 py-1 rounded-lg">
                    @csrf
                    <input type="file" name="csv_file" accept=".csv" required class="text-[10px] w-36 cursor-pointer text-slate-600 outline-none">
                    <button type="submit" class="px-2 py-1 bg-gray-800 hover:bg-gray-700 text-white rounded text-[10px] font-bold transition">
                        Upload CSV
                    </button>
                </form>

                <a href="{{ route('admin.products.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md transition duration-150 ease-in-out text-xs">
                    + Add New Product
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('success'))
                <div class="p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 rounded-r-lg shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Search and Filter -->
            <div class="bg-white p-4 shadow-sm sm:rounded-lg flex flex-col md:flex-row gap-4 items-center justify-between">
                <form method="GET" action="{{ route('admin.products.index') }}" class="w-full md:w-1/3 flex gap-2">
                    <input type="text" name="search" placeholder="Search by name, SKU, or barcode..." value="{{ request('search') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                    <button type="submit" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-md text-sm font-semibold transition">
                        Search
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.products.index') }}" class="px-3 py-2 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-md text-sm transition flex items-center">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Product Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-sm font-bold text-gray-500 uppercase tracking-wider">
                                <th class="py-3 px-4">Name / SKU</th>
                                <th class="py-3 px-4">Barcode</th>
                                <th class="py-3 px-4">Category</th>
                                <th class="py-3 px-4 text-right">Cost</th>
                                <th class="py-3 px-4 text-right">Price</th>
                                <th class="py-3 px-4">Stock Levels</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($products as $prod)
                                <tr class="hover:bg-gray-50 transition">
                                    <!-- Name & SKU -->
                                    <td class="py-4 px-4 text-sm">
                                        <div class="font-bold text-gray-900">{{ $prod->name }}</div>
                                        <div class="text-xs text-gray-500 font-mono mt-0.5">{{ $prod->sku }}</div>
                                    </td>
                                    <!-- Barcode -->
                                    <td class="py-4 px-4 text-sm font-mono text-gray-600">
                                        {{ $prod->barcode ?? 'N/A' }}
                                    </td>
                                    <!-- Category -->
                                    <td class="py-4 px-4 text-sm">
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded">
                                            {{ $prod->category->name }}
                                        </span>
                                    </td>
                                    <!-- Cost -->
                                    <td class="py-4 px-4 text-sm text-right text-gray-600 font-semibold font-mono">
                                        ${{ number_format($prod->cost, 2) }}
                                    </td>
                                    <!-- Price -->
                                    <td class="py-4 px-4 text-sm text-right text-gray-900 font-bold font-mono">
                                        ${{ number_format($prod->price, 2) }}
                                    </td>
                                    <!-- Stock Levels per Branch -->
                                    <td class="py-4 px-4 text-sm space-y-1.5">
                                        @foreach($prod->branches as $branch)
                                            <div class="flex items-center justify-between gap-6 text-xs border-b border-dashed border-gray-100 pb-0.5">
                                                <span class="text-gray-500">{{ $branch->name }}:</span>
                                                <span class="font-bold {{ $branch->pivot->stock_quantity < 20 ? 'text-rose-600' : 'text-gray-900' }}">
                                                    {{ number_format($branch->pivot->stock_quantity) }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </td>
                                    <!-- Status -->
                                    <td class="py-4 px-4 text-sm text-center">
                                        @if($prod->status)
                                            <span class="px-2 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-800 rounded-full">
                                                Active
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-800 rounded-full">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <!-- Actions -->
                                    <td class="py-4 px-4 text-sm text-center">
                                        <button type="button" 
                                                onclick="Livewire.dispatch('open-stock-adjuster', { productId: {{ $prod->id }}, productName: '{{ addslashes($prod->name) }}' })"
                                                class="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded font-semibold text-xs border border-indigo-200 transition">
                                            Adjust Stock
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-sm text-gray-500">
                                        No products found. Add a product to get started.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Livewire Stock Adjuster Component -->
    <livewire:⚡stock-adjuster />

    <!-- Script to reload page on stock update to show changes in table -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('stock-updated', () => {
                window.location.reload();
            });
        });
    </script>
</x-app-layout>

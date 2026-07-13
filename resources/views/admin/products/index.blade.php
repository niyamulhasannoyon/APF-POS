<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <h2 class="font-extrabold text-xl text-slate-100 leading-tight">
                {{ __('Inventory Management') }}
            </h2>
            <div class="flex flex-wrap items-center gap-3">
                <!-- CSV Export -->
                <a href="{{ route('admin.products.export') }}" class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 rounded-lg font-semibold transition text-xs flex items-center gap-1.5 shadow">
                    📥 Export CSV
                </a>

                <!-- CSV Import Form -->
                <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 border border-slate-700 bg-slate-900/50 px-2 py-1 rounded-lg shadow">
                    @csrf
                    <input type="file" name="csv_file" accept=".csv" required class="text-[10px] w-36 cursor-pointer text-slate-400 outline-none">
                    <button type="submit" class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-[10px] font-bold transition">
                        Upload CSV
                    </button>
                </form>

                <a href="{{ route('admin.products.create') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold shadow-lg shadow-indigo-500/10 transition duration-150 ease-in-out text-xs">
                    + Add New Product
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('success'))
                <div class="p-4 bg-emerald-500/10 border-l-4 border-emerald-500 text-emerald-400 rounded-r-lg shadow border border-emerald-500/20 text-xs font-semibold">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Search and Filter -->
            <div class="glass-card flex flex-col md:flex-row gap-4 items-center justify-between p-4.5">
                <form method="GET" action="{{ route('admin.products.index') }}" class="w-full md:w-1/3 flex gap-2">
                    <input type="text" name="search" placeholder="Search by name, SKU, or barcode..." value="{{ request('search') }}" class="block w-full">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition">
                        Search
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.products.index') }}" class="px-3 py-2 bg-slate-800 border border-slate-700 text-slate-350 hover:text-slate-100 rounded-lg text-xs font-semibold transition flex items-center">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Product Table -->
            <div class="glass-card">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
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
                        <tbody class="divide-y divide-slate-800/40">
                            @forelse($products as $prod)
                                <tr class="hover:bg-slate-900/20 transition-colors duration-150">
                                    <!-- Name & SKU -->
                                    <td class="py-4 px-4 text-sm">
                                        <div class="font-bold text-slate-200">{{ $prod->name }}</div>
                                        <div class="text-xs text-slate-500 font-mono mt-1">{{ $prod->sku }}</div>
                                    </td>
                                    <!-- Barcode -->
                                    <td class="py-4 px-4 text-sm font-mono text-slate-400">
                                        {{ $prod->barcode ?? 'N/A' }}
                                    </td>
                                    <!-- Category -->
                                    <td class="py-4 px-4 text-sm">
                                        <span class="px-2.5 py-1 text-xs font-bold bg-slate-800 text-slate-400 rounded-md border border-slate-700">
                                            {{ $prod->category->name }}
                                        </span>
                                    </td>
                                    <!-- Cost -->
                                    <td class="py-4 px-4 text-sm text-right text-slate-400 font-semibold font-mono">
                                        ${{ number_format($prod->cost, 2) }}
                                    </td>
                                    <!-- Price -->
                                    <td class="py-4 px-4 text-sm text-right text-slate-100 font-bold font-mono">
                                        ${{ number_format($prod->price, 2) }}
                                    </td>
                                    <!-- Stock Levels per Branch -->
                                    <td class="py-4 px-4 text-sm space-y-1.5 w-60">
                                        @foreach($prod->branches as $branch)
                                            <div class="flex items-center justify-between gap-6 text-xs border-b border-dashed border-slate-800/45 pb-0.5">
                                                <span class="text-slate-500 text-[11px]">{{ $branch->name }}:</span>
                                                <span class="font-bold {{ $branch->pivot->stock_quantity < 20 ? 'text-rose-450' : 'text-slate-200' }}">
                                                    {{ number_format($branch->pivot->stock_quantity) }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </td>
                                    <!-- Status -->
                                    <td class="py-4 px-4 text-sm text-center">
                                        @if($prod->status)
                                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 rounded-md border border-emerald-500/20">
                                                Active
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase bg-slate-800 text-slate-450 rounded-md border border-slate-700">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <!-- Actions -->
                                    <td class="py-4 px-4 text-sm text-center">
                                        <button type="button" 
                                                onclick="Livewire.dispatch('open-stock-adjuster', { productId: {{ $prod->id }}, productName: '{{ addslashes($prod->name) }}' })"
                                                class="px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500 text-indigo-400 hover:text-white rounded-lg font-bold text-xs border border-indigo-500/20 transition-all duration-200">
                                            Adjust Stock
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-xs text-slate-500">
                                        No products found. Add a product to get started.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 border-t border-slate-800/60 pt-4 px-4">
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

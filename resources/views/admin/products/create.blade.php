<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-xl text-slate-100 leading-tight">
                {{ __('Add New Product') }}
            </h2>
            <a href="{{ route('admin.products.index') }}" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 active:bg-slate-750 text-slate-200 border border-slate-700 rounded-lg font-semibold transition text-xs">
                ➔ Back to list
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-card">
                <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 gap-5">
                        <!-- Product Name -->
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Product Name *</label>
                            <input type="text" name="name" id="name" required value="{{ old('name') }}" class="block w-full">
                            @error('name')
                                <p class="text-xs text-rose-450 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- SKU -->
                            <div>
                                <label for="sku" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">SKU *</label>
                                <input type="text" name="sku" id="sku" required value="{{ old('sku') }}" placeholder="e.g. BEV-COKE-250" class="block w-full">
                                @error('sku')
                                    <p class="text-xs text-rose-455 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Barcode -->
                            <div>
                                <label for="barcode" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Barcode / EAN</label>
                                <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}" placeholder="e.g. 5449000000996" class="block w-full font-mono">
                                @error('barcode')
                                    <p class="text-xs text-rose-455 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Category Selection -->
                        <div>
                            <label for="category_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Category *</label>
                            <select name="category_id" id="category_id" required class="block w-full">
                                <option value="" disabled selected>Select a Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="text-xs text-rose-450 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Cost Price -->
                            <div>
                                <label for="cost" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Cost Price ($) *</label>
                                <input type="number" name="cost" id="cost" required step="0.01" min="0" value="{{ old('cost') }}" class="block w-full font-mono">
                                @error('cost')
                                    <p class="text-xs text-rose-455 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Retail Price -->
                            <div>
                                <label for="price" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Selling Price ($) *</label>
                                <input type="number" name="price" id="price" required step="0.01" min="0" value="{{ old('price') }}" class="block w-full font-mono">
                                @error('price')
                                    <p class="text-xs text-rose-455 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center pt-2">
                        <input type="checkbox" name="status" id="status" value="1" checked class="rounded border-slate-700 bg-slate-900 text-indigo-650 shadow-sm focus:ring-indigo-500/30">
                        <label for="status" class="ms-2 text-sm font-semibold text-slate-300 cursor-pointer select-none">Product is available for sale</label>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-800/80">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white rounded-lg font-bold shadow-lg shadow-indigo-500/10 transition duration-150 ease-in-out text-sm">
                            Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

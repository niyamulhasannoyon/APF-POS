<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add New Product') }}
            </h2>
            <a href="{{ route('admin.products.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-semibold transition text-sm">
                ➔ Back to list
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Product Name -->
                        <div class="md:col-span-2">
                            <label for="name" class="block text-sm font-semibold text-gray-700">Product Name *</label>
                            <input type="text" name="name" id="name" required value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                            @error('name')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- SKU -->
                        <div>
                            <label for="sku" class="block text-sm font-semibold text-gray-700">SKU (Stock Keeping Unit) *</label>
                            <input type="text" name="sku" id="sku" required value="{{ old('sku') }}" placeholder="e.g. BEV-COKE-250" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                            @error('sku')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Barcode -->
                        <div>
                            <label for="barcode" class="block text-sm font-semibold text-gray-700">Barcode / UPC / EAN</label>
                            <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}" placeholder="e.g. 5449000000996" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                            @error('barcode')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category Selection -->
                        <div>
                            <label for="category_id" class="block text-sm font-semibold text-gray-700">Category *</label>
                            <select name="category_id" id="category_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                                <option value="" disabled selected>Select a Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Cost Price -->
                        <div>
                            <label for="cost" class="block text-sm font-semibold text-gray-700">Cost Price ($) *</label>
                            <input type="number" name="cost" id="cost" required step="0.01" min="0" value="{{ old('cost') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                            @error('cost')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Retail Price -->
                        <div>
                            <label for="price" class="block text-sm font-semibold text-gray-700">Selling Price ($) *</label>
                            <input type="number" name="price" id="price" required step="0.01" min="0" value="{{ old('price') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                            @error('price')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center pt-2">
                        <input type="checkbox" name="status" id="status" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <label for="status" class="ms-2 text-sm font-semibold text-gray-700">Product is available for sale</label>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md transition duration-150 ease-in-out text-sm">
                            Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

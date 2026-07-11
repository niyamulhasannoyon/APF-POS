<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create New Branch') }}
            </h2>
            <a href="{{ route('admin.branches.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-semibold transition text-sm">
                ➔ Back to list
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.branches.store') }}" class="space-y-6">
                    @csrf

                    <!-- Branch Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700">Branch Name *</label>
                        <input type="text" name="name" id="name" required value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        @error('name')
                            <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Contact Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-700">Contact Phone</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        @error('phone')
                            <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="address" class="block text-sm font-semibold text-gray-700">Address / Location</label>
                        <textarea name="address" id="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="flex items-center">
                        <input type="checkbox" name="status" id="status" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <label for="status" class="ms-2 text-sm font-semibold text-gray-700">Branch is Active</label>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md transition duration-150 ease-in-out text-sm">
                            Save Branch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

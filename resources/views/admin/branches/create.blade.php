<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-xl text-slate-100 leading-tight">
                {{ __('Create New Branch') }}
            </h2>
            <a href="{{ route('admin.branches.index') }}" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 active:bg-slate-750 text-slate-200 border border-slate-700 rounded-lg font-semibold transition text-xs">
                ➔ Back to list
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-card">
                <form method="POST" action="{{ route('admin.branches.store') }}" class="space-y-6">
                    @csrf

                    <!-- Branch Name -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Branch Name *</label>
                        <input type="text" name="name" id="name" required value="{{ old('name') }}" class="block w-full">
                        @error('name')
                            <p class="text-xs text-rose-450 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Contact Phone -->
                    <div>
                        <label for="phone" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Contact Phone</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="block w-full font-mono">
                        @error('phone')
                            <p class="text-xs text-rose-450 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="address" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Address / Location</label>
                        <textarea name="address" id="address" rows="3" class="block w-full">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="text-xs text-rose-450 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="flex items-center">
                        <input type="checkbox" name="status" id="status" value="1" checked class="rounded border-slate-700 bg-slate-900 text-indigo-650 shadow-sm focus:ring-indigo-500/30">
                        <label for="status" class="ms-2 text-sm font-semibold text-slate-300 cursor-pointer select-none">Branch is Active</label>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-800/80">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white rounded-lg font-bold shadow-lg shadow-indigo-500/10 transition duration-150 text-sm">
                            Save Branch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

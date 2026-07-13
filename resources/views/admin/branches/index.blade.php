<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-extrabold text-xl text-slate-100 leading-tight">
                {{ __('Branches Management') }}
            </h2>
            <a href="{{ route('admin.branches.create') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold shadow-lg shadow-indigo-500/10 transition duration-150 text-xs">
                + Create New Branch
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-500/10 border-l-4 border-emerald-500 text-emerald-400 rounded-r-lg shadow border border-emerald-500/20 text-xs font-semibold">
                    {{ session('success') }}
                </div>
            @endif

            <div class="glass-card">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Branch Name</th>
                                <th class="py-3 px-4">Address</th>
                                <th class="py-3 px-4">Contact Phone</th>
                                <th class="py-3 px-4 text-center">Assigned Staff</th>
                                <th class="py-3 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40">
                            @forelse($branches as $branch)
                                <tr class="hover:bg-slate-900/20 transition-colors duration-150">
                                    <td class="py-4 px-4 text-sm text-slate-200 font-bold">{{ $branch->name }}</td>
                                    <td class="py-4 px-4 text-sm text-slate-400">{{ $branch->address ?? 'N/A' }}</td>
                                    <td class="py-4 px-4 text-sm text-slate-300 font-mono">{{ $branch->phone ?? 'N/A' }}</td>
                                    <td class="py-4 px-4 text-sm text-slate-350 text-center font-semibold">{{ $branch->users_count }} staff</td>
                                    <td class="py-4 px-4 text-sm text-center">
                                        @if($branch->status)
                                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 rounded-md border border-emerald-500/20">
                                                Active
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase bg-slate-800 text-slate-400 rounded-md border border-slate-700">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-xs text-slate-500">
                                        No branches found. Click "+ Create New Branch" to register one.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

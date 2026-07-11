<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Branches Management') }}
            </h2>
            <a href="{{ route('admin.branches.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md transition duration-150 ease-in-out text-sm">
                + Create New Branch
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 rounded-r-lg shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="py-3 px-4 text-sm font-bold text-gray-500">Branch Name</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-500">Address</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-500">Contact Phone</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-500 text-center">Assigned Staff</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-500 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branches as $branch)
                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                    <td class="py-4 px-4 text-sm text-gray-900 font-bold">{{ $branch->name }}</td>
                                    <td class="py-4 px-4 text-sm text-gray-600">{{ $branch->address ?? 'N/A' }}</td>
                                    <td class="py-4 px-4 text-sm text-gray-600 font-mono">{{ $branch->phone ?? 'N/A' }}</td>
                                    <td class="py-4 px-4 text-sm text-gray-600 text-center font-semibold">{{ $branch->users_count }} staff</td>
                                    <td class="py-4 px-4 text-sm text-center">
                                        @if($branch->status)
                                            <span class="px-2.5 py-1 text-xs font-bold bg-emerald-100 text-emerald-800 rounded-full">
                                                Active
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 text-xs font-bold bg-gray-100 text-gray-800 rounded-full">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-sm text-gray-500">
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

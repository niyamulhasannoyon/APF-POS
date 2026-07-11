<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount('users')->get();
        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('admin.branches.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        Branch::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'status' => $request->has('status') ? $request->status : true,
        ]);

        return redirect()->route('admin.branches.index')->with('success', 'Branch created successfully.');
    }
}

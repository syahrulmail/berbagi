<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::with(['supervisor'])
            ->withCount(['donations', 'agents'])
            ->orderBy('code')
            ->paginate(15);

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        $supervisors = User::where('role', 'supervisor')->where('is_active', true)->orderBy('name')->get();

        return view('branches.create', compact('supervisors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'target_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $branch = Branch::create($data);

        ActivityLog::record('branch.create', 'Membuat cabang ' . $branch->name);

        return redirect()->route('branches.index')->with('success', 'Cabang berhasil dibuat.');
    }

    public function edit(Branch $branch)
    {
        $supervisors = User::where('role', 'supervisor')->where('is_active', true)->orderBy('name')->get();

        return view('branches.edit', compact('branch', 'supervisors'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:branches,code,' . $branch->id],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'target_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $branch->update($data);

        ActivityLog::record('branch.update', 'Memperbarui cabang ' . $branch->name);

        return redirect()->route('branches.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        ActivityLog::record('branch.delete', 'Menghapus cabang ' . $branch->name);
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Cabang berhasil dihapus.');
    }
}

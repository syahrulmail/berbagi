<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::with(['supervisor'])
            ->withCount(['donations', 'agents'])
            ->withSum('donations', 'amount')
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            });

        $sort = $request->input('sort', 'code');
        if (!in_array($sort, ['code', 'terkumpul', 'agents', 'progress', 'donations', 'status'], true)) {
            $sort = 'code';
        }
        $dir = $request->input('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        switch ($sort) {
            case 'terkumpul':
                $branches->orderBy('donations_sum_amount', $dir);
                break;
            case 'agents':
                $branches->orderBy('agents_count', $dir);
                break;
            case 'donations':
                $branches->orderBy('donations_count', $dir);
                break;
            case 'status':
                $branches->orderBy('is_active', $dir)->orderBy('code', 'asc');
                break;
            case 'progress':
                $branches->orderByRaw(
                    "(CASE WHEN target_amount > 0 THEN donations_sum_amount / target_amount ELSE 0 END) {$dir}"
                );
                break;
            default:
                $branches->orderBy('code', $dir);
        }

        $branches = $branches->paginate(15)->withQueryString();

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

        $this->syncSupervisor($branch, $data['supervisor_id'] ?? null);

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

        $this->syncSupervisor($branch, $data['supervisor_id'] ?? null);

        ActivityLog::record('branch.update', 'Memperbarui cabang ' . $branch->name);

        return redirect()->route('branches.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        ActivityLog::record('branch.delete', 'Menghapus cabang ' . $branch->name);
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Cabang berhasil dihapus.');
    }

    /**
     * Jaga agar relasi cabang <-> supervisor selalu konsisten dua arah:
     * branches.supervisor_id <-> users.branch_id (untuk role supervisor).
     *
     * @param  Branch|null  $branch
     * @param  int|string|null  $supervisorId
     */
    protected function syncSupervisor($branch, $supervisorId = null)
    {
        if (!$branch) {
            return;
        }

        if ($supervisorId) {
            // Satu supervisor hanya boleh mengepalai satu cabang.
            User::where('id', $supervisorId)->where('role', 'supervisor')->update(['branch_id' => $branch->id]);
            Branch::where('supervisor_id', $supervisorId)->where('id', '!=', $branch->id)->update(['supervisor_id' => null]);
        } elseif ($branch->supervisor_id) {
            // Supervisor dipilih lewat relasi, pastikan branch_id user ikut.
            User::where('id', $branch->supervisor_id)->where('role', 'supervisor')->update(['branch_id' => $branch->id]);
        } else {
            // Auto-taut: bila ada supervisor yang sudah menunjuk cabang ini
            // via users.branch_id (link searah yang belum pernah diisi).
            $candidate = User::where('role', 'supervisor')->where('branch_id', $branch->id)->first();
            if ($candidate) {
                $branch->supervisor_id = $candidate->id;
                $branch->save();
            }
        }
    }
}

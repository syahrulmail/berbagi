<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $actor = auth()->user();

        $users = User::with('branch')
            ->visibleTo($actor)
            ->when($actor->isAdmin() && $request->role, function ($query, $role) {
                return $query->where('role', $role);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = $this->userStats($actor);

        return view('users.index', compact('users', 'stats'));
    }

    /**
     * Statistik pengguna, dibatasi sesuai wewenang pengelola.
     */
    protected function userStats(User $actor): array
    {
        $base = User::visibleTo($actor);

        return [
            'total'      => (clone $base)->count(),
            'admin'      => $actor->isAdmin() ? User::where('role', User::ROLE_ADMIN)->count() : 0,
            'supervisor' => $actor->isAdmin() ? User::where('role', User::ROLE_SUPERVISOR)->count() : 0,
            'agen'       => (clone $base)->where('role', User::ROLE_AGEN)->count(),
            'active'     => (clone $base)->where('is_active', true)->count(),
        ];
    }

    /**
     * Cabang yang boleh dipilih pengelola (supervisor hanya cabangnya).
     */
    protected function selectableBranches(User $actor)
    {
        $query = Branch::where('is_active', true)->orderBy('name');

        if ($actor->isSupervisor()) {
            $query->where('id', $actor->branch_id);
        }

        return $query->get();
    }

    public function create()
    {
        $actor = auth()->user();
        $branches = $this->selectableBranches($actor);

        return view('users.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $actor = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,supervisor,agen,donatur'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'api_ss' => ['nullable', 'string', 'max:255'],
            'api_cc' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $actor->isAdmin()) {
            $data['role'] = User::ROLE_AGEN;
            $data['branch_id'] = $actor->branch_id;
        }

        unset($data['api_ss'], $data['api_cc']);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active');
        $data['slug'] = User::uniqueSlug($data['username']);

        $user = User::create($data);

        $this->syncSupervisorBranch($user, $data);

        Setting::set('agent_profile_' . $user->slug, json_encode([
            'photo' => '',
            'intro' => '',
            'api_ss' => trim((string) $request->input('api_ss', '')),
            'api_cc' => trim((string) $request->input('api_cc', '')),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        ActivityLog::record('user.create', 'Membuat user ' . $user->name);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $actor = auth()->user();
        $this->authorizeUserManage($user);
        $branches = $this->selectableBranches($actor);

        $profile = $this->decodeProfile(Setting::get('agent_profile_' . $user->slug, '{}'));

        return view('users.edit', compact('user', 'branches', 'profile'));
    }

    /**
     * Pastikan pengelola berhak mengelola user target.
     */
    protected function authorizeUserManage(User $user): void
    {
        if (! $user->isManageableBy(auth()->user())) {
            abort(403, 'Anda tidak berhak mengelola pengguna ini.');
        }
    }

    public function update(Request $request, User $user)
    {
        $actor = auth()->user();
        $this->authorizeUserManage($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,supervisor,agen,donatur'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'existing_photo' => ['nullable', 'string', 'max:255'],
            'photo_remove' => ['nullable', 'string', 'in:0,1'],
            'intro' => ['nullable', 'string', 'max:500'],
            'api_ss' => ['nullable', 'string', 'max:255'],
            'api_cc' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $actor->isAdmin()) {
            $data['role'] = User::ROLE_AGEN;
            $data['branch_id'] = $actor->branch_id;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        $oldSlug = $user->slug;
        $data['slug'] = User::uniqueSlug($data['username'], $user->id);

        unset($data['api_ss'], $data['api_cc']);

        $user->update($data);

        $this->syncSupervisorBranch($user, $data);

        $this->saveProfile($request, $user, $oldSlug);

        ActivityLog::record('user.update', 'Memperbarui user ' . $user->name);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    /**
     * Jaga relasi supervisor <-> cabang agar konsisten dua arah:
     * saat user supervisor diberi branch_id, cabang terkait ikut
     * menunjuk user tsb sebagai supervisor.
     *
     * @param  User  $user
     * @param  array  $data
     */
    protected function syncSupervisorBranch(User $user, array $data)
    {
        if ($user->role === User::ROLE_SUPERVISOR) {
            if (!empty($data['branch_id'])) {
                Branch::where('id', $data['branch_id'])->update(['supervisor_id' => $user->id]);
                Branch::where('supervisor_id', $user->id)->where('id', '!=', $data['branch_id'])->update(['supervisor_id' => null]);
            }
        } else {
            // Role bukan supervisor: cabang tidak boleh menunjuk user ini.
            Branch::where('supervisor_id', $user->id)->update(['supervisor_id' => null]);
        }
    }

    protected function saveProfile(Request $request, User $user, string $oldSlug): void
    {
        $oldKey = 'agent_profile_' . $oldSlug;
        $newKey = 'agent_profile_' . $user->slug;

        $profile = $this->decodeProfile(Setting::get($oldKey, '{}'));
        $existing = (string) ($request->input('existing_photo') ?? ($profile['photo'] ?? ''));
        $removeFlag = (string) ($request->input('photo_remove') ?? '0');
        $photo = $existing;

        $file = $request->file('photo');
        if ($file !== null && $file->isValid()) {
            $newPath = $file->store('agents', 'public');
            if ($newPath && $photo && $photo !== $newPath) {
                $this->deleteStoredPhoto($photo);
            }
            $photo = $newPath ?: $photo;
        } elseif ($removeFlag === '1' && $photo) {
            $this->deleteStoredPhoto($photo);
            $photo = '';
        }

        $intro = trim((string) ($request->input('intro') ?? ($profile['intro'] ?? '')));

        $apiSs = $request->has('api_ss')
            ? trim((string) $request->input('api_ss'))
            : (string) ($profile['api_ss'] ?? '');
        $apiCc = $request->has('api_cc')
            ? trim((string) $request->input('api_cc'))
            : (string) ($profile['api_cc'] ?? '');

        if ($oldKey !== $newKey) {
            Setting::where('key', $oldKey)->delete();
        }

        Setting::set($newKey, json_encode([
            'photo' => $photo,
            'intro' => $intro,
            'api_ss' => $apiSs,
            'api_cc' => $apiCc,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function decodeProfile(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return ['photo' => '', 'intro' => '', 'api_ss' => '', 'api_cc' => ''];
        }

        return [
            'photo' => (string) ($decoded['photo'] ?? ''),
            'intro' => (string) ($decoded['intro'] ?? ''),
            'api_ss' => (string) ($decoded['api_ss'] ?? ''),
            'api_cc' => (string) ($decoded['api_cc'] ?? ''),
        ];
    }

    protected function deleteStoredPhoto(string $path): void
    {
        if (preg_match('#^(https?://|/|data:)#i', $path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public function destroy(User $user)
    {
        $actor = auth()->user();
        $this->authorizeUserManage($user);

        if ($actor->id === $user->id) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus admin terakhir.');
        }

        ActivityLog::record('user.delete', 'Menghapus user ' . $user->name);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}

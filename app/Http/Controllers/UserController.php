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
        $users = User::with('branch')
            ->when($request->role, function ($query, $role) {
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

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('users.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,supervisor,agen,donatur'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active');
        $data['slug'] = User::uniqueSlug($data['username']);

        $user = User::create($data);

        ActivityLog::record('user.create', 'Membuat user ' . $user->name);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        $profile = $this->decodeProfile(Setting::get('agent_profile_' . $user->slug, '{}'));

        return view('users.edit', compact('user', 'branches', 'profile'));
    }

    public function update(Request $request, User $user)
    {
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
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        $oldSlug = $user->slug;
        $data['slug'] = User::uniqueSlug($data['username'], $user->id);

        $user->update($data);

        $this->saveProfile($request, $user, $oldSlug);

        ActivityLog::record('user.update', 'Memperbarui user ' . $user->name);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
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

        if ($oldKey !== $newKey) {
            Setting::where('key', $oldKey)->delete();
        }

        Setting::set($newKey, json_encode([
            'photo' => $photo,
            'intro' => $intro,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function decodeProfile(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return ['photo' => '', 'intro' => ''];
        }

        return [
            'photo' => (string) ($decoded['photo'] ?? ''),
            'intro' => (string) ($decoded['intro'] ?? ''),
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
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus admin terakhir.');
        }

        ActivityLog::record('user.delete', 'Menghapus user ' . $user->name);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}

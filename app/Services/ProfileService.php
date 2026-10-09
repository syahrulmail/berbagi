<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    /**
     * Ambil data profil (foto & sambutan) milik user.
     */
    public function data(User $user): array
    {
        return $this->decode(Setting::get('agent_profile_' . $user->slug, '{}'));
    }

    /**
     * Simpan data profil (identitas, keamanan, foto & sambutan) milik user.
     */
    public function save(User $user, Request $request): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'existing_photo' => ['nullable', 'string', 'max:255'],
            'photo_remove' => ['nullable', 'string', 'in:0,1'],
            'intro' => ['nullable', 'string', 'max:500'],
            'api_ss' => ['nullable', 'string', 'max:255'],
        ]);

        $oldSlug = $user->slug;

        // Identitas (role & cabang tidak dapat diubah dari halaman ini).
        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $newSlug = User::uniqueSlug($user->username, $user->id);
        $slugChanged = $oldSlug !== $newSlug;
        $user->slug = $newSlug;
        $user->save();

        // Foto & sambutan.
        $profile = $this->decode(Setting::get('agent_profile_' . $oldSlug, '{}'));
        $existing = (string) ($data['existing_photo'] ?? ($profile['photo'] ?? ''));
        $removeFlag = (string) ($data['photo_remove'] ?? '0');
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

        if ($slugChanged) {
            Setting::where('key', 'agent_profile_' . $oldSlug)->delete();
        }

        Setting::set('agent_profile_' . $newSlug, json_encode([
            'photo' => $photo,
            'intro' => trim((string) ($data['intro'] ?? '')),
            'api_ss' => trim((string) ($data['api_ss'] ?? '')),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        ActivityLog::record('profile.update', 'Memperbarui profil ' . $user->name);
    }

    protected function deleteStoredPhoto(string $path): void
    {
        if (preg_match('#^(https?://|/|data:)#i', $path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * Simpan hanya foto profil user (sambutan dipertahankan).
     * Mendukung perpindahan key bila slug/username berubah.
     */
    public function savePhoto(User $user, Request $request, ?string $oldSlug = null): void
    {
        $data = $request->validate([
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'existing_photo' => ['nullable', 'string', 'max:255'],
            'photo_remove' => ['nullable', 'string', 'in:0,1'],
        ]);

        $oldSlug = $oldSlug ?: $user->slug;
        $oldKey = 'agent_profile_' . $oldSlug;
        $newKey = 'agent_profile_' . $user->slug;

        $profile = $this->decode(Setting::get($oldKey, '{}'));
        $photo = (string) ($data['existing_photo'] ?? $profile['photo']);
        $removeFlag = (string) ($data['photo_remove'] ?? '0');

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

        if ($oldKey !== $newKey) {
            Setting::where('key', $oldKey)->delete();
        }

        Setting::set($newKey, json_encode([
            'photo' => $photo,
            'intro' => $profile['intro'],
            'api_ss' => $profile['api_ss'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function decode(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return ['photo' => '', 'intro' => '', 'api_ss' => ''];
        }

        return [
            'photo' => (string) ($decoded['photo'] ?? ''),
            'intro' => (string) ($decoded['intro'] ?? ''),
            'api_ss' => (string) ($decoded['api_ss'] ?? ''),
        ];
    }
}

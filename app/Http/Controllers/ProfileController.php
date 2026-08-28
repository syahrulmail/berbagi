<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $profile = $this->decodeProfile(Setting::get('agent_profile_' . $user->slug, '{}'));

        return view('profile.edit', compact('user', 'profile'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $key = 'agent_profile_' . $user->slug;

        $data = $request->validate([
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'existing_photo' => ['nullable', 'string', 'max:255'],
            'photo_remove' => ['nullable', 'string', 'in:0,1'],
            'intro' => ['nullable', 'string', 'max:500'],
        ]);

        $existing = (string) ($data['existing_photo'] ?? '');
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

        $intro = trim((string) ($data['intro'] ?? ''));

        Setting::set($key, json_encode([
            'photo' => $photo,
            'intro' => $intro,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        ActivityLog::record('profile.update', 'Memperbarui profil ' . $user->name);

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil disimpan.');
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
}

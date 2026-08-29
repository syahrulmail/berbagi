<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AchievementController extends Controller
{
    public function index()
    {
        $achievements = Achievement::orderBy('sort_order')->orderBy('id')->paginate(10);

        return view('achievements.index', compact('achievements'));
    }

    public function create()
    {
        return view('achievements.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('achievements', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        Achievement::create($data);

        ActivityLog::record('achievement.create', 'Membuat pencapaian ' . $data['value']);

        return redirect()->route('achievements.index')->with('success', 'Pencapaian berhasil dibuat.');
    }

    public function edit(Achievement $achievement)
    {
        return view('achievements.edit', compact('achievement'));
    }

    public function update(Request $request, Achievement $achievement)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            if ($achievement->image) {
                Storage::disk('public')->delete($achievement->image);
            }
            $data['image'] = $request->file('image')->store('achievements', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        $achievement->update($data);

        ActivityLog::record('achievement.update', 'Memperbarui pencapaian ' . $achievement->value);

        return redirect()->route('achievements.index')->with('success', 'Pencapaian berhasil diperbarui.');
    }

    public function destroy(Achievement $achievement)
    {
        if ($achievement->image) {
            Storage::disk('public')->delete($achievement->image);
        }

        ActivityLog::record('achievement.delete', 'Menghapus pencapaian ' . $achievement->value);
        $achievement->delete();

        return redirect()->route('achievements.index')->with('success', 'Pencapaian berhasil dihapus.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'icon' => ['nullable', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'max:2048'],
            'color' => ['nullable', 'string', 'max:20'],
            'value' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->paginate(10);

        return view('banners.index', compact('banners'));
    }

    public function create()
    {
        return view('banners.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:banner,label'],
            'image' => ['nullable', 'image', 'max:5120'],
            'url' => ['nullable', 'url'],
            'label_color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        Banner::create($data);

        ActivityLog::record('banner.create', 'Membuat banner ' . $data['title']);

        return redirect()->route('banners.index')->with('success', 'Banner berhasil dibuat.');
    }

    public function edit(Banner $banner)
    {
        return view('banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:banner,label'],
            'image' => ['nullable', 'image', 'max:5120'],
            'url' => ['nullable', 'url'],
            'label_color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        if ($request->hasFile('image')) {
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        $banner->update($data);

        ActivityLog::record('banner.update', 'Memperbarui banner ' . $banner->title);

        return redirect()->route('banners.index')->with('success', 'Banner berhasil diperbarui.');
    }

    public function destroy(Banner $banner)
    {
        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }

        ActivityLog::record('banner.delete', 'Menghapus banner ' . $banner->title);
        $banner->delete();

        return redirect()->route('banners.index')->with('success', 'Banner berhasil dihapus.');
    }
}

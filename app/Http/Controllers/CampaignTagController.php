<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CampaignTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignTagController extends Controller
{
    public function index()
    {
        $tags = CampaignTag::withCount('programs')->orderBy('name')->paginate(10);

        return view('campaign-tags.index', compact('tags'));
    }

    public function create()
    {
        return view('campaign-tags.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:1000'],
            'color' => ['required', 'string', 'max:20'],
        ]);

        $names = $this->parseNames($data['name']);
        $created = 0;

        foreach ($names as $name) {
            $slug = Str::slug($name);

            if (CampaignTag::where('slug', $slug)->exists()) {
                continue;
            }

            CampaignTag::create([
                'name' => $name,
                'slug' => $slug,
                'color' => $data['color'],
            ]);
            $created++;
        }

        if ($created > 0) {
            ActivityLog::record('campaign_tag.create', 'Membuat ' . $created . ' label kampanye: ' . implode(', ', $names));

            return redirect()->route('campaign-tags.index')->with('success', $created . ' label kampanye berhasil dibuat.');
        }

        return redirect()->route('campaign-tags.create')
            ->with('error', 'Tidak ada label baru yang dibuat (nama sudah digunakan).')
            ->withInput();
    }

    protected function parseNames(string $raw): array
    {
        $names = [];
        $seen = [];

        foreach (explode(',', $raw) as $part) {
            $name = trim($part);

            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $names[] = $name;
        }

        return array_slice($names, 0, 50);
    }

    public function edit(CampaignTag $campaignTag)
    {
        return view('campaign-tags.edit', compact('campaignTag'));
    }

    public function update(Request $request, CampaignTag $campaignTag)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:1000'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:campaign_tags,slug,' . $campaignTag->id],
            'color' => ['required', 'string', 'max:20'],
        ]);

        $names = $this->parseNames($data['name']);
        $primary = array_shift($names) ?? $campaignTag->name;

        $newSlug = $data['slug'] ?: Str::slug($primary);

        if (CampaignTag::where('slug', $newSlug)->where('id', '!=', $campaignTag->id)->exists()) {
            return back()->withErrors(['slug' => 'Slug sudah digunakan label lain.'])->withInput();
        }

        $campaignTag->update([
            'name' => $primary,
            'slug' => $newSlug,
            'color' => $data['color'],
        ]);

        $created = 0;

        foreach ($names as $name) {
            $slug = Str::slug($name);

            if (CampaignTag::where('slug', $slug)->exists()) {
                continue;
            }

            CampaignTag::create([
                'name' => $name,
                'slug' => $slug,
                'color' => $data['color'],
            ]);
            $created++;
        }

        $message = 'Label kampanye berhasil diperbarui.';
        if ($created > 0) {
            $message .= ' ' . $created . ' label baru turut dibuat.';
        }

        ActivityLog::record('campaign_tag.update', 'Memperbarui label kampanye ' . $campaignTag->name . ($created > 0 ? ' (+' . $created . ' label baru)' : ''));

        return redirect()->route('campaign-tags.index')->with('success', $message);
    }

    public function destroy(CampaignTag $campaignTag)
    {
        ActivityLog::record('campaign_tag.delete', 'Menghapus label kampanye ' . $campaignTag->name);
        $campaignTag->delete();

        return redirect()->route('campaign-tags.index')->with('success', 'Label kampanye berhasil dihapus.');
    }
}

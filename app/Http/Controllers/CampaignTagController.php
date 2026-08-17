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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:campaign_tags,slug'],
            'color' => ['required', 'string', 'max:20'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        CampaignTag::create($data);

        ActivityLog::record('campaign_tag.create', 'Membuat label kampanye ' . $data['name']);

        return redirect()->route('campaign-tags.index')->with('success', 'Label kampanye berhasil dibuat.');
    }

    public function edit(CampaignTag $campaignTag)
    {
        return view('campaign-tags.edit', compact('campaignTag'));
    }

    public function update(Request $request, CampaignTag $campaignTag)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:campaign_tags,slug,' . $campaignTag->id],
            'color' => ['required', 'string', 'max:20'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        $campaignTag->update($data);

        ActivityLog::record('campaign_tag.update', 'Memperbarui label kampanye ' . $campaignTag->name);

        return redirect()->route('campaign-tags.index')->with('success', 'Label kampanye berhasil diperbarui.');
    }

    public function destroy(CampaignTag $campaignTag)
    {
        ActivityLog::record('campaign_tag.delete', 'Menghapus label kampanye ' . $campaignTag->name);
        $campaignTag->delete();

        return redirect()->route('campaign-tags.index')->with('success', 'Label kampanye berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CampaignTag;
use App\Models\DonationItem;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        $query = Program::query()
            ->withCount(['donationItems as donation_items_count', 'campaignTags'])
            ->withSum('donationItems as total_collected', 'amount');

        if ($search = trim((string) $request->input('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($category = $request->input('program_category')) {
            $query->where('program_category', $category);
        }

        if ($tag = $request->input('tag')) {
            $query->whereHas('campaignTags', fn ($q) => $q->where('campaign_tags.id', $tag));
        }

        if (in_array($request->input('status'), ['0', '1'], true)) {
            $query->where('is_active', $request->boolean('status'));
        }

        $sort = $request->input('sort', 'name');
        $dir = $request->input('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        switch ($sort) {
            case 'collected':
                $query->orderBy('total_collected', $dir)->orderBy('name');
                break;
            case 'donations':
                $query->orderBy('donation_items_count', $dir)->orderBy('name');
                break;
            default:
                $query->orderBy('name', $dir);
        }

        $programs = $query->paginate(10)->withQueryString();

        $allTags = CampaignTag::orderBy('name')->get();

        return view('programs.index', compact('programs', 'allTags'));
    }

    public function create()
    {
        $defaultTags = CampaignTag::whereIn('slug', CampaignTag::DEFAULT_TAG_SLUGS)->orderBy('name')->get();
        $extraTags = CampaignTag::whereNotIn('slug', CampaignTag::DEFAULT_TAG_SLUGS)->orderBy('name')->get();
        $defaultTagValue = old('default_tag', '');
        $tagNamesValue = old('tag_names', '');

        return view('programs.create', compact('defaultTags', 'extraTags', 'defaultTagValue', 'tagNamesValue'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:programs,slug'],
            'program_category' => ['required', 'in:' . implode(',', array_keys(Program::CATEGORIES))],
            'category' => ['nullable', 'in:penggalangan,penyaluran'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'goal_amount' => ['required', 'numeric', 'min:0'],
            'terkumpul_publik' => ['nullable', 'numeric', 'min:0'],
            'suka' => ['nullable', 'integer', 'min:0'],
            'klik' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'show_goal' => ['boolean'],
            'media_paths' => ['nullable', 'array'],
            'media_paths.*' => ['nullable', 'string', 'max:255'],
            'media_orders' => ['nullable', 'array'],
            'media_orders.*' => ['nullable', 'integer', 'min:0', 'max:999'],
            'media_replace' => ['nullable', 'array'],
            'media_replace.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'media_new_orders' => ['nullable', 'array'],
            'media_new_orders.*' => ['nullable', 'integer', 'min:0', 'max:999'],
            'media_files' => ['nullable', 'array'],
            'media_files.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'default_tag' => ['required', 'string', 'in:' . implode(',', CampaignTag::DEFAULT_TAG_SLUGS)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:campaign_tags,id'],
            'tag_names' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $data['show_goal'] = $request->boolean('show_goal');
        $data['terkumpul_publik'] = (float) ($data['terkumpul_publik'] ?? 0);
        $data['suka'] = (int) ($data['suka'] ?? 0);
        $data['klik'] = (int) ($data['klik'] ?? 0);
        $data['media'] = $this->resolveMedia($request);

        $program = Program::create($data);
        $program->campaignTags()->sync($this->resolveTagIds($request));

        ActivityLog::record('program.create', 'Membuat program ' . $program->name);

        return redirect()->route('programs.index')->with('success', 'Program berhasil dibuat.');
    }

    public function edit(Program $program)
    {
        $defaultTags = CampaignTag::whereIn('slug', CampaignTag::DEFAULT_TAG_SLUGS)->orderBy('name')->get();
        $extraTags = CampaignTag::whereNotIn('slug', CampaignTag::DEFAULT_TAG_SLUGS)->orderBy('name')->get();

        $currentDefault = $program->campaignTags->first(function ($t) {
            return in_array($t->slug, CampaignTag::DEFAULT_TAG_SLUGS, true);
        });

        $defaultTagValue = old('default_tag', $currentDefault ? $currentDefault->slug : '');

        $extraNames = $program->campaignTags
            ->reject(function ($t) {
                return in_array($t->slug, CampaignTag::DEFAULT_TAG_SLUGS, true);
            })
            ->pluck('name')->implode(', ');

        $tagNamesValue = old('tag_names', $extraNames);

        return view('programs.edit', compact('program', 'defaultTags', 'extraTags', 'defaultTagValue', 'tagNamesValue'));
    }

    public function update(Request $request, Program $program)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:programs,slug,' . $program->id],
            'program_category' => ['required', 'in:' . implode(',', array_keys(Program::CATEGORIES))],
            'category' => ['nullable', 'in:penggalangan,penyaluran'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'goal_amount' => ['required', 'numeric', 'min:0'],
            'terkumpul_publik' => ['nullable', 'numeric', 'min:0'],
            'suka' => ['nullable', 'integer', 'min:0'],
            'klik' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'show_goal' => ['boolean'],
            'media_paths' => ['nullable', 'array'],
            'media_paths.*' => ['nullable', 'string', 'max:255'],
            'media_orders' => ['nullable', 'array'],
            'media_orders.*' => ['nullable', 'integer', 'min:0', 'max:999'],
            'media_replace' => ['nullable', 'array'],
            'media_replace.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'media_new_orders' => ['nullable', 'array'],
            'media_new_orders.*' => ['nullable', 'integer', 'min:0', 'max:999'],
            'media_files' => ['nullable', 'array'],
            'media_files.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'default_tag' => ['required', 'string', 'in:' . implode(',', CampaignTag::DEFAULT_TAG_SLUGS)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:campaign_tags,id'],
            'tag_names' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $data['show_goal'] = $request->boolean('show_goal');
        $data['terkumpul_publik'] = (float) ($data['terkumpul_publik'] ?? 0);
        $data['suka'] = (int) ($data['suka'] ?? 0);
        $data['klik'] = (int) ($data['klik'] ?? 0);
        $data['media'] = $this->resolveMedia($request);

        $program->update($data);
        $program->campaignTags()->sync($this->resolveTagIds($request));

        ActivityLog::record('program.update', 'Memperbarui program ' . $program->name);

        return redirect()->route('programs.index')->with('success', 'Program berhasil diperbarui.');
    }

    public function uploadRichImage(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $path = $request->file('image')->store('programs', 'public');

        if (!$path) {
            return response()->json(['error' => 'Gagal menyimpan gambar.'], 500);
        }

        return response()->json(['url' => asset('storage/' . $path)]);
    }

    protected function resolveTagIds(Request $request): array
    {
        $ids = [];

        $defaultSlug = (string) $request->input('default_tag', '');

        if (in_array($defaultSlug, CampaignTag::DEFAULT_TAG_SLUGS, true)) {
            $tag = CampaignTag::where('slug', $defaultSlug)->first();

            if ($tag) {
                $ids[$tag->id] = true;
            }
        }

        foreach ((array) $request->input('tags', []) as $id) {
            $ids[(int) $id] = true;
        }

        $raw = trim((string) $request->input('tag_names', ''));

        if ($raw !== '') {
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

                $tag = CampaignTag::where('slug', Str::slug($name))->first();

                if (! $tag) {
                    $tag = CampaignTag::create([
                        'name' => $name,
                        'slug' => Str::slug($name),
                        'color' => '#08A899',
                    ]);
                }

                $ids[$tag->id] = true;
            }
        }

        return array_keys($ids);
    }

    protected function resolveMedia(Request $request): array
    {
        $items = [];

        foreach ((array) $request->input('media_paths', []) as $i => $path) {
            if (!is_string($path) || trim($path) === '') {
                continue;
            }

            if ((string) $request->input("media_remove.$i", '0') === '1') {
                $this->deleteStoredMedia($path);
                continue;
            }

            $replacement = $request->file("media_replace.$i");

            if ($replacement !== null && $replacement->isValid()) {
                $newPath = $replacement->store('programs', 'public');

                if ($newPath) {
                    $this->deleteStoredMedia($path);
                    $path = $newPath;
                }
            }

            $items[] = [
                'path'  => $path,
                'order' => (int) $request->input("media_orders.$i", 0),
            ];
        }

        $maxOrder = count($items) ? max(array_column($items, 'order')) : -1;

        $files = array_values(array_filter($request->file('media_files') ?: [], function ($f) {
            return $f !== null && $f->isValid();
        }));

        $newOrders = array_values((array) $request->input('media_new_orders', []));

        foreach ($files as $idx => $file) {
            $path = $file->store('programs', 'public');

            if (!$path) {
                continue;
            }

            $order = isset($newOrders[$idx]) ? (int) $newOrders[$idx] : ($maxOrder + 1);

            if ($order <= $maxOrder) {
                $order = $maxOrder + 1;
            }

            $maxOrder = $order;
            $items[] = ['path' => $path, 'order' => $order];
        }

        usort($items, function ($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });

        return array_values($items);
    }

    protected function deleteStoredMedia(string $path): void
    {
        if (preg_match('#^(https?://|/|data:)#i', $path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public function destroy(Program $program)
    {
        if (DonationItem::where('program_id', $program->id)->exists()) {
            return back()->with('error', 'Program tidak dapat dihapus karena masih memiliki donasi.');
        }

        foreach ($program->media_items as $item) {
            $this->deleteStoredMedia($item['path']);
        }

        ActivityLog::record('program.delete', 'Menghapus program ' . $program->name);
        $program->delete();

        return redirect()->route('programs.index')->with('success', 'Program berhasil dihapus.');
    }
}

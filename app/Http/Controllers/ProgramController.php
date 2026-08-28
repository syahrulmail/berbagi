<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CampaignTag;
use App\Models\Donation;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = Program::withCount(['donations', 'campaignTags'])
            ->withSum('donations as total_collected', 'amount')
            ->orderBy('name')
            ->paginate(10);

        return view('programs.index', compact('programs'));
    }

    public function create()
    {
        $tags = CampaignTag::orderBy('name')->get();

        return view('programs.create', compact('tags'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:programs,slug'],
            'category' => ['nullable', 'in:penggalangan,penyaluran'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'url'],
            'goal_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:campaign_tags,id'],
            'tag_names' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        $program = Program::create($data);
        $program->campaignTags()->sync($this->resolveTagIds($request));

        ActivityLog::record('program.create', 'Membuat program ' . $program->name);

        return redirect()->route('programs.index')->with('success', 'Program berhasil dibuat.');
    }

    public function edit(Program $program)
    {
        $tags = CampaignTag::orderBy('name')->get();

        return view('programs.edit', compact('program', 'tags'));
    }

    public function update(Request $request, Program $program)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:programs,slug,' . $program->id],
            'category' => ['nullable', 'in:penggalangan,penyaluran'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'url'],
            'goal_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:campaign_tags,id'],
            'tag_names' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        $program->update($data);
        $program->campaignTags()->sync($this->resolveTagIds($request));

        ActivityLog::record('program.update', 'Memperbarui program ' . $program->name);

        return redirect()->route('programs.index')->with('success', 'Program berhasil diperbarui.');
    }

    protected function resolveTagIds(Request $request): array
    {
        $ids = [];

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

    public function destroy(Program $program)
    {
        if (Donation::where('program_id', $program->id)->exists()) {
            return back()->with('error', 'Program tidak dapat dihapus karena masih memiliki donasi.');
        }

        ActivityLog::record('program.delete', 'Menghapus program ' . $program->name);
        $program->delete();

        return redirect()->route('programs.index')->with('success', 'Program berhasil dihapus.');
    }
}

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
            'description' => ['nullable', 'string'],
            'goal_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:campaign_tags,id'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        $program = Program::create($data);
        $program->campaignTags()->sync($data['tags'] ?? []);

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
            'description' => ['nullable', 'string'],
            'goal_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:campaign_tags,id'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        $program->update($data);
        $program->campaignTags()->sync($data['tags'] ?? []);

        ActivityLog::record('program.update', 'Memperbarui program ' . $program->name);

        return redirect()->route('programs.index')->with('success', 'Program berhasil diperbarui.');
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

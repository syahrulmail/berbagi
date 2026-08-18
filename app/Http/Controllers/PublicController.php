<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\CampaignTag;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use App\Models\WaFollowup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicController extends Controller
{
    public function home()
    {
        $programs = Program::where('is_active', true)
            ->withSum('donations as total_collected', 'amount')
            ->with('campaignTags')
            ->orderByDesc('created_at')
            ->get();

        $banners = Banner::where('is_active', true)
            ->where('type', 'banner')
            ->orderBy('sort_order')
            ->get();

        $tags = Cache::remember('public_tags', 3600, function () {
            return CampaignTag::withCount('programs')->orderBy('name')->get();
        });

        $waNumber = Setting::get('wa_public_number', '6281234567890');
        $waTemplate = Setting::get('wa_public_template', '');

        $totalCollected = \App\Models\Donation::sum('amount');
        $totalAgents = User::where('role', User::ROLE_AGEN)->where('is_active', true)->count();

        return view('public.home', compact('programs', 'banners', 'tags', 'waNumber', 'waTemplate', 'totalCollected', 'totalAgents'));
    }

    public function program(Program $program)
    {
        if (!$program->is_active) {
            abort(404);
        }

        $collected = $program->donations()->sum('amount');
        $waNumber = Setting::get('wa_public_number', '6281234567890');
        $waTemplate = Setting::get('wa_public_template', '');
        $agen = null;

        return view('public.program', compact('program', 'collected', 'waNumber', 'waTemplate', 'agen'));
    }

    public function agentProgram(string $agentSlug, Program $program)
    {
        $agen = $this->resolveAgent($agentSlug);

        if (!$program->is_active) {
            abort(404);
        }

        $collected = $program->donations()->sum('amount');

        $waNumber = preg_replace('/\D/', '', $agen->phone ?: '');
        $waNumber = $waNumber !== ''
            ? $waNumber
            : preg_replace('/\D/', '', Setting::get('wa_public_number', '6281234567890'));

        $waTemplate = Setting::get('wa_agent_template', '');

        return view('public.program', compact('program', 'collected', 'waNumber', 'waTemplate', 'agen'));
    }

    public function agent(string $slug)
    {
        $agen = $this->resolveAgent($slug);

        $programs = Program::where('is_active', true)
            ->withSum('donations as total_collected', 'amount')
            ->with('campaignTags')
            ->orderByDesc('created_at')
            ->get();

        $waTemplate = Setting::get('wa_agent_template', '');
        $waFallback = Setting::get('wa_public_number', '6281234567890');

        return view('public.agent', compact('agen', 'programs', 'waTemplate', 'waFallback'));
    }

    protected function resolveAgent(string $slug): User
    {
        $agen = User::where('slug', $slug)->where('is_active', true)->first();

        if (!$agen || ($agen->role !== User::ROLE_AGEN && $agen->role !== User::ROLE_SUPERVISOR)) {
            abort(404);
        }

        return $agen;
    }

    public function followup(Request $request)
    {
        $data = $request->validate([
            'source' => ['required', 'in:home,program,agent'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'agen_id' => ['nullable', 'exists:users,id'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        WaFollowup::create([
            'agen_id' => $data['agen_id'] ?? null,
            'program_id' => $data['program_id'] ?? null,
            'phone' => $data['phone'] ?? null,
            'source' => $data['source'],
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 480),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }
}

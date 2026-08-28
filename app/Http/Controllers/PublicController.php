<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\CampaignTag;
use App\Models\Donation;
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

        $programCards = $this->cardifyPrograms($programs, $waNumber, $waTemplate, 'home');

        $totalCollected = \App\Models\Donation::sum('amount');
        $totalAgents = User::where('role', User::ROLE_AGEN)->where('is_active', true)->count();
        $globalTarget = (float) Setting::get('global_target', '1500000000');
        $globalProgress = $globalTarget > 0 ? min(100, round(($totalCollected / $globalTarget) * 100, 1)) : 0;

        $recentDonors = $this->recentDonors(6);

        return view('public.home', compact(
            'programs', 'banners', 'tags', 'waNumber', 'waTemplate',
            'totalCollected', 'totalAgents', 'globalTarget', 'globalProgress', 'recentDonors'
        ));
    }

    public function program(Program $program)
    {
        if (!$program->is_active) {
            abort(404);
        }

        $program->loadMissing('campaignTags');

        $collected = $program->donations()->sum('amount');
        $waNumber = Setting::get('wa_public_number', '6281234567890');
        $waTemplate = Setting::get('wa_public_template', '');
        $agen = null;

        $relatedCards = $this->relatedProgramCards($program, $waNumber, $waTemplate, 'program');

        return view('public.program', compact('program', 'collected', 'waNumber', 'waTemplate', 'agen', 'relatedCards'));
    }

    public function agentProgram(string $agentSlug, Program $program)
    {
        $agen = $this->resolveAgent($agentSlug);

        if (!$program->is_active) {
            abort(404);
        }

        $program->loadMissing('campaignTags');

        $collected = $program->donations()->sum('amount');

        $waNumber = preg_replace('/\D/', '', $agen->phone ?: '');
        $waNumber = $waNumber !== ''
            ? $waNumber
            : preg_replace('/\D/', '', Setting::get('wa_public_number', '6281234567890'));

        $waTemplate = Setting::get('wa_agent_template', '');

        $relatedCards = $this->relatedProgramCards($program, $waNumber, $waTemplate, 'agent');

        return view('public.program', compact('program', 'collected', 'waNumber', 'waTemplate', 'agen', 'relatedCards'));
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

    public function transparansi()
    {
        $totalCollected = Donation::sum('amount');
        $totalGoal = (float) Setting::get('global_target', '1500000000');
        $totalDonations = Donation::count();
        $globalProgress = $totalGoal > 0 ? min(100, round(($totalCollected / $totalGoal) * 100, 1)) : 0;

        $programs = Program::where('is_active', true)
            ->withSum('donations as total_collected', 'amount')
            ->orderBy('name')
            ->get();

        $perProgram = $programs->map(function ($p) {
            $collected = (float) ($p->total_collected ?? 0);
            $goal = (float) $p->goal_amount;

            return [
                'name'     => $p->name,
                'collected' => $collected,
                'goal'     => $goal,
                'progress' => $goal > 0 ? min(100, round(($collected / $goal) * 100, 1)) : 0,
            ];
        })->values();

        $yearly = Donation::selectRaw('YEAR(donation_date) as year, SUM(amount) as total')
            ->whereNotNull('donation_date')
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->map(function ($r) {
                return ['label' => (string) $r->year, 'value' => (int) $r->total];
            })
            ->values()->all();

        $waNumber = preg_replace('/\D/', '', Setting::get('wa_public_number', '6281234567890'));

        $recentDonors = $this->recentDonors(8);

        return view('public.transparansi', compact(
            'totalCollected', 'totalGoal', 'totalDonations', 'globalProgress',
            'perProgram', 'yearly', 'waNumber', 'recentDonors'
        ));
    }

    public function caraDonasi()
    {
        $waNumber = preg_replace('/\D/', '', Setting::get('wa_public_number', '6281234567890'));
        $waTemplate = Setting::get('wa_public_template', '');
        $programs = Program::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
        $recentDonors = $this->recentDonors(4);

        return view('public.cara-donasi', compact('waNumber', 'waTemplate', 'programs', 'recentDonors'));
    }

    protected function maskName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return '';
        }

        return mb_substr($name, 0, 1) . str_repeat('*', max(2, mb_strlen($name) - 1));
    }

    protected function recentDonors(int $limit = 6): array
    {
        return Donation::with(['contact', 'program'])
            ->latest('donation_date')
            ->limit($limit)
            ->get()
            ->map(function ($d) {
                $name = trim((string) optional($d->contact)->name);
                $masked = $this->maskName($name);

                return [
                    'name'    => $name !== '' ? 'Sdr. ' . $masked : 'Donatur anonim',
                    'initial' => $name !== '' ? mb_substr($name, 0, 1) : 'D',
                    'amount'  => 'Rp ' . number_format((float) $d->amount, 0, ',', '.'),
                    'program' => optional($d->program)->name ?? 'Wakaf umum',
                    'date'    => $d->donation_date ? $d->donation_date->format('d M Y') : '',
                ];
            })
            ->all();
    }

    protected function resolveAgent(string $slug): User
    {
        $agen = User::where('slug', $slug)->where('is_active', true)->first();

        if (!$agen || ($agen->role !== User::ROLE_AGEN && $agen->role !== User::ROLE_SUPERVISOR)) {
            abort(404);
        }

        return $agen;
    }

    protected function cardifyPrograms($programs, string $waNumber, string $waTemplate, string $waSource): array
    {
        return $programs->map(function ($p) use ($waNumber, $waTemplate, $waSource) {
            $collected = (float) ($p->total_collected ?? 0);
            $goal = (float) $p->goal_amount;
            $progress = $goal > 0 ? min(100, round(($collected / $goal) * 100, 1)) : 0;
            $isComplete = $goal > 0 && $collected >= $goal;
            $waMsg = str_replace('{program}', $p->name, $waTemplate ?: 'Assalamualaikum, saya ingin berdonasi untuk program {program}');

            return [
                'slug'        => $p->slug,
                'name'        => $p->name,
                'description' => $p->description,
                'image'       => $p->image_url,
                'category'    => $p->category ?? 'penggalangan',
                'tags'        => $p->campaignTags->pluck('name')->all(),
                'progress'    => $progress,
                'collected'   => 'Rp ' . number_format($collected, 0, ',', '.'),
                'goal'        => 'Rp ' . number_format($goal, 0, ',', '.'),
                'remaining'   => $isComplete ? null : 'Rp ' . number_format(max(0, $goal - $collected), 0, ',', '.'),
                'is_complete' => $isComplete,
                'url'         => route('public.program', $p->slug),
                'wa_url'      => 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMsg),
                'wa_source'   => $waSource,
                'wa_program'  => $p->id,
            ];
        })->values()->all();
    }

    protected function relatedProgramCards(Program $program, string $waNumber, string $waTemplate, string $waSource): array
    {
        $tagIds = $program->campaignTags->pluck('id');

        if ($tagIds->isEmpty()) {
            return [];
        }

        $related = Program::where('is_active', true)
            ->where('id', '!=', $program->id)
            ->whereHas('campaignTags', function ($q) use ($tagIds) {
                $q->whereIn('campaign_tags.id', $tagIds);
            })
            ->withSum('donations as total_collected', 'amount')
            ->with('campaignTags')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        return $this->cardifyPrograms($related, $waNumber, $waTemplate, $waSource);
    }

    public function followup(Request $request)
    {
        $data = $request->validate([
            'source' => ['required', 'in:home,program,agent,transparansi,cara-donasi'],
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

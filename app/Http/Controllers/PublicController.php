<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
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

        $achievements = Achievement::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tags = Cache::remember('public_tags', 3600, function () {
            return CampaignTag::withCount('programs')->orderBy('name')->get();
        });

        $waNumber = Setting::get('wa_public_number', '6281234567890');
        $waTemplate = Setting::get('wa_public_template', '');

        $programCards = $this->cardifyPrograms($programs, $waNumber, $waTemplate, 'home');

        $sections = $this->funnelSections();

        return view('public.home', compact(
            'programs', 'banners', 'achievements', 'tags', 'waNumber', 'waTemplate'
        ) + $sections);
    }

    protected function funnelSections(): array
    {
        $homeQuote = Setting::get('home_quote', '<p>&quot;Sebaik-baik manusia adalah yang paling bermanfaat bagi manusia lainnya.&quot; — <strong>HR. Ahmad &amp; Thabrani</strong></p>');

        $testimonials = array_values(array_filter(array_map(function ($item) {
            if (! is_array($item)) {
                return null;
            }
            $text = trim((string) ($item['text'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));

            if ($text === '' && $name === '') {
                return null;
            }

            return [
                'photo' => (string) ($item['photo'] ?? ''),
                'photo_url' => asset_photo_url((string) ($item['photo'] ?? '')),
                'text'  => $text,
                'name'  => $name,
            ];
        }, json_decode(Setting::get('home_testimonials', '[]'), true) ?: [])));

        $partnerLogos = array_values(array_filter(array_map(function ($item) {
            $path = trim(is_array($item) ? (string) ($item['photo'] ?? '') : (string) $item);

            return $path !== '' ? asset_photo_url($path) : null;
        }, json_decode(Setting::get('home_partner_logos', '[]'), true) ?: [])));

        return compact('homeQuote', 'testimonials', 'partnerLogos');
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

        return view('public.program', compact('program', 'collected', 'waNumber', 'waTemplate', 'agen', 'relatedCards'))->with('agenPhoto', '');
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

        $profile = $this->agentProfile($agen);
        $agenPhoto = asset_photo_url($profile['photo'] ?? '');

        return view('public.program', compact('program', 'collected', 'waNumber', 'waTemplate', 'agen', 'agenPhoto', 'relatedCards'));
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

        $achievements = Achievement::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tags = Cache::remember('public_tags', 3600, function () {
            return CampaignTag::withCount('programs')->orderBy('name')->get();
        });

        $sections = $this->funnelSections();

        $profile = $this->agentProfile($agen);
        $agenPhoto = asset_photo_url($profile['photo'] ?? '');
        $agenIntro = ($profile['intro'] ?? '') !== ''
            ? $profile['intro']
            : 'Assalamualaikum, saya siap membantu Anda menyalurkan wakaf, infak, dan sedekah melalui program-program BWA. Insya Allah amanah dan tepat sasaran.';

        return view('public.agent', compact('agen', 'programs', 'waTemplate', 'waFallback', 'achievements', 'tags', 'agenPhoto', 'agenIntro') + $sections);
    }

    protected function agentProfile(User $user): array
    {
        $decoded = json_decode(Setting::get('agent_profile_' . $user->slug, '{}'), true);

        if (! is_array($decoded)) {
            $decoded = [];
        }

        return [
            'photo' => (string) ($decoded['photo'] ?? ''),
            'intro' => (string) ($decoded['intro'] ?? ''),
        ];
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
                'tags'        => $this->cardifyTags($p),
                'progress'    => $progress,
                'collected'   => 'Rp ' . number_format($collected, 0, ',', '.'),
                'goal'        => 'Rp ' . number_format($goal, 0, ',', '.'),
                'remaining'   => $isComplete ? null : 'Rp ' . number_format(max(0, $goal - $collected), 0, ',', '.'),
                'is_complete' => $isComplete,
                'url'         => route('public.program', $p->slug),
                'wa_url'      => 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMsg),
                'wa_source'   => $waSource,
                'wa_program'  => $p->id,
                'edit_url'    => auth()->check() && auth()->user()->isAdmin() ? route('programs.edit', $p) : null,
            ];
        })->values()->all();
    }

    protected function cardifyTags($program): array
    {
        return $program->campaignTags->map(function ($t) {
            return [
                'name'       => $t->name,
                'color'      => $t->color,
                'is_default' => in_array($t->slug, CampaignTag::DEFAULT_TAG_SLUGS, true),
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

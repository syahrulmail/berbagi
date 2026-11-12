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

        $instagramPosts = $this->instagramPosts();

        return view('public.home', compact(
            'programs', 'programCards', 'banners', 'achievements', 'tags', 'waNumber', 'waTemplate', 'instagramPosts'
        ) + $sections);
    }

    /**
     * Daftar postingan Instagram yang ditampilkan di bawah kartu pencapaian.
     * Kunci "home_instagram_posts" menyimpan larik objek berisi "url",
     * "ratio", dan "thumbnail".
     *
     * @return array<int,array{url:string,embed:string,thumbnail:?string,ratio:float,zoom:float,top:int}>
     */
    protected function instagramPosts(): array
    {
        $decoded = json_decode((string) Setting::get('home_instagram_posts', '[]'), true);

        if (! is_array($decoded)) {
            return [];
        }

        $posts = [];
        foreach ($decoded as $item) {
            $url = is_array($item) ? (string) ($item['url'] ?? '') : (string) $item;
            $embed = instagram_embed_url($url);

            if ($embed === null) {
                continue;
            }

            // Rasio media diambil sekali saat admin menyimpan URL, agar
            // halaman publik tidak pernah menunggu panggilan ke Instagram.
            $ratio = is_array($item) ? (float) ($item['ratio'] ?? 0) : 0.0;
            if ($ratio < 0.5 || $ratio > 2.6) {
                $ratio = 1.0;
            }

            // Embed resmi membatasi tinggi media pada kotak 4:5 (1,25).
            // Media yang lebih tinggi (reel/IGTV) jadi mengecil dan diberi
            // bilah hitam di kiri-kanan, sehingga perlu diperbesar tepat
            // sampai lebarnya memenuhi kartu. Media 4:5 atau lebih pendek
            // sudah selebar kartu, tetapi masih ada sisa kotak di atasnya
            // yang ikut digeser agar media rata atas. Lebar kartu dikunci
            // 272px supaya hitungan piksel ini tepat.
            $cardWidth = 272;
            $zoom = max(1, $ratio / 1.25);
            $pad  = max(0, (1.25 - $ratio) / 2) * $cardWidth;

            // Sampul dipakai sebagai pengganti iframe sampai pengunjung
            // menekan kartu, agar halaman tidak memuat embed Instagram
            // (±229 KB HTML + JS eksternal) saat pertama dibuka.
            $thumbnail = is_array($item) ? trim((string) ($item['thumbnail'] ?? '')) : '';
            if ($thumbnail !== '' && ! preg_match('#^https://#i', $thumbnail)) {
                $thumbnail = '';
            }

            $posts[] = [
                'url'       => $url,
                'embed'     => $embed,
                'thumbnail' => $thumbnail !== '' ? $thumbnail : null,
                'ratio'     => $ratio,
                'zoom'      => round($zoom, 4),
                // 54px = tinggi header embed Instagram pada lebar alaminya.
                'top'       => (int) round(-(54 * $zoom + $pad)),
            ];
        }

        return $posts;
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

            $photo = (string) ($item['photo'] ?? '');
            $dims = photo_dimensions($photo);

            return [
                'photo' => $photo,
                'photo_url' => asset_photo_url($photo),
                'width' => $dims[0] ?? null,
                'height' => $dims[1] ?? null,
                'text'  => $text,
                'name'  => $name,
            ];
        }, $this->decodeJsonList(Setting::get('home_testimonials', '[]')))));

        $partnerLogos = array_values(array_filter(array_map(function ($item) {
            $path = trim(is_array($item) ? (string) ($item['photo'] ?? '') : (string) $item);

            if ($path === '') {
                return null;
            }

            $dims = photo_dimensions($path);

            return [
                'url' => asset_photo_url($path),
                'width' => $dims[0] ?? null,
                'height' => $dims[1] ?? null,
            ];
        }, $this->decodeJsonList(Setting::get('home_partner_logos', '[]')))));

        return compact('homeQuote', 'testimonials', 'partnerLogos');
    }

    /**
     * Decode setelan JSON yang berupa daftar menjadi array.
     * Nilai yang tidak valid / bukan array (mis. skalar atau string)
     * dikembalikan sebagai array kosong agar tidak memicu TypeError.
     *
     * @param  mixed  $json
     * @return array<int,mixed>
     */
    protected function decodeJsonList($json): array
    {
        $decoded = json_decode((string) $json, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function program(Program $program)
    {
        if (!$program->is_active) {
            abort(404);
        }

        $program->loadMissing('campaignTags');

        $collected = (float) $program->public_collected;
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

        $collected = (float) $program->public_collected;

        $waNumber = normalize_wa_phone($agen->phone ?: '')
            ?? (preg_replace('/\D/', '', Setting::get('wa_public_number', '6281234567890')) ?: '6281234567890');

        $waTemplate = Setting::get('wa_agent_template', '');

        $relatedCards = $this->relatedProgramCards($program, $waNumber, $waTemplate, 'agent', $agen);

        $profile = $this->agentProfile($agen);
        $agenPhoto = asset_photo_url($profile['photo'] ?? '');

        return view('public.program', compact('program', 'collected', 'waNumber', 'waTemplate', 'agen', 'agenPhoto', 'relatedCards'));
    }

    public function agent(string $slug)
    {
        $agen = $this->resolveAgent($slug);

        $programs = Program::where('is_active', true)
            ->with('campaignTags')
            ->orderByDesc('created_at')
            ->get();

        $waTemplate = Setting::get('wa_agent_template', '');
        $waFallback = Setting::get('wa_public_number', '6281234567890');

        $cleanPhone = normalize_wa_phone($agen->phone ?: '');
        $waNumber = $cleanPhone ?? (preg_replace('/\D/', '', $waFallback) ?: '6281234567890');

        $programCards = $this->cardifyPrograms($programs, $waNumber, $waTemplate, 'agent', $agen);

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

        $instagramPosts = $this->instagramPosts();

        return view('public.agent', compact('agen', 'programs', 'programCards', 'waTemplate', 'waFallback', 'achievements', 'tags', 'agenPhoto', 'agenIntro', 'instagramPosts') + $sections);
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

    protected function cardifyPrograms($programs, string $waNumber, string $waTemplate, string $waSource, $agen = null): array
    {
        return $programs->map(function ($p) use ($waNumber, $waTemplate, $waSource, $agen) {
            $collected = (float) $p->public_collected;
            $goal = (float) $p->goal_amount;
            $progress = $goal > 0 ? min(100, round(($collected / $goal) * 100, 1)) : 0;
            $isComplete = $goal > 0 && $collected >= $goal;

            if ($agen) {
                $waMsg = str_replace(
                    ['{agen}', '{program}'],
                    [$agen->name, $p->name],
                    $waTemplate ?: 'Assalamualaikum {agen}, saya ingin berdonasi untuk program {program} melalui Anda.'
                );
                $url = route('public.agent-program', ['agentSlug' => $agen->slug, 'program' => $p->slug]);
            } else {
                $waMsg = str_replace('{program}', $p->name, $waTemplate ?: 'Assalamualaikum, saya ingin berdonasi untuk program {program}');
                $url = route('public.program', $p->slug);
            }

            return [
                'id'          => $p->id,
                'slug'        => $p->slug,
                'name'        => $p->name,
                'description' => \Illuminate\Support\Str::limit(strip_tags((string) $p->description), 160),
                'image'       => $p->image_url,
                'media'       => $p->media_slides,
                'category'    => $p->category ?? 'penggalangan',
                'tags'        => $this->cardifyTags($p),
                'progress'    => $progress,
                'collected'   => 'Rp ' . number_format($collected, 0, ',', '.'),
                'goal'        => 'Rp ' . number_format($goal, 0, ',', '.'),
                'remaining'   => $isComplete ? null : 'Rp ' . number_format(max(0, $goal - $collected), 0, ',', '.'),
                'is_complete' => $isComplete,
                'show_goal'   => (bool) $p->show_goal,
                'suka'        => (int) $p->total_suka,
                'klik'        => (int) $p->total_klik,
                'url'         => $url,
                'wa_url'      => 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMsg),
                'wa_source'   => $waSource,
                'wa_program'  => $p->id,
                'wa_agen'     => $agen ? $agen->id : null,
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

    protected function relatedProgramCards(Program $program, string $waNumber, string $waTemplate, string $waSource, $agen = null): array
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
            ->with('campaignTags')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        return $this->cardifyPrograms($related, $waNumber, $waTemplate, $waSource, $agen);
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

    /**
     * Catat klik suka dari pengunjung (tanpa batas per pengunjung).
     * Angka publik yang ditampilkan = input 'Suka' + klik suka riil ini.
     */
    public function suka(Program $program)
    {
        if (!$program->is_active) {
            abort(404);
        }

        $program->increment('suka_riil');

        return response()->json([
            'success' => true,
            'total'   => (int) $program->suka + (int) $program->fresh()->suka_riil,
        ]);
    }

    /**
     * Catat klik 'Detail' (buka modal detail) dari pengunjung.
     * Angka publik yang ditampilkan = input 'Klik' + klik detail riil ini.
     */
    public function klik(Program $program)
    {
        if (!$program->is_active) {
            abort(404);
        }

        $program->increment('klik_riil');

        return response()->json([
            'success' => true,
            'total'   => (int) $program->klik + (int) $program->fresh()->klik_riil,
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Donation;
use App\Models\PopupBanner;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PopupService
{
    /**
     * Halaman publik yang sedang dibuka. Halaman program milik agen
     * dianggap cocok untuk banner "Detail Program" maupun "Halaman Agen".
     *
     * @return array<int,string>
     */
    public function pageContexts(): array
    {
        $name = optional(request()->route())->getName();

        if ($name === 'public.program') {
            return ['program'];
        }

        if ($name === 'public.agent-program') {
            return ['program', 'agent'];
        }

        if ($name === 'public.agent') {
            return ['agent'];
        }

        return ['home'];
    }

    /**
     * Banner popup yang aktif untuk halaman & agen saat ini.
     *
     * @param  \App\Models\User|null  $agent
     * @return array<int,array<string,mixed>>
     */
    public function banners(?User $agent = null): array
    {
        $contexts = $this->pageContexts();

        $banners = PopupBanner::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $banners
            ->filter(function (PopupBanner $banner) use ($contexts, $agent) {
                return $this->matchesSchedule($banner)
                    && $this->matchesPage($banner, $contexts)
                    && $this->matchesAgent($banner, $contexts, $agent);
            })
            ->map(function (PopupBanner $banner) {
                return [
                    'id'            => $banner->id,
                    'title'         => $banner->title,
                    'image'         => $banner->image_url,
                    'url'           => $banner->url,
                    'trigger'       => $banner->trigger_type,
                    'delay_minutes' => max(1, (int) $banner->delay_minutes),
                ];
            })
            ->values()
            ->all();
    }

    protected function matchesSchedule(PopupBanner $banner): bool
    {
        $now = now();

        if ($banner->schedule_type === 'range') {
            if ($banner->start_date && $now->copy()->startOfDay()->lt($banner->start_date->copy()->startOfDay())) {
                return false;
            }

            if ($banner->end_date && $now->copy()->startOfDay()->gt($banner->end_date->copy()->startOfDay())) {
                return false;
            }
        }

        if ($banner->schedule_type === 'days') {
            $days = array_map('intval', is_array($banner->days) ? $banner->days : []);

            if (! in_array((int) $now->dayOfWeek, $days, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int,string>  $contexts
     */
    protected function matchesPage(PopupBanner $banner, array $contexts): bool
    {
        $pages = is_array($banner->pages) ? $banner->pages : [];

        if (empty($pages)) {
            return true;
        }

        return count(array_intersect($pages, $contexts)) > 0;
    }

    /**
     * @param  array<int,string>  $contexts
     */
    protected function matchesAgent(PopupBanner $banner, array $contexts, ?User $agent): bool
    {
        if (! in_array('agent', $contexts, true)) {
            return true;
        }

        if ($banner->agent_scope !== 'selected') {
            return true;
        }

        if (! $agent) {
            return false;
        }

        $ids = array_map('intval', is_array($banner->agent_ids) ? $banner->agent_ids : []);

        return in_array((int) $agent->id, $ids, true);
    }

    /**
     * Data widget statistik kecil di kiri bawah halaman publik.
     *
     * @param  \App\Models\User|null  $agent
     * @return array<string,mixed>|null
     */
    public function statsWidget(?User $agent = null): ?array
    {
        if ((string) Setting::get('popup_stats_enabled', '1') === '0') {
            return null;
        }

        $data = Cache::remember('public_popup_stats_v1', 300, function () {
            return $this->buildStatsData();
        });

        if (empty($data['patterns'])) {
            return null;
        }

        $waUrl = $this->whatsappUrl($agent);
        $programUrl = $agent
            ? route('public.agent', $agent->slug) . '#program'
            : route('home') . '#program';

        $patterns = array_map(function ($pattern) use ($waUrl, $programUrl) {
            if ($pattern['type'] === 'achievement') {
                $pattern['url'] = $programUrl;
                $pattern['cta'] = 'Lihat program';
                $pattern['external'] = false;
            } else {
                $pattern['url'] = $waUrl;
                $pattern['cta'] = 'Berbagi sekarang';
                $pattern['external'] = true;
            }

            return $pattern;
        }, $data['patterns']);

        return [
            'delay_seconds' => max(0, (int) Setting::get('popup_stats_delay_seconds', 8)),
            'patterns'      => array_values($patterns),
        ];
    }

    /**
     * @return array{patterns: array<int,array<string,mixed>>}
     */
    protected function buildStatsData(): array
    {
        $patterns = [];
        $now = now();

        $todayCount = $this->peopleCount($now->copy()->startOfDay(), $now->copy()->endOfDay());
        $category = $this->todayCategory();

        if ($todayCount > 0 && $category !== '') {
            $patterns[] = [
                'type'     => 'category',
                'count'    => $todayCount,
                'category' => $category,
            ];
        }

        $periods = [
            ['label' => 'hari ini', 'start' => $now->copy()->startOfDay(), 'end' => $now->copy()->endOfDay()],
            ['label' => 'kemarin', 'start' => $now->copy()->subDay()->startOfDay(), 'end' => $now->copy()->subDay()->endOfDay()],
            ['label' => 'pekan ini', 'start' => $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(), 'end' => $now->copy()->endOfDay()],
            ['label' => 'bulan ini', 'start' => $now->copy()->startOfMonth()->startOfDay(), 'end' => $now->copy()->endOfDay()],
        ];

        foreach ($periods as $period) {
            $count = $this->peopleCount($period['start'], $period['end']);

            if ($count > 0) {
                $patterns[] = [
                    'type'   => 'period',
                    'period' => $period['label'],
                    'count'  => $count,
                ];
            }
        }

        $achievements = Achievement::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(5)
            ->get(['value', 'label', 'icon']);

        foreach ($achievements as $achievement) {
            $patterns[] = [
                'type'  => 'achievement',
                'value' => (string) $achievement->value,
                'label' => (string) $achievement->label,
                'icon'  => (string) ($achievement->icon ?: 'fa-trophy'),
            ];
        }

        return ['patterns' => $patterns];
    }

    protected function peopleCount(Carbon $start, Carbon $end): int
    {
        $base = Donation::whereDate('donation_date', '>=', $start->toDateString())
            ->whereDate('donation_date', '<=', $end->toDateString());

        $withContact = (clone $base)->whereNotNull('contact_id')->distinct()->count('contact_id');
        $withoutContact = (clone $base)->whereNull('contact_id')->count();

        return (int) ($withContact + $withoutContact);
    }

    protected function todayCategory(): string
    {
        $rows = Donation::query()
            ->join('programs', 'programs.id', '=', 'donations.program_id')
            ->whereDate('donations.donation_date', now()->toDateString())
            ->select('programs.program_category as category', DB::raw('COUNT(*) as total'))
            ->groupBy('programs.program_category')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return '';
        }

        $row = $rows->random();
        $code = (string) $row->category;

        return Program::CATEGORIES[$code] ?? $code;
    }

    protected function whatsappUrl(?User $agent = null): string
    {
        $phone = $agent ? preg_replace('/\D/', '', (string) $agent->phone) : '';

        if ($phone === '') {
            $phone = preg_replace('/\D/', '', (string) Setting::get('wa_public_number', '6281234567890'));
        }

        $greeting = $agent
            ? 'Assalamualaikum ' . $agent->name . ', saya ingin ikut berdonasi melalui Berbagi.or.id.'
            : 'Assalamualaikum, saya ingin ikut berdonasi melalui Berbagi.or.id.';

        return 'https://wa.me/' . $phone . '?text=' . urlencode($greeting . ' Mohon info selanjutnya.');
    }
}

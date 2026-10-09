<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use App\Services\IntegrationCheckService;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MobileAppController extends Controller
{
    /**
     * Scope data donasi berdasarkan peran user.
     */
    protected function scopeDonations($query, ?string $table = null)
    {
        $user = auth()->user();
        $column = fn (string $name) => $table ? "{$table}.{$name}" : $name;

        if ($user->isAgen()) {
            $query->where($column('agen_id'), $user->id);
        } elseif ($user->isSupervisor() && $user->branch_id) {
            $query->where($column('branch_id'), $user->branch_id);
        }

        return $query;
    }

    /**
     * Normalisasi input tanggal menjadi Y-m-d (atau null bila tidak valid).
     */
    protected function sanitizeDate($value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Scope data kontak berdasarkan peran user.
     */
    protected function scopeContacts($query)
    {
        $user = auth()->user();

        if ($user->isAgen()) {
            $query->where('agen_id', $user->id);
        } elseif ($user->isSupervisor() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    /**
     * Cabang yang dapat diakses user (untuk filter unduhan).
     */
    protected function visibleBranches()
    {
        $query = Branch::where('is_active', true)->orderBy('name');

        $user = auth()->user();

        if ($user && ($user->isSupervisor() || $user->isAgen())) {
            $query->where('id', $user->branch_id);
        }

        return $query->get();
    }

    protected function branchStats($branch, $month, $year)
    {
        $collected = $branch->donations()
            ->whereYear('donation_date', $year)
            ->whereMonth('donation_date', $month)
            ->sum('amount');

        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'city' => $branch->city,
            'target' => (float) $branch->target_amount,
            'collected' => (float) $collected,
            'progress' => $branch->target_amount > 0
                ? round(((float) $collected / (float) $branch->target_amount) * 100, 1)
                : 0,
        ];
    }

    /**
     * Entri mobile (/mo): alihkan ke dashboard setelah login.
     */
    public function home()
    {
        return redirect()->route('mo.dashboard');
    }

    /**
     * Dashboard mobile (/mo/dashboard).
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $today = now()->toDateString();

        // Ringkasan donasi (scoped)
        $donationsQuery = Donation::query();
        $this->scopeDonations($donationsQuery);

        // Pemilihan cabang (khusus admin, bisa lebih dari satu)
        $isAdmin = $user->isAdmin();
        $branches = $isAdmin
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        $selectedBranches = [];
        if ($isAdmin) {
            $selectedBranches = array_values(array_filter(
                array_map('intval', (array) $request->query('branches', [])),
                fn ($value) => $value > 0
            ));

            if (! empty($selectedBranches)) {
                $donationsQuery->whereIn('branch_id', $selectedBranches);
            }
        }

        $todayTotal = (clone $donationsQuery)->where('donation_date', $today)->sum('amount');
        $donorsToday = (clone $donationsQuery)
            ->where('donation_date', $today)
            ->whereNotNull('contact_id')
            ->distinct()
            ->count('contact_id');
        $todayTransactions = (clone $donationsQuery)
            ->where('donation_date', $today)
            ->count();

        // Periode cepat (quick tabs): all | 7d | month | year
        $range = $request->query('range');
        $range = in_array($range, ['all', '7d', 'month', 'year'], true) ? $range : null;
        $periodIsAll = ($range === 'all');

        if ($periodIsAll) {
            $earliest = (clone $donationsQuery)->min('donation_date');
            $from = $this->sanitizeDate($request->get('from')) ?: ($earliest ?: $today);
            $to = $this->sanitizeDate($request->get('to')) ?: $today;
        } else {
            $rangeFrom = null;
            if ($range === '7d') {
                $rangeFrom = now()->subDays(6)->toDateString();
            } elseif ($range === 'month') {
                $rangeFrom = now()->startOfMonth()->toDateString();
            } elseif ($range === 'year') {
                $rangeFrom = now()->startOfYear()->toDateString();
            }

            $from = $this->sanitizeDate($request->get('from')) ?: ($rangeFrom ?: now()->startOfMonth()->toDateString());
            $to = $this->sanitizeDate($request->get('to')) ?: $today;
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $fromDate = Carbon::parse($from);
        $toDate = Carbon::parse($to);

        $periodQuery = (clone $donationsQuery);
        if (! $periodIsAll) {
            $periodQuery->whereDate('donation_date', '>=', $from)
                ->whereDate('donation_date', '<=', $to);
        }

        $periodTotal = (clone $periodQuery)->sum('amount');
        $periodTransactions = (clone $periodQuery)->count();
        $periodDonors = (clone $periodQuery)
            ->whereNotNull('contact_id')
            ->distinct()
            ->count('contact_id');

        // Periode sebelumnya (panjang sama) untuk menghitung pertumbuhan
        $days = $fromDate->diffInDays($toDate) + 1;
        if ($periodIsAll) {
            $growthPercent = 0;
        } else {
            $prevTo = $fromDate->copy()->subDay();
            $prevFrom = $prevTo->copy()->subDays($days - 1);
            $prevPeriodTotal = (clone $donationsQuery)
                ->whereDate('donation_date', '>=', $prevFrom->toDateString())
                ->whereDate('donation_date', '<=', $prevTo->toDateString())
                ->sum('amount');

            $growthPercent = $prevPeriodTotal > 0
                ? round((($periodTotal - $prevPeriodTotal) / $prevPeriodTotal) * 100, 1)
                : 0;
        }

        // Rekap seluruh data tercatat (tidak terpengaruh periode)
        $totalRecorded = (clone $donationsQuery)->sum('amount');
        $totalTransactions = (clone $donationsQuery)->count();
        $totalDonors = (clone $donationsQuery)->whereNotNull('contact_id')->distinct()->count('contact_id');

        // Target: admin melihat total cabang yang dipilih (atau semua cabang aktif),
        // selain itu target cabang sendiri.
        if ($isAdmin) {
            $totalTarget = empty($selectedBranches)
                ? Branch::where('is_active', true)->sum('target_amount')
                : Branch::whereIn('id', $selectedBranches)->sum('target_amount');
        } elseif ($user->isSupervisor() && $user->branch) {
            $totalTarget = (float) $user->branch->target_amount;
        } else {
            $totalTarget = 0;
        }

        $overallProgress = $totalTarget > 0
            ? round(($periodTotal / $totalTarget) * 100, 1)
            : 0;

        // Tren sesuai periode terpilih
        $trend = [];
        if ($days <= 31) {
            $rows = (clone $periodQuery)
                ->selectRaw('YEAR(donation_date) as yr, MONTH(donation_date) as mon, DAY(donation_date) as day, SUM(amount) as total')
                ->groupBy('yr', 'mon', 'day')
                ->get();

            $map = [];
            foreach ($rows as $row) {
                $map[sprintf('%04d-%02d-%02d', $row->yr, $row->mon, $row->day)] = (int) $row->total;
            }

            for ($date = $fromDate->copy(); $date->lte($toDate); $date->addDay()) {
                $trend[] = [
                    'label' => $date->format('d/m'),
                    'is_weekend' => $date->isWeekend(),
                    'value' => $map[$date->toDateString()] ?? 0,
                ];
            }
        } else {
            $rows = (clone $periodQuery)
                ->selectRaw('YEAR(donation_date) as yr, MONTH(donation_date) as mon, SUM(amount) as total')
                ->groupBy('yr', 'mon')
                ->get();

            $map = [];
            foreach ($rows as $row) {
                $map[sprintf('%04d-%02d', $row->yr, $row->mon)] = (int) $row->total;
            }

            for ($date = $fromDate->copy()->startOfMonth(); $date->lte($toDate); $date->addMonth()) {
                $trend[] = [
                    'label' => $date->format('m/Y'),
                    'is_weekend' => false,
                    'value' => $map[$date->format('Y-m')] ?? 0,
                ];
            }
        }
        $trendMax = max(1, max(array_column($trend, 'value')));

        // 10 kontak dengan total donasi tertinggi (sesuai cabang & periode)
        $topContactRows = (clone $periodQuery)
            ->whereNotNull('contact_id')
            ->selectRaw('contact_id, SUM(amount) as total, COUNT(*) as transactions')
            ->groupBy('contact_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $contactsById = Contact::whereIn('id', $topContactRows->pluck('contact_id'))->get()->keyBy('id');

        $topContacts = $topContactRows->map(function ($row) use ($contactsById) {
            $contact = $contactsById->get($row->contact_id);

            return (object) [
                'contact_id' => $row->contact_id,
                'name' => $contact->name ?? 'Donatur',
                'phone' => $contact->phone ?? '',
                'initial' => strtoupper(substr($contact->name ?? '?', 0, 1)),
                'total_formatted' => 'Rp ' . number_format((float) $row->total, 0, ',', '.'),
                'transactions' => (int) $row->transactions,
            ];
        });

        // 10 program dengan total donasi tertinggi (sesuai cabang & periode)
        $topProgramQuery = \Illuminate\Support\Facades\DB::table('donation_items')
            ->join('donations', 'donations.id', '=', 'donation_items.donation_id')
            ->when(! $periodIsAll, fn ($q) => $q
                ->whereDate('donations.donation_date', '>=', $from)
                ->whereDate('donations.donation_date', '<=', $to))
            ->when($isAdmin && ! empty($selectedBranches), fn ($q) => $q->whereIn('donations.branch_id', $selectedBranches));
        $this->scopeDonations($topProgramQuery, 'donations');

        $topProgramRows = $topProgramQuery
            ->groupBy('donation_items.program_id')
            ->selectRaw('donation_items.program_id as program_id, SUM(donation_items.amount) as total, COUNT(DISTINCT donation_items.donation_id) as transactions')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $programsById = Program::whereIn('id', $topProgramRows->pluck('program_id'))->get()->keyBy('id');

        $topPrograms = $topProgramRows->map(function ($row) use ($programsById) {
            $program = $programsById->get($row->program_id);

            return (object) [
                'program_id' => $row->program_id,
                'name' => $program->name ?? 'Program',
                'category_label' => $program->category_label ?? null,
                'total_formatted' => 'Rp ' . number_format((float) $row->total, 0, ',', '.'),
                'transactions' => (int) $row->transactions,
            ];
        });

        // Papan peringkat cabang & agen: terlihat semua role, sesuai periode.
        $leaderboardQuery = Donation::query();
        if (! $periodIsAll) {
            $leaderboardQuery->whereDate('donation_date', '>=', $from)
                ->whereDate('donation_date', '<=', $to);
        }
        if ($isAdmin && ! empty($selectedBranches)) {
            $leaderboardQuery->whereIn('branch_id', $selectedBranches);
        }

        // Cabang dengan total donasi tertinggi (sesuai periode)
        $topBranchRows = (clone $leaderboardQuery)
            ->whereNotNull('branch_id')
            ->selectRaw('branch_id, SUM(amount) as total, COUNT(*) as transactions')
            ->groupBy('branch_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $branchesById = Branch::whereIn('id', $topBranchRows->pluck('branch_id'))->get()->keyBy('id');

        $topBranches = $topBranchRows->map(function ($row) use ($branchesById) {
            $branch = $branchesById->get($row->branch_id);

            return (object) [
                'branch_id' => $row->branch_id,
                'name' => $branch->name ?? 'Cabang',
                'total_formatted' => 'Rp ' . number_format((float) $row->total, 0, ',', '.'),
                'transactions' => (int) $row->transactions,
            ];
        });

        // Agen dengan total donasi tertinggi (> 0) sesuai periode
        $topAgentRows = (clone $leaderboardQuery)
            ->whereNotNull('agen_id')
            ->selectRaw('agen_id, SUM(amount) as total, COUNT(*) as transactions')
            ->groupBy('agen_id')
            ->havingRaw('SUM(amount) > 0')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $agentsById = User::with('branch')->whereIn('id', $topAgentRows->pluck('agen_id'))->get()->keyBy('id');

        $topAgents = $topAgentRows->map(function ($row) use ($agentsById) {
            $agent = $agentsById->get($row->agen_id);

            return (object) [
                'agent_id' => $row->agen_id,
                'name' => $agent->name ?? 'Agen',
                'branch_name' => ($agent && $agent->branch) ? $agent->branch->name : null,
                'initial' => strtoupper(substr($agent->name ?? '?', 0, 1)),
                'total_formatted' => 'Rp ' . number_format((float) $row->total, 0, ',', '.'),
                'transactions' => (int) $row->transactions,
            ];
        });

        $totalPrograms = Program::where('is_active', true)->count();
        $totalContacts = (clone $this->scopeContacts(Contact::query()))->count();
        $donatedContacts = (clone $this->scopeContacts(Contact::query()))->where('status', 'donated')->count();

        if ($periodIsAll) {
            $periodLabel = 'Seluruh data';
        } elseif ($range === '7d') {
            $periodLabel = '7 Hari Terakhir';
        } elseif ($range === 'month') {
            $periodLabel = 'Bulan Ini';
        } elseif ($range === 'year') {
            $periodLabel = 'Tahun Ini';
        } else {
            $periodLabel = $fromDate->format('d M Y') . ' - ' . $toDate->format('d M Y');
        }

        if ($periodIsAll) {
            $activeRange = 'all';
        } elseif ($range !== null) {
            $activeRange = $range;
        } else {
            $activeRange = ($from === now()->startOfMonth()->toDateString() && $to === $today) ? 'month' : null;
        }

        $hour = (int) now()->format('G');
        $greeting = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 19 ? 'Selamat Sore' : 'Selamat Malam'));

        $waNumber = Setting::get('wa_public_number', '');

        if ($isAdmin) {
            if (empty($selectedBranches)) {
                $branchSummary = 'Semua Cabang';
            } elseif (count($selectedBranches) === 1) {
                $branchSummary = optional($branches->firstWhere('id', $selectedBranches[0]))->name ?: '1 Cabang';
            } else {
                $branchSummary = count($selectedBranches) . ' Cabang';
            }
        } else {
            $branchSummary = $user->branch->name ?? '-';
        }

        return view('mobile.home', compact(
            'user', 'greeting', 'todayTotal', 'todayTransactions', 'donorsToday',
            'periodTotal', 'periodTransactions', 'periodDonors', 'periodLabel', 'growthPercent',
            'overallProgress', 'totalTarget', 'trend', 'trendMax',
            'topContacts', 'topPrograms', 'totalPrograms', 'totalContacts',
            'topBranches', 'topAgents',
            'donatedContacts', 'waNumber',
            'totalRecorded', 'totalTransactions', 'totalDonors',
            'branches', 'selectedBranches', 'branchSummary', 'isAdmin', 'from', 'to',
            'periodIsAll', 'range', 'activeRange'
        ));
    }

    /**
     * Daftar donasi mobile (mendukung search & filter).
     */
    public function donations(Request $request)
    {
        $query = Donation::with(['branch', 'agen', 'contact', 'items.program', 'program'])
            ->select('donations.*');
        $this->scopeDonations($query);

        $query->when($request->search, function ($q, $search) {
            $search = trim($search);
            $digits = preg_replace('/[^0-9]/', '', $search);

            return $q->where(function ($inner) use ($search, $digits) {
                $inner->whereHas('contact', function ($c) use ($search, $digits) {
                    $c->where('name', 'like', "%{$search}%");
                    if ($digits !== '') {
                        $c->orWhere('phone', 'like', "%{$digits}%");
                    }
                })
                    ->orWhereHas('program', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('items.program', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhere('donor_info', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        })
        ->when($request->from, fn ($q, $from) => $q->whereDate('donations.donation_date', '>=', $from))
        ->when($request->to, fn ($q, $to) => $q->whereDate('donations.donation_date', '<=', $to))
        ->when($request->period === 'today', fn ($q) => $q->whereDate('donations.donation_date', now()->toDateString()))
        ->when($request->period === 'week', fn ($q) => $q->whereDate('donations.donation_date', '>=', now()->subDays(6)->toDateString()));

        if ($request->get('sort') === 'amount') {
            $donations = $query->orderByDesc('donations.amount')
                ->orderByDesc('donations.id')
                ->limit(50)
                ->get();
        } else {
            $donations = $query->orderByDesc('donations.donation_date')
                ->orderByDesc('donations.id')
                ->limit(50)
                ->get();
        }

        $donations->each(function ($d) {
            $d->amount_formatted = 'Rp ' . number_format((float) $d->amount, 0, ',', '.');
            $d->date_formatted = $d->donation_date ? $d->donation_date->format('d M Y') : '-';
            $d->program_label = $d->items->isNotEmpty()
                ? $d->items->map(fn ($i) => $i->program->name ?? '')->filter()->implode(', ')
                : ($d->program->name ?? '-');
            $d->total_amount = (float) $d->amount;
            $d->payment_method_label = $this->paymentMethodLabel($d->payment_method);
        });

        $downloadBranches = $this->visibleBranches();

        return view('mobile.donations', compact('donations', 'downloadBranches'));
    }

    /**
     * Daftar kontak mobile.
     */
    public function contacts(Request $request)
    {
        $query = Contact::with(['agen', 'branch'])
            ->withCount('donations as donation_count')
            ->withSum('donations as donation_total', 'amount')
            ->withMax('donations as last_donation_date', 'donation_date');
        $this->scopeContacts($query);

        $query->when($request->search, function ($q, $search) {
            $search = trim($search);
            $digits = preg_replace('/[^0-9]/', '', $search);

            return $q->where(function ($inner) use ($search, $digits) {
                $inner->where('name', 'like', "%{$search}%");
                if ($digits !== '') {
                    $inner->orWhere('phone', 'like', "%{$digits}%");
                }
            });
        })
        ->when($request->status, fn ($q, $status) => $q->where('status', $status));

        if ($request->get('sort') === 'donation') {
            $query->orderByDesc('donation_total');
        } else {
            $query->orderByDesc('created_at');
        }

        $contacts = $query->limit(80)->get();

        $contacts->each(function ($c) {
            $c->donation_count = (int) $c->donation_count;
            $c->donation_total = (float) $c->donation_total;
            $c->donation_total_formatted = 'Rp ' . number_format($c->donation_total, 0, ',', '.');
            $c->last_donation_date_formatted = $c->last_donation_date
                ? \Illuminate\Support\Carbon::parse($c->last_donation_date)->format('d M Y')
                : null;
        });

        $statusCounts = [
            'all' => (clone $this->scopeContacts(Contact::query()))->count(),
            'donated' => (clone $this->scopeContacts(Contact::query()))->where('status', 'donated')->count(),
            'prospect' => (clone $this->scopeContacts(Contact::query()))->where('status', 'prospect')->count(),
            'contacted' => (clone $this->scopeContacts(Contact::query()))->where('status', 'contacted')->count(),
            'churned' => (clone $this->scopeContacts(Contact::query()))->where('status', 'churned')->count(),
        ];

        return view('mobile.contacts', compact('contacts', 'statusCounts'));
    }

    /**
     * Daftar program mobile.
     */
    public function programs(Request $request)
    {
        $programs = Program::where('is_active', true)
            ->with('campaignTags')
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', '%' . trim($search) . '%'))
            ->when($request->jenis === 'penggalangan', fn ($q) => $q->where(function ($sub) {
                $sub->whereNull('category')->orWhere('category', '')->orWhere('category', 'penggalangan');
            }))
            ->when($request->jenis === 'penyaluran', fn ($q) => $q->where('category', 'penyaluran'))
            ->get();

        $aggregates = \Illuminate\Support\Facades\DB::table('donation_items')
            ->join('donations', 'donations.id', '=', 'donation_items.donation_id')
            ->whereIn('donation_items.program_id', $programs->pluck('id'))
            ->when($request->from, fn ($q, $from) => $q->whereDate('donations.donation_date', '>=', $from))
            ->when($request->to, fn ($q, $to) => $q->whereDate('donations.donation_date', '<=', $to));

        $this->scopeDonations($aggregates, 'donations');

        $aggregates = $aggregates
            ->groupBy('donation_items.program_id')
            ->selectRaw('donation_items.program_id as program_id, COALESCE(SUM(donation_items.amount), 0) as total_collected, COUNT(DISTINCT donation_items.donation_id) as donation_count')
            ->get()
            ->keyBy('program_id');

        $programs->each(function ($p) use ($aggregates) {
            $row = $aggregates->get($p->id);
            $p->collected = $row ? (float) $row->total_collected : 0.0;
            $p->donation_count = $row ? (int) $row->donation_count : 0;
            $p->collected_formatted = 'Rp ' . number_format($p->collected, 0, ',', '.');
        });

        if ($request->get('sort') === 'donation') {
            $programs = $programs->sortByDesc('collected')->values();
        } else {
            $programs = $programs->sortByDesc('created_at')->values();
        }

        return view('mobile.programs', compact('programs'));
    }

    /**
     * Menu Lainnya: profil, menu manajemen (sesuai role).
     */
    public function more(IntegrationCheckService $integration)
    {
        $user = auth()->user();

        // Profil agen
        $profile = json_decode(Setting::get('agent_profile_' . $user->slug, '{}'), true);
        if (! is_array($profile)) {
            $profile = [];
        }

        // Status indikator API WA (SS/CC) milik pengguna saat ini.
        $statuses = $integration->forUsers([
            $user->id => [
                'ss' => trim((string) ($profile['api_ss'] ?? '')),
                'cc' => trim((string) ($profile['api_cc'] ?? '')),
            ],
        ]);
        $apiStatus = $statuses[$user->id] ?? [
            'ss' => IntegrationCheckService::STATUS_EMPTY,
            'cc' => IntegrationCheckService::STATUS_EMPTY,
        ];

        return view('mobile.more', compact('user', 'profile', 'apiStatus'));
    }

    /**
     * Profil Saya (mobile): foto & sambutan halaman publik.
     */
    public function profile(ProfileService $profiles)
    {
        $user = auth()->user();
        $profile = $profiles->data($user);

        return view('mobile.profile', compact('user', 'profile'));
    }

    /**
     * Simpan Profil Saya dari aplikasi mobile.
     */
    public function profileUpdate(Request $request, ProfileService $profiles)
    {
        $profiles->save(auth()->user(), $request);

        return redirect()->route('mo.profile')->with('success', 'Profil berhasil disimpan.');
    }

    /**
     * Cabang (admin).
     */
    public function branches()
    {
        $month = now()->month;
        $year = now()->year;

        $branches = Branch::with('supervisor')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => $this->branchStats($b, $month, $year));

        return view('mobile.branches', compact('branches'));
    }

    /**
     * Pengguna (admin: semua; supervisor: agen cabangnya).
     */
    public function users(Request $request, IntegrationCheckService $integration)
    {
        $actor = auth()->user();
        $profiles = Setting::where('key', 'like', 'agent_profile_%')->pluck('value', 'key');

        $from = $request->get('from');
        $to = $request->get('to');
        $sortDonation = $request->get('sort') === 'donation';
        $search = trim((string) $request->get('search', ''));
        $branchFilter = $actor->isAdmin() && $request->filled('branch_id') ? $request->get('branch_id') : null;

        $hasFilter = $search !== '' || $branchFilter !== null || $from || $to || $sortDonation;

        $query = User::with('branch')->visibleTo($actor);

        if ($search !== '') {
            $digits = preg_replace('/[^0-9]/', '', $search);
            $query->where(function ($inner) use ($search, $digits) {
                $inner->where('name', 'like', "%{$search}%");
                if ($digits !== '') {
                    $inner->orWhere('phone', 'like', "%{$digits}%");
                }
            });
        }

        if ($branchFilter !== null) {
            $query->where('branch_id', $branchFilter);
        }

        $users = $query->orderBy('role')->orderBy('name')->get();

        $aggregates = Donation::query()
            ->whereIn('agen_id', $users->pluck('id'))
            ->when($from, fn ($q, $value) => $q->whereDate('donation_date', '>=', $value))
            ->when($to, fn ($q, $value) => $q->whereDate('donation_date', '<=', $value))
            ->groupBy('agen_id')
            ->selectRaw('agen_id, COALESCE(SUM(amount), 0) as total, COUNT(*) as transactions, COUNT(DISTINCT contact_id) as donors')
            ->get()
            ->keyBy('agen_id');

        $users = $users->map(function ($u) use ($profiles, $aggregates) {
            $profile = json_decode($profiles->get('agent_profile_' . $u->slug, '{}'), true);
            $photo = is_array($profile) ? (string) ($profile['photo'] ?? '') : '';

            $row = $aggregates->get($u->id);
            $total = $row ? (float) $row->total : 0.0;
            $transactions = $row ? (int) $row->transactions : 0;
            $donors = $row ? (int) $row->donors : 0;

            return [
                'id' => $u->id,
                'name' => $u->name,
                'role_label' => $u->roleLabel(),
                'role' => $u->role,
                'branch' => $u->branch->name ?? '-',
                'is_active' => (bool) $u->is_active,
                'initial' => strtoupper(substr($u->name, 0, 1)),
                'photo_url' => $photo !== '' ? asset_photo_url($photo) : '',
                'donation_total' => $total,
                'donation_count' => $transactions,
                'donor_count' => $donors,
                'donation_total_formatted' => 'Rp ' . number_format($total, 0, ',', '.'),
                'donation_meta' => $transactions > 0
                    ? ('Dari ' . $transactions . ' transaksi - ' . $donors . ' Donatur')
                    : null,
                'api_ss_key' => is_array($profile) ? trim((string) ($profile['api_ss'] ?? '')) : '',
                'api_cc_key' => is_array($profile) ? trim((string) ($profile['api_cc'] ?? '')) : '',
            ];
        });

        if ($sortDonation) {
            $users = $users->sortByDesc('donation_total')->values();
        }

        $statusInput = [];
        foreach ($users as $u) {
            $statusInput[$u['id']] = ['ss' => $u['api_ss_key'], 'cc' => $u['api_cc_key']];
        }

        $statuses = $integration->forUsers($statusInput);

        $users = $users->map(function ($u) use ($statuses) {
            $u['api_ss'] = $statuses[$u['id']]['ss'] ?? IntegrationCheckService::STATUS_EMPTY;
            $u['api_cc'] = $statuses[$u['id']]['cc'] ?? IntegrationCheckService::STATUS_EMPTY;

            return $u;
        });

        $branches = $actor->isAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('mobile.users', compact('users', 'branches', 'hasFilter', 'sortDonation'));
    }

    /**
     * Detail donasi (JSON, untuk bottom sheet mobile).
     */
    public function donationDetail($id)
    {
        $donation = Donation::with(['branch', 'agen', 'contact', 'items.program', 'creator'])->find($id);

        if (! $donation) {
            return response()->json(['error' => 'Donasi tidak ditemukan.'], 404);
        }

        if (! $this->canAccessDonation($donation)) {
            return response()->json(['error' => 'Anda tidak memiliki izin untuk melihat donasi ini.'], 403);
        }

        $items = $donation->items->map(fn ($item) => [
            'category_label' => $item->program ? $item->program->category_label : ($item->program_category ?: '-'),
            'program_name' => $item->program->name ?? '-',
            'amount_formatted' => 'Rp ' . number_format((float) $item->amount, 0, ',', '.'),
        ])->all();

        return response()->json([
            'id' => $donation->id,
            'donation_date_formatted' => $donation->donation_date ? $donation->donation_date->format('d M Y') : '-',
            'branch' => $donation->branch->name ?? '-',
            'agen' => $donation->agen->name ?? '-',
            'contact' => $donation->contact_id ? ($donation->contact->name ?? '-') : '-',
            'contact_phone' => $donation->contact_id ? ($donation->contact->phone ?? '-') : '-',
            'donor_info' => $donation->donor_info,
            'items' => $items,
            'amount_formatted' => 'Rp ' . number_format((float) $donation->amount, 0, ',', '.'),
            'payment_method_label' => $this->paymentMethodLabel($donation->payment_method),
            'note' => $donation->note,
            'proof_url' => $donation->payment_proof ? asset_photo_url($donation->payment_proof) : null,
            'created_at_formatted' => $donation->created_at ? $donation->created_at->format('d M Y H:i') : '-',
            'creator' => $donation->creator->name ?? '-',
            'can_edit' => true,
            'edit_url' => route('mo.donation.edit', $donation->id),
        ]);
    }

    /**
     * Detail pengguna (JSON, untuk bottom sheet mobile).
     */
    public function userDetail($id)
    {
        $actor = auth()->user();
        $user = User::with('branch')->find($id);

        if (! $user) {
            return response()->json(['error' => 'Pengguna tidak ditemukan.'], 404);
        }

        if (! $user->isManageableBy($actor)) {
            return response()->json(['error' => 'Anda tidak memiliki izin untuk melihat pengguna ini.'], 403);
        }

        $profile = (new ProfileService())->data($user);
        $photo = $profile['photo'] ?? '';

        $donations = Donation::where('agen_id', $user->id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'role_label' => $user->roleLabel(),
            'branch' => $user->branch->name ?? '-',
            'is_active' => (bool) $user->is_active,
            'photo_url' => $photo !== '' ? asset_photo_url($photo) : '',
            'initial' => strtoupper(substr($user->name, 0, 1)),
            'donation_count' => $donations->count(),
            'donation_total_formatted' => 'Rp ' . number_format((float) $donations->sum('amount'), 0, ',', '.'),
            'public_url' => $user->slug ? route('public.agent', $user->slug) : null,
            'can_edit' => true,
            'edit_url' => route('mo.user.edit', $user->id),
        ]);
    }

    /**
     * Detail kontak (JSON, untuk bottom sheet mobile).
     */
    public function contactDetail($id)
    {
        $contact = Contact::with(['agen', 'branch'])->find($id);

        if (! $contact) {
            return response()->json(['error' => 'Kontak tidak ditemukan.'], 404);
        }

        if (! $this->canAccessContact($contact)) {
            return response()->json(['error' => 'Anda tidak memiliki izin untuk melihat kontak ini.'], 403);
        }

        $donationEntries = [];
        $donations = $contact->donations()
            ->with(['items.program', 'program'])
            ->orderByDesc('donation_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        foreach ($donations as $donation) {
            $date = $donation->donation_date ? $donation->donation_date->format('d/m/y') : '-';

            if ($donation->items->isNotEmpty()) {
                foreach ($donation->items as $item) {
                    $donationEntries[] = [
                        'date' => $date,
                        'category' => $item->program ? $item->program->category_label : ($item->program_category ?: '-'),
                        'amount_formatted' => 'Rp ' . number_format((float) $item->amount, 0, ',', '.'),
                        'program_name' => $item->program->name ?? '-',
                    ];
                }

                continue;
            }

            $donationEntries[] = [
                'date' => $date,
                'category' => $donation->program ? $donation->program->category_label : '-',
                'amount_formatted' => 'Rp ' . number_format((float) $donation->amount, 0, ',', '.'),
                'program_name' => $donation->program->name ?? '-',
            ];
        }

        return response()->json([
            'id' => $contact->id,
            'name' => $contact->name,
            'phone' => $contact->phone,
            'status' => $contact->status,
            'status_label' => $contact->statusLabel(),
            'branch' => $contact->branch->name ?? '-',
            'agen' => $contact->agen->name ?? '-',
            'notes' => $contact->notes,
            'donation_count' => $contact->donations()->count(),
            'donation_total_formatted' => 'Rp ' . number_format((float) $contact->donations()->sum('amount'), 0, ',', '.'),
            'donations' => $donationEntries,
            'can_edit' => true,
            'edit_url' => route('mo.contact.edit', $contact->id),
        ]);
    }

    /**
     * Pencarian kontak (JSON) untuk autocomplete form donation mobile.
     * Mencari berdasarkan nama dan nomor WA (abaikan +, -, spasi).
     */
    public function contactSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $digits = preg_replace('/[^0-9]/', '', $q);

        $query = Contact::query();
        $this->scopeContacts($query);

        if ($q !== '') {
            $query->where(function ($inner) use ($q, $digits) {
                $inner->where('name', 'like', "%{$q}%");
                if ($digits !== '') {
                    $inner->orWhere('phone', 'like', "%{$digits}%");
                }
            });
        }

        $contacts = $query->orderBy('name')->limit(30)->get();

        return response()->json([
            'contacts' => $contacts->map(function (Contact $contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'phone' => $contact->phone,
                    'label' => $contact->name . ($contact->phone ? ' (' . $contact->phone . ')' : ''),
                ];
            })->values(),
        ]);
    }

    protected function paymentMethodLabel(?string $method): string
    {
        return [
            'cash' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'e-wallet' => 'E-Wallet',
        ][$method] ?? '-';
    }

    /**
     * Cek apakah user berhak mengakses donasi (scoped per role).
     */
    protected function canAccessDonation(Donation $donation): bool
    {
        $user = auth()->user();

        if ($user->isAgen()) {
            return (int) $donation->agen_id === (int) $user->id;
        }

        if ($user->isSupervisor()) {
            return (int) $donation->branch_id === (int) $user->branch_id;
        }

        return true;
    }

    /**
     * Cek apakah user berhak mengakses kontak (scoped per role).
     */
    protected function canAccessContact(Contact $contact): bool
    {
        $user = auth()->user();

        if ($user->isAgen()) {
            return (int) $contact->agen_id === (int) $user->id;
        }

        if ($user->isSupervisor()) {
            return (int) $contact->branch_id === (int) $user->branch_id;
        }

        return true;
    }
}

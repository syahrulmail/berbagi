<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
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
    public function dashboard()
    {
        $user = auth()->user();
        $today = now()->toDateString();
        $month = now()->month;
        $year = now()->year;

        // Ringkasan donasi (scoped)
        $donationsQuery = Donation::query();
        $this->scopeDonations($donationsQuery);

        $todayTotal = (clone $donationsQuery)->where('donation_date', $today)->sum('amount');
        $monthTotal = (clone $donationsQuery)
            ->whereYear('donation_date', $year)
            ->whereMonth('donation_date', $month)
            ->sum('amount');

        $prevDate = now()->subMonth();
        $prevMonthTotal = (clone $donationsQuery)
            ->whereYear('donation_date', $prevDate->year)
            ->whereMonth('donation_date', $prevDate->month)
            ->sum('amount');

        $growthPercent = $prevMonthTotal > 0
            ? round((($monthTotal - $prevMonthTotal) / $prevMonthTotal) * 100, 1)
            : 0;

        // Rekap seluruh data tercatat (sesuai role)
        $totalRecorded = (clone $donationsQuery)->sum('amount');
        $totalTransactions = (clone $donationsQuery)->count();
        $totalDonors = (clone $donationsQuery)->whereNotNull('contact_id')->distinct()->count('contact_id');

        // Donatur bulan ini & hari ini
        $monthDonors = (clone $donationsQuery)
            ->whereYear('donation_date', $year)
            ->whereMonth('donation_date', $month)
            ->whereNotNull('contact_id')
            ->distinct()
            ->count('contact_id');

        $donorsToday = (clone $donationsQuery)
            ->where('donation_date', $today)
            ->whereNotNull('contact_id')
            ->distinct()
            ->count('contact_id');

        // Target: admin melihat total semua cabang aktif, selain itu target cabang sendiri
        if ($user->isAdmin()) {
            $totalTarget = Branch::where('is_active', true)->sum('target_amount');
        } elseif ($user->isSupervisor() && $user->branch) {
            $totalTarget = (float) $user->branch->target_amount;
        } else {
            $totalTarget = 0;
        }

        $overallProgress = $totalTarget > 0
            ? round(($monthTotal / $totalTarget) * 100, 1)
            : 0;

        // Tren bulan ini (harian)
        $monthlyTotals = (clone $donationsQuery)
            ->whereYear('donation_date', $year)
            ->whereMonth('donation_date', $month)
            ->selectRaw('DAY(donation_date) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $trend = [];
        $lastDay = now()->day;
        for ($day = 1; $day <= $lastDay; $day++) {
            $date = Carbon::create($year, $month, $day);
            $trend[] = [
                'label' => $date->format('d/m'),
                'is_weekend' => $date->isWeekend(),
                'value' => (int) ($monthlyTotals[$day] ?? 0),
            ];
        }
        $trendMax = max(1, max(array_column($trend, 'value')));

        // Donasi terbaru
        $recentDonations = (clone $donationsQuery)
            ->with(['branch', 'agen', 'contact', 'items.program'])
            ->orderByDesc('donation_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $recentDonations->each(function ($d) {
            $d->amount_formatted = 'Rp ' . number_format((float) $d->amount, 0, ',', '.');
            $d->date_formatted = $d->donation_date ? $d->donation_date->format('d M Y') : '-';
            $d->program_label = $d->items->isNotEmpty()
                ? $d->items->map(fn ($i) => $i->program->name ?? '')->filter()->implode(', ')
                : ($d->program->name ?? '-');
        });

        $totalPrograms = Program::where('is_active', true)->count();
        $totalContacts = (clone $this->scopeContacts(Contact::query()))->count();
        $donatedContacts = (clone $this->scopeContacts(Contact::query()))->where('status', 'donated')->count();

        // Statistik tambahan bulan ini
        $monthDonations = (clone $donationsQuery)
            ->whereYear('donation_date', $year)
            ->whereMonth('donation_date', $month)
            ->count();

        $hour = (int) now()->format('G');
        $greeting = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 19 ? 'Selamat Sore' : 'Selamat Malam'));

        $waNumber = Setting::get('wa_public_number', '');

        return view('mobile.home', compact(
            'user', 'greeting', 'todayTotal', 'monthTotal', 'growthPercent',
            'overallProgress', 'totalTarget', 'trend', 'trendMax',
            'recentDonations', 'totalPrograms', 'totalContacts',
            'donatedContacts', 'monthDonations', 'waNumber',
            'totalRecorded', 'totalTransactions', 'totalDonors', 'monthDonors', 'donorsToday'
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

        $donations = $query->orderByDesc('donations.donation_date')
            ->orderByDesc('donations.id')
            ->limit(50)
            ->get();

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
    public function more()
    {
        $user = auth()->user();

        // Profil agen
        $profile = json_decode(Setting::get('agent_profile_' . $user->slug, '{}'), true);
        if (! is_array($profile)) {
            $profile = [];
        }

        return view('mobile.more', compact('user', 'profile'));
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
     * Pengguna (admin).
     */
    public function users()
    {
        $users = User::with('branch')
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role_label' => $u->roleLabel(),
                    'role' => $u->role,
                    'branch' => $u->branch->name ?? '-',
                    'is_active' => (bool) $u->is_active,
                    'initial' => strtoupper(substr($u->name, 0, 1)),
                ];
            });

        return view('mobile.users', compact('users'));
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

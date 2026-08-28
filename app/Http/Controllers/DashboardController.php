<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        if ($user->isSupervisor()) {
            return $this->supervisorDashboard($user);
        }

        return $this->agenDashboard($user);
    }

    protected function adminDashboard()
    {
        $today = now()->toDateString();
        $month = now()->month;
        $year = now()->year;

        $todayTotal = Donation::where('donation_date', $today)->sum('amount');

        $monthTotal = Donation::whereYear('donation_date', $year)
            ->whereMonth('donation_date', $month)
            ->sum('amount');

        $prevMonthTotal = Donation::whereYear('donation_date', $year)
            ->whereMonth('donation_date', now()->subMonth()->month)
            ->sum('amount');

        $growthPercent = $prevMonthTotal > 0
            ? round((($monthTotal - $prevMonthTotal) / $prevMonthTotal) * 100, 1)
            : 0;

        $totalTarget = Branch::where('is_active', true)->sum('target_amount');

        $overallProgress = $totalTarget > 0
            ? round(($monthTotal / $totalTarget) * 100, 1)
            : 0;

        $branches = Branch::with('supervisor')->where('is_active', true)->get()
            ->map(function ($branch) use ($month, $year) {
                $collected = $branch->donations()
                    ->whereYear('donation_date', $year)
                    ->whereMonth('donation_date', $month)
                    ->sum('amount');

                $branch->collected = (float) $collected;
                $branch->progress = $branch->target_amount > 0
                    ? round(((float) $collected / (float) $branch->target_amount) * 100, 1)
                    : 0;

                return $branch;
            })
            ->sortByDesc('progress')
            ->values();

        $topPerformers = $branches->take(10);

        $totalDonationsToday = Donation::where('donation_date', $today)->count();

        // Tren 7 hari terakhir
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $trend[] = [
                'date' => Carbon::parse($date)->format('d M'),
                'total' => Donation::where('donation_date', $date)->sum('amount'),
            ];
        }

        $totalPrograms = Program::where('is_active', true)->count();
        $totalAgents = User::where('role', 'agen')->where('is_active', true)->count();

        // Funnel konversi WhatsApp
        $waClicksTotal = \App\Models\WaFollowup::count();
        $waClicksMonth = \App\Models\WaFollowup::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        $waSourceLabels = [
            'home' => 'Home',
            'program' => 'Detail Program',
            'agent' => 'Halaman Agen',
        ];

        $waClicksBySource = \App\Models\WaFollowup::select('source', DB::raw('count(*) as total'))
            ->groupBy('source')
            ->orderByDesc('total')
            ->get()
            ->map(function ($r) use ($waSourceLabels) {
                return [
                    'source' => $r->source,
                    'label'  => $waSourceLabels[$r->source] ?? ucfirst($r->source),
                    'total'  => (int) $r->total,
                ];
            })
            ->values()->all();

        $donationsMonthCount = Donation::whereYear('donation_date', $year)
            ->whereMonth('donation_date', $month)
            ->count();

        $waConversionRate = $waClicksMonth > 0
            ? round(($donationsMonthCount / $waClicksMonth) * 100, 1)
            : 0;

        $waTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $waTrend[] = [
                'date' => Carbon::parse($date)->format('d M'),
                'total' => \App\Models\WaFollowup::whereDate('created_at', $date)->count(),
            ];
        }

        return view('dashboard.admin', compact(
            'todayTotal',
            'monthTotal',
            'growthPercent',
            'totalTarget',
            'overallProgress',
            'branches',
            'topPerformers',
            'totalDonationsToday',
            'trend',
            'totalPrograms',
            'totalAgents',
            'waClicksTotal',
            'waClicksMonth',
            'waClicksBySource',
            'donationsMonthCount',
            'waConversionRate',
            'waTrend'
        ));
    }

    protected function supervisorDashboard(User $user)
    {
        $branch = $user->branch;
        $month = now()->month;
        $year = now()->year;

        $collected = 0;
        $target = 0;
        $agents = collect();

        if ($branch) {
            $collected = $branch->donations()
                ->whereYear('donation_date', $year)
                ->whereMonth('donation_date', $month)
                ->sum('amount');

            $target = (float) $branch->target_amount;

            $agents = User::where('role', 'agen')
                ->where('branch_id', $branch->id)
                ->with(['donations' => function ($q) use ($year, $month) {
                    $q->whereYear('donation_date', $year)->whereMonth('donation_date', $month);
                }])
                ->get()
                ->map(function ($agent) {
                    $agent->collected = $agent->donations->sum('amount');
                    return $agent;
                })
                ->sortByDesc('collected')
                ->values();
        }

        $progress = $target > 0 ? round(((float) $collected / $target) * 100, 1) : 0;

        $fuTotal = 0;
        if ($branch) {
            $agentIds = User::where('role', 'agen')
                ->where('branch_id', $branch->id)
                ->pluck('id');
            $fuTotal = \App\Models\WaFollowup::whereIn('agen_id', $agentIds)->count();
        }

        return view('dashboard.supervisor', compact('branch', 'collected', 'target', 'progress', 'agents', 'fuTotal'));
    }

    protected function agenDashboard(User $user)
    {
        $month = now()->month;
        $year = now()->year;

        $collected = Donation::where('agen_id', $user->id)
            ->whereYear('donation_date', $year)
            ->whereMonth('donation_date', $month)
            ->sum('amount');

        $totalContacts = $user->contacts()->count();
        $donatedContacts = $user->contacts()->where('status', 'donated')->count();

        $fuTotal = \App\Models\WaFollowup::where('agen_id', $user->id)->count();
        $fuMonth = \App\Models\WaFollowup::where('agen_id', $user->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        $recentFollowups = \App\Models\WaFollowup::where('agen_id', $user->id)
            ->with('program')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $recentDonations = Donation::where('agen_id', $user->id)
            ->with(['program', 'branch'])
            ->orderByDesc('donation_date')
            ->limit(10)
            ->get();

        return view('dashboard.agen', compact(
            'collected',
            'totalContacts',
            'donatedContacts',
            'fuTotal',
            'fuMonth',
            'recentFollowups',
            'recentDonations'
        ));
    }
}

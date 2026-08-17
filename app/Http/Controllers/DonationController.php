<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $query = Donation::with(['branch', 'agen', 'program', 'contact']);

        if (auth()->user()->isAgen()) {
            $query->where('agen_id', auth()->id());
        } elseif (auth()->user()->isSupervisor() && auth()->user()->branch_id) {
            $query->where('branch_id', auth()->user()->branch_id);
        }

        $query->when($request->from, function ($q, $from) {
            return $q->whereDate('donation_date', '>=', $from);
        })
        ->when($request->to, function ($q, $to) {
            return $q->whereDate('donation_date', '<=', $to);
        })
        ->when($request->branch_id, function ($q, $branchId) {
            return $q->where('branch_id', $branchId);
        })
        ->when($request->program_id, function ($q, $programId) {
            return $q->where('program_id', $programId);
        });

        $donations = $query->orderByDesc('donation_date')->orderByDesc('id')->paginate(15)->withQueryString();

        $totalAmount = (clone $query)->sum('amount');

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();

        return view('donations.index', compact('donations', 'totalAmount', 'branches', 'programs'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        $agents = $this->visibleAgents();
        $contacts = Contact::orderBy('name')->get();

        return view('donations.create', compact('branches', 'programs', 'agents', 'contacts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'donation_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'agen_id' => ['required', 'exists:users,id'],
            'program_id' => ['required', 'exists:programs,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'payment_method' => ['required', 'in:cash,transfer,qris,e-wallet'],
            'note' => ['nullable', 'string'],
        ]);

        if (auth()->user()->isAgen()) {
            $data['agen_id'] = auth()->id();
            $data['branch_id'] = auth()->user()->branch_id ?? $data['branch_id'];
        }

        $data['created_by'] = auth()->id();

        $donation = Donation::create($data);

        if ($donation->contact_id) {
            $donation->contact()->update(['status' => Contact::STATUS_DONATED]);
        }

        ActivityLog::record('donation.create', 'Mencatat donasi Rp ' . number_format((float) $donation->amount, 0, ',', '.'));

        return redirect()->route('donations.index')->with('success', 'Donasi berhasil dicatat.');
    }

    public function edit(Donation $donation)
    {
        $this->authorizeAccess($donation);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        $agents = $this->visibleAgents();
        $contacts = Contact::orderBy('name')->get();

        return view('donations.edit', compact('donation', 'branches', 'programs', 'agents', 'contacts'));
    }

    public function update(Request $request, Donation $donation)
    {
        $this->authorizeAccess($donation);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'donation_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'agen_id' => ['required', 'exists:users,id'],
            'program_id' => ['required', 'exists:programs,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'payment_method' => ['required', 'in:cash,transfer,qris,e-wallet'],
            'note' => ['nullable', 'string'],
        ]);

        $donation->update($data);

        ActivityLog::record('donation.update', 'Memperbarui donasi #' . $donation->id);

        return redirect()->route('donations.index')->with('success', 'Donasi berhasil diperbarui.');
    }

    public function destroy(Donation $donation)
    {
        $this->authorizeAccess($donation);

        ActivityLog::record('donation.delete', 'Menghapus donasi #' . $donation->id);
        $donation->delete();

        return redirect()->route('donations.index')->with('success', 'Donasi berhasil dihapus.');
    }

    protected function visibleAgents()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return User::whereIn('role', ['agen', 'supervisor'])->orderBy('name')->get();
        }

        if ($user->isSupervisor()) {
            return User::where('role', 'agen')
                ->where('branch_id', $user->branch_id)
                ->orderBy('name')
                ->get();
        }

        return User::where('id', $user->id)->get();
    }

    protected function authorizeAccess(Donation $donation): void
    {
        $user = auth()->user();

        if ($user->isAgen() && (int) $donation->agen_id !== (int) $user->id) {
            abort(403, 'Anda hanya dapat mengelola donasi milik sendiri.');
        }

        if ($user->isSupervisor() && (int) $donation->branch_id !== (int) $user->branch_id) {
            abort(403, 'Anda hanya dapat mengelola donasi di cabang Anda.');
        }
    }
}

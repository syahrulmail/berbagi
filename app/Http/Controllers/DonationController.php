<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $query = Donation::with(['branch', 'agen', 'program', 'contact', 'items.program'])->select('donations.*');

        if (auth()->user()->isAgen()) {
            $query->where('agen_id', auth()->id());
        } elseif (auth()->user()->isSupervisor() && auth()->user()->branch_id) {
            $query->where('branch_id', auth()->user()->branch_id);
        }

        $query->leftJoin('programs as donasi_program', 'donations.program_id', '=', 'donasi_program.id');
        $query->leftJoin('contacts as donasi_kontak', 'donations.contact_id', '=', 'donasi_kontak.id');

        $query->when($request->from, function ($q, $from) {
            return $q->whereDate('donations.donation_date', '>=', $from);
        })
        ->when($request->to, function ($q, $to) {
            return $q->whereDate('donations.donation_date', '<=', $to);
        })
        ->when($request->branch_id, function ($q, $branchId) {
            return $q->where('donations.branch_id', $branchId);
        })
        ->when($request->search, function ($q, $search) {
            $search = trim($search);
            $digits = preg_replace('/\D/', '', $search);
            $phoneVariant = null;

            if (strlen($digits) >= 4) {
                if (strpos($digits, '0') === 0) {
                    $phoneVariant = '62' . substr($digits, 1);
                } elseif (strpos($digits, '8') === 0) {
                    $phoneVariant = '62' . $digits;
                }
            }

            return $q->where(function ($inner) use ($search, $digits, $phoneVariant) {
                $inner->where('donasi_kontak.name', 'like', "%{$search}%")
                    ->orWhereExists(function ($sub) use ($search) {
                        $sub->selectRaw(1)
                            ->from('donation_items')
                            ->join('programs', 'donation_items.program_id', '=', 'programs.id')
                            ->whereColumn('donation_items.donation_id', 'donations.id')
                            ->where('programs.name', 'like', "%{$search}%");
                    });

                if (strlen($digits) >= 4) {
                    $inner->orWhere('donasi_kontak.phone', 'like', "%{$digits}%");

                    if ($phoneVariant) {
                        $inner->orWhere('donasi_kontak.phone', 'like', "%{$phoneVariant}%");
                    }
                }
            });
        });

        $sortable = [
            'date'     => 'donations.donation_date',
            'branch'   => 'branches.name',
            'agent'    => 'donasi_agen.name',
            'category' => 'donasi_program.program_category',
            'program'  => 'donasi_program.name',
            'donatur'  => 'donasi_kontak.name',
            'amount'   => 'donations.amount',
        ];

        $sort = $request->input('sort', 'date');
        if (!array_key_exists($sort, $sortable)) {
            $sort = 'date';
        }
        $dir = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sort === 'branch') {
            $query->leftJoin('branches', 'donations.branch_id', '=', 'branches.id');
        } elseif ($sort === 'agent') {
            $query->leftJoin('users as donasi_agen', 'donations.agen_id', '=', 'donasi_agen.id');
        }

        $query->orderBy($sortable[$sort], $dir);
        if ($sort !== 'date') {
            $query->orderByDesc('donations.donation_date');
        }
        $query->orderByDesc('donations.id');

        $donations = $query->paginate(15)->withQueryString();

        $totalAmount = (clone $query)->sum('donations.amount');

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
            'items' => ['required', 'array', 'min:1'],
            'items.*.program_id' => ['required', 'exists:programs,id'],
            'items.*.amount' => ['required', 'numeric', 'min:1'],
            'items.*.program_category' => ['nullable', 'string'],
            'donation_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'agen_id' => ['required', 'exists:users,id'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'donor_info' => ['nullable', 'string'],
            'payment_method' => ['required', 'in:cash,transfer,qris,e-wallet'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'note' => ['nullable', 'string'],
        ]);

        unset($data['payment_proof']);

        if (auth()->user()->isAgen()) {
            $data['agen_id'] = auth()->id();
            $data['branch_id'] = auth()->user()->branch_id ?? $data['branch_id'];
        }

        $this->normalizeBranch($data);

        if ($request->hasFile('payment_proof')) {
            $data['payment_proof'] = $request->file('payment_proof')->store('donation-proofs', 'public');
        }

        $data['created_by'] = auth()->id();

        $items = $this->normalizeItems($request->input('items'));
        $data['amount'] = round(array_sum(array_column($items, 'amount')), 2);
        $data['program_id'] = $items[0]['program_id'];

        $donation = Donation::create($data);

        foreach ($items as $item) {
            $donation->items()->create([
                'program_id' => $item['program_id'],
                'program_category' => $item['program_category'] ?? null,
                'amount' => $item['amount'],
            ]);
        }

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

    /**
     * Rincian donasi untuk modal (format JSON).
     */
    public function detail(Donation $donation)
    {
        $this->authorizeAccess($donation);

        $donation->load(['branch', 'agen', 'contact', 'items.program', 'creator']);

        return response()->json([
            'id' => $donation->id,
            'donation_date_formatted' => $donation->donation_date->format('d M Y'),
            'branch' => $donation->branch->name ?? '-',
            'agen' => $donation->agen->name ?? '-',
            'contact' => $donation->contact_id ? ($donation->contact->name ?? '-') : '-',
            'contact_phone' => $donation->contact_id ? ($donation->contact->phone ?? '-') : '-',
            'donor_info' => $donation->donor_info,
            'items' => $donation->items->map(function ($item) {
                return [
                    'category_label' => $item->program ? $item->program->category_label : ($item->program_category ?: '-'),
                    'program_name' => $item->program->name ?? '-',
                    'amount_formatted' => 'Rp ' . number_format((float) $item->amount, 0, ',', '.'),
                ];
            }),
            'amount_formatted' => 'Rp ' . number_format((float) $donation->amount, 0, ',', '.'),
            'payment_method_label' => $this->paymentMethodLabel($donation->payment_method),
            'note' => $donation->note,
            'proof_url' => $donation->payment_proof ? asset_photo_url($donation->payment_proof) : null,
            'created_at_formatted' => $donation->created_at ? $donation->created_at->format('d M Y H:i') : '-',
            'creator' => $donation->creator->name ?? '-',
        ]);
    }

    /**
     * Field form edit donasi untuk dimuat di dalam modal rincian (format JSON).
     */
    public function editFields(Donation $donation)
    {
        $this->authorizeAccess($donation);

        $donation->load('items.program');

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        $agents = $this->visibleAgents();
        $contacts = Contact::orderBy('name')->get();

        $html = view('donations._edit_fields', compact('donation', 'branches', 'programs', 'agents', 'contacts'))->render();

        return response()->json(['html' => $html]);
    }

    public function update(Request $request, Donation $donation)
    {
        $this->authorizeAccess($donation);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.program_id' => ['required', 'exists:programs,id'],
            'items.*.amount' => ['required', 'numeric', 'min:1'],
            'items.*.program_category' => ['nullable', 'string'],
            'donation_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'agen_id' => ['required', 'exists:users,id'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'donor_info' => ['nullable', 'string'],
            'payment_method' => ['required', 'in:cash,transfer,qris,e-wallet'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'note' => ['nullable', 'string'],
        ]);

        unset($data['payment_proof']);

        $this->normalizeBranch($data);

        $proofPath = $donation->payment_proof;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')->store('donation-proofs', 'public');
        } elseif ($request->boolean('remove_payment_proof')) {
            $proofPath = null;
        }

        if ($proofPath !== $donation->payment_proof) {
            if ($donation->payment_proof) {
                Storage::disk('public')->delete($donation->payment_proof);
            }
            $data['payment_proof'] = $proofPath;
        }

        $items = $this->normalizeItems($request->input('items'));
        $data['amount'] = round(array_sum(array_column($items, 'amount')), 2);
        $data['program_id'] = $items[0]['program_id'];

        $donation->update($data);
        $donation->items()->delete();

        foreach ($items as $item) {
            $donation->items()->create([
                'program_id' => $item['program_id'],
                'program_category' => $item['program_category'] ?? null,
                'amount' => $item['amount'],
            ]);
        }

        ActivityLog::record('donation.update', 'Memperbarui donasi #' . $donation->id);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Donasi berhasil diperbarui.',
            ]);
        }

        return redirect()->route('donations.index')->with('success', 'Donasi berhasil diperbarui.');
    }

    public function destroy(Donation $donation)
    {
        $this->authorizeAccess($donation);

        if ($donation->payment_proof) {
            Storage::disk('public')->delete($donation->payment_proof);
        }

        ActivityLog::record('donation.delete', 'Menghapus donasi #' . $donation->id);
        $donation->delete();

        return redirect()->route('donations.index')->with('success', 'Donasi berhasil dihapus.');
    }

    /**
     * Pastikan cabang donasi selalu konsisten dengan cabang agen-nya,
     * agar dashboard per-cabang tidak salah hitung.
     *
     * @param  array  $data
     */
    protected function normalizeBranch(array &$data)
    {
        if (empty($data['agen_id'])) {
            return;
        }

        $agen = User::find($data['agen_id']);

        if ($agen && $agen->branch_id && (int) $data['branch_id'] !== (int) $agen->branch_id) {
            $data['branch_id'] = $agen->branch_id;
            ActivityLog::record('donation.branch_sync', 'Cabang donasi disesuaikan ke cabang agen ' . $agen->name);
        }
    }

    /**
     * Bersihkan daftar item program donasi: buang baris kosong dan
     * pastikan minimal satu item valid (sudah dijamin validasi).
     *
     * @param  array  $items
     * @return array
     */
    protected function paymentMethodLabel($method)
    {
        $labels = [
            'cash' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'e-wallet' => 'E-Wallet',
        ];

        return $labels[$method] ?? ($method ?: '-');
    }

    protected function normalizeItems($items)
    {
        if (!is_array($items)) {
            $items = [];
        }

        $items = array_values(array_filter($items, function ($item) {
            return is_array($item)
                && !empty($item['program_id'])
                && (float) ($item['amount'] ?? 0) > 0;
        }));

        if (count($items) === 0) {
            $items = [[
                'program_id' => null,
                'program_category' => null,
                'amount' => 0,
            ]];
        }

        return $items;
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

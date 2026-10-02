<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use App\Services\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);

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
        $downloadBranches = $this->visibleBranches();

        return view('donations.index', compact('donations', 'totalAmount', 'branches', 'programs', 'downloadBranches'));
    }

    /**
     * Unduh data donasi (XLSX) sesuai filter cabang & periode.
     */
    public function download(Request $request)
    {
        $request->validate([
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', Rule::when($request->filled('from'), ['after_or_equal:from'])],
        ]);

        $allowedBranchIds = $this->visibleBranches()->pluck('id')->all();
        $requestedBranchIds = array_values(array_filter((array) $request->input('branch_ids', [])));

        if (array_diff($requestedBranchIds, $allowedBranchIds)) {
            abort(403, 'Cabang yang dipilih tidak sesuai dengan akses Anda.');
        }

        $donations = $this->filteredQuery($request)
            ->orderBy('donations.donation_date')
            ->orderBy('donations.id')
            ->get();

        $headers = [
            'Cabang',
            'Agent',
            'Tanggal Donasi',
            'Kontak Donatur',
            'Info Donatur',
            'Kategori Program',
            'Program',
            'Nominal',
            'Total Donasi',
            'Tanggal Pembayaran',
            'Metode Pembayaran',
            'Bukti Pembayaran',
            'Catatan',
        ];

        $writer = (new XlsxWriter('Donasi'))
            ->setColumnWidths([
                1 => 20, 2 => 20, 3 => 15, 4 => 24, 5 => 28,
                6 => 20, 7 => 30, 8 => 16, 9 => 16, 10 => 18,
                11 => 18, 12 => 36, 13 => 28,
            ])
            ->addRow($headers, XlsxWriter::STYLE_HEADER);

        foreach ($donations as $donation) {
            [$categories, $programs, $amounts] = $this->exportItems($donation);

            if (count($amounts) === 1) {
                $nominalCell = ['value' => $amounts[0], 'style' => XlsxWriter::STYLE_NUMBER];
            } else {
                $nominalCell = [
                    'value' => $this->formatAmounts($amounts),
                    'style' => XlsxWriter::STYLE_WRAP,
                ];
            }

            $wrap = XlsxWriter::STYLE_WRAP;

            $writer->addRow([
                ['value' => $donation->branch->name ?? '-', 'style' => $wrap],
                ['value' => $donation->agen->name ?? '-', 'style' => $wrap],
                ['value' => $donation->donation_date ? $donation->donation_date->format('d/m/Y') : '-', 'style' => $wrap],
                ['value' => $this->contactLabel($donation->contact), 'style' => $wrap],
                ['value' => $donation->donor_info ?: '-', 'style' => $wrap],
                ['value' => $categories ? implode("\n", $categories) : '-', 'style' => $wrap],
                ['value' => $programs ? implode("\n", $programs) : '-', 'style' => $wrap],
                $nominalCell,
                ['value' => (float) $donation->amount, 'style' => XlsxWriter::STYLE_NUMBER],
                ['value' => $this->paymentDateLabel($donation), 'style' => $wrap],
                ['value' => $this->paymentMethodLabel($donation->payment_method), 'style' => $wrap],
                ['value' => $donation->payment_proof ? asset_photo_url($donation->payment_proof) : '-', 'style' => $wrap],
                ['value' => $donation->note ?: '-', 'style' => $wrap],
            ]);
        }

        $path = tempnam(sys_get_temp_dir(), 'donasi_');

        if ($path === false) {
            abort(500, 'Gagal menyiapkan berkas unduhan.');
        }

        $writer->save($path);

        ActivityLog::record('donation.download', 'Mengunduh data donasi (' . $donations->count() . ' baris)');

        $filename = 'donasi-' . now()->format('Ymd-His') . '.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Bangun query donasi yang sudah difilter (role, periode, cabang, pencarian).
     * Dipakai bersama oleh index() dan download() agar konsisten.
     */
    protected function filteredQuery(Request $request)
    {
        $query = Donation::with(['branch', 'agen', 'program', 'contact', 'items.program'])->select('donations.*');

        if (auth()->user()->isAgen()) {
            $query->where('donations.agen_id', auth()->id());
        } elseif (auth()->user()->isSupervisor() && auth()->user()->branch_id) {
            $query->where('donations.branch_id', auth()->user()->branch_id);
        }

        $query->leftJoin('programs as donasi_program', 'donations.program_id', '=', 'donasi_program.id');
        $query->leftJoin('contacts as donasi_kontak', 'donations.contact_id', '=', 'donasi_kontak.id');

        $branchIds = array_values(array_filter((array) $request->input('branch_ids', [])));

        $query->when($request->from, function ($q, $from) {
            return $q->whereDate('donations.donation_date', '>=', $from);
        })
        ->when($request->to, function ($q, $to) {
            return $q->whereDate('donations.donation_date', '<=', $to);
        })
        ->when($request->branch_id, function ($q, $branchId) {
            return $q->where('donations.branch_id', $branchId);
        })
        ->when(count($branchIds), function ($q) use ($branchIds) {
            return $q->whereIn('donations.branch_id', $branchIds);
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

        return $query;
    }

    /**
     * Daftar cabang yang boleh diakses pengguna saat ini.
     * Admin melihat semua cabang, supervisor & agen hanya cabangnya sendiri.
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

    /**
     * Ambil daftar kategori, program, dan nominal donasi untuk ekspor.
     * Donasi lama tanpa item memakai relasi program langsung.
     *
     * @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, float>}
     */
    protected function exportItems(Donation $donation): array
    {
        $items = $donation->items;

        if ($items->isEmpty()) {
            $program = $donation->program;

            return [
                [$program ? ($program->category_label ?: '-') : '-'],
                [$program->name ?? '-'],
                [(float) $donation->amount],
            ];
        }

        $categories = [];
        $programs = [];
        $amounts = [];

        foreach ($items as $item) {
            $program = $item->program;

            $categories[] = $program ? ($program->category_label ?: '-') : ($item->program_category ?: '-');
            $programs[] = $program->name ?? '-';
            $amounts[] = (float) $item->amount;
        }

        return [$categories, $programs, $amounts];
    }

    protected function formatAmounts(array $amounts): string
    {
        return implode("\n", array_map(function ($value) {
            return number_format((float) $value, 0, ',', '.');
        }, $amounts));
    }

    protected function contactLabel(?Contact $contact): string
    {
        if (!$contact) {
            return '-';
        }

        return $contact->phone ? $contact->name . ' (' . $contact->phone . ')' : $contact->name;
    }

    /**
     * Label tanggal pembayaran. Jika belum diisi, pakai waktu pencatatan.
     */
    protected function paymentDateLabel(Donation $donation): string
    {
        if ($donation->payment_date) {
            return $donation->payment_date->format('d/m/Y');
        }

        return $donation->created_at ? $donation->created_at->format('d/m/Y H:i') : '-';
    }

    public function create()
    {
        $branches = $this->visibleBranches();
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
            'payment_date' => ['nullable', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'agen_id' => ['required', 'exists:users,id'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'donor_info' => ['nullable', 'string'],
            'payment_method' => ['required', 'in:cash,transfer,qris,e-wallet'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'note' => ['nullable', 'string'],
        ]);

        unset($data['payment_proof']);

        $data = $this->enforceRoleAssignment($data);

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
            'payment_date_formatted' => $donation->payment_date ? $donation->payment_date->format('d M Y') : '-',
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
            'payment_date' => ['nullable', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'agen_id' => ['required', 'exists:users,id'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'donor_info' => ['nullable', 'string'],
            'payment_method' => ['required', 'in:cash,transfer,qris,e-wallet'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'note' => ['nullable', 'string'],
        ]);

        unset($data['payment_proof']);

        $data = $this->enforceRoleAssignment($data);

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

    /**
     * Paksa cabang/agent sesuai role agar tidak bisa diubah lewat request manual.
     * Agen selalu terisi dirinya dan cabangnya; supervisor selalu cabangnya.
     */
    protected function enforceRoleAssignment(array $data): array
    {
        $user = auth()->user();

        if ($user->isAgen()) {
            $data['agen_id'] = $user->id;
            $data['branch_id'] = $user->branch_id ?? ($data['branch_id'] ?? null);

            return $data;
        }

        if ($user->isSupervisor()) {
            $allowedAgentIds = $this->visibleAgents()
                ->pluck('id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->all();

            if (! in_array((int) ($data['agen_id'] ?? 0), $allowedAgentIds, true)) {
                throw ValidationException::withMessages([
                    'agen_id' => 'Agent yang dipilih tidak berada di cabang Anda.',
                ]);
            }

            $data['branch_id'] = $user->branch_id;
        }

        return $data;
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

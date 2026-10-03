<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactImportService;
use App\Services\XlsxWriter;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    protected $importService;

    public function __construct(ContactImportService $importService)
    {
        $this->importService = $importService;
    }
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);

        $sort = $request->input('sort', 'created_at');
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        switch ($sort) {
            case 'name':
                $query->orderBy('contacts.name', $dir)->orderBy('contacts.id');
                break;
            case 'status':
                $query->orderBy('contacts.status', $dir)->orderBy('contacts.id');
                break;
            case 'agen':
                $query->orderBy(
                    User::select('name')->whereColumn('users.id', 'contacts.agen_id'),
                    $dir
                )->orderBy('contacts.id');
                break;
            case 'donation':
                $query->orderBy('total_donation', $dir)->orderBy('contacts.id');
                break;
            default:
                $sort = 'created_at';
                $query->orderByDesc('contacts.created_at');
        }

        $contacts = $query->paginate(15)->withQueryString();

        $agents = $this->visibleAgents();

        return view('contacts.index', compact('contacts', 'agents'));
    }

    /**
     * Bangun query kontak yang sudah difilter (role, status, pencarian).
     * Dipakai bersama oleh index() dan download() agar konsisten.
     */
    protected function filteredQuery(Request $request)
    {
        $query = Contact::with(['agen', 'branch'])
            ->withSum('donations as total_donation', 'amount');

        if (auth()->user()->isAgen()) {
            $query->where('agen_id', auth()->id());
        } elseif (auth()->user()->isSupervisor() && auth()->user()->branch_id) {
            $query->where('branch_id', auth()->user()->branch_id);
        }

        $query->when($request->status, function ($q, $status) {
            return $q->where('status', $status);
        })
        ->when($request->search, function ($q, $search) {
            return $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        });

        return $query;
    }

    /**
     * Unduh data kontak (XLSX) sesuai akses role dan filter yang aktif.
     */
    public function download(Request $request)
    {
        $contacts = $this->filteredQuery($request)
            ->withCount('donations')
            ->orderBy('name')
            ->get();

        $headers = [
            'Nama',
            'No. WhatsApp',
            'Status',
            'Agen',
            'Cabang',
            'Total Donasi',
            'Jumlah Donasi',
            'Catatan',
            'Dibuat Pada',
        ];

        $writer = (new XlsxWriter('Kontak'))
            ->setColumnWidths([
                1 => 28, 2 => 20, 3 => 16, 4 => 22, 5 => 22,
                6 => 16, 7 => 14, 8 => 36, 9 => 20,
            ])
            ->addRow($headers, XlsxWriter::STYLE_HEADER);

        foreach ($contacts as $contact) {
            $writer->addRow([
                ['value' => $contact->name, 'style' => XlsxWriter::STYLE_WRAP],
                ['value' => $contact->phone ?: '-', 'style' => XlsxWriter::STYLE_WRAP],
                ['value' => $contact->statusLabel(), 'style' => XlsxWriter::STYLE_WRAP],
                ['value' => $contact->agen->name ?? '-', 'style' => XlsxWriter::STYLE_WRAP],
                ['value' => $contact->branch->name ?? '-', 'style' => XlsxWriter::STYLE_WRAP],
                ['value' => (float) ($contact->total_donation ?? 0), 'style' => XlsxWriter::STYLE_NUMBER],
                ['value' => (int) $contact->donations_count, 'style' => XlsxWriter::STYLE_NUMBER],
                ['value' => $contact->notes ?: '-', 'style' => XlsxWriter::STYLE_WRAP],
                ['value' => $contact->created_at ? $contact->created_at->format('d/m/Y H:i') : '-', 'style' => XlsxWriter::STYLE_WRAP],
            ]);
        }

        $path = tempnam(sys_get_temp_dir(), 'kontak_');

        if ($path === false) {
            abort(500, 'Gagal menyiapkan berkas unduhan.');
        }

        $writer->save($path);

        ActivityLog::record('contact.download', 'Mengunduh data kontak (' . $contacts->count() . ' baris)');

        $filename = 'kontak-' . now()->format('Ymd-His') . '.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function create()
    {
        $agents = $this->visibleAgents();
        $branches = $this->visibleBranches();

        return view('contacts.create', compact('agents', 'branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:prospect,contacted,donated,churned'],
            'agen_id' => ['nullable', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $normalized = $this->importService->normalizePhone($data['phone']);
        if ($normalized === null) {
            return back()->withErrors(['phone' => 'Format No. WhatsApp tidak valid. Gunakan 10-15 digit angka (contoh: 62812xxxxxxx atau 0812xxxxxxx).'])->withInput();
        }
        $data['phone'] = $normalized;

        $map = $this->importService->normalizedPhoneMap();
        if (isset($map[$normalized])) {
            return back()->withErrors(['phone' => "Nomor WhatsApp sudah terdaftar atas nama '{$map[$normalized]['name']}'."])->withInput();
        }

        if (auth()->user()->isSupervisor()) {
            $data['branch_id'] = auth()->user()->branch_id;
        }

        if (!auth()->user()->isAgen() && !empty($data['agen_id'])) {
            $agent = User::find($data['agen_id']);
            if ($agent) {
                if (!empty($data['branch_id']) && (int) $agent->branch_id !== (int) $data['branch_id']) {
                    return back()->withErrors(['agen_id' => 'Agent tidak terdaftar di Cabang yang dipilih.'])->withInput();
                }
                if (empty($data['branch_id'])) {
                    $data['branch_id'] = $agent->branch_id;
                }
            }
        }

        if (auth()->user()->isAgen()) {
            $data['agen_id'] = auth()->id();
            $data['branch_id'] = auth()->user()->branch_id;
        }

        $contact = Contact::create($data);

        ActivityLog::record('contact.create', 'Membuat kontak ' . $contact->name);

        return redirect()->route('contacts.index')->with('success', 'Kontak berhasil ditambahkan.');
    }

    public function storeQuick(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'status' => ['nullable', 'in:prospect,contacted,donated,churned'],
            'agen_id' => ['nullable', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $normalized = $this->importService->normalizePhone($data['phone']);
        if ($normalized === null) {
            return response()->json([
                'success' => false,
                'message' => 'Format No. WhatsApp tidak valid. Gunakan 10-15 digit angka (contoh: 62812xxxxxxx atau 0812xxxxxxx).',
            ], 422);
        }
        $data['phone'] = $normalized;

        $map = $this->importService->normalizedPhoneMap();
        if (isset($map[$normalized])) {
            return response()->json([
                'success' => false,
                'message' => "Nomor WhatsApp sudah terdaftar atas nama '{$map[$normalized]['name']}'.",
            ], 422);
        }

        if (!auth()->user()->isAgen() && !empty($data['agen_id'])) {
            $agent = User::find($data['agen_id']);
            if ($agent) {
                if (!empty($data['branch_id']) && (int) $agent->branch_id !== (int) $data['branch_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Agent tidak terdaftar di Cabang yang dipilih.',
                    ], 422);
                }
                if (empty($data['branch_id'])) {
                    $data['branch_id'] = $agent->branch_id;
                }
            }
        }

        if (auth()->user()->isAgen()) {
            $data['agen_id'] = auth()->id();
            $data['branch_id'] = auth()->user()->branch_id;
        }

        $contact = Contact::create($data);

        ActivityLog::record('contact.create', 'Membuat kontak ' . $contact->name);

        return response()->json([
            'success' => true,
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
            ],
        ]);
    }

    public function edit(Contact $contact)
    {
        $this->authorizeAccess($contact);

        $agents = $this->visibleAgents();

        return view('contacts.edit', compact('contact', 'agents'));
    }

    public function update(Request $request, Contact $contact)
    {
        $this->authorizeAccess($contact);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:prospect,contacted,donated,churned'],
            'agen_id' => ['nullable', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $normalized = $this->importService->normalizePhone($data['phone']);
        if ($normalized === null) {
            return back()->withErrors(['phone' => 'Format No. WhatsApp tidak valid. Gunakan 10-15 digit angka (contoh: 62812xxxxxxx atau 0812xxxxxxx).'])->withInput();
        }
        $data['phone'] = $normalized;

        $map = $this->importService->normalizedPhoneMap();
        if (isset($map[$normalized]) && (int) $map[$normalized]['id'] !== (int) $contact->id) {
            return back()->withErrors(['phone' => "Nomor WhatsApp sudah terdaftar atas nama '{$map[$normalized]['name']}'."])->withInput();
        }

        if (!auth()->user()->isAgen() && !empty($data['agen_id'])) {
            $agent = User::find($data['agen_id']);
            if ($agent) {
                if (!empty($data['branch_id']) && (int) $agent->branch_id !== (int) $data['branch_id']) {
                    return back()->withErrors(['agen_id' => 'Agent tidak terdaftar di Cabang yang dipilih.'])->withInput();
                }
                if (empty($data['branch_id'])) {
                    $data['branch_id'] = $agent->branch_id;
                }
            }
        }

        if (auth()->user()->isAgen()) {
            $data['agen_id'] = auth()->id();
            $data['branch_id'] = auth()->user()->branch_id;
        }

        $contact->update($data);

        ActivityLog::record('contact.update', 'Memperbarui kontak ' . $contact->name);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Kontak berhasil diperbarui.',
            ]);
        }

        return redirect()->route('contacts.index')->with('success', 'Kontak berhasil diperbarui.');
    }

    /**
     * Rincian kontak untuk modal (format JSON).
     */
    public function detail(Contact $contact)
    {
        $this->authorizeAccess($contact);

        $contact->load(['agen', 'branch']);

        $donations = $contact->donations()
            ->with(['items.program', 'program'])
            ->orderByDesc('donation_date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($donation) {
                $items = $donation->items->map(function ($item) {
                    return [
                        'category_label' => $item->program ? $item->program->category_label : ($item->program_category ?: '-'),
                        'program_name' => $item->program->name ?? '-',
                    ];
                });

                if ($items->isEmpty() && $donation->program) {
                    $items = collect([[
                        'category_label' => $donation->program->category_label ?: '-',
                        'program_name' => $donation->program->name,
                    ]]);
                }

                return [
                    'id' => $donation->id,
                    'date_formatted' => $donation->donation_date ? $donation->donation_date->format('d M Y') : '-',
                    'items' => $items->values(),
                    'amount_formatted' => 'Rp ' . number_format((float) $donation->amount, 0, ',', '.'),
                ];
            })
            ->values();

        return response()->json([
            'id' => $contact->id,
            'name' => $contact->name,
            'phone' => $contact->phone,
            'status' => $contact->status,
            'status_label' => $contact->statusLabel(),
            'status_color' => $this->statusColor($contact->status),
            'agen' => $contact->agen->name ?? '-',
            'branch' => $contact->branch->name ?? '-',
            'notes' => $contact->notes,
            'donation_count' => $contact->donations()->count(),
            'donation_total_formatted' => 'Rp ' . number_format((float) $contact->donations()->sum('amount'), 0, ',', '.'),
            'donations' => $donations,
            'created_at_formatted' => $contact->created_at ? $contact->created_at->format('d M Y H:i') : '-',
            'updated_at_formatted' => $contact->updated_at ? $contact->updated_at->format('d M Y H:i') : '-',
        ]);
    }

    /**
     * Field form edit kontak untuk dimuat di dalam modal rincian (format JSON).
     */
    public function editFields(Contact $contact)
    {
        $this->authorizeAccess($contact);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $agents = $this->visibleAgents();

        $html = view('contacts._edit_fields', compact('contact', 'branches', 'agents'))->render();

        return response()->json(['html' => $html]);
    }

    protected function statusColor(string $status): string
    {
        $colors = [
            'prospect' => 'badge-blue',
            'contacted' => 'badge-orange',
            'donated' => 'badge-green',
            'churned' => 'badge-red',
        ];

        return $colors[$status] ?? 'badge-gray';
    }

    public function destroy(Contact $contact)
    {
        $this->authorizeAccess($contact);

        ActivityLog::record('contact.delete', 'Menghapus kontak ' . $contact->name);
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Kontak berhasil dihapus.');
    }

    public function storePaste(Request $request)
    {
        $request->validate([
            'paste_lines' => ['required', 'string'],
        ]);

        try {
            $rows = $this->importService->parsePaste($request->input('paste_lines'));
        } catch (\Throwable $e) {
            return back()->withErrors(['import' => $e->getMessage()])->withInput();
        }

        if (empty($rows)) {
            return back()->withErrors(['import' => 'Tidak ada baris data yang dapat diproses.'])->withInput();
        }

        $result = $this->importService->processRows($rows);

        if (!empty($result['errors'])) {
            $errors = array_merge(
                ['Tempel kontak dibatalkan. Perbaiki data berikut lalu coba lagi:'],
                $result['errors']
            );
            return back()->withErrors(['import' => $errors])->withInput();
        }

        $created = $this->importService->createContacts($result['contacts'], auth()->user());

        ActivityLog::record('contact.import.paste', 'Menambahkan ' . $created . ' kontak melalui Tempel');

        return redirect()->route('contacts.index')->with('success', $created . ' kontak berhasil ditambahkan melalui Tempel.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => ['required', 'file'],
        ]);

        $file = $request->file('import_file');
        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));

        if (!in_array($ext, ['xls', 'xlsx', 'csv', 'txt'])) {
            return back()->withErrors(['import' => 'Ekstensi file tidak didukung. Gunakan file .xls, .xlsx, .csv, atau .txt.'])->withInput();
        }

        try {
            $rows = $this->importService->parseFile($file->getPathname(), $file->getClientOriginalName());
        } catch (\Throwable $e) {
            return back()->withErrors(['import' => $e->getMessage()])->withInput();
        }

        if (empty($rows)) {
            return back()->withErrors(['import' => 'File tidak berisi data yang dapat diproses.'])->withInput();
        }

        $result = $this->importService->processRows($rows);

        if (!empty($result['errors'])) {
            $errors = array_merge(
                ['Import dibatalkan. Perbaiki data berikut lalu coba lagi:'],
                $result['errors']
            );
            return back()->withErrors(['import' => $errors])->withInput();
        }

        $created = $this->importService->createContacts($result['contacts'], auth()->user());

        ActivityLog::record('contact.import.file', 'Import ' . $created . ' kontak dari file ' . $file->getClientOriginalName());

        return redirect()->route('contacts.index')->with('success', $created . ' kontak berhasil diimport dari ' . $file->getClientOriginalName() . '.');
    }

    protected function visibleAgents()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return User::where('role', 'agen')->orderBy('name')->get();
        }

        if ($user->isSupervisor()) {
            return User::where('role', 'agen')
                ->where('branch_id', $user->branch_id)
                ->orderBy('name')
                ->get();
        }

        if ($user->isAgen()) {
            return collect([$user]);
        }

        return collect();
    }

    protected function visibleBranches()
    {
        $query = Branch::where('is_active', true)->orderBy('name');

        $user = auth()->user();

        if ($user && ($user->isSupervisor() || $user->isAgen())) {
            $query->where('id', $user->branch_id);
        }

        return $query->get();
    }

    protected function authorizeAccess(Contact $contact): void
    {
        $user = auth()->user();

        if ($user->isAgen() && (int) $contact->agen_id !== (int) $user->id) {
            abort(403, 'Anda hanya dapat mengelola kontak milik sendiri.');
        }

        if ($user->isSupervisor() && $contact->branch_id !== $user->branch_id) {
            abort(403, 'Anda hanya dapat mengelola kontak di cabang Anda.');
        }
    }
}

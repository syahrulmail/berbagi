<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactImportService;
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
        $query = Contact::with(['agen', 'branch']);

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

        $contacts = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $agents = $this->visibleAgents();

        return view('contacts.index', compact('contacts', 'agents'));
    }

    public function create()
    {
        $agents = $this->visibleAgents();

        return view('contacts.create', compact('agents'));
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

        return collect();
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

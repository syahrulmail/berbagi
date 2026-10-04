<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\ActivityLog;
use App\Models\Banner;
use App\Models\CampaignTag;
use App\Models\Contact;
use App\Models\DonationItem;
use App\Models\Program;
use App\Models\User;
use App\Models\WaFollowup;
use App\Models\WhatsappMessage;
use App\Services\ContactImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Modul mobile tambahan agar fitur desktop tersedia di /mo/
 * (WhatsApp, Follow-up WA, Label Kampanye, Banner, Pencapaian, Log Aktivitas,
 * Import/Tempel Kontak, dan daftar donatur program).
 */
class MobileModuleController extends MobileAppController
{
    /* =====================================================
     | WHATSAPP
     | ===================================================== */

    public function whatsappIndex(Request $request)
    {
        $messages = WhatsappMessage::with('contact')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $statusCounts = [
            'all' => WhatsappMessage::count(),
            'pending' => WhatsappMessage::where('status', 'pending')->count(),
            'sent' => WhatsappMessage::where('status', 'sent')->count(),
            'failed' => WhatsappMessage::where('status', 'failed')->count(),
        ];

        return view('mobile.whatsapp.index', compact('messages', 'statusCounts'));
    }

    public function whatsappCreate()
    {
        $contacts = Contact::orderBy('name')->get();

        return view('mobile.whatsapp.form', compact('contacts'));
    }

    public function whatsappStore(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'phone' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string'],
        ]);

        $message = WhatsappMessage::create([
            'contact_id' => $data['contact_id'] ?? null,
            'phone' => $data['phone'],
            'message' => $data['message'],
            'status' => WhatsappMessage::STATUS_PENDING,
        ]);

        ActivityLog::record('whatsapp.create', 'Menjadwalkan pesan WhatsApp ke ' . $message->phone);

        return redirect()->route('mo.whatsapp')->with('success', 'Pesan WhatsApp dijadwalkan untuk dikirim.');
    }

    public function whatsappDestroy(WhatsappMessage $whatsapp)
    {
        ActivityLog::record('whatsapp.delete', 'Menghapus pesan WhatsApp ke ' . $whatsapp->phone);
        $whatsapp->delete();

        return redirect()->route('mo.whatsapp')->with('success', 'Pesan WhatsApp berhasil dihapus.');
    }

    /* =====================================================
     | FOLLOW-UP WA
     | ===================================================== */

    public function followupIndex(Request $request)
    {
        $user = auth()->user();

        $query = WaFollowup::query()->with(['agen', 'program']);

        if ($user->isAgen()) {
            $query->where('agen_id', $user->id);
        } elseif ($user->isSupervisor() && $user->branch_id) {
            $query->whereHas('agen', fn ($q) => $q->where('branch_id', $user->branch_id));
        }

        $query->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to));

        $followups = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('mobile.followups.index', compact('followups'));
    }

    public function followupDestroy(WaFollowup $followup)
    {
        $user = auth()->user();

        if ($user->isAgen() && (int) $followup->agen_id !== (int) $user->id) {
            abort(403);
        }

        $followup->delete();

        return redirect()->route('mo.followups')->with('success', 'Data follow-up dihapus.');
    }

    /* =====================================================
     | LABEL KAMPANYE
     | ===================================================== */

    public function campaignTagIndex()
    {
        $tags = CampaignTag::withCount('programs')->orderBy('name')->paginate(15);

        return view('mobile.campaign-tags.index', compact('tags'));
    }

    public function campaignTagCreate()
    {
        return view('mobile.campaign-tags.form')->with('tag', null);
    }

    public function campaignTagStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:1000'],
            'color' => ['required', 'string', 'max:20'],
        ]);

        $names = $this->parseTagNames($data['name']);
        $created = 0;

        foreach ($names as $name) {
            $slug = Str::slug($name);

            if (CampaignTag::where('slug', $slug)->exists()) {
                continue;
            }

            CampaignTag::create(['name' => $name, 'slug' => $slug, 'color' => $data['color']]);
            $created++;
        }

        if ($created > 0) {
            ActivityLog::record('campaign_tag.create', 'Membuat ' . $created . ' label kampanye: ' . implode(', ', $names));

            return redirect()->route('mo.campaign-tags')->with('success', $created . ' label kampanye berhasil dibuat.');
        }

        return back()->with('error', 'Tidak ada label baru yang dibuat (nama sudah digunakan).')->withInput();
    }

    public function campaignTagEdit(CampaignTag $campaignTag)
    {
        return view('mobile.campaign-tags.form', ['tag' => $campaignTag]);
    }

    public function campaignTagUpdate(Request $request, CampaignTag $campaignTag)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:1000'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:campaign_tags,slug,' . $campaignTag->id],
            'color' => ['required', 'string', 'max:20'],
        ]);

        $names = $this->parseTagNames($data['name']);
        $primary = array_shift($names) ?? $campaignTag->name;
        $newSlug = $data['slug'] ?: Str::slug($primary);

        if (CampaignTag::where('slug', $newSlug)->where('id', '!=', $campaignTag->id)->exists()) {
            return back()->withErrors(['slug' => 'Slug sudah digunakan label lain.'])->withInput();
        }

        $campaignTag->update(['name' => $primary, 'slug' => $newSlug, 'color' => $data['color']]);

        foreach ($names as $name) {
            $slug = Str::slug($name);

            if (! CampaignTag::where('slug', $slug)->exists()) {
                CampaignTag::create(['name' => $name, 'slug' => $slug, 'color' => $data['color']]);
            }
        }

        ActivityLog::record('campaign_tag.update', 'Memperbarui label kampanye ' . $campaignTag->name);

        return redirect()->route('mo.campaign-tags')->with('success', 'Label kampanye berhasil diperbarui.');
    }

    public function campaignTagDestroy(CampaignTag $campaignTag)
    {
        ActivityLog::record('campaign_tag.delete', 'Menghapus label kampanye ' . $campaignTag->name);
        $campaignTag->delete();

        return redirect()->route('mo.campaign-tags')->with('success', 'Label kampanye berhasil dihapus.');
    }

    protected function parseTagNames(string $raw): array
    {
        $names = [];
        $seen = [];

        foreach (explode(',', $raw) as $part) {
            $name = trim($part);

            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $names[] = $name;
        }

        return array_slice($names, 0, 50);
    }

    /* =====================================================
     | BANNER & LABEL
     | ===================================================== */

    public function bannerIndex()
    {
        $banners = Banner::orderBy('sort_order')->paginate(15);

        return view('mobile.banners.index', compact('banners'));
    }

    public function bannerCreate()
    {
        return view('mobile.banners.form')->with('banner', null);
    }

    public function bannerStore(Request $request)
    {
        $data = $this->validateBanner($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        Banner::create($data);

        ActivityLog::record('banner.create', 'Membuat banner ' . $data['title']);

        return redirect()->route('mo.banners')->with('success', 'Banner berhasil dibuat.');
    }

    public function bannerEdit(Banner $banner)
    {
        return view('mobile.banners.form', compact('banner'));
    }

    public function bannerUpdate(Request $request, Banner $banner)
    {
        $data = $this->validateBanner($request);

        if ($request->hasFile('image')) {
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        $banner->update($data);

        ActivityLog::record('banner.update', 'Memperbarui banner ' . $banner->title);

        return redirect()->route('mo.banners')->with('success', 'Banner berhasil diperbarui.');
    }

    public function bannerDestroy(Banner $banner)
    {
        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }

        ActivityLog::record('banner.delete', 'Menghapus banner ' . $banner->title);
        $banner->delete();

        return redirect()->route('mo.banners')->with('success', 'Banner berhasil dihapus.');
    }

    protected function validateBanner(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:banner,label'],
            'image' => ['nullable', 'image', 'max:5120'],
            'url' => ['nullable', 'url'],
            'label_color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);
    }

    /* =====================================================
     | PENCAPAIAN
     | ===================================================== */

    public function achievementIndex()
    {
        $achievements = Achievement::orderBy('sort_order')->orderBy('id')->paginate(15);

        return view('mobile.achievements.index', compact('achievements'));
    }

    public function achievementCreate()
    {
        return view('mobile.achievements.form')->with('achievement', null);
    }

    public function achievementStore(Request $request)
    {
        $data = $this->validateAchievement($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('achievements', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        Achievement::create($data);

        ActivityLog::record('achievement.create', 'Membuat pencapaian ' . $data['value']);

        return redirect()->route('mo.achievements')->with('success', 'Pencapaian berhasil dibuat.');
    }

    public function achievementEdit(Achievement $achievement)
    {
        return view('mobile.achievements.form', compact('achievement'));
    }

    public function achievementUpdate(Request $request, Achievement $achievement)
    {
        $data = $this->validateAchievement($request);

        if ($request->hasFile('image')) {
            if ($achievement->image) {
                Storage::disk('public')->delete($achievement->image);
            }
            $data['image'] = $request->file('image')->store('achievements', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');

        $achievement->update($data);

        ActivityLog::record('achievement.update', 'Memperbarui pencapaian ' . $achievement->value);

        return redirect()->route('mo.achievements')->with('success', 'Pencapaian berhasil diperbarui.');
    }

    public function achievementDestroy(Achievement $achievement)
    {
        if ($achievement->image) {
            Storage::disk('public')->delete($achievement->image);
        }

        ActivityLog::record('achievement.delete', 'Menghapus pencapaian ' . $achievement->value);
        $achievement->delete();

        return redirect()->route('mo.achievements')->with('success', 'Pencapaian berhasil dihapus.');
    }

    protected function validateAchievement(Request $request): array
    {
        return $request->validate([
            'icon' => ['nullable', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'max:2048'],
            'color' => ['nullable', 'string', 'max:20'],
            'value' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }

    /* =====================================================
     | LOG AKTIVITAS
     | ===================================================== */

    public function activityLogIndex(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->search, function ($q, $search) {
                $search = trim($search);

                return $q->where(function ($inner) use ($search) {
                    $inner->where('action', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('mobile.activity-logs.index', compact('logs'));
    }

    /* =====================================================
     | KONTAK: IMPORT / TEMPEL
     | ===================================================== */

    public function contactImportForm()
    {
        return view('mobile.contacts-import');
    }

    public function contactImportStore(Request $request, ContactImportService $importService)
    {
        $request->validate(['import_file' => ['required', 'file']]);

        $file = $request->file('import_file');
        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));

        if (! in_array($ext, ['xls', 'xlsx', 'csv', 'txt'], true)) {
            return back()->withErrors(['import' => 'Ekstensi file tidak didukung. Gunakan .xls, .xlsx, .csv, atau .txt.'])->withInput();
        }

        try {
            $rows = $importService->parseFile($file->getPathname(), $file->getClientOriginalName());
        } catch (\Throwable $e) {
            return back()->withErrors(['import' => $e->getMessage()])->withInput();
        }

        return $this->processContactImport($importService, $rows, $file->getClientOriginalName(), 'contact.import.file');
    }

    public function contactPasteStore(Request $request, ContactImportService $importService)
    {
        $request->validate(['paste_lines' => ['required', 'string']]);

        try {
            $rows = $importService->parsePaste($request->input('paste_lines'));
        } catch (\Throwable $e) {
            return back()->withErrors(['import' => $e->getMessage()])->withInput();
        }

        return $this->processContactImport($importService, $rows, null, 'contact.import.paste');
    }

    protected function processContactImport(ContactImportService $service, array $rows, ?string $filename, string $action)
    {
        if (empty($rows)) {
            return back()->withErrors(['import' => 'Tidak ada data yang dapat diproses.'])->withInput();
        }

        $result = $service->processRows($rows);

        if (! empty($result['errors'])) {
            return back()->withErrors([
                'import' => array_merge(['Import dibatalkan. Perbaiki data berikut lalu coba lagi:'], $result['errors']),
            ])->withInput();
        }

        $created = $service->createContacts($result['contacts'], auth()->user());

        ActivityLog::record($action, 'Menambahkan ' . $created . ' kontak' . ($filename ? ' dari file ' . $filename : ' melalui Tempel'));

        $suffix = $filename ? ' dari ' . $filename : ' melalui Tempel';

        return redirect()->route('mo.contacts')->with('success', $created . ' kontak berhasil ditambahkan' . $suffix . '.');
    }

    /* =====================================================
     | PROGRAM: DAFTAR DONATUR (JSON untuk bottom sheet)
     | ===================================================== */

    public function programDonors(Program $program)
    {
        $user = auth()->user();

        $query = DonationItem::query()
            ->join('donations', 'donations.id', '=', 'donation_items.donation_id')
            ->join('contacts', 'contacts.id', '=', 'donations.contact_id')
            ->leftJoin('users as agens', 'agens.id', '=', 'contacts.agen_id')
            ->where('donation_items.program_id', $program->id);

        if ($user->isAgen()) {
            $query->where('contacts.agen_id', $user->id);
        } elseif ($user->isSupervisor() && $user->branch_id) {
            $query->where('contacts.branch_id', $user->branch_id);
        }

        $donors = $query
            ->groupBy('contacts.id', 'contacts.name', 'contacts.phone', 'agens.name')
            ->select([
                'contacts.id',
                'contacts.name',
                'contacts.phone',
                'agens.name as agen_name',
                DB::raw('COUNT(DISTINCT donation_items.donation_id) as donation_count'),
                DB::raw('SUM(donation_items.amount) as total_amount'),
            ])
            ->orderByDesc('total_amount')
            ->orderBy('contacts.name')
            ->get()
            ->map(fn ($d) => [
                'name' => $d->name,
                'phone' => $d->phone,
                'agen' => $d->agen_name ?? '-',
                'count' => (int) $d->donation_count,
                'total_formatted' => 'Rp ' . number_format((float) $d->total_amount, 0, ',', '.'),
            ]);

        return response()->json([
            'program_name' => $program->name,
            'count' => $donors->count(),
            'donors' => $donors,
        ]);
    }
}

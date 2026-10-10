<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Broadcast;
use App\Models\Contact;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\FollowupWaService;
use Illuminate\Http\Request;

/**
 * Halaman "Follow-up WA" desktop: broadcast otomatis/terbatas,
 * template manual, dan warming antar pengguna.
 */
class FollowupWaController extends Controller
{
    /** @var FollowupWaService */
    protected $service;

    public function __construct(FollowupWaService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $connections = $this->service->connections($user);
        $activeBroadcast = $this->service->activeBroadcast($user);

        $broadcasts = $this->broadcastScope($user)
            ->with('user')
            ->withCount([
                'targets',
                'targets as sent_targets_count' => function ($query) {
                    $query->where('status', 'sent');
                },
                'targets as failed_targets_count' => function ($query) {
                    $query->where('status', 'failed');
                },
            ])
            ->latest('id')
            ->limit(15)
            ->get();

        $branches = $this->visibleBranches();
        $agens = $this->visibleAgents();
        $warmingConfig = $this->service->warmingConfig();
        $warmingRecipients = $this->service->warmingRecipients($user);

        $logs = $this->logScope($user)
            ->with('contact')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $webhookUrl = $this->service->webhookUrl($user);

        return view('followupwa.index', compact(
            'user',
            'connections',
            'activeBroadcast',
            'broadcasts',
            'branches',
            'agens',
            'warmingConfig',
            'warmingRecipients',
            'logs',
            'webhookUrl'
        ));
    }

    /**
     * Pratinjau kontak + pesan yang akan dikirim (dipakai tab Otomatis & Manual).
     */
    public function previewContacts(Request $request)
    {
        $user = $request->user();
        $message = (string) $request->input('message', '');

        $limit = (int) $request->input('limit', 100);
        $limit = max(1, min(200, $limit));

        $contacts = $this->service->previewContacts($user, [
            'branch_id' => $request->input('branch_id'),
            'agen_id' => $request->input('agen_id'),
            'statuses' => $request->input('statuses', []),
            'followups' => $request->input('followups', []),
        ], $limit);

        $items = $contacts->map(function (Contact $contact) use ($user, $message) {
            $rendered = $message !== '' ? $this->service->renderTemplate($message, $contact, $user) : '';

            return [
                'id' => $contact->id,
                'name' => $contact->name,
                'phone' => $contact->phone,
                'status' => $contact->status,
                'status_label' => $contact->statusLabel(),
                'followup_count' => (int) $contact->followup_count,
                'message' => $rendered,
                'wa_link' => $this->service->waLink($contact, $rendered),
            ];
        });

        return response()->json([
            'count' => $items->count(),
            'contacts' => $items,
        ]);
    }

    public function storeBroadcast(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'media_path' => ['nullable', 'string', 'max:500'],
            'media_type' => ['nullable', 'in:image,video,audio,document'],
            'media_file' => ['nullable', 'file', 'max:25600', 'mimes:jpg,jpeg,png,gif,webp,bmp,mp4,mov,avi,mkv,webm,mp3,wav,ogg,m4a,aac,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'agen_id' => ['nullable', 'exists:users,id'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['in:prospect,contacted,donated,churned'],
            'followups' => ['nullable', 'array'],
            'followups.*' => ['integer', 'min:0', 'max:3'],
            'mechanism' => ['required', 'in:auto,limit'],
            'stop_at' => ['nullable', 'date'],
            'limit_count' => ['nullable', 'integer', 'min:1'],
            'schedule_type' => ['required', 'in:now,scheduled'],
            'scheduled_at' => ['nullable', 'date', 'required_if:schedule_type,scheduled'],
            'interval_min' => ['nullable', 'integer', 'min:5'],
            'interval_max' => ['nullable', 'integer', 'min:5'],
        ]);

        if ($request->hasFile('media_file')) {
            $media = $this->service->storeUploadedMedia($request->file('media_file'));
            $data['media_path'] = $media['media_path'];
            $data['media_type'] = $media['media_type'];
        }

        $result = $this->service->createBroadcast($request->user(), $data);

        if (! $result['ok']) {
            return redirect()->route('whatsapp.index')->with('error', $result['error']);
        }

        ActivityLog::record('broadcast.create', 'Memulai broadcast "' . $result['broadcast']->name . '" ke ' . $result['broadcast']->total . ' kontak.');

        return redirect()->route('whatsapp.index')->with('success', 'Broadcast dimulai.');
    }

    public function stopBroadcast(Broadcast $broadcast)
    {
        $this->authorizeBroadcast($broadcast);
        $this->service->stopBroadcast($broadcast);
        Broadcast::where('id', $broadcast->id)->update(['last_tick_at' => now()]);

        return redirect()->route('whatsapp.index')->with('success', 'Broadcast dihentikan.');
    }

    /**
     * Catat klik "Terkirim" pada pengiriman manual.
     */
    public function logManual(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'exists:contacts,id'],
            'message' => ['required', 'string'],
        ]);

        $contact = Contact::findOrFail($data['contact_id']);
        $this->authorizeContact($contact);

        $this->service->logManual($request->user(), $contact, $data['message']);

        return response()->json(['ok' => true]);
    }

    public function saveWarming(Request $request)
    {
        $data = $request->validate([
            'active' => ['nullable'],
            'amount_pair' => ['nullable', 'integer', 'min:1'],
            'interval_min' => ['nullable', 'integer', 'min:5'],
            'interval_max' => ['nullable', 'integer', 'min:5'],
            'start_time' => ['nullable', 'string', 'max:5'],
            'stop_time' => ['nullable', 'string', 'max:5'],
            'days' => ['nullable', 'array'],
            'days.*' => ['integer', 'min:1', 'max:7'],
            'messages' => ['nullable', 'string'],
        ]);

        $this->service->saveWarmingConfig($data);

        return redirect()->route('whatsapp.index')->with('success', 'Konfigurasi warming disimpan.');
    }

    public function runWarming(Request $request)
    {
        $data = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $result = $this->service->runWarming($request->user(), (int) ($data['amount'] ?? 5));

        if (! $result['ok']) {
            return redirect()->route('whatsapp.index')->with('error', $result['error']);
        }

        return redirect()->route('whatsapp.index')
            ->with('success', 'Warming selesai: ' . $result['sent'] . ' terkirim, ' . $result['failed'] . ' gagal.');
    }

    public function destroyLog(WhatsappMessage $whatsappMessage)
    {
        $this->authorizeLog($whatsappMessage);

        ActivityLog::record('whatsapp.delete', 'Menghapus log pesan ke ' . $whatsappMessage->phone);
        $whatsappMessage->delete();

        return redirect()->route('whatsapp.index')->with('success', 'Log pesan dihapus.');
    }

    /* =====================================================
     | SCOPE & OTORISASI
     | ===================================================== */

    protected function broadcastScope(User $viewer)
    {
        $query = Broadcast::query();

        if ($viewer->isAgen()) {
            $query->where('user_id', $viewer->id);
        } elseif ($viewer->isSupervisor()) {
            $query->whereIn('user_id', $this->visibleUserIds($viewer));
        }

        return $query;
    }

    protected function logScope(User $viewer)
    {
        $query = WhatsappMessage::query();

        if ($viewer->isAgen()) {
            $query->whereHas('contact', function ($sub) use ($viewer) {
                $sub->where('agen_id', $viewer->id);
            });
        } elseif ($viewer->isSupervisor() && $viewer->branch_id) {
            $query->whereHas('contact', function ($sub) use ($viewer) {
                $sub->where('branch_id', $viewer->branch_id);
            });
        }

        return $query;
    }

    protected function visibleUserIds(User $viewer)
    {
        if ($viewer->isSupervisor() && $viewer->branch_id) {
            return User::where('branch_id', $viewer->branch_id)->pluck('id');
        }

        if ($viewer->isAgen()) {
            return collect([$viewer->id]);
        }

        return User::pluck('id');
    }

    protected function visibleAgents()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return User::where('role', 'agen')->orderBy('name')->get();
        }

        if ($user->isSupervisor()) {
            return User::where('role', 'agen')->where('branch_id', $user->branch_id)->orderBy('name')->get();
        }

        return collect([$user]);
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

    protected function authorizeBroadcast(Broadcast $broadcast): void
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return;
        }

        if ((int) $broadcast->user_id === (int) $user->id) {
            return;
        }

        if ($user->isSupervisor() && $this->visibleUserIds($user)->contains($broadcast->user_id)) {
            return;
        }

        abort(403);
    }

    protected function authorizeContact(Contact $contact): void
    {
        $user = auth()->user();

        if ($user->isAgen() && (int) $contact->agen_id !== (int) $user->id) {
            abort(403);
        }

        if ($user->isSupervisor() && (int) $contact->branch_id !== (int) $user->branch_id) {
            abort(403);
        }
    }

    protected function authorizeLog(WhatsappMessage $message): void
    {
        $user = auth()->user();

        if ($user->isAgen() && (! $message->contact || (int) $message->contact->agen_id !== (int) $user->id)) {
            abort(403);
        }

        if ($user->isSupervisor() && (! $message->contact || (int) $message->contact->branch_id !== (int) $user->branch_id)) {
            abort(403);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use App\Models\Contact;
use App\Models\User;
use App\Services\FollowupWaService;
use Illuminate\Http\Request;

/**
 * Halaman "Follow-up WA" pada aplikasi mobile /mo/.
 */
class MobileFollowupWaController extends MobileModuleController
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
            ->latest('id')
            ->limit(10)
            ->get();

        $agens = $this->formAgents($user);
        $branches = $this->visibleBranches();
        $warmingConfig = $this->service->warmingConfig();
        $warmingRecipients = $this->service->warmingRecipients($user);

        return view('mobile.followupwa.index', compact(
            'user',
            'connections',
            'activeBroadcast',
            'broadcasts',
            'agens',
            'branches',
            'warmingConfig',
            'warmingRecipients'
        ));
    }

    public function previewContacts(Request $request)
    {
        $user = $request->user();
        $message = (string) $request->input('message', '');

        $limit = (int) $request->input('limit', 50);
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
                'status_label' => $contact->statusLabel(),
                'followup_count' => (int) $contact->followup_count,
                'message' => $rendered,
                'wa_link' => $this->service->waLink($contact, $rendered),
            ];
        });

        return response()->json(['count' => $items->count(), 'contacts' => $items]);
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
            return redirect()->route('mo.whatsapp')->with('error', $result['error']);
        }

        return redirect()->route('mo.whatsapp')->with('success', 'Broadcast dimulai.');
    }

    public function stopBroadcast(Broadcast $broadcast)
    {
        $this->authorizeBroadcast($broadcast);
        $this->service->stopBroadcast($broadcast);

        return redirect()->route('mo.whatsapp')->with('success', 'Broadcast dihentikan.');
    }

    public function logManual(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'exists:contacts,id'],
            'message' => ['required', 'string'],
        ]);

        $contact = Contact::findOrFail($data['contact_id']);

        if (! $this->canAccessContact($contact)) {
            abort(403);
        }

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
            'messages' => ['nullable', 'string'],
        ]);

        $this->service->saveWarmingConfig($data);

        return redirect()->route('mo.whatsapp')->with('success', 'Konfigurasi warming disimpan.');
    }

    public function runWarming(Request $request)
    {
        $data = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $result = $this->service->runWarming($request->user(), (int) ($data['amount'] ?? 5));

        if (! $result['ok']) {
            return redirect()->route('mo.whatsapp')->with('error', $result['error']);
        }

        return redirect()->route('mo.whatsapp')
            ->with('success', 'Warming selesai: ' . $result['sent'] . ' terkirim, ' . $result['failed'] . ' gagal.');
    }

    /* =====================================================
     | SCOPING
     | ===================================================== */

    protected function broadcastScope(User $viewer)
    {
        $query = Broadcast::query();

        if ($viewer->isAgen()) {
            $query->where('user_id', $viewer->id);
        } elseif ($viewer->isSupervisor() && $viewer->branch_id) {
            $query->whereIn('user_id', User::where('branch_id', $viewer->branch_id)->pluck('id'));
        }

        return $query;
    }

    protected function formAgents(User $user)
    {
        if ($user->isAdmin()) {
            return User::where('role', 'agen')->orderBy('name')->get();
        }

        if ($user->isSupervisor()) {
            return User::where('role', 'agen')->where('branch_id', $user->branch_id)->orderBy('name')->get();
        }

        return User::where('id', $user->id)->get();
    }

    protected function authorizeBroadcast(Broadcast $broadcast): void
    {
        $user = auth()->user();

        if ($user->isAdmin() || (int) $broadcast->user_id === (int) $user->id) {
            return;
        }

        if ($user->isSupervisor() && $user->branch_id) {
            $allowed = User::where('branch_id', $user->branch_id)->pluck('id')->contains($broadcast->user_id);
            if ($allowed) {
                return;
            }
        }

        abort(403);
    }
}

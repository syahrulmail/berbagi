<?php

namespace App\Services;

use App\Models\Broadcast;
use App\Models\BroadcastTarget;
use App\Models\Contact;
use App\Models\Setting;
use App\Models\User;
use App\Models\WarmingLog;
use App\Models\WhatsappMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orkestrasi fitur Follow-up WhatsApp:
 * broadcast otomatis/terbatas, kirim manual, dan warming antar pengguna.
 */
class FollowupWaService
{
    /** @var WhatsAppSenderService */
    protected $sender;

    /** @var TemplateRenderer */
    protected $renderer;

    /** @var ProfileService */
    protected $profiles;

    /** @var IntegrationCheckService */
    protected $integration;

    public function __construct(
        WhatsAppSenderService $sender,
        TemplateRenderer $renderer,
        ProfileService $profiles,
        IntegrationCheckService $integration
    ) {
        $this->sender = $sender;
        $this->renderer = $renderer;
        $this->profiles = $profiles;
        $this->integration = $integration;
    }

    /* =====================================================
     | KONEKSI & PROVIDER
     | ===================================================== */

    /**
     * Ambil kunci + status koneksi API WA milik user.
     *
     * @return array{ss:string,cc:string,ss_status:string,cc_status:string}
     */
    public function connections(User $user): array
    {
        $profile = $this->profiles->data($user);
        $statuses = $this->integration->forUsers([
            $user->id => ['ss' => $profile['api_ss'], 'cc' => $profile['api_cc']],
        ]);

        return [
            'ss' => $profile['api_ss'],
            'cc' => $profile['api_cc'],
            'ss_status' => $statuses[$user->id]['ss'] ?? IntegrationCheckService::STATUS_EMPTY,
            'cc_status' => $statuses[$user->id]['cc'] ?? IntegrationCheckService::STATUS_EMPTY,
        ];
    }

    /**
     * Pilih provider pengiriman. SS prioritas, CC fallback (hanya sesaat).
     *
     * @return array{provider:?string,key:?string,ok:bool,reason:?string}
     */
    public function resolveProvider(User $user, bool $scheduled): array
    {
        $connections = $this->connections($user);
        $ssOk = $connections['ss_status'] === IntegrationCheckService::STATUS_OK;
        $ccOk = $connections['cc_status'] === IntegrationCheckService::STATUS_OK;

        if ($ssOk) {
            return ['provider' => WhatsAppSenderService::PROVIDER_SS, 'key' => $connections['ss'], 'ok' => true, 'reason' => null];
        }

        if ($ccOk && ! $scheduled) {
            return ['provider' => WhatsAppSenderService::PROVIDER_CC, 'key' => $connections['cc'], 'ok' => true, 'reason' => null];
        }

        if ($ccOk && $scheduled) {
            return [
                'provider' => null,
                'key' => null,
                'ok' => false,
                'reason' => 'Pengiriman terjadwal hanya didukung Starsender. Aktifkan API SS atau pilih "Kirim Sekarang".',
            ];
        }

        if ($connections['ss'] === '' && $connections['cc'] === '') {
            return [
                'provider' => null,
                'key' => null,
                'ok' => false,
                'reason' => 'Kunci API WhatsApp belum diisi. Lengkapi API SS/CC pada data pengguna.',
            ];
        }

        return [
            'provider' => null,
            'key' => null,
            'ok' => false,
            'reason' => 'Tidak ada API WhatsApp yang aktif (lampu indikator merah). Periksa kunci API.',
        ];
    }

    protected function keyFor(User $user, ?string $provider): string
    {
        $profile = $this->profiles->data($user);

        if ($provider === WhatsAppSenderService::PROVIDER_SS) {
            return $profile['api_ss'];
        }

        if ($provider === WhatsAppSenderService::PROVIDER_CC) {
            return $profile['api_cc'];
        }

        return '';
    }

    /* =====================================================
     | KONTAK
     | ===================================================== */

    /**
     * Query dasar kontak sesuai wewenang viewer.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function contactScope(User $viewer)
    {
        $query = Contact::query();

        if ($viewer->isAgen()) {
            $query->where('agen_id', $viewer->id);
        } elseif ($viewer->isSupervisor()) {
            if ($viewer->branch_id) {
                $query->where('branch_id', $viewer->branch_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    /**
     * @param  array{branch_id?:mixed,agen_id?:mixed,statuses?:array,followups?:array}  $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filterContacts(User $viewer, array $filters)
    {
        $query = $this->contactScope($viewer)->with(['agen', 'branch']);

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (! empty($filters['agen_id'])) {
            $query->where('agen_id', $filters['agen_id']);
        }

        $statuses = $this->toStringArray($filters['statuses'] ?? []);

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        } else {
            $query->where('status', '!=', Contact::STATUS_CHURNED);
        }

        $followups = $this->toIntArray($filters['followups'] ?? []);

        if ($followups !== []) {
            $query->where(function ($sub) use ($followups) {
                foreach ($followups as $bucket) {
                    if ($bucket >= 3) {
                        $sub->orWhere('followup_count', '>=', 3);
                    } else {
                        $sub->orWhere('followup_count', $bucket);
                    }
                }
            });
        }

        $query->where(function ($sub) {
            $sub->whereNull('wa_valid')->orWhere('wa_valid', true);
        });

        return $query->orderBy('followup_count')->orderBy('id');
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return Collection<int,Contact>
     */
    public function previewContacts(User $viewer, array $filters, int $limit = 100): Collection
    {
        return $this->filterContacts($viewer, $filters)->limit($limit)->get();
    }

    /* =====================================================
     | BROADCAST OTOMATIS / TERBATAS
     | ===================================================== */

    public function activeBroadcast(User $user): ?Broadcast
    {
        return Broadcast::where('user_id', $user->id)
            ->whereIn('status', [Broadcast::STATUS_QUEUED, Broadcast::STATUS_RUNNING, Broadcast::STATUS_PAUSED])
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array{ok:bool,broadcast:?Broadcast,error:?string}
     */
    public function createBroadcast(User $user, array $data): array
    {
        if ($this->activeBroadcast($user) !== null) {
            return ['ok' => false, 'broadcast' => null, 'error' => 'Masih ada broadcast aktif. Hentikan dulu sebelum memulai yang baru.'];
        }

        $scheduled = ($data['schedule_type'] ?? 'now') === 'scheduled';
        $provider = $this->resolveProvider($user, $scheduled);

        if (! $provider['ok']) {
            return ['ok' => false, 'broadcast' => null, 'error' => $provider['reason']];
        }

        $mechanism = ($data['mechanism'] ?? Broadcast::MECHANISM_AUTO) === Broadcast::MECHANISM_LIMIT
            ? Broadcast::MECHANISM_LIMIT
            : Broadcast::MECHANISM_AUTO;

        $query = $this->filterContacts($user, $data);
        $total = (clone $query)->count();

        if ($total === 0) {
            return ['ok' => false, 'broadcast' => null, 'error' => 'Tidak ada kontak yang cocok dengan filter.'];
        }

        $requested = (int) ($data['limit_count'] ?? 0);

        if ($mechanism === Broadcast::MECHANISM_LIMIT) {
            $batchSize = $requested > 0 ? min($requested, $total) : $total;
        } else {
            $batchSize = $requested > 0 ? min($requested, $total) : min($total, 10);
        }

        $intervalMin = max(5, (int) ($data['interval_min'] ?? 20));
        $intervalMax = max($intervalMin, (int) ($data['interval_max'] ?? 60));

        $broadcast = Broadcast::create([
            'user_id' => $user->id,
            'name' => $data['name'] ?? ('Broadcast ' . now()->format('d M Y H:i')),
            'message_template' => $data['message'] ?? '',
            'media_path' => $data['media_path'] ?? null,
            'media_type' => $data['media_type'] ?? null,
            'target_branch_id' => ($data['branch_id'] ?? null) ?: null,
            'target_agen_id' => ($data['agen_id'] ?? null) ?: null,
            'target_statuses' => $this->toStringArray($data['statuses'] ?? []),
            'target_followups' => $this->toIntArray($data['followups'] ?? []),
            'mechanism' => $mechanism,
            'stop_at' => ! empty($data['stop_at']) ? Carbon::parse($data['stop_at']) : null,
            'limit_count' => $mechanism === Broadcast::MECHANISM_LIMIT ? $batchSize : null,
            'schedule_type' => $scheduled ? 'scheduled' : 'now',
            'scheduled_at' => $scheduled && ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null,
            'interval_min' => $intervalMin,
            'interval_max' => $intervalMax,
            'provider' => $provider['provider'],
            'status' => Broadcast::STATUS_RUNNING,
            'total' => $total,
        ]);

        $contacts = $query->limit($batchSize)->get();
        $this->createTargets($broadcast, $user, $contacts);
        $this->runPendingTargets($broadcast);

        return ['ok' => true, 'broadcast' => $broadcast, 'error' => null];
    }

    /**
     * Buat baris target untuk sekumpulan kontak (idempotent per kontak).
     */
    protected function createTargets(Broadcast $broadcast, User $user, Collection $contacts): void
    {
        foreach ($contacts as $contact) {
            BroadcastTarget::firstOrCreate(
                ['broadcast_id' => $broadcast->id, 'contact_id' => $contact->id],
                [
                    'provider' => $broadcast->provider,
                    'status' => BroadcastTarget::STATUS_PENDING,
                    'rendered_message' => $this->renderTemplate($broadcast->message_template, $contact, $user),
                ]
            );
        }
    }

    /**
     * Kirim seluruh target yang masih pending.
     */
    public function runPendingTargets(Broadcast $broadcast): void
    {
        if ($broadcast->provider === null) {
            return;
        }

        $broadcast->refresh();

        if (in_array($broadcast->status, [Broadcast::STATUS_STOPPED, Broadcast::STATUS_COMPLETED], true)) {
            return;
        }

        $user = $broadcast->user;
        $provider = $broadcast->provider;
        $key = $this->keyFor($user, $provider);
        $scheduled = $broadcast->schedule_type === 'scheduled';

        $targets = $broadcast->targets()
            ->where('status', BroadcastTarget::STATUS_PENDING)
            ->with('contact')
            ->orderBy('id')
            ->get();

        $cursor = $broadcast->scheduled_at ? $broadcast->scheduled_at->copy() : Carbon::now();

        foreach ($targets as $index => $target) {
            if ($broadcast->status === Broadcast::STATUS_STOPPED) {
                $target->update(['status' => BroadcastTarget::STATUS_STOPPED, 'error' => 'Dihentikan pengguna.']);
                continue;
            }

            if ($broadcast->stop_at && Carbon::now()->greaterThan($broadcast->stop_at)) {
                $target->update(['status' => BroadcastTarget::STATUS_STOPPED, 'error' => 'Melewati batas waktu stop.']);
                $broadcast->update(['status' => Broadcast::STATUS_STOPPED]);
                continue;
            }

            if ($index > 0) {
                $cursor = $cursor->copy()->addSeconds(random_int($broadcast->interval_min, $broadcast->interval_max));
            }

            $contact = $target->contact;

            if ($contact === null) {
                $target->update(['status' => BroadcastTarget::STATUS_SKIPPED, 'error' => 'Kontak tidak ditemukan.']);

                continue;
            }

            $mediaPath = trim((string) $broadcast->media_path);
            $options = [
                'media_url' => $mediaPath !== '' ? asset_photo_url($mediaPath) : null,
                'media_type' => $broadcast->media_type,
            ];

            if ($scheduled) {
                $options['schedule'] = $cursor->getTimestamp() * 1000;
            }

            $target->update([
                'provider' => $provider,
                'status' => BroadcastTarget::STATUS_PROCESSING,
                'attempts' => $target->attempts + 1,
                'scheduled_at' => $cursor,
            ]);

            $result = $this->sender->send($provider, $key, $contact->phone, $target->rendered_message, $options);

            $this->applySendResult($broadcast, $target, $contact, $result, $cursor);
        }

        $broadcast->refresh();

        $pending = $broadcast->targets()->where('status', BroadcastTarget::STATUS_PENDING)->count();

        if ($broadcast->status !== Broadcast::STATUS_STOPPED && $pending === 0) {
            $hasMore = $broadcast->mechanism === Broadcast::MECHANISM_AUTO
                && $broadcast->targets()->count() < $broadcast->total;

            $broadcast->update([
                'status' => $hasMore ? Broadcast::STATUS_RUNNING : Broadcast::STATUS_COMPLETED,
                'last_tick_at' => Carbon::now(),
            ]);
        } else {
            $broadcast->update(['last_tick_at' => Carbon::now()]);
        }

        if ($broadcast->status === Broadcast::STATUS_STOPPED) {
            $broadcast->targets()
                ->where('status', BroadcastTarget::STATUS_PENDING)
                ->update(['status' => BroadcastTarget::STATUS_STOPPED, 'error' => 'Dihentikan pengguna.']);
        }
    }

    /**
     * @param  array<string,mixed>  $result
     */
    protected function applySendResult(Broadcast $broadcast, BroadcastTarget $target, Contact $contact, array $result, Carbon $cursor): void
    {
        if ($result['ok']) {
            $target->update([
                'status' => BroadcastTarget::STATUS_SENT,
                'response' => $result['response'],
                'error' => null,
                'sent_at' => Carbon::now(),
            ]);

            DB::table('broadcasts')->where('id', $broadcast->id)->increment('sent');

            $this->touchContact($contact, $target->rendered_message, $result);
        } else {
            $target->update([
                'status' => BroadcastTarget::STATUS_FAILED,
                'response' => $result['response'],
                'error' => $result['error'],
            ]);

            DB::table('broadcasts')->where('id', $broadcast->id)->increment('failed');

            $this->logMessage($contact, $target->rendered_message, WhatsappMessage::STATUS_FAILED, $result, 'bc');
        }
    }

    protected function touchContact(Contact $contact, string $message, array $result = []): void
    {
        $contact->increment('followup_count');
        $contact->last_messaged_at = Carbon::now();

        if ($contact->status === Contact::STATUS_PROSPECT) {
            $contact->status = Contact::STATUS_CONTACTED;
        }

        $contact->save();

        if ($result !== []) {
            $this->logMessage($contact, $message, WhatsappMessage::STATUS_SENT, $result, 'bc');
        }
    }

    /**
     * @param  array<string,mixed>  $result
     */
    protected function logMessage(Contact $contact, string $message, string $status, array $result, string $kind): void
    {
        WhatsappMessage::create([
            'contact_id' => $contact->id,
            'phone' => $contact->phone,
            'message' => $message,
            'status' => $status,
            'response' => json_encode([
                'kind' => $kind,
                'provider' => $result['provider'] ?? null,
                'ok' => $result['ok'] ?? null,
                'error' => $result['error'] ?? null,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sent_at' => $status === WhatsappMessage::STATUS_SENT ? Carbon::now() : null,
        ]);
    }

    public function stopBroadcast(Broadcast $broadcast): void
    {
        $broadcast->update(['status' => Broadcast::STATUS_STOPPED]);
        $broadcast->targets()
            ->where('status', BroadcastTarget::STATUS_PENDING)
            ->update(['status' => BroadcastTarget::STATUS_STOPPED, 'error' => 'Dihentikan pengguna.']);
    }

    /**
     * Lanjutkan batch otomatis (dipanggil webhook saat ada balasan).
     */
    public function nextAutoBatch(Broadcast $broadcast, int $size = 5): void
    {
        if ($broadcast->mechanism !== Broadcast::MECHANISM_AUTO || $broadcast->status !== Broadcast::STATUS_RUNNING) {
            return;
        }

        $user = $broadcast->user;
        $existing = $broadcast->targets()->pluck('contact_id')->filter()->all();

        $filters = [
            'branch_id' => $broadcast->target_branch_id,
            'agen_id' => $broadcast->target_agen_id,
            'statuses' => $broadcast->target_statuses,
            'followups' => $broadcast->target_followups,
        ];

        $contacts = $this->filterContacts($user, $filters)
            ->when($existing !== [], function ($query) use ($existing) {
                return $query->whereNotIn('id', $existing);
            })
            ->limit($size)
            ->get();

        if ($contacts->isEmpty()) {
            $broadcast->update(['status' => Broadcast::STATUS_COMPLETED]);

            return;
        }

        $this->createTargets($broadcast, $user, $contacts);
        $this->runPendingTargets($broadcast);
    }

    public function recordReply(Broadcast $broadcast): void
    {
        DB::table('broadcasts')->where('id', $broadcast->id)->increment('replies');
    }

    /* =====================================================
     | MANUAL
     | ===================================================== */

    public function renderTemplate(string $template, Contact $contact, ?User $user = null): string
    {
        $vars = [
            'nama' => (string) $contact->name,
            'nama_kontak' => (string) $contact->name,
            'nomor' => (string) $contact->phone,
            'nama_agen' => (string) ($user ? $user->name : ''),
            'cabang' => (string) ($contact->branch->name ?? ''),
        ];

        return $this->renderer->render($template, $vars);
    }

    /**
     * Wajib kirim? Tidak. Manual hanya mencatat klik "Terkirim".
     */
    public function logManual(User $user, Contact $contact, string $message): WhatsappMessage
    {
        $rendered = $this->renderer->hasUnresolvedPlaceholder($message)
            ? $this->renderTemplate($message, $contact, $user)
            : $message;

        $record = WhatsappMessage::create([
            'contact_id' => $contact->id,
            'phone' => $contact->phone,
            'message' => $rendered,
            'status' => WhatsappMessage::STATUS_SENT,
            'response' => json_encode([
                'kind' => 'manual',
                'by' => $user->id,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sent_at' => Carbon::now(),
        ]);

        $contact->increment('followup_count');
        $contact->last_messaged_at = Carbon::now();
        if ($contact->status === Contact::STATUS_PROSPECT) {
            $contact->status = Contact::STATUS_CONTACTED;
        }
        $contact->save();

        return $record;
    }

    public function waLink(Contact $contact, string $message): string
    {
        return 'https://wa.me/' . $this->sender->normalizePhone($contact->phone) . '?text=' . rawurlencode($message);
    }

    /* =====================================================
     | WARMING
     | ===================================================== */

    /**
     * @return array<string,mixed>
     */
    public function warmingConfig(): array
    {
        $decoded = json_decode((string) Setting::get('warming_config', '{}'), true);

        if (! is_array($decoded)) {
            $decoded = [];
        }

        return array_merge([
            'active' => false,
            'amount_pair' => 5,
            'interval_min' => 30,
            'interval_max' => 90,
            'start_time' => '08:00',
            'stop_time' => '21:00',
            'messages' => "Assalamualaikum kak, semoga harimu menyenangkan.",
        ], $decoded);
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function saveWarmingConfig(array $data): void
    {
        $config = [
            'active' => ! empty($data['active']),
            'amount_pair' => max(1, (int) ($data['amount_pair'] ?? 5)),
            'interval_min' => max(5, (int) ($data['interval_min'] ?? 30)),
            'interval_max' => max(5, (int) ($data['interval_max'] ?? 90)),
            'start_time' => $data['start_time'] ?? '08:00',
            'stop_time' => $data['stop_time'] ?? '21:00',
            'messages' => $data['messages'] ?? '',
        ];

        Setting::set('warming_config', json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'warming');
    }

    /**
     * Daftar pengguna lain (non-self) yang punya nomor + status API-nya.
     *
     * @return array<int,array<string,mixed>>
     */
    public function warmingRecipients(User $runner): array
    {
        $query = User::query()
            ->where('id', '!=', $runner->id)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR, User::ROLE_AGEN])
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        if ($runner->isSupervisor() && $runner->branch_id) {
            $query->where('branch_id', $runner->branch_id);
        }

        $users = $query->orderBy('name')->get();

        $profiles = [];
        foreach ($users as $user) {
            $profile = $this->profiles->data($user);
            $profiles[$user->id] = ['ss' => $profile['api_ss'], 'cc' => $profile['api_cc']];
        }

        $statuses = $this->integration->forUsers($profiles);

        $today = Carbon::today();

        return $users->map(function (User $user) use ($statuses, $today) {
            $sent = WarmingLog::where('from_user_id', $user->id)
                ->where('direction', WarmingLog::DIRECTION_OUT)
                ->where('created_at', '>=', $today)
                ->count();
            $received = WarmingLog::where('to_user_id', $user->id)
                ->where('direction', WarmingLog::DIRECTION_IN)
                ->where('created_at', '>=', $today)
                ->count();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->roleLabel(),
                'ss' => $statuses[$user->id]['ss'] ?? IntegrationCheckService::STATUS_EMPTY,
                'cc' => $statuses[$user->id]['cc'] ?? IntegrationCheckService::STATUS_EMPTY,
                'sent' => $sent,
                'received' => $received,
            ];
        })->all();
    }

    /**
     * Jalankan warming sekali: runner mengirim ke sejumlah pengguna lain.
     *
     * @return array{ok:bool,sent:int,failed:int,error:?string}
     */
    public function runWarming(User $runner, int $amount): array
    {
        $provider = $this->resolveProvider($runner, false);

        if (! $provider['ok']) {
            return ['ok' => false, 'sent' => 0, 'failed' => 0, 'error' => $provider['reason']];
        }

        $amount = max(1, $amount);
        $recipients = User::query()
            ->where('id', '!=', $runner->id)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR, User::ROLE_AGEN])
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        if ($runner->isSupervisor() && $runner->branch_id) {
            $recipients->where('branch_id', $runner->branch_id);
        }

        $recipients = $recipients->inRandomOrder()->limit($amount)->get();

        if ($recipients->isEmpty()) {
            return ['ok' => false, 'sent' => 0, 'failed' => 0, 'error' => 'Tidak ada pengguna tujuan warming.'];
        }

        $config = $this->warmingConfig();
        $template = (string) ($config['messages'] ?? '');
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            $message = $this->renderer->render($template, ['nama' => $recipient->name]);
            $result = $this->sender->send($provider['provider'], $provider['key'], $recipient->phone, $message);
            $ok = ! empty($result['ok']);

            WarmingLog::create([
                'from_user_id' => $runner->id,
                'to_user_id' => $recipient->id,
                'from_phone' => $runner->phone,
                'to_phone' => $recipient->phone,
                'message' => $message,
                'provider' => $provider['provider'],
                'direction' => WarmingLog::DIRECTION_OUT,
                'response' => json_encode([
                    'ok' => $ok,
                    'error' => $result['error'] ?? null,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            if ($ok) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->saveWarmingConfig(array_merge($config, ['active' => true]));

        return ['ok' => true, 'sent' => $sent, 'failed' => $failed, 'error' => null];
    }

    /**
     * Catat balasan masuk untuk warming (dipanggil webhook).
     */
    public function recordWarmingIncoming(User $runner, string $fromPhone, string $message): void
    {
        $from = User::where('phone', $fromPhone)->first();

        WarmingLog::create([
            'from_user_id' => $from ? $from->id : null,
            'to_user_id' => $runner->id,
            'from_phone' => $fromPhone,
            'to_phone' => $runner->phone,
            'message' => $message,
            'provider' => null,
            'direction' => WarmingLog::DIRECTION_IN,
            'response' => null,
        ]);
    }

    /* =====================================================
     | HELPERS
     | ===================================================== */

    /**
     * Simpan file media broadcast ke disk publik.
     *
     * @return array{media_path:string,media_type:string}
     */
    public function storeUploadedMedia(\Illuminate\Http\UploadedFile $file): array
    {
        return [
            'media_path' => $file->store('wa-media', 'public'),
            'media_type' => $this->detectMediaType($file->getMimeType(), $file->getClientOriginalExtension()),
        ];
    }

    /**
     * Kenali jenis media secara otomatis dari mime/ekstensi.
     */
    public function detectMediaType(?string $mime, ?string $extension = null): string
    {
        $mime = strtolower((string) $mime);

        if (strpos($mime, 'image/') === 0) {
            return 'image';
        }

        if (strpos($mime, 'video/') === 0) {
            return 'video';
        }

        if (strpos($mime, 'audio/') === 0) {
            return 'audio';
        }

        $ext = strtolower((string) $extension);

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)) {
            return 'image';
        }

        if (in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm'], true)) {
            return 'video';
        }

        if (in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'aac'], true)) {
            return 'audio';
        }

        return 'document';
    }

    /**
     * Token webhook milik pengguna (dibuat sekali, disimpan di settings).
     */
    public function webhookToken(User $user): string
    {
        $key = 'wa_webhook_token_' . $user->id;
        $token = (string) Setting::get($key, '');

        if ($token === '') {
            $token = bin2hex(random_bytes(16));
            Setting::set($key, $token, 'warming');
        }

        return $token;
    }

    public function webhookUrl(User $user, string $provider = 'starsender'): string
    {
        return url('/wa/webhook/' . $provider) . '?u=' . $user->id . '&t=' . $this->webhookToken($user);
    }

    /**
     * @param  mixed  $value
     * @return array<int,int>
     */
    protected function toIntArray($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if ($item === null || $item === '') {
                continue;
            }
            $result[] = (int) $item;
        }

        return array_values(array_unique($result));
    }

    /**
     * @param  mixed  $value
     * @return array<int,string>
     */
    protected function toStringArray($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $result[] = $item;
            }
        }

        return array_values(array_unique($result));
    }
}

<?php

namespace App\Services;

use App\Models\Broadcast;
use App\Models\BroadcastTarget;
use App\Models\Contact;
use App\Models\Setting;
use App\Models\User;
use App\Models\WarmingLog;
use App\Models\WhatsappMessage;
use App\Services\ImageWebpService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Tentukan pemilik efektif broadcast.
     *
     * Admin/Supervisor yang memilih agen akan memakai API SS/CC agen tsb;
     * selain itu memakai user yang sedang bertindak.
     *
     * @return array{ok:bool,owner:?User,error:?string}
     */
    public function resolveBroadcastOwner(User $actor, $agenId): array
    {
        if ($actor->isAgen()) {
            return ['ok' => true, 'owner' => $actor, 'error' => null];
        }

        $agenId = (int) $agenId;

        if ($agenId <= 0) {
            return ['ok' => true, 'owner' => $actor, 'error' => null];
        }

        $agent = User::where('id', $agenId)->where('role', User::ROLE_AGEN)->first();

        if ($agent === null) {
            return ['ok' => false, 'owner' => null, 'error' => 'Agen yang dipilih tidak ditemukan.'];
        }

        if ($actor->isSupervisor()) {
            if (! $actor->branch_id || (int) $agent->branch_id !== (int) $actor->branch_id) {
                return ['ok' => false, 'owner' => null, 'error' => 'Agen yang dipilih bukan bagian dari cabang yang Anda kelola.'];
            }
        }

        return ['ok' => true, 'owner' => $agent, 'error' => null];
    }

    /**
     * Lampirkan status koneksi API SS/CC ke tiap user (properti api_status).
     */
    public function decorateApiStatuses(Collection $users): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $profiles = [];
        foreach ($users as $user) {
            $profile = $this->profiles->data($user);
            $profiles[$user->id] = ['ss' => $profile['api_ss'], 'cc' => $profile['api_cc']];
        }

        $statuses = $this->integration->forUsers($profiles);

        foreach ($users as $user) {
            $user->api_status = $statuses[$user->id] ?? [
                'ss' => IntegrationCheckService::STATUS_EMPTY,
                'cc' => IntegrationCheckService::STATUS_EMPTY,
            ];
        }
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

    /**
     * Uraikan daftar kontak yang ditempel (format "Nama,NomorWhatsapp" per baris).
     * Hanya memakai entri yang diisikan; kontak yang cocok di DB dipakai untuk mencatat log.
     *
     * @return array<int,array<string,mixed>>
     */
    public function parsePastedContacts(string $paste, User $user, string $message = ''): array
    {
        $items = [];
        $lines = preg_split('/\r\n|\r|\n/', $paste);

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || strpos($line, ',') === false) {
                continue;
            }

            list($name, $rawPhone) = array_pad(explode(',', $line, 2), 2, '');
            $name = trim($name);
            $phone = $this->sender->normalizePhone(trim($rawPhone));

            if ($name === '' || $phone === '') {
                continue;
            }

            $contact = new Contact();
            $contact->name = $name;
            $contact->phone = $phone;

            $rendered = $message !== '' ? $this->renderTemplate($message, $contact, $user) : '';
            $existing = Contact::where('phone', $phone)->first();

            $items[] = [
                'id' => $existing ? $existing->id : null,
                'name' => $name,
                'phone' => $phone,
                'status' => 'paste',
                'status_label' => 'Paste',
                'followup_count' => $existing ? (int) $existing->followup_count : 0,
                'message' => $rendered,
                'wa_link' => 'https://wa.me/' . $phone . '?text=' . rawurlencode($rendered),
            ];
        }

        return $items;
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
        $ownerResult = $this->resolveBroadcastOwner($user, $data['agen_id'] ?? null);

        if (! $ownerResult['ok']) {
            return ['ok' => false, 'broadcast' => null, 'error' => $ownerResult['error']];
        }

        $owner = $ownerResult['owner'];

        if ($this->activeBroadcast($owner) !== null) {
            $who = (int) $owner->id === (int) $user->id ? 'Anda' : 'agen ' . $owner->name;

            return ['ok' => false, 'broadcast' => null, 'error' => 'Masih ada broadcast aktif untuk ' . $who . '. Hentikan dulu sebelum memulai yang baru.'];
        }

        $scheduled = ($data['schedule_type'] ?? 'now') === 'scheduled';
        $provider = $this->resolveProvider($owner, $scheduled);

        if (! $provider['ok']) {
            return ['ok' => false, 'broadcast' => null, 'error' => $provider['reason']];
        }

        $mechanism = ($data['mechanism'] ?? Broadcast::MECHANISM_AUTO) === Broadcast::MECHANISM_LIMIT
            ? Broadcast::MECHANISM_LIMIT
            : Broadcast::MECHANISM_AUTO;

        $query = $this->filterContacts($owner, $data);
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
        $startsAt = $scheduled && ! empty($data['scheduled_at'])
            ? Carbon::parse($data['scheduled_at'])
            : Carbon::now();

        $broadcast = Broadcast::create([
            'user_id' => $owner->id,
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
            'scheduled_at' => $scheduled ? $startsAt : null,
            'interval_min' => $intervalMin,
            'interval_max' => $intervalMax,
            'provider' => $provider['provider'],
            'status' => $startsAt->isFuture() ? Broadcast::STATUS_QUEUED : Broadcast::STATUS_RUNNING,
            'total' => $batchSize,
        ]);

        $contacts = $query->orderBy('id')->limit($batchSize)->get();
        $this->createTargets($broadcast, $owner, $contacts, $startsAt);

        return ['ok' => true, 'broadcast' => $broadcast, 'error' => null];
    }

    /**
     * Buat baris target untuk sekumpulan kontak (idempotent per kontak).
     *
     * Setiap target diberi jadwal kirim berurutan dengan jeda acak
     * interval_min..interval_max. Target pertama memakai $firstAt.
     */
    protected function createTargets(Broadcast $broadcast, User $user, Collection $contacts, Carbon $firstAt, bool $gapBeforeFirst = false): int
    {
        $cursor = $firstAt->copy();
        $created = 0;

        foreach ($contacts as $index => $contact) {
            if ($index > 0 || $gapBeforeFirst) {
                $cursor = $cursor->copy()->addSeconds(random_int($broadcast->interval_min, $broadcast->interval_max));
            }

            $target = BroadcastTarget::firstOrCreate(
                ['broadcast_id' => $broadcast->id, 'contact_id' => $contact->id],
                [
                    'provider' => $broadcast->provider,
                    'status' => BroadcastTarget::STATUS_PENDING,
                    'rendered_message' => $this->renderTemplate($broadcast->message_template, $contact, $user),
                    'scheduled_at' => $cursor,
                ]
            );

            if ($target->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Kirim satu target yang sudah jatuh tempo.
     *
     * @return array<string,mixed>
     */
    protected function sendTarget(Broadcast $broadcast, BroadcastTarget $target): array
    {
        $contact = $target->contact;

        if ($contact === null) {
            $target->update(['status' => BroadcastTarget::STATUS_SKIPPED, 'error' => 'Kontak tidak ditemukan.']);

            return ['ok' => false, 'provider' => $broadcast->provider, 'response' => '', 'error' => 'Kontak tidak ditemukan.'];
        }

        $mediaPath = trim((string) $broadcast->media_path);
        $options = [
            'media_url' => $mediaPath !== '' ? asset_photo_url($mediaPath) : null,
            'media_type' => $broadcast->media_type,
        ];

        $target->update([
            'provider' => $broadcast->provider,
            'status' => BroadcastTarget::STATUS_PROCESSING,
            'attempts' => $target->attempts + 1,
        ]);

        $result = $this->sender->send(
            $broadcast->provider,
            $this->keyFor($broadcast->user, $broadcast->provider),
            $contact->phone,
            $target->rendered_message,
            $options
        );

        $this->applySendResult($broadcast, $target, $contact, $result);

        return $result;
    }

    /**
     * Proses antrean broadcast: kirim target yang jatuh tempo dan tunggu
     * target berikutnya hingga $until. Dipanggil command followup:starsender.
     *
     * @return array{sent:int,failed:int}
     */
    public function processQueue(?Carbon $until = null): array
    {
        $until = $until ?: Carbon::now();
        $sent = 0;
        $failed = 0;

        do {
            $didWork = false;
            $nextDue = null;

            $broadcasts = Broadcast::whereIn('status', [Broadcast::STATUS_QUEUED, Broadcast::STATUS_RUNNING])
                ->orderBy('id')
                ->get();

            foreach ($broadcasts as $broadcast) {
                try {
                    $broadcast->refresh();
                } catch (\Throwable $e) {
                    continue;
                }

                if (! in_array($broadcast->status, [Broadcast::STATUS_QUEUED, Broadcast::STATUS_RUNNING], true)) {
                    continue;
                }

                if ($broadcast->schedule_type === 'scheduled' && $broadcast->scheduled_at && Carbon::now()->lt($broadcast->scheduled_at)) {
                    $nextDue = $this->earlier($nextDue, $broadcast->scheduled_at);

                    continue;
                }

                if ($broadcast->status === Broadcast::STATUS_QUEUED) {
                    $broadcast->update(['status' => Broadcast::STATUS_RUNNING]);
                }

                if ($broadcast->stop_at && Carbon::now()->greaterThanOrEqualTo($broadcast->stop_at)) {
                    $this->stopBroadcast($broadcast);
                    $didWork = true;

                    continue;
                }

                $target = $broadcast->targets()
                    ->where('status', BroadcastTarget::STATUS_PENDING)
                    ->orderBy('scheduled_at')
                    ->orderBy('id')
                    ->first();

                if ($target === null) {
                    $this->finishIfDone($broadcast);

                    continue;
                }

                if ($target->scheduled_at && $target->scheduled_at->greaterThan(Carbon::now())) {
                    $nextDue = $this->earlier($nextDue, $target->scheduled_at);

                    continue;
                }

                $result = $this->sendTarget($broadcast, $target);

                if (! empty($result['ok'])) {
                    $sent++;
                } else {
                    $failed++;
                }

                $didWork = true;
                $broadcast->update(['last_tick_at' => Carbon::now()]);

                $next = $broadcast->targets()
                    ->where('status', BroadcastTarget::STATUS_PENDING)
                    ->orderBy('scheduled_at')
                    ->orderBy('id')
                    ->first();

                if ($next !== null) {
                    $earliest = Carbon::now()->addSeconds(random_int($broadcast->interval_min, $broadcast->interval_max));

                    if ($next->scheduled_at === null || $next->scheduled_at->lessThan($earliest)) {
                        $next->update(['scheduled_at' => $earliest]);
                    }
                } else {
                    $this->finishIfDone($broadcast);
                }
            }

            if (! $didWork) {
                if ($nextDue !== null && $nextDue->lessThanOrEqualTo($until)) {
                    $seconds = $nextDue->getTimestamp() - Carbon::now()->getTimestamp();

                    if ($seconds > 0) {
                        sleep($seconds);
                    }

                    continue;
                }

                break;
            }
        } while (Carbon::now()->lessThan($until));

        return ['sent' => $sent, 'failed' => $failed];
    }

    protected function earlier(?Carbon $current, Carbon $candidate): Carbon
    {
        if ($current === null || $candidate->lessThan($current)) {
            return $candidate;
        }

        return $current;
    }

    /**
     * Tandai broadcast selesai bila tidak ada target tersisa.
     *
     * Broadcast otomatis dibiarkan tetap berjalan selama masih ada kontak
     * yang belum pernah dikirimi, karena balasan berikutnya akan menambah
     * kuota 5 kontak. Broadcast selesai saat kontak habis atau dihentikan.
     */
    protected function finishIfDone(Broadcast $broadcast): void
    {
        $broadcast->refresh();

        if (in_array($broadcast->status, [Broadcast::STATUS_STOPPED, Broadcast::STATUS_COMPLETED, Broadcast::STATUS_FAILED], true)) {
            return;
        }

        $pending = $broadcast->targets()->where('status', BroadcastTarget::STATUS_PENDING)->count();

        if ($pending > 0) {
            return;
        }

        if ($broadcast->mechanism === Broadcast::MECHANISM_AUTO && $this->hasAvailableContacts($broadcast)) {
            $broadcast->update(['last_tick_at' => Carbon::now()]);

            return;
        }

        $broadcast->update([
            'status' => Broadcast::STATUS_COMPLETED,
            'last_tick_at' => Carbon::now(),
        ]);
    }

    protected function hasAvailableContacts(Broadcast $broadcast): bool
    {
        $user = $broadcast->user;

        if ($user === null) {
            return false;
        }

        $existing = $broadcast->targets()->pluck('contact_id')->filter()->all();

        $filters = [
            'branch_id' => $broadcast->target_branch_id,
            'agen_id' => $broadcast->target_agen_id,
            'statuses' => $broadcast->target_statuses,
            'followups' => $broadcast->target_followups,
        ];

        return $this->filterContacts($user, $filters)
            ->when($existing !== [], function ($query) use ($existing) {
                return $query->whereNotIn('id', $existing);
            })
            ->exists();
    }

    /**
     * @param  array<string,mixed>  $result
     */
    protected function applySendResult(Broadcast $broadcast, BroadcastTarget $target, Contact $contact, array $result): void
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

    public function pauseBroadcast(Broadcast $broadcast): bool
    {
        if ($broadcast->status !== Broadcast::STATUS_RUNNING) {
            return false;
        }

        $broadcast->update(['status' => Broadcast::STATUS_PAUSED, 'last_tick_at' => Carbon::now()]);

        return true;
    }

    public function resumeBroadcast(Broadcast $broadcast): bool
    {
        if ($broadcast->status !== Broadcast::STATUS_PAUSED) {
            return false;
        }

        $broadcast->update(['status' => Broadcast::STATUS_RUNNING, 'last_tick_at' => Carbon::now()]);

        $next = $broadcast->targets()
            ->where('status', BroadcastTarget::STATUS_PENDING)
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->first();

        if ($next !== null) {
            $earliest = Carbon::now()->addSeconds(random_int($broadcast->interval_min, $broadcast->interval_max));

            if ($next->scheduled_at === null || $next->scheduled_at->lessThan($earliest)) {
                $next->update(['scheduled_at' => $earliest]);
            }
        }

        return true;
    }

    /**
     * Hapus riwayat broadcast. Log pesan (whatsapp_messages) tidak dihapus.
     */
    public function deleteBroadcast(Broadcast $broadcast): void
    {
        $broadcast->delete();
    }

    /**
     * Tambah 5 kontak berikutnya ke batch otomatis, maksimal sebanyak
     * kuota yang masih tersedia. Dipanggil webhook saat ada balasan.
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
            ->orderBy('id')
            ->limit($size)
            ->get();

        if ($contacts->isEmpty()) {
            $this->finishIfDone($broadcast);

            return;
        }

        $lastScheduled = $broadcast->targets()->max('scheduled_at');
        $firstAt = $lastScheduled ? Carbon::parse($lastScheduled) : Carbon::now();

        if ($firstAt->lessThan(Carbon::now())) {
            $firstAt = Carbon::now();
        }

        $created = $this->createTargets($broadcast, $user, $contacts, $firstAt, true);

        if ($created > 0) {
            DB::table('broadcasts')->where('id', $broadcast->id)->increment('total', $created);
        }
    }

    /**
     * Proses balasan masuk untuk broadcast otomatis yang sedang berjalan.
     *
     * Balasan hanya dihitung bila:
     * - status broadcast masih berjalan,
     * - pengirim adalah kontak yang sudah dikirimi pesan oleh broadcast ini,
     * - waktu pesan masuk tidak lebih lama dari broadcast/pesan yang dikirim
     *   (mencegah balasan dari histori masa lalu dihitung ulang).
     */
    public function handleIncomingBroadcastReply(User $user, string $from, ?Carbon $receivedAt = null, ?string $messageId = null): bool
    {
        $broadcast = $this->activeBroadcast($user);

        if ($broadcast === null
            || $broadcast->mechanism !== Broadcast::MECHANISM_AUTO
            || $broadcast->status !== Broadcast::STATUS_RUNNING) {
            return false;
        }

        $receivedAt = $receivedAt ?: Carbon::now();

        if ($broadcast->created_at && $receivedAt->lessThan($broadcast->created_at)) {
            return false;
        }

        if ($messageId !== null && $messageId !== '') {
            $cacheKey = 'wa_bc_reply:' . $broadcast->id . ':' . sha1($messageId);

            if (! Cache::add($cacheKey, 1, Carbon::now()->addDay())) {
                return false;
            }
        }

        $phone = $this->sender->normalizePhone($from);

        if ($phone === '') {
            return false;
        }

        $variants = array_values(array_unique([
            $phone,
            '0' . substr($phone, 2),
            '+' . $phone,
        ]));

        $target = $broadcast->targets()
            ->where('status', BroadcastTarget::STATUS_SENT)
            ->whereHas('contact', function ($query) use ($variants) {
                $query->whereIn('phone', $variants);
            })
            ->orderByDesc('sent_at')
            ->first();

        if ($target === null) {
            return false;
        }

        if ($target->sent_at && $receivedAt->lessThan($target->sent_at)) {
            return false;
        }

        $this->recordReply($broadcast);
        $this->nextAutoBatch($broadcast, 5);

        return true;
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

        $config = array_merge([
            'active' => false,
            'amount_pair' => 5,
            'amount' => 5,
            'interval_min' => 30,
            'interval_max' => 90,
            'start_time' => '08:00',
            'stop_time' => '21:00',
            'days' => [1, 2, 3, 4, 5, 6, 7],
            'messages' => "Assalamualaikum kak, semoga harimu menyenangkan.",
        ], $decoded);

        $config['days'] = $this->normalizeDays($config['days'] ?? null);

        return $config;
    }

    /**
     * Normalisasi daftar hari (1=Senin .. 7=Minggu). Kosong => semua hari.
     *
     * @param  mixed  $days
     * @return array<int,int>
     */
    protected function normalizeDays($days): array
    {
        if (! is_array($days)) {
            return [1, 2, 3, 4, 5, 6, 7];
        }

        $result = [];
        foreach ($days as $day) {
            $day = (int) $day;
            if ($day >= 1 && $day <= 7) {
                $result[] = $day;
            }
        }

        $result = array_values(array_unique($result));

        return $result === [] ? [1, 2, 3, 4, 5, 6, 7] : $result;
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function saveWarmingConfig(array $data): void
    {
        $intervalMin = max(5, (int) ($data['interval_min'] ?? 30));

        $config = [
            'active' => ! empty($data['active']),
            'amount_pair' => max(1, (int) ($data['amount_pair'] ?? 5)),
            'amount' => max(1, min(50, (int) ($data['amount'] ?? 5))),
            'interval_min' => $intervalMin,
            'interval_max' => max($intervalMin, (int) ($data['interval_max'] ?? 90)),
            'start_time' => $data['start_time'] ?? '08:00',
            'stop_time' => $data['stop_time'] ?? '21:00',
            'days' => $this->normalizeDays($data['days'] ?? null),
            'messages' => $data['messages'] ?? '',
        ];

        Setting::set('warming_config', json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'warming');
    }

    /**
     * Pilih satu baris template warming secara acak (satu baris = satu pesan).
     */
    public function pickWarmingMessage(string $template): string
    {
        $lines = [];

        foreach (preg_split('/\r\n|\r|\n/', $template) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        if ($lines === []) {
            return trim($template);
        }

        return $lines[array_rand($lines)];
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

        $users = $query->with('branch')->orderBy('name')->get();

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
                'branch' => $user->branch->name ?? '-',
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
            $message = $this->renderer->render($this->pickWarmingMessage($template), ['nama' => $recipient->name]);
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

        return ['ok' => true, 'sent' => $sent, 'failed' => $failed, 'error' => null];
    }

    /**
     * Warming otomatis sesuai jadwal (dipanggil oleh scheduler tiap menit).
     * Untuk setiap pengguna yang memenuhi syarat, kirim satu siklus warming
     * bila jeda (interval) sejak pesan keluar terakhir sudah terlewati.
     *
     * @return array{ok:bool,ran:int,sent:int,failed:int,skipped:?string}
     */
    public function runScheduledWarming(?Carbon $now = null): array
    {
        $this->touchWarmingCron($now);

        $config = $this->warmingConfig();

        if (empty($config['active'])) {
            return ['ok' => true, 'ran' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 'inactive'];
        }

        $now = $now ?: Carbon::now();

        if (! in_array((int) $now->dayOfWeekIso, $config['days'], true)) {
            return ['ok' => true, 'ran' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 'day'];
        }

        if (! $this->withinWindow($now, (string) $config['start_time'], (string) $config['stop_time'])) {
            return ['ok' => true, 'ran' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 'window'];
        }

        $amount = max(1, (int) $config['amount_pair']);
        $intervalMin = max(5, (int) $config['interval_min']);
        $intervalMax = max($intervalMin, (int) $config['interval_max']);

        $users = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR, User::ROLE_AGEN])
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        $ran = 0;
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $profile = $this->profiles->data($user);

            if ($profile['api_ss'] === '' && $profile['api_cc'] === '') {
                continue;
            }

            $last = WarmingLog::where('from_user_id', $user->id)
                ->where('direction', WarmingLog::DIRECTION_OUT)
                ->max('created_at');

            $gap = random_int($intervalMin, $intervalMax);

            if ($last && Carbon::parse($last)->addSeconds($gap)->greaterThan($now)) {
                continue;
            }

            $result = $this->runWarming($user, $amount);

            if (! empty($result['ok'])) {
                $ran++;
                $sent += (int) $result['sent'];
                $failed += (int) $result['failed'];
            }
        }

        return ['ok' => true, 'ran' => $ran, 'sent' => $sent, 'failed' => $failed, 'skipped' => null];
    }

    /**
     * Cek apakah waktu sekarang berada dalam rentang jam mulai-selesai.
     * Mendukung rentang yang melewati tengah malam.
     */
    protected function withinWindow(Carbon $now, string $start, string $stop): bool
    {
        $current = $now->format('H:i');

        $start = strlen($start) >= 5 ? substr($start, 0, 5) : $start;
        $stop = strlen($stop) >= 5 ? substr($stop, 0, 5) : $stop;

        if ($start === '' || $stop === '') {
            return true;
        }

        if ($start <= $stop) {
            return $current >= $start && $current <= $stop;
        }

        return $current >= $start || $current <= $stop;
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
            'to_phone' => (string) ($runner->phone ?: ''),
            'message' => $message,
            'provider' => null,
            'direction' => WarmingLog::DIRECTION_IN,
            'response' => null,
        ]);
    }

    /**
     * Gabungan log pengiriman WhatsApp (broadcast/manual) dan warming
     * yang sudah dinormalisasi untuk tabel log di halaman Follow-up.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function recentLogs(User $viewer, int $limit = 50): Collection
    {
        $limit = max(1, min(200, $limit));

        $messageQuery = WhatsappMessage::query()->with(['contact.agen.branch']);

        if ($viewer->isAgen()) {
            $messageQuery->whereHas('contact', function ($query) use ($viewer) {
                $query->where('agen_id', $viewer->id);
            });
        } elseif ($viewer->isSupervisor() && $viewer->branch_id) {
            $agentIds = User::where('branch_id', $viewer->branch_id)->pluck('id');
            $messageQuery->whereHas('contact', function ($query) use ($viewer, $agentIds) {
                $query->where('branch_id', $viewer->branch_id)
                    ->orWhereIn('agen_id', $agentIds);
            });
        }

        $messages = $messageQuery->orderByDesc('created_at')->limit($limit)->get();

        $manualIds = $messages->map(function (WhatsappMessage $message) {
            $response = json_decode((string) $message->response, true);
            $kind = is_array($response) ? ($response['kind'] ?? null) : null;

            return $kind === 'manual' ? (int) ($response['by'] ?? 0) : 0;
        })->filter()->unique()->values();

        $manualUsers = $manualIds->isEmpty()
            ? collect()
            : User::with('branch')->whereIn('id', $manualIds)->get()->keyBy('id');

        $warmingQuery = WarmingLog::query()
            ->with(['fromUser.branch', 'toUser'])
            ->where('direction', WarmingLog::DIRECTION_OUT);

        if ($viewer->isAgen()) {
            $warmingQuery->where('from_user_id', $viewer->id);
        } elseif ($viewer->isSupervisor() && $viewer->branch_id) {
            $warmingQuery->whereIn('from_user_id', User::where('branch_id', $viewer->branch_id)->pluck('id'));
        }

        $warmingLogs = $warmingQuery->orderByDesc('created_at')->limit($limit)->get();

        $entries = collect();

        foreach ($messages as $message) {
            $response = json_decode((string) $message->response, true);
            $kind = is_array($response) ? ($response['kind'] ?? null) : null;
            $sender = $message->contact->agen ?? null;

            if ($kind === 'manual' && isset($response['by'])) {
                $sender = $manualUsers->get((int) $response['by']) ?: $sender;
            }

            $entries->push([
                'key' => 'wa-' . $message->id,
                'source' => 'whatsapp',
                'id' => $message->id,
                'contact_name' => $message->contact->name ?? '-',
                'phone' => $message->phone,
                'message' => (string) $message->message,
                'status' => $message->status,
                'created_at' => $message->created_at,
                'method' => $kind === 'manual' ? 'Manual' : 'Otomatis',
                'sender_name' => $sender->name ?? '-',
                'sender_branch' => $sender->branch->name ?? '-',
                'sender_phone' => $sender->phone ?? '-',
            ]);
        }

        foreach ($warmingLogs as $log) {
            $response = json_decode((string) $log->response, true);
            $ok = ! is_array($response) || ($response['ok'] ?? true) !== false;
            $sender = $log->fromUser;

            $entries->push([
                'key' => 'warming-' . $log->id,
                'source' => 'warming',
                'id' => $log->id,
                'contact_name' => $log->toUser->name ?? $log->to_phone,
                'phone' => $log->to_phone,
                'message' => (string) $log->message,
                'status' => $ok ? WhatsappMessage::STATUS_SENT : WhatsappMessage::STATUS_FAILED,
                'created_at' => $log->created_at,
                'method' => 'Warming',
                'sender_name' => $sender->name ?? '-',
                'sender_branch' => $sender->branch->name ?? '-',
                'sender_phone' => $log->from_phone ?: ($sender->phone ?? '-'),
            ]);
        }

        return $entries->sortByDesc(function ($entry) {
            return $entry['created_at'] ? $entry['created_at']->getTimestamp() : 0;
        })->take($limit)->values();
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
        $type = $this->detectMediaType($file->getMimeType(), $file->getClientOriginalExtension());

        // Hanya gambar yang dikonversi ke WebP; video/audio/dokumen disimpan apa adanya.
        if ($type === 'image') {
            $path = app(ImageWebpService::class)->store($file, 'wa-media', 'public');

            if ($path !== null) {
                return ['media_path' => $path, 'media_type' => 'image'];
            }
        }

        return [
            'media_path' => $file->store('wa-media', 'public'),
            'media_type' => $type,
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
     * Nama provider webhook yang kanonik: starsender atau cloudchat.
     */
    public function normalizeWebhookProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));

        if ($provider === 'ss' || $provider === 'starsender') {
            return 'starsender';
        }

        if ($provider === 'cc' || $provider === 'cloudchat') {
            return 'cloudchat';
        }

        return $provider;
    }

    /**
     * Token webhook milik pengguna, terpisah per provider
     * (dibuat sekali, disimpan di settings).
     *
     * Token lama (tanpa sufiks provider) hanya dipakai ulang untuk Starsender
     * agar URL yang sudah terpasang tetap valid. Provider lain memakai token
     * acak sendiri; token yang masih sama dengan token lama otomatis
     * di-regenerate supaya tiap provider benar-benar berbeda.
     */
    public function webhookToken(User $user, string $provider = 'starsender'): string
    {
        $provider = $this->normalizeWebhookProvider($provider);
        $legacyKey = 'wa_webhook_token_' . $user->id;
        $key = $legacyKey . '_' . $provider;
        $token = (string) Setting::get($key, '');
        $legacy = (string) Setting::get($legacyKey, '');

        $needsReset = $token === ''
            || ($provider !== 'starsender' && $legacy !== '' && $token === $legacy);

        if ($needsReset) {
            $token = ($provider === 'starsender' && $legacy !== '')
                ? $legacy
                : bin2hex(random_bytes(16));
            Setting::set($key, $token, 'warming');
        }

        return $token;
    }

    public function webhookUrl(User $user, string $provider = 'starsender'): string
    {
        $provider = $this->normalizeWebhookProvider($provider);

        return route('wa.webhook', [
            'provider' => $provider,
            'u' => $user->id,
            't' => $this->webhookToken($user, $provider),
        ]);
    }

    /**
     * Token global untuk endpoint cron warming (dibuat sekali, disimpan di settings).
     */
    public function cronToken(): string
    {
        $token = (string) Setting::get('wa_cron_token', '');

        if ($token === '') {
            $token = bin2hex(random_bytes(16));
            Setting::set('wa_cron_token', $token, 'warming');
        }

        return $token;
    }

    /**
     * URL yang dipanggil tiap menit oleh cron untuk menjalankan warming otomatis.
     */
    public function cronUrl(): string
    {
        return url('/wa/cron/' . $this->cronToken());
    }

    /**
     * Catat waktu terakhir warming dijalankan oleh cron/scheduler.
     * Dipanggil dari awal runScheduledWarming(), sehingga berlaku untuk
     * cron CLI (schedule:run) maupun endpoint URL cron.
     */
    public function touchWarmingCron(?Carbon $at = null): void
    {
        Setting::set('warming_last_cron', ($at ?: Carbon::now())->toDateTimeString(), 'warming');
    }

    /**
     * Waktu terakhir cron menjalankan warming (null bila belum pernah).
     */
    public function warmingLastCron(): ?Carbon
    {
        $value = (string) Setting::get('warming_last_cron', '');

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Status kesehatan cron warming untuk indikator di panel.
     * status: ok (<= 5 menit), fail (> 5 menit), empty (belum ada data).
     *
     * @return array{status:string,last:?Carbon,minutes:?int,label:string}
     */
    public function cronStatus(): array
    {
        $last = $this->warmingLastCron();

        if (! $last) {
            return ['status' => 'empty', 'last' => null, 'minutes' => null, 'label' => 'Belum ada data'];
        }

        $minutes = (int) floor($last->diffInMinutes(Carbon::now()));

        if ($minutes <= 5) {
            return [
                'status' => 'ok',
                'last' => $last,
                'minutes' => $minutes,
                'label' => $minutes <= 0 ? 'Baru saja' : ($minutes . ' menit lalu'),
            ];
        }

        if ($minutes < 60) {
            $label = $minutes . ' menit lalu';
        } elseif ($minutes < 1440) {
            $label = (int) floor($minutes / 60) . ' jam lalu';
        } else {
            $label = (int) floor($minutes / 1440) . ' hari lalu';
        }

        return ['status' => 'fail', 'last' => $last, 'minutes' => $minutes, 'label' => $label];
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

<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Broadcast;
use App\Models\BroadcastTarget;
use App\Models\Contact;
use App\Models\Setting;
use App\Models\User;
use App\Models\WarmingLog;
use App\Models\WhatsappMessage;
use App\Services\FollowupWaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FollowupWaTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::fake();
    }

    protected function makeUser(string $role, ?Branch $branch = null, string $phone = '628120000000'): User
    {
        return User::create([
            'name' => ucfirst($role) . ' ' . uniqid(),
            'username' => 'u_' . $role . '_' . uniqid(),
            'slug' => 's_' . $role . '_' . uniqid(),
            'email' => $role . '_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'role' => $role,
            'phone' => $phone,
            'is_active' => true,
            'branch_id' => $branch ? $branch->id : null,
        ]);
    }

    protected function setApi(User $user, string $ss = 'SS-DEVICE-KEY', string $cc = ''): void
    {
        Setting::set('agent_profile_' . $user->slug, json_encode([
            'photo' => '',
            'intro' => '',
            'api_ss' => $ss,
            'api_cc' => $cc,
        ]));
    }

    protected function makeContact(string $name, string $phone, array $extra = []): Contact
    {
        return Contact::create(array_merge([
            'name' => $name,
            'phone' => $phone,
            'status' => Contact::STATUS_PROSPECT,
        ], $extra));
    }

    protected function makeLog(Contact $contact, string $message, ?Carbon $at = null): WhatsappMessage
    {
        $log = WhatsappMessage::create([
            'contact_id' => $contact->id,
            'phone' => $contact->phone,
            'message' => $message,
            'status' => WhatsappMessage::STATUS_SENT,
            'sent_at' => $at ?: Carbon::now(),
        ]);

        if ($at) {
            $log->created_at = $at;
            $log->save();
        }

        return $log;
    }

    public function test_followup_wa_page_renders_tabs(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('whatsapp.index'));

        $response->assertOk();
        $response->assertSee('Otomatis');
        $response->assertSee('Manual');
        $response->assertSee('Warming');
    }

    public function test_broadcast_sends_and_updates_contacts(): void
    {
        $admin = $this->makeUser('admin');
        $this->setApi($admin);

        $agen = $this->makeUser('agen');
        $c1 = $this->makeContact('Kontak Satu', '628111111111', ['agen_id' => $agen->id]);
        $c2 = $this->makeContact('Kontak Dua', '628222222222', ['agen_id' => $agen->id]);

        $response = $this->actingAs($admin)->post(route('followupwa.broadcast.store'), [
            'name' => 'Uji Broadcast',
            'message' => 'Halo [nama]',
            'mechanism' => 'limit',
            'limit_count' => 2,
            'schedule_type' => 'now',
            'interval_min' => 5,
            'interval_max' => 5,
            'statuses' => ['prospect'],
            'agen_id' => $agen->id,
        ]);

        $response->assertRedirect(route('whatsapp.index'));

        $broadcast = Broadcast::where('user_id', $admin->id)->first();
        $this->assertNotNull($broadcast);
        $this->assertSame(2, $broadcast->sent);
        $this->assertSame(2, $broadcast->targets()->where('status', BroadcastTarget::STATUS_SENT)->count());

        $this->assertSame(1, $c1->fresh()->followup_count);
        $this->assertSame(Contact::STATUS_CONTACTED, $c1->fresh()->status);
        $this->assertSame(1, $c2->fresh()->followup_count);
        $this->assertSame(2, WhatsappMessage::whereIn('contact_id', [$c1->id, $c2->id])->count());
    }

    public function test_broadcast_rejected_without_api_key(): void
    {
        $admin = $this->makeUser('admin');
        $this->setApi($admin, '', '');

        $this->actingAs($admin)->post(route('followupwa.broadcast.store'), [
            'message' => 'Halo',
            'mechanism' => 'auto',
            'schedule_type' => 'now',
        ])->assertRedirect(route('whatsapp.index'))->assertSessionHas('error');

        $this->assertSame(0, Broadcast::count());
    }

    public function test_manual_log_records_followup(): void
    {
        $agen = $this->makeUser('agen');
        $contact = $this->makeContact('Kontak Agen', '628333333333', ['agen_id' => $agen->id]);

        $response = $this->actingAs($agen)->postJson(route('followupwa.manual'), [
            'contact_id' => $contact->id,
            'message' => 'Halo [nama]',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(1, $contact->fresh()->followup_count);
        $this->assertSame(1, WhatsappMessage::where('contact_id', $contact->id)->count());
    }

    public function test_preview_contacts_returns_json(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeContact('Preview Satu', '628444444444');

        $response = $this->actingAs($admin)->postJson(route('followupwa.contacts'), [
            'message' => 'Hai [nama]',
            'statuses' => ['prospect'],
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['count', 'contacts']);
        $this->assertGreaterThanOrEqual(1, $response->json('count'));
    }

    public function test_preview_contacts_respects_limit(): void
    {
        $admin = $this->makeUser('admin');
        for ($i = 0; $i < 5; $i++) {
            $this->makeContact('Limit ' . $i, '62850000000' . $i);
        }

        $response = $this->actingAs($admin)->postJson(route('followupwa.contacts'), [
            'message' => 'Hai [nama]',
            'limit' => 3,
        ]);

        $response->assertOk();
        $this->assertSame(3, $response->json('count'));
    }

    public function test_manual_panel_renders_dropdowns_and_wa_app_choice(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('whatsapp.index'));

        $response->assertOk();
        $response->assertSee('Jumlah Kontak');
        $response->assertSee('WA Bisnis');
        $response->assertSee('WA Personal');
        $response->assertSee('id="fuwa-manual-status"', false);
        $response->assertSee('id="fuwa-manual-followup"', false);
        $response->assertSee('id="fuwa-manual-limit"', false);
        $response->assertSee('id="fuwa-manual-waapp"', false);
        $response->assertSee('com.whatsapp.w4b', false);
    }

    public function test_mobile_manual_panel_renders_dropdowns_and_wa_app_choice(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('mo.whatsapp'));

        $response->assertOk();
        $response->assertSee('id="mo-manual-status"', false);
        $response->assertSee('id="mo-manual-followup"', false);
        $response->assertSee('id="mo-manual-limit"', false);
        $response->assertSee('id="mo-manual-waapp"', false);
        $response->assertSee('com.whatsapp.w4b', false);
    }

    public function test_mobile_log_tab_lists_and_deletes_messages(): void
    {
        $admin = $this->makeUser('admin');
        $contact = $this->makeContact('Log Kontak', '628111222333');
        $message = WhatsappMessage::create([
            'contact_id' => $contact->id,
            'phone' => $contact->phone,
            'message' => 'Pesan uji log',
            'status' => WhatsappMessage::STATUS_SENT,
            'sent_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($admin)->get(route('mo.whatsapp'));

        $response->assertOk();
        $response->assertSee('Log Pesan');
        $response->assertSee('Pesan uji log');
        $response->assertSee('id="mo-fuwa-log"', false);
        $response->assertSee('data-tab="log"', false);

        $this->actingAs($admin)->delete(route('mo.whatsapp.log.destroy', $message))
            ->assertRedirect(route('mo.whatsapp'));
        $this->assertNull(WhatsappMessage::find($message->id));
    }

    public function test_mobile_log_delete_is_scoped_to_owner(): void
    {
        $agen = $this->makeUser('agen');
        $other = $this->makeUser('agen');
        $contact = $this->makeContact('Log Agen', '628333444555', ['agen_id' => $agen->id]);
        $message = WhatsappMessage::create([
            'contact_id' => $contact->id,
            'phone' => $contact->phone,
            'message' => 'Pesan agen',
            'status' => WhatsappMessage::STATUS_SENT,
            'sent_at' => Carbon::now(),
        ]);

        $this->actingAs($other)->delete(route('mo.whatsapp.log.destroy', $message))
            ->assertForbidden();
        $this->assertNotNull(WhatsappMessage::find($message->id));
    }

    public function test_desktop_log_scope_by_role(): void
    {
        $admin = $this->makeUser('admin');

        $branchA = Branch::create(['code' => 'LA-' . uniqid(), 'name' => 'Log A ' . uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['code' => 'LB-' . uniqid(), 'name' => 'Log B ' . uniqid(), 'is_active' => true]);

        $supervisor = $this->makeUser('supervisor', $branchA);
        $agenA = $this->makeUser('agen', $branchA);
        $agenB = $this->makeUser('agen', $branchB);

        $cA = $this->makeContact('Kontak A', '628100000001', ['agen_id' => $agenA->id, 'branch_id' => $branchA->id]);
        $cB = $this->makeContact('Kontak B', '628100000002', ['agen_id' => $agenB->id, 'branch_id' => $branchB->id]);
        $cA2 = $this->makeContact('Kontak A2', '628100000003', ['agen_id' => $agenA->id]);

        $this->makeLog($cA, 'LOG-A');
        $this->makeLog($cB, 'LOG-B');
        $this->makeLog($cA2, 'LOG-A2');

        $this->actingAs($admin)->get(route('whatsapp.index'))
            ->assertOk()->assertSee('LOG-A')->assertSee('LOG-B')->assertSee('LOG-A2');

        $this->actingAs($supervisor)->get(route('whatsapp.index'))
            ->assertOk()->assertSee('LOG-A')->assertSee('LOG-A2')->assertDontSee('LOG-B');

        $this->actingAs($agenA)->get(route('whatsapp.index'))
            ->assertOk()->assertSee('LOG-A')->assertSee('LOG-A2')->assertDontSee('LOG-B');
    }

    public function test_mobile_log_scope_by_role(): void
    {
        $branchA = Branch::create(['code' => 'MA-' . uniqid(), 'name' => 'Mob A ' . uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['code' => 'MB-' . uniqid(), 'name' => 'Mob B ' . uniqid(), 'is_active' => true]);

        $agenA = $this->makeUser('agen', $branchA);
        $agenB = $this->makeUser('agen', $branchB);

        $cA = $this->makeContact('Mob Kontak A', '628200000001', ['agen_id' => $agenA->id, 'branch_id' => $branchA->id]);
        $cB = $this->makeContact('Mob Kontak B', '628200000002', ['agen_id' => $agenB->id, 'branch_id' => $branchB->id]);

        $this->makeLog($cA, 'MLOG-A');
        $this->makeLog($cB, 'MLOG-B');

        $this->actingAs($agenA)->get(route('mo.whatsapp'))
            ->assertOk()->assertSee('MLOG-A')->assertDontSee('MLOG-B');
    }

    public function test_log_lists_only_last_50_messages(): void
    {
        $admin = $this->makeUser('admin');
        $contact = $this->makeContact('Banyak Log', '628100000009');

        for ($i = 0; $i < 55; $i++) {
            $this->makeLog($contact, 'MSG-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT), Carbon::now()->subMinutes(100 - $i));
        }

        $response = $this->actingAs($admin)->get(route('whatsapp.index'));
        $response->assertOk();
        $response->assertSee('MSG-054');
        $response->assertSee('MSG-005');
        $response->assertDontSee('MSG-004');
    }

    public function test_warming_config_visible_only_for_admin(): void
    {
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor');
        $agen = $this->makeUser('agen');

        $this->actingAs($admin)->get(route('whatsapp.index'))
            ->assertOk()->assertSee('id="warming-config"', false)->assertSee('Cron terakhir');

        $this->actingAs($supervisor)->get(route('whatsapp.index'))
            ->assertOk()->assertDontSee('id="warming-config"', false)->assertDontSee('Cron terakhir');

        $this->actingAs($agen)->get(route('whatsapp.index'))
            ->assertOk()->assertDontSee('id="warming-config"', false)->assertDontSee('Cron terakhir');

        $this->actingAs($admin)->get(route('mo.whatsapp'))
            ->assertOk()->assertSee('id="mo-warming-run"', false)->assertSee('Status Cron Warming');

        $this->actingAs($supervisor)->get(route('mo.whatsapp'))
            ->assertOk()->assertDontSee('id="mo-warming-run"', false)->assertDontSee('Status Cron Warming');

        $this->actingAs($agen)->get(route('mo.whatsapp'))
            ->assertOk()->assertDontSee('id="mo-warming-run"', false)->assertDontSee('Status Cron Warming');
    }

    public function test_warming_recipients_include_branch_and_api(): void
    {
        $branch = Branch::create(['code' => 'WR-' . uniqid(), 'name' => 'Warming Cabang ' . uniqid(), 'is_active' => true]);
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch, '628120000123');
        $this->setApi($agen, 'SS-KEY');

        $service = app(FollowupWaService::class);
        $row = collect($service->warmingRecipients($admin))->firstWhere('id', $agen->id);

        $this->assertNotNull($row);
        $this->assertSame($branch->name, $row['branch']);
        $this->assertArrayHasKey('ss', $row);
        $this->assertArrayHasKey('cc', $row);
    }

    public function test_template_message_supports_random_variation(): void
    {
        $service = app(FollowupWaService::class);
        $contact = $this->makeContact('Random Kontak', '628999999999');

        $rendered = $service->renderTemplate('{Halo|Hai|Assalamualaikum} [nama]', $contact);

        $this->assertStringNotContainsString('{', $rendered);
        $this->assertStringNotContainsString('|', $rendered);
        $this->assertStringContainsString('Random Kontak', $rendered);
    }

    public function test_auto_broadcast_first_batch_uses_limit_count(): void
    {
        $admin = $this->makeUser('admin');
        $this->setApi($admin);

        for ($i = 0; $i < 8; $i++) {
            $this->makeContact('Auto ' . $i, '62870000000' . $i);
        }

        $this->actingAs($admin)->post(route('followupwa.broadcast.store'), [
            'message' => 'Halo [nama]',
            'mechanism' => 'auto',
            'limit_count' => 3,
            'schedule_type' => 'now',
            'interval_min' => 5,
            'interval_max' => 5,
        ])->assertRedirect(route('whatsapp.index'));

        $broadcast = Broadcast::where('user_id', $admin->id)->first();
        $this->assertNotNull($broadcast);
        $this->assertGreaterThanOrEqual(8, $broadcast->total);
        $this->assertSame(3, $broadcast->targets()->count());
        $this->assertSame(3, $broadcast->sent);
    }

    public function test_detect_media_type_from_mime_and_extension(): void
    {
        $service = app(FollowupWaService::class);

        $this->assertSame('image', $service->detectMediaType('image/jpeg'));
        $this->assertSame('video', $service->detectMediaType('video/mp4'));
        $this->assertSame('audio', $service->detectMediaType('audio/mpeg'));
        $this->assertSame('document', $service->detectMediaType('application/pdf'));
        $this->assertSame('image', $service->detectMediaType('application/octet-stream', 'png'));
    }

    public function test_warming_run_logs_outgoing_message(): void
    {
        $admin = $this->makeUser('admin');
        $this->setApi($admin);
        $winner = $this->makeUser('agen', null, '628555555555');

        $response = $this->actingAs($admin)->post(route('followupwa.warming.run'), [
            'amount' => 5,
        ]);

        $response->assertRedirect(route('whatsapp.index'));
        $this->assertGreaterThanOrEqual(1, WarmingLog::where('from_user_id', $admin->id)
            ->where('direction', WarmingLog::DIRECTION_OUT)
            ->count());
    }

    public function test_cron_status_indicator_tracks_last_run(): void
    {
        $service = app(FollowupWaService::class);

        $this->assertSame('empty', $service->cronStatus()['status']);
        $this->assertNull($service->warmingLastCron());

        $service->touchWarmingCron(Carbon::now());

        $status = $service->cronStatus();
        $this->assertSame('ok', $status['status']);
        $this->assertNotNull($status['last']);

        Setting::set('warming_last_cron', '');
        $this->assertSame('empty', $service->cronStatus()['status']);

        $service->runScheduledWarming();

        $this->assertNotNull($service->warmingLastCron());
    }

    public function test_warming_panel_shows_cron_indicator(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('whatsapp.index'))
            ->assertOk()
            ->assertSee('Cron terakhir');
    }

    public function test_warming_panel_hides_cron_url(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('whatsapp.index'))
            ->assertOk()
            ->assertDontSee('URL Cron Warming')
            ->assertDontSee('/wa/cron/');

        $this->actingAs($admin)->get(route('mo.whatsapp'))
            ->assertOk()
            ->assertDontSee('/wa/cron/');
    }

    public function test_webhook_rejects_invalid_token(): void
    {
        $user = $this->makeUser('admin');

        $this->post(route('wa.webhook', ['provider' => 'starsender', 'u' => $user->id, 't' => 'wrong']), [
            'from' => '628111',
            'text' => 'halo',
        ])->assertStatus(403);
    }

    public function test_webhook_records_reply_and_warms(): void
    {
        $service = app(FollowupWaService::class);
        $user = $this->makeUser('admin');
        $token = $service->webhookToken($user);

        $this->post(route('wa.webhook', ['provider' => 'starsender', 'u' => $user->id, 't' => $token]), [
            'from' => '628999999999',
            'text' => 'Terima kasih',
        ])->assertOk();

        $this->assertSame(1, WarmingLog::where('to_user_id', $user->id)
            ->where('direction', WarmingLog::DIRECTION_IN)
            ->count());
    }

    public function test_mobile_followup_wa_page_renders(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('mo.whatsapp'))->assertOk()->assertSee('Otomatis');
    }

    public function test_warming_config_saves_days(): void
    {
        $service = app(FollowupWaService::class);

        $service->saveWarmingConfig([
            'active' => true,
            'amount_pair' => 3,
            'interval_min' => 10,
            'interval_max' => 20,
            'start_time' => '08:00',
            'stop_time' => '20:00',
            'days' => [1, 3, 5],
            'messages' => "Halo [nama]\nSelamat pagi [nama]",
        ]);

        $config = $service->warmingConfig();
        $this->assertSame([1, 3, 5], $config['days']);
        $this->assertSame(3, $config['amount_pair']);
    }

    public function test_warming_interval_amount_is_persisted_and_used_for_run(): void
    {
        $service = app(FollowupWaService::class);
        $admin = $this->makeUser('admin');
        $this->setApi($admin);

        $this->actingAs($admin)->post(route('followupwa.warming'), [
            'active' => false,
            'amount_pair' => 3,
            'amount' => 7,
            'interval_min' => 10,
            'interval_max' => 20,
            'start_time' => '08:00',
            'stop_time' => '20:00',
            'days' => [1, 2, 3, 4, 5],
            'messages' => 'Halo [nama]',
        ])->assertRedirect(route('whatsapp.index'));

        $this->assertSame(7, $service->warmingConfig()['amount']);

        $this->actingAs($admin)->get(route('whatsapp.index'))->assertSee('value="7"', false);

        for ($i = 0; $i < 8; $i++) {
            $this->makeUser('agen', null, '6285' . str_pad((string) $i, 8, '0', STR_PAD_LEFT));
        }

        $this->actingAs($admin)->post(route('followupwa.warming.run'))
            ->assertRedirect(route('whatsapp.index'));

        $this->assertSame(7, WarmingLog::where('from_user_id', $admin->id)
            ->where('direction', WarmingLog::DIRECTION_OUT)
            ->count());
    }

    public function test_warming_config_empty_days_defaults_to_all(): void
    {
        $service = app(FollowupWaService::class);

        $service->saveWarmingConfig(['days' => []]);

        $this->assertSame([1, 2, 3, 4, 5, 6, 7], $service->warmingConfig()['days']);
    }

    public function test_pick_warming_message_returns_single_line(): void
    {
        $service = app(FollowupWaService::class);

        $picked = $service->pickWarmingMessage("Baris satu\n\nBaris dua\n  \nBaris tiga");

        $this->assertContains($picked, ['Baris satu', 'Baris dua', 'Baris tiga']);
    }

    public function test_scheduled_warming_skips_when_inactive(): void
    {
        $service = app(FollowupWaService::class);
        $service->saveWarmingConfig(['active' => false]);

        $result = $service->runScheduledWarming(Carbon::parse('2026-10-12 10:00:00'));

        $this->assertSame('inactive', $result['skipped']);
        $this->assertSame(0, $result['ran']);
    }

    public function test_scheduled_warming_skips_other_day(): void
    {
        $service = app(FollowupWaService::class);
        $service->saveWarmingConfig([
            'active' => true,
            'days' => [2],
            'start_time' => '00:00',
            'stop_time' => '23:59',
        ]);

        $result = $service->runScheduledWarming(Carbon::parse('2026-10-12 10:00:00'));

        $this->assertSame('day', $result['skipped']);
    }

    public function test_scheduled_warming_skips_outside_window(): void
    {
        $service = app(FollowupWaService::class);
        $service->saveWarmingConfig([
            'active' => true,
            'days' => [1, 2, 3, 4, 5, 6, 7],
            'start_time' => '08:00',
            'stop_time' => '09:00',
        ]);

        $result = $service->runScheduledWarming(Carbon::parse('2026-10-12 15:00:00'));

        $this->assertSame('window', $result['skipped']);
    }

    public function test_scheduled_warming_runs_for_eligible_user(): void
    {
        $service = app(FollowupWaService::class);
        $admin = $this->makeUser('admin');
        $this->setApi($admin);
        $this->makeUser('agen', null, '628555550001');

        $service->saveWarmingConfig([
            'active' => true,
            'amount_pair' => 1,
            'interval_min' => 5,
            'interval_max' => 5,
            'start_time' => '00:00',
            'stop_time' => '23:59',
            'days' => [1, 2, 3, 4, 5, 6, 7],
            'messages' => "Halo [nama]",
        ]);

        $result = $service->runScheduledWarming(Carbon::parse('2026-10-12 10:00:00'));

        $this->assertGreaterThanOrEqual(1, $result['ran']);
        $this->assertGreaterThanOrEqual(1, WarmingLog::where('from_user_id', $admin->id)
            ->where('direction', WarmingLog::DIRECTION_OUT)
            ->count());
    }

    public function test_cron_endpoint_runs_with_valid_token(): void
    {
        $service = app(FollowupWaService::class);
        $service->saveWarmingConfig(['active' => false]);

        $response = $this->get(route('wa.cron', ['token' => $service->cronToken()]));

        $response->assertOk();
    }

    public function test_cron_endpoint_rejects_invalid_token(): void
    {
        $this->get(route('wa.cron', ['token' => 'wrong-token']))->assertStatus(403);
    }
}

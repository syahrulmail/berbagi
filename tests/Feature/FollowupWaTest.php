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
}

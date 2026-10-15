<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DonationKwitansiTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(string $role, Branch $branch = null): User
    {
        $data = [
            'name' => 'User ' . $role . ' ' . uniqid(),
            'username' => 'user_' . $role . '_' . uniqid(),
            'slug' => 'user_' . $role . '_' . uniqid(),
            'email' => $role . '_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'role' => $role,
        ];
        if ($branch) {
            $data['branch_id'] = $branch->id;
        }

        return User::create($data);
    }

    protected function makeDonation(User $admin, array $overrides = [])
    {
        $branch = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Test', 'is_active' => true]);
        $agen = $this->makeUser('agen', $branch);
        $contact = Contact::create(['name' => 'Budi Test', 'phone' => '081234567890', 'status' => 'prospect']);
        $program = Program::create(['name' => 'Program Wakaf Test', 'slug' => 'program-wakaf-' . uniqid(), 'program_category' => 'WAP', 'is_active' => true]);

        $attrs = array_merge([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'donor_info' => 'Info Donatur Test',
            'amount' => 50000,
            'donation_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'created_by' => $admin->id,
        ], $overrides);

        $donation = Donation::create($attrs);
        $donation->items()->create([
            'program_id' => $program->id,
            'program_category' => 'WAP',
            'amount' => 50000,
        ]);

        return [$branch, $agen, $contact, $program, $donation];
    }

    public function test_kwitansi_page_renders_mapped_donation_data(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $response = $this->actingAs($admin)->get(route('donations.kwitansi', $donation));

        $response->assertOk();
        $response->assertSee('FORMULIR WAKAF', false);
        $response->assertSee('Budi Test');
        $response->assertSee('Info Donatur Test');
        $response->assertSee('08123456****');
        $response->assertSee('Quran');
        $response->assertSee('50.000');
        $response->assertSee('Lima puluh ribu Rupiah');
        $response->assertSee('Total: Lima puluh ribu Rupiah');
        $response->assertSee('Metode Pembayaran : Transfer Bank');
        $response->assertSee('Kode');
        $response->assertDontSee('Kode Unik Proyek');
        $response->assertDontSee('facebook.com/wakafalquran');
        $response->assertDontSee('No. FKJ');
        $response->assertDontSee('Ttd Wakif/Pendaftar');
        $response->assertDontSee('Asli (putih)');
    }

    public function test_kwitansi_is_forbidden_for_unrelated_agen(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $otherBranch = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Lain', 'is_active' => true]);
        $otherAgen = $this->makeUser('agen', $otherBranch);

        $this->actingAs($otherAgen)
            ->get(route('donations.kwitansi', $donation))
            ->assertForbidden();
    }

    public function test_kwitansi_is_forbidden_for_other_supervisor(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $otherBranch = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Lain', 'is_active' => true]);
        $supervisor = $this->makeUser('supervisor', $otherBranch);

        $this->actingAs($supervisor)
            ->get(route('donations.kwitansi', $donation))
            ->assertForbidden();
    }

    public function test_mobile_donation_detail_exposes_kwitansi_url(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $response = $this->actingAs($admin)
            ->getJson(route('mo.api.donation-detail', $donation->id));

        $response->assertOk()
            ->assertJsonPath('kwitansi_url', route('donations.kwitansi', $donation->id));

        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $response->json('wa_kwitansi_url'));
    }

    public function test_desktop_detail_exposes_kwitansi_whatsapp_url(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $response = $this->actingAs($admin)->getJson(route('donations.detail', $donation));

        $response->assertOk();
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $response->json('wa_kwitansi_url'));
        $this->assertStringContainsString('Kwitansi', rawurldecode($response->json('wa_kwitansi_url')));
    }

    public function test_kwitansi_whatsapp_url_is_empty_without_phone(): void
    {
        $admin = $this->makeUser('admin');
        [, , $contact, , $donation] = $this->makeDonation($admin);

        $contact->update(['phone' => '']);
        $donation->load('contact');

        $this->assertSame('', kwitansi_link_wa_url($donation));
    }

    public function test_public_kwitansi_requires_valid_signature(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        // Tanpa tanda tangan -> ditolak.
        $this->get(route('kwitansi.public', $donation))->assertForbidden();

        // Dengan tanda tangan -> dapat dibuka tanpa login.
        $signed = \Illuminate\Support\Facades\URL::signedRoute('kwitansi.public', ['donation' => $donation->id]);
        $this->get($signed)->assertOk()->assertSee('Budi Test');
    }

    public function test_public_kwitansi_exposes_thumbnail_open_graph_image(): void
    {
        if (! function_exists('imagettftext')) {
            $this->markTestSkipped('GD FreeType tidak tersedia.');
        }

        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        \Illuminate\Support\Facades\Storage::fake('public');

        $signed = \Illuminate\Support\Facades\URL::signedRoute('kwitansi.public', ['donation' => $donation->id]);

        $this->get($signed)
            ->assertOk()
            ->assertSee('property="og:image"', false)
            ->assertSee('/storage/kwitansi/', false)
            ->assertSee('summary_large_image');
    }

    public function test_donation_detail_exposes_kwitansi_whatsapp_link(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $response = $this->actingAs($admin)
            ->getJson(route('donations.detail', $donation));

        $response->assertOk();

        $waUrl = $response->json('wa_kwitansi_url');
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $waUrl);

        $link = kwitansi_public_url($donation->fresh('contact'));
        $this->assertStringContainsString(rawurlencode($link), $waUrl);
    }

    public function test_donation_detail_kwitansi_whatsapp_link_empty_without_phone(): void
    {
        $admin = $this->makeUser('admin');
        [, , $contact, , $donation] = $this->makeDonation($admin);

        $contact->update(['phone' => '']);

        $response = $this->actingAs($admin)
            ->getJson(route('donations.detail', $donation));

        $response->assertOk();
        $this->assertSame('', $response->json('wa_kwitansi_url'));
    }

    public function test_donation_detail_is_forbidden_for_unrelated_agen(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $otherBranch = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Lain', 'is_active' => true]);
        $otherAgen = $this->makeUser('agen', $otherBranch);

        $this->actingAs($otherAgen)
            ->getJson(route('donations.detail', $donation))
            ->assertForbidden();
    }

    public function test_kwitansi_image_service_renders_png(): void
    {
        if (! function_exists('imagettftext')) {
            $this->markTestSkipped('GD FreeType tidak tersedia.');
        }

        \Illuminate\Support\Facades\Storage::fake('public');

        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $service = new \App\Services\KwitansiImageService();
        $rel = $service->render($donation->fresh(['contact', 'items.program', 'program']));

        $this->assertNotNull($rel);
        $this->assertStringStartsWith('kwitansi/', $rel);

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $this->assertTrue($disk->exists($rel));

        $size = getimagesize($disk->path($rel));
        $this->assertSame(1000, $size[0]);
        $this->assertSame('image/png', $size['mime']);
    }

    public function test_profile_update_stores_agent_signature(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Tanda Tangan Agen', false);

        $this->actingAs($admin)->put(route('profile.update'), [
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
            'phone' => '08123456789',
            'signature' => \Illuminate\Http\UploadedFile::fake()->image('ttd.png', 300, 120),
        ])->assertRedirect(route('profile.edit'));

        $admin->refresh();
        $path = agent_signature_path($admin);

        $this->assertNotSame('', $path);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($path));
    }

    public function test_kwitansi_page_shows_brand_logo_stamp_and_agent_signature(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $donation->load('agen');
        \Illuminate\Support\Facades\Storage::disk('public')->put('signatures/ttd.png', 'x');
        \App\Models\Setting::set('agent_profile_' . $donation->agen->slug, json_encode([
            'photo' => '',
            'signature' => 'signatures/ttd.png',
            'intro' => '',
            'api_ss' => '',
            'api_cc' => '',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $response = $this->actingAs($admin)->get(route('donations.kwitansi', $donation));

        $response->assertOk();
        $response->assertSee('images/logo-bwa.png', false);
        $response->assertSee('images/stamp-bwa.png', false);
        $response->assertSee('/storage/signatures/ttd.png', false);
    }

    public function test_kwitansi_print_preserves_background_colors(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $response = $this->actingAs($admin)->get(route('donations.kwitansi', $donation));

        $response->assertOk();
        $response->assertSee('print-color-adjust: exact', false);
        $response->assertSee('-webkit-print-color-adjust: exact', false);
        $response->assertSee('images/logo-bwa-putih.png', false);
    }
}

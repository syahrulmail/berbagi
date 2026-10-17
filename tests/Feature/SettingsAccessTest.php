<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SettingsAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(string $role, ?Branch $branch = null): User
    {
        $data = [
            'name' => ucfirst($role) . ' ' . uniqid(),
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

    protected function makeBranch(): Branch
    {
        return Branch::create(['code' => 'ST-' . uniqid(), 'name' => 'Cabang Setelan ' . uniqid(), 'is_active' => true]);
    }

    public function test_admin_can_access_settings(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('settings.index'))
            ->assertOk();
    }

    public function test_supervisor_cannot_access_settings(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        $this->actingAs($supervisor)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($supervisor)->put(route('settings.update'), [])->assertForbidden();
    }

    public function test_settings_menu_is_hidden_for_supervisor(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        $this->actingAs($supervisor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Pengaturan')
            ->assertSee('Log Aktivitas');
    }

    public function test_settings_menu_is_shown_for_admin(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pengaturan')
            ->assertSee('Cabang dan Pengguna')
            ->assertSee('Manajemen');
    }

    public function test_branch_and_user_category_is_shown_for_supervisor(): void
    {
        $this->actingAs($this->makeUser('supervisor', $this->makeBranch()))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cabang dan Pengguna')
            ->assertSee('Pengguna')
            ->assertDontSee('Manajemen');
    }

    public function test_settings_page_shows_tracking_inputs(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Pelacakan &amp; Verifikasi', false)
            ->assertSee('name="google_site_verification"', false)
            ->assertSee('name="gtm_container_id"', false)
            ->assertSee('name="ga_measurement_id"', false)
            ->assertSee('name="head_tracking_code"', false)
            ->assertSee('name="body_tracking_code"', false);
    }

    public function test_admin_can_save_tracking_settings(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->put(route('settings.update'), [
            'wa_reminder_hour' => 9,
            'wa_public_number' => '6281234567890',
            'google_site_verification' => 'verify-token',
            'gtm_container_id' => 'GTM-ABC123',
            'ga_measurement_id' => 'G-TEST123',
            'head_tracking_code' => '<script>console.log("head")</script>',
            'body_tracking_code' => '<noscript>pixel</noscript>',
        ])->assertRedirect(route('settings.index'));

        $this->assertSame('verify-token', Setting::get('google_site_verification'));
        $this->assertSame('GTM-ABC123', Setting::get('gtm_container_id'));
        $this->assertSame('G-TEST123', Setting::get('ga_measurement_id'));
        $this->assertSame('<script>console.log("head")</script>', Setting::get('head_tracking_code'));
        $this->assertSame('<noscript>pixel</noscript>', Setting::get('body_tracking_code'));
    }

    public function test_settings_page_excludes_moved_sections(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('settings.index'))
            ->assertOk()
            ->assertDontSee('Target Global BWA Bulanan')
            ->assertDontSee('Teks Bar Atas (Trustbar)')
            ->assertDontSee('Quote di Bawah Hero')
            ->assertDontSee('Testimoni Tokoh')
            ->assertDontSee('Logo Mitra (Perusahaan/Lembaga)')
            ->assertDontSee('Otomasi WhatsApp');
    }

    public function test_admin_can_access_new_management_pages(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('settings.target'))->assertOk()->assertSee('Target &amp; Informasi', false);
        $this->actingAs($admin)->get(route('settings.testimonials'))->assertOk()->assertSee('Tambah Testimoni');
        $this->actingAs($admin)->get(route('settings.partners'))->assertOk()->assertSee('Tambah Logo');
    }

    public function test_supervisor_cannot_access_new_management_pages(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        $this->actingAs($supervisor)->get(route('settings.target'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('settings.testimonials'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('settings.partners'))->assertForbidden();
    }

    public function test_target_info_update_saves_values(): void
    {
        $this->actingAs($this->makeUser('admin'))->put(route('settings.target.update'), [
            'trustbar_text' => 'Trustbar Baru',
            'home_quote' => '<p>Quote baru</p><script>alert(1)</script>',
        ])->assertRedirect(route('settings.target'));

        $this->assertSame('Trustbar Baru', Setting::get('trustbar_text'));
        $this->assertStringContainsString('Quote baru', Setting::get('home_quote'));
        $this->assertStringNotContainsString('<script', Setting::get('home_quote'));
    }

    public function test_testimonials_update_saves_values(): void
    {
        $this->actingAs($this->makeUser('admin'))->put(route('settings.testimonials.update'), [
            'testimonials' => [
                ['text' => 'Testi pertama', 'name' => 'Tokoh Satu', 'existing_photo' => '', 'photo_remove' => '0'],
            ],
        ])->assertRedirect(route('settings.testimonials'));

        $items = json_decode(Setting::get('home_testimonials'), true);
        $this->assertSame('Testi pertama', $items[0]['text']);
        $this->assertSame('Tokoh Satu', $items[0]['name']);
    }

    public function test_partners_update_saves_values(): void
    {
        $this->actingAs($this->makeUser('admin'))->put(route('settings.partners.update'), [
            'logos' => [
                ['existing_photo' => 'partner-logos/a.png', 'photo_remove' => '0'],
            ],
        ])->assertRedirect(route('settings.partners'));

        $items = json_decode(Setting::get('home_partner_logos'), true);
        $this->assertContains('partner-logos/a.png', $items);
    }

    public function test_admin_can_access_mobile_settings_pages(): void
    {
        $admin = $this->makeUser('admin');

        foreach ([
            'mo.settings',
            'mo.settings.target',
            'mo.settings.testimonials',
            'mo.settings.partners',
        ] as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
        }
    }

    public function test_admin_can_save_mobile_general_settings(): void
    {
        $this->actingAs($this->makeUser('admin'))->put(route('mo.settings.update'), [
            'wa_public_number' => '628111222333',
            'wa_public_template' => 'Halo {program}',
            'wa_agent_template' => 'Halo {agen}',
        ])->assertRedirect(route('mo.settings'));

        $this->assertSame('628111222333', Setting::get('wa_public_number'));
        $this->assertSame('Halo {program}', Setting::get('wa_public_template'));
    }

    public function test_mobile_pengaturan_menu_visible_for_admin(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('mo.more'))
            ->assertOk()
            ->assertSee('Target &amp; Informasi', false)
            ->assertSee('Testimoni')
            ->assertSee('Mitra')
            ->assertSee('Pengaturan Sistem')
            ->assertSee('Cabang dan Pengguna');
    }

    public function test_mobile_branch_and_user_category_visible_for_supervisor(): void
    {
        $this->actingAs($this->makeUser('supervisor', $this->makeBranch()))
            ->get(route('mo.more'))
            ->assertOk()
            ->assertSee('Cabang dan Pengguna')
            ->assertSee('Pengguna')
            ->assertDontSee('Manajemen');
    }
}

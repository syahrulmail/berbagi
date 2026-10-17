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
            ->assertSee('Pengaturan');
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
}

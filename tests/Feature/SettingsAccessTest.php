<?php

namespace Tests\Feature;

use App\Models\Branch;
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
}

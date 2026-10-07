<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserManagementTest extends TestCase
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
        return Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang ' . uniqid(), 'is_active' => true]);
    }

    protected function payload(array $overrides = []): array
    {
        $username = 'new_' . uniqid();

        return array_merge([
            'name' => 'Pengguna Baru ' . uniqid(),
            'username' => $username,
            'email' => $username . '@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'agen',
        ], $overrides);
    }

    public function test_supervisor_sees_only_agents_of_own_branch(): void
    {
        $branch = $this->makeBranch();
        $otherBranch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $ownAgent = $this->makeUser('agen', $branch);
        $otherAgent = $this->makeUser('agen', $otherBranch);
        $admin = $this->makeUser('admin');

        $this->actingAs($supervisor)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee($ownAgent->name)
            ->assertDontSee($otherAgent->name)
            ->assertDontSee($admin->name);
    }

    public function test_supervisor_menu_shown_but_branch_menu_hidden(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        $this->actingAs($supervisor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pengguna')
            ->assertDontSee(route('branches.index'));
    }

    public function test_admin_sees_all_users(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee($supervisor->name)
            ->assertSee($agent->name);
    }

    public function test_supervisor_store_forces_role_and_branch(): void
    {
        $branch = $this->makeBranch();
        $otherBranch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);

        $payload = $this->payload(['role' => 'admin', 'branch_id' => $otherBranch->id]);

        $this->actingAs($supervisor)
            ->post(route('users.store'), $payload)
            ->assertRedirect(route('users.index'));

        $created = User::where('username', $payload['username'])->firstOrFail();
        $this->assertSame('agen', $created->role);
        $this->assertSame($branch->id, $created->branch_id);
    }

    public function test_supervisor_can_edit_own_agent(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($supervisor)->get(route('users.edit', $agent))->assertOk();
    }

    public function test_supervisor_cannot_edit_agent_of_other_branch(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $otherAgent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($supervisor)->get(route('users.edit', $otherAgent))->assertForbidden();
        $this->actingAs($supervisor)->put(route('users.update', $otherAgent), $this->payload())->assertForbidden();
    }

    public function test_supervisor_cannot_edit_non_agent_in_own_branch(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $peer = $this->makeUser('supervisor', $branch);

        $this->actingAs($supervisor)->get(route('users.edit', $peer))->assertForbidden();
    }

    public function test_supervisor_update_forces_role_and_branch(): void
    {
        $branch = $this->makeBranch();
        $otherBranch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($supervisor)
            ->put(route('users.update', $agent), $this->payload([
                'username' => $agent->username,
                'email' => $agent->email,
                'role' => 'admin',
                'branch_id' => $otherBranch->id,
            ]))
            ->assertRedirect(route('users.index'));

        $agent->refresh();
        $this->assertSame('agen', $agent->role);
        $this->assertSame($branch->id, $agent->branch_id);
    }

    public function test_supervisor_can_delete_own_agent(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($supervisor)
            ->delete(route('users.destroy', $agent))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $agent->id]);
    }

    public function test_supervisor_cannot_delete_agent_of_other_branch(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());
        $otherAgent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($supervisor)
            ->delete(route('users.destroy', $otherAgent))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $otherAgent->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_agent_cannot_access_user_management(): void
    {
        $agent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($agent)->get(route('users.index'))->assertForbidden();
    }

    public function test_mobile_supervisor_sees_only_own_agents(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $ownAgent = $this->makeUser('agen', $branch);
        $otherAgent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($supervisor)
            ->get(route('mo.users'))
            ->assertOk()
            ->assertSee($ownAgent->name)
            ->assertDontSee($otherAgent->name);
    }

    public function test_mobile_supervisor_scope_enforced_on_edit(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $ownAgent = $this->makeUser('agen', $branch);
        $otherAgent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($supervisor)->get(route('mo.user.edit', $ownAgent))->assertOk();
        $this->actingAs($supervisor)->get(route('mo.user.edit', $otherAgent))->assertForbidden();
    }

    public function test_mobile_supervisor_store_forces_role_and_branch(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);

        $payload = $this->payload(['role' => 'admin', 'branch_id' => $this->makeBranch()->id]);

        $this->actingAs($supervisor)
            ->post(route('mo.user.store'), $payload)
            ->assertRedirect(route('mo.users'));

        $created = User::where('username', $payload['username'])->firstOrFail();
        $this->assertSame('agen', $created->role);
        $this->assertSame($branch->id, $created->branch_id);
    }

    public function test_mobile_agent_cannot_access_user_management(): void
    {
        $agent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($agent)->get(route('mo.users'))->assertForbidden();
    }
}

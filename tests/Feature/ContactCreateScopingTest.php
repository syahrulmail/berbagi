<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactCreateScopingTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBranch(string $label): Branch
    {
        return Branch::create([
            'code' => 'BR-' . uniqid(),
            'name' => 'Cabang ' . $label . ' ' . uniqid(),
            'is_active' => true,
        ]);
    }

    protected function makeUser(string $role, ?Branch $branch = null): User
    {
        $data = [
            'name' => ucfirst($role) . ' ' . uniqid(),
            'username' => 'user_' . uniqid(),
            'slug' => 'user_' . uniqid(),
            'email' => uniqid() . '@test.local',
            'phone' => '628' . uniqid(),
            'password' => bcrypt('password'),
            'role' => $role,
        ];

        if ($branch) {
            $data['branch_id'] = $branch->id;
        }

        return User::create($data);
    }

    public function test_supervisor_create_page_shows_only_own_branch_and_agents_with_branch_selected()
    {
        $ownBranch = $this->makeBranch('Sendiri');
        $otherBranch = $this->makeBranch('Lain');

        $supervisor = $this->makeUser('supervisor', $ownBranch);
        $ownAgent = $this->makeUser('agen', $ownBranch);
        $otherAgent = $this->makeUser('agen', $otherBranch);

        $this->actingAs($supervisor)->get(route('contacts.create'))
            ->assertOk()
            ->assertSee($ownBranch->name)
            ->assertDontSee($otherBranch->name)
            ->assertSee($ownAgent->name)
            ->assertDontSee($otherAgent->name)
            ->assertSee('value="' . $ownBranch->id . '" selected', false);
    }

    public function test_agent_create_page_shows_only_own_branch_and_self_selected()
    {
        $ownBranch = $this->makeBranch('Agen');
        $otherBranch = $this->makeBranch('Lain');

        $agent = $this->makeUser('agen', $ownBranch);
        $otherAgent = $this->makeUser('agen', $otherBranch);

        $this->actingAs($agent)->get(route('contacts.create'))
            ->assertOk()
            ->assertSee($ownBranch->name)
            ->assertDontSee($otherBranch->name)
            ->assertSee($agent->name)
            ->assertDontSee($otherAgent->name)
            ->assertSee('value="' . $ownBranch->id . '" selected', false)
            ->assertSee('value="' . $agent->id . '" data-branch="' . $ownBranch->id . '" selected', false);
    }

    public function test_admin_create_page_shows_all_branches_and_agents_without_default()
    {
        $branchA = $this->makeBranch('Admin A');
        $branchB = $this->makeBranch('Admin B');

        $admin = $this->makeUser('admin');
        $agentA = $this->makeUser('agen', $branchA);
        $agentB = $this->makeUser('agen', $branchB);

        $this->actingAs($admin)->get(route('contacts.create'))
            ->assertOk()
            ->assertSee($branchA->name)
            ->assertSee($branchB->name)
            ->assertSee($agentA->name)
            ->assertSee($agentB->name)
            ->assertDontSee('value="' . $branchA->id . '" selected', false)
            ->assertDontSee('value="' . $branchB->id . '" selected', false);
    }

    public function test_supervisor_store_forces_own_branch()
    {
        $ownBranch = $this->makeBranch('Simpan');
        $otherBranch = $this->makeBranch('Lain');

        $supervisor = $this->makeUser('supervisor', $ownBranch);
        $ownAgent = $this->makeUser('agen', $ownBranch);
        $otherAgent = $this->makeUser('agen', $otherBranch);

        $phone = '62812' . random_int(1000000, 9999999);

        $this->actingAs($supervisor)->post(route('contacts.store'), [
            'tab' => 'manual',
            'name' => 'Kontak Supervisor ' . uniqid(),
            'phone' => $phone,
            'status' => 'prospect',
            'branch_id' => $otherBranch->id,
            'agen_id' => $ownAgent->id,
        ])->assertRedirect(route('contacts.index'));

        $this->assertDatabaseHas('contacts', [
            'phone' => $phone,
            'branch_id' => $ownBranch->id,
            'agen_id' => $ownAgent->id,
        ]);
    }

    public function test_supervisor_store_rejects_agent_from_other_branch()
    {
        $ownBranch = $this->makeBranch('Tolak');
        $otherBranch = $this->makeBranch('Lain');

        $supervisor = $this->makeUser('supervisor', $ownBranch);
        $otherAgent = $this->makeUser('agen', $otherBranch);

        $phone = '62813' . random_int(1000000, 9999999);

        $this->actingAs($supervisor)
            ->from(route('contacts.create'))
            ->post(route('contacts.store'), [
                'tab' => 'manual',
                'name' => 'Kontak Tolak ' . uniqid(),
                'phone' => $phone,
                'status' => 'prospect',
                'branch_id' => $otherBranch->id,
                'agen_id' => $otherAgent->id,
            ])
            ->assertSessionHasErrors('agen_id');

        $this->assertDatabaseMissing('contacts', ['phone' => $phone]);
    }
}

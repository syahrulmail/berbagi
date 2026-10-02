<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DonationRoleScopingTest extends TestCase
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

    protected function makeUser(string $role, Branch $branch = null): User
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

    protected function makeProgram(): Program
    {
        return Program::create([
            'name' => 'Program ' . uniqid(),
            'slug' => 'program-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);
    }

    protected function storePayload(Program $program, Contact $contact, int $branchId, int $agenId): array
    {
        return [
            'items' => [[
                'program_id' => $program->id,
                'amount' => 10000,
                'program_category' => 'WAP',
            ]],
            'donation_date' => now()->format('Y-m-d'),
            'branch_id' => $branchId,
            'agen_id' => $agenId,
            'contact_id' => $contact->id,
            'payment_method' => 'transfer',
        ];
    }

    public function test_supervisor_create_page_only_shows_own_branch_and_agents()
    {
        $ownBranch = $this->makeBranch('Sendiri');
        $otherBranch = $this->makeBranch('Lain');

        $supervisor = $this->makeUser('supervisor', $ownBranch);
        $ownAgent = $this->makeUser('agen', $ownBranch);
        $otherAgent = $this->makeUser('agen', $otherBranch);

        $this->actingAs($supervisor)->get(route('donations.create'))
            ->assertOk()
            ->assertSee($ownBranch->name)
            ->assertDontSee($otherBranch->name)
            ->assertSee($ownAgent->name)
            ->assertDontSee($otherAgent->name);
    }

    public function test_agent_create_page_only_shows_own_branch_and_self()
    {
        $ownBranch = $this->makeBranch('Agen');
        $otherBranch = $this->makeBranch('Lain');

        $agent = $this->makeUser('agen', $ownBranch);
        $otherAgent = $this->makeUser('agen', $otherBranch);

        $this->actingAs($agent)->get(route('donations.create'))
            ->assertOk()
            ->assertSee($ownBranch->name)
            ->assertDontSee($otherBranch->name)
            ->assertSee($agent->name)
            ->assertDontSee($otherAgent->name);
    }

    public function test_supervisor_store_rejects_agent_from_other_branch()
    {
        $ownBranch = $this->makeBranch('Sendiri');
        $otherBranch = $this->makeBranch('Lain');

        $supervisor = $this->makeUser('supervisor', $ownBranch);
        $otherAgent = $this->makeUser('agen', $otherBranch);
        $program = $this->makeProgram();
        $contact = Contact::create(['name' => 'Donatur ' . uniqid(), 'phone' => '0812000001', 'status' => 'prospect']);

        $this->actingAs($supervisor)
            ->from(route('donations.create'))
            ->post(route('donations.store'), $this->storePayload($program, $contact, $otherBranch->id, $otherAgent->id))
            ->assertSessionHasErrors('agen_id');

        $this->assertDatabaseMissing('donations', ['contact_id' => $contact->id]);
    }

    public function test_supervisor_store_forces_own_branch()
    {
        $ownBranch = $this->makeBranch('Sendiri');
        $otherBranch = $this->makeBranch('Lain');

        $supervisor = $this->makeUser('supervisor', $ownBranch);
        $ownAgent = $this->makeUser('agen', $ownBranch);
        $program = $this->makeProgram();
        $contact = Contact::create(['name' => 'Donatur ' . uniqid(), 'phone' => '0812000002', 'status' => 'prospect']);

        $this->actingAs($supervisor)
            ->post(route('donations.store'), $this->storePayload($program, $contact, $otherBranch->id, $ownAgent->id))
            ->assertRedirect(route('donations.index'));

        $this->assertDatabaseHas('donations', [
            'branch_id' => $ownBranch->id,
            'agen_id' => $ownAgent->id,
        ]);
    }

    public function test_agent_store_forces_self_and_own_branch()
    {
        $ownBranch = $this->makeBranch('Agen');
        $otherBranch = $this->makeBranch('Lain');

        $agent = $this->makeUser('agen', $ownBranch);
        $otherAgent = $this->makeUser('agen', $otherBranch);
        $program = $this->makeProgram();
        $contact = Contact::create(['name' => 'Donatur ' . uniqid(), 'phone' => '0812000003', 'status' => 'prospect']);

        $this->actingAs($agent)
            ->post(route('donations.store'), $this->storePayload($program, $contact, $otherBranch->id, $otherAgent->id))
            ->assertRedirect(route('donations.index'));

        $this->assertDatabaseHas('donations', [
            'branch_id' => $ownBranch->id,
            'agen_id' => $agent->id,
        ]);
    }

    public function test_mobile_supervisor_create_page_only_shows_own_branch()
    {
        $ownBranch = $this->makeBranch('Mobile Sendiri');
        $otherBranch = $this->makeBranch('Mobile Lain');

        $supervisor = $this->makeUser('supervisor', $ownBranch);

        $this->actingAs($supervisor)->get(route('mo.donation.create'))
            ->assertOk()
            ->assertSee($ownBranch->name)
            ->assertDontSee($otherBranch->name);
    }
}

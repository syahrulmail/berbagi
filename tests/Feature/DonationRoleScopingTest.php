<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
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

    protected function makeDonation(Branch $branch, User $agen, Contact $contact): Donation
    {
        $program = $this->makeProgram();

        $donation = Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => 15000,
            'donation_date' => now()->format('Y-m-d'),
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'created_by' => $agen->id,
        ]);

        $donation->items()->create([
            'program_id' => $program->id,
            'program_category' => 'WAP',
            'amount' => 15000,
        ]);

        return $donation;
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
        $contact = Contact::create([
            'name' => 'Donatur ' . uniqid(),
            'phone' => '0812000001',
            'status' => 'prospect',
            'branch_id' => $ownBranch->id,
        ]);

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
        $contact = Contact::create([
            'name' => 'Donatur ' . uniqid(),
            'phone' => '0812000002',
            'status' => 'prospect',
            'branch_id' => $ownBranch->id,
        ]);

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
        $contact = Contact::create([
            'name' => 'Donatur ' . uniqid(),
            'phone' => '0812000003',
            'status' => 'prospect',
            'branch_id' => $ownBranch->id,
            'agen_id' => $agent->id,
        ]);

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

    public function test_supervisor_index_shows_only_own_branch_donations()
    {
        $ownBranch = $this->makeBranch('Index Sendiri');
        $otherBranch = $this->makeBranch('Index Lain');

        $supervisor = $this->makeUser('supervisor', $ownBranch);
        $ownAgent = $this->makeUser('agen', $ownBranch);
        $otherAgent = $this->makeUser('agen', $otherBranch);

        $ownContact = Contact::create(['name' => 'Donatur Sendiri ' . uniqid(), 'phone' => '0813000001', 'status' => 'prospect']);
        $otherContact = Contact::create(['name' => 'Donatur Lain ' . uniqid(), 'phone' => '0813000002', 'status' => 'prospect']);

        $this->makeDonation($ownBranch, $ownAgent, $ownContact);
        $this->makeDonation($otherBranch, $otherAgent, $otherContact);

        $this->actingAs($supervisor)->get(route('donations.index'))
            ->assertOk()
            ->assertSee($ownContact->name)
            ->assertDontSee($otherContact->name);
    }

    public function test_agent_index_shows_only_own_donations()
    {
        $branch = $this->makeBranch('Index Agen');

        $agentA = $this->makeUser('agen', $branch);
        $agentB = $this->makeUser('agen', $branch);

        $contactA = Contact::create(['name' => 'Donatur Agen Satu ' . uniqid(), 'phone' => '0813000003', 'status' => 'prospect']);
        $contactB = Contact::create(['name' => 'Donatur Agen Dua ' . uniqid(), 'phone' => '0813000004', 'status' => 'prospect']);

        $this->makeDonation($branch, $agentA, $contactA);
        $this->makeDonation($branch, $agentB, $contactB);

        $this->actingAs($agentA)->get(route('donations.index'))
            ->assertOk()
            ->assertSee($contactA->name)
            ->assertDontSee($contactB->name);
    }

    public function test_supervisor_branch_filter_only_lists_and_selects_own_branch()
    {
        $ownBranch = $this->makeBranch('Filter Sendiri');
        $otherBranch = $this->makeBranch('Filter Lain');

        $supervisor = $this->makeUser('supervisor', $ownBranch);

        $this->actingAs($supervisor)->get(route('donations.index'))
            ->assertOk()
            ->assertSee('value="' . $ownBranch->id . '" selected', false)
            ->assertDontSee('Semua Cabang')
            ->assertDontSee($otherBranch->name);
    }

    public function test_admin_branch_filter_lists_all_branches_with_all_option()
    {
        $branchA = $this->makeBranch('Filter Admin A');
        $branchB = $this->makeBranch('Filter Admin B');

        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('donations.index'))
            ->assertOk()
            ->assertSee('Semua Cabang')
            ->assertSee($branchA->name)
            ->assertSee($branchB->name);
    }

    public function test_agent_filter_visible_for_admin_and_supervisor_only()
    {
        $branch = $this->makeBranch('Filter Agen');
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($admin)->get(route('donations.index'))
            ->assertOk()
            ->assertSee('id="filter-agen"', false)
            ->assertSee('>Agen<', false);

        $this->actingAs($supervisor)->get(route('donations.index'))
            ->assertOk()
            ->assertSee('id="filter-agen"', false)
            ->assertSee('>Agen<', false);

        $this->actingAs($agent)->get(route('donations.index'))
            ->assertOk()
            ->assertDontSee('id="filter-agen"', false);
    }

    public function test_admin_agent_options_carry_branch_marker()
    {
        $branch = $this->makeBranch('Marker');
        $admin = $this->makeUser('admin');
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($admin)->get(route('donations.index'))
            ->assertOk()
            ->assertSee('data-branch="' . $branch->id . '"', false)
            ->assertSee($agent->name);
    }

    public function test_admin_index_filters_donations_by_agent()
    {
        $branch = $this->makeBranch('Filter Agen Dua');
        $admin = $this->makeUser('admin');
        $agentA = $this->makeUser('agen', $branch);
        $agentB = $this->makeUser('agen', $branch);

        $contactA = Contact::create(['name' => 'Donatur Agen A ' . uniqid(), 'phone' => '0813000011', 'status' => 'prospect']);
        $contactB = Contact::create(['name' => 'Donatur Agen B ' . uniqid(), 'phone' => '0813000012', 'status' => 'prospect']);

        $this->makeDonation($branch, $agentA, $contactA);
        $this->makeDonation($branch, $agentB, $contactB);

        $this->actingAs($admin)->get(route('donations.index', ['agen_id' => $agentA->id]))
            ->assertOk()
            ->assertSee($contactA->name)
            ->assertDontSee($contactB->name);
    }

    public function test_agent_donation_create_only_lists_own_contacts()
    {
        $branch = $this->makeBranch('Kontak Agen');
        $agent = $this->makeUser('agen', $branch);
        $otherAgent = $this->makeUser('agen', $branch);

        $ownContact = Contact::create([
            'name' => 'Donatur Milik Sendiri ' . uniqid(),
            'phone' => '0814000001',
            'status' => 'prospect',
            'branch_id' => $branch->id,
            'agen_id' => $agent->id,
        ]);
        $otherContact = Contact::create([
            'name' => 'Donatur Milik Lain ' . uniqid(),
            'phone' => '0814000002',
            'status' => 'prospect',
            'branch_id' => $branch->id,
            'agen_id' => $otherAgent->id,
        ]);

        $this->actingAs($agent)->get(route('donations.create'))
            ->assertOk()
            ->assertSee($ownContact->name)
            ->assertDontSee($otherContact->name);
    }

    public function test_supervisor_donation_create_lists_own_branch_contacts_only()
    {
        $ownBranch = $this->makeBranch('Kontak Sup');
        $otherBranch = $this->makeBranch('Kontak Sup Lain');

        $supervisor = $this->makeUser('supervisor', $ownBranch);
        $ownAgent = $this->makeUser('agen', $ownBranch);
        $otherAgent = $this->makeUser('agen', $otherBranch);

        $ownContact = Contact::create([
            'name' => 'Donatur Cabang Sendiri ' . uniqid(),
            'phone' => '0814000003',
            'status' => 'prospect',
            'branch_id' => $ownBranch->id,
            'agen_id' => $ownAgent->id,
        ]);
        $otherContact = Contact::create([
            'name' => 'Donatur Cabang Lain ' . uniqid(),
            'phone' => '0814000004',
            'status' => 'prospect',
            'branch_id' => $otherBranch->id,
            'agen_id' => $otherAgent->id,
        ]);

        $this->actingAs($supervisor)->get(route('donations.create'))
            ->assertOk()
            ->assertSee($ownContact->name)
            ->assertDontSee($otherContact->name);
    }

    public function test_admin_donation_create_lists_all_contacts()
    {
        $branchA = $this->makeBranch('Kontak Admin A');
        $branchB = $this->makeBranch('Kontak Admin B');
        $admin = $this->makeUser('admin');
        $agentA = $this->makeUser('agen', $branchA);
        $agentB = $this->makeUser('agen', $branchB);

        $contactA = Contact::create([
            'name' => 'Donatur Admin A ' . uniqid(),
            'phone' => '0814000005',
            'status' => 'prospect',
            'branch_id' => $branchA->id,
            'agen_id' => $agentA->id,
        ]);
        $contactB = Contact::create([
            'name' => 'Donatur Admin B ' . uniqid(),
            'phone' => '0814000006',
            'status' => 'prospect',
            'branch_id' => $branchB->id,
            'agen_id' => $agentB->id,
        ]);

        $this->actingAs($admin)->get(route('donations.create'))
            ->assertOk()
            ->assertSee($contactA->name)
            ->assertSee($contactB->name);
    }

    public function test_agent_donation_edit_only_lists_own_contacts()
    {
        $branch = $this->makeBranch('Edit Kontak Agen');
        $agent = $this->makeUser('agen', $branch);
        $otherAgent = $this->makeUser('agen', $branch);

        $ownContact = Contact::create([
            'name' => 'Edit Donatur Sendiri ' . uniqid(),
            'phone' => '0814000007',
            'status' => 'prospect',
            'branch_id' => $branch->id,
            'agen_id' => $agent->id,
        ]);
        $otherContact = Contact::create([
            'name' => 'Edit Donatur Lain ' . uniqid(),
            'phone' => '0814000008',
            'status' => 'prospect',
            'branch_id' => $branch->id,
            'agen_id' => $otherAgent->id,
        ]);

        $donation = $this->makeDonation($branch, $agent, $ownContact);

        $this->actingAs($agent)->get(route('donations.edit', $donation))
            ->assertOk()
            ->assertSee($ownContact->name)
            ->assertDontSee($otherContact->name);
    }

    public function test_agent_cannot_store_donation_with_contact_of_another_agent()
    {
        $branch = $this->makeBranch('Store Kontak Agen');
        $agent = $this->makeUser('agen', $branch);
        $otherAgent = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $otherContact = Contact::create([
            'name' => 'Donatur Bukan Milik ' . uniqid(),
            'phone' => '0814000009',
            'status' => 'prospect',
            'branch_id' => $branch->id,
            'agen_id' => $otherAgent->id,
        ]);

        $this->actingAs($agent)
            ->from(route('donations.create'))
            ->post(route('donations.store'), $this->storePayload($program, $otherContact, $branch->id, $agent->id))
            ->assertSessionHasErrors('contact_id');

        $this->assertDatabaseMissing('donations', ['contact_id' => $otherContact->id]);
    }
}

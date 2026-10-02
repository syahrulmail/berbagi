<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DonationSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(string $role, Branch $branch, string $name, string $phone = null): User
    {
        return User::create([
            'name' => $name,
            'username' => 'user_' . uniqid(),
            'slug' => 'user_' . uniqid(),
            'email' => uniqid() . '@test.local',
            'phone' => $phone,
            'password' => bcrypt('password'),
            'role' => $role,
            'branch_id' => $branch->id,
        ]);
    }

    protected function makeDonation(Branch $branch, User $agen, Contact $contact): Donation
    {
        $program = Program::create([
            'name' => 'Program Cari ' . uniqid(),
            'slug' => 'program-cari-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);

        return Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => 10000,
            'donation_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'created_by' => $agen->id,
        ]);
    }

    protected function scenario(): array
    {
        $branchA = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Alpha ' . uniqid(), 'is_active' => true]);
        $branchB = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Beta ' . uniqid(), 'is_active' => true]);

        $admin = $this->makeUser('admin', $branchA, 'Admin Uji ' . uniqid());

        $agentA = $this->makeUser('agen', $branchA, 'Agen Alpha ' . uniqid(), '6281300002222');
        $agentB = $this->makeUser('agen', $branchB, 'Agen Beta ' . uniqid(), '6281300003333');

        $contactA = Contact::create([
            'name' => 'Donatur Alpha ' . uniqid(),
            'phone' => '6281200001111',
            'status' => 'donated',
            'agen_id' => $agentA->id,
            'branch_id' => $branchA->id,
        ]);

        $contactB = Contact::create([
            'name' => 'Donatur Beta ' . uniqid(),
            'phone' => '6281200009999',
            'status' => 'donated',
            'agen_id' => $agentB->id,
            'branch_id' => $branchB->id,
        ]);

        $this->makeDonation($branchA, $agentA, $contactA);
        $this->makeDonation($branchB, $agentB, $contactB);

        return compact('admin', 'branchA', 'branchB', 'agentA', 'agentB', 'contactA', 'contactB');
    }

    public function test_search_by_agent_name()
    {
        $s = $this->scenario();

        $this->actingAs($s['admin'])->get(route('donations.index', ['search' => $s['agentA']->name]))
            ->assertOk()
            ->assertSee($s['contactA']->name)
            ->assertDontSee($s['contactB']->name);
    }

    public function test_search_by_branch_name()
    {
        $s = $this->scenario();

        $this->actingAs($s['admin'])->get(route('donations.index', ['search' => $s['branchA']->name]))
            ->assertOk()
            ->assertSee($s['contactA']->name)
            ->assertDontSee($s['contactB']->name);
    }

    public function test_search_by_whatsapp_ignores_separators()
    {
        $s = $this->scenario();

        $this->actingAs($s['admin'])->get(route('donations.index', ['search' => '+62 812-0000-1111']))
            ->assertOk()
            ->assertSee($s['contactA']->name)
            ->assertDontSee($s['contactB']->name);
    }

    public function test_search_by_local_whatsapp_number()
    {
        $s = $this->scenario();

        $this->actingAs($s['admin'])->get(route('donations.index', ['search' => '0812-0000-1111']))
            ->assertOk()
            ->assertSee($s['contactA']->name)
            ->assertDontSee($s['contactB']->name);
    }

    public function test_search_by_agent_whatsapp_number()
    {
        $s = $this->scenario();

        $this->actingAs($s['admin'])->get(route('donations.index', ['search' => '0813-0000-2222']))
            ->assertOk()
            ->assertSee($s['contactA']->name)
            ->assertDontSee($s['contactB']->name);
    }
}

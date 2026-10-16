<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactIndexTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBranch(): Branch
    {
        return Branch::create([
            'code' => 'BR-' . uniqid(),
            'name' => 'Cabang Kontak ' . uniqid(),
            'is_active' => true,
        ]);
    }

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

    protected function makeDonation(Contact $contact, Branch $branch, User $agen, float $amount): Donation
    {
        $program = Program::create([
            'name' => 'Program Kontak ' . uniqid(),
            'slug' => 'program-kontak-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);

        return Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => $amount,
            'donation_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'created_by' => $agen->id,
        ]);
    }

    public function test_index_hides_whatsapp_and_branch_columns_shows_phone_branch_and_total_donation()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);

        $contact = Contact::create([
            'name' => 'Kontak Uji ' . uniqid(),
            'phone' => '6281200009999',
            'status' => 'donated',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
            'notes' => 'Catatan uji',
        ]);

        $this->makeDonation($contact, $branch, $agen, 15000);
        $this->makeDonation($contact, $branch, $agen, 25000);

        $this->actingAs($admin)
            ->get(route('contacts.index', ['search' => $contact->name]))
            ->assertOk()
            ->assertDontSee('No. WhatsApp')
            ->assertDontSee('<th>Cabang</th>', false)
            ->assertDontSee('<td>' . $contact->phone . '</td>', false)
            ->assertSee($contact->name)
            ->assertSee($contact->phone)
            ->assertSee($branch->name)
            ->assertSee('Rp 40.000');
    }

    public function test_branch_and_agen_filters_are_scoped_by_role()
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor', $branchA);
        $agentA = $this->makeUser('agen', $branchA);
        $agentB = $this->makeUser('agen', $branchB);

        $contactA = Contact::create([
            'name' => 'Kontak Cabang A ' . uniqid(),
            'phone' => '628111000001',
            'status' => 'prospect',
            'agen_id' => $agentA->id,
            'branch_id' => $branchA->id,
        ]);

        $contactB = Contact::create([
            'name' => 'Kontak Cabang B ' . uniqid(),
            'phone' => '628111000002',
            'status' => 'prospect',
            'agen_id' => $agentB->id,
            'branch_id' => $branchB->id,
        ]);

        $this->actingAs($admin)
            ->get(route('contacts.index', ['branch_id' => $branchA->id]))
            ->assertOk()
            ->assertSee($contactA->name)
            ->assertDontSee($contactB->name);

        $this->actingAs($admin)
            ->get(route('contacts.index', ['agen_id' => $agentB->id]))
            ->assertOk()
            ->assertSee($contactB->name)
            ->assertDontSee($contactA->name);

        $this->actingAs($admin)
            ->get(route('contacts.index'))
            ->assertOk()
            ->assertSee('name="branch_id"', false)
            ->assertSee('name="agen_id"', false)
            ->assertSee('data-branch="' . $branchB->id . '"', false);

        $this->actingAs($supervisor)
            ->get(route('contacts.index'))
            ->assertOk()
            ->assertSee('data-branch="' . $branchA->id . '"', false)
            ->assertDontSee('data-branch="' . $branchB->id . '"', false)
            ->assertDontSee($branchB->name);
    }

    public function test_index_shows_followup_badge_and_last_donation_date()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);

        $followupAt = now()->subDays(3);

        $contact = Contact::create([
            'name' => 'Kontak FU ' . uniqid(),
            'phone' => '6281299990001',
            'status' => 'donated',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
            'followup_count' => 2,
            'last_messaged_at' => $followupAt,
        ]);

        $this->makeDonation($contact, $branch, $agen, 10000);

        $this->actingAs($admin)
            ->get(route('contacts.index', ['search' => $contact->name]))
            ->assertOk()
            ->assertSee('FU 2x')
            ->assertSee($followupAt->format('d/m/Y'))
            ->assertSee(now()->format('d/m/Y'))
            ->assertSee('Rp 10.000');
    }

    public function test_followup_badge_caps_at_three_plus()
    {
        $this->assertSame('0x', followup_badge(0));
        $this->assertSame('1x', followup_badge(1));
        $this->assertSame('2x', followup_badge(2));
        $this->assertSame('3x', followup_badge(3));
        $this->assertSame('3+x', followup_badge(4));
        $this->assertSame('3+x', followup_badge(12));
        $this->assertSame('0x', followup_badge(null));
    }
}

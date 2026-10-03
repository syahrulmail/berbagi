<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProgramDonorsTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBranch(): Branch
    {
        return Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Donor ' . uniqid(), 'is_active' => true]);
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

    protected function makeProgram(string $name): Program
    {
        return Program::create([
            'name' => $name,
            'slug' => 'program-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);
    }

    protected function makeContact(Branch $branch, User $agen, string $name): Contact
    {
        return Contact::create([
            'name' => $name,
            'phone' => '628' . uniqid(),
            'status' => 'donated',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
        ]);
    }

    protected function makeDonation(Contact $contact, Branch $branch, User $agen, Program $program, float $amount, string $date): void
    {
        $donation = Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => $amount,
            'donation_date' => $date,
            'payment_method' => 'transfer',
            'created_by' => $agen->id,
        ]);

        $donation->items()->create([
            'program_id' => $program->id,
            'program_category' => 'WAP',
            'amount' => $amount,
        ]);
    }

    public function test_admin_sees_all_donors_with_transaction_count_and_total()
    {
        $branch1 = $this->makeBranch();
        $branch2 = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen1 = $this->makeUser('agen', $branch1);
        $agen2 = $this->makeUser('agen', $branch2);

        $program = $this->makeProgram('Program Donor Admin ' . uniqid());
        $otherProgram = $this->makeProgram('Program Lain ' . uniqid());

        $contactA = $this->makeContact($branch1, $agen1, 'Donor Alpha');
        $contactB = $this->makeContact($branch2, $agen2, 'Donor Beta');

        $this->makeDonation($contactA, $branch1, $agen1, $program, 10000, '2026-01-01');
        $this->makeDonation($contactA, $branch1, $agen1, $program, 20000, '2026-02-01');
        $this->makeDonation($contactB, $branch2, $agen2, $program, 50000, '2026-01-15');
        $this->makeDonation($contactB, $branch2, $agen2, $otherProgram, 99999, '2026-01-16');

        $response = $this->actingAs($admin)->getJson(route('programs.donors', $program));

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('program_name', $program->name);

        $html = $response->json('html');
        $this->assertStringContainsString('Donor Alpha', $html);
        $this->assertStringContainsString('Donor Beta', $html);
        $this->assertStringContainsString('2x', $html);
        $this->assertStringContainsString('Rp 30.000', $html);
        $this->assertStringContainsString('Rp 50.000', $html);
        $this->assertStringContainsString('data-contact-detail="' . $contactA->id . '"', $html);
        $this->assertStringNotContainsString('Rp 99.999', $html);
    }

    public function test_supervisor_only_sees_donors_in_own_branch()
    {
        $branch1 = $this->makeBranch();
        $branch2 = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch1);
        $agen1 = $this->makeUser('agen', $branch1);
        $agen2 = $this->makeUser('agen', $branch2);

        $program = $this->makeProgram('Program Donor Supervisor ' . uniqid());
        $contactA = $this->makeContact($branch1, $agen1, 'Donor Cabang Satu');
        $contactB = $this->makeContact($branch2, $agen2, 'Donor Cabang Dua');

        $this->makeDonation($contactA, $branch1, $agen1, $program, 10000, '2026-01-01');
        $this->makeDonation($contactB, $branch2, $agen2, $program, 20000, '2026-01-02');

        $response = $this->actingAs($supervisor)->getJson(route('programs.donors', $program));

        $response->assertOk()->assertJsonPath('count', 1);

        $html = $response->json('html');
        $this->assertStringContainsString('Donor Cabang Satu', $html);
        $this->assertStringNotContainsString('Donor Cabang Dua', $html);
    }

    public function test_agen_only_sees_own_contacts()
    {
        $branch = $this->makeBranch();
        $agen1 = $this->makeUser('agen', $branch);
        $agen2 = $this->makeUser('agen', $branch);

        $program = $this->makeProgram('Program Donor Agen ' . uniqid());
        $contactA = $this->makeContact($branch, $agen1, 'Donor Milik Agen Satu');
        $contactB = $this->makeContact($branch, $agen2, 'Donor Milik Agen Dua');

        $this->makeDonation($contactA, $branch, $agen1, $program, 10000, '2026-01-01');
        $this->makeDonation($contactB, $branch, $agen2, $program, 20000, '2026-01-02');

        $response = $this->actingAs($agen1)->getJson(route('programs.donors', $program));

        $response->assertOk()->assertJsonPath('count', 1);

        $html = $response->json('html');
        $this->assertStringContainsString('Donor Milik Agen Satu', $html);
        $this->assertStringNotContainsString('Donor Milik Agen Dua', $html);
    }

    public function test_index_makes_collected_amount_clickable_only_when_positive()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $contact = $this->makeContact($branch, $agen, 'Kontak Klik');

        $withDonation = $this->makeProgram('Program Terkumpul ' . uniqid());
        $empty = $this->makeProgram('Program Kosong ' . uniqid());

        $this->makeDonation($contact, $branch, $agen, $withDonation, 15000, '2026-01-01');

        $this->actingAs($admin)
            ->get(route('programs.index', ['search' => $withDonation->name]))
            ->assertOk()
            ->assertSee('data-program-donors="' . $withDonation->id . '"', false)
            ->assertSee('program-donors-modal', false);

        $this->actingAs($admin)
            ->get(route('programs.index', ['search' => $empty->name]))
            ->assertOk()
            ->assertDontSee('data-program-donors="' . $empty->id . '"', false);
    }
}

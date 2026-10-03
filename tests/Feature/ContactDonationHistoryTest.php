<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactDonationHistoryTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBranch(): Branch
    {
        return Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Riwayat ' . uniqid(), 'is_active' => true]);
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

    protected function makeProgram(): Program
    {
        return Program::create([
            'name' => 'Program Riwayat ' . uniqid(),
            'slug' => 'program-riwayat-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);
    }

    protected function makeDonation(Contact $contact, Branch $branch, User $agen, Program $program, float $amount, string $date): Donation
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

        return $donation;
    }

    protected function makeContact(Branch $branch, User $agen): Contact
    {
        return Contact::create([
            'name' => 'Kontak Riwayat ' . uniqid(),
            'phone' => '628' . uniqid(),
            'status' => 'donated',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_contact_detail_json_lists_donations_with_program_and_amount()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $contact = $this->makeContact($branch, $agen);

        $programA = $this->makeProgram();
        $programB = $this->makeProgram();

        $this->makeDonation($contact, $branch, $agen, $programA, 10000, '2026-01-01');
        $this->makeDonation($contact, $branch, $agen, $programB, 30000, '2026-02-01');

        $this->actingAs($admin)
            ->getJson(route('contacts.detail', $contact))
            ->assertOk()
            ->assertJsonCount(2, 'donations')
            ->assertJsonStructure([
                'donations' => [
                    ['id', 'date_formatted', 'amount_formatted', 'items' => [['category_label', 'program_name']]],
                ],
            ])
            ->assertJsonPath('donations.0.amount_formatted', 'Rp 30.000')
            ->assertJsonPath('donations.0.date_formatted', '01 Feb 2026')
            ->assertJsonFragment(['program_name' => $programB->name])
            ->assertJsonFragment(['program_name' => $programA->name]);
    }

    public function test_contact_detail_json_has_empty_donations_without_history()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $contact = $this->makeContact($branch, $agen);

        $this->actingAs($admin)
            ->getJson(route('contacts.detail', $contact))
            ->assertOk()
            ->assertJsonCount(0, 'donations');
    }

    public function test_contacts_index_includes_donation_detail_modal_and_script()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $contact = $this->makeContact($branch, $agen);

        $this->actingAs($admin)
            ->get(route('contacts.index', ['search' => $contact->name]))
            ->assertOk()
            ->assertSee('donation-detail-modal', false)
            ->assertSee('js/donation-detail.js', false);
    }
}

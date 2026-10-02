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
}

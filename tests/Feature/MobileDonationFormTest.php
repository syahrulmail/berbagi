<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MobileDonationFormTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBranch(): Branch
    {
        return Branch::create([
            'code' => 'BR-' . uniqid(),
            'name' => 'Cabang ' . uniqid(),
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

    public function test_donation_form_has_searchable_contact_and_program_inputs(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $response = $this->actingAs($agen)->get(route('mo.donation.create'));

        $response->assertOk();
        $response->assertSee('mo-contact-ac', false);
        $response->assertSee('Tambah kontak baru');
        $response->assertSee('Ketik nama program');
        $response->assertDontSee('Belum ada kontaknya');
        $response->assertSee('mo-quick-contact', false);
    }

    public function test_agent_can_quick_create_contact_from_donation_form(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $response = $this->actingAs($agen)->postJson(route('contacts.quick'), [
            'name' => 'Donatur Baru',
            'phone' => '081234567890',
            'status' => 'prospect',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('contacts', [
            'name' => 'Donatur Baru',
            'agen_id' => $agen->id,
        ]);

        $contact = Contact::where('name', 'Donatur Baru')->first();
        $this->assertNotNull($contact);
        $this->assertNotEmpty($contact->phone);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DonationDetailModalTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(string $role, Branch $branch = null): User
    {
        $data = [
            'name' => 'User ' . $role . ' ' . uniqid(),
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

    protected function makeDonation(User $admin, array $overrides = [])
    {
        $branch = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Test', 'is_active' => true]);
        $agen = $this->makeUser('agen', $branch);
        $contact = Contact::create(['name' => 'Budi Test', 'phone' => '081234567890', 'status' => 'prospect']);
        $program = Program::create(['name' => 'Program Wakaf Test', 'slug' => 'program-wakaf-' . uniqid(), 'program_category' => 'WAP', 'is_active' => true]);

        $attrs = array_merge([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => 50000,
            'donation_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'created_by' => $admin->id,
        ], $overrides['attrs'] ?? []);

        $donation = Donation::create($attrs);
        $donation->items()->create([
            'program_id' => $program->id,
            'program_category' => 'WAP',
            'amount' => 50000,
        ]);

        return [$branch, $agen, $contact, $program, $donation];
    }

    public function test_detail_endpoint_returns_expected_json()
    {
        $admin = $this->makeUser('admin');
        [$branch, $agen, $contact, $program, $donation] = $this->makeDonation($admin);

        $response = $this->actingAs($admin)
            ->getJson(route('donations.detail', $donation));

        $response->assertOk()
            ->assertJson([
                'id' => $donation->id,
                'branch' => $branch->name,
                'agen' => $agen->name,
                'contact' => 'Budi Test',
                'contact_phone' => '081234567890',
                'payment_method_label' => 'Transfer Bank',
            ])
            ->assertJsonPath('amount_formatted', 'Rp 50.000')
            ->assertJsonPath('items.0.program_name', 'Program Wakaf Test')
            ->assertJsonPath('items.0.amount_formatted', 'Rp 50.000')
            ->assertJsonPath('creator', $admin->name);
    }

    public function test_edit_fields_endpoint_returns_form_html()
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $response = $this->actingAs($admin)
            ->getJson(route('donations.edit-fields', $donation));

        $response->assertOk()->assertJsonStructure(['html']);
        $html = $response->json('html');
        $this->assertStringContainsString('id="donation-modal-form"', $html);
        $this->assertStringContainsString('name="items[0][program_id]"', $html);
        $this->assertStringContainsString('name="payment_method"', $html);
        $this->assertStringContainsString('name="contact_id"', $html);
        $this->assertStringContainsString('name="donation_date"', $html);
        $this->assertStringContainsString('id="donation-items"', $html);
    }

    public function test_update_via_ajax_returns_json()
    {
        $admin = $this->makeUser('admin');
        [$branch, $agen, , $program, $donation] = $this->makeDonation($admin);
        $contact = $donation->contact;

        $response = $this->actingAs($admin)->postJson(route('donations.update', $donation), [
            '_method' => 'PUT',
            'items' => [
                ['program_id' => $program->id, 'program_category' => 'WAP', 'amount' => 20000],
            ],
            'donation_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'contact_id' => $contact->id,
            'donor_info' => '',
            'payment_method' => 'transfer',
            'note' => 'updated via modal',
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Donasi berhasil diperbarui.']);

        $this->assertSame('20000.00', Donation::find($donation->id)->amount);
        $this->assertSame('transfer', Donation::find($donation->id)->payment_method);
    }

    public function test_update_via_ajax_returns_validation_errors()
    {
        $admin = $this->makeUser('admin');
        [$branch, $agen, $contact, , $donation] = $this->makeDonation($admin);

        $response = $this->actingAs($admin)->postJson(route('donations.update', $donation), [
            '_method' => 'PUT',
            'items' => [],
            'donation_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'contact_id' => $contact->id,
            'payment_method' => 'cash',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }
}

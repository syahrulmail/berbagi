<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DonationFormDefaultsTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBranch(): Branch
    {
        return Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Form ' . uniqid(), 'is_active' => true]);
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

    protected function makeProgram(string $name, ?string $category): Program
    {
        return Program::create([
            'name' => $name,
            'slug' => 'program-' . uniqid(),
            'program_category' => 'WAP',
            'category' => $category,
            'is_active' => true,
        ]);
    }

    protected function makeContact(): Contact
    {
        return Contact::create([
            'name' => 'Kontak Form ' . uniqid(),
            'phone' => '628' . uniqid(),
            'status' => 'prospect',
        ]);
    }

    protected function makeDonation(Branch $branch, User $agen, Contact $contact, Program $program): Donation
    {
        $donation = Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => 10000,
            'donation_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'created_by' => $agen->id,
        ]);

        $donation->items()->create([
            'program_id' => $program->id,
            'program_category' => 'WAP',
            'amount' => 10000,
        ]);

        return $donation;
    }

    public function test_create_form_only_lists_penggalangan_and_unclassified_programs()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);

        $penggalangan = $this->makeProgram('Program Penggalangan XYZ', 'penggalangan');
        $unclassified = $this->makeProgram('Program Belum Jenis XYZ', null);
        $penyaluran = $this->makeProgram('Program Penyaluran XYZ', 'penyaluran');

        $this->actingAs($admin)->get(route('donations.create'))
            ->assertOk()
            ->assertSee('Program Penggalangan XYZ')
            ->assertSee('Program Belum Jenis XYZ')
            ->assertDontSee('Program Penyaluran XYZ');
    }

    public function test_create_form_defaults_payment_date_today_and_transfer_method()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('donations.create'))
            ->assertOk()
            ->assertSee('name="payment_date" value="' . now()->toDateString() . '"', false)
            ->assertSee('value="transfer" selected', false);
    }

    public function test_edit_form_includes_payment_date_and_filters_programs()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $contact = $this->makeContact();
        $program = $this->makeProgram('Program Edit Penggalangan XYZ', 'penggalangan');
        $penyaluran = $this->makeProgram('Program Edit Penyaluran XYZ', 'penyaluran');

        $donation = $this->makeDonation($branch, $agen, $contact, $program);

        $this->actingAs($admin)->get(route('donations.edit', $donation))
            ->assertOk()
            ->assertSee('name="payment_date"', false)
            ->assertSee('Program Edit Penggalangan XYZ')
            ->assertDontSee('Program Edit Penyaluran XYZ');
    }

    public function test_edit_form_keeps_currently_selected_penyaluran_program_visible()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $contact = $this->makeContact();
        $penyaluran = $this->makeProgram('Program Lama Penyaluran XYZ', 'penyaluran');

        $donation = $this->makeDonation($branch, $agen, $contact, $penyaluran);

        $this->actingAs($admin)->get(route('donations.edit', $donation))
            ->assertOk()
            ->assertSee('Program Lama Penyaluran XYZ');
    }

    public function test_edit_modal_defaults_empty_payment_date_and_filters_programs()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $contact = $this->makeContact();
        $program = $this->makeProgram('Program Modal Penggalangan XYZ', 'penggalangan');
        $penyaluran = $this->makeProgram('Program Modal Penyaluran XYZ', 'penyaluran');

        $donation = $this->makeDonation($branch, $agen, $contact, $program);

        $response = $this->actingAs($admin)->getJson(route('donations.edit-fields', $donation))->assertOk();
        $html = $response->json('html');

        $this->assertStringContainsString('name="payment_date" value="' . now()->toDateString() . '"', $html);
        $this->assertStringContainsString('Program Modal Penggalangan XYZ', $html);
        $this->assertStringNotContainsString('Program Modal Penyaluran XYZ', $html);
    }
}

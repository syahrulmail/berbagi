<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DonationKwitansiTest extends TestCase
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
            'donor_info' => 'Info Donatur Test',
            'amount' => 50000,
            'donation_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'created_by' => $admin->id,
        ], $overrides);

        $donation = Donation::create($attrs);
        $donation->items()->create([
            'program_id' => $program->id,
            'program_category' => 'WAP',
            'amount' => 50000,
        ]);

        return [$branch, $agen, $contact, $program, $donation];
    }

    public function test_kwitansi_page_renders_mapped_donation_data(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $response = $this->actingAs($admin)->get(route('donations.kwitansi', $donation));

        $response->assertOk();
        $response->assertSee('FORMULIR WAKAF', false);
        $response->assertSee('Budi Test');
        $response->assertSee('Info Donatur Test');
        $response->assertSee('08123456****');
        $response->assertSee('Quran');
        $response->assertSee('50.000');
        $response->assertSee('Lima puluh ribu Rupiah');
        $response->assertSee('Total: Lima puluh ribu Rupiah');
        $response->assertDontSee('No. FKJ');
        $response->assertDontSee('Ttd Wakif/Pendaftar');
        $response->assertDontSee('Asli (putih)');
    }

    public function test_kwitansi_is_forbidden_for_unrelated_agen(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $otherBranch = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Lain', 'is_active' => true]);
        $otherAgen = $this->makeUser('agen', $otherBranch);

        $this->actingAs($otherAgen)
            ->get(route('donations.kwitansi', $donation))
            ->assertForbidden();
    }

    public function test_kwitansi_is_forbidden_for_other_supervisor(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $otherBranch = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Lain', 'is_active' => true]);
        $supervisor = $this->makeUser('supervisor', $otherBranch);

        $this->actingAs($supervisor)
            ->get(route('donations.kwitansi', $donation))
            ->assertForbidden();
    }

    public function test_mobile_donation_detail_exposes_kwitansi_url(): void
    {
        $admin = $this->makeUser('admin');
        [, , , , $donation] = $this->makeDonation($admin);

        $response = $this->actingAs($admin)
            ->getJson(route('mo.api.donation-detail', $donation->id));

        $response->assertOk()
            ->assertJsonPath('kwitansi_url', route('donations.kwitansi', $donation->id));
    }
}

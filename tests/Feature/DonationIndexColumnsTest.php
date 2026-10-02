<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DonationIndexColumnsTest extends TestCase
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

    public function test_index_hides_branch_and_contact_columns_and_inlines_them()
    {
        $branch = $this->makeBranch('Donasi');
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);

        $program = Program::create([
            'name' => 'Program Donasi ' . uniqid(),
            'slug' => 'program-donasi-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);

        $contact = Contact::create([
            'name' => 'Donatur Uji ' . uniqid(),
            'phone' => '6289900011122',
            'status' => 'donated',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
        ]);

        Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => 50000,
            'donation_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'created_by' => $agen->id,
        ]);

        $this->actingAs($admin)
            ->get(route('donations.index', ['search' => $contact->name]))
            ->assertOk()
            ->assertDontSee('sort-link">Cabang', false)
            ->assertDontSee('Kontak Donatur')
            ->assertSee('sort-link">Donatur', false)
            ->assertSee($contact->name)
            ->assertSee($contact->phone)
            ->assertSee($branch->name);
    }
}

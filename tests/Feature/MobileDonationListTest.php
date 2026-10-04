<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MobileDonationListTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBranch(): Branch
    {
        return Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang ' . uniqid(), 'is_active' => true]);
    }

    protected function makeUser(string $role, ?Branch $branch = null): User
    {
        $data = [
            'name' => ucfirst($role) . ' ' . uniqid(),
            'username' => 'user_' . uniqid(),
            'slug' => 'user_' . uniqid(),
            'email' => uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'role' => $role,
        ];

        if ($branch) {
            $data['branch_id'] = $branch->id;
        }

        return User::create($data);
    }

    protected function makeContact(string $name, string $phone, User $agen, Branch $branch): Contact
    {
        return Contact::create([
            'name' => $name,
            'phone' => $phone,
            'status' => 'donated',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
        ]);
    }

    protected function makeProgram(): Program
    {
        return Program::create([
            'name' => 'Program ' . uniqid(),
            'slug' => 'program-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);
    }

    protected function makeDonation(Branch $branch, User $agen, Contact $contact, Program $program, string $date, ?string $note = null): Donation
    {
        return Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => 25000,
            'donation_date' => $date,
            'payment_method' => 'transfer',
            'note' => $note,
            'created_by' => $agen->id,
        ]);
    }

    public function test_mobile_list_shows_note_and_searches_by_note(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();
        $note = 'Catatan khusus unik ' . uniqid();
        $contact = $this->makeContact('Donatur Catatan', '628111000111', $agen, $branch);

        $this->makeDonation($branch, $agen, $contact, $program, now()->format('Y-m-d'), $note);

        $response = $this->actingAs($agen)->get(route('mo.donations', ['search' => $note]));

        $response->assertOk();
        $response->assertSee($note);
        $response->assertSee('mo-row-note', false);
    }

    public function test_mobile_list_search_matches_phone_ignoring_symbols(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();
        $contact = $this->makeContact('Donatur Telp', '6281234567890', $agen, $branch);

        $this->makeDonation($branch, $agen, $contact, $program, now()->format('Y-m-d'));

        $response = $this->actingAs($agen)->get(route('mo.donations', ['search' => '+62 812-3456.7890']));

        $response->assertOk();
        $response->assertSee('Donatur Telp');
    }

    public function test_advanced_filters_are_hidden_by_default(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $response = $this->actingAs($agen)->get(route('mo.donations'));

        $response->assertOk();
        $response->assertSee('id="mo-filter-toggle"', false);
        $response->assertSee('id="mo-advanced-filters" hidden', false);
    }

    public function test_mobile_list_filters_by_date_range(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();
        $inside = $this->makeContact('Donatur Dalam Rentang', '628111222333', $agen, $branch);
        $outside = $this->makeContact('Donatur Luar Rentang', '628444555666', $agen, $branch);

        $this->makeDonation($branch, $agen, $inside, $program, now()->subDays(3)->format('Y-m-d'));
        $this->makeDonation($branch, $agen, $outside, $program, now()->subDays(40)->format('Y-m-d'));

        $response = $this->actingAs($agen)->get(route('mo.donations', [
            'from' => now()->subDays(7)->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee('Donatur Dalam Rentang');
        $response->assertDontSee('Donatur Luar Rentang');
    }
}

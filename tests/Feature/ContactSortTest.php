<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactSortTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeAdmin(): User
    {
        return User::create([
            'name' => 'Admin Sort ' . uniqid(),
            'username' => 'user_' . uniqid(),
            'slug' => 'user_' . uniqid(),
            'email' => uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    protected function makeBranch(): Branch
    {
        return Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Sort ' . uniqid(), 'is_active' => true]);
    }

    protected function makeAgen(Branch $branch, string $name): User
    {
        return User::create([
            'name' => $name,
            'username' => 'user_' . uniqid(),
            'slug' => 'user_' . uniqid(),
            'email' => uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'role' => 'agen',
            'branch_id' => $branch->id,
        ]);
    }

    protected function makeContact(string $name, string $status = 'prospect', ?int $agenId = null, ?int $branchId = null): Contact
    {
        return Contact::create([
            'name' => $name,
            'phone' => '628' . uniqid(),
            'status' => $status,
            'agen_id' => $agenId,
            'branch_id' => $branchId,
        ]);
    }

    protected function makeDonation(Contact $contact, Branch $branch, User $agen, float $amount): void
    {
        $program = Program::create([
            'name' => 'Program Sort ' . uniqid(),
            'slug' => 'program-sort-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);

        Donation::create([
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

    public function test_index_sorts_by_name()
    {
        $admin = $this->makeAdmin();
        $first = $this->makeContact('Sort Nama AAA ' . uniqid());
        $last = $this->makeContact('Sort Nama ZZZ ' . uniqid());

        $this->actingAs($admin)
            ->get(route('contacts.index', ['search' => 'Sort Nama', 'sort' => 'name', 'dir' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder([$first->name, $last->name]);

        $this->actingAs($admin)
            ->get(route('contacts.index', ['search' => 'Sort Nama', 'sort' => 'name', 'dir' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder([$last->name, $first->name]);
    }

    public function test_index_sorts_by_status()
    {
        $admin = $this->makeAdmin();
        $churned = $this->makeContact('Sort Status Alpha ' . uniqid(), 'churned');
        $prospect = $this->makeContact('Sort Status Beta ' . uniqid(), 'prospect');

        $this->actingAs($admin)
            ->get(route('contacts.index', ['search' => 'Sort Status', 'sort' => 'status', 'dir' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder([$prospect->name, $churned->name]);

        $this->actingAs($admin)
            ->get(route('contacts.index', ['search' => 'Sort Status', 'sort' => 'status', 'dir' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder([$churned->name, $prospect->name]);
    }

    public function test_index_sorts_by_agen()
    {
        $admin = $this->makeAdmin();
        $branch = $this->makeBranch();
        $agenA = $this->makeAgen($branch, 'Agen Sort AAA ' . uniqid());
        $agenZ = $this->makeAgen($branch, 'Agen Sort ZZZ ' . uniqid());

        $first = $this->makeContact('Sort Agen Satu ' . uniqid(), 'prospect', $agenA->id, $branch->id);
        $last = $this->makeContact('Sort Agen Dua ' . uniqid(), 'prospect', $agenZ->id, $branch->id);

        $this->actingAs($admin)
            ->get(route('contacts.index', ['search' => 'Sort Agen', 'sort' => 'agen', 'dir' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder([$first->name, $last->name]);
    }

    public function test_index_sorts_by_total_donation()
    {
        $admin = $this->makeAdmin();
        $branch = $this->makeBranch();
        $agen = $this->makeAgen($branch, 'Agen Donasi ' . uniqid());

        $small = $this->makeContact('Sort Donasi Kecil ' . uniqid(), 'donated', $agen->id, $branch->id);
        $large = $this->makeContact('Sort Donasi Besar ' . uniqid(), 'donated', $agen->id, $branch->id);

        $this->makeDonation($small, $branch, $agen, 5000);
        $this->makeDonation($large, $branch, $agen, 20000);

        $this->actingAs($admin)
            ->get(route('contacts.index', ['search' => 'Sort Donasi', 'sort' => 'donation', 'dir' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder([$large->name, $small->name]);

        $this->actingAs($admin)
            ->get(route('contacts.index', ['search' => 'Sort Donasi', 'sort' => 'donation', 'dir' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder([$small->name, $large->name]);
    }
}

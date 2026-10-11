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

    public function test_bottom_tab_has_quick_add_and_no_lainnya(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $response = $this->actingAs($agen)->get(route('mo.donations'));

        $response->assertOk();
        $response->assertSee('id="mo-add-tab"', false);
        $response->assertSee('Tambah Donasi');
        $response->assertSee('Tambah Kontak');
        $response->assertDontSee('Lainnya');
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

    public function test_mobile_list_can_sort_by_largest_amount(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();
        $small = $this->makeContact('Sort Kecil', '628111000111', $agen, $branch);
        $big = $this->makeContact('Sort Besar', '628222000222', $agen, $branch);

        foreach ([[$small, 10000, now()->format('Y-m-d')], [$big, 500000, now()->subDays(5)->format('Y-m-d')]] as [$contact, $amount, $date]) {
            Donation::create([
                'branch_id' => $branch->id,
                'agen_id' => $agen->id,
                'program_id' => $program->id,
                'contact_id' => $contact->id,
                'amount' => $amount,
                'donation_date' => $date,
                'payment_method' => 'transfer',
                'created_by' => $agen->id,
            ]);
        }

        $default = $this->actingAs($agen)->get(route('mo.donations'));
        $default->assertOk();
        $default->assertSeeInOrder(['Sort Kecil', 'Sort Besar']);

        $sorted = $this->actingAs($agen)->get(route('mo.donations', ['sort' => 'amount']));
        $sorted->assertOk();
        $sorted->assertSeeInOrder(['Sort Besar', 'Sort Kecil']);
    }

    public function test_mobile_admin_sees_branch_and_agent_filters(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $this->makeUser('agen', $branch);

        $response = $this->actingAs($admin)->get(route('mo.donations'));

        $response->assertOk();
        $response->assertSee('id="filter_branch"', false);
        $response->assertSee('id="filter_agen"', false);
        $response->assertSee('>Agen<', false);
    }

    public function test_mobile_filters_order_branch_and_agent_above_dates(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $this->makeUser('agen', $branch);

        $response = $this->actingAs($admin)->get(route('mo.donations'));

        $response->assertOk();
        $response->assertSeeInOrder(['>Cabang<', '>Agen<', 'Dari tanggal'], false);
    }

    public function test_mobile_supervisor_branch_preselected_and_only_own_agents(): void
    {
        $ownBranch = $this->makeBranch();
        $otherBranch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $ownBranch);
        $ownAgent = $this->makeUser('agen', $ownBranch);
        $otherAgent = $this->makeUser('agen', $otherBranch);

        $response = $this->actingAs($supervisor)->get(route('mo.donations'));

        $response->assertOk();
        $response->assertSee('id="filter_branch"', false);
        $response->assertSee('value="' . $ownBranch->id . '" selected', false);
        $response->assertSee($ownAgent->name);
        $response->assertDontSee($otherAgent->name);
        $response->assertDontSee('Semua Cabang');
    }

    public function test_mobile_agent_does_not_see_agent_filter(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $response = $this->actingAs($agen)->get(route('mo.donations'));

        $response->assertOk();
        $response->assertDontSee('id="filter_agen"', false);
    }

    public function test_mobile_list_loads_more_in_steps_of_ten(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        for ($i = 0; $i < 12; $i++) {
            $contact = $this->makeContact('Donatur ' . $i, '62811' . str_pad((string) $i, 8, '0', STR_PAD_LEFT), $agen, $branch);
            $this->makeDonation($branch, $agen, $contact, $program, now()->subDays($i)->format('Y-m-d'));
        }

        $first = $this->actingAs($agen)->get(route('mo.donations'));
        $first->assertOk();
        $first->assertSee('Tampilkan +10 Data');
        $first->assertDontSee('Donatur 11');

        $more = $this->actingAs($agen)->get(route('mo.donations', ['show' => 20]));
        $more->assertOk();
        $more->assertSee('Donatur 11');
        $more->assertDontSee('Tampilkan +10 Data');
    }
}

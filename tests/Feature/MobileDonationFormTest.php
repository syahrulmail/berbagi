<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Program;
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

    protected function makeContact(string $name, string $phone, ?User $agen = null, ?Branch $branch = null): Contact
    {
        return Contact::create([
            'name' => $name,
            'phone' => $phone,
            'status' => 'prospect',
            'agen_id' => $agen?->id,
            'branch_id' => $branch?->id,
        ]);
    }

    protected function makeProgram(string $name = null, ?string $category = 'penggalangan', bool $active = true): Program
    {
        return Program::create([
            'name' => $name ?: 'Program ' . uniqid(),
            'slug' => 'program-' . uniqid(),
            'program_category' => 'WAP',
            'category' => $category,
            'is_active' => $active,
        ]);
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
        $response->assertSee('value="transfer" selected', false);
    }

    public function test_donation_form_footer_is_not_sticky_and_uses_short_save_label(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $response = $this->actingAs($agen)->get(route('mo.donation.create'));

        $response->assertOk();
        $response->assertSee('mo-form-footer--static', false);
        $response->assertSee('Simpan</button>', false);
        $response->assertDontSee('Simpan Donasi');
    }

    public function test_donation_form_only_lists_active_penggalangan_programs(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $this->makeProgram('Program Galang Aktif XZQ', 'penggalangan', true);
        $this->makeProgram('Program Salur Aktif XZQ', 'penyaluran', true);
        $this->makeProgram('Program Galang Nonaktif XZQ', 'penggalangan', false);

        $response = $this->actingAs($agen)->get(route('mo.donation.create'));

        $response->assertOk();
        $response->assertSee('Program Galang Aktif XZQ');
        $response->assertDontSee('Program Salur Aktif XZQ');
        $response->assertDontSee('Program Galang Nonaktif XZQ');
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
    }

    public function test_contact_search_finds_by_name_and_phone(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $this->makeContact('Ahmad Fauzi', '6281234567890', $agen, $branch);

        $this->actingAs($agen)
            ->getJson(route('mo.api.contact-search', ['q' => 'Fauzi']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Ahmad Fauzi']);

        $this->actingAs($agen)
            ->getJson(route('mo.api.contact-search', ['q' => '62812-345 678']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Ahmad Fauzi']);
    }

    public function test_contact_search_is_scoped_to_current_user(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $other = $this->makeUser('agen', $branch);

        $this->makeContact('Kontak Sendiri', '628111111111', $agen, $branch);
        $this->makeContact('Kontak Orang Lain', '628222222222', $other, $branch);

        $this->actingAs($agen)
            ->getJson(route('mo.api.contact-search', ['q' => 'Kontak']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Kontak Sendiri'])
            ->assertJsonMissing(['name' => 'Kontak Orang Lain']);
    }

    public function test_donation_store_requires_contact(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $this->actingAs($agen)->post(route('mo.donation.store'), [
            'items' => [[
                'program_id' => $program->id,
                'amount' => 10000,
                'program_category' => 'WAP',
            ]],
            'donation_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'payment_method' => 'transfer',
        ])->assertSessionHasErrors('contact_id');
    }

    public function test_agent_cannot_use_other_agent_contact(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $other = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();
        $foreign = $this->makeContact('Kontak Lain', '628333333333', $other, $branch);

        $this->actingAs($agen)->post(route('mo.donation.store'), [
            'items' => [[
                'program_id' => $program->id,
                'amount' => 10000,
                'program_category' => 'WAP',
            ]],
            'donation_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'contact_id' => $foreign->id,
            'payment_method' => 'transfer',
        ])->assertSessionHasErrors('contact_id');
    }
}

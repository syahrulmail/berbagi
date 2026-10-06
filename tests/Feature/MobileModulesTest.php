<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Banner;
use App\Models\Branch;
use App\Models\CampaignTag;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\DonationItem;
use App\Models\Program;
use App\Models\User;
use App\Models\WaFollowup;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MobileModulesTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBranch(string $label = 'A'): Branch
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

    protected function makeProgram(): Program
    {
        return Program::create([
            'name' => 'Program ' . uniqid(),
            'slug' => 'program-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_all_module_pages(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $routes = [
            'mo.donations',
            'mo.whatsapp',
            'mo.whatsapp.create',
            'mo.followups',
            'mo.campaign-tags',
            'mo.campaign-tags.create',
            'mo.banners',
            'mo.banners.create',
            'mo.achievements',
            'mo.achievements.create',
            'mo.activity-logs',
            'mo.contact.import-form',
        ];

        foreach ($routes as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_supervisor_can_open_content_monitoring_but_not_admin_only(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        foreach (['mo.banners', 'mo.banners.create', 'mo.activity-logs', 'mo.whatsapp', 'mo.followups', 'mo.contact.import-form'] as $name) {
            $this->actingAs($supervisor)->get(route($name))->assertOk();
        }

        $this->actingAs($supervisor)->get(route('mo.campaign-tags'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('mo.achievements'))->assertForbidden();
    }

    public function test_agen_can_open_communication_but_not_management(): void
    {
        $agen = $this->makeUser('agen', $this->makeBranch());

        foreach (['mo.whatsapp', 'mo.whatsapp.create', 'mo.followups', 'mo.contact.import-form'] as $name) {
            $this->actingAs($agen)->get(route($name))->assertOk();
        }

        foreach (['mo.campaign-tags', 'mo.banners', 'mo.achievements', 'mo.activity-logs'] as $name) {
            $this->actingAs($agen)->get(route($name))->assertForbidden();
        }
    }

    public function test_whatsapp_message_can_be_created_and_deleted(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('mo.whatsapp.store'), [
            'phone' => '628123456789',
            'message' => 'Halo donatur',
        ])->assertRedirect(route('mo.whatsapp'));

        $message = WhatsappMessage::where('phone', '628123456789')->first();
        $this->assertNotNull($message);
        $this->assertSame(WhatsappMessage::STATUS_PENDING, $message->status);

        $this->actingAs($admin)->delete(route('mo.whatsapp.destroy', $message->id))
            ->assertRedirect(route('mo.whatsapp'));
        $this->assertNull(WhatsappMessage::find($message->id));
    }

    public function test_campaign_tags_can_be_created_in_bulk(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('mo.campaign-tags.store'), [
            'name' => 'Bantuan Ummat, Program Dai',
            'color' => '#086e66',
        ])->assertRedirect(route('mo.campaign-tags'));

        $this->assertDatabaseHas('campaign_tags', ['slug' => 'bantuan-ummat']);
        $this->assertDatabaseHas('campaign_tags', ['slug' => 'program-dai']);
    }

    public function test_banner_and_achievement_can_be_created(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('mo.banners.store'), [
            'title' => 'Banner Ramadhan',
            'type' => 'banner',
            'is_active' => '1',
        ])->assertRedirect(route('mo.banners'));

        $this->assertDatabaseHas('banners', ['title' => 'Banner Ramadhan']);

        $this->actingAs($admin)->post(route('mo.achievements.store'), [
            'value' => '1.250',
            'label' => 'Donatur Aktif',
            'is_active' => '1',
        ])->assertRedirect(route('mo.achievements'));

        $this->assertDatabaseHas('achievements', ['label' => 'Donatur Aktif']);
    }

    public function test_program_edit_and_delete_are_admin_only_on_mobile(): void
    {
        $agen = $this->makeUser('agen', $this->makeBranch());
        $program = $this->makeProgram();

        $this->actingAs($agen)->get(route('mo.program.edit', $program->id))->assertForbidden();
        $this->actingAs($agen)->delete(route('mo.program.destroy', $program->id))->assertForbidden();

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get(route('mo.program.edit', $program->id))->assertOk();
    }

    public function test_contact_paste_import_creates_contacts(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('mo.contact.paste'), [
            'paste_lines' => "Nama,WhatsApp,Status\nNama Satu,081234567890,Prospek\nNama Dua,081234567891,Wakif",
        ])->assertRedirect(route('mo.contacts'));

        $this->assertDatabaseHas('contacts', ['name' => 'Nama Satu']);
        $this->assertDatabaseHas('contacts', ['name' => 'Nama Dua']);
    }

    public function test_program_donors_json_is_scoped_by_role(): void
    {
        $branchA = $this->makeBranch('A');
        $branchB = $this->makeBranch('B');
        $agenA = $this->makeUser('agen', $branchA);
        $agenB = $this->makeUser('agen', $branchB);
        $program = $this->makeProgram();

        $this->makeDonation($branchA, $agenA, $agenA, $program, 'Donor A');
        $this->makeDonation($branchB, $agenB, $agenB, $program, 'Donor B');

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->getJson(route('mo.program.donors', $program->id))
            ->assertOk()
            ->assertJson(['count' => 2]);
    }

    public function test_contact_list_shows_total_and_last_donation(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $contact = Contact::create([
            'name' => 'Donatur Rekap',
            'phone' => '628123000111',
            'status' => 'donated',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
        ]);

        foreach ([['2026-01-05', 25000], ['2026-02-10', 17500]] as [$date, $amount]) {
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

        $response = $this->actingAs($agen)->get(route('mo.contacts'));

        $response->assertOk();
        $response->assertSee('Rp 42.500');
        $response->assertSee('Terakhir 10 Feb 2026');
    }

    public function test_contact_list_has_sticky_search_and_hidden_status_filters(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $response = $this->actingAs($agen)->get(route('mo.contacts'));

        $response->assertOk();
        $response->assertSee('mo-sticky-filter', false);
        $response->assertSee('data-filter-toggle="mo-contact-filters"', false);
        $response->assertSee('id="mo-contact-filters" hidden', false);
    }

    public function test_contact_search_ignores_phone_separators(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $contact = Contact::create([
            'name' => 'Kontak WA ' . uniqid(),
            'phone' => '628123456789',
            'status' => 'prospect',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($agen)->get(route('mo.contacts', ['search' => '+62 812-3456-789']));

        $response->assertOk();
        $response->assertSee($contact->name);
        $response->assertSee('628123456789');
    }

    public function test_contact_form_footer_is_not_sticky_and_uses_short_save_label(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $response = $this->actingAs($agen)->get(route('mo.contact.create'));

        $response->assertOk();
        $response->assertSee('mo-form-footer--static', false);
        $response->assertSee('Simpan</button>', false);
        $response->assertDontSee('Simpan Kontak');
        $response->assertDontSee('Simpan Perubahan');
    }

    public function test_contact_form_branch_options_are_scoped_by_role(): void
    {
        $branchA = $this->makeBranch('A');
        $branchB = $this->makeBranch('B');
        $supervisor = $this->makeUser('supervisor', $branchA);

        $response = $this->actingAs($supervisor)->get(route('mo.contact.create'));

        $response->assertOk();
        $response->assertSee($branchA->name);
        $response->assertDontSee($branchB->name);

        $adminResponse = $this->actingAs($this->makeUser('admin'))->get(route('mo.contact.create'));

        $adminResponse->assertOk();
        $adminResponse->assertSee($branchA->name);
        $adminResponse->assertSee($branchB->name);
    }

    public function test_contact_list_can_sort_by_donation_total(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $small = Contact::create([
            'name' => 'Kontak Donasi Kecil ' . uniqid(),
            'phone' => '62812' . random_int(1000000, 9999999),
            'status' => 'donated',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
        ]);
        $large = Contact::create([
            'name' => 'Kontak Donasi Besar ' . uniqid(),
            'phone' => '62812' . random_int(1000000, 9999999),
            'status' => 'donated',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
        ]);

        foreach ([[$small, 5000], [$large, 120000]] as [$contact, $amount]) {
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

        $response = $this->actingAs($agen)->get(route('mo.contacts', ['sort' => 'donation']));

        $response->assertOk();
        $response->assertSeeInOrder([$large->name, $small->name]);
    }

    public function test_contact_filters_show_all_status_labels_and_horizontal_scroll(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $response = $this->actingAs($agen)->get(route('mo.contacts'));

        $response->assertOk();
        $response->assertSee('mo-segmented--scroll', false);
        $response->assertSee('value="donation"', false);
        foreach (['Semua', 'Donatur', 'Simpan', 'Prospek', 'Stop'] as $label) {
            $response->assertSee($label);
        }
    }

    public function test_contact_detail_json_lists_donation_history(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $contact = Contact::create([
            'name' => 'Kontak Riwayat ' . uniqid(),
            'phone' => '62812' . random_int(1000000, 9999999),
            'status' => 'donated',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
        ]);

        Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => 30000,
            'donation_date' => '2026-03-07',
            'payment_method' => 'transfer',
            'created_by' => $agen->id,
        ]);

        $response = $this->actingAs($agen)->getJson(route('mo.api.contact-detail', $contact->id));

        $response->assertOk()
            ->assertJsonPath('donations.0.date', '07/03/26')
            ->assertJsonPath('donations.0.category', 'Quran')
            ->assertJsonPath('donations.0.amount_formatted', 'Rp 30.000')
            ->assertJsonPath('donations.0.program_name', $program->name);
    }

    public function test_dashboard_shows_recorded_totals_and_monthly_trend(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $this->makeProgramDonation($branch, $agen, $program, 50000, now()->toDateString());
        $this->makeProgramDonation($branch, $agen, $program, 25000, now()->toDateString());
        $this->makeProgramDonation($branch, $agen, $program, 40000, now()->subMonth()->startOfMonth()->toDateString());

        $response = $this->actingAs($agen)->get(route('mo.dashboard'));

        $response->assertOk();
        $response->assertSee('Total Donasi Tercatat');
        $response->assertSee('mo-hero-amount--sm', false);
        $response->assertSee('Rp 115.000');
        $response->assertSee('Rp 75.000');
        $response->assertSee('3 Transaksi dari 3 Donatur (seluruh data tercatat)');
        $response->assertSee('2 Transaksi dari 2 Donatur (bulan ini)');
        $response->assertSee('Total Donasi Hari Ini');
        $response->assertSee('2 Transaksi dari 2 Donatur (Hari ini)');
        $response->assertDontSee('mo-stats', false);
        $response->assertSee('Tren Bulan ini');
        $response->assertSee('mo-trend-h', false);
        $response->assertDontSee('Tren 7 Hari');

        $tomorrow = now()->addDay();
        if ($tomorrow->month === now()->month) {
            $response->assertDontSee($tomorrow->format('d/m'));
        }
    }

    public function test_dashboard_cards_are_collapsible_and_recent_shows_ten(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        for ($i = 0; $i < 12; $i++) {
            $this->makeProgramDonation($branch, $agen, $program, 1000, now()->toDateString());
        }

        $response = $this->actingAs($agen)->get(route('mo.dashboard'));

        $response->assertOk();
        $response->assertSee('mo-card--list', false);
        $response->assertSee('data-filter-toggle="mo-trend-body"', false);
        $response->assertSee('data-filter-toggle="mo-recent-body"', false);
        $response->assertSee('id="mo-trend-body" class="mo-collapse-body" hidden', false);
        $response->assertSee('id="mo-recent-body" class="mo-collapse-body mo-list" hidden', false);
        $this->assertSame(10, substr_count($response->getContent(), 'data-donation-detail'));
    }

    protected function makeNamedProgram(string $name): Program
    {
        return Program::create([
            'name' => $name,
            'slug' => 'program-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);
    }

    protected function makeProgramDonation(Branch $branch, User $agen, Program $program, float $amount, string $date): Donation
    {
        $contact = Contact::create([
            'name' => 'Kontak Program ' . uniqid(),
            'phone' => '62812' . random_int(1000000, 9999999),
            'status' => 'donated',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
        ]);

        $donation = Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => $amount,
            'donation_date' => $date,
            'payment_method' => 'transfer',
            'created_by' => $agen->id,
        ]);

        DonationItem::create([
            'donation_id' => $donation->id,
            'program_id' => $program->id,
            'program_category' => 'WAP',
            'amount' => $amount,
        ]);

        return $donation;
    }

    public function test_program_list_hides_progress_and_shows_donation_count(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $this->makeProgramDonation($branch, $agen, $program, 50000, '2026-01-05');
        $this->makeProgramDonation($branch, $agen, $program, 30000, '2026-02-10');

        $response = $this->actingAs($agen)->get(route('mo.programs'));

        $response->assertOk();
        $response->assertDontSee('mo-progress-track', false);
        $response->assertDontSee('mo-progress-fill', false);
        $response->assertDontSee('Target Rp');
        $response->assertSee('Rp 80.000');
        $response->assertSee('dari 2 donasi');
    }

    public function test_program_card_urls_follow_role(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $agentUrl = route('public.agent-program', ['agentSlug' => $agen->slug, 'program' => $program->slug]);

        $response = $this->actingAs($agen)->get(route('mo.programs'));

        $response->assertOk();
        $response->assertSee('data-program-url="' . $agentUrl . '"', false);
        $response->assertSee('data-program-share="' . $agentUrl . '"', false);

        $publicUrl = route('public.program', $program->slug);

        $adminResponse = $this->actingAs($this->makeUser('admin'))->get(route('mo.programs'));

        $adminResponse->assertOk();
        $adminResponse->assertSee('data-program-url="' . $publicUrl . '"', false);
        $adminResponse->assertSee('data-program-share="' . $publicUrl . '"', false);
    }

    public function test_program_list_can_filter_by_category(): void
    {
        $agen = $this->makeUser('agen', $this->makeBranch());

        $gali = Program::create([
            'name' => 'Penggalangan ' . uniqid(),
            'slug' => 'gali-' . uniqid(),
            'program_category' => 'WAP',
            'category' => 'penggalangan',
            'is_active' => true,
        ]);
        $salur = Program::create([
            'name' => 'Penyaluran ' . uniqid(),
            'slug' => 'salur-' . uniqid(),
            'program_category' => 'WAP',
            'category' => 'penyaluran',
            'is_active' => true,
        ]);
        $default = Program::create([
            'name' => 'Tanpa Jenis ' . uniqid(),
            'slug' => 'default-' . uniqid(),
            'program_category' => 'WAP',
            'category' => null,
            'is_active' => true,
        ]);

        $all = $this->actingAs($agen)->get(route('mo.programs'));
        $all->assertOk();
        $all->assertSee($gali->name);
        $all->assertSee($salur->name);
        $all->assertSee($default->name);

        $galiOnly = $this->actingAs($agen)->get(route('mo.programs', ['jenis' => 'penggalangan']));
        $galiOnly->assertOk();
        $galiOnly->assertSee($gali->name);
        $galiOnly->assertSee($default->name);
        $galiOnly->assertDontSee($salur->name);

        $salurOnly = $this->actingAs($agen)->get(route('mo.programs', ['jenis' => 'penyaluran']));
        $salurOnly->assertOk();
        $salurOnly->assertSee($salur->name);
        $salurOnly->assertDontSee($gali->name);
        $salurOnly->assertDontSee($default->name);
    }

    public function test_program_donation_total_is_scoped_by_role(): void
    {
        $branch = $this->makeBranch();
        $agenA = $this->makeUser('agen', $branch);
        $agenB = $this->makeUser('agen', $branch);
        $program = $this->makeNamedProgram('Program Scope ' . uniqid());

        $this->makeProgramDonation($branch, $agenA, $program, 40000, '2026-02-01');
        $this->makeProgramDonation($branch, $agenB, $program, 90000, '2026-02-02');

        $response = $this->actingAs($agenA)->get(route('mo.programs'));

        $response->assertOk();
        $response->assertSee('Rp 40.000');
        $response->assertSee('dari 1 donasi');
        $response->assertDontSee('Rp 130.000');
    }

    public function test_program_date_range_filters_donation_period(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeNamedProgram('Program Periode ' . uniqid());

        $this->makeProgramDonation($branch, $agen, $program, 25000, '2026-01-15');
        $this->makeProgramDonation($branch, $agen, $program, 70000, '2026-02-20');

        $response = $this->actingAs($agen)->get(route('mo.programs', [
            'from' => '2026-02-01',
            'to' => '2026-02-28',
        ]));

        $response->assertOk();
        $response->assertSee('Rp 70.000');
        $response->assertSee('dari 1 donasi');
        $response->assertDontSee('Rp 95.000');
    }

    public function test_program_list_can_sort_by_donation_total(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $small = $this->makeNamedProgram('Program Kecil ' . uniqid());
        $large = $this->makeNamedProgram('Program Besar ' . uniqid());

        $this->makeProgramDonation($branch, $agen, $small, 10000, '2026-02-01');
        $this->makeProgramDonation($branch, $agen, $large, 200000, '2026-02-02');

        $response = $this->actingAs($agen)->get(route('mo.programs', ['sort' => 'donation']));

        $response->assertOk();
        $response->assertSeeInOrder([$large->name, $small->name]);
    }

    public function test_program_list_can_search_and_has_sticky_hidden_filters(): void
    {
        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);

        $target = $this->makeNamedProgram('PencarianKhusus ' . uniqid());
        $this->makeNamedProgram('Program Lain ' . uniqid());

        $response = $this->actingAs($agen)->get(route('mo.programs', ['search' => 'PencarianKhusus']));

        $response->assertOk();
        $response->assertSee($target->name);
        $response->assertSee('mo-sticky-filter', false);
        $response->assertSee('data-filter-toggle="mo-program-filters"', false);
        $response->assertSee('id="mo-program-filters" hidden', false);
    }

    protected function makeDonation(Branch $branch, User $agen, User $contactOwner, Program $program, string $name): void
    {
        $contact = Contact::create([
            'name' => $name,
            'phone' => '62812' . random_int(1000000, 9999999),
            'status' => 'donated',
            'agen_id' => $contactOwner->id,
            'branch_id' => $branch->id,
        ]);

        $donation = Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => 25000,
            'donation_date' => now()->format('Y-m-d'),
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'created_by' => $agen->id,
        ]);

        $donation->items()->create([
            'program_id' => $program->id,
            'program_category' => 'WAP',
            'amount' => 25000,
        ]);
    }
}

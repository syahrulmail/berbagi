<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Banner;
use App\Models\Branch;
use App\Models\CampaignTag;
use App\Models\Contact;
use App\Models\Donation;
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

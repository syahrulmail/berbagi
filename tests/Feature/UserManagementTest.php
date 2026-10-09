<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseTransactions;

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

    protected function makeBranch(): Branch
    {
        return Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang ' . uniqid(), 'is_active' => true]);
    }

    protected function payload(array $overrides = []): array
    {
        $username = 'new_' . uniqid();

        return array_merge([
            'name' => 'Pengguna Baru ' . uniqid(),
            'username' => $username,
            'email' => $username . '@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'agen',
            'phone' => '628123456789',
        ], $overrides);
    }

    public function test_supervisor_sees_only_agents_of_own_branch(): void
    {
        $branch = $this->makeBranch();
        $otherBranch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $ownAgent = $this->makeUser('agen', $branch);
        $otherAgent = $this->makeUser('agen', $otherBranch);
        $admin = $this->makeUser('admin');

        $this->actingAs($supervisor)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee($ownAgent->name)
            ->assertDontSee($otherAgent->name)
            ->assertDontSee($admin->name);
    }

    public function test_supervisor_menu_shown_but_branch_menu_hidden(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        $this->actingAs($supervisor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pengguna')
            ->assertDontSee(route('branches.index'));
    }

    public function test_admin_sees_all_users(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee($supervisor->name)
            ->assertSee($agent->name);
    }

    public function test_users_index_displays_api_wa_status_column(): void
    {
        Cache::flush();

        Http::fake([
            'https://api.starsender.online/*' => Http::response(['success' => true], 200),
            'https://app.cloudchat.id/*' => Http::response(['success' => true], 200),
        ]);

        $admin = $this->makeUser('admin');
        $connected = $this->makeUser('agen', $this->makeBranch());

        Setting::set('agent_profile_' . $connected->slug, json_encode([
            'photo' => '',
            'intro' => '',
            'api_ss' => 'SS-OK-' . uniqid(),
            'api_cc' => 'CC-OK-' . uniqid(),
        ]));

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('API WA')
            ->assertSee('API SS: Terkoneksi')
            ->assertSee('API CC: Terkoneksi')
            ->assertSee('API SS: Belum diisi');
    }

    public function test_users_index_marks_unconnected_api_keys_as_failed(): void
    {
        Cache::flush();

        Http::fake([
            'https://api.starsender.online/*' => Http::response(['success' => false], 200),
            'https://app.cloudchat.id/*' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $admin = $this->makeUser('admin');
        $broken = $this->makeUser('agen', $this->makeBranch());

        Setting::set('agent_profile_' . $broken->slug, json_encode([
            'photo' => '',
            'intro' => '',
            'api_ss' => 'SS-BAD-' . uniqid(),
            'api_cc' => 'CC-BAD-' . uniqid(),
        ]));

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('API SS: Tidak terkoneksi')
            ->assertSee('API CC: Tidak terkoneksi');
    }

    public function test_supervisor_store_forces_role_and_branch(): void
    {
        $branch = $this->makeBranch();
        $otherBranch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);

        $payload = $this->payload(['role' => 'admin', 'branch_id' => $otherBranch->id]);

        $this->actingAs($supervisor)
            ->post(route('users.store'), $payload)
            ->assertRedirect(route('users.index'));

        $created = User::where('username', $payload['username'])->firstOrFail();
        $this->assertSame('agen', $created->role);
        $this->assertSame($branch->id, $created->branch_id);
    }

    public function test_supervisor_can_edit_own_agent(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($supervisor)->get(route('users.edit', $agent))->assertOk();
    }

    public function test_supervisor_cannot_edit_agent_of_other_branch(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $otherAgent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($supervisor)->get(route('users.edit', $otherAgent))->assertForbidden();
        $this->actingAs($supervisor)->put(route('users.update', $otherAgent), $this->payload())->assertForbidden();
    }

    public function test_supervisor_cannot_edit_non_agent_in_own_branch(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $peer = $this->makeUser('supervisor', $branch);

        $this->actingAs($supervisor)->get(route('users.edit', $peer))->assertForbidden();
    }

    public function test_supervisor_update_forces_role_and_branch(): void
    {
        $branch = $this->makeBranch();
        $otherBranch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($supervisor)
            ->put(route('users.update', $agent), $this->payload([
                'username' => $agent->username,
                'email' => $agent->email,
                'role' => 'admin',
                'branch_id' => $otherBranch->id,
            ]))
            ->assertRedirect(route('users.index'));

        $agent->refresh();
        $this->assertSame('agen', $agent->role);
        $this->assertSame($branch->id, $agent->branch_id);
    }

    public function test_supervisor_can_delete_own_agent(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($supervisor)
            ->delete(route('users.destroy', $agent))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $agent->id]);
    }

    public function test_supervisor_cannot_delete_agent_of_other_branch(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());
        $otherAgent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($supervisor)
            ->delete(route('users.destroy', $otherAgent))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $otherAgent->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_agent_cannot_access_user_management(): void
    {
        $agent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($agent)->get(route('users.index'))->assertForbidden();
    }

    public function test_mobile_supervisor_sees_only_own_agents(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $ownAgent = $this->makeUser('agen', $branch);
        $otherAgent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($supervisor)
            ->get(route('mo.users'))
            ->assertOk()
            ->assertSee($ownAgent->name)
            ->assertDontSee($otherAgent->name);
    }

    public function test_mobile_supervisor_scope_enforced_on_edit(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $ownAgent = $this->makeUser('agen', $branch);
        $otherAgent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($supervisor)->get(route('mo.user.edit', $ownAgent))->assertOk();
        $this->actingAs($supervisor)->get(route('mo.user.edit', $otherAgent))->assertForbidden();
    }

    public function test_mobile_supervisor_store_forces_role_and_branch(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);

        $payload = $this->payload(['role' => 'admin', 'branch_id' => $this->makeBranch()->id]);

        $this->actingAs($supervisor)
            ->post(route('mo.user.store'), $payload)
            ->assertRedirect(route('mo.users'));

        $created = User::where('username', $payload['username'])->firstOrFail();
        $this->assertSame('agen', $created->role);
        $this->assertSame($branch->id, $created->branch_id);
    }

    public function test_mobile_agent_cannot_access_user_management(): void
    {
        $agent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($agent)->get(route('mo.users'))->assertForbidden();
    }

    public function test_supervisor_more_hides_banner_label(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        $this->actingAs($supervisor)
            ->get(route('mo.more'))
            ->assertOk()
            ->assertDontSee('Banner & Label')
            ->assertSee('Log Aktivitas')
            ->assertSee('Pengguna');
    }

    public function test_admin_more_shows_banner_label(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('mo.more'))
            ->assertOk()
            ->assertSee('Banner & Label');
    }

    public function test_supervisor_cannot_access_banner_page(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        $this->actingAs($supervisor)->get(route('mo.banners'))->assertForbidden();
    }

    public function test_admin_can_access_banner_page(): void
    {
        $this->actingAs($this->makeUser('admin'))->get(route('mo.banners'))->assertOk();
    }

    public function test_supervisor_sees_add_agent_in_sheet_on_users_page(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        $this->actingAs($supervisor)
            ->get(route('mo.users'))
            ->assertOk()
            ->assertSee('Tambah Agen');
    }

    public function test_add_agent_hidden_off_users_page(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        $this->actingAs($supervisor)
            ->get(route('mo.more'))
            ->assertOk()
            ->assertDontSee('Tambah Agen');
    }

    public function test_add_agent_form_preselects_supervisor_branch(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);

        $this->actingAs($supervisor)
            ->get(route('mo.user.create'))
            ->assertOk()
            ->assertSee('value="' . $branch->id . '" selected', false);
    }

    public function test_mobile_user_form_has_photo_field_and_required_labels(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($supervisor)
            ->get(route('mo.user.create'))
            ->assertOk()
            ->assertSee('Foto Profil')
            ->assertSee('name="photo"', false)
            ->assertSee('<span class="req">*</span>', false)
            ->assertSee('enctype="multipart/form-data"', false);

        $this->actingAs($supervisor)
            ->get(route('mo.user.edit', $agent))
            ->assertOk()
            ->assertSee('Foto Profil')
            ->assertSee('name="photo"', false);
    }

    public function test_mobile_user_store_requires_phone(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);

        $payload = $this->payload(['role' => 'agen', 'branch_id' => $branch->id]);
        unset($payload['phone']);

        $this->actingAs($supervisor)
            ->post(route('mo.user.store'), $payload)
            ->assertSessionHasErrors('phone');
    }

    public function test_mobile_supervisor_can_upload_agent_photo(): void
    {
        Storage::fake('public');
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);

        $payload = $this->payload([
            'role' => 'agen',
            'branch_id' => $branch->id,
            'photo' => UploadedFile::fake()->image('agent.jpg'),
        ]);

        $this->actingAs($supervisor)
            ->post(route('mo.user.store'), $payload)
            ->assertRedirect(route('mo.users'));

        $agent = User::where('username', $payload['username'])->firstOrFail();
        $stored = json_decode(Setting::get('agent_profile_' . $agent->slug, '{}'), true);
        $this->assertNotEmpty($stored['photo'] ?? '');
        Storage::disk('public')->assertExists($stored['photo']);
    }

    public function test_mobile_users_list_shows_profile_photo(): void
    {
        Storage::fake('public');
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $path = 'agents/photo-' . uniqid() . '.jpg';
        Setting::set('agent_profile_' . $agent->slug, json_encode(['photo' => $path, 'intro' => '']));

        $this->actingAs($supervisor)
            ->get(route('mo.users'))
            ->assertOk()
            ->assertSee(asset_photo_url($path));
    }

    public function test_photo_field_card_is_first(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        foreach ([route('mo.user.create'), route('mo.user.edit', $agent)] as $url) {
            $html = $this->actingAs($supervisor)->get($url)->assertOk()->getContent();
            $this->assertLessThan(
                mb_strpos($html, 'Identitas'),
                mb_strpos($html, 'Foto Profil'),
                'Kartu Foto Profil harus berada sebelum Identitas.'
            );
        }
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

    public function test_user_detail_json_includes_donation_stats(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        foreach ([150000, 50000] as $amount) {
            Donation::create([
                'branch_id' => $branch->id,
                'agen_id' => $agent->id,
                'program_id' => $program->id,
                'amount' => $amount,
                'donation_date' => now()->toDateString(),
                'payment_method' => 'transfer',
                'created_by' => $supervisor->id,
            ]);
        }

        $this->actingAs($supervisor)
            ->get(route('mo.api.user-detail', $agent))
            ->assertOk()
            ->assertJson([
                'id' => $agent->id,
                'name' => $agent->name,
                'donation_count' => 2,
                'donation_total_formatted' => 'Rp 200.000',
                'can_edit' => true,
            ])
            ->assertJsonPath('edit_url', route('mo.user.edit', $agent));
    }

    public function test_supervisor_cannot_view_other_branch_user_detail(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());
        $otherAgent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($supervisor)
            ->get(route('mo.api.user-detail', $otherAgent))
            ->assertForbidden();
    }

    public function test_agent_cannot_view_user_detail(): void
    {
        $agent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($agent)
            ->get(route('mo.api.user-detail', $agent))
            ->assertForbidden();
    }

    public function test_users_list_opens_detail_sheet(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($supervisor)
            ->get(route('mo.users'))
            ->assertOk()
            ->assertSee('data-user-detail="' . $agent->id . '"', false)
            ->assertSee('mo-user-sheet', false)
            ->assertDontSee(route('mo.user.edit', $agent));
    }

    protected function makeContact(Branch $branch, User $agent, array $overrides = []): Contact
    {
        return Contact::create(array_merge([
            'name' => 'Donor ' . uniqid(),
            'phone' => '628' . random_int(100000000, 999999999),
            'status' => Contact::STATUS_DONATED,
            'agen_id' => $agent->id,
            'branch_id' => $branch->id,
        ], $overrides));
    }

    protected function makeDonation(Branch $branch, User $agent, User $creator, Program $program, float $amount, ?Contact $contact = null, ?string $date = null): Donation
    {
        return Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agent->id,
            'program_id' => $program->id,
            'contact_id' => $contact ? $contact->id : null,
            'amount' => $amount,
            'donation_date' => $date ?: now()->toDateString(),
            'payment_method' => 'transfer',
            'created_by' => $creator->id,
        ]);
    }

    public function test_users_list_shows_donation_summary(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $donorA = $this->makeContact($branch, $agent);
        $donorB = $this->makeContact($branch, $agent);
        $this->makeDonation($branch, $agent, $supervisor, $program, 100000, $donorA);
        $this->makeDonation($branch, $agent, $supervisor, $program, 100000, $donorB);

        $this->actingAs($supervisor)
            ->get(route('mo.users'))
            ->assertOk()
            ->assertSee('Rp 200.000')
            ->assertSee('Dari 2 transaksi - 2 Donatur');
    }

    public function test_users_list_shows_active_and_inactive_status(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $this->makeUser('agen', $branch);
        $inactive = $this->makeUser('agen', $branch);
        $inactive->forceFill(['is_active' => false])->save();

        $this->actingAs($admin)
            ->get(route('mo.users'))
            ->assertOk()
            ->assertSee('mo-row-donation--stack', false)
            ->assertSee('>Aktif</div>', false)
            ->assertSee('>Nonaktif</div>', false);
    }

    public function test_users_search_by_name_and_phone_ignores_symbols(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $target = $this->makeUser('agen', $branch);
        $other = $this->makeUser('agen', $branch);
        $target->forceFill(['name' => 'Zulkifli Ramadhan', 'phone' => '628123456789'])->save();
        $other->forceFill(['name' => 'Budi Santoso', 'phone' => '628999999999'])->save();

        $this->actingAs($admin)->get(route('mo.users', ['search' => 'Zulkifli']))
            ->assertOk()->assertSee('Zulkifli Ramadhan')->assertDontSee('Budi Santoso');

        $this->actingAs($admin)->get(route('mo.users', ['search' => '+62 812-3456-789']))
            ->assertOk()->assertSee('Zulkifli Ramadhan')->assertDontSee('Budi Santoso');
    }

    public function test_users_branch_filter_only_for_admin(): void
    {
        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agentA = $this->makeUser('agen', $branchA);
        $agentB = $this->makeUser('agen', $branchB);

        $this->actingAs($admin)->get(route('mo.users', ['branch_id' => $branchA->id]))
            ->assertOk()->assertSee($agentA->name)->assertDontSee($agentB->name);

        $this->actingAs($admin)->get(route('mo.users'))
            ->assertOk()->assertSee('name="branch_id"', false);

        $supervisor = $this->makeUser('supervisor', $branchA);
        $this->actingAs($supervisor)->get(route('mo.users', ['branch_id' => $branchB->id]))
            ->assertOk()->assertDontSee('name="branch_id"', false);
    }

    public function test_users_search_bar_matches_contact_style(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('mo.users'))
            ->assertOk()
            ->assertSee('placeholder="Cari nama / nomor HP..."', false)
            ->assertSee('data-filter-toggle="mo-user-filters"', false)
            ->assertSee('fa-sliders', false)
            ->assertDontSee('Terapkan');
    }

    public function test_users_date_range_filters_donation_totals(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $this->makeDonation($branch, $agent, $supervisor, $program, 300000, null, '2026-01-15');
        $this->makeDonation($branch, $agent, $supervisor, $program, 700000, null, '2026-03-15');

        $this->actingAs($supervisor)
            ->get(route('mo.users', ['from' => '2026-03-01', 'to' => '2026-03-31']))
            ->assertOk()
            ->assertSee('Rp 700.000')
            ->assertSee('Dari 1 transaksi - 0 Donatur');
    }

    public function test_users_sorted_by_largest_donation(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $big = $this->makeUser('agen', $branch);
        $small = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $this->makeDonation($branch, $big, $supervisor, $program, 900000);
        $this->makeDonation($branch, $small, $supervisor, $program, 100000);

        $html = $this->actingAs($supervisor)
            ->get(route('mo.users', ['sort' => 'donation']))
            ->assertOk()->getContent();

        $this->assertLessThan(
            mb_strpos($html, $small->name),
            mb_strpos($html, $big->name),
            'Agen dengan donasi terbesar harus tampil lebih dulu.'
        );
    }

    public function test_user_detail_json_includes_public_profile_url(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        $this->actingAs($supervisor)
            ->get(route('mo.api.user-detail', $agent))
            ->assertOk()
            ->assertJsonPath('public_url', route('public.agent', $agent->slug));
    }

    public function test_mobile_user_form_uses_plain_non_sticky_save_button(): void
    {
        $branch = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch);
        $agent = $this->makeUser('agen', $branch);

        foreach ([route('mo.user.create'), route('mo.user.edit', $agent)] as $url) {
            $this->actingAs($supervisor)->get($url)
                ->assertOk()
                ->assertSee('mo-form-footer mo-form-footer--static', false)
                ->assertSee('Simpan</button>', false)
                ->assertDontSee('Simpan Perubahan');
        }
    }

    public function test_desktop_user_store_saves_integration_keys(): void
    {
        $admin = $this->makeUser('admin');

        $payload = $this->payload([
            'api_ss' => 'SS-STORE-1',
            'api_cc' => 'CC-STORE-1',
        ]);

        $this->actingAs($admin)->post(route('users.store'), $payload)
            ->assertRedirect(route('users.index'));

        $created = User::where('username', $payload['username'])->firstOrFail();
        $profile = json_decode(Setting::get('agent_profile_' . $created->slug, '{}'), true);
        $this->assertSame('SS-STORE-1', $profile['api_ss']);
        $this->assertSame('CC-STORE-1', $profile['api_cc']);
    }

    public function test_desktop_create_form_has_photo_upload_below_phone(): void
    {
        $admin = $this->makeUser('admin');

        $html = $this->actingAs($admin)->get(route('users.create'))->assertOk()->getContent();

        $this->assertStringContainsString('Foto Profil', $html);
        $this->assertStringContainsString('name="photo"', $html);
        $this->assertStringContainsString('Teks Sambutan', $html);
        $this->assertStringContainsString('name="intro"', $html);
        $this->assertLessThan(
            mb_strpos($html, 'name="photo"'),
            mb_strpos($html, 'name="phone"'),
            'Foto Profil harus berada di bawah No. WhatsApp.'
        );
        $this->assertLessThan(
            mb_strpos($html, 'name="intro"'),
            mb_strpos($html, 'name="photo"'),
            'Teks Sambutan harus berada di bawah Foto Profil.'
        );
    }

    public function test_desktop_user_store_saves_photo(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('admin');

        $payload = $this->payload([
            'photo' => UploadedFile::fake()->image('avatar.jpg'),
            'intro' => 'Sambutan pengguna baru',
        ]);

        $this->actingAs($admin)->post(route('users.store'), $payload)
            ->assertRedirect(route('users.index'));

        $created = User::where('username', $payload['username'])->firstOrFail();
        $profile = json_decode(Setting::get('agent_profile_' . $created->slug, '{}'), true);
        $this->assertNotEmpty($profile['photo']);
        Storage::disk('public')->assertExists($profile['photo']);
        $this->assertSame('Sambutan pengguna baru', $profile['intro']);
    }

    public function test_desktop_user_update_saves_integration_keys(): void
    {
        $admin = $this->makeUser('admin');
        $agent = $this->makeUser('agen', $this->makeBranch());

        $this->actingAs($admin)->put(route('users.update', $agent), $this->payload([
            'username' => $agent->username,
            'email' => $agent->email,
            'api_ss' => 'SS-UPD-1',
            'api_cc' => 'CC-UPD-1',
        ]))->assertRedirect(route('users.index'));

        $profile = json_decode(Setting::get('agent_profile_' . $agent->fresh()->slug, '{}'), true);
        $this->assertSame('SS-UPD-1', $profile['api_ss']);
        $this->assertSame('CC-UPD-1', $profile['api_cc']);
    }

    public function test_user_forms_show_integration_fields_above_active(): void
    {
        $admin = $this->makeUser('admin');
        $agent = $this->makeUser('agen', $this->makeBranch());

        $urls = [
            route('users.create'),
            route('users.edit', $agent),
            route('mo.user.create'),
            route('mo.user.edit', $agent),
        ];

        foreach ($urls as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('API SS', $html);
            $this->assertStringContainsString('API CC', $html);
            $this->assertLessThan(
                mb_strpos($html, 'name="is_active"'),
                mb_strpos($html, 'name="api_ss"'),
                'Field API SS harus berada di atas checklist Aktif pada ' . $url
            );
        }
    }

    public function test_mobile_user_store_and_update_saves_integration_keys(): void
    {
        $admin = $this->makeUser('admin');

        $payload = $this->payload([
            'api_ss' => 'SS-MO-1',
            'api_cc' => 'CC-MO-1',
        ]);

        $this->actingAs($admin)->post(route('mo.user.store'), $payload)
            ->assertRedirect(route('mo.users'));

        $created = User::where('username', $payload['username'])->firstOrFail();
        $profile = json_decode(Setting::get('agent_profile_' . $created->slug, '{}'), true);
        $this->assertSame('SS-MO-1', $profile['api_ss']);
        $this->assertSame('CC-MO-1', $profile['api_cc']);

        $this->actingAs($admin)->put(route('mo.user.update', $created->id), $this->payload([
            'username' => $created->username,
            'email' => $created->email,
            'api_ss' => 'SS-MO-2',
            'api_cc' => 'CC-MO-2',
        ]))->assertRedirect(route('mo.users'));

        $profile = json_decode(Setting::get('agent_profile_' . $created->fresh()->slug, '{}'), true);
        $this->assertSame('SS-MO-2', $profile['api_ss']);
        $this->assertSame('CC-MO-2', $profile['api_cc']);
    }
}

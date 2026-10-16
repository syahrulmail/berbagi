<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactDuplicateScopingTest extends TestCase
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

    protected function makeContact(string $name, string $phone, User $agen, Branch $branch, ?string $notes = null): Contact
    {
        return Contact::create([
            'name' => $name,
            'phone' => $phone,
            'status' => Contact::STATUS_PROSPECT,
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
            'notes' => $notes,
        ]);
    }

    public function test_store_blocks_duplicate_phone_for_same_agent(): void
    {
        $branch = $this->makeBranch('Sama');
        $agen = $this->makeUser('agen', $branch);
        $phone = '62812' . random_int(1000000, 9999999);
        $this->makeContact('Kontak Lama', $phone, $agen, $branch);

        $this->actingAs($agen)
            ->from(route('contacts.create'))
            ->post(route('contacts.store'), [
                'tab' => 'manual',
                'name' => 'Kontak Baru',
                'phone' => $phone,
                'status' => 'prospect',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertSame(1, Contact::where('phone', $phone)->count());
    }

    public function test_store_allows_same_phone_for_different_agent(): void
    {
        $branch = $this->makeBranch('Beda');
        $agenA = $this->makeUser('agen', $branch);
        $agenB = $this->makeUser('agen', $branch);
        $phone = '62813' . random_int(1000000, 9999999);
        $this->makeContact('Kontak Agen A', $phone, $agenA, $branch);

        $this->actingAs($agenB)
            ->post(route('contacts.store'), [
                'tab' => 'manual',
                'name' => 'Kontak Agen B',
                'phone' => $phone,
                'status' => 'prospect',
            ])
            ->assertRedirect(route('contacts.index'));

        $this->assertSame(2, Contact::where('phone', $phone)->count());
        $this->assertDatabaseHas('contacts', ['phone' => $phone, 'agen_id' => $agenB->id]);
    }

    public function test_quick_store_allows_same_phone_for_different_agent(): void
    {
        $branch = $this->makeBranch('Quick');
        $agenA = $this->makeUser('agen', $branch);
        $agenB = $this->makeUser('agen', $branch);
        $phone = '62814' . random_int(1000000, 9999999);
        $this->makeContact('Kontak Agen A', $phone, $agenA, $branch);

        $this->actingAs($agenB)
            ->postJson(route('contacts.quick'), [
                'name' => 'Kontak Agen B',
                'phone' => $phone,
                'status' => 'prospect',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('contacts', ['phone' => $phone, 'agen_id' => $agenB->id]);
    }

    public function test_paste_import_allows_same_phone_for_different_agent(): void
    {
        $branch = $this->makeBranch('Import');
        $admin = $this->makeUser('admin');
        $agenA = $this->makeUser('agen', $branch);
        $agenB = $this->makeUser('agen', $branch);
        $phone = '62815' . random_int(1000000, 9999999);

        $paste = "Cabang,Agen,Nama,No WhatsApp,Status,Catatan\n"
            . ",{$agenA->name},Nama A,{$phone},Prospek,catatan A\n"
            . ",{$agenB->name},Nama B,{$phone},Prospek,catatan B\n";

        $this->actingAs($admin)
            ->post(route('contacts.paste'), ['paste_lines' => $paste])
            ->assertRedirect(route('contacts.index'));

        $this->assertSame(2, Contact::where('phone', $phone)->count());
    }

    public function test_paste_import_blocks_same_phone_for_same_agent(): void
    {
        $branch = $this->makeBranch('Import Sama');
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $phone = '62816' . random_int(1000000, 9999999);

        $paste = "Cabang,Agen,Nama,No WhatsApp,Status,Catatan\n"
            . ",{$agen->name},Nama A,{$phone},Prospek,catatan A\n"
            . ",{$agen->name},Nama B,{$phone},Prospek,catatan B\n";

        $this->actingAs($admin)
            ->from(route('contacts.create'))
            ->post(route('contacts.paste'), ['paste_lines' => $paste])
            ->assertSessionHasErrors('import');

        $this->assertSame(0, Contact::where('phone', $phone)->count());
    }

    public function test_desktop_index_search_matches_notes(): void
    {
        $branch = $this->makeBranch('Catatan');
        $agen = $this->makeUser('agen', $branch);
        $this->makeContact('Budi Santoso', '62817' . random_int(1000000, 9999999), $agen, $branch, 'kenalan dari masjid Al-Ikhlas');

        $this->actingAs($agen)
            ->get(route('contacts.index', ['search' => 'Al-Ikhlas']))
            ->assertOk()
            ->assertSee('Budi Santoso');
    }

    public function test_mobile_contact_list_search_matches_notes(): void
    {
        $branch = $this->makeBranch('Catatan Mobile');
        $agen = $this->makeUser('agen', $branch);
        $this->makeContact('Siti Aminah', '62818' . random_int(1000000, 9999999), $agen, $branch, 'referensi dari pengajian subuh');

        $this->actingAs($agen)
            ->get(route('mo.contacts', ['search' => 'pengajian subuh']))
            ->assertOk()
            ->assertSee('Siti Aminah');
    }
}

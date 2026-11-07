<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactImportService;
use App\Services\WhatsAppSenderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InternationalPhoneTest extends TestCase
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
            'username' => 'user_' . uniqid(),
            'slug' => 'user_' . uniqid(),
            'email' => uniqid() . '@test.local',
            'phone' => '628' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => $role,
        ];

        if ($branch) {
            $data['branch_id'] = $branch->id;
        }

        return User::create($data);
    }

    public function test_normalize_wa_phone_keeps_indonesian_rules(): void
    {
        $this->assertSame('6281234567890', normalize_wa_phone('081234567890'));
        $this->assertSame('6281234567890', normalize_wa_phone('81234567890'));
        $this->assertSame('6281234567890', normalize_wa_phone('+62 812-3456-7890'));
        $this->assertSame('6281234567890', normalize_wa_phone('6281234567890'));
    }

    public function test_normalize_wa_phone_accepts_foreign_numbers(): void
    {
        $this->assertSame('60123456789', normalize_wa_phone('+60 12-345 6789'));
        $this->assertSame('6591234567', normalize_wa_phone('+65 9123 4567'));
        $this->assertSame('14155552671', normalize_wa_phone('+1 (415) 555-2671'));
        $this->assertSame('60123456789', normalize_wa_phone('0060123456789'));
    }

    public function test_normalize_wa_phone_rejects_invalid_input(): void
    {
        $this->assertNull(normalize_wa_phone(''));
        $this->assertNull(normalize_wa_phone('abc'));
        $this->assertNull(normalize_wa_phone('123'));
        $this->assertNull(normalize_wa_phone('6281234567890123456'));
        $this->assertNull(normalize_wa_phone('62812<987'));
    }

    public function test_import_service_uses_shared_normalization(): void
    {
        $service = new ContactImportService();

        $this->assertSame('60123456789', $service->normalizePhone('+60 12-345 6789'));
        $this->assertSame('6281234567890', $service->normalizePhone('081234567890'));
        $this->assertNull($service->normalizePhone('not-a-number'));
    }

    public function test_sender_normalization_supports_foreign_numbers(): void
    {
        $service = new WhatsAppSenderService();

        $this->assertSame('6281234567890', $service->normalizePhone('081234567890'));
        $this->assertSame('60123456789', $service->normalizePhone('+60123456789'));
        $this->assertSame('60123456789', $service->normalizePhone('0060123456789'));
    }

    public function test_store_accepts_foreign_phone_number(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('contacts.store'), [
                'tab' => 'manual',
                'name' => 'Kontak Malaysia',
                'phone' => '+60 12-345 6789',
                'status' => 'prospect',
            ])
            ->assertRedirect(route('contacts.index'));

        $this->assertDatabaseHas('contacts', [
            'name' => 'Kontak Malaysia',
            'phone' => '60123456789',
        ]);
    }

    public function test_quick_store_accepts_foreign_phone_number(): void
    {
        $branch = $this->makeBranch('Quick');
        $agen = $this->makeUser('agen', $branch);

        $this->actingAs($agen)
            ->postJson(route('contacts.quick'), [
                'name' => 'Kontak Singapura',
                'phone' => '+65 9123 4567',
                'status' => 'prospect',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('contacts', ['phone' => '6591234567']);
    }

    public function test_duplicate_detection_applies_to_foreign_numbers(): void
    {
        $branch = $this->makeBranch('Dup');
        $agen = $this->makeUser('agen', $branch);

        Contact::create([
            'name' => 'Kontak Lama',
            'phone' => '60123456789',
            'status' => 'prospect',
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($agen)
            ->from(route('contacts.create'))
            ->post(route('contacts.store'), [
                'tab' => 'manual',
                'name' => 'Kontak Baru',
                'phone' => '+60 12-345 6789',
                'status' => 'prospect',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertSame(1, Contact::where('phone', '60123456789')->count());
    }

    public function test_paste_import_accepts_foreign_numbers(): void
    {
        $admin = $this->makeUser('admin');

        $paste = "Cabang,Agen,Nama,No WhatsApp,Status,Catatan\n"
            . ",,Tamu Malaysia,+60 12-345 6789,Prospek,catatan\n"
            . ",,Tamu Singapura,+65 9123 4567,Prospek,catatan\n";

        $this->actingAs($admin)
            ->post(route('contacts.paste'), ['paste_lines' => $paste])
            ->assertRedirect(route('contacts.index'));

        $this->assertDatabaseHas('contacts', ['phone' => '60123456789']);
        $this->assertDatabaseHas('contacts', ['phone' => '6591234567']);
    }
}

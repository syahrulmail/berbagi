<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicDashboardTest extends TestCase
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

    protected function makeProgram(): Program
    {
        return Program::create([
            'name' => 'Program ' . uniqid(),
            'slug' => 'program-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);
    }

    protected function makeAgen(Branch $branch): User
    {
        return User::create([
            'name' => 'Agen ' . uniqid(),
            'username' => 'agen_' . uniqid(),
            'slug' => 'agen_' . uniqid(),
            'email' => 'agen_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'role' => 'agen',
            'branch_id' => $branch->id,
        ]);
    }

    protected function makeDonation(Branch $branch, User $agen, Program $program, string $name, string $phone): Contact
    {
        $contact = Contact::create([
            'name' => $name,
            'phone' => $phone,
            'status' => 'donated',
            'agen_id' => $agen->id,
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

        return $contact;
    }

    protected function validToken(): string
    {
        return encrypt(json_encode(['exp' => now()->addHours(6)->timestamp]));
    }

    public function test_without_token_shows_password_form(): void
    {
        Setting::set('lapdfbwa_password', Hash::make('rahasia'));

        $response = $this->get(route('lapdfbwa.show'));

        $response->assertOk();
        $response->assertSee('Laporan DFBWA');
        $response->assertSee('name="password"', false);
        $response->assertDontSee('Tren Periode');
    }

    public function test_wrong_password_is_rejected(): void
    {
        Setting::set('lapdfbwa_password', Hash::make('rahasia'));

        $response = $this->from(route('lapdfbwa.show'))->post(route('lapdfbwa.auth'), [
            'password' => 'salah',
        ]);

        $response->assertRedirect(route('lapdfbwa.show'));
        $response->assertSessionHas('error');

        $this->followingRedirects()
            ->from(route('lapdfbwa.show'))
            ->post(route('lapdfbwa.auth'), ['password' => 'salah'])
            ->assertSee('Password salah');
    }

    public function test_correct_password_redirects_with_token(): void
    {
        Setting::set('lapdfbwa_password', Hash::make('rahasia'));

        $response = $this->post(route('lapdfbwa.auth'), ['password' => 'rahasia']);

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString('mo/lapdfbwa', $location);
        $this->assertStringContainsString('t=', $location);
    }

    public function test_valid_token_shows_dashboard_and_hides_contact_phone(): void
    {
        Setting::set('lapdfbwa_password', Hash::make('rahasia'));

        $branch = $this->makeBranch();
        $agen = $this->makeAgen($branch);
        $program = $this->makeProgram();
        $contact = $this->makeDonation($branch, $agen, $program, 'Donatur Rahasia', '6281999888777');

        $response = $this->get(route('lapdfbwa.show', ['t' => $this->validToken()]));

        $response->assertOk();
        $response->assertSee('Tren Periode');
        $response->assertSee('Donatur Rahasia');
        $response->assertDontSee('6281999888777');
    }

    public function test_dashboard_keeps_token_in_filter_form(): void
    {
        Setting::set('lapdfbwa_password', Hash::make('rahasia'));

        $token = $this->validToken();

        $response = $this->get(route('lapdfbwa.show', ['t' => $token]));

        $response->assertOk();
        $response->assertSee('name="t" value="' . $token . '"', false);
    }

    public function test_invalid_token_shows_password_form(): void
    {
        Setting::set('lapdfbwa_password', Hash::make('rahasia'));

        $response = $this->get(route('lapdfbwa.show', ['t' => 'token-ngawur']));

        $response->assertOk();
        $response->assertDontSee('Tren Periode');
        $response->assertSee('name="password"', false);
    }

    public function test_expired_token_shows_password_form(): void
    {
        Setting::set('lapdfbwa_password', Hash::make('rahasia'));

        $expired = encrypt(json_encode(['exp' => now()->subHour()->timestamp]));

        $response = $this->get(route('lapdfbwa.show', ['t' => $expired]));

        $response->assertOk();
        $response->assertDontSee('Tren Periode');
    }

    public function test_no_appbar_or_tabbar_on_public_page(): void
    {
        Setting::set('lapdfbwa_password', Hash::make('rahasia'));

        $response = $this->get(route('lapdfbwa.show', ['t' => $this->validToken()]));

        $response->assertOk();
        $response->assertDontSee('mo-tabbar', false);
        $response->assertDontSee('mo-appbar', false);
    }

    public function test_admin_can_set_and_clear_public_password(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin_' . uniqid(),
            'slug' => 'admin_' . uniqid(),
            'email' => 'admin_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->put(route('settings.update'), [
            'wa_reminder_hour' => 9,
            'wa_public_number' => '6281234567890',
            'lapdfbwa_password' => 'rahasia123',
        ])->assertRedirect(route('settings.index'));

        $hash = Setting::get('lapdfbwa_password');
        $this->assertNotEmpty($hash);
        $this->assertNotSame('rahasia123', $hash);
        $this->assertTrue(Hash::check('rahasia123', $hash));

        $this->actingAs($admin)->put(route('settings.update'), [
            'wa_reminder_hour' => 9,
            'wa_public_number' => '6281234567890',
            'lapdfbwa_password_clear' => 1,
        ])->assertRedirect(route('settings.index'));

        $this->assertSame('', Setting::get('lapdfbwa_password'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DonationNoteColumnTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBranch(): Branch
    {
        return Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Catatan ' . uniqid(), 'is_active' => true]);
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

    protected function makeContact(): Contact
    {
        return Contact::create([
            'name' => 'Donatur Catatan ' . uniqid(),
            'phone' => '628' . uniqid(),
            'status' => 'donated',
        ]);
    }

    protected function makeProgram(): Program
    {
        return Program::create([
            'name' => 'Program Catatan ' . uniqid(),
            'slug' => 'program-catatan-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);
    }

    protected function makeDonation(Branch $branch, User $agen, Contact $contact, Program $program, ?string $note): Donation
    {
        return Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => 25000,
            'donation_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'note' => $note,
            'created_by' => $agen->id,
        ]);
    }

    public function test_note_column_is_shown_next_to_nominal()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $contact = $this->makeContact();
        $program = $this->makeProgram();
        $note = 'Catatan donasi penting ' . uniqid();

        $this->makeDonation($branch, $agen, $contact, $program, $note);

        $response = $this->actingAs($admin)
            ->get(route('donations.index', ['search' => $contact->name]))
            ->assertOk();

        $html = $response->getContent();
        $nominalPos = strpos($html, 'Nominal');
        $catatanPos = strpos($html, '>Catatan<');
        $aksiPos = strpos($html, '>Aksi<');

        $this->assertNotFalse($catatanPos, 'Header Catatan tidak ditemukan.');
        $this->assertGreaterThan($nominalPos, $catatanPos, 'Kolom Catatan harus di kanan kolom Nominal.');
        $this->assertLessThan($aksiPos, $catatanPos, 'Kolom Catatan harus di kiri kolom Aksi.');

        $response->assertSee($note);
    }

    public function test_empty_note_renders_dash()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $contact = $this->makeContact();
        $program = $this->makeProgram();

        $this->makeDonation($branch, $agen, $contact, $program, null);

        $this->actingAs($admin)
            ->get(route('donations.index', ['search' => $contact->name]))
            ->assertOk()
            ->assertSee('>Catatan<', false)
            ->assertDontSee('note-cell', false);
    }
}

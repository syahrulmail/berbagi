<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DonationDownloadTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(string $role, Branch $branch = null): User
    {
        $data = [
            'name' => 'User ' . $role . ' ' . uniqid(),
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

    protected function makeDonation(User $admin)
    {
        $branch = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Test', 'is_active' => true]);
        $agen = $this->makeUser('agen', $branch);
        $contact = Contact::create(['name' => 'Budi Test', 'phone' => '081234567890', 'status' => 'prospect']);
        $program = Program::create(['name' => 'Program Wakaf Test', 'slug' => 'program-wakaf-' . uniqid(), 'program_category' => 'WAP', 'is_active' => true]);

        $donation = Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => 50000,
            'donation_date' => now()->format('Y-m-d'),
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'created_by' => $admin->id,
        ]);

        $donation->items()->create([
            'program_id' => $program->id,
            'program_category' => 'WAP',
            'amount' => 50000,
        ]);

        return [$branch, $donation];
    }

    public function test_download_button_is_rendered_on_donations_index()
    {
        $admin = $this->makeUser('admin');
        $this->makeDonation($admin);

        $response = $this->actingAs($admin)->get(route('donations.index'));

        $response->assertOk()
            ->assertSee('Download Donasi')
            ->assertSee('donation-download-modal', false)
            ->assertSee('data-donation-download-open', false)
            ->assertSee(route('donations.download'), false);
    }

    public function test_create_and_edit_forms_have_payment_date_field()
    {
        $admin = $this->makeUser('admin');
        [, $donation] = $this->makeDonation($admin);

        $this->actingAs($admin)->get(route('donations.create'))
            ->assertOk()
            ->assertSee('name="payment_date"', false);

        $json = $this->actingAs($admin)->getJson(route('donations.edit-fields', $donation))
            ->assertOk();

        $this->assertStringContainsString('name="payment_date"', $json->json('html'));
    }

    public function test_download_returns_xlsx_with_expected_headers()
    {
        $admin = $this->makeUser('admin');
        [$branch, $donation] = $this->makeDonation($admin);

        $response = $this->actingAs($admin)->get(route('donations.download', [
            'branch_ids' => [$branch->id],
            'from' => now()->subDay()->format('Y-m-d'),
            'to' => now()->addDay()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $file = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($file);
        $this->assertSame('PK', substr((string) file_get_contents($file), 0, 2));
    }

    public function test_download_rejects_invalid_date_range()
    {
        $admin = $this->makeUser('admin');
        $this->makeDonation($admin);

        $response = $this->actingAs($admin)->get(route('donations.download', [
            'from' => now()->format('Y-m-d'),
            'to' => now()->subDays(3)->format('Y-m-d'),
        ]));

        $response->assertSessionHasErrors('to');
    }

    public function test_download_modal_lists_only_own_branch_for_supervisor()
    {
        $branchA = Branch::create(['code' => 'A-' . uniqid(), 'name' => 'Cabang A', 'is_active' => true]);
        $branchB = Branch::create(['code' => 'B-' . uniqid(), 'name' => 'Cabang B', 'is_active' => true]);

        $supervisor = $this->makeUser('supervisor', $branchA);

        $this->actingAs($supervisor)->get(route('donations.index'))
            ->assertOk()
            ->assertSee('name="branch_ids[]" value="' . $branchA->id . '"', false)
            ->assertDontSee('name="branch_ids[]" value="' . $branchB->id . '"', false);
    }

    public function test_agent_download_other_branch_is_forbidden()
    {
        $branchA = Branch::create(['code' => 'A-' . uniqid(), 'name' => 'Cabang A', 'is_active' => true]);
        $branchB = Branch::create(['code' => 'B-' . uniqid(), 'name' => 'Cabang B', 'is_active' => true]);

        $agent = $this->makeUser('agen', $branchA);

        $this->actingAs($agent)->get(route('donations.download', [
            'branch_ids' => [$branchB->id],
        ]))->assertForbidden();
    }

    public function test_supervisor_can_download_own_branch()
    {
        $branch = Branch::create(['code' => 'S-' . uniqid(), 'name' => 'Cabang Sendiri', 'is_active' => true]);

        $supervisor = $this->makeUser('supervisor', $branch);

        $this->actingAs($supervisor)->get(route('donations.download', [
            'branch_ids' => [$branch->id],
        ]))->assertOk();
    }

    public function test_agent_download_contains_only_own_donations()
    {
        $branch = Branch::create(['code' => 'AG-' . uniqid(), 'name' => 'Cabang Agen', 'is_active' => true]);
        $program = Program::create(['name' => 'Program Agen', 'slug' => 'program-agen-' . uniqid(), 'program_category' => 'WAP', 'is_active' => true]);

        $agentA = $this->makeUser('agen', $branch);
        $agentB = $this->makeUser('agen', $branch);

        $contactA = Contact::create(['name' => 'Kontak Agen Satu', 'phone' => '0811000001', 'status' => 'prospect']);
        $contactB = Contact::create(['name' => 'Kontak Agen Dua', 'phone' => '0811000002', 'status' => 'prospect']);

        foreach ([[$agentA, $contactA, 10000], [$agentB, $contactB, 20000]] as [$agent, $contact, $amount]) {
            $donation = Donation::create([
                'branch_id' => $branch->id,
                'agen_id' => $agent->id,
                'program_id' => $program->id,
                'contact_id' => $contact->id,
                'amount' => $amount,
                'donation_date' => now()->format('Y-m-d'),
                'payment_date' => now()->format('Y-m-d'),
                'payment_method' => 'transfer',
                'created_by' => $agent->id,
            ]);

            $donation->items()->create([
                'program_id' => $program->id,
                'program_category' => 'WAP',
                'amount' => $amount,
            ]);
        }

        $response = $this->actingAs($agentA)->get(route('donations.download'));
        $response->assertOk();

        $file = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive();
        $zip->open($file);
        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('Kontak Agen Satu', $sheet);
        $this->assertStringNotContainsString('Kontak Agen Dua', $sheet);
    }
}

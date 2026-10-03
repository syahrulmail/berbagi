<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactDownloadTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeBranch(): Branch
    {
        return Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Kontak ' . uniqid(), 'is_active' => true]);
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

    protected function makeContact(string $name, Branch $branch, User $agen, string $status = 'prospect'): Contact
    {
        return Contact::create([
            'name' => $name,
            'phone' => '628' . uniqid(),
            'status' => $status,
            'agen_id' => $agen->id,
            'branch_id' => $branch->id,
            'notes' => 'Catatan ' . $name,
        ]);
    }

    protected function sheetContent($response): string
    {
        $file = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($file);
        $this->assertSame('PK', substr((string) file_get_contents($file), 0, 2));

        $zip = new \ZipArchive();
        $zip->open($file);
        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        return $sheet;
    }

    public function test_download_button_is_rendered_next_to_add_contact()
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('contacts.index'))
            ->assertOk()
            ->assertSee('Download Kontak')
            ->assertSee('contacts/download', false);
    }

    public function test_download_returns_xlsx_with_expected_headers()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $this->makeContact('Kontak Unduh Satu', $branch, $agen);

        $response = $this->actingAs($admin)->get(route('contacts.download'));

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $sheet = $this->sheetContent($response);
        $this->assertStringContainsString('Nama', $sheet);
        $this->assertStringContainsString('No. WhatsApp', $sheet);
        $this->assertStringContainsString('Total Donasi', $sheet);
        $this->assertStringContainsString('Kontak Unduh Satu', $sheet);
    }

    public function test_admin_download_contains_contacts_from_all_branches()
    {
        $branch1 = $this->makeBranch();
        $branch2 = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen1 = $this->makeUser('agen', $branch1);
        $agen2 = $this->makeUser('agen', $branch2);

        $this->makeContact('Kontak Cabang Satu', $branch1, $agen1);
        $this->makeContact('Kontak Cabang Dua', $branch2, $agen2);

        $sheet = $this->sheetContent($this->actingAs($admin)->get(route('contacts.download')));

        $this->assertStringContainsString('Kontak Cabang Satu', $sheet);
        $this->assertStringContainsString('Kontak Cabang Dua', $sheet);
    }

    public function test_supervisor_download_contains_only_own_branch_contacts()
    {
        $branch1 = $this->makeBranch();
        $branch2 = $this->makeBranch();
        $supervisor = $this->makeUser('supervisor', $branch1);
        $agen1 = $this->makeUser('agen', $branch1);
        $agen2 = $this->makeUser('agen', $branch2);

        $this->makeContact('Kontak Supervisor Satu', $branch1, $agen1);
        $this->makeContact('Kontak Supervisor Dua', $branch2, $agen2);

        $sheet = $this->sheetContent($this->actingAs($supervisor)->get(route('contacts.download')));

        $this->assertStringContainsString('Kontak Supervisor Satu', $sheet);
        $this->assertStringNotContainsString('Kontak Supervisor Dua', $sheet);
    }

    public function test_agent_download_contains_only_own_contacts()
    {
        $branch = $this->makeBranch();
        $agen1 = $this->makeUser('agen', $branch);
        $agen2 = $this->makeUser('agen', $branch);

        $this->makeContact('Kontak Agen Satu', $branch, $agen1);
        $this->makeContact('Kontak Agen Dua', $branch, $agen2);

        $sheet = $this->sheetContent($this->actingAs($agen1)->get(route('contacts.download')));

        $this->assertStringContainsString('Kontak Agen Satu', $sheet);
        $this->assertStringNotContainsString('Kontak Agen Dua', $sheet);
    }

    public function test_download_respects_active_search_filter()
    {
        $branch = $this->makeBranch();
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);

        $this->makeContact('Download Filter Cocok', $branch, $agen);
        $this->makeContact('Download Filter Lain', $branch, $agen);

        $sheet = $this->sheetContent(
            $this->actingAs($admin)->get(route('contacts.download', ['search' => 'Filter Cocok']))
        );

        $this->assertStringContainsString('Download Filter Cocok', $sheet);
        $this->assertStringNotContainsString('Download Filter Lain', $sheet);
    }
}

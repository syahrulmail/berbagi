<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Services\ContactImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactImportTemplateTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_import_tab_shows_download_links_for_each_template_format()
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('contacts.create'));

        $response->assertOk();
        $response->assertSee('downloads/template-import-kontak.xlsx', false);
        $response->assertSee('downloads/template-import-kontak.xls', false);
        $response->assertSee('downloads/template-import-kontak.csv', false);
    }

    public function test_mobile_import_page_shows_download_links_for_each_template_format()
    {
        $branch = Branch::create([
            'code' => 'BWA-MB',
            'name' => 'Cabang Mobile',
            'city' => 'Mobile',
            'is_active' => true,
        ]);
        $admin = $this->makeUser('admin', $branch);

        $response = $this->actingAs($admin)->get(route('mo.contact.import-form'));

        $response->assertOk();
        $response->assertSee('downloads/template-import-kontak.xlsx', false);
        $response->assertSee('downloads/template-import-kontak.xls', false);
        $response->assertSee('downloads/template-import-kontak.csv', false);
    }

    public function test_mobile_import_page_shows_structured_format_guide(): void
    {
        $branch = Branch::create([
            'code' => 'BWA-FMT',
            'name' => 'Cabang Format',
            'is_active' => true,
        ]);
        $admin = $this->makeUser('admin', $branch);

        $response = $this->actingAs($admin)->get(route('mo.contact.import-form'));

        $response->assertOk();
        $response->assertSee('<b>Cabang, Agen, Nama, No. WhatsApp, Status, Catatan</b>', false);
        $response->assertSee('<b>Prospek, Simpan, Wakif, atau Stop</b>', false);
        $response->assertSee('8-15 digit', false);
        $response->assertSee('<b>62</b>', false);
        $response->assertSee('<b>+62</b>', false);
        $response->assertSee('+60123456789');
        $response->assertSee('data-file-label', false);
        $response->assertSee('data-import-file', false);
    }

    public function test_template_files_exist_in_public_downloads()
    {
        foreach (['xlsx', 'xls', 'csv'] as $ext) {
            $path = public_path('downloads/template-import-kontak.' . $ext);
            $this->assertFileExists($path);
            $this->assertGreaterThan(0, filesize($path));
        }
    }

    public function test_templates_are_parsed_with_expected_header_and_columns()
    {
        $service = app(ContactImportService::class);
        $header = ['Cabang', 'Agent', 'Nama', 'No. WhatsApp', 'Status', 'Catatan'];

        foreach (['xlsx', 'xls', 'csv'] as $ext) {
            $name = 'template-import-kontak.' . $ext;
            $rows = $service->parseFile(public_path('downloads/' . $name), $name);

            $this->assertNotEmpty($rows, $name . ' tidak berisi data.');
            $this->assertSame($header, array_slice($rows[0], 0, 6), $name . ' baris judul tidak sesuai.');
            $this->assertCount(6, $rows, $name . ' harus berisi 1 baris judul + 5 contoh kontak.');
        }
    }

    public function test_template_rows_pass_import_validation_with_matching_branches_and_agents()
    {
        // Selaraskan dengan baris contoh pada template. Pakai firstOrCreate agar
        // tetap valid walau database uji sudah berisi data seeder.
        $setup = [
            'Jakarta Pusat' => 'Agen Andi',
            'Jakarta Selatan' => 'Agen Budi',
            'Jakarta Timur' => 'Agen Citra',
            'Jakarta Barat' => 'Agen Dewi',
            'Bandung' => 'Agen Eko',
        ];

        $branches = [];
        foreach ($setup as $city => $agentName) {
            $branch = Branch::firstOrCreate(
                ['name' => 'Cabang ' . $city],
                [
                    'code' => 'BWA-T' . strtoupper(substr(md5($city), 0, 3)),
                    'city' => $city,
                    'is_active' => true,
                ]
            );

            User::firstOrCreate(
                ['name' => $agentName, 'branch_id' => $branch->id],
                [
                    'username' => 'user_' . uniqid(),
                    'slug' => 'user_' . uniqid(),
                    'email' => uniqid() . '@test.local',
                    'phone' => '628' . uniqid(),
                    'password' => bcrypt('password'),
                    'role' => 'agen',
                ]
            );

            $branches[$city] = $branch;
        }

        $service = app(ContactImportService::class);
        $rows = $service->parseFile(public_path('downloads/template-import-kontak.csv'), 'template-import-kontak.csv');

        $result = $service->processRows($rows, null);

        $this->assertSame([], $result['errors']);
        $this->assertCount(5, $result['contacts']);
        $this->assertSame('6281234567890', $result['contacts'][0]['phone']);
        $this->assertSame('prospect', $result['contacts'][0]['status']);
        $this->assertSame((int) $branches['Jakarta Pusat']->id, $result['contacts'][0]['branch_id']);
        $this->assertNotNull($result['contacts'][0]['agen_id']);
        $this->assertSame('6281298765432', $result['contacts'][1]['phone']);
        $this->assertSame('donated', $result['contacts'][1]['status']);
        $this->assertNull($result['contacts'][4]['agen_id'], 'Baris tanpa agen harus tetap valid.');
        $this->assertSame((int) $branches['Bandung']->id, $result['contacts'][4]['branch_id']);
    }
}

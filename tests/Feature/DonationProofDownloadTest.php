<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DonationProofDownloadTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
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

    protected function makeProgram(): Program
    {
        return Program::create([
            'name' => 'Program Bukti ' . uniqid(),
            'slug' => 'program-bukti-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);
    }

    protected function makeContact(): Contact
    {
        return Contact::create([
            'name' => 'Donatur Bukti ' . uniqid(),
            'phone' => '628' . uniqid(),
            'status' => 'donated',
        ]);
    }

    protected function putProof(): string
    {
        $path = 'donation-proofs/bukti-' . uniqid() . '.png';
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        Storage::disk('public')->put($path, $png);

        return $path;
    }

    protected function makeDonation(Branch $branch, User $agen, Program $program, Contact $contact, ?string $proof, ?string $note): Donation
    {
        return Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agen->id,
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'amount' => 50000,
            'donation_date' => now()->format('Y-m-d'),
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'transfer',
            'payment_proof' => $proof,
            'note' => $note,
            'created_by' => $agen->id,
        ]);
    }

    protected function zipEntry(string $file, string $entry): string
    {
        $zip = new \ZipArchive();
        $zip->open($file);
        $content = (string) $zip->getFromName($entry);
        $zip->close();

        return $content;
    }

    public function test_modal_has_download_bt_button_next_to_xlsx()
    {
        $admin = $this->makeUser('admin');

        $html = $this->actingAs($admin)->get(route('donations.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Download BT', $html);
        $this->assertStringContainsString(route('donations.download-proof'), $html);
        $this->assertStringContainsString(route('donations.download'), $html);

        $xlsxPos = strpos($html, 'Download XLSX');
        $btPos = strpos($html, 'Download BT');

        $this->assertNotFalse($btPos);
        $this->assertGreaterThan($xlsxPos, $btPos, 'Tombol Download BT harus di kanan tombol Download XLSX.');
    }

    public function test_download_proof_embeds_images_and_notes()
    {
        $branch = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Bukti', 'is_active' => true]);
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();
        $contact = $this->makeContact();
        $note = 'Catatan bukti ' . uniqid();

        $this->makeDonation($branch, $agen, $program, $contact, $this->putProof(), $note);

        $response = $this->actingAs($admin)->get(route('donations.download-proof', ['branch_ids' => [$branch->id]]));

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );

        $file = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($file);
        $this->assertSame('PK', substr((string) file_get_contents($file), 0, 2));

        $contentTypes = $this->zipEntry($file, '[Content_Types].xml');
        $sheet = $this->zipEntry($file, 'xl/worksheets/sheet1.xml');
        $drawing = $this->zipEntry($file, 'xl/drawings/drawing1.xml');
        $drawingRels = $this->zipEntry($file, 'xl/drawings/_rels/drawing1.xml.rels');
        $sheetRels = $this->zipEntry($file, 'xl/worksheets/_rels/sheet1.xml.rels');

        $this->assertStringContainsString('drawing1.xml', $contentTypes);
        $this->assertStringContainsString('<drawing r:id="rId1"/>', $sheet);
        $this->assertStringContainsString($note, $sheet);
        $this->assertStringContainsString('<xdr:pic>', $drawing);
        $this->assertStringContainsString('../media/image1.png', $drawingRels);
        $this->assertStringContainsString('../drawings/drawing1.xml', $sheetRels);

        // Gambar dalam satu baris/sel, catatan di sel bawahnya (A2).
        $this->assertStringContainsString('<xdr:row>0</xdr:row>', $drawing);
        $this->assertStringContainsString('<c r="A2"', $sheet);

        // Ukuran kertas A4, margin narrow, header/footer 0, fit 1 halaman lebar.
        $this->assertStringContainsString('<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>', $sheet);
        $this->assertStringContainsString('<pageSetup paperSize="9" orientation="portrait" fitToWidth="1" fitToHeight="0"/>', $sheet);
        $this->assertStringContainsString('left="0.25" right="0.25"', $sheet);
        $this->assertStringContainsString('top="0.75" bottom="0.75"', $sheet);
        $this->assertStringContainsString('header="0" footer="0"', $sheet);

        $zip = new \ZipArchive();
        $zip->open($file);
        $this->assertNotFalse($zip->locateName('xl/media/image1.png'));
        $zip->close();

        $this->assertStringNotContainsString('storage/donation-proofs', $sheet);
    }

    public function test_download_proof_skips_donations_without_proof()
    {
        $branch = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Skip', 'is_active' => true]);
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $withProofNote = 'Ada bukti ' . uniqid();
        $withoutProofNote = 'Tanpa bukti ' . uniqid();

        $this->makeDonation($branch, $agen, $program, $this->makeContact(), $this->putProof(), $withProofNote);
        $this->makeDonation($branch, $agen, $program, $this->makeContact(), null, $withoutProofNote);

        $response = $this->actingAs($admin)->get(route('donations.download-proof', ['branch_ids' => [$branch->id]]));
        $response->assertOk();

        $sheet = $this->zipEntry($response->baseResponse->getFile()->getPathname(), 'xl/worksheets/sheet1.xml');

        $this->assertStringContainsString($withProofNote, $sheet);
        $this->assertStringNotContainsString($withoutProofNote, $sheet);
    }

    public function test_download_proof_without_images_is_valid_file()
    {
        $branch = Branch::create(['code' => 'BR-' . uniqid(), 'name' => 'Cabang Kosong', 'is_active' => true]);
        $admin = $this->makeUser('admin');
        $agen = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $this->makeDonation($branch, $agen, $program, $this->makeContact(), null, 'Catatan tanpa gambar');

        $response = $this->actingAs($admin)->get(route('donations.download-proof', ['branch_ids' => [$branch->id]]));
        $response->assertOk();

        $file = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($file);
        $this->assertSame('PK', substr((string) file_get_contents($file), 0, 2));

        $sheet = $this->zipEntry($file, 'xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('Tidak ada bukti pembayaran', $sheet);
        $this->assertStringNotContainsString('<drawing r:id', $sheet);
    }

    public function test_agent_download_proof_other_branch_is_forbidden()
    {
        $branchA = Branch::create(['code' => 'A-' . uniqid(), 'name' => 'Cabang A', 'is_active' => true]);
        $branchB = Branch::create(['code' => 'B-' . uniqid(), 'name' => 'Cabang B', 'is_active' => true]);

        $agent = $this->makeUser('agen', $branchA);

        $this->actingAs($agent)->get(route('donations.download-proof', [
            'branch_ids' => [$branchB->id],
        ]))->assertForbidden();
    }
}

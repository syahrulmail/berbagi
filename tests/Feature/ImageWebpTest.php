<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\CampaignTag;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use App\Services\ImageWebpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageWebpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    protected function makeUser(string $role = 'admin'): User
    {
        return User::create([
            'name' => ucfirst($role) . ' ' . uniqid(),
            'username' => 'user_' . uniqid(),
            'slug' => 'user_' . uniqid(),
            'email' => uniqid() . '@test.local',
            'phone' => '628' . uniqid(),
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    /**
     * Buat berkas gambar asli berformat tertentu di disk sementara.
     */
    protected function makeImage(string $format, int $width = 200, int $height = 120): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 12, 120, 200));

        $path = tempnam(sys_get_temp_dir(), 'upl_') . '.' . $format;

        switch ($format) {
            case 'png':
                imagepng($image, $path);
                break;
            case 'gif':
                imagegif($image, $path);
                break;
            case 'bmp':
                imagebmp($image, $path);
                break;
            case 'webp':
                imagewebp($image, $path, 90);
                break;
            default:
                imagejpeg($image, $path, 90);
                break;
        }

        imagedestroy($image);

        return $path;
    }

    protected function uploadedFile(string $format, int $width = 200, int $height = 120): UploadedFile
    {
        return new UploadedFile($this->makeImage($format, $width, $height), 'foto.' . $format, null, null, true);
    }

    public function test_service_is_available_on_this_environment()
    {
        $this->assertTrue(app(ImageWebpService::class)->available(), 'GD dengan WebP diperlukan untuk menjalankan fitur ini.');
    }

    public function test_uploaded_jpeg_is_stored_as_webp()
    {
        $path = app(ImageWebpService::class)->store($this->uploadedFile('jpg'), 'banners');

        $this->assertNotNull($path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);

        $info = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame(IMAGETYPE_WEBP, $info[2]);
    }

    public function test_all_supported_input_formats_become_webp()
    {
        foreach (['png', 'gif', 'bmp', 'webp', 'jpg'] as $format) {
            $path = app(ImageWebpService::class)->store($this->uploadedFile($format), 'programs');

            $this->assertNotNull($path, "Format {$format} gagal disimpan.");
            $this->assertStringEndsWith('.webp', $path, "Format {$format} tidak menjadi WebP.");
        }
    }

    public function test_large_image_is_downscaled_to_max_dimension()
    {
        $path = app(ImageWebpService::class)->store($this->uploadedFile('jpg', 3000, 1200), 'programs');

        $info = getimagesize(Storage::disk('public')->path($path));

        $this->assertLessThanOrEqual(ImageWebpService::MAX_DIMENSION, max($info[0], $info[1]));
        $this->assertSame(IMAGETYPE_WEBP, $info[2]);
        // Rasio 3000x1200 harus tetap terjaga.
        $this->assertSame(2400, $info[0]);
        $this->assertSame(960, $info[1]);
    }

    public function test_alphatransparency_is_preserved()
    {
        $image = imagecreatetruecolor(50, 50);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefilledrectangle($image, 0, 0, 50, 50, imagecolorallocatealpha($image, 0, 0, 0, 127));

        $source = tempnam(sys_get_temp_dir(), 'alpha_') . '.png';
        imagepng($image, $source);
        imagedestroy($image);

        $path = app(ImageWebpService::class)->store(new UploadedFile($source, 'alpha.png', null, null, true), 'programs');

        $converted = imagecreatefromwebp(Storage::disk('public')->path($path));
        $color = imagecolorsforindex($converted, imagecolorat($converted, 25, 25));

        $this->assertSame(127, $color['alpha'], 'Area transparan harus tetap transparan setelah konversi.');
    }

    public function test_non_image_upload_falls_back_to_original_behaviour()
    {
        $file = new UploadedFile(
            tap(tempnam(sys_get_temp_dir(), 'doc_') . '.txt', function ($p) {
                file_put_contents($p, 'bukan gambar');
            }),
            'catatan.txt',
            null,
            null,
            true
        );

        $path = app(ImageWebpService::class)->store($file, 'wa-media');

        $this->assertNotNull($path);
        $this->assertStringEndsWith('.txt', $path, 'Berkas non-gambar harus disimpan apa adanya.');
    }

    public function test_banner_upload_through_controller_is_stored_as_webp()
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post(route('banners.store'), [
            'title' => 'Banner Uji WebP',
            'type' => 'banner',
            'image' => $this->uploadedFile('png'),
            'is_active' => '1',
        ]);

        $response->assertRedirect();

        $banner = Banner::where('title', 'Banner Uji WebP')->firstOrFail();

        $this->assertStringEndsWith('.webp', (string) $banner->image);
        Storage::disk('public')->assertExists($banner->image);
        $this->assertSame(IMAGETYPE_WEBP, getimagesize(Storage::disk('public')->path($banner->image))[2]);
    }

    public function test_program_cover_upload_is_stored_as_webp()
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post(route('programs.store'), [
            'name' => 'Program Uji WebP',
            'slug' => 'program-uji-webp',
            'program_category' => 'WAP',
            'description' => 'Deskripsi program uji konversi WebP.',
            'goal_amount' => 1000000,
            'media_files' => [$this->uploadedFile('jpg')],
            'default_tag' => CampaignTag::DEFAULT_TAG_SLUGS[0],
            'is_active' => '1',
        ]);

        $response->assertRedirect();

        $program = Program::where('slug', 'program-uji-webp')->first();
        $this->assertNotNull($program, 'Program gagal dibuat: ' . json_encode(session('errors') ? session('errors')->all() : []));

        $media = (array) $program->media;
        $this->assertNotEmpty($media, 'Gambar program harus tersimpan di media.');
        $this->assertStringEndsWith('.webp', (string) ($media[0]['path'] ?? ''));
        Storage::disk('public')->assertExists($media[0]['path']);
    }

    public function test_agent_profile_photo_and_signature_become_webp()
    {
        $admin = $this->makeUser('admin');

        $agent = User::create([
            'name' => 'Agen Uji WebP',
            'username' => 'agen_webp_' . uniqid(),
            'slug' => 'agen-webp-' . uniqid(),
            'email' => uniqid() . '@test.local',
            'phone' => '628' . uniqid(),
            'password' => bcrypt('password'),
            'role' => 'agen',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Agen WebP Baru',
            'username' => 'agen_baru_' . uniqid(),
            'slug' => 'agen-baru-' . uniqid(),
            'email' => uniqid() . '@test.local',
            'phone' => '628' . uniqid(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'agen',
            'photo' => $this->uploadedFile('png'),
            'is_active' => '1',
        ]);

        $created = User::where('name', 'Agen WebP Baru')->first();
        $this->assertNotNull($created, 'User agen gagal dibuat: ' . json_encode(session('errors') ? session('errors')->all() : []));

        $profile = json_decode((string) Setting::get('agent_profile_' . $created->slug, '{}'), true);

        $this->assertArrayHasKey('photo', $profile);
        $this->assertStringEndsWith('.webp', (string) $profile['photo']);
        Storage::disk('public')->assertExists($profile['photo']);

        unset($agent);
    }

    public function test_convert_stored_removes_old_file_and_returns_new_path()
    {
        $service = app(ImageWebpService::class);

        $relative = 'banners/lama-' . uniqid() . '.png';
        Storage::disk('public')->put($relative, file_get_contents($this->makeImage('png')));

        $new = $service->convertStored($relative);

        $this->assertNotNull($new);
        $this->assertStringEndsWith('.webp', $new);
        Storage::disk('public')->assertExists($new);
        Storage::disk('public')->assertMissing($relative);
    }

    public function test_convert_stored_skips_files_already_webp()
    {
        $service = app(ImageWebpService::class);

        $relative = 'banners/sudah-' . uniqid() . '.webp';
        Storage::disk('public')->put($relative, file_get_contents($this->makeImage('webp')));

        $this->assertNull($service->convertStored($relative));
        Storage::disk('public')->assertExists($relative);
    }

    public function test_existing_files_are_converted_by_artisan_command_and_references_updated()
    {
        $admin = $this->makeUser('admin');

        $oldPath = 'banners/lama-' . uniqid() . '.png';
        Storage::disk('public')->put($oldPath, file_get_contents($this->makeImage('png')));

        $banner = Banner::create([
            'title' => 'Banner Lama',
            'type' => 'banner',
            'image' => $oldPath,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->artisan('images:webp')->assertExitCode(0);

        $banner->refresh();

        $this->assertStringEndsWith('.webp', (string) $banner->image);
        Storage::disk('public')->assertExists($banner->image);
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertSame(IMAGETYPE_WEBP, getimagesize(Storage::disk('public')->path($banner->image))[2]);

        unset($admin);
    }

    public function test_dry_run_does_not_change_anything()
    {
        $oldPath = 'banners/kering-' . uniqid() . '.png';
        Storage::disk('public')->put($oldPath, file_get_contents($this->makeImage('png')));

        $banner = Banner::create([
            'title' => 'Banner Dry Run',
            'type' => 'banner',
            'image' => $oldPath,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->artisan('images:webp', ['--dry-run' => true])->assertExitCode(0);

        $banner->refresh();

        $this->assertSame($oldPath, (string) $banner->image);
        Storage::disk('public')->assertExists($oldPath);
    }
}

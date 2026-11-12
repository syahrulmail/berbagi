<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeVideosTest extends TestCase
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
        return Branch::create([
            'code' => 'VID-' . uniqid(),
            'name' => 'Cabang Video ' . uniqid(),
            'is_active' => true,
        ]);
    }

    protected function fakeVideo(string $name = 'klip.mp4', int $kilobytes = 1024): UploadedFile
    {
        $header = "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom";
        $content = $header . str_repeat("\0", max(1, $kilobytes * 1024 - strlen($header)));

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_home_hides_video_section_without_videos(): void
    {
        Setting::set('home_videos', '[]');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-hv', false);
    }

    public function test_home_shows_video_with_poster_and_without_iframe(): void
    {
        Setting::set('home_videos', json_encode([
            [
                'video' => 'home-videos/klip-satu.mp4',
                'poster' => 'home-video-posters/sampul-satu.webp',
                'ratio' => 1.775,
            ],
            [
                'video' => 'home-videos/klip-dua.webm',
                'poster' => null,
                'ratio' => 1.0,
            ],
        ]));

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('data-hv-item', false);
        $response->assertSee('home-videos/klip-satu.mp4', false);
        $response->assertSee('home-video-posters/sampul-satu.webp', false);
        $response->assertSee('aspect-ratio: 1 / 1.775', false);
        $response->assertSee('class="hv-cover"', false);
        // Video tidak dimuat sebelum tombol putar ditekan.
        $response->assertSee('preload="none"', false);
        $response->assertDontSee('<iframe', false);
        $response->assertDontSee('data-interval', false);
    }

    public function test_video_without_poster_shows_gradient_fallback(): void
    {
        Setting::set('home_videos', json_encode([
            ['video' => 'home-videos/tanpa-sampul.mp4', 'poster' => null, 'ratio' => 1.0],
        ]));

        $this->get('/')
            ->assertOk()
            ->assertSee('hv-fallback', false)
            ->assertDontSee('class="hv-cover"', false);
    }

    public function test_multiple_videos_render_desktop_navigation_buttons(): void
    {
        Setting::set('home_videos', json_encode([
            ['video' => 'home-videos/nav-satu.mp4', 'poster' => null, 'ratio' => 1.0],
            ['video' => 'home-videos/nav-dua.mp4', 'poster' => null, 'ratio' => 1.0],
        ]));

        $this->get('/')
            ->assertOk()
            ->assertSee('data-hv-prev', false)
            ->assertSee('data-hv-next', false);
    }

    public function test_single_video_has_no_navigation_buttons(): void
    {
        Setting::set('home_videos', json_encode([
            ['video' => 'home-videos/tunggal.mp4', 'poster' => null, 'ratio' => 1.0],
        ]));

        $this->get('/')
            ->assertOk()
            ->assertDontSee('class="hv-nav hv-nav--prev"', false)
            ->assertDontSee('class="hv-nav hv-nav--next"', false);
    }

    public function test_admin_can_save_video_and_poster(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('settings.videos'))
            ->assertOk()
            ->assertSee('Video Beranda');

        $this->actingAs($admin)
            ->put(route('settings.videos.update'), [
                'videos' => [
                    [
                        'video' => $this->fakeVideo('pertama.mp4', 512),
                        'poster' => UploadedFile::fake()->image('sampul.jpg', 320, 480),
                        'existing_video' => '',
                        'existing_poster' => '',
                        'ratio' => '1.775',
                        'remove' => '0',
                    ],
                ],
            ])
            ->assertRedirect(route('settings.videos'));

        $saved = json_decode(Setting::get('home_videos'), true);

        $this->assertCount(1, $saved);
        $this->assertStringStartsWith('home-videos/', $saved[0]['video']);
        $this->assertStringStartsWith('home-video-posters/', (string) $saved[0]['poster']);
        $this->assertEqualsWithDelta(1.775, $saved[0]['ratio'], 0.001);
        Storage::disk('public')->assertExists($saved[0]['video']);
        Storage::disk('public')->assertExists($saved[0]['poster']);
    }

    public function test_replacing_video_removes_old_file(): void
    {
        $admin = $this->makeUser('admin');

        Setting::set('home_videos', json_encode([
            ['video' => 'home-videos/lama.mp4', 'poster' => null, 'ratio' => 1.0],
        ]));
        Storage::disk('public')->put('home-videos/lama.mp4', 'lama');

        $this->actingAs($admin)
            ->put(route('settings.videos.update'), [
                'videos' => [
                    [
                        'video' => $this->fakeVideo('baru.mp4', 512),
                        'existing_video' => 'home-videos/lama.mp4',
                        'existing_poster' => '',
                        'ratio' => '1.0',
                        'remove' => '0',
                    ],
                ],
            ])
            ->assertRedirect(route('settings.videos'));

        $saved = json_decode(Setting::get('home_videos'), true);

        Storage::disk('public')->assertMissing('home-videos/lama.mp4');
        Storage::disk('public')->assertExists($saved[0]['video']);
    }

    public function test_removing_video_deletes_files_and_setting(): void
    {
        $admin = $this->makeUser('admin');

        Setting::set('home_videos', json_encode([
            ['video' => 'home-videos/dihapus.mp4', 'poster' => 'home-video-posters/dihapus.webp', 'ratio' => 1.0],
        ]));
        Storage::disk('public')->put('home-videos/dihapus.mp4', 'video');
        Storage::disk('public')->put('home-video-posters/dihapus.webp', 'poster');

        $this->actingAs($admin)
            ->put(route('settings.videos.update'), [
                'videos' => [
                    [
                        'existing_video' => 'home-videos/dihapus.mp4',
                        'existing_poster' => 'home-video-posters/dihapus.webp',
                        'ratio' => '1.0',
                        'remove' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('settings.videos'));

        $this->assertSame('[]', Setting::get('home_videos'));
        Storage::disk('public')->assertMissing('home-videos/dihapus.mp4');
        Storage::disk('public')->assertMissing('home-video-posters/dihapus.webp');
    }

    public function test_admin_cannot_upload_non_video_file(): void
    {
        $admin = $this->makeUser('admin');
        $before = Setting::get('home_videos', '[]');

        $this->actingAs($admin)
            ->put(route('settings.videos.update'), [
                'videos' => [
                    [
                        'video' => UploadedFile::fake()->create('dokumen.txt', 10, 'text/plain'),
                        'ratio' => '1.0',
                    ],
                ],
            ])
            ->assertSessionHasErrors('videos.0.video');

        $this->assertSame($before, Setting::get('home_videos', '[]'));
    }

    public function test_admin_cannot_upload_video_larger_than_ten_megabytes(): void
    {
        $admin = $this->makeUser('admin');
        $before = Setting::get('home_videos', '[]');

        $this->actingAs($admin)
            ->put(route('settings.videos.update'), [
                'videos' => [
                    [
                        'video' => $this->fakeVideo('besar.mp4', 11264),
                        'ratio' => '1.0',
                    ],
                ],
            ])
            ->assertSessionHasErrors('videos.0.video');

        $this->assertSame($before, Setting::get('home_videos', '[]'));
    }

    public function test_ratio_out_of_range_falls_back_to_square(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->put(route('settings.videos.update'), [
                'videos' => [
                    [
                        'video' => $this->fakeVideo('rasio.mp4', 128),
                        'ratio' => '9',
                    ],
                ],
            ])
            ->assertRedirect(route('settings.videos'));

        $saved = json_decode(Setting::get('home_videos'), true);
        $this->assertEqualsWithDelta(1.0, $saved[0]['ratio'], 0.001);
    }

    public function test_supervisor_cannot_manage_videos(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        $this->actingAs($supervisor)
            ->get(route('settings.videos'))
            ->assertForbidden();
    }

    public function test_mobile_admin_can_manage_videos(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('mo.settings.videos'))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('mo.settings.videos.update'), [
                'videos' => [
                    [
                        'video' => $this->fakeVideo('mobile.mp4', 128),
                        'ratio' => '1.0',
                    ],
                ],
            ])
            ->assertRedirect(route('mo.settings.videos'));

        $saved = json_decode(Setting::get('home_videos'), true);
        $this->assertCount(1, $saved);
    }

    public function test_agent_page_shows_video_section(): void
    {
        Setting::set('home_videos', json_encode([
            ['video' => 'home-videos/agen.mp4', 'poster' => null, 'ratio' => 1.0],
        ]));

        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $agen->slug = 'agen-video-' . uniqid();
        $agen->save();

        $this->get(route('public.agent', $agen->slug))
            ->assertOk()
            ->assertSee('home-videos/agen.mp4', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InstagramPostsTest extends TestCase
{
    use DatabaseTransactions;

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
            'code' => 'IG-' . uniqid(),
            'name' => 'Cabang IG ' . uniqid(),
            'is_active' => true,
        ]);
    }

    public function test_instagram_embed_url_helper_normalizes_supported_links(): void
    {
        $this->assertSame(
            'https://www.instagram.com/p/DfmbqAppDMi/embed/',
            instagram_embed_url('http://instagram.com/p/DfmbqAppDMi/')
        );
        $this->assertSame(
            'https://www.instagram.com/reel/ABC123/embed/',
            instagram_embed_url('https://www.instagram.com/reel/ABC123/?utm_source=ig_web')
        );
        $this->assertSame(
            'https://www.instagram.com/tv/XYZ-_9/embed/',
            instagram_embed_url('https://instagram.com/tv/XYZ-_9/')
        );

        $this->assertNull(instagram_embed_url('https://example.com/p/abc'));
        $this->assertNull(instagram_embed_url(''));
    }

    public function test_home_shows_instagram_section_when_posts_configured(): void
    {
        Setting::set('home_instagram_posts', json_encode([
            ['url' => 'https://www.instagram.com/p/DfmbqAppDMi/'],
        ]));

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('https://www.instagram.com/p/DfmbqAppDMi/embed/', false);
        $response->assertSee('Kabar Terbaru Kami');
        $response->assertSee('data-ig-posts', false);
    }

    public function test_home_hides_instagram_section_without_posts(): void
    {
        Setting::set('home_instagram_posts', '[]');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Kabar Terbaru Kami');
    }

    public function test_invalid_setting_links_are_ignored_on_public_home(): void
    {
        Setting::set('home_instagram_posts', json_encode([
            ['url' => 'https://example.com/bukan-instagram'],
            ['url' => 'https://www.instagram.com/p/VALIDPOST1/'],
        ]));

        $this->get('/')
            ->assertOk()
            ->assertSee('VALIDPOST1/embed', false)
            ->assertDontSee('bukan-instagram');
    }

    public function test_admin_can_manage_instagram_posts(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('settings.instagram'))
            ->assertOk()
            ->assertSee('Postingan Instagram');

        $this->actingAs($admin)
            ->put(route('settings.instagram.update'), [
                'posts' => [
                    ['url' => 'https://www.instagram.com/p/FIRSTPOST/'],
                    ['url' => 'https://www.instagram.com/reel/SECONDREEL/'],
                    ['url' => ''],
                ],
            ])
            ->assertRedirect(route('settings.instagram'));

        $saved = json_decode(Setting::get('home_instagram_posts'), true);

        $this->assertCount(2, $saved);
        $this->assertSame('https://www.instagram.com/p/FIRSTPOST/', $saved[0]['url']);
        $this->assertSame('https://www.instagram.com/reel/SECONDREEL/', $saved[1]['url']);
    }

    public function test_admin_cannot_save_non_instagram_link(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->put(route('settings.instagram.update'), [
                'posts' => [
                    ['url' => 'https://example.com/bukan-instagram'],
                ],
            ])
            ->assertSessionHasErrors('posts');

        $this->assertSame('[]', Setting::get('home_instagram_posts', '[]'));
    }

    public function test_supervisor_cannot_manage_instagram_posts(): void
    {
        $supervisor = $this->makeUser('supervisor', $this->makeBranch());

        $this->actingAs($supervisor)
            ->get(route('settings.instagram'))
            ->assertForbidden();
    }

    public function test_mobile_admin_can_manage_instagram_posts(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('mo.settings.instagram'))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('mo.settings.instagram.update'), [
                'posts' => [
                    ['url' => 'https://www.instagram.com/p/MOBILEPOST/'],
                ],
            ])
            ->assertRedirect(route('mo.settings.instagram'));

        $saved = json_decode(Setting::get('home_instagram_posts'), true);
        $this->assertSame('https://www.instagram.com/p/MOBILEPOST/', $saved[0]['url']);
    }

    public function test_agent_page_shows_instagram_section(): void
    {
        Setting::set('home_instagram_posts', json_encode([
            ['url' => 'https://www.instagram.com/p/AGENTPAGE1/'],
        ]));

        $branch = $this->makeBranch();
        $agen = $this->makeUser('agen', $branch);
        $agen->slug = 'agen-ig-' . uniqid();
        $agen->save();

        $this->get(route('public.agent', $agen->slug))
            ->assertOk()
            ->assertSee('AGENTPAGE1/embed', false);
    }
}

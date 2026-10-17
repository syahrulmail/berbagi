<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Branch;
use App\Models\Donation;
use App\Models\PopupBanner;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PopupBannerTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(string $role, array $extra = []): User
    {
        $suffix = uniqid();

        return User::create(array_merge([
            'name' => ucfirst($role) . ' ' . $suffix,
            'username' => 'user_' . $role . '_' . $suffix,
            'slug' => 'user-' . $role . '-' . $suffix,
            'email' => $role . '_' . $suffix . '@test.local',
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
        ], $extra));
    }

    protected function makeBranch(): Branch
    {
        return Branch::create([
            'code' => 'PB-' . uniqid(),
            'name' => 'Cabang Popup ' . uniqid(),
            'is_active' => true,
        ]);
    }

    protected function makeProgram(array $extra = []): Program
    {
        $suffix = uniqid();

        return Program::create(array_merge([
            'name' => 'Program ' . $suffix,
            'slug' => 'program-' . $suffix,
            'description' => 'Program uji popup.',
            'goal_amount' => 1000000,
            'is_active' => true,
        ], $extra));
    }

    protected function makeBanner(array $extra = []): PopupBanner
    {
        return PopupBanner::create(array_merge([
            'title' => 'Banner ' . uniqid(),
            'image' => 'popup-banners/demo.png',
            'schedule_type' => 'always',
            'pages' => ['home'],
            'agent_scope' => 'all',
            'trigger_type' => 'welcome',
            'delay_minutes' => 1,
            'sort_order' => 0,
            'is_active' => true,
        ], $extra));
    }

    public function test_admin_can_open_banner_page(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('popup-banners.index'))
            ->assertOk()
            ->assertSee('Popup Statistik Otomatis')
            ->assertSee('Tambah Banner');

        $this->actingAs($admin)
            ->get(route('popup-banners.create'))
            ->assertOk()
            ->assertSee('Semua halaman')
            ->assertSee('Saat pengunjung akan keluar');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('popup-banners');
    }

    public function test_supervisor_cannot_access_banner_pages(): void
    {
        $supervisor = $this->makeUser('supervisor', ['branch_id' => $this->makeBranch()->id]);

        $this->actingAs($supervisor)->get(route('popup-banners.index'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('popup-banners.create'))->assertForbidden();
        $this->actingAs($supervisor)->post(route('popup-banners.store'), [])->assertForbidden();
    }

    public function test_admin_can_create_banner_with_full_settings(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('admin');
        $agent = $this->makeUser('agen', ['branch_id' => $this->makeBranch()->id]);

        $this->actingAs($admin)
            ->post(route('popup-banners.store'), [
                'title' => 'Promo Akhir Tahun',
                'image' => UploadedFile::fake()->image('promo.jpg'),
                'url' => 'https://example.com/promo',
                'schedule_type' => 'days',
                'days' => [1, 3, 3],
                'pages' => ['home', 'program'],
                'agent_scope' => 'selected',
                'agent_ids' => [$agent->id],
                'trigger_type' => 'time',
                'delay_minutes' => 3,
                'sort_order' => 7,
                'is_active' => 1,
            ])
            ->assertRedirect(route('popup-banners.index'));

        $banner = PopupBanner::where('title', 'Promo Akhir Tahun')->firstOrFail();

        $this->assertSame([1, 3], $banner->days);
        $this->assertSame(['home', 'program'], $banner->pages);
        $this->assertSame([$agent->id], $banner->agent_ids);
        $this->assertSame('time', $banner->trigger_type);
        $this->assertSame(3, $banner->delay_minutes);
        $this->assertSame(7, $banner->sort_order);
        $this->assertTrue($banner->is_active);
        Storage::disk('public')->assertExists($banner->image);
    }

    public function test_admin_can_remove_banner_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('popup-banners/old.png', 'x');
        $admin = $this->makeUser('admin');
        $banner = $this->makeBanner(['image' => 'popup-banners/old.png']);

        $this->actingAs($admin)
            ->put(route('popup-banners.update', $banner), [
                'title' => $banner->title,
                'remove_image' => 1,
                'schedule_type' => 'always',
                'pages' => ['home'],
                'agent_scope' => 'all',
                'trigger_type' => 'welcome',
                'is_active' => 1,
            ])
            ->assertRedirect(route('popup-banners.index'));

        $this->assertNull($banner->fresh()->image);
        Storage::disk('public')->assertMissing('popup-banners/old.png');
    }

    public function test_public_home_only_shows_banner_for_matching_page(): void
    {
        $homeBanner = $this->makeBanner(['title' => 'Banner Beranda', 'pages' => ['home']]);
        $programBanner = $this->makeBanner(['title' => 'Banner Program', 'pages' => ['program']]);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('popup-banners/demo.png');
        $response->assertSee('Banner Beranda');
        $response->assertDontSee('Banner Program');
        $this->assertTrue($homeBanner->is_active);
        $this->assertNotNull($programBanner->id);
    }

    public function test_banner_days_schedule_is_respected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-17 10:00:00'));

        try {
            $todayBanner = $this->makeBanner([
                'title' => 'Banner Hari Ini',
                'schedule_type' => 'days',
                'days' => [6],
            ]);
            $this->makeBanner([
                'title' => 'Banner Hari Lain',
                'schedule_type' => 'days',
                'days' => [1],
            ]);

            $response = $this->get(route('home'))->assertOk();

            $response->assertSee('Banner Hari Ini');
            $response->assertDontSee('Banner Hari Lain');
            $this->assertSame(6, (int) Carbon::now()->dayOfWeek);
            $this->assertNotNull($todayBanner->id);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_selected_agent_banner_only_shows_on_that_agent_page(): void
    {
        $branch = $this->makeBranch();
        $agentA = $this->makeUser('agen', ['branch_id' => $branch->id]);
        $agentB = $this->makeUser('agen', ['branch_id' => $branch->id]);

        $this->makeBanner([
            'title' => 'Banner Agen A',
            'pages' => ['agent'],
            'agent_scope' => 'selected',
            'agent_ids' => [$agentA->id],
        ]);

        $this->get(route('public.agent', $agentA->slug))
            ->assertOk()
            ->assertSee('Banner Agen A');

        $this->get(route('public.agent', $agentB->slug))
            ->assertOk()
            ->assertDontSee('Banner Agen A');
    }

    public function test_stats_widget_uses_donations_and_achievements(): void
    {
        $branch = $this->makeBranch();
        $agent = $this->makeUser('agen', ['branch_id' => $branch->id]);
        $program = $this->makeProgram(['program_category' => 'WAP']);

        Donation::create([
            'branch_id' => $branch->id,
            'agen_id' => $agent->id,
            'program_id' => $program->id,
            'amount' => 100000,
            'donation_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        Achievement::create([
            'value' => '12.345',
            'label' => 'Penerima Manfaat',
            'icon' => 'fa-people-group',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Setting::set('popup_stats_enabled', '1');

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('bpStats');
        $response->assertSee('"type":"category"', false);
        $response->assertSee('"period":"hari ini"', false);
        $response->assertSee('"period":"pekan ini"', false);
        $response->assertSee('"period":"bulan ini"', false);
        $response->assertDontSee('"period":"kemarin"', false);
        $response->assertSee('"category":"Quran"', false);
        $response->assertSee('"type":"achievement"', false);
        $response->assertSee('"value":"12.345"', false);
        $response->assertSee('"label":"Penerima Manfaat"', false);
        $response->assertSee('"cta":"Berbagi sekarang"', false);
    }

    public function test_stats_widget_can_be_disabled(): void
    {
        Setting::set('popup_stats_enabled', '0');
        Achievement::create([
            'value' => '99',
            'label' => 'Penerima Manfaat',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('bpStats');
    }

    public function test_admin_can_save_stats_widget_settings(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->put(route('popup-banners.settings'), [
                'popup_stats_enabled' => 1,
                'popup_stats_delay_seconds' => 15,
            ])
            ->assertRedirect(route('popup-banners.index'));

        $this->assertSame('1', Setting::get('popup_stats_enabled'));
        $this->assertSame('15', Setting::get('popup_stats_delay_seconds'));

        $this->actingAs($admin)
            ->put(route('popup-banners.settings'), [
                'popup_stats_delay_seconds' => 5,
            ]);

        $this->assertSame('0', Setting::get('popup_stats_enabled'));
        $this->assertSame('5', Setting::get('popup_stats_delay_seconds'));
    }
}

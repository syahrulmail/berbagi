<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_when_list_settings_are_not_arrays(): void
    {
        Setting::set('home_testimonials', '"bukan-array"');
        Setting::set('home_partner_logos', '123');

        $this->get('/')->assertOk();
    }

    public function test_home_renders_valid_list_settings(): void
    {
        Setting::set('home_testimonials', json_encode([
            ['photo' => '', 'text' => 'Testimoni contoh', 'name' => 'Budi'],
        ]));
        Setting::set('home_partner_logos', json_encode(['logos/mitra.png']));

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Testimoni contoh');
    }
}

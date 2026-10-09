<?php

namespace Tests\Feature;

use App\Services\IntegrationCheckService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrationCheckServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['services.starsender.base_url' => 'https://api.starsender.online']);
        config(['services.cloudchat.base_url' => 'https://app.cloudchat.id/api/public/v1']);
    }

    public function test_empty_keys_return_empty_without_http(): void
    {
        Http::fake();

        $result = (new IntegrationCheckService())->forUsers([
            1 => ['ss' => '', 'cc' => '   '],
        ]);

        $this->assertSame(IntegrationCheckService::STATUS_EMPTY, $result[1]['ss']);
        $this->assertSame(IntegrationCheckService::STATUS_EMPTY, $result[1]['cc']);
        Http::assertNothingSent();
    }

    public function test_valid_keys_are_marked_ok(): void
    {
        Http::fake([
            'https://api.starsender.online/*' => Http::response(['success' => true], 200),
            'https://app.cloudchat.id/*' => Http::response(['success' => true], 200),
        ]);

        $result = (new IntegrationCheckService())->forUsers([
            1 => ['ss' => 'SS-' . uniqid(), 'cc' => 'CC-' . uniqid()],
        ]);

        $this->assertSame(IntegrationCheckService::STATUS_OK, $result[1]['ss']);
        $this->assertSame(IntegrationCheckService::STATUS_OK, $result[1]['cc']);
    }

    public function test_invalid_keys_are_marked_failed(): void
    {
        Http::fake([
            'https://api.starsender.online/*' => Http::response(['success' => false], 200),
            'https://app.cloudchat.id/*' => Http::response([], 401),
        ]);

        $result = (new IntegrationCheckService())->forUsers([
            1 => ['ss' => 'SS-' . uniqid(), 'cc' => 'CC-' . uniqid()],
        ]);

        $this->assertSame(IntegrationCheckService::STATUS_FAIL, $result[1]['ss']);
        $this->assertSame(IntegrationCheckService::STATUS_FAIL, $result[1]['cc']);
    }

    public function test_results_are_cached_per_key(): void
    {
        Http::fake([
            'https://api.starsender.online/*' => Http::response(['success' => true], 200),
        ]);

        $key = 'SS-' . uniqid();
        $service = new IntegrationCheckService();

        $service->forUsers([1 => ['ss' => $key, 'cc' => '']]);
        $service->forUsers([2 => ['ss' => $key, 'cc' => '']]);

        Http::assertSentCount(1);
    }
}

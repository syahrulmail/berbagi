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
            'https://api.starsender.online/api/check-number' => Http::response(['success' => true], 200),
            'https://app.cloudchat.id/*' => Http::response(['success' => true], 200),
        ]);

        $result = (new IntegrationCheckService())->forUsers([
            1 => ['ss' => 'SS-' . uniqid(), 'cc' => 'CC-' . uniqid()],
        ]);

        $this->assertSame(IntegrationCheckService::STATUS_OK, $result[1]['ss']);
        $this->assertSame(IntegrationCheckService::STATUS_OK, $result[1]['cc']);

        Http::assertSent(function ($request) {
            return strpos($request->url(), '/api/check-number') !== false;
        });
        Http::assertNotSent(function ($request) {
            return strpos($request->url(), '/api/devices') !== false;
        });
    }

    public function test_starsender_check_number_failure_is_marked_failed(): void
    {
        Http::fake([
            'https://api.starsender.online/api/check-number' => Http::response(['success' => false], 200),
        ]);

        $result = (new IntegrationCheckService())->forUsers([
            1 => ['ss' => 'SS-' . uniqid(), 'cc' => ''],
        ]);

        $this->assertSame(IntegrationCheckService::STATUS_FAIL, $result[1]['ss']);
        Http::assertNotSent(function ($request) {
            return strpos($request->url(), '/api/devices') !== false;
        });
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

    public function test_transient_failure_does_not_downgrade_known_good_key(): void
    {
        $key = 'SS-' . uniqid();
        $service = new IntegrationCheckService();

        Http::fake([
            'https://api.starsender.online/*' => Http::response(['success' => true], 200),
        ]);
        $this->assertSame(IntegrationCheckService::STATUS_OK, $service->forUsers([1 => ['ss' => $key, 'cc' => '']])[1]['ss']);

        Cache::forget('integration_status_ss_' . sha1($key));

        Http::fake([
            'https://api.starsender.online/*' => Http::response('', 500),
        ]);
        $this->assertSame(
            IntegrationCheckService::STATUS_OK,
            $service->forUsers([1 => ['ss' => $key, 'cc' => '']])[1]['ss'],
            'Kegagalan sementara (5xx) tidak boleh menandai kunci yang sehat sebagai fail.'
        );
    }

    public function test_transient_failure_is_short_cached_for_never_good_key(): void
    {
        $key = 'SS-' . uniqid();
        $service = new IntegrationCheckService();

        Http::fake([
            'https://api.starsender.online/*' => Http::response('', 429),
        ]);

        $this->assertSame(IntegrationCheckService::STATUS_FAIL, $service->forUsers([1 => ['ss' => $key, 'cc' => '']])[1]['ss']);

        $cached = Cache::get('integration_status_ss_' . sha1($key));
        $this->assertSame(IntegrationCheckService::STATUS_FAIL, $cached);
    }

    public function test_key_recovers_after_transient_failure_is_cleared(): void
    {
        $key = 'SS-' . uniqid();
        $service = new IntegrationCheckService();

        Http::fake([
            'https://api.starsender.online/*' => Http::response('', 429),
        ]);
        $this->assertSame(IntegrationCheckService::STATUS_FAIL, $service->forUsers([1 => ['ss' => $key, 'cc' => '']])[1]['ss']);

        Cache::forget('integration_status_ss_' . sha1($key));

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake([
            'https://api.starsender.online/*' => Http::response(['success' => true], 200),
        ]);
        $this->assertSame(IntegrationCheckService::STATUS_OK, $service->forUsers([1 => ['ss' => $key, 'cc' => '']])[1]['ss']);
    }

    public function test_definitive_device_disconnected_is_cached_as_fail(): void
    {
        Http::fake([
            'https://api.starsender.online/*' => Http::response([
                'success' => false,
                'data' => ['status' => false, 'message' => 'Device not connected'],
            ], 400),
        ]);

        $key = 'SS-' . uniqid();
        $service = new IntegrationCheckService();

        $this->assertSame(IntegrationCheckService::STATUS_FAIL, $service->forUsers([1 => ['ss' => $key, 'cc' => '']])[1]['ss']);

        Http::fake();
        (new IntegrationCheckService())->forUsers([1 => ['ss' => $key, 'cc' => '']]);
        Http::assertNothingSent();
    }
}

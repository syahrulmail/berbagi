<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Tests\TestCase;

class RootUrlHostTest extends TestCase
{
    /**
     * Terapkan logika forceRootUrl dari AppServiceProvider untuk request tertentu.
     */
    protected function applyRootUrl(Request $request): void
    {
        $this->app->instance('request', $request);

        $provider = new AppServiceProvider($this->app);

        $method = new \ReflectionMethod($provider, 'configureSubDirectory');
        $method->setAccessible(true);
        $method->invoke($provider);
    }

    public function test_root_url_follows_www_host_over_https()
    {
        $this->applyRootUrl(Request::create('https://www.berbagi.or.id/mo/login'));

        $this->assertStringStartsWith('https://www.berbagi.or.id', url('/mo/login'));
    }

    public function test_root_url_follows_non_www_host_over_http()
    {
        $this->applyRootUrl(Request::create('http://berbagi.or.id/mo/login'));

        $this->assertStringStartsWith('http://berbagi.or.id', url('/mo/login'));
    }

    public function test_root_url_and_scheme_match_current_request()
    {
        $this->applyRootUrl(Request::create('https://berbagi.or.id/mo/login'));

        $this->assertStringStartsWith('https://berbagi.or.id', url('/dashboard'));
    }
}

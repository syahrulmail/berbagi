<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.custom');

        \Illuminate\Support\Facades\View::composer('layouts.public', function ($view) {
            $view->with('siteWaNumber', preg_replace('/\D/', '', \App\Models\Setting::get('wa_public_number', '6281234567890')));
        });

        if (! $this->app->runningInConsole()) {
            $this->configureSubDirectory();
        }
    }

    /**
     * Mendukung pemasangan aplikasi pada sub-direktori
     * (mis. http://berbagi.or.id/berbagi) di shared hosting cPanel.
     *
     * Symfony menurunkan base path/url dari SCRIPT_NAME & PHP_SELF secara
     * lazy. Dengan SCRIPT_NAME = "<prefix>/index.php", preparasi base url
     * memakai fallback dirname sehingga menghasilkan baseUrl = prefix
     * sub-direktori untuk semua request bersih (tanpa segmen /public/).
     * Hasilnya: routing, fullUrl() untuk redirect-back, dan URL generation
     * seluruhnya konsisten.
     *
     * @return void
     */
    protected function configureSubDirectory()
    {
        $prefix = (string) parse_url(config('app.url'), PHP_URL_PATH);

        if (! empty($prefix) && $prefix !== '/') {
            $server = request()->server;

            $server->set('SCRIPT_NAME', $prefix . '/index.php');
            $server->set('PHP_SELF', $prefix . '/index.php');
        }

        \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
    }
}

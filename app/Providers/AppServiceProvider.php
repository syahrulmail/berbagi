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
        require_once __DIR__ . '/../helpers.php';
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
            $view->with('trustbarText', \App\Models\Setting::get('trustbar_text', 'Badan Wakaf Al Qur\'an · Terdaftar & Berizin'));
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
     * PENTING: host & skema root URL diambil dari permintaan yang sedang
     * berjalan, bukan dari host statis APP_URL. Sebab cookie sesi bersifat
     * host-only; bila form POST mengarah ke host yang berbeda dari host saat
     * halaman dimuat (mis. pengguna membuka lewat "www" tapi aksi form
     * mengarah ke domain tanpa "www"), browser tidak mengirim cookie sesi
     * sehingga validasi CSRF gagal dengan error 419 Page Expired.
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

        $root = request()->getSchemeAndHttpHost();

        if (! empty($prefix) && $prefix !== '/') {
            $root .= $prefix;
        }

        \Illuminate\Support\Facades\URL::forceRootUrl($root);
    }
}

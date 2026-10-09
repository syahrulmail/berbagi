<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Memeriksa status koneksi kunci integrasi WhatsApp per pengguna.
 *
 * - API SS (Starsender):
 *     1) POST /api/check-number   -> memakai Device API key, sekaligus
 *        memastikan device benar-benar terhubung (aktif).
 *     2) Bila gagal, GET /api/devices -> memakai Account API key, sebagai
 *        fallback bila yang disimpan ternyata Account key.
 * - API CC (CloudChat):
 *     POST /check-number memakai Bearer key (sk_live_...).
 *
 * Hasil tiap kunci di-cache agar daftar pengguna tidak memanggil
 * provider berulang-ulang pada setiap pemuatan halaman.
 */
class IntegrationCheckService
{
    public const STATUS_EMPTY = 'empty';
    public const STATUS_OK = 'ok';
    public const STATUS_FAIL = 'fail';

    protected const TTL_MINUTES = 10;
    protected const TIMEOUT_SECONDS = 6;
    protected const PROBE_PHONE = '6281234567890';

    /**
     * Hitung status untuk banyak pengguna sekaligus dalam satu pool request.
     *
     * @param  array<int,array{ss:string,cc:string}>  $profiles  keyed by user id
     * @return array<int,array{ss:string,cc:string}>
     */
    public function forUsers(array $profiles): array
    {
        $result = [];
        $ssJobs = [];
        $ccJobs = [];

        foreach ($profiles as $userId => $keys) {
            $ss = trim((string) ($keys['ss'] ?? ''));
            if ($ss === '') {
                $result[$userId]['ss'] = self::STATUS_EMPTY;
            } else {
                $cacheKey = $this->cacheKey('ss', $ss);
                $cached = Cache::get($cacheKey);
                if ($cached !== null) {
                    $result[$userId]['ss'] = $cached;
                } else {
                    $ssJobs[$userId . '|ss'] = ['key' => $ss, 'cacheKey' => $cacheKey];
                }
            }

            $cc = trim((string) ($keys['cc'] ?? ''));
            if ($cc === '') {
                $result[$userId]['cc'] = self::STATUS_EMPTY;
            } else {
                $cacheKey = $this->cacheKey('cc', $cc);
                $cached = Cache::get($cacheKey);
                if ($cached !== null) {
                    $result[$userId]['cc'] = $cached;
                } else {
                    $ccJobs[$userId . '|cc'] = ['key' => $cc, 'cacheKey' => $cacheKey];
                }
            }
        }

        if (empty($ssJobs) && empty($ccJobs)) {
            return $result;
        }

        $responses = Http::pool(function (Pool $pool) use ($ssJobs, $ccJobs) {
            foreach ($ssJobs as $id => $job) {
                $pool->as($id)->acceptJson()->timeout(self::TIMEOUT_SECONDS)
                    ->withHeaders(['Authorization' => $job['key']])
                    ->post($this->starsenderCheckUrl(), ['number' => self::PROBE_PHONE]);
            }

            foreach ($ccJobs as $id => $job) {
                $pool->as($id)->acceptJson()->timeout(self::TIMEOUT_SECONDS)
                    ->withToken($job['key'])
                    ->post($this->cloudchatUrl(), ['phone' => self::PROBE_PHONE]);
            }
        });

        // CloudChat: satu probe sudah final.
        foreach ($ccJobs as $id => $job) {
            list($userId) = explode('|', $id);
            $status = $this->isSuccess($responses[$id] ?? null) ? self::STATUS_OK : self::STATUS_FAIL;
            Cache::put($job['cacheKey'], $status, now()->addMinutes(self::TTL_MINUTES));
            $result[$userId]['cc'] = $status;
        }

        // Starsender: probe check-number; yang gagal dicoba ulang via devices.
        $ssFallback = [];
        foreach ($ssJobs as $id => $job) {
            list($userId) = explode('|', $id);
            if ($this->isSuccess($responses[$id] ?? null)) {
                Cache::put($job['cacheKey'], self::STATUS_OK, now()->addMinutes(self::TTL_MINUTES));
                $result[$userId]['ss'] = self::STATUS_OK;
            } else {
                $ssFallback[$id] = $job;
            }
        }

        if (! empty($ssFallback)) {
            $fallbackResponses = Http::pool(function (Pool $pool) use ($ssFallback) {
                foreach ($ssFallback as $id => $job) {
                    $pool->as($id)->acceptJson()->timeout(self::TIMEOUT_SECONDS)
                        ->withHeaders(['Authorization' => $job['key']])
                        ->get($this->starsenderDevicesUrl());
                }
            });

            foreach ($ssFallback as $id => $job) {
                list($userId) = explode('|', $id);
                $status = $this->isSuccess($fallbackResponses[$id] ?? null) ? self::STATUS_OK : self::STATUS_FAIL;
                Cache::put($job['cacheKey'], $status, now()->addMinutes(self::TTL_MINUTES));
                $result[$userId]['ss'] = $status;
            }
        }

        return $result;
    }

    /**
     * Apakah respons menandakan koneksi/kunci valid.
     *
     * @param  mixed  $response
     */
    protected function isSuccess($response): bool
    {
        if (! $response instanceof Response || ! $response->successful()) {
            return false;
        }

        $json = $response->json();

        if (is_array($json) && array_key_exists('success', $json) && ! $json['success']) {
            return false;
        }

        return true;
    }

    protected function cacheKey(string $provider, string $key): string
    {
        return 'integration_status_' . $provider . '_' . sha1($key);
    }

    protected function starsenderBase(): string
    {
        $base = config('services.starsender.base_url', 'https://api.starsender.online');

        return rtrim((string) ($base ?: 'https://api.starsender.online'), '/');
    }

    protected function starsenderCheckUrl(): string
    {
        return $this->starsenderBase() . '/api/check-number';
    }

    protected function starsenderDevicesUrl(): string
    {
        return $this->starsenderBase() . '/api/devices';
    }

    protected function cloudchatUrl(): string
    {
        $base = config('services.cloudchat.base_url', 'https://app.cloudchat.id/api/public/v1');

        return rtrim((string) ($base ?: 'https://app.cloudchat.id/api/public/v1'), '/') . '/check-number';
    }
}

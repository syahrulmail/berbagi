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
 *     POST /api/check-number -> memakai Device API key, sekaligus
 *     memastikan device benar-benar terhubung (aktif).
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
    protected const TTL_TRANSIENT_MINUTES = 1;
    protected const TTL_LAST_OK_HOURS = 6;
    protected const TIMEOUT_SECONDS = 6;
    protected const PROBE_PHONE = '6281234567890';

    /**
     * Status hasil klasifikasi sebuah probe.
     * - STATUS_OK / STATUS_FAIL: hasil definitif (aman di-cache lama).
     * - STATUS_UNKNOWN: gangguan sementara (timeout, koneksi, 5xx, 429);
     *   tidak boleh di-cache sebagai kegagalan permanen.
     */
    protected const STATUS_UNKNOWN = 'unknown';

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
            $result[$userId]['cc'] = $this->resolve(
                'cc', $job['key'], $job['cacheKey'], $responses[$id] ?? null
            );
        }

        // Starsender: cukup probe check-number memakai Device API key.
        foreach ($ssJobs as $id => $job) {
            list($userId) = explode('|', $id);
            $result[$userId]['ss'] = $this->resolve(
                'ss', $job['key'], $job['cacheKey'], $responses[$id] ?? null
            );
        }

        return $result;
    }

    /**
     * Tentukan status akhir sebuah probe dan simpan ke cache.
     *
     * Kegagalan definitif (kunci salah, device terputus) di-cache normal,
     * sedangkan gangguan sementara (timeout, koneksi, 5xx, 429) tidak
     * boleh menimpa status sehat menjadi "fail" selama berapa menit.
     */
    protected function resolve(string $provider, string $key, string $cacheKey, $response): string
    {
        $status = $this->classify($response);

        if ($status === self::STATUS_OK) {
            Cache::put($cacheKey, self::STATUS_OK, now()->addMinutes(self::TTL_MINUTES));
            Cache::put($this->lastOkKey($provider, $key), true, now()->addHours(self::TTL_LAST_OK_HOURS));

            return self::STATUS_OK;
        }

        if ($status === self::STATUS_FAIL) {
            Cache::put($cacheKey, self::STATUS_FAIL, now()->addMinutes(self::TTL_MINUTES));

            return self::STATUS_FAIL;
        }

        // Gangguan sementara: pertahankan status sehat terakhir bila ada.
        if (Cache::get($this->lastOkKey($provider, $key))) {
            Cache::put($cacheKey, self::STATUS_OK, now()->addMinutes(self::TTL_TRANSIENT_MINUTES));

            return self::STATUS_OK;
        }

        // Belum pernah sukses: tandai gagal sesaat agar cepat dipulihkan.
        Cache::put($cacheKey, self::STATUS_FAIL, now()->addMinutes(self::TTL_TRANSIENT_MINUTES));

        return self::STATUS_FAIL;
    }

    /**
     * Klasifikasi respons probe menjadi ok / fail / unknown (sementara).
     *
     * @param  mixed  $response
     */
    protected function classify($response): string
    {
        if (! $response instanceof Response) {
            // Timeout, koneksi putus, atau exception lain: bukan bukti kunci salah.
            return self::STATUS_UNKNOWN;
        }

        $status = $response->status();

        if ($status === 429 || $status >= 500) {
            return self::STATUS_UNKNOWN;
        }

        if ($status === 401 || $status === 403) {
            return self::STATUS_FAIL;
        }

        $json = $response->json();

        if ($status >= 400) {
            // Starsender memakai 400 + success:false untuk "Device not connected".
            return self::STATUS_FAIL;
        }

        if (is_array($json) && array_key_exists('success', $json) && ! $json['success']) {
            return self::STATUS_FAIL;
        }

        return self::STATUS_OK;
    }

    protected function lastOkKey(string $provider, string $key): string
    {
        return 'integration_last_ok_' . $provider . '_' . sha1($key);
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

    protected function cloudchatUrl(): string
    {
        $base = config('services.cloudchat.base_url', 'https://app.cloudchat.id/api/public/v1');

        return rtrim((string) ($base ?: 'https://app.cloudchat.id/api/public/v1'), '/') . '/check-number';
    }
}

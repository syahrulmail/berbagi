<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Memeriksa status koneksi kunci integrasi WhatsApp per pengguna.
 *
 * - API SS  -> Starsender (Authorization: <key>)
 * - API CC  -> CloudChat  (Bearer <key>)
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
        $jobs = [];

        foreach ($profiles as $userId => $keys) {
            foreach (['ss', 'cc'] as $provider) {
                $key = trim((string) ($keys[$provider] ?? ''));

                if ($key === '') {
                    $result[$userId][$provider] = self::STATUS_EMPTY;
                    continue;
                }

                $cacheKey = $this->cacheKey($provider, $key);
                $cached = Cache::get($cacheKey);

                if ($cached !== null) {
                    $result[$userId][$provider] = $cached;
                    continue;
                }

                $jobs[$userId . '|' . $provider] = [
                    'provider' => $provider,
                    'key' => $key,
                    'cacheKey' => $cacheKey,
                ];
            }
        }

        if (! empty($jobs)) {
            $responses = Http::pool(function (Pool $pool) use ($jobs) {
                foreach ($jobs as $id => $job) {
                    $request = $pool->as($id)->acceptJson()->timeout(self::TIMEOUT_SECONDS);

                    if ($job['provider'] === 'ss') {
                        $request->withHeaders(['Authorization' => $job['key']])
                            ->get($this->starsenderUrl());
                    } else {
                        $request->withToken($job['key'])
                            ->post($this->cloudchatUrl(), ['phone' => self::PROBE_PHONE]);
                    }
                }
            });

            foreach ($jobs as $id => $job) {
                list($userId, $provider) = explode('|', $id);

                $status = $this->interpret($job['provider'], isset($responses[$id]) ? $responses[$id] : null);

                Cache::put($job['cacheKey'], $status, now()->addMinutes(self::TTL_MINUTES));

                $result[$userId][$provider] = $status;
            }
        }

        return $result;
    }

    protected function interpret(string $provider, $response): string
    {
        if (! $response instanceof Response || ! $response->successful()) {
            return self::STATUS_FAIL;
        }

        if ($provider === 'ss') {
            $json = $response->json();

            if (is_array($json) && array_key_exists('success', $json) && ! $json['success']) {
                return self::STATUS_FAIL;
            }
        }

        return self::STATUS_OK;
    }

    protected function cacheKey(string $provider, string $key): string
    {
        return 'integration_status_' . $provider . '_' . sha1($key);
    }

    protected function starsenderUrl(): string
    {
        $base = config('services.starsender.base_url', 'https://api.starsender.online');

        return rtrim((string) ($base ?: 'https://api.starsender.online'), '/') . '/api/devices';
    }

    protected function cloudchatUrl(): string
    {
        $base = config('services.cloudchat.base_url', 'https://app.cloudchat.id/api/public/v1');

        return rtrim((string) ($base ?: 'https://app.cloudchat.id/api/public/v1'), '/') . '/check-number';
    }
}

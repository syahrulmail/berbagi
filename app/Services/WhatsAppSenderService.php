<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengiriman pesan WhatsApp via Starsender (SS) dan CloudChat (CC).
 *
 * Semua metode mengembalikan array ternormalisasi:
 *   ['ok' => bool, 'provider' => 'ss'|'cc', 'status' => int|null,
 *    'response' => string, 'error' => string|null]
 */
class WhatsAppSenderService
{
    public const PROVIDER_SS = 'ss';
    public const PROVIDER_CC = 'cc';
    public const PROVIDER_MANUAL = 'manual';

    protected const TIMEOUT_SECONDS = 20;

    /**
     * Kirim via Starsender (Authorization: Device API key).
     *
     * @param  array{media_url?:string,media_type?:string,delay?:int,schedule?:int}  $options
     */
    public function sendStarsender(string $key, string $to, string $body, array $options = []): array
    {
        $mediaUrl = trim((string) ($options['media_url'] ?? ''));
        $isMedia = $mediaUrl !== '';

        $payload = [
            'messageType' => $isMedia ? 'media' : 'text',
            'to' => $this->normalizePhone($to),
            'body' => $body,
        ];

        if ($isMedia) {
            $payload['file'] = $mediaUrl;
        }

        $delay = (int) ($options['delay'] ?? 0);
        if ($delay > 0) {
            $payload['delay'] = $delay;
        }

        $schedule = $options['schedule'] ?? null;
        if ($schedule !== null && (int) $schedule > 0) {
            $payload['schedule'] = (int) $schedule;
        }

        return $this->dispatch(
            self::PROVIDER_SS,
            $this->starsenderBase() . '/api/send',
            ['Authorization' => $key],
            $payload
        );
    }

    /**
     * Kirim via CloudChat (Authorization: Bearer sk_live_...).
     *
     * @param  array{media_url?:string,media_type?:string}  $options
     */
    public function sendCloudchat(string $token, string $to, string $body, array $options = []): array
    {
        $mediaUrl = trim((string) ($options['media_url'] ?? ''));
        $mediaType = trim((string) ($options['media_type'] ?? 'image'));

        $payload = [
            'channel' => 'whatsapp',
            'to' => $this->normalizePhone($to),
        ];

        if ($mediaUrl !== '') {
            $type = in_array($mediaType, ['image', 'video', 'document', 'audio'], true) ? $mediaType : 'image';
            $payload['type'] = $type;
            $payload['url'] = $mediaUrl;
            $payload['media_url'] = $mediaUrl;
            $payload['content'] = ['caption' => $body];
        } else {
            $payload['type'] = 'text';
            $payload['content'] = ['text' => $body];
        }

        return $this->dispatch(
            self::PROVIDER_CC,
            $this->cloudchatBase() . '/messages',
            ['Authorization' => 'Bearer ' . $token],
            $payload
        );
    }

    /**
     * Kirim lewat provider terpilih.
     */
    public function send(string $provider, string $key, string $to, string $body, array $options = []): array
    {
        if ($provider === self::PROVIDER_SS) {
            return $this->sendStarsender($key, $to, $body, $options);
        }

        if ($provider === self::PROVIDER_CC) {
            return $this->sendCloudchat($key, $to, $body, $options);
        }

        return [
            'ok' => false,
            'provider' => $provider,
            'status' => null,
            'response' => '',
            'error' => 'Provider tidak dikenal.',
        ];
    }

    /**
     * Cek apakah nomor terdaftar WhatsApp.
     *
     * @return bool|null  true/false bila terdeteksi, null bila provider gagal.
     */
    public function checkNumber(string $provider, string $key, string $phone)
    {
        $phone = $this->normalizePhone($phone);

        try {
            if ($provider === self::PROVIDER_SS) {
                $response = Http::acceptJson()->timeout(self::TIMEOUT_SECONDS)
                    ->withHeaders(['Authorization' => $key])
                    ->post($this->starsenderBase() . '/api/check-number', ['number' => $phone]);

                if (! $response->successful()) {
                    return null;
                }

                $json = $response->json();
                $status = $json['data']['status'] ?? null;

                return $this->interpretRegistration($status, is_array($json) ? ($json['success'] ?? true) : true);
            }

            if ($provider === self::PROVIDER_CC) {
                $response = Http::acceptJson()->timeout(self::TIMEOUT_SECONDS)
                    ->withToken($key)
                    ->post($this->cloudchatBase() . '/check-number', ['phone' => $phone]);

                if (! $response->successful()) {
                    return null;
                }

                $json = $response->json();
                $registered = $json['data']['is_registered'] ?? null;

                return $this->interpretRegistration($registered, is_array($json) ? ($json['success'] ?? true) : true);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal cek nomor WA: ' . $e->getMessage());
        }

        return null;
    }

    protected function dispatch(string $provider, string $url, array $headers, array $payload): array
    {
        try {
            $response = Http::acceptJson()->timeout(self::TIMEOUT_SECONDS)
                ->withHeaders($headers)
                ->post($url, $payload);

            $body = mb_substr($response->body(), 0, 2000);
            $json = $response->json();
            $ok = $response->successful() && (! is_array($json) || ($json['success'] ?? true) !== false);

            return [
                'ok' => $ok,
                'provider' => $provider,
                'status' => $response->status(),
                'response' => $body,
                'error' => $ok ? null : $this->extractError($json, $response->status()),
            ];
        } catch (\Throwable $e) {
            Log::error('Error WhatsApp ' . $provider . ': ' . $e->getMessage());

            return [
                'ok' => false,
                'provider' => $provider,
                'status' => null,
                'response' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  mixed  $json
     */
    protected function extractError($json, int $status): string
    {
        if (is_array($json)) {
            $message = $json['message'] ?? $json['error'] ?? null;
            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        return 'HTTP ' . $status;
    }

    /**
     * @param  mixed  $value
     */
    protected function interpretRegistration($value, bool $success): ?bool
    {
        if (! $success) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['true', 'valid', 'registered', 'active', 'yes', '1'], true)) {
                return true;
            }
            if (in_array($normalized, ['false', 'invalid', 'not_registered', 'unregistered', 'no', '0'], true)) {
                return false;
            }
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        return null;
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return '';
        }

        if ($digits[0] === '0') {
            $digits = '62' . substr($digits, 1);
        }

        return $digits;
    }

    protected function starsenderBase(): string
    {
        $base = config('services.starsender.base_url', 'https://api.starsender.online');

        return rtrim((string) ($base ?: 'https://api.starsender.online'), '/');
    }

    protected function cloudchatBase(): string
    {
        $base = config('services.cloudchat.base_url', 'https://app.cloudchat.id/api/public/v1');

        return rtrim((string) ($base ?: 'https://app.cloudchat.id/api/public/v1'), '/');
    }
}

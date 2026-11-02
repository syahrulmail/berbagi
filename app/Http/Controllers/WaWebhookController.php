<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FollowupWaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Penerima webhook balasan WhatsApp (Starsender / CloudChat).
 *
 * URL webhook bersifat per pengguna dan per provider, dibangun oleh
 * FollowupWaService::webhookUrl(). Identitas dikirim lewat query string:
 * parameter "u" berisi ID pengguna dan parameter "t" berisi token webhook
 * (token terpisah untuk Starsender dan CloudChat). Jangan menambahkan
 * placeholder apa pun ke URL tersebut.
 */
class WaWebhookController extends Controller
{
    /** @var FollowupWaService */
    protected $service;

    public function __construct(FollowupWaService $service)
    {
        $this->service = $service;
    }

    public function verify(Request $request, string $provider)
    {
        return response()->json(['ok' => true, 'provider' => $provider]);
    }

    /**
     * Endpoint cron: dipanggil tiap menit untuk menjalankan warming otomatis.
     * Setara dengan `php artisan warming:run`.
     */
    public function cron(string $token)
    {
        if (! hash_equals($this->service->cronToken(), $token)) {
            Log::warning('Cron warming ditolak: token tidak valid.');

            return response('forbidden', 403)->header('Content-Type', 'text/plain');
        }

        \Illuminate\Support\Facades\Artisan::call('warming:run');
        $output = trim(\Illuminate\Support\Facades\Artisan::output());

        return response($output !== '' ? $output : 'ok', 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    public function receive(Request $request, string $provider)
    {
        $userId = (int) $request->query('u');
        $token = (string) $request->query('t');
        $user = $userId > 0 ? User::find($userId) : null;

        if ($user === null || ! hash_equals($this->service->webhookToken($user, $provider), $token)) {
            Log::warning('Webhook WA ditolak: identitas/token tidak valid.', ['user' => $userId]);

            return response()->json(['ok' => false, 'error' => 'invalid_identity'], 403);
        }

        $payload = $request->all();
        $from = $this->extractFrom($payload);
        $text = $this->extractText($payload);

        if ($from !== '') {
            $this->service->handleIncomingBroadcastReply(
                $user,
                $from,
                $this->extractTimestamp($payload),
                $this->extractMessageId($payload)
            );

            $this->service->recordWarmingIncoming($user, $from, $text);
        }

        Log::info('Webhook WA diterima.', [
            'provider' => $provider,
            'user' => $user->id,
            'from' => $from,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    protected function extractFrom(array $payload): string
    {
        $candidates = [
            $payload['from'] ?? null,
            $payload['phone'] ?? null,
            $payload['number'] ?? null,
            $payload['data']['from'] ?? null,
            $payload['data']['phone'] ?? null,
            $payload['data']['sender'] ?? null,
            $payload['sender'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $digits = preg_replace('/\D+/', '', (string) $candidate);

            if ($digits !== '' && strlen($digits) >= 8) {
                return $digits;
            }
        }

        return '';
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    protected function extractText(array $payload): string
    {
        $candidates = [
            $payload['message'] ?? null,
            $payload['text'] ?? null,
            $payload['body'] ?? null,
            $payload['data']['text'] ?? null,
            $payload['data']['message'] ?? null,
            $payload['data']['body'] ?? null,
            $payload['data']['content']['text'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    /**
     * Waktu pesan masuk menurut provider. Null berarti pakai waktu sekarang.
     *
     * @param  array<string,mixed>  $payload
     */
    protected function extractTimestamp(array $payload): ?Carbon
    {
        $candidates = [
            $payload['timestamp'] ?? null,
            $payload['time'] ?? null,
            $payload['created_at'] ?? null,
            $payload['date'] ?? null,
            $payload['data']['timestamp'] ?? null,
            $payload['data']['time'] ?? null,
            $payload['data']['created_at'] ?? null,
            $payload['message']['timestamp'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }

            try {
                if (is_numeric($candidate)) {
                    $value = (float) $candidate;

                    if ($value >= 1000000000000) {
                        $value = $value / 1000;
                    }

                    if ($value > 0) {
                        return Carbon::createFromTimestamp((int) $value);
                    }
                } else {
                    return Carbon::parse((string) $candidate);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    protected function extractMessageId(array $payload): ?string
    {
        $candidates = [
            $payload['message_id'] ?? null,
            $payload['id'] ?? null,
            $payload['data']['message_id'] ?? null,
            $payload['data']['id'] ?? null,
            $payload['message']['id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return trim((string) $candidate);
            }
        }

        return null;
    }
}

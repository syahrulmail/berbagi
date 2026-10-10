<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FollowupWaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Penerima webhook balasan WhatsApp (Starsender / CloudChat).
 *
 * URL webhook per pengguna memuat identitas:
 *   /wa/webhook/{provider}?u={userId}&t={token}
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

    public function receive(Request $request, string $provider)
    {
        $userId = (int) $request->query('u');
        $token = (string) $request->query('t');
        $user = $userId > 0 ? User::find($userId) : null;

        if ($user === null || ! hash_equals($this->service->webhookToken($user), $token)) {
            Log::warning('Webhook WA ditolak: identitas/token tidak valid.', ['user' => $userId]);

            return response()->json(['ok' => false, 'error' => 'invalid_identity'], 403);
        }

        $payload = $request->all();
        $from = $this->extractFrom($payload);
        $text = $this->extractText($payload);

        if ($from !== '') {
            $broadcast = $this->service->activeBroadcast($user);

            if ($broadcast !== null && $broadcast->mechanism === 'auto') {
                $this->service->recordReply($broadcast);
                $this->service->nextAutoBatch($broadcast, 5);
            }

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
}

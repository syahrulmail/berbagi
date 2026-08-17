<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiService
{
    /**
     * Kirim pesan via WhatsApp API.
     *
     * Konfigurasi via env:
     *   WHATSAPP_API_URL   - endpoint API
     *   WHATSAPP_API_TOKEN - token otorisasi
     *
     * Implementasi di sini mengikuti format generik JSON yang umum dipakai
     * provider WhatsApp Business API / gateway lokal (Fonnte, Wablas, dst).
     * Sesuaikan struktur payload dengan provider yang dipakai BWA.
     */
    public function send(WhatsappMessage $message): bool
    {
        $baseUrl = config('services.whatsapp.url');
        $token = config('services.whatsapp.token');

        if (empty($baseUrl) || empty($token)) {
            Log::warning('WhatsApp API belum dikonfigurasi (WHATSAPP_API_URL / WHATSAPP_API_TOKEN kosong).', [
                'message_id' => $message->id,
            ]);

            $message->update([
                'status' => WhatsappMessage::STATUS_FAILED,
                'response' => 'API belum dikonfigurasi',
            ]);

            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->post(rtrim($baseUrl, '/') . '/send-message', [
                    'phone' => $message->phone,
                    'message' => $message->message,
                ]);

            if ($response->successful()) {
                $message->update([
                    'status' => WhatsappMessage::STATUS_SENT,
                    'response' => $response->body(),
                    'sent_at' => now(),
                ]);

                return true;
            }

            $message->update([
                'status' => WhatsappMessage::STATUS_FAILED,
                'response' => $response->body(),
            ]);

            Log::error('Gagal mengirim WhatsApp', [
                'message_id' => $message->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            $message->update([
                'status' => WhatsappMessage::STATUS_FAILED,
                'response' => $e->getMessage(),
            ]);

            Log::error('Error WhatsApp API: ' . $e->getMessage(), [
                'message_id' => $message->id,
            ]);

            return false;
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\WhatsappMessage;
use App\Services\WhatsAppApiService;
use Illuminate\Console\Command;

class SendWhatsappMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:send {--limit=20}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim pesan WhatsApp yang berstatus pending melalui API.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(WhatsAppApiService $service)
    {
        $limit = (int) $this->option('limit');

        $messages = WhatsappMessage::where('status', WhatsappMessage::STATUS_PENDING)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($messages->isEmpty()) {
            $this->info('Tidak ada pesan pending.');

            return 0;
        }

        $sent = 0;
        $failed = 0;

        foreach ($messages as $message) {
            if ($service->send($message)) {
                $sent++;
                $this->info("Terikirim: {$message->phone}");
            } else {
                $failed++;
                $this->error("Gagal: {$message->phone}");
            }
        }

        $this->info("Selesai. Terkirim: {$sent}, Gagal: {$failed}.");

        return 0;
    }
}

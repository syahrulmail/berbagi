<?php

namespace App\Console\Commands;

use App\Services\FollowupWaService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendFollowupBroadcasts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'followup:starsender {--seconds=50 : Batas waktu proses dalam detik}';

    /**
     * @var string
     */
    protected $description = 'Kirim antrean broadcast Follow-up WA sesuai jadwal dan jeda acak.';

    /**
     * @return int
     */
    public function handle(FollowupWaService $service)
    {
        $seconds = max(0, (int) $this->option('seconds'));
        $until = Carbon::now()->addSeconds($seconds);

        $result = $service->processQueue($until);

        $this->info('Broadcast: ' . $result['sent'] . ' terkirim, ' . $result['failed'] . ' gagal.');

        return 0;
    }
}

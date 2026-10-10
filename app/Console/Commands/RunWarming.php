<?php

namespace App\Console\Commands;

use App\Services\FollowupWaService;
use Illuminate\Console\Command;

class RunWarming extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warming:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jalankan warming WhatsApp otomatis sesuai konfigurasi (jam, hari, jeda).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(FollowupWaService $service)
    {
        $result = $service->runScheduledWarming();

        if ($result['skipped'] !== null) {
            $this->info('Warming dilewati (' . $result['skipped'] . ').');

            return 0;
        }

        $this->info('Warming: ' . $result['ran'] . ' pengguna, '
            . $result['sent'] . ' terkirim, ' . $result['failed'] . ' gagal.');

        return 0;
    }
}

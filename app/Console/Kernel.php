<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Kirim pesan WhatsApp pending setiap 5 menit.
        // Dijalankan in-process (Artisan::call) karena proc_open/exec disabled di produksi,
        // sehingga $schedule->command() tidak dapat menjalankan sub-proses.
        $schedule->call(function () {
            Artisan::call('whatsapp:send');
        })->name('wa-send')->everyFiveMinutes()->withoutOverlapping();

        // Warming otomatis sesuai konfigurasi (jam, hari, jeda)
        $schedule->call(function () {
            Artisan::call('warming:run');
        })->name('wa-warming')->everyMinute()->withoutOverlapping();

        // Contoh otomasi lain: cek status kontak berulang kali dihubungi
        // $schedule->command('contacts:status-check')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

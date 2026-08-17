<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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
        // Kirim pesan WhatsApp pending setiap 5 menit
        $schedule->command('whatsapp:send')
            ->everyFiveMinutes()
            ->withoutOverlapping();

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

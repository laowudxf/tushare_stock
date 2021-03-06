<?php

namespace App\Console;

use App\Console\Commands\CheckMatureOrder;
use App\Console\Commands\SettlementOrder;
use App\Console\Commands\UpdateStockDayliyAll;
use App\Console\Commands\UpdateTradeDates;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(UpdateStockDayliyAll::class, ["--week"])->dailyAt("16:00");
        $schedule->command(UpdateTradeDates::class, ["--week"])->dailyAt("15:30");
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

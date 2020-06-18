<?php

namespace App\Console\Commands;

use App\Http\Controllers\StockSyncController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateStockDayliyAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:stockDailyAll {--week}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '拉取所有股票日线';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
//        $week = $this->option("week");
//        Log::info("schedule --- week:{$week}");
//        return;

        $week = $this->option("week");
        $ssc = new StockSyncController();
        if ($week) {

            if (now()->isWeekend()) {
                Log::info("周末不需要拉取");
                return;
            }

            $ssc->syncStockDailyWeek();
            $ssc->syncStockFQWeek();
        } else {
            $ssc->syncStockDailyAll();
            $ssc->syncStockFQ();
        }
    }
}

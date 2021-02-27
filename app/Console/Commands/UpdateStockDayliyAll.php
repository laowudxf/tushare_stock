<?php

namespace App\Console\Commands;

use App\Http\Controllers\StockSyncController;
use App\Http\Controllers\UpdateController;
use App\Models\TradeDate;
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
    public function handle(UpdateController $updateController)
    {
        //
//        $week = $this->option("week");
//        Log::info("schedule --- week:{$week}");
//        return;

        $week = $this->option("week");
        $ssc = new StockSyncController();
        $updateController->updateTradeDate($week);
        if ($week) {
            if (now()->isWeekend()) {
                Log::info("周末不需要拉取");
                return;
            }

            $tradeDate = TradeDate::orderBy("trade_date", 'desc')->first();
            $ssc->syncStockDailyDay($tradeDate->trade_date);
            $ssc->syncStockFQDay($tradeDate->trade_date);
            if (now()->isFriday()) {
                $updateController->generatorWeekStock(null, true, $this);
            }
        } else {
            $ssc->syncStockDailyAll();
            $ssc->syncStockFQ();
        }
    }
}

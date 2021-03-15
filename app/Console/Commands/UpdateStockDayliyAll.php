<?php

namespace App\Console\Commands;

use App\Http\Controllers\StockSyncController;
use App\Http\Controllers\UpdateController;
use App\Models\TradeDate;
use Carbon\Carbon;
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

        $week = $this->option("week");
        $ssc = new StockSyncController();
        $updateController->updateTradeDate($week);
        if ($week) {
//            $date = "20210308";

//            $tradeDate = TradeDate::orderBy("trade_date", 'desc')->first();
//            $date = $tradeDate->trade_date;
            $date = now()->format("Ymd");
            $dateCarbon = Carbon::createFromFormat("Ymd", $date);
            if ($dateCarbon->isWeekend()) {
                Log::info("周末不需要拉取");
                return;
            }


            $ssc->syncStockDailyDay($date);
            $ssc->syncStockFQDay($date);
            $this->call("stock:updateDailyExtra", [
                "--week" => true,
                "--date" => $date
            ]);

            $this->call("stock:genIndStock", [
                "--day" => true,
                "--date" => $date
            ]);

            if ($dateCarbon->isFriday()) {
                $updateController->generatorWeekStock(null, true, $this);
            }
        } else {
            $ssc->syncStockDailyAll();
            $ssc->syncStockFQ();
            $this->call("stock:updateDailyExtra");
            $this->call("stock:genIndStock");
            $updateController->generatorWeekStock(null, false, $this);
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Models\StockDaily;
use App\Models\StockWeek;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateStockWeek extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:stockWeek {--week}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成周线';

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
       $allStocks = Stock::all(["id"]);
       $isWeek = $this->option("week");
       foreach ($allStocks as $index => $stock) {
           $this->info("dealing {$index}");
           $stockDaily = null;
           if ($isWeek == false) {
               $stockDaily = StockDaily::where('stock_id', $stock->id)->orderBy("trade_date")->get();
           } else {
               list($mondayStr, $fridayStr) = $this->calcMondayAndFriday(now()->format("Ymd"));
               $stockDaily = StockDaily::where('stock_id', $stock->id)
                   ->where('trade_date', '>=', $mondayStr)
                   ->where('trade_date', '<=', $fridayStr)
                   ->orderBy("trade_date")->get();
           }
           $date = $stockDaily[0]->trade_date;
           list($mondayStr, $fridayStr) = $this->calcMondayAndFriday($date);
           $insertStockWeekData = [];
           $weekData = [];
           $lastClose = 0;
           foreach ($stockDaily as $daily) {
               if (intval($daily->trade_date) >= $mondayStr && intval($daily->trade_date) <= $fridayStr) { //在本周
                   $weekData[] = $daily;
               } else {
                   if (empty($weekData) == false) {
                      $insertStockWeekData[] = $this->dealWeekData($weekData,$lastClose);
                   }
                   list($mondayStr, $fridayStr) = $this->calcMondayAndFriday($daily->trade_date);
                   $weekData = [];
                   $weekData[] = $daily;
               }
           }
           if (empty($weekData) == false) {
               $insertStockWeekData[] = $this->dealWeekData($weekData, $lastClose);
           }
           if ($isWeek == false) {
               StockWeek::insert($insertStockWeekData);
           } else {
              foreach ($insertStockWeekData as $data) {
                  $exists = StockWeek::where('stock_id', $data["stock_id"])->where('trade_date', $data["trade_date"])->exists();
                 if($exists) {
                     StockWeek::where('stock_id', $data["stock_id"])->where('trade_date', $data["trade_date"])->update($data);
                 } else {
                     StockWeek::create($data);
                 }
              }
           }
       }
    }

    function dealWeekData($weekData, &$lastClose) {
        $weekDataCollect = Collect($weekData);
        $change = $weekDataCollect->last()->close - $weekData[0]->open;
        $pct_chg = $change / $weekData[0]->open;
        $result = [
            "stock_id" => $weekData[0]->stock_id,
            "trade_date" => $weekDataCollect->last()->trade_date,
            "open" => $weekData[0]->open,
            "high" => $weekDataCollect->max("high"),
            "low" => $weekDataCollect->max("low"),
            "close" => $weekDataCollect->last()->close,
            "pre_close" => $lastClose,
            "change" => $weekDataCollect->last()->close - $weekData[0]->open,
            "pct_chg" => $pct_chg,
            "vol" => $weekDataCollect->sum('vol'),
            "amount" => $weekDataCollect->sum('amount'),
            "fq_factor" => $weekDataCollect->last()->fq_factor
        ];
        $lastClose = $weekDataCollect->last()->close;
        return $result;
    }
    public function calcMondayAndFriday($dateStr) {

        $d = Carbon::createFromFormat("Ymd", $dateStr);
        $monday = $d->copy()->subDays($d->dayOfWeekIso - 1);
        $friday = $d->copy()->addDays(5 - $d->dayOfWeekIso);
        return [$monday->format("Ymd"), $friday->format("Ymd")];
    }
}

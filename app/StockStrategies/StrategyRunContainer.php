<?php


namespace App\StockStrategies;


use App\Models\Stock;
use App\Models\StockDaily;
use Carbon\Carbon;

class StrategyRunContainer
{
    private $strategy;
    private $startDate;
    private $endDate;

    public $stockCodePool;
    public $stockDailyData = [];
    public $stockCloses = [];
    public $stockTecData = [];

    public function __construct(Carbon $startDate,Carbon $endDate, DefaultStockStrategy $strategy)
    {
        $this->strategy = $strategy;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    function run(){
        $this->stockCodePool = $this->strategy->ensureStockPool();
        $this->initData();
        $datePoint = $this->startDate->copy();
        while ($datePoint < $this->endDate) {
//            var_dump($datePoint->format("Ymd"));
            $this->strategy->openQuotation($datePoint);
            $this->strategy->closeQuotation($datePoint);
            $datePoint = $datePoint->addDays(1);
        };

    }

    function initData() {
        $stocks = Stock::whereIn('ts_code', $this->stockCodePool)->get();
//        dd($stocks->toArray());
        $preDays = $this->strategy->shouldPreDays;
        foreach ($stocks as $stock) {
            $stockDays  =  StockDaily::where(["stock_id" => $stock->id])
                ->where('trade_date', '>=', $this->startDate->copy()->subDays($preDays * 2)->format("Ymd"))
                ->where('trade_date', '<=', $this->endDate->format("Ymd"))->get();
            $startTradeDate = StockDaily::where(["stock_id" => $stock->id])
                ->where('trade_date', '>=', $this->startDate->format("Ymd"))->first()->trade_date;

            $this->stockDailyData[$stock->ts_code] = $stockDays;


            //计算复权

            $info = $stockDays[count($stockDays) - 2];
            $fq_last = $info->fq_factor;
            $close_prices = $stockDays->map(function ($v) use($fq_last){
                $d = $v->only(["close", "trade_date", "fq_factor"]);
                $d["close"] = round($d["fq_factor"] / $fq_last * $d["close"], 6);
                return $d;
            })->pluck('close', 'trade_date')->toArray();
//            dd($close_prices);

            //计算技术指标
            $closesAndDate = $stockDays->pluck('close', 'trade_date');
//            $closes = $closesAndDate->values()->toArray();
            $dates = $closesAndDate->keys()->toArray();
            $realDateIndex = array_search($startTradeDate, $dates);

            $this->stockCloses[$stock->ts_code] = $close_prices;
            $closes = array_values($close_prices);
            foreach ($this->strategy->needTecs as $key => $needTec) {
                $d = $needTec->deal($closes);
//                dd($d, $realDateIndex, $close_prices);
                $a = array_filter($d, function ($key) use ($realDateIndex) {
                        return $key >= $realDateIndex;
                }, ARRAY_FILTER_USE_KEY);
                $aa = [];
                foreach ($a as $k => $value) {
                   $aa[$dates[$k]] = $value;
                }
                $this->stockTecData[$stock->ts_code][$key] = $aa;
            }

            dd($this->stockTecData, $startTradeDate);
//            dd($this->stockCloses);

//            dd($stockDays->toArray());
        }
    }

    function stop() {

    }

}

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
    public $stockDailyDate = [];

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
        foreach ($stocks as $stock) {
            $stockDays  =  StockDaily::where(["stock_id" => $stock->id])
                ->where('trade_date', '>=', $this->startDate->format("Ymd"))
                ->where('trade_date', '<=', $this->endDate->format("Ymd"))->get();
            $this->stockDailyDate[$stock->ts_code] = $stockDays;
//            dd($stockDays->toArray());
        }
    }

    function stop() {

    }

}

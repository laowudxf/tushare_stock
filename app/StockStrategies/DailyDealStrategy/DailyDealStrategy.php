<?php


namespace App\StockStrategies\DailyDealStrategy;
use App\Models\Stock;
use App\StockStrategies\DefaultStockStrategy;


class DailyDealStrategy extends DefaultStockStrategy
{

    public function ensureStockPool()
    {
        $stockPools = Stock::all();
        return $stockPools->pluck('ts_code')->toArray();
    }

}

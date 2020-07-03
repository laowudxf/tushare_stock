<?php

namespace App\Http\Controllers;

use App\Models\StockDaily;
use App\StockStrategies\DefaultStockStrategy;
use App\StockStrategies\StrategyRunContainer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class StockController extends Controller
{
    //
    public function test() {
//        $startDate = Date::create(2020, 1, 1);
//        $endDate = now();
//        $result = $this->getStock('000001.SZ', $startDate, $endDate);
//
//        $allClose = $result->pluck('close')->toArray();
////        dd($allClose);
//        $ma5_t =trader_ma($allClose, 5);
//        $macd5 = trader_macd($allClose, 5);
//
//        dd($ma5_t, $macd5);

         return $this->lookBackTest(now()->subDays(700), now()->subDays(5));
    }

    public function  getStock($tz_code, $startDate, $endDate) {
        $startDateInt = $this->dateToInt($startDate);
        $endDateInt = $this->dateToInt($endDate);
        return StockDaily::where('stock_id', 2)->where('trade_date', '>=', $startDateInt)->where('trade_date', '<=', $endDateInt)->get();
    }

    private function dateToInt(Carbon $date) {
       return intval(''.$date->year.sprintf("%02d", $date->month).sprintf("%02d", $date->day));
    }

    //-----------回测
    function lookBackTest($startDate, $endDate) {
        $strategy = new DefaultStockStrategy();
        $runner = new StrategyRunContainer($startDate, $endDate, $strategy);
        $strategy->setRunContainer($runner);
        return $runner->run();
    }
}


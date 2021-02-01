<?php

namespace App\Http\Controllers;

use App\Models\StockDaily;
use App\StockStrategies\DefaultStockStrategy;
use App\StockStrategies\StrategyRunContainer;
use App\StockStrategies\WeekStrategyRunContainer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class StockController extends Controller
{
    //
    public function test() {
         return $this->lookBackTest(Carbon::create(2019, 1), Carbon::create(2020, 12));
    }


//    private function dateToInt(Carbon $date) {
//       return intval(''.$date->year.sprintf("%02d", $date->month).sprintf("%02d", $date->day));
//    }

    //-----------回测
    function lookBackTest($startDate, $endDate) {
        $strategy = new DefaultStockStrategy();
//        $runner = new StrategyRunContainer($startDate, $endDate, $strategy);
        $runner = new WeekStrategyRunContainer($startDate, $endDate, $strategy);
        $strategy->setRunContainer($runner);
        return $runner->run();
    }
}


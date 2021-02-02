<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockDaily;
use App\Repository\Facade\RDS\RDS;
use App\StockStrategies\DefaultStockStrategy;
use App\StockStrategies\StrategyRunContainer;
use App\StockStrategies\WeekStrategyRunContainer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class StockController extends Controller
{
    //
    public function test(Request $request) {
        $startDate = $request->input("start_date");
        $endDate = $request->input("end_date");
        return $this->lookBackTest($request->input("ts_code"),
            Carbon::createFromFormat("Y-m", $startDate),
            Carbon::createFromFormat("Y-m", $endDate));
    }

    public function buyStockAnalyse(Request $request) {

        $strategy = new DefaultStockStrategy();

        $now = now()->subDays(7);
        $dateRange = $this->calcMondayAndFriday($now);
        $runner = new WeekStrategyRunContainer(Carbon::createFromFormat("Ymd", $dateRange[0]),
            Carbon::createFromFormat("Ymd", $dateRange[1]), $strategy);
        $strategy->setRunContainer($runner);
        $runner->run();
        dd($strategy->buyPlan);
    }

    public function calcMondayAndFriday($d) {
        $monday = $d->copy()->subDays($d->dayOfWeekIso - 1);
        $friday = $d->copy()->addDays(5 - $d->dayOfWeekIso);
        return [$monday->format("Ymd"), $friday->format("Ymd")];
    }

    public function stocks() {
        return RDS::success(Stock::all());
    }


//    private function dateToInt(Carbon $date) {
//       return intval(''.$date->year.sprintf("%02d", $date->month).sprintf("%02d", $date->day));
//    }

    //-----------回测
    function lookBackTest($ts_code, $startDate, $endDate) {
        $strategy = new DefaultStockStrategy();
        if ($ts_code) {
            $strategy->defaultStocks = [$ts_code];
        }
//        $runner = new StrategyRunContainer($startDate, $endDate, $strategy);
        $runner = new WeekStrategyRunContainer($startDate, $endDate, $strategy);
        $strategy->setRunContainer($runner);
        return $runner->run();
    }
}


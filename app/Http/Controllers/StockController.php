<?php

namespace App\Http\Controllers;

use App\Models\StockDaily;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class StockController extends Controller
{
    //
    public function test() {
        $startDate = Date::create(2020, 1, 1);
        $endDate = now();
        $result = $this->getStock('000001.SZ', $startDate, $endDate);
        $ma5 = $this->ti_MA(5, 20200122, $result);
        $ma10 = $this->ti_MA(10, 20200122, $result);
        dd($ma5, $ma10);
        dd($result->toArray());
    }

    public function  getStock($tz_code, $startDate, $endDate) {
        $startDateInt = $this->dateToInt($startDate);
        $endDateInt = $this->dateToInt($endDate);
        return StockDaily::where('stock_id', 2)->where('trade_date', '>=', $startDateInt)->where('trade_date', '<=', $endDateInt)->get();
    }

    private function dateToInt(Carbon $date) {
       return intval(''.$date->year.sprintf("%02d", $date->month).sprintf("%02d", $date->day));
    }

    private function ti_MA($day, $dateInt, $stockDailies) {
        $allDates = $stockDailies->pluck('trade_date');
        $dateIndex = array_search($dateInt, $allDates->toArray());

        // 如果之前没有$day - 1天则无法计算
        if ($dateIndex < $day - 1) {
           return null;
        }

        $result = array_slice($stockDailies->toArray(), $dateIndex - $day + 1, $day);
        return Collect($result)->avg('close');
    }

}


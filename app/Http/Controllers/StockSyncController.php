<?php

namespace App\Http\Controllers;

use App\Client\TushareClient;
use App\Models\Area;
use App\Models\Industry;
use App\Models\Market;
use App\Models\Stock;

use App\Models\StockDaily;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StockSyncController extends Controller
{
    //

    public function syncStockList() {
        $client = new TushareClient();
       $list = $client->stockList();
       if ($list["code"] != 0) {
           Log::warning("sync stock list failed msg:".$list["msg"]);
           return;
       }
        $fields =  $list["data"]["fields"];
        $items =  $list["data"]["items"];

        foreach ($items as $item) {

            $symbol = $item[1];
            if (Stock::where('symbol', $symbol)->exists()) {
                continue;
            }

            $insertData = [];
            foreach ($item as $key => $v) {
               $k = $fields[$key];
                if ($k == "area") {
                    $result = Area::where('name', $v)->first();
                    if ($result == null) {
                        $result = Area::create(["name" => $v]);
                    }
                    $insertData["area_id"] = $result->id;
                    continue;
                }
                if ($k == "industry") {
                    $result = Industry::where('name', $v)->first();
                    if ($result == null) {
                        $result = Industry::create(["name" => $v]);
                    }
                    $insertData["industry_id"] = $result->id;
                    continue;
                }
               if ($k == "market") {
                   $result = Market::where('name', $v)->first();
                   if ($result == null) {
                       $result = Market::create(["name" => $v]);
                   }
                   $insertData["market_id"] = $result->id;
                   continue;
               }

               $insertData[$k] = $v;
            }

            Stock::create($insertData);
        }
    }

    function syncStockDailyAll() {
           $allStocks = Stock::all();
           $client = new TushareClient();
           foreach ($allStocks as $stock) {
               $result = $client->stockDaily($stock->ts_code);
               $this->dealOneStockDaily($result, $stock);
           }
    }

    function syncStockDailyWeek() {
        $allStocks = Stock::all();
        $client = new TushareClient();
        foreach ($allStocks as $stock) {
            $result = $client->stockDaily($stock->ts_code, null, now()->subDays(7)->format("Ymd"), now()->format("Ymd"));
            $this->dealOneStockDaily($result, $stock);
        }
    }

    private function dealOneStockDaily($data, $stock) {
        $stockId = $stock->id;

        if ($data["code"] != 0) {
            Log::warning("sync stock daily failed msg:".$data["msg"]);
            return;
        }


        $fields =  $data["data"]["fields"];
        $items =  $data["data"]["items"];


        $insertData = [];

        Log::info("update stock daily symbol:{$stock->symbol} name:{$stock->name}");
        $allInsertDate = [];
        foreach ($items as $item) {
            $trade_date = $item[1];
            if (StockDaily::where('trade_date', $trade_date)->where('stock_id', $stockId)->exists()) {
                continue;
            }
            foreach ($item as $key => $v) {
                $k = $fields[$key];
                if ($k == "ts_code") {
                    $insertData["stock_id"] = $stockId;
                    continue;
                }
                $insertData[$k] = $v;
            }
            $allInsertDate[] = $insertData;
        }
        StockDaily::insert($allInsertDate);

    }

    function syncStockDaily() {

    }
}

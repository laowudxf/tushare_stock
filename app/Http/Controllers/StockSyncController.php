<?php

namespace App\Http\Controllers;

use App\Client\TushareClient;
use App\Models\Area;
use App\Models\Industry;
use App\Models\Market;
use App\Models\Stock;

use App\Models\StockDaily;
use App\Models\StockDailyExtra;
use App\Models\StockFq;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function foo\func;

class StockSyncController extends Controller
{
    //

    public $console;

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

    function syncStockDailyExtra($week = false) {
        $allStocks = Stock::allStockWithoutCS();
        $client = new TushareClient();
        $c = count($allStocks);
        foreach ($allStocks as $key => $stock) {
            if ($this->console) {
                $this->console->info("deal {$key} count: {$c}");
            } else {
                Log::info("deal {$key} count: {$c}");
            }
            $this->counterDelayCounter("dailyExtra");
            $result = null;
            if ($week) {
                $result = $client->backDaily(null, now()->format("Ymd"));
            } else {
                $result = $client->backDaily($stock->ts_code);
            }
            $this->dealOneStockDailyExtra($result, $stock);
        }
    }

    function syncStockDailyAll() {
           $allStocks = Stock::allStockWithoutCS();
           $client = new TushareClient();
           $count = count($allStocks);
           foreach ($allStocks as $key => $stock) {
               $log = "deal {$key} total {$count}";
               if ($this->console) {
                   $this->console->info($log);
               } else {
                   Log::info($log);
               }
               $this->counterDelayCounter("stock");
               $result = $client->stockDaily($stock->ts_code);
               $this->dealOneStockDaily($result, $stock);
           }
    }

    private $delayCounter = [];

    function syncStockDailyWeek() {
        $allStocks = Stock::allStockWithoutCS();
        $client = new TushareClient();
        foreach ($allStocks as $stock) {
            $this->counterDelayCounter("stock");
            $result = $client->stockDaily($stock->ts_code, null, now()->subDays(30)->format("Ymd"), now()->format("Ymd"));
            $this->dealOneStockDaily($result, $stock);
        }
    }

    function syncStockDailyDay($trade_date) {
        $client = new TushareClient();
        $result = $client->stockDaily(null, $trade_date);
        $this->dealAllStockDaily($result);
    }

    public function counterDelayCounter($key, $customCount = 460) {

        $time = now()->timestamp;
        if (isset($this->delayCounter[$key]) == false) {
            $data = [$time, 1];
            $this->delayCounter[$key] = $data;
            return;
        }

        $data = $this->delayCounter[$key];

        $count = $data[1];
        $recordTime = $data[0];

        if (($time - $recordTime) < 60) {
            $count += 1;
        } else {
            $count = 0;
            $recordTime = $time;
        }

        $this->delayCounter[$key] = [$recordTime, $count];

        if ($count > ($customCount ? $customCount : 460)) {
            sleep(60 - ($time - $recordTime));
            return;
        }

    }

    private function dealOneStockDailyExtra($data, $stock) {
        $stockId = $stock->id;

        if ($data["code"] != 0) {
            Log::warning("sync stock daily failed msg:".$data["msg"]);
            return -1;
        }


        $fields =  $data["data"]["fields"];
        $items =  $data["data"]["items"];


        $insertData = [];

        Log::info("update stock daily extra symbol:{$stock->symbol} name:{$stock->name}");
        $allInsertDate = [];
        foreach ($items as $item) {
            $trade_date = $item[1];
            if (StockDailyExtra::where('trade_date', $trade_date)->where('stock_id', $stockId)->exists()) {
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
//            $tmp = collect($insertData);
//            $allInsertDate[] = $tmp->only(["stock_id", "trade_date", "pe", "total_mv", "float_mv"])->toArray();
            $allInsertDate[] = $insertData;
        }
        $index = 0;
        $trunk = [];
        foreach($allInsertDate as $key => $v) {
            $index += 1;
            $trunk[] = $v;
            if ($index == 200) {
                $index = 0;
                StockDailyExtra::insert($trunk);
                $trunk = [];
            }
        }
        StockDailyExtra::insert($trunk);
        return 0;
    }

    private function dealAllStockDaily($data) {
        if ($data["code"] != 0) {
            Log::warning("sync stock daily failed msg:".$data["msg"]);
            return -1;
        }

        $fields =  $data["data"]["fields"];
        $items =  $data["data"]["items"];


        $insertData = [];

        foreach ($items as $item) {
            $ts_code = '';
            foreach ($item as $key => $v) {
                $k = $fields[$key];
                if ($k == "ts_code") {
                    $ts_code = $v;
                    continue;
                }
                $insertData[$k] = $v;
            }
            $stock = Stock::where('ts_code', $ts_code)->first();
            if ($stock == null) {
                continue;
            }
            $info = $stock->stockDailies()->where('trade_date', $insertData["trade_date"])->first();
            Log::info("update stock daily symbol:{$stock->symbol} name:{$stock->name}");
            if ($info) {
                $info->update($insertData);
            } else {
                $stock->stockDailies()->create($insertData);
            }
        }
        return 0;
    }

    private function dealOneStockDaily($data, $stock) {
        $stockId = $stock->id;

        if ($data["code"] != 0) {
            Log::warning("sync stock daily failed msg:".$data["msg"]);
            return -1;
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
        return 0;
    }

    function syncStockFQ() {
        $allStocks = Stock::allStockWithoutCS();
        $client = new TushareClient();
        foreach ($allStocks as $key => $stock) {
            $this->counterDelayCounter("fq");
            Log::info("index: {$key} update stock daily symbol:{$stock->symbol} name:{$stock->name}");
            $this->dealOneStockFQ($client, $stock);
        }
    }

    function syncStockFQWeek() {
        $allStocks = Stock::allStockWithoutCS();
        $client = new TushareClient();
        foreach ($allStocks as $key => $stock) {
            $this->counterDelayCounter("fq");
            Log::info("index: {$key} update stock daily symbol:{$stock->symbol} name:{$stock->name}");
            $this->dealOneStockFQ($client, $stock, true);
        }
    }

    function syncStockFQDay($trade_date) {
        $client = new TushareClient();
        $data = $client->stockFQ('', $trade_date);

        $fields =  $data["data"]["fields"];
        $items =  $data["data"]["items"];


        $insertData = [];

        foreach ($items as $item) {
            $ts_code = '';
            foreach ($item as $key => $v) {
                $k = $fields[$key];
                if ($k == "ts_code") {
                    $ts_code = $v;
                    continue;
                }
                if ($k == "adj_factor") {
                    $insertData["fq_factor"] = $v;
                    continue;
                }
                $insertData[$k] = $v;
            }
            $stock = Stock::where('ts_code', $ts_code)->first();
            if ($stock == null) {
                continue;
            }
            $info = $stock->stockDailies()->where('trade_date', $insertData["trade_date"])->first();
            Log::info("update stock daily symbol:{$stock->symbol} name:{$stock->name}");
            if ($info) {
                $info->update($insertData);
            }
        }
        return 0;

//        if ($result["code"] != 0) {
//            Log::error("update Stock FQ fail name:{$stock->name} msg:{$result["msg"]}");
//            return;
//        }
//
//        $data = $result["data"];
//        $items = $data["items"];
//
//
//        while ($data["has_more"] == true) {
//            $count = count($items);
//            $endDate = $items[$count - 1];
//
//            $result = $client->stockFQ($stock->ts_code, null, null, $endDate[1]);
//
//            if ($result["code"] != 0) {
//                Log::error("update Stock FQ fail name:{$stock->name} msg:{$result["msg"]}");
//                return;
//            }
//
//            $data = $result["data"];
//            $items = array_merge($items, $result["data"]["items"]);
//        }
//
//        $insertData = [];
//        $a = [];
//        $r = $stock->stockDailies()->get();
//        foreach ($r as $v) {
//            $a[strval($v->trade_date)] = $v->id;
//        }
//        foreach ($items as $key => $item) {
//
//            $trade_date = $item[1];
//            if (isset($a[strval($trade_date)]) == false) {
//                continue;
//            }
//
//            $record_id = $a[strval($trade_date)];
//
//            $insertData[] = [
//                "id" => $record_id,
//                "fq_factor" => $item[2]
//            ];
//        }
//
//        $chunk_datas = array_chunk($insertData, 200, true);
//        foreach ($chunk_datas as $chunk_data) {
//            $this->updateBatch("stock_dailies",$chunk_data);
//        }
    }

    function dealOneStockFQ($client,Stock $stock, $isWeek = false) {
        $result = $client->stockFQ($stock->ts_code, null, $isWeek ? now()->subDays(30): null, null);

        if ($result["code"] != 0) {
            Log::error("update Stock FQ fail name:{$stock->name} msg:{$result["msg"]}");
            return;
        }

        $data = $result["data"];
        $items = $data["items"];


        while ($data["has_more"] == true) {
            $count = count($items);
            $endDate = $items[$count - 1];

            $result = $client->stockFQ($stock->ts_code, null, null, $endDate[1]);

            if ($result["code"] != 0) {
                Log::error("update Stock FQ fail name:{$stock->name} msg:{$result["msg"]}");
                return;
            }

            $data = $result["data"];
            $items = array_merge($items, $result["data"]["items"]);
        }

        $insertData = [];
        $a = [];
        $r = $stock->stockDailies()->get();
        foreach ($r as $v) {
            $a[strval($v->trade_date)] = $v->id;
        }
        foreach ($items as $key => $item) {

            $trade_date = $item[1];
            if (isset($a[strval($trade_date)]) == false) {
               continue;
            }

            $record_id = $a[strval($trade_date)];

            $insertData[] = [
                "id" => $record_id,
                "fq_factor" => $item[2]
            ];
        }

        $chunk_datas = array_chunk($insertData, 200, true);
        foreach ($chunk_datas as $chunk_data) {
            $this->updateBatch("stock_dailies",$chunk_data);
        }
    }

    public function updateBatch($tableName,$multipleData = [])
    {
        try {
            if (empty($multipleData)) {
                throw new \Exception("数据不能为空");
            }
            $firstRow  = current($multipleData);

            $updateColumn = array_keys($firstRow);
            // 默认以id为条件更新，如果没有ID则以第一个字段为条件
            $referenceColumn = isset($firstRow['id']) ? 'id' : current($updateColumn);
            unset($updateColumn[0]);
            // 拼接sql语句
            $updateSql = "UPDATE " . $tableName . " SET ";
            $sets      = [];
            $bindings  = [];
            foreach ($updateColumn as $uColumn) {
                $setSql = "`" . $uColumn . "` = CASE ";
                foreach ($multipleData as $data) {
                    $setSql .= "WHEN `" . $referenceColumn . "` = ? THEN ? ";
                    $bindings[] = $data[$referenceColumn];
                    $bindings[] = $data[$uColumn];
                }
                $setSql .= "ELSE `" . $uColumn . "` END ";
                $sets[] = $setSql;
            }
            $updateSql .= implode(', ', $sets);
            $whereIn   = collect($multipleData)->pluck($referenceColumn)->values()->all();
            $bindings  = array_merge($bindings, $whereIn);
            $whereIn   = rtrim(str_repeat('?,', count($whereIn)), ',');
            $updateSql = rtrim($updateSql, ", ") . " WHERE `" . $referenceColumn . "` IN (" . $whereIn . ")";
            // 传入预处理sql语句和对应绑定数据
            return DB::update($updateSql, $bindings);
        } catch (\Exception $e) {
            Log::error("复权 插入数据为空");
            return false;
        }
    }
}

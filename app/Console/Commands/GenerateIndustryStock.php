<?php

namespace App\Console\Commands;

use App\Models\Industry;
use App\Models\Stock;
use App\Models\StockDaily;
use App\Models\TradeDate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateIndustryStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:genIndStock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成行业股票';

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
        $industries = Industry::all();
//        $industries = Industry::whereId(17)->get();
        $startDate = '20100101';
//        $startDate = '20210106';

        $allDates = TradeDate::where('trade_date', '>=', $startDate)
            ->orderBy('trade_date')
            ->get();

        $indusCount = count($industries);
        foreach ($industries as $industryKey => $industry) {
            $this->info("deal {$industry->name}, index {$industryKey} total {$indusCount}");
            $newStock = $this->createStock($industryKey, $industry, $startDate);
            $newStock->stockDailies()->delete();

            $allStock = Stock::whereIndustryId($industry->id)->get();

            $preDate = null;
            $insertData = [];
            $lastInsertData = null;
            foreach ($allDates as $date) {
               $validStocks = $allStock->filter(function ($s) use($date) {
                    $listDate = Carbon::createFromFormat('Ymd',$s->list_date);
                    $listDateInt = intval($listDate->addDays(60)->format("Ymd"));
                    return $listDateInt <= intval($date->trade_date);
               });

                $infos = $stockDailies = StockDaily::whereIn('stock_id', $validStocks->pluck('id'))
                    ->where('trade_date', $date->trade_date)->get();
                $lastInsertData = $this->generateStock($infos,$date->trade_date,$industry,$newStock, $lastInsertData);
                    $insertData[] = $lastInsertData;
                $preDate = $date;
            }
            StockDaily::insert($insertData);
            /*
            $stockDailies = StockDaily::whereIn('stock_id', $allStock->pluck('id'))
                ->where('trade_date', '>=', $startDate)->orderBy('trade_date')->get();
            $index = 0;
            $count = count($stockDailies);
            $infos = [];


            $preDate = null;
            foreach ($allDates as $key => $date) {
                $info = $stockDailies[$index];
                while ($info->trade_date == $date->trade_date) { //聚合操作
                    $infos[] = $info;
                    $index += 1;
                    if ($count == $index) {
                        break;
                    }
                    $info = $stockDailies[$index];
                }
                $this->generateStock($infos, $date->trade_date, $industry, $newStock, $preDate);
                $infos = [];
                $preDate = $date;
            }
            */
        }
    }

    public function createStock($index, $industry, $date) {
        $symbol = 900000 + $index + 1;
        $ts_code = "{$symbol}.CS";
        $new = Stock::whereTsCode($ts_code)->first();
        if ($new) {
            return $new;
        }
        $stock = new Stock();
        $stock->name = $industry->name.'聚合';
        $stock->symbol = $symbol;
        $stock->ts_code = $ts_code;
        $stock->ts_code = $ts_code;
        $stock->area_id = 0;
        $stock->industry_id = 0;
        $stock->market_id = 0;
        $stock->market_id = 0;
        $stock->list_date = $date;
        $stock->save();
        return $stock;
    }
    public function generateStock($infos, $date, $industry, $newStock, $preData) {
        $lastStockInfo = null;

        $pre_close = 100; //初始值为1
        if ($preData) {
            $pre_close = $preData["close"];
        }

        $infosCollect = $infos;
        $allAmount = $infosCollect->sum('amount');

        $pct_chg = $infos->sum(function ($v) use($allAmount) {
            return $v["pct_chg"] * ($v["amount"] / $allAmount);
        });

        $open = $pre_close;
        $close = $open * ((100 + $pct_chg) / 100);
        $low = min($close, $open);
        $high = max($close, $open);
        $change = $close - $open;

//        $stockDaily = null;
//        $stockDaily = StockDaily::where(['stock_id' => $newStock->id, "trade_date" => $date])->first();
//        if ($stockDaily == null) {
//            $stockDaily = new StockDaily();
//        }

//        $stockDaily = new StockDaily();
//        $stockDaily->stock_id = $newStock->id;
//        $stockDaily->trade_date = $date;
//        $stockDaily->open = $open;
//        $stockDaily->close = $close;
//        $stockDaily->low = $low;
//        $stockDaily->high = $high;
//        $stockDaily->change = $change;
//        $stockDaily->pct_chg = $pct_chg;
//        $stockDaily->pre_close = $pre_close;
//        $stockDaily->vol = 0;
//        $stockDaily->amount = 0;
//        $stockDaily->fq_factor = 1;
//        $stockDaily->save();

        return [
            "stock_id" => $newStock->id,
            "trade_date" => $date,
            "open" => $open,
            "close" => $close,
            "low" => $low,
            "high" => $high,
            "change" => $change,
            "pct_chg" => $pct_chg,
            "pre_close" => $pre_close,
            "vol" => 0,
            "amount" => 0,
            "fq_factor" => 1,
        ];
    }
}

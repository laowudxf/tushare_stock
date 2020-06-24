<?php


namespace App\StockStrategies;


use App\Client\TushareClient;
use App\Models\Stock;
use App\Models\StockDaily;
use Carbon\Carbon;
use Carbon\Traits\Date;
use Illuminate\Support\Facades\Log;

class StrategyRunContainer
{
    public $strategy;

    public $stockAccount;

    private $startDate;
    private $endDate;

    private $rate = 1.0015;

    public $stockCodePool;
    public $stockDailyData = [];
    public $stockCloses = [];
    public $stockTecData = [];

    public function __construct(Carbon $startDate,Carbon $endDate, DefaultStockStrategy $strategy)
    {
        $this->strategy = $strategy;
        $this->stockAccount = new StockAccount($strategy->initMoney);
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    function run(){
        $this->stockCodePool = $this->strategy->ensureStockPool();
        $this->initData();
//        $datePoint = $this->startDate->copy();

        $client = new TushareClient();
        $tradeDates = $client->tradeDates($this->startDate->format("Ymd"), $this->endDate->format("Ymd"));
        $tradeDates = Collect($tradeDates["data"]["items"])->pluck(1);

        foreach ($tradeDates as $tradeDate) {
            $date = Carbon::createFromFormat("Ymd", $tradeDate);
            $this->strategy->openQuotation($date);
            $this->strategy->closeQuotation($date);
        }
        dd($this->strategy->buyPoint);

//        while ($datePoint < $this->endDate) {
////            var_dump($datePoint->format("Ymd"));
//            $this->strategy->openQuotation($datePoint);
//            $this->strategy->closeQuotation($datePoint);
//            $datePoint = $datePoint->addDays(1);
//        };

    }

    function initData() {
        $stocks = Stock::whereIn('ts_code', $this->stockCodePool)->get();
//        dd($stocks->toArray());
        $preDays = $this->strategy->shouldPreDays;
        foreach ($stocks as $stock) {
            $stockDays  =  StockDaily::where(["stock_id" => $stock->id])
                ->where('trade_date', '>=', $this->startDate->copy()->subDays($preDays * 2)->format("Ymd"))
                ->where('trade_date', '<=', $this->endDate->format("Ymd"))->get();
            $startStockDaily = StockDaily::where(["stock_id" => $stock->id])
                ->where('trade_date', '>=', $this->startDate->format("Ymd"))->first();
            try {

                $startTradeDate = $startStockDaily->trade_date;
            } catch (\Exception $exception) {
//                dd($startStockDaily, $stock);
                continue;
            }

            $this->stockDailyData[$stock->ts_code] = $stockDays;


            //计算复权

            $info = $stockDays[count($stockDays) - 1];
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
                        return $key >= $realDateIndex - 5;
                }, ARRAY_FILTER_USE_KEY);
                $aa = [];
                foreach ($a as $k => $value) {
                   $aa[$dates[$k]] = $value;
                }
                $this->stockTecData[$stock->ts_code][$key] = $aa;
            }

//            dd($this->stockCloses);

//            dd($stockDays->toArray());
        }
//        dd($this->stockDailyData);
//        dd($this->stockTecData, $startTradeDate);
    }

    //-------- trade

    public function buy($ts_code, $trade_date, $hands = null, $price = null) {
        $dailyData = $this->stockDailyData[$ts_code];
        if ($trade_date instanceof Carbon) {
            $trade_date = $trade_date->format("Ymd");
        }
        $dayDate = $dailyData->firstWhere('trade_date', $trade_date);
        if ($dayDate == null) {
            log::warning("当天没有开盘数据 ts_code:{$ts_code}, 无法购买");
            return -1;
        }

        if ($hands) {
            $needMoney = ($dayDate->open * $hands * 100) * $this->rate;
            if ($this->stockAccount->money < $needMoney) {
                log::warning("资金不够 {$this->stockAccount->money}, need {$needMoney}, ts_code:{$ts_code}, 无法购买");
                return -2;
            }
            $this->stockAccount->buy($ts_code, $trade_date, $hands, $needMoney, $dayDate->open);
            return;
        }

        if ($price) {
            $hands = ($price / $dayDate->open / 100) % 1 ;
            if ($hands == 0) {
                log::warning("资金不够 {$this->stockAccount->money}, ts_code:{$ts_code}, 无法购买");
                return -2;
            }
            $needMoney = ($dayDate->open * $hands * 100) * $this->rate;
            if ($this->stockAccount->money < $needMoney) {
                log::warning("资金不够 {$this->stockAccount->money}, need {$needMoney}, ts_code:{$ts_code}, 无法购买");
                return -2;
            }
            $this->stockAccount->buy($ts_code, $trade_date, $hands, $needMoney, $dayDate->open);
            return;
        }

    }

    public function tecIndexSlice($ts_code, $tecIndex, $trade_date, $preCount = 5) {
       $tecIndex = $this->stockTecData[$ts_code][$tecIndex];
      $tradeDateIndex = array_search($trade_date, array_keys($tecIndex));
      $preDateIndex = $tradeDateIndex - ($preCount - 1);
      if ($preDateIndex < 0) {
          $preCount += $preDateIndex;
          $preDateIndex = 0;
      }
      return array_slice($tecIndex, $preDateIndex, $preCount);

    }

}

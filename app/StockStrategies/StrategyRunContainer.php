<?php


namespace App\StockStrategies;


use App\Client\TushareClient;
use App\Models\Stock;
use App\Models\StockDaily;
use App\Models\StockTec;
use App\Models\TradeDate;
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
    public $showProfit = false;
    public $isLookBackTest = true;
//    public $showProfit = false;

    public function __construct(Carbon $startDate,Carbon $endDate, DefaultStockStrategy $strategy)
    {
        $this->strategy = $strategy;
        $this->stockAccount = new StockAccount($strategy->initMoney);
        $this->stockAccount->runContainer = $this;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    function run(){
        $this->stockCodePool = $this->strategy->ensureStockPool();
//        $t_start = msectime();
        $this->initData();
//        $t_end = msectime();
//         dd($t_end - $t_start);
//        $datePoint = $this->startDate->copy();

        $tradeDates = TradeDate::dates($this->startDate->format("Ymd"), $this->endDate->format("Ymd"))->get();
        $tradeDates = $tradeDates->pluck('trade_date');

        foreach ($tradeDates as $tradeDate) {
            $date = Carbon::createFromFormat("Ymd", $tradeDate);
            $this->strategy->openQuotation($date);
            $this->strategy->closeQuotation($date);
        }

        if ($this->showProfit) {
            foreach ($this->strategy->buyPoint as &$item){
                $item = Collect($item)->filter(function($v){
                    return $v["profit"] != null;
                })->sortByDesc(function ($v){
                    return $v["profit"][1];
                })->toArray();
            }
        }

        $this->dealResult();
    }

    function dealResult() {
        $flatten = Collect($this->strategy->buyPoint)->flatten(1);


        if ($this->isLookBackTest) {
            Log::info($this->stockAccount->tradeLogs);
        }
        //打印结果
        if ($this->showProfit) {
            Log::info(["result" => ["涨:".$flatten->where('profit.1', '>', 0)->count(),
                "跌:".$flatten->where('profit.1', '<', 0)->count()],
                "buyPoint" => $this->strategy->buyPoint]);
        } else {
        }

        return ["result" => ["涨:".$flatten->where('profit.1', '>', 0)->count(),
            "跌:".$flatten->where('profit.1', '<', 0)->count()],
            "buyPoint" => $this->strategy->buyPoint];

    }

    function initData() {
        $stocks = Stock::whereIn('ts_code', $this->stockCodePool)->get();
        $preDays = $this->strategy->shouldPreDays;
        $newStartDay = $this->startDate->copy()->subDays($preDays >= 200 ? 200: $preDays * 2);
        foreach ($stocks as $stock) {
            $stockDays = StockDaily::where(["stock_id" => $stock->id])
                ->where('trade_date', '>=', $newStartDay->format("Ymd"))
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
            if (count($stockDays) == 0) {
                continue;
            }


            $info = $stockDays[count($stockDays) - 1];
            $fq_last = $info->fq_factor;
            $close_prices = $stockDays->map(function ($v) use($fq_last){
                $d = $v->only(["close", "trade_date", "fq_factor"]);
                $d["close"] = round($d["fq_factor"] / $fq_last * $d["close"], 6);
                return $d;
            })->pluck('close', 'trade_date')->toArray();

            //计算技术指标
            $closesAndDate = $stockDays->pluck('close', 'trade_date');
            $dates = $closesAndDate->keys()->toArray();
            $realDateIndex = array_search($startTradeDate, $dates);

            $this->stockCloses[$stock->ts_code] = $close_prices;
            $closes = array_values($close_prices);
            foreach ($this->strategy->needTecs as $key => $needTec) {
                $result = $needTec->deal($closes, $realDateIndex, $dates);
                $this->stockTecData[$stock->ts_code][$key] = $result;
            }

        }
    }

    // public functions
    //-------- trade
    public function sell($ts_code, $trade_date, $hands = null){
        $dailyData = $this->stockDailyData[$ts_code];
        if ($trade_date instanceof Carbon) {
            $trade_date = $trade_date->format("Ymd");
        }
        $dayDate = $dailyData->firstWhere('trade_date', $trade_date);
        if ($dayDate == null) {
            log::warning("当天没有开盘数据 ts_code:{$ts_code}, 无法卖出");
            return -1;
        }

        if ($hands) {
//            $needMoney = ($dayDate->open * $hands * 100) * $this->rate;
//            if ($this->stockAccount->money < $needMoney) {
//                log::warning("资金不够 {$this->stockAccount->money}, need {$needMoney}, ts_code:{$ts_code}, 无法购买");
//                return -2;
//            }
            $this->stockAccount->sell($ts_code, $trade_date, $hands, $dayDate->open, $this->rate);
            return 0;
        }

//        if ($price) {
//            $hands = intval($price / $dayDate->open / 100);
//            if ($hands == 0) {
//                log::warning("资金不够 {$this->stockAccount->money}, ts_code:{$ts_code}, 无法购买");
//                return -2;
//            }
//            $needMoney = ($dayDate->open * $hands * 100) * $this->rate;
//            if ($this->stockAccount->money < $needMoney) {
//                log::warning("资金不够 {$this->stockAccount->money}, need {$needMoney}, ts_code:{$ts_code}, 无法购买");
//                return -2;
//            }
//            $this->stockAccount->buy($ts_code, $trade_date, $hands, $needMoney, $dayDate->open);
//            return 0;
//        }
    }


    /***
     * @param $ts_code
     * @param $trade_date
     * @param null $hands
     * @param null $price
     * @return int|void
     */
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
            return 0;
        }

        if ($price) {
            $hands = intval($price / $dayDate->open / 100);
            if ($hands == 0) {
                log::warning("资金不够 {$this->stockAccount->money}, ts_code:{$ts_code}, 无法购买");
                return -2;
            }
            $needMoney = $hands * 100 * $dayDate->open;
//            $needMoney = ($dayDate->open * $hands * 100) * $this->rate;
//            if ($this->stockAccount->money < $needMoney) {
//                log::warning("资金不够 {$this->stockAccount->money}, need {$needMoney}, ts_code:{$ts_code}, 无法购买");
//                return -2;
//            }
            $this->stockAccount->buy($ts_code, $trade_date, $hands, $needMoney, $dayDate->open);
            return 0;
        }

    }

    /***
     * @param $ts_code
     * @param $tecIndex
     * @param $trade_date
     * @param int $preCount
     * @return array|null
     */
    public function tecIndexSlice($ts_code, $tecIndex, $trade_date, $preCount = 5, $isBoll = false) {
        if (isset($this->stockTecData[$ts_code]) == false) {
            return null;
        }
        if (isset($this->stockTecData[$ts_code][$tecIndex]) == false) {
            return null;
        }
       $tecIndex = $this->stockTecData[$ts_code][$tecIndex];
      if ($isBoll == false) {
          $tradeDateIndex = array_search($trade_date, array_keys($tecIndex));
          $preDateIndex = $tradeDateIndex - ($preCount - 1);
          if ($preDateIndex < 0) {
              $preCount += $preDateIndex;
              $preDateIndex = 0;
          }
          return array_slice($tecIndex, $preDateIndex, $preCount);
      } else {
          $result = [];
          foreach ($tecIndex as $key => $item) {
              $tradeDateIndex = array_search($trade_date, array_keys($item));
              $preDateIndex = $tradeDateIndex - ($preCount - 1);
              if ($preDateIndex < 0) {
                  $preCount += $preDateIndex;
                  $preDateIndex = 0;
              }
              $result[$key] = array_slice($item, $preDateIndex, $preCount);
          }
          return $result;
      }

    }

    /***
     * @param $ts_code
     * @param $date
     * @return mixed
     */
    public function stockDailyInfo($ts_code, $date) {
       return $this->stockDailyData[$ts_code]->firstWhere('trade_date', $date);
    }

    public function nextTradeDays($date, $count = 1) {
        $day1 = TradeDate::where('trade_date', $date)->first();
        $day1 = TradeDate::find($day1->id + $count);
        return $day1;
    }
    /***
     * @param $ts_code
     * @param $date
     * @param int $period
     * @return array|null
     */
    public function profitForNextDays($ts_code, $date, $period = 3) {
//        $day1 = TradeDate::where('trade_date', $date)->first();
//        $day1 = TradeDate::find($day1->id + 1);
        $day1 = $this->nextTradeDays($date);
        if ($day1 == null) {
            return null;
        }
        $day2 = TradeDate::find($day1->id + $period);
        if ($day2 == null) {
            return null;
        }
        $stock1 = $this->stockDailyInfo($ts_code, $day1->trade_date);
        $stock2 = $this->stockDailyInfo($ts_code, $day2->trade_date);
        if ($stock2 == null || $stock1 == null) {
            return null;
        }

        $closeOff = $stock2->close - $stock1->open;
        $percent = $closeOff / $stock1->open;
        return [$closeOff, round($percent, 2)];
    }
}

<?php


namespace App\StockStrategies;


use App\Client\TushareClient;
use App\Models\Stock;
use App\Models\StockDaily;
use App\Models\StockTec;
use App\Models\StockWeek;
use App\Models\TradeDate;
use App\Repository\Facade\RDS\RDS;
use Carbon\Carbon;
use Carbon\Traits\Date;
use Illuminate\Support\Facades\Log;

class WeekStrategyRunContainer
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
        $this->initData();
//        $t_start = msectime();
//        $t_end = msectime();
//         dd($t_end - $t_start);

        $tradeDates = TradeDate::dates($this->startDate->format("Ymd"), $this->endDate->format("Ymd"))->get();
        $tradeDates = $tradeDates->pluck('trade_date');

        $tradeAccountInfo = [];
        foreach ($tradeDates as $tradeDate) {
            $date = Carbon::createFromFormat("Ymd", $tradeDate);
            //每周一交易一次 todo 有时候会放假
            if ($date->isFriday()) {
                $d = TradeDate::where('trade_date', '>=', $date->format("Ymd"))->orderBy('trade_date', 'asc')->first();
                $this->strategy->closeQuotation(Carbon::createFromFormat("Ymd", strval($d->trade_date)));
            }
            if ($date->isMonday()) {
                $d = TradeDate::where('trade_date', '>=', $date->format("Ymd"))->orderBy('trade_date', 'asc')->first();
                $this->strategy->openQuotation(Carbon::createFromFormat("Ymd", strval($d->trade_date)));
            }
            $tradeAccountInfo[] = ["date" => $tradeDate, "amount" => $this->stockAccount->allPropertyMoney($tradeDate)];
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

        return $this->dealResult($tradeAccountInfo);
    }

    function dealResult($tradeAccountInfo) {
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

//        return  ["result" => ["涨:".$flatten->where('profit.1', '>', 0)->count(),
//            "跌:".$flatten->where('profit.1', '<', 0)->count()],
//            "buyPoint" => $this->strategy->buyPoint];
        return RDS::success(["trade_log" => $this->stockAccount->tradeLogs,
            "account_log" => $tradeAccountInfo]);

    }

    function initData() {
        $stocks = Stock::whereIn('ts_code', $this->stockCodePool)->get();
        $preDays = $this->strategy->shouldPreDays;
        $newStartDay = $this->startDate->copy()->subWeeks($preDays >= 200 ? 200: $preDays * 2);
        foreach ($stocks as $stock) {
            $stockDays = StockWeek::where(["stock_id" => $stock->id])
                ->where('trade_date', '>=', $newStartDay->format("Ymd"))
                ->where('trade_date', '<=', $this->endDate->format("Ymd"))->get();
            $startStockDaily = StockWeek::where(["stock_id" => $stock->id])
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
        $dayDate = $this->stockDailyInfo($ts_code, $trade_date);
        if ($dayDate == null) {
            log::warning("date: {$trade_date} 当天没有开盘数据 ts_code:{$ts_code}, 无法卖出");
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
        //week 策略 必须获取daily数据

        $dayData = $this->stockDailyInfo($ts_code, $trade_date);


        if ($dayData == null) {
            log::warning("当天没有开盘数据 ts_code:{$ts_code}, 无法购买");
            return -1;
        }

        if ($hands) {
//            $needMoney = ($dayDate->open * $hands * 100) * $this->rate;
            $needMoney = $dayData->open * $hands * 100;
            if ($this->stockAccount->money < $needMoney) {
                log::warning("资金不够 {$this->stockAccount->money}, need {$needMoney}, ts_code:{$ts_code}, 无法购买");
                return -2;
            }
            $this->stockAccount->buy($ts_code, $trade_date, $hands, $needMoney, $dayData->open);
            return 0;
        }

        if ($price) {
            $hands = intval($price / $dayData->open / 100);

            if ($hands == 0) {
                log::warning("资金不够 {$this->stockAccount->money}, ts_code:{$ts_code}, 无法购买");
                return -2;
            }
            $needMoney = $hands * 100 * $dayData->open;
//            $needMoney = ($dayDate->open * $hands * 100) * $this->rate;
//            if ($this->stockAccount->money < $needMoney) {
//                log::warning("资金不够 {$this->stockAccount->money}, need {$needMoney}, ts_code:{$ts_code}, 无法购买");
//                return -2;
//            }
            $this->stockAccount->buy($ts_code, $trade_date, $hands, $needMoney, $dayData->open);
            return 0;
        }

    }

    function searchNearBy($item, $arr) {
        foreach ($arr as $ik => $ii) {
            if ($ii >= intval($item)) {
                return $ik - 1;
            }
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
          $tradeDateIndex = $this->searchNearBy($trade_date, array_keys($tecIndex));
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
              if ($tradeDateIndex == false) { //找最近的
                  $dates = array_keys($item);
                  $tradeDateIndex = $this->searchNearBy($trade_date, $dates);
              }
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
        $stock = Stock::where(['ts_code' => $ts_code])->first();
        $dayData = StockDaily::where(['stock_id' => $stock->id, 'trade_date' => $date])->first();
        $newDayData = StockDaily::where(['stock_id' => $stock->id])->orderBy('trade_date', 'desc')->first();
        if ($newDayData == null || $dayData == null) {
            return null;
        }
        $scale = $dayData->fq_factor / $newDayData->fq_factor;
        $dayData->open *= $scale;
        $dayData->open = round($dayData->open, 2);
        $dayData->close *= $scale;
        $dayData->close = round($dayData->close, 2);
       return $dayData;
    }

//    public function nextTradeDays($date, $count = 1) {
//        $day1 = TradeDate::where('trade_date', $date)->first();
//        if ($day1 == null) {
//            return $this->nextTradeDays(Carbon::createFromFormat("Ymd", strval($date))->addDays()->format("Ymd"), $count - 1);
//        }
//        $day1 = TradeDate::find($day1->id + $count);
//        return $day1;
//    }
    /***
     * @param $ts_code
     * @param $date
     * @param int $period
     * @return array|null
     */

    public function profitForNextDays($ts_code, $date, $period = 3) {
        return  null;
//        $day1 = $this->nextTradeDays($date);
//        if ($day1 == null) {
//            return null;
//        }
//        $day2 = TradeDate::find($day1->id + $period * 7);
//        if ($day2 == null) {
//            return null;
//        }
//        $stock1 = $this->stockDailyInfo($ts_code, $day1->trade_date);
//        $stock2 = $this->stockDailyInfo($ts_code, $day2->trade_date);
////        dd($day1->trade_date, $day2->trade_date, $ts_code);
//        if ($stock2 == null || $stock1 == null) {
//            return null;
//        }
//
//        $closeOff = $stock2->close - $stock1->open;
//        $percent = $closeOff / $stock1->open;
//        return [$closeOff, round($percent, 2)];
    }
}

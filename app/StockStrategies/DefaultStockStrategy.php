<?php


namespace App\StockStrategies;


use App\Models\Stock;

class DefaultStockStrategy
{

    private  $runContainer;

//    public $needTecs = [StockTecIndex::create(StockTecIndex::MACD)];

    //初始 资金
    public $initMoney = 200000;

    public $needTecsParams = [
        [StockTecIndex::MACD, []],
        [StockTecIndex::BOLL, []],
//        [StockTecIndex::RSI, [6]],
    //    [StockTecIndex::RSI, [12]],
    //    [StockTecIndex::MA, [5]],
    //    [StockTecIndex::MA, [10]],
    ];

    public $needTecs = [];

    public $shouldPreDays = 0;
    public function __construct()
    {

        // 计算如果计算技术指标需要提前的行情时间
       foreach ($this->needTecsParams as $param) {
           $tecIndex = StockTecIndex::create($param[0], $param[1]);
           $this->needTecs[] = $tecIndex;
           if ($tecIndex->shouldPreDays() > $this->shouldPreDays) {
               $this->shouldPreDays = $tecIndex->shouldPreDays();
           }
       }
    }

    public function ensureStockPool() {

        $stockPools = Stock::limit(500)->get();
        $stockPools = $stockPools->filter(function ($v){
            return strstr($v->name, "ST") == null;
        });
        return $stockPools->pluck('ts_code')->toArray();

        return [
            "000001.SZ",
            "000002.SZ",
            "002216.SZ",
            "002547.SZ",
        ];
    }


    /**
     * @param mixed $runContainer
     */
    public function setRunContainer(StrategyRunContainer $runContainer): void
    {
        $this->runContainer = $runContainer;
    }


    /***
     * 开盘
     */
    public function openQuotation($date) {

//        dd($date->format('Ymd'));
//        $ts_code, $trade_date, $hands,
//        $this->runContainer->buy("000001.SZ", $date, 10);
//        dd($this->runContainer->stockAccount->tradeLogs,$this->runContainer->stockAccount->shippingSpace);
        $account = $this->runContainer->stockAccount;

        //卖出
       foreach ($account->shippingSpace as $key => $items) {
           foreach ($items as $item) {
//               dd($date->format("Ymd"), $this->runContainer->nextTradeDays($item["date"], 4)->trade_date);
               if ($date->format("Ymd") < $this->runContainer->nextTradeDays($item["date"], 4)->trade_date) {
                   continue; //先持有三天
               }

               $this->runContainer->sell($key, $date, $item["hand"]);

//               list($isProfit, $profitPercent) = $this->runContainer->stockAccount->isStockProfit($key, $date->format("Ymd"));
//               if ($isProfit == null) {
//                   continue;
//               }
//
//               if ($isProfit > 0) {
//                   $this->runContainer->sell($key, $date, $item["hand"]);
//               }
           }
       }

        //买入
        if (empty($this->buyPlan) || $account->money < 20000) {
            return;
        }

        $limitBuyPlan = $this->buyPlan;
        if(count($this->buyPlan) > 10) {
            $limitBuyPlan = array_slice($this->buyPlan, 0, 10);
        }

        $preMoney = $account->money / count($limitBuyPlan);
        foreach ($limitBuyPlan as $item) {
            $this->runContainer->buy($item["stock"], $date, null, $preMoney);
        }

    }

    private $buyPlan = [];

    //tmp
    public $buyPoint = [];
    public function closeQuotation($date) {
        $this->buyPlan = [];

        $stocks = $this->ensureStockPool();
        $dateFormat = $date->format("Ymd");
        foreach ($stocks as $key => $stock) {
            $result = $this->runContainer->tecIndexSlice($stock, 0, $date->format("Ymd"), 5);
            $bollTec = $this->runContainer->tecIndexSlice($stock, 1, $date->format("Ymd"), 1, true);
            if ($result == null || $bollTec == null) {
                continue;
            }

        //    $isBuyPoint = $this->isAscendingChannel($bollTec) && $this->isMACDBuyDot($result);
            $isBuyPoint = $this->isAscendingChannel($bollTec) && $this->isMACDBottomRebound($result);
//            dd($this->bollMidSlope($bollTec, $dateFormat), $dateFormat, $stock);
//            $isBuyPoint = ($this->bollMidSlope($bollTec) > 1) && $this->isMACDBottomRebound($result);
            if ($isBuyPoint) {
                $result = $this->runContainer->profitForNextDays($stock, $date->format("Ymd"), 3);
                $this->buyPoint[$stock][] = [
                    "date" =>  $date->format('Ymd'),
                    "profit" => $result,
                    "slope" => $this->bollUpSlope($bollTec)
                ];

                $this->buyPlan[] = [
                    "stock" => $stock
                ];
            }
        }
    }

    public function isMACDBottomRebound(array $result) {
        if (empty($result)) {
            return false;
        }

        $max = null;

        foreach ($result as $key => $item) {
            if ($max == null) {
                $max = [$key, $item];
                continue;
            }

            //判断都小于0
            if ($item >= 0) {
                return false;
            }

            if ($item < $max[1]) {
                $max = [$key, $item];
            }
        }

        //若最高的不是倒数第二个则不符合
        if ($max[0] != count($result) - 2) {
           return false;
        }

        //最高的之前是递减的
        $last = null;
        foreach ($result as $key => $r) {
            if ($key >= $max[0]) {
                break;
            }
            if ($last == null) {
                $last = $r;
                continue;
            }
            if ($r > $last) {
                return false;
            }
            $last = $r;
        }

        return true;

    }

    //金叉
    public function isMACDBuyDot(array $result) {

        if (empty($result)) {
            return false;
        }
        //都小于 0
        foreach ($result as $r) {
            if ($r > 0) {
                return false;
            }
        }
        //递减
        $last = null;
        foreach ($result as $r) {
            if ($last == null) {
                $last = $r;
                continue;
            }
            if ($r < $last) {
                return false;
            }
            $last = $r;
        }
//        if ($result[0] > -0.1) {
//            return false;
//        }
        if ($result[0] > $result[count($result) - 1]) {
            return false;
        }
        //金叉
        if ($result[count($result) - 1] < -0.03 &&  $result[count($result) - 1] < 0.1) {
            return false;
        }

        return true;
    }

    public function isAscendingChannel(array $result) {
//       return $this->bollMidSlope($result) > 0 && $this->bollUpSlope($result) > 1;
        return  $this->bollUpSlope($result) > 1;
    }

    public function bollMidSlope(array $result) {
        return $this->bollSlope($result, 1);
    }

    public function bollSlope(array $result, $type) {
         $r = $result[$type + 3];
         return $r[0] ?? null;
    }

    public function bollUpSlope(array $result) {
        return $this->bollSlope($result, 0);
    }
}

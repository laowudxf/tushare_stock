<?php


namespace App\StockStrategies;


use App\Models\Stock;
use App\Models\StockDaily;
use App\Models\StockWeek;

class DefaultStockStrategy
{

    public  $runContainer;


    //初始 资金
    public $initMoney = 200000;
    public $defaultStocks = [];

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

        $stockPools = Stock::limit(100)->get();
        $stockPools = $stockPools->filter(function ($v){
            return strstr($v->name, "ST") == null;
        });
        return $stockPools->pluck('ts_code')->toArray();

        if (count($this->defaultStocks)) {
            return $this->defaultStocks;
        } else {

            return [
                "000333.SZ",
//            "300014.SZ",
//            "601088.SH",
//            "000725.SZ",
//            "000001.SZ",
            ];
        }
    }


    /**
     * @param mixed $runContainer
     */
    public function setRunContainer($runContainer)
    {
        $this->runContainer = $runContainer;
    }


    public function sellStrategy($date) {

        $account = $this->runContainer->stockAccount;
        //卖出
        foreach ($account->shippingSpace as $key => $items) {
            foreach ($items as $item) {
                $macdTec = $this->runContainer->tecIndexSlice($key, 0, $date->format("Ymd"), 5, false);
                $bollTec = $this->runContainer->tecIndexSlice($key, 1, $date->format("Ymd"), 1, true);

                if ($macdTec) {
//                    $bollDown = $this->bollMidSlope($bollTec) < -0.2;
//                    $bollDown1 = $this->bollMidSlope($bollTec) < -0.7;
                    $isSellPoint = $this->isMACDTopRebound($macdTec);
//                    if($isSellPoint || $weekInfo->pct_chg < -0.1) {
                    if($isSellPoint ) {
                        $shouldNotBeSell = false;
                        foreach ($this->buyPlan as $plan) {
                            foreach ($plan as $ts_code) {
                                if ($ts_code == $key) {
                                    $shouldNotBeSell = true;
                                    continue;
                                }
                            }
                        }
                        if ($shouldNotBeSell) {
                            continue;
                        }
                        $this->runContainer->sell($key, $date, $item["hand"]);
                    }
                }


            }
        }
    }

    public function sellStrategyMACD($date) {

        $account = $this->runContainer->stockAccount;
        //卖出
        foreach ($account->shippingSpace as $key => $items) {
            foreach ($items as $item) {
                $macdTec = $this->runContainer->tecIndexSlice($key, 0, $date->format("Ymd"), 4, false);
                $bollTec = $this->runContainer->tecIndexSlice($key, 1, $date->format("Ymd"), 1, true);

                //止盈止损
                $stockInfo = StockDaily::infoWithTsCodeQuery($key)->where('trade_date', $date->format("Ymd"))->orderBy('trade_date')->first();
                if ($stockInfo) {
                    $stockInfo->updatePrice();
                    $profit = ($stockInfo->close - $item["unit_cost"]) / $item["unit_cost"];

//                    if ($profit < -0.1) {
//                        $this->runContainer->sell($key, $date, $item["hand"]);
//                        return;
//                    }
//
//                    if ($profit > 0.15) {
//                        $this->runContainer->sell($key, $date, $item["hand"]);
//                        return;
//                    }
                }

                if ($macdTec) {
                    $bollDown = $this->bollMidSlope($bollTec) < -0.2;
                    $bollDown1 = $this->bollMidSlope($bollTec) < -0.7;
                    $isSellPoint = ($this->isMACDTopRebound($macdTec) && $bollDown) || $bollDown1;
                    if($isSellPoint ) {
                        $shouldNotBeSell = false;
                        foreach ($this->buyPlan as $plan) {
                            foreach ($plan as $ts_code) {
                                if ($ts_code == $key) {
                                    $shouldNotBeSell = true;
                                    continue;
                                }
                            }
                        }
                        if ($shouldNotBeSell) {
                            continue;
                        }
                        $this->runContainer->sell($key, $date, $item["hand"]);
                    }
                }


            }
        }
    }
    /***
     * 开盘
     */
    public function openQuotation($date) {

        $account = $this->runContainer->stockAccount;
        $this->sellStrategyMACD($date);
//        $this->sellStrategy($date);


        //买入
        if (empty($this->buyPlan) || $account->money < 20000) {
            return;
        }

        $limitBuyPlan = $this->buyPlan;
        if(count($this->buyPlan) > 5) {
            $limitBuyPlan = array_slice($this->buyPlan, 0, 5);
        }

//        $preMoney = $account->money > 200000 ? 200000: $account->money  / count($limitBuyPlan);
        $preMoney = $account->money  / count($limitBuyPlan);

        foreach ($limitBuyPlan as $item) {
            $this->runContainer->buy($item["stock"], $date, null, $preMoney);
        }

    }

    public $buyPlan = [];

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


            //算法1
            $macdUpBuyPoint = $this->isMACDBottomRebound($result, false);
            $isBuyPoint = (($this->isMACDBottomRebound($result) || $macdUpBuyPoint) && $this->isAscendingChannel($bollTec));

//            $isBuyPoint = $this->isMACDBottomRebound($result) && $this->bollMidSlope($bollTec) > 0;;

            if ($isBuyPoint) {
                $this->buyPoint[$stock][] = [
                    "date" =>  $date->format('Ymd'),
                    "slope" => $this->bollUpSlope($bollTec)
                ];

                $this->buyPlan[] = [
                    "date" => $date->format("Ymd"),
                    "stock" => $stock
                ];
            }
        }
    }


    /**
     * 高点下去
     * @param array $result
     * @return bool
     */
    public function isMACDTopRebound(array $result) {
        if (empty($result)) {
            return false;
        }

        $max = null;

        $lessZero = true;
        $bigThanZero = true;
        foreach ($result as $key => $item) {
            if ($max == null) {
                $max = [$key, $item];
                continue;
            }

            //判断都小于0
            if ($item >= 0) {
                $lessZero &= false;
            }

            if ($item < 0) {
                $bigThanZero &= false;
            }


            if ($item > $max[1]) {
                $max = [$key, $item];
            }
        }

        //若最高的不是倒数第二个则不符合
        if ($max[0] != count($result) - 2) {
            return false;
        }

//        //最高的之前是递增
//        $last = null;
//        foreach ($result as $key => $r) {
//            if ($key >= $max[0]) {
//                break;
//            }
//            if ($last == null) {
//                $last = $r;
//                continue;
//            }
//            if ($r < $last) {
//                return false;
//            }
//            $last = $r;
//        }

        return true;

    }
    /**
     * 低点起来 或者 一直往上走
     * @param array $result
     * @return bool
     */
    public function isMACDBottomRebound(array $result, $isNeedBottom = true) {
        if (empty($result)) {
            return false;
        }

        $max = null;

        $lessZero = true;
        $bigThanZero = true;
        foreach ($result as $key => $item) {
            if ($max == null) {
                $max = [$key, $item];
                continue;
            }

            //判断都小于0
            if ($item >= 0) {
                $lessZero &= false;
            }

            if ($item < 0) {
               $bigThanZero &= false;
            }


            if ($isNeedBottom) {
                if ($item < $max[1]) {
                    $max = [$key, $item];
                }
            } else {
                if ($item > $max[1]) {
                    $max = [$key, $item];
                }
            }
        }

//        if ($bigThanZero) {
//            return false;
//        }

        if ($isNeedBottom) {
            //若最低的不是倒数第二个则不符合
            if ($max[0] != count($result) - 2) {
                return false;
            }
        } else {

            if ($max[0] != count($result) - 1) {
                return false;
            }

            //最低的之前是递减
//            $last = null;
//            foreach ($result as $key => $r) {
//                if ($key >= $max[0]) {
//                    break;
//                }
//                if ($last == null) {
//                    $last = $r;
//                    continue;
//                }
//                if ($r < $last) {
//                    return false;
//                }
//                $last = $r;
//            }
        }
        return true;
    }

    //macd金叉
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
//       return $this->bollMidSlope($result) > 0.5 && $this->bollUpSlope($result) > 1;
        return $this->bollMidSlope($result) > 0.3;
//        return  $this->bollUpSlope($result) > 1;
    }

    /**
     * boll 中位线斜率
     * @param array $result
     * @return mixed|null
     */
    public function bollMidSlope(array $result) {
        return $this->bollSlope($result, 1);
    }

    public function bollSlope(array $result, $type) {
        $r = $result[$type + 3];
         return $r[0] ?? null;
    }

    /**
     * boll 上斜率
     * @param array $result
     * @param $type
     * @return mixed|null
     */
    public function bollUpSlope(array $result) {
        return $this->bollSlope($result, 0);
    }
}

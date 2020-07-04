<?php


namespace App\StockStrategies;


use App\Models\Stock;

class DefaultStockStrategy
{

    private  $runContainer;

//    public $needTecs = [StockTecIndex::create(StockTecIndex::MACD)];

    //初始 资金
    public $initMoney = 100000;

    public $needTecsParams = [
        [StockTecIndex::MACD, []],
        [StockTecIndex::BOLL, []],
    //    [StockTecIndex::RSI, [6]],
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
         return $stockPools->pluck('ts_code')->toArray();
//        dd($stockPools->toArray());

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
    }

    private $buyPlan = [];

    //tmp
    public $buyPoint = [];
    public function closeQuotation($date) {

        $stocks = $this->ensureStockPool();
        foreach ($stocks as $stock) {
            $result = $this->runContainer->tecIndexSlice($stock, 0, $date->format("Ymd"), 5);
            $bollTec = $this->runContainer->tecIndexSlice($stock, 1, $date->format("Ymd"), 4, true);
            if ($result == null || $bollTec == null) {
                continue;
            }

            $isBuyPoint = $this->isAscendingChannel($bollTec) && $this->isMACDBuyDot($result);
            if ($isBuyPoint) {
                $result = $this->runContainer->profitForNextDays($stock, $date->format("Ymd"), 7);
                $this->buyPoint[$stock][] = [
                    "date" =>  $date->format('Ymd'),
                    "profit" => $result
                ];
            }
        }
    }

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
       return $this->bollMidSlope($result) > 0.02 && $this->bollUpSlope($result) > 0;
    }

    public function bollMidSlope(array $result) {
        return $this->bollSlope($result, 1);
    }

    public function bollSlope(array $result, $type) {
        $midArr = $result[$type];
        $a = $midArr[0];
        $b = $midArr[count($midArr) - 1];
        $slope = ($b - $a) / $a;
        return $slope;
    }

    public function bollUpSlope(array $result) {
        return $this->bollSlope($result, 0);
    }
}

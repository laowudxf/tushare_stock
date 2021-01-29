<?php


namespace App\StockStrategies\TStrategy;
use App\Models\Stock;
use App\StockStrategies\DefaultStockStrategy;
use App\StockStrategies\StockTecIndex;


class TStrategy extends DefaultStockStrategy
{

    public $needTecsParams = [
        [StockTecIndex::MA, [5]],
        [StockTecIndex::MA, [10]],
        [StockTecIndex::MA, [20]],
    ];

    public function ensureStockPool()
    {
        return ["000725"];
    }


    /***
     * 开盘
     */
    public function openQuotation($date) {

        $account = $this->runContainer->stockAccount;

        //卖出
        foreach ($account->shippingSpace as $key => $items) {
            foreach ($items as $item) {
//               dd($date->format("Ymd"), $this->runContainer->nextTradeDays($item["date"], 4)->trade_date);
                list($isProfit, $profitPercent) = $this->runContainer->stockAccount->isStockProfit($key, $date->format("Ymd"));
                if ($profitPercent != null && $profitPercent < -0.04) { //止损
                    $this->runContainer->sell($key, $date, $item["hand"]);
                    continue;
                }

                if ($profitPercent != null && $profitPercent > 0.1) { //止盈
                    $this->runContainer->sell($key, $date, $item["hand"]);
                    continue;
                }

                $day = $this->runContainer->nextTradeDays($item["date"], 4);
                if ($day == null) {
                    continue;
                }

                if ($date->format("Ymd") < $day->trade_date) {
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

//            $isBuyPoint = $this->isAscendingChannel($bollTec) && $this->isMACDBuyDot($result);
            $isBuyPoint = $this->isMACDBottomRebound_1($result);

            if ($isBuyPoint) {
                $result = $this->runContainer->profitForNextDays($stock, $date->format("Ymd"), 7);
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

}

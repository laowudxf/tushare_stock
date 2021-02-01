<?php


namespace App\StockStrategies;


class StockAccount
{
    public $money;
    public $shippingSpace = [];


    public $tradeLogs = [];
    public $runContainer;

    public function __construct($money)
    {
        $this->money = $money;
    }

    public function allPropertyMoney() {
        $stockMoney = 0;
       foreach ($this->shippingSpace as $space) {
           foreach ($space as $item) {
              $stockMoney += $item["unit_cost"] * $item["hand"] * 100;
           }
       }
       return round($stockMoney + $this->money, 2);
    }

    public function avgPrice($ts_code) {
        if (isset($this->shippingSpace[$ts_code]) == false) {
            return null;
        }

       $records = $this->shippingSpace[$ts_code];

        $hands = 0;
        $money = 0;
       foreach ($records as $record) {
          $hands += $record["hand"];
          $money += $record["unit_cost"] * $record["hand"];
       }
       $avg = $money / $hands;
       return $avg;
    }

    public function isStockProfit($ts_code, $date) {
       $avg = $this->avgPrice($ts_code);
       if ($avg == null) {
           return null;
       }
        $info = $this->runContainer->stockDailyData[$ts_code]->firstWhere('trade_date', $date);
       if ($info == null) {
           return null;
       }
        $nowPrice = $info->open;
        $profitUnit = $nowPrice - $avg;
        return [$profitUnit > 0, $profitUnit / $avg];

    }
    public function sell($ts_code, $trade_date, $hand, $unitCost, $rate) {
        $data = $this->shippingSpace[$ts_code];
        if ($data == null) {
            return;
        }

        $nowHand = $hand;
        $price = 0;
        foreach ($data as &$item) {
            if ($nowHand == 0) {
                break;
            }
            if ($nowHand >= $item["hand"]) {
                $nowHand -=$item["hand"];
                $item["sold"] = true;
            } else {
                $item["hand"] -= $nowHand;
                $price += ($nowHand * $unitCost * 100) / $rate;
                $this->money += ($nowHand * $unitCost * 100) / $rate;
                $nowHand = 0;
                break;
            }
        }

        $data = Collect($data)->map(function ($v) use ($unitCost, &$price){
            if (($v["sold"] ?? false) == true) {
                $this->money += $v["hand"] * $unitCost * 100;
                $price += $v["hand"] * $unitCost * 100;
            }
            return $v;
        })->filter(function ($v){
            return ($v["sold"] ?? false) == false;
        })->toArray();

        if (empty($data)) {
            unset($this->shippingSpace[$ts_code]);
        } else {
            $this->shippingSpace[$ts_code] = $data;
        }

        $this->tradeLogs[] = [
            "ts_code" => $ts_code,
            "trade_date" => $trade_date,
            "type" => 1,
            "type_desc" => "sell",
            "hand" => $hand - $nowHand,
            "price" => $price,
            "unit_cost" => $unitCost,
            "after_money" => round($this->money, 2),
            "total_money" => $this->allPropertyMoney(),
        ];
    }

    public function buy($ts_code, $trade_date, $hand, $price, $unitCost) {
       $this->shippingSpace[$ts_code][] = [
           "hand" => $hand,
           "unit_cost" => $unitCost,
           "date" => $trade_date
       ];
       $this->money -= $price;

       $this->tradeLogs[] = [
           "ts_code" => $ts_code,
           "trade_date" => $trade_date,
           "type" => 0,
           "type_desc" => "buy",
           "hand" => $hand,
           "price" => $price,
           "unit_cost" => $unitCost,
           "after_money" => round($this->money, 2),
            "total_money" => $this->allPropertyMoney(),
       ];

    }
}

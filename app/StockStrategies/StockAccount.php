<?php


namespace App\StockStrategies;


class StockAccount
{
    public $money;
    public $shippingSpace = [];


    public $tradeLogs = [];

    public function __construct($money)
    {
        $this->money = $money;
    }
    public function sell($ts_code, $trade_date, $hand, $unitCost) {
        $data = $this->shippingSpace[$ts_code];
        if ($data == null) {
            return;
        }

        $nowHand = $hand;
        foreach ($data as &$item) {
            if ($nowHand == 0) {
                break;
            }
            if ($nowHand >= $item["hand"]) {
                $nowHand -=$item["hand"];
                $item["sold"] = true;
            } else {
                $item["hand"] -= $nowHand;
                $this->money += $nowHand * $unitCost * 100;
                $nowHand = 0;
                break;
            }
        }

        $data = Collect($data)->map(function ($v) use ($unitCost){
            if (($v["sold"] ?? false) == true) {
                $this->money += $v["hand"] * $unitCost * 100;
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
            "unit_cost" => $unitCost,
            "after_money" => $this->money
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
           "after_money" => $this->money
       ];

    }
}

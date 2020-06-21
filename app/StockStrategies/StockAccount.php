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

    public function buy($ts_code, $trade_date, $hand, $price, $unitCost) {
       $nowHand = $this->shippingSpace[$ts_code] ?? 0;
       $this->shippingSpace[$ts_code] = $nowHand + $hand;
       $this->money -= $price;

       $this->tradeLogs[] = [
           "ts_code" => $ts_code,
           "trade_date" => $trade_date,
           "hand" => $hand,
           "price" => $price,
           "unit_code" => $unitCost,
           "afterMoney" => $this->money
       ];

    }
}

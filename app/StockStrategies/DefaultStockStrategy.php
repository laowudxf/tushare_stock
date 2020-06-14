<?php


namespace App\StockStrategies;


class DefaultStockStrategy
{

    private $runContainer;

    public function ensureStockPool() {

        return [
            "000001.SZ",
            "000002.SZ",
            "002216.SZ",
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
    }


    public function closeQuotation($date) {
        $datas = $this->runContainer->stockDailyDate["002216.SZ"];
        $info = $datas[count($datas) - 2];
        $fq_last = $info->fq_factor;
        $close_prices = $this->runContainer->stockDailyDate["002216.SZ"]->map(function ($v) use($fq_last){
            $d = $v->only(["close", "trade_date", "fq_factor"]);
            $d["close"] = round($d["fq_factor"] / $fq_last * $d["close"], 3);
            return $d;
        });
        $closes = $close_prices->pluck("close")->toArray();
//        dd(1);
//        dd(trader_sma($closes, 26),trader_sma($closes, 12));
//        dd();
        $macds = trader_macdfix($closes, 9);
//        dd($macds);
        dd($macds, $close_prices->pluck("trade_date"), $close_prices);
    }
}

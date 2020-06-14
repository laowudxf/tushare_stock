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
            $d["close"] = round($d["fq_factor"] / $fq_last * $d["close"], 6);
            return $d;
        });

        $closes = $close_prices->pluck("close")->toArray();
        $ema12 = trader_ema($closes, 12);
        $ema26 = trader_ema($closes, 26);
        $diff = [];
        foreach ($ema26 as $key => $i) {
            $diff[$key] = round($ema12[$key] - $i, 6);
        }
        $a = trader_ema($diff, 9);
        $diffValues = array_values($diff);
//        dd($a, $m_1);
        $macd = [];
        foreach ($a as $key => $i) {
            $macd[$key + 25] = round(2*($diffValues[$key] - $i), 6);
        }

        $pr = [];
        $d = $close_prices->pluck("trade_date");
        foreach ($macd as $key => $i) {
            $pr[] = [round($i, 2) ,$d[$key]];
        }
        dd($pr);
    }

    function exponentialMovingAverage(array $numbers, int $n): array
    {
        $numbers=array_reverse($numbers);
        $m   = count($numbers);
        $α   = 2 / ($n + 1);
        $EMA = [];

        // Start off by seeding with the first data point
        $EMA[] = $numbers[0];

        // Each day after: EMAtoday = α⋅xtoday + (1-α)EMAyesterday
        for ($i = 1; $i < $m; $i++) {
            $EMA[] = ($α * $numbers[$i]) + ((1 - $α) * $EMA[$i - 1]);
        }
        $EMA=array_reverse($EMA);
        return $EMA;
    }
}

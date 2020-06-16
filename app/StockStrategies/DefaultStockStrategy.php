<?php


namespace App\StockStrategies;


class DefaultStockStrategy
{

    private $runContainer;

//    public $needTecs = [StockTecIndex::create(StockTecIndex::MACD)];
    public $needTecsParams = [
        [StockTecIndex::MACD, []],
        [StockTecIndex::MA, [5]],
        [StockTecIndex::MA, [10]],
        [StockTecIndex::RSI, [6]],
    ];

    public $needTecs = [];

    public $shouldPreDays = 0;
    public function __construct()
    {
       foreach ($this->needTecsParams as $param) {
           $tecIndex = StockTecIndex::create($param[0], $param[1]);
           $this->needTecs[] = $tecIndex;
           if ($tecIndex->shouldPreDays() > $this->shouldPreDays) {
               $this->shouldPreDays = $tecIndex->shouldPreDays();
           }
       }
    }

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
        tec_macd($closes);
        var_dump(1);
//        $ema12 = trader_ema($closes, 12);
//        $ema26 = trader_ema($closes, 26);
//        $diff = [];
//        foreach ($ema26 as $key => $i) {
//            $diff[$key] = round($ema12[$key] - $i, 6);
//        }
//        $a = trader_ema($diff, 9);
//        $diffValues = array_values($diff);
////        dd($a, $m_1);
//        $macd = [];
//        foreach ($a as $key => $i) {
//            $macd[$key + 25] = round(2*($diffValues[$key] - $i), 6);
//        }
//
//        $pr = [];
//        $d = $close_prices->pluck("trade_date");
//        foreach ($macd as $key => $i) {
//            $pr[] = [round($i, 2) ,$d[$key]];
//        }
//        dd($pr);
    }

}

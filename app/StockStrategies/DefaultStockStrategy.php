<?php


namespace App\StockStrategies;


use App\Models\Stock;

class DefaultStockStrategy
{

    private $runContainer;

//    public $needTecs = [StockTecIndex::create(StockTecIndex::MACD)];

    //初始 资金
    public $initMoney = 100000;

    public $needTecsParams = [
        [StockTecIndex::MACD, []],
        [StockTecIndex::RSI, [6]],
        [StockTecIndex::RSI, [12]],
        [StockTecIndex::MA, [5]],
        [StockTecIndex::MA, [10]],
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

//        $stockPools = Stock::take(100)->get();
//        return $stockPools->pluck('ts_code')->toArray();
//        dd($stockPools->toArray());

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
//        $ts_code, $trade_date, $hands,
//        $this->runContainer->buy("000001.SZ", $date, 10);
//        dd($this->runContainer->stockAccount->tradeLogs,$this->runContainer->stockAccount->shippingSpace);
    }

    private $buyPlan = [];

    public function closeQuotation($date) {

        dd($this->runContainer->stockTecData, $this->runContainer->tecIndexSlice("000001.SZ", 0, 20200331, 10));
        dd($this->runContainer->stockTecData, $this->runContainer->tecIndexSlice("000001.SZ", 0, $date->format("Ymd")));
        dd($this->runContainer->stockTecData);
    }

}

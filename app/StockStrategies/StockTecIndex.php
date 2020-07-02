<?php


namespace App\StockStrategies;


use App\Exceptions\CustomException;

class StockTecIndex
{
    const MACD = 0;
    const RSI = 1;
    const  MA = 2;
    const  BOLL = 3;

    public $tecIndex;
    public $period;

    public function __construct($tecIndex,array $period = [])
    {
        $this->tecIndex = $tecIndex;
        $this->period = $period;
    }

    public static function create($tecIndex,array $period = []) {
        return new StockTecIndex($tecIndex, $period);
    }

    public function shouldPreDays () {
        switch ($this->tecIndex) {
            case self::MACD:
                return 33;
                break;
            case self::MA:
            case self::RSI:
                return $this->period[0];
            case self::BOLL: //通常为20 +- 2
                return 22;
        }
    }

    function deal($datas, $realDateIndex, $dates) {
        if (is_array($this->period) == false) {
            throw CustomException::createWithError(CustomException::ERROR_PERIOD);
        }

       switch ($this->tecIndex) {
           case self::MACD:
               return tec_macd($datas);
               break;
           case self::RSI:
               return trader_rsi($datas, ...$this->period);
               break;
           case self::MA:
               return trader_ma($datas, ...$this->period);
               break;
           case self::BOLL:
//               $r = trader_bbands($datas, 20, 2, 2);
//               dd($r);
               return trader_bbands($datas, 20, 2, 2);
               break;
       }
    }

    private function insertDate($result, $realDateIndex, $dates) {

        if ($result == null) {
            null;
        }
        $a = array_filter($result, function ($key) use ($realDateIndex) {
            //-5 为了留一点技术指标提前量，方便对比。
            return $key >= $realDateIndex - 5;
        }, ARRAY_FILTER_USE_KEY);
        $aa = [];
        foreach ($a as $k => $value) {
            $aa[$dates[$k]] = $value;
        }
    }

}



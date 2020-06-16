<?php


namespace App\StockStrategies;


use App\Exceptions\CustomException;

class StockTecIndex
{
    const MACD = 0;
    const RSI = 1;
    const  MA = 2;

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
        }
    }

    function deal($datas) {
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
       }
    }

}



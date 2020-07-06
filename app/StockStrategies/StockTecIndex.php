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
            case self::BOLL: //通常为20 +- 2 计算斜率 100
                return 200;
        }
    }

    function deal($datas, $realDateIndex, $dates) {
        if (is_array($this->period) == false) {
            throw CustomException::createWithError(CustomException::ERROR_PERIOD);
        }

       switch ($this->tecIndex) {
           case self::MACD:
               return $this->insertDate( tec_macd($datas), $realDateIndex, $dates);
               break;
           case self::RSI:
               return $this->insertDate( trader_rsi($datas, ...$this->period), $realDateIndex, $dates);
               break;
           case self::MA:
               return $this->insertDate( trader_ma($datas, ...$this->period), $realDateIndex, $dates);
               break;
           case self::BOLL:
//               $r = trader_bbands($datas, 20, 2, 2);
//               dd($r);
               return $this->insertDate(trader_bbands($datas, 20, 2, 2), $realDateIndex, $dates);
               break;
       }
    }

    private function insertDate($result, $realDateIndex, $dates) {
        if ($result == null) {
            return null;
        }

        switch ($this->tecIndex) {
            case self::MACD:
            case self::RSI:
            case self::MA:
                $a = array_filter($result, function ($key) use ($realDateIndex) {
                    //-5 为了留一点技术指标提前量，方便对比。
                    return $key >= $realDateIndex - 5;
                }, ARRAY_FILTER_USE_KEY);
                $aa = [];
                foreach ($a as $k => $value) {
                    $aa[$dates[$k]] = $value;
                }
                return $aa;
            case self::BOLL:
                $aaa = [];
                $macdSlopePreDay = 80;
                $diffArr = [];
                foreach ($result as $key => $item) {
                    $a = array_filter($item, function ($key) use ($realDateIndex, $macdSlopePreDay) {
                        //-5 为了留一点技术指标提前量，方便对比。
                        // -80 为了计算斜率
                        return $key >= $realDateIndex - $macdSlopePreDay;
                    }, ARRAY_FILTER_USE_KEY);
                    $aa = [];
                    $slopeArr = [];
                    $firstKey = -1;

                    $window = [];
                    foreach ($a as $k => $value) {
                        //处理slope
                        $slope = null;
                        if ($firstKey == -1) {
                            $firstKey = $k;
                        }
                        $window[] = $value;

                        if (($k - $firstKey) == $macdSlopePreDay) {
                            array_shift($window);
                            $firstKey += 1;
                            $diff = 0;
                            if ($key == 0) { // upper
                                list($min, $max) = $this->findMaxMin($window);
                                $diff = $max - $min;
                                $diffArr[$k] = $diff;
                            } else {
                                $diff = $diffArr[$k];
                            }
                            $scale = 100 / $diff;
                                if (isset($a[$k - 1])) {
                                    $last = $a[$k - 1];
                                    $slope = ($value - $last) * $scale;
                                }
                        }
                        //-------处理slope

                        $aa[$dates[$k]] = $value;
                        $slopeArr[$dates[$k]] = $slope;
                    }

                    $aaa[$key] = $aa;
                    $aaa[$key + 3] = $slopeArr;
                }
                return $aaa;
        }
    }

    function findMaxMin($array) {
        $min = null;
        $max = null;
        foreach ($array as $key => $item) {
            if ($key == 0) {
                $min = $item;
                $max = $item;
                continue;
            }

            if ($item < $min) {
                $min = $item;
            }
            if ($item > $max) {
                $max = $item;
            }
        }

        return [$min, $max];
    }

    function arrayFirst(array $array) {
        if (count($array) == 0) {
            return null;
        }

        return $array[0];
    }
    function arrayLast(array $array) {
       if (count($array) == 0) {
           return null;
       }

       return $array[count($array) - 1];
    }


}



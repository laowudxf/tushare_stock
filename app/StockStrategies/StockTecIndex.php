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

                    $min = [];
                    $max = [];
                    foreach ($a as $k => $value) {
                        //处理slope
                        $slope = null;
                        if ($firstKey == -1) {
                            $min[] = $value;
                            $max[] = $value;
                            $firstKey = $k;
                        } else {
                            $lastMin = $this->arrayLast($min);
                            $lastMax = $this->arrayLast($max);
                            if ($lastMin && $value < $lastMin) {
                                $min[] = $value;
                            } else if ($lastMax && $value > $lastMax) {
                                $max[] = $value;
                            }
                        }

                        if (($k - $firstKey) == $macdSlopePreDay) {
                            $v = $a[$firstKey];
                            $firstKey += 1;
                            $lastMin = $this->arrayLast($min);
                            $lastMax = $this->arrayLast($max);

                            if ($v == $lastMin) {
                                array_pop($min);
                            } else if ($v == $lastMax) {
                                array_pop($max);
                            }
                            $lastMin = $this->arrayLast($min);
                            $lastMax = $this->arrayLast($max);

                            if ($lastMax == null) {
                                $max[] = array_shift($min);
                                $lastMax = $this->arrayLast($max);
                            }

                            if ($lastMin == null) {
                                $min[] = array_shift($max);
                                $lastMin = $this->arrayLast($min);
                            }

                            $diff = 0;
                            if ($key == 0) { // upper
                                $diff = $lastMax - $lastMin;
                                $diffArr[$k] = $diff;
                            } else {
                                $diff = $diffArr[$k];
                            }
                            $scale = 100 / $diff;
                                if (isset($a[$k - 1])) {
                                    $last = $a[$k - 1];
                                    $slope = ($value - $last) * $scale;
//                                    if ($k == 285) {
//                                        dd($slope, ($value - $last) * $scale, $scale);
//                                    }
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
    function arrayLast(array $array) {
       if (count($array) == 0) {
           return null;
       }

       return $array[count($array) - 1];
    }
}



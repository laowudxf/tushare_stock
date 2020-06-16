<?php

if (!function_exists('tec_macd')) {

    /***
     * @param $data
     * @return array
     */
    function tec_macd($data) {
        $closes = $data;
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
        return $macd;
    }

}

if (!function_exists("msectime")) {
    function msectime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }
}


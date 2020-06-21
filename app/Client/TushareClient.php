<?php


namespace App\Client;
use GuzzleHttp\Client;

class TushareClient
{

    const BASE_URL = "http://api.tushare.pro";
    const TOKEN = "ad4ec55b8beed935a2de6d1d01e4894317e01ec8ed2653cd21452e30";

    const URI_STOCK_BASIC = "stock_basic";
    const URI_DAILY = "daily";
    const URI_FQ = "adj_factor";
    const URI_TRADE_DATE = "trade_cal";
    private  $client;
    public function __construct()
    {
       $this->client = new Client();
    }

    public function stockList() {
        return $this->sendReqest(self::URI_STOCK_BASIC);
    }

    public function stockDaily($tsCode, $tradeDate = null, $startDate = null, $endDate = null) {
        $param = ["ts_code" => $tsCode];
        if($tradeDate) {
            $param = array_merge($param, ["trade_date" => $tradeDate]);
        } else if ($startDate && $endDate) {
            $param = array_merge($param, ["start_date" => $startDate, "end_date" => $endDate]);
        }
        return $this->sendReqest(self::URI_DAILY, $param);
    }

    public function stockFQ($tsCode, $tradeDate = null, $startDate = null, $endDate = null) {
        $param = ["ts_code" => $tsCode];
        if($tradeDate) {
            $param = array_merge($param, ["trade_date" => $tradeDate]);
        }

        if ($startDate) {
            $param = array_merge($param, ["start_date" => $startDate]);
        }

        if ($endDate) {
            $param = array_merge($param, ["end_date" => $endDate]);
        }

        return $this->sendReqest(self::URI_FQ, $param);
    }

    public function tradeDates($startDate, $endDate) {

        return $this->sendReqest(self::URI_TRADE_DATE, [
            "exchange" => "SSE",
            "start_date" => $startDate,
            "end_date" => $endDate,
            "is_open" => 1
        ]);
    }

    private function sendReqest($uri,$param = []) {
        if (empty($param)) {
            $param = (object)[];
        }
       $response = $this->client->request("POST", self::BASE_URL, [
            "json" =>  [
                "api_name" => $uri,
                "token" => self::TOKEN,
                "params" => $param
            ]
        ]);

       $json_str = (string)$response->getBody();
       $json = json_decode($json_str, true);
       return $json;
    }
}

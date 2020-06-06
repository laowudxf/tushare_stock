<?php


namespace App\Client;
use GuzzleHttp\Client;

class TushareClient
{

    const BASE_URL = "http://api.tushare.pro";
    const TOKEN = "ad4ec55b8beed935a2de6d1d01e4894317e01ec8ed2653cd21452e30";

    const URI_STOCK_BASIC = "stock_basic";
    private  $client;
    public function __construct()
    {
       $this->client = new Client();
    }

    public function stockList() {
        return $this->sendReqest(self::URI_STOCK_BASIC);
    }

    private function sendReqest($uri,$param = []) {
       $response = $this->client->request("POST", self::BASE_URL, [
            "json" =>  [
                "api_name" => $uri,
                "token" => self::TOKEN
//                "params" => $param
            ]
        ]);

       $json_str = (string)$response->getBody();
       $json = json_decode($json_str, true);
       return $json;
    }
}

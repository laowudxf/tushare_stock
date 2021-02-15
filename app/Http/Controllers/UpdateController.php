<?php

namespace App\Http\Controllers;

use App\Client\TushareClient;
use App\Models\TradeDate;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    //

    public function updateTradeDate($week) {
        $client = new TushareClient();
        $tradeDates = $client->tradeDates( $week ? now()->subDays(30)->format("Ymd") :"20000101", now()->format("Ymd"));
        if ($tradeDates["code"] != 0) {
            $this->warn("请求出错");
            return;
        }

        $insert_data = [];
        foreach ($tradeDates["data"]["items"] as $data) {
            if (TradeDate::where('trade_date', $data[1])->exists()) {
                continue;
            }
            $insert_data[] = [
                "trade_date" => $data[1]
            ];
        }
        TradeDate::insert($insert_data);

    }
}

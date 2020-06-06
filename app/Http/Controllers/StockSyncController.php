<?php

namespace App\Http\Controllers;

use App\Client\TushareClient;
use Illuminate\Http\Request;

class StockSyncController extends Controller
{
    //

    public function syncStockList(TushareClient $client) {
       $list = $client->stockList();

    }
}

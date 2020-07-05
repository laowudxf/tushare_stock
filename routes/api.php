<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get("hello", function (\App\Repository\Facade\RDS\RDS $rds) {
    $c = new \App\Http\Controllers\StockSyncController();
    $c->syncStockFQ();
});

Route::get('test', "StockController@test");

Route::get('stock', function () {
    return \App\Models\Stock::all();
});

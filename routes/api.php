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

Route::get("hello", function (\App\Http\Controllers\StockSyncController $syncController) {
    $syncController->syncStockFQDay(\App\Models\TradeDate::orderBy('trade_date', 'desc')->first()->trade_date);
});

Route::get('test', "StockController@test");
Route::get('analyse', "StockController@buyStockAnalyse");
Route::get('stocks', "StockController@stocks");
Route::get('stock', "StockController@stockInfo");


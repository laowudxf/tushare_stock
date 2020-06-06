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
    $stringtime = date("YmdHis",time());
    dd(strtotime($stringtime));
//     $json_str = file_get_contents( resource_path('province.json'));
//     $province = json_decode($json_str, true);
//    dd($province);
});

Route::get('test', function (\App\Client\TushareClient $client) {
  return $client->stockList();
});


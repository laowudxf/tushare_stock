<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class StockController extends Controller
{
    //
    public function test() {
        $start_date = Date::create(2020, 1, 1);
        dd($start_date);
    }
}

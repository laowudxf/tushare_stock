<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class StockTec extends Model
{

    protected $guarded = ["id"];
    public $timestamps = false;

    static function generateTecData($stock, $startDate, $endDate) {
        $result = StockTec::where('ts_code', $stock->ts_code)
            ->where('trade_date', '>=', intval($startDate))
            ->where('trade_date', '<=', intval($endDate))->get();

        return [
            0 => $result->pluck('macd', "trade_date")->toArray(),
            1 => [
                $result->pluck('boll_0', "trade_date")->toArray(),
                $result->pluck('boll_1', "trade_date")->toArray(),
                $result->pluck('boll_2', "trade_date")->toArray(),
                $result->pluck('boll_3', "trade_date")->toArray(),
                $result->pluck('boll_4', "trade_date")->toArray(),
                $result->pluck('boll_5', "trade_date")->toArray(),
            ]
        ];
    }
}

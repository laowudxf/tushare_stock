<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\StockDailyExtra
 *
 * @property int $id
 * @property string $ts_code
 * @property int $trade_date
 * @property float|null $pe
 * @property float|null $total_mv
 * @property float|null $float_mv
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stock[] $stocks
 * @property-read int|null $stocks_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDailyExtra newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDailyExtra newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDailyExtra query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDailyExtra whereFloatMv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDailyExtra whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDailyExtra wherePe($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDailyExtra whereTotalMv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDailyExtra whereTradeDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDailyExtra whereTsCode($value)
 * @mixin \Eloquent
 */
class StockDailyExtra extends Model
{
    //
    protected $table = "stock_dailies_extra";
    protected $guarded = ["id"];


    function stocks() {
        return $this->hasMany(Stock::class);
    }

    static function infoWithTsCodeQuery($ts_code) {
        $stock = Stock::where('ts_code', $ts_code)->first();
        return StockDailyExtra::where('stock_id', $stock->id);
    }
}

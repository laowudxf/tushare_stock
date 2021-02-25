<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\StockTec
 *
 * @property int $id
 * @property string $ts_code
 * @property int $trade_date
 * @property float|null $macd
 * @property float|null $boll_0
 * @property float|null $boll_1
 * @property float|null $boll_2
 * @property float|null $boll_3
 * @property float|null $boll_4
 * @property float|null $boll_5
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec whereBoll0($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec whereBoll1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec whereBoll2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec whereBoll3($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec whereBoll4($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec whereBoll5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec whereMacd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec whereTradeDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockTec whereTsCode($value)
 * @mixin \Eloquent
 */
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

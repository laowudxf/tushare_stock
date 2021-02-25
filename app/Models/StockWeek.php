<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\StockWeek
 *
 * @property int $id
 * @property int $stock_id
 * @property int $trade_date
 * @property float $open
 * @property float $high
 * @property float $low
 * @property float $close
 * @property float $pre_close
 * @property float $change
 * @property float $pct_chg
 * @property float $vol
 * @property float $amount
 * @property float $fq_factor
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stock[] $stocks
 * @property-read int|null $stocks_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek whereChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek whereClose($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek whereFqFactor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek whereHigh($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek whereLow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek whereOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek wherePctChg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek wherePreClose($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek whereStockId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek whereTradeDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockWeek whereVol($value)
 * @mixin \Eloquent
 */
class StockWeek extends Model
{
    protected $guarded = ["id"];
    public $timestamps = false;
//    const UPDATED_AT = null;

    function stocks() {
        return $this->hasMany(Stock::class);
    }
}

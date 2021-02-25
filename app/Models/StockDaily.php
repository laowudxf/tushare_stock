<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\StockDaily
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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily whereChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily whereClose($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily whereFqFactor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily whereHigh($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily whereLow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily whereOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily wherePctChg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily wherePreClose($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily whereStockId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily whereTradeDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockDaily whereVol($value)
 * @mixin \Eloquent
 */
class StockDaily extends Model
{
    protected $guarded = ["id", "updated_at"];
    public $timestamps = false;
//    const UPDATED_AT = null;

    function stocks() {
        return $this->hasMany(Stock::class);
    }

    static function infoWithTsCodeQuery($ts_code) {
        $stock = Stock::where('ts_code', $ts_code)->first();
        return StockDaily::where('stock_id', $stock->id);
    }

    function updatePrice() {
        $newDayData = StockDaily::where('stock_id', $this->stock_id)->orderBy('trade_date', 'desc')->first();
        $scale = $this->fq_factor / $newDayData->fq_factor;
        $this->open *= $scale;
        $this->open = round($this->open, 2);
        $this->close *= $scale;
        $this->close = round($this->close, 2);
    }

    static function updatePriceArray($array) {
        if (count($array) == 0) {
            return;
        }

        $newDayData = StockDaily::where('stock_id', $array[0]->stock_id)->orderBy('trade_date', 'desc')->first();
        foreach ($array as $stock) {
            $scale = $stock->fq_factor / $newDayData->fq_factor;
            $stock->open *= $scale;
            $stock->open = round($stock->open, 2);
            $stock->high *= $scale;
            $stock->high = round($stock->high, 2);
            $stock->low *= $scale;
            $stock->low = round($stock->low, 2);
            $stock->close *= $scale;
            $stock->close = round($stock->close, 2);
        }
    }
}

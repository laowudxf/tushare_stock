<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TradeDate
 *
 * @property int $id
 * @property int $trade_date
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TradeDate dates($start, $end)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TradeDate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TradeDate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TradeDate query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TradeDate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TradeDate whereTradeDate($value)
 * @mixin \Eloquent
 */
class TradeDate extends Model
{
    protected $fillable = ["trade_date"];
    public $timestamps = false;
    function scopeDates($query, $start, $end) {
        return $query->where('trade_date', '>=', $start)->where('trade_date', '<=', $end);
    }
}

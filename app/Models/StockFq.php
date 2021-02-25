<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\StockFq
 *
 * @property-read \App\Models\Stock $stock
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockFq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockFq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\StockFq query()
 * @mixin \Eloquent
 */
class StockFq extends Model
{

    protected $fillable = ["trade_date", "stock_id"];

    function stock() {
        return $this->belongsTo(Stock::class);
    }

}

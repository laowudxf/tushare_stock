<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

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
}

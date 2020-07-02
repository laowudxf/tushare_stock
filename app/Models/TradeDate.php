<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class TradeDate extends Model
{
    protected $fillable = ["trade_date"];
    public $timestamps = false;
    function scopeDates($query, $start, $end) {
        return $query->where('trade_date', '>=', $start)->where('trade_date', '<=', $end);
    }
}

<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = ["name", "symbol", 'ts_code', 'area_id', 'industry_id', 'market_id', 'list_date'];
    function area() {
        return $this->belongsTo(Area::class);
    }

    function industry() {
        return $this->belongsTo(Industry::class);
    }

    function market() {
       return $this->belongsTo(Market::class);
    }

    function stockDailies() {
       return $this->hasMany(StockDaily::class);
    }
}

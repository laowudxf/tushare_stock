<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class StockFq extends Model
{

    protected $fillable = ["trade_date", "stock_id"];

    function stock() {
        return $this->belongsTo(Stock::class);
    }

}

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
}

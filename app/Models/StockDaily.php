<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class StockDaily extends Model
{
    protected $guarded = ["id"];
    public $timestamps = false;

    function stocks() {
        return $this->hasMany(Stock::class);
    }
}

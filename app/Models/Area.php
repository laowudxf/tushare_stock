<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Area extends Model
{

    protected $fillable = ["name"];
    function stocks() {
        return $this->hasMany(Stock::class);
    }
}

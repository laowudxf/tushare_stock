<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Stock
 *
 * @property int $id
 * @property string $name
 * @property string $symbol
 * @property string $ts_code
 * @property int $area_id
 * @property int $industry_id
 * @property int $market_id
 * @property string $list_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Area $area
 * @property-read \App\Models\Industry $industry
 * @property-read \App\Models\Market $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\StockDaily[] $stockDailies
 * @property-read int|null $stock_dailies_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock whereAreaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock whereIndustryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock whereListDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock whereTsCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stock whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

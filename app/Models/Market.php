<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Market
 *
 * @property int $id
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stock[] $stocks
 * @property-read int|null $stocks_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Market newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Market newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Market query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Market whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Market whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Market whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Market whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Market extends Model
{
    protected $fillable = ["name"];

    function stocks() {
        return $this->hasMany(Stock::class);
    }
}

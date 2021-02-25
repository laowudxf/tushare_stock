<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Industry
 *
 * @property int $id
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stock[] $stocks
 * @property-read int|null $stocks_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Industry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Industry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Industry query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Industry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Industry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Industry whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Industry whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Industry extends Model
{
    protected $fillable = ["name"];

    function stocks() {
        return $this->hasMany(Stock::class);
    }
}

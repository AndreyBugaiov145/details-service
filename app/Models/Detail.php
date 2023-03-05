<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Detail
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $s_number
 * @property string $short_description
 * @property string $interchange_numbers
 * @property int $price
 * @property int|null $new_price
 * @property int|null $shipping_price
 * @property int|null $total_price
 * @property int|null $coefficient
 * @property int $stock
 * @property int $category_id
 * @property int $currency_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DetailAnalogue> $detail_analogues
 * @property-read int|null $detail_analogues_count
 * @method static \Illuminate\Database\Eloquent\Builder|Detail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Detail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Detail query()
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereCoefficient($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereInterchangeNumbers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereNewPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereSNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereShippingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereShortDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Detail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Detail extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        's_number',
        'short_description',
        'interchange_numbers',
        'price',
        'new_price',
        'shipping_price',
        'total_price',
        'coefficient',
        'category_id',
        'currency_id',
        'partkey',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, );
    }

    public function detail_analogues()
    {
        return $this->hasMany(DetailAnalogue::class, );
    }
}

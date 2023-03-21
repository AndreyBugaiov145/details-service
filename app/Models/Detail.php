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
 * @property int $us_shipping_price
 * @property int $ua_shipping_price
 * @property int $price_markup
 * @property int $stock
 * @property int $category_id
 * @property int $currency_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category $category
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

    protected $appends = ['total_price_usd', 'total_price_uah','isDisabled'];

    protected $fillable = [
        'title',
        'slug',
        's_number',
        'short_description',
        'interchange_numbers',
        'price',
        'us_shipping_price',
        'ua_shipping_price',
        'price_markup',
        'category_id',
        'currency_id',
        'partkey',
        'is_manual_added',
        'analogy_details',
    ];

    protected $casts = [
        'analogy_details' => 'array',
    ];


    public function category()
    {
        return $this->belongsTo(Category::class,);
    }

    public function getTotalPriceUsdAttribute()
    {
        return round( $this->price + $this->us_shipping_price + $this->ua_shipping_price + $this->price_markup,0);
    }

    public function getIsDisabledAttribute() :bool
    {
        return $this->price  == 0;
    }


    public function getTotalPriceUahAttribute()
    {
        $cf = optional(Currency::where('code', Currency::UAH_CODE)->first())->rate;

        return $this->total_price_usd * $cf;
    }
}

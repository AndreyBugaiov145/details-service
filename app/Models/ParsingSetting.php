<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ParsingSetting
 *
 * @property int $id
 * @property string $brand
 * @property int $year_from
 * @property string $year_to
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ParsingSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ParsingSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ParsingSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|ParsingSetting whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParsingSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParsingSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParsingSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParsingSetting whereYearFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParsingSetting whereYearTo($value)
 * @mixin \Eloquent
 */
class ParsingSetting extends Model
{
    use HasFactory;
    use SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL = 'fail';

    protected $fillable = [
        'brand',
        'year',
        'category_parsing_at',
        'car_models',
        'detail_parsing_at',
        'category_parsing_status',
        'detail_parsing_status',
        'is_show',
        'is_parsing_analogy_details'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'category_parsing_at',
        'detail_parsing_at'
    ];

    public function getCategoryParsingAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d G:i:s');
    }

    public function getDetailParsingAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d G:i:s');
    }

}


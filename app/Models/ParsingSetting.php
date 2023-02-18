<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected $fillable = ['brand', 'year_from', 'year_to'];
}

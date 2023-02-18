<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\DetailAnalogue
 *
 * @property int $id
 * @property string $brand
 * @property string $model
 * @property string $years
 * @property int $detail_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Detail $detail
 * @method static \Illuminate\Database\Eloquent\Builder|DetailAnalogue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DetailAnalogue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DetailAnalogue query()
 * @method static \Illuminate\Database\Eloquent\Builder|DetailAnalogue whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DetailAnalogue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DetailAnalogue whereDetailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DetailAnalogue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DetailAnalogue whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DetailAnalogue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DetailAnalogue whereYears($value)
 * @mixin \Eloquent
 */
class DetailAnalogue extends Model
{
    use HasFactory;

    public function detail()
    {
        return $this->belongsTo(Detail::class);
    }
}

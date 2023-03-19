<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    use HasFactory;

    public function apply(Builder $builder, Model $model): void
    {

    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('onlyWorking', function (Builder $builder) {
            $builder->where('fail_count', '<', 15);
        });
    }
}

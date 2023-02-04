<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detail extends Model
{
    use HasFactory;

    public function category()
    {
        return $this->belongsTo(Category::class, );
    }

    public function detail_analogues()
    {
        return $this->hasMany(DetailAnalogue::class, );
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParsingSetting extends Model
{
    use HasFactory;

    protected $fillable = ['brand', 'year_from', 'year_to'];
}

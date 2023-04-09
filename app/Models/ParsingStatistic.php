<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParsingStatistic extends Model
{
    use HasFactory;

    const PARSING_CATEGORY = 'categories';
    const PARSING_DETAIL = 'details';

    protected $table = 'grabber_statistic';

    protected $fillable = [
        'parsing_setting_id',
        'parsing_status',
        'request_count',
        'request_time',
        'parsing_type',
        'used_memory',
    ];
}

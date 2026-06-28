<?php

namespace App\Models;

use App\Enums\LogType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'series_id',
        'message',
        'type',
    ];

    protected $casts = [
        'type' => LogType::class,
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }
}

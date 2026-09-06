<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteCheckLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'monitored_site_id',
        'status_code',
        'response_time_ms',
        'is_online',
        'error_message',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'response_time_ms' => 'float',
            'is_online' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MonitoredSite, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(MonitoredSite::class, 'monitored_site_id');
    }
}

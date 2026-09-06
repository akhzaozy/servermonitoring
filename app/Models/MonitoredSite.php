<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'stack_type',
        'port',
        'is_active',
        'last_status_code',
        'last_response_time_ms',
        'is_online',
        'last_checked_at',
        'last_error',
        'ssl_valid',
        'ssl_expires_at',
        'expected_keyword',
        'vhost_path',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_online' => 'boolean',
            'ssl_valid' => 'boolean',
            'last_response_time_ms' => 'float',
            'last_status_code' => 'integer',
            'port' => 'integer',
            'last_checked_at' => 'datetime',
            'ssl_expires_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<SiteCheckLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SiteCheckLog::class);
    }

    /**
     * Recent check logs for sparklines / history bar.
     *
     * @return HasMany<SiteCheckLog, $this>
     */
    public function recentLogs(int $limit = 30): HasMany
    {
        return $this->logs()->latest('id')->take($limit);
    }
}

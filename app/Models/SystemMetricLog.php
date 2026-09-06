<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemMetricLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'cpu_usage_percent',
        'ram_usage_percent',
        'ram_used_mb',
        'ram_total_mb',
        'disk_usage_percent',
        'disk_read_kb_s',
        'disk_write_kb_s',
        'temperature_celsius',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'cpu_usage_percent' => 'float',
            'ram_usage_percent' => 'float',
            'ram_used_mb' => 'float',
            'ram_total_mb' => 'float',
            'disk_usage_percent' => 'float',
            'disk_read_kb_s' => 'float',
            'disk_write_kb_s' => 'float',
            'temperature_celsius' => 'float',
            'recorded_at' => 'datetime',
        ];
    }
}

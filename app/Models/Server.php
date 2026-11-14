<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $fillable = [
        'name',
        'type',
        'base_url',
        'token',
        'ssh_host',
        'ssh_port',
        'ssh_user',
        'ssh_password',
        'ssh_key',
        'use_cloudflare',
        'portainer_url',
        'is_active',
    ];

    protected $hidden = ['token', 'ssh_password', 'ssh_key'];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'ssh_password' => 'encrypted',
            'ssh_key' => 'encrypted',
            'ssh_port' => 'integer',
            'use_cloudflare' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isLocal(): bool
    {
        return $this->type === 'local';
    }

    public function isSsh(): bool
    {
        return $this->type === 'ssh';
    }

    /**
     * Server tempat dashboard berjalan. Dibuat otomatis jika belum ada.
     */
    public static function local(): self
    {
        return static::firstOrCreate(['type' => 'local'], [
            'name' => config('monitor.server_name'),
            'portainer_url' => config('monitor.portainer_url'),
        ]);
    }

    public static function resolve(?int $id): self
    {
        return ($id ? static::find($id) : null) ?? static::local();
    }
}

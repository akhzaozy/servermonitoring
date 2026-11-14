<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $fillable = ['name', 'type', 'base_url', 'token', 'portainer_url', 'is_active'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function isLocal(): bool
    {
        return $this->type === 'local';
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

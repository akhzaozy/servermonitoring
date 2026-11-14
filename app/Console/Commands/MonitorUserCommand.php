<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MonitorUserCommand extends Command
{
    protected $signature = 'monitor:user
        {email? : Email login}
        {--name= : Nama tampilan}
        {--password= : Password (jika kosong akan ditanyakan)}
        {--from-env : Baca ADMIN_EMAIL / ADMIN_PASSWORD dari environment; lewati jika sudah ada}';

    protected $description = 'Buat atau perbarui akun login dashboard';

    public function handle(): int
    {
        if ($this->option('from-env')) {
            $email = config('monitor.admin.email');
            $password = config('monitor.admin.password');
            if (! $email || ! $password) {
                return self::SUCCESS;
            }
            if (User::where('email', $email)->exists()) {
                return self::SUCCESS;
            }
            $name = config('monitor.admin.name');
        } else {
            $email = $this->argument('email') ?: $this->ask('Email');
            $name = $this->option('name') ?: 'Administrator';
            $password = $this->option('password') ?: $this->secret('Password (min. 8 karakter)');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string) $password) < 8) {
            $this->error('Email tidak valid atau password kurang dari 8 karakter.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => $password]);
        $this->info(($user->wasRecentlyCreated ? 'Akun dibuat: ' : 'Akun diperbarui: ').$email);

        return self::SUCCESS;
    }
}

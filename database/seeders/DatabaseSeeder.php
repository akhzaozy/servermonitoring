<?php

namespace Database\Seeders;

use App\Models\MonitoredSite;
use App\Models\SiteCheckLog;
use App\Models\SystemMetricLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $sitesData = [
            [
                'name' => 'Next.js Frontend App',
                'url' => 'http://localhost:3000',
                'stack_type' => 'nextjs',
                'port' => 3000,
                'is_active' => true,
                'last_status_code' => 200,
                'last_response_time_ms' => 18.5,
                'is_online' => true,
                'last_checked_at' => now()->subSeconds(10),
                'vhost_path' => '/etc/nginx/sites-enabled/nextjs-portal.conf',
            ],
            [
                'name' => 'Laravel REST API & Engine',
                'url' => 'http://127.0.0.1:8000',
                'stack_type' => 'laravel',
                'port' => 8000,
                'is_active' => true,
                'last_status_code' => 200,
                'last_response_time_ms' => 34.2,
                'is_online' => true,
                'last_checked_at' => now()->subSeconds(15),
                'vhost_path' => '/etc/nginx/sites-enabled/laravel-engine.conf',
            ],
            [
                'name' => 'PHP Native Admin Portal',
                'url' => 'http://127.0.0.1:8080',
                'stack_type' => 'native_php',
                'port' => 8080,
                'is_active' => true,
                'last_status_code' => 200,
                'last_response_time_ms' => 12.8,
                'is_online' => true,
                'last_checked_at' => now()->subSeconds(20),
                'vhost_path' => '/etc/nginx/sites-enabled/native-portal.conf',
            ],
            [
                'name' => 'Cloudflare Tunnel Gateway',
                'url' => 'https://1.1.1.1',
                'stack_type' => 'api',
                'port' => 443,
                'is_active' => true,
                'last_status_code' => 200,
                'last_response_time_ms' => 15.4,
                'is_online' => true,
                'last_checked_at' => now()->subSeconds(5),
                'ssl_valid' => true,
                'ssl_expires_at' => now()->addDays(85),
            ],
            [
                'name' => 'STB Local Network Services',
                'url' => 'http://127.0.0.1:9000',
                'stack_type' => 'other',
                'port' => 9000,
                'is_active' => true,
                'last_status_code' => 200,
                'last_response_time_ms' => 8.2,
                'is_online' => true,
                'last_checked_at' => now()->subSeconds(25),
            ],
        ];

        foreach ($sitesData as $data) {
            $site = MonitoredSite::create($data);

            // Generate 20 realistic check logs for 24h history bar & sparklines
            for ($i = 20; $i >= 0; $i--) {
                $isUp = true;
                $ms = round(rand(10, 85) + ($data['stack_type'] === 'laravel' ? 20 : 5), 1);
                $code = 200;

                SiteCheckLog::create([
                    'monitored_site_id' => $site->id,
                    'status_code' => $code,
                    'response_time_ms' => $ms,
                    'is_online' => $isUp,
                    'checked_at' => now()->subMinutes($i * 3),
                ]);
            }
        }

        // Generate initial System Metric snapshots for CPU, RAM, and Disk Load sparklines
        for ($j = 25; $j >= 0; $j--) {
            $cpu = round(rand(12, 38) + sin($j) * 8, 1);
            $ramPercent = round(42.5 + cos($j) * 3, 1);
            $readKb = round(rand(50, 450) / 10, 1);
            $writeKb = round(rand(120, 850) / 10, 1);
            $temp = round(48.5 + rand(-20, 30) / 10, 1);

            SystemMetricLog::create([
                'cpu_usage_percent' => $cpu,
                'ram_usage_percent' => $ramPercent,
                'ram_used_mb' => 870.4,
                'ram_total_mb' => 2048.0,
                'disk_usage_percent' => 38.2,
                'disk_read_kb_s' => $readKb,
                'disk_write_kb_s' => $writeKb,
                'temperature_celsius' => $temp,
                'recorded_at' => now()->subSeconds($j * 20),
            ]);
        }
    }
}

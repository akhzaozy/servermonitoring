<?php

namespace Tests\Feature;

use App\Models\MonitoredSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('STB Monitor');
        $response->assertSee('Beban CPU');
        $response->assertSee('RAM Memory');
        $response->assertSee('Storage & Disk Load', false);
    }

    public function test_metrics_api_returns_system_telemetry(): void
    {
        $response = $this->getJson('/api/monitoring/metrics');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'cpu' => ['usage_percent', 'cores', 'load_avg'],
            'ram' => ['total_mb', 'used_mb', 'free_mb', 'usage_percent'],
            'disk' => ['total_gb', 'used_gb', 'free_gb', 'usage_percent'],
            'disk_io' => ['read_kb_s', 'write_kb_s', 'load_status', 'load_percent'],
            'temperature' => ['celsius', 'status'],
            'system' => ['hostname', 'os', 'kernel', 'architecture'],
        ]);
    }

    public function test_nginx_status_and_syntax_test(): void
    {
        $info = $this->getJson('/api/monitoring/nginx');
        $info->assertStatus(200);
        $info->assertJsonStructure(['installed', 'is_running', 'status_label', 'version', 'worker_processes']);

        $test = $this->postJson('/api/monitoring/nginx/test');
        $test->assertStatus(200);
        $test->assertJsonStructure(['success', 'output']);
    }

    public function test_monitored_site_crud_and_check(): void
    {
        $site = MonitoredSite::create([
            'name' => 'Next.js App Test',
            'url' => 'https://1.1.1.1',
            'stack_type' => 'nextjs',
            'port' => 3000,
            'is_active' => true,
        ]);

        $list = $this->getJson('/api/monitoring/sites');
        $list->assertStatus(200);
        $this->assertCount(1, $list->json());

        $check = $this->postJson("/api/monitoring/sites/{$site->id}/check");
        $check->assertStatus(200);
        $check->assertJsonStructure(['check', 'uptime_percentage', 'history_bars']);

        $delete = $this->deleteJson("/api/monitoring/sites/{$site->id}");
        $delete->assertStatus(200);
        $this->assertDatabaseCount('monitored_sites', 0);
    }
}

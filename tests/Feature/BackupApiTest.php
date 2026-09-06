<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_status_endpoint_returns_valid_structure(): void
    {
        $response = $this->getJson('/api/monitoring/backup/status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'is_running',
            'script_exists',
            'script_path',
            'backup_dir',
            'last_run' => [
                'timestamp',
                'human',
                'status',
            ],
            'total_size_human',
            'latest_files',
            'recent_logs',
        ]);
    }

    public function test_backup_logs_endpoint_returns_log_lines(): void
    {
        $response = $this->getJson('/api/monitoring/backup/logs?lines=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'is_running',
            'logs',
        ]);
        $this->assertIsArray($response->json('logs'));
    }

    public function test_manual_backup_trigger_endpoint(): void
    {
        $response = $this->postJson('/api/monitoring/backup/run');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
        ]);
    }
}

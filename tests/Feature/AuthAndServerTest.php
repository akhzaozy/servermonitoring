<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthAndServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_login(): void
    {
        $this->getJson('/api/monitoring/metrics')->assertUnauthorized();
        $this->postJson('/api/monitoring/terminal/exec', ['command' => 'ls'])->assertUnauthorized();
    }

    public function test_user_can_login_and_logout(): void
    {
        User::factory()->create(['email' => 'a@b.test', 'password' => 'secret-pass']);

        $this->post('/login', ['email' => 'a@b.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'a@b.test', 'password' => 'secret-pass'])->assertRedirect('/');
        $this->assertAuthenticated();

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_all_pages_render_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['/', '/websites', '/backups', '/servers', '/terminal'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_terminal_runs_commands_and_tracks_cwd(): void
    {
        $this->actingAs(User::factory()->create());

        $r = $this->postJson('/api/monitoring/terminal/exec', ['command' => 'echo halo', 'cwd' => '/tmp'])->assertOk();
        $this->assertSame('halo', trim($r->json('output')));
        $this->assertStringEndsWith('tmp', $r->json('cwd'));

        $r = $this->postJson('/api/monitoring/terminal/exec', ['command' => 'cd /; pwd', 'cwd' => '/tmp'])->assertOk();
        $this->assertSame('/', $r->json('cwd'));

        $r = $this->postJson('/api/monitoring/terminal/exec', ['command' => 'exit 3; true'])->assertOk();
        $this->assertNotNull($r->json('cwd'));
    }

    public function test_terminal_can_be_disabled(): void
    {
        config(['monitor.terminal.enabled' => false]);
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/monitoring/terminal/exec', ['command' => 'ls'])->assertForbidden();
    }

    public function test_agent_endpoints_require_token(): void
    {
        $this->getJson('/api/agent/metrics')->assertStatus(503);

        config(['monitor.agent.token' => 'tok-123']);
        $this->getJson('/api/agent/metrics')->assertUnauthorized();
        $this->withToken('tok-123')->getJson('/api/agent/metrics')->assertOk()->assertJsonStructure(['cpu', 'ram', 'disk']);
        $this->withToken('tok-123')->postJson('/api/agent/exec', ['command' => 'echo x'])->assertForbidden();

        config(['monitor.agent.allow_exec' => true]);
        $this->withToken('tok-123')->postJson('/api/agent/exec', ['command' => 'echo x'])->assertOk()->assertJsonPath('exit_code', 0);
    }

    public function test_remote_server_metrics_are_proxied_from_agent(): void
    {
        $this->actingAs(User::factory()->create());
        $server = Server::create(['name' => 'S2', 'type' => 'remote', 'base_url' => 'http://10.0.0.2:8080', 'token' => 'tok']);

        Http::fake([
            '10.0.0.2:8080/api/agent/metrics' => Http::response([
                'cpu' => ['usage_percent' => 12.5, 'cores' => 2, 'load_avg' => [0.1, 0.1, 0.1]],
                'ram' => ['usage_percent' => 40, 'used_mb' => 400, 'total_mb' => 1000],
                'disk' => ['usage_percent' => 10, 'used_gb' => 1, 'total_gb' => 10, 'partitions' => []],
                'disk_io' => ['read_kb_s' => 0, 'write_kb_s' => 0],
                'temperature' => ['celsius' => null],
                'system' => ['hostname' => 'srv2', 'uptime_formatted' => '1d'],
            ]),
        ]);

        $this->getJson('/api/monitoring/metrics?server='.$server->id)
            ->assertOk()->assertJsonPath('system.hostname', 'srv2')->assertJsonPath('server.name', 'S2');

        $this->getJson('/api/monitoring/servers/overview')->assertOk()->assertJsonCount(2);
    }

    public function test_server_crud_hides_token_and_protects_local(): void
    {
        $this->actingAs(User::factory()->create());

        $res = $this->postJson('/api/monitoring/servers', ['name' => 'S2', 'base_url' => 'http://1.2.3.4:8080', 'token' => 'secret'])->assertCreated();
        $this->assertArrayNotHasKey('token', $res->json('server'));
        $this->assertSame('secret', Server::find($res->json('server.id'))->token);

        $local = Server::local();
        $this->deleteJson('/api/monitoring/servers/'.$local->id)->assertStatus(422);
    }

    public function test_ssh_cloudflare_server_can_be_created_and_encrypts_credentials(): void
    {
        $this->actingAs(User::factory()->create());

        $res = $this->postJson('/api/monitoring/servers', [
            'type' => 'ssh',
            'name' => 'Server FSTI',
            'ssh_host' => 'server-fsti.myst-tech.com',
            'ssh_user' => 'adminfsti',
            'ssh_password' => 'rahasia123',
            'use_cloudflare' => true,
        ])->assertCreated();

        $this->assertSame('ssh', $res->json('server.type'));
        $this->assertTrue($res->json('server.use_cloudflare'));
        $this->assertTrue($res->json('server.has_password'));
        $this->assertArrayNotHasKey('ssh_password', $res->json('server'));

        $srv = Server::find($res->json('server.id'));
        $this->assertSame('rahasia123', $srv->ssh_password);
    }
}

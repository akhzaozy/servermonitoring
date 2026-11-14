<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Mengambil metrik sistem & menjalankan perintah terminal di server remote
 * langsung lewat SSH atau Cloudflare Tunnel (cloudflared access ssh)
 * TANPA perlu meng-install aplikasi/agent di server tujuan.
 */
class SshServerService
{
    private const MARKER = '__STB_END__';

    /**
     * Ambil metrik lengkap (CPU, RAM, Disk, Disk I/O, Suhu, System Info) via SSH satu kali jalan.
     *
     * @return array<string, mixed>
     */
    public function metrics(Server $server): array
    {
        $probeScript = <<<'SH'
export LC_ALL=C
echo "===HOST==="
hostname 2>/dev/null || uname -n
uname -r 2>/dev/null
uname -m 2>/dev/null
echo "===OS==="
if [ -r /etc/os-release ]; then grep '^PRETTY_NAME=' /etc/os-release | head -n 1; fi
echo "===MODEL==="
if [ -r /proc/device-tree/model ]; then tr -d '\0\r\n' < /proc/device-tree/model; echo "";
elif [ -r /sys/devices/virtual/dmi/id/product_name ]; then head -n 1 /sys/devices/virtual/dmi/id/product_name;
else echo "Linux Server"; fi
echo "===UPTIME==="
if [ -r /proc/uptime ]; then cat /proc/uptime; else echo "0 0"; fi
echo "===CORES==="
grep -c '^processor' /proc/cpuinfo 2>/dev/null || nproc 2>/dev/null || echo 1
echo "===LOAD==="
if [ -r /proc/loadavg ]; then cat /proc/loadavg; else echo "0.00 0.00 0.00"; fi
echo "===STAT1==="
head -n 1 /proc/stat 2>/dev/null
sleep 0.08 2>/dev/null || sleep 1
echo "===STAT2==="
head -n 1 /proc/stat 2>/dev/null
echo "===MEM==="
grep -E '^(MemTotal|MemFree|MemAvailable|SwapTotal|SwapFree):' /proc/meminfo 2>/dev/null
echo "===DF==="
df -P -k / 2>/dev/null | tail -n +2
echo "===VMSTAT==="
grep -E '^(pgpgin|pgpgout) ' /proc/vmstat 2>/dev/null
echo "===TEMP==="
cat /sys/class/thermal/thermal_zone0/temp 2>/dev/null || cat /sys/class/thermal/thermal_zone1/temp 2>/dev/null || echo ""
SH;

        $process = $this->buildSshProcess($server, $probeScript, 12);
        $process->run();

        if (! $process->isSuccessful() && trim($process->getOutput()) === '') {
            $err = trim($process->getErrorOutput()) ?: 'Gagal terhubung ke server via SSH.';
            throw new RuntimeException($this->cleanSshError($err, $server));
        }

        return $this->parseMetricsOutput($process->getOutput(), $server);
    }

    /**
     * Jalankan perintah di Web Terminal melalui SSH / Cloudflare Tunnel.
     *
     * @return array{output: string, cwd: string, exit_code: int, user: string, host: string, timed_out: bool}
     */
    public function exec(Server $server, string $command, ?string $cwd = null): array
    {
        $timeout = max(5, (int) config('monitor.terminal.timeout', 30));
        $cd = ($cwd !== null && $cwd !== '')
            ? 'cd '.escapeshellarg($cwd).' 2>/dev/null || cd ~ 2>/dev/null'
            : 'cd ~ 2>/dev/null';

        $remoteScript = $cd.'; eval '.escapeshellarg($command).'; ec=$?; '
            .'printf \'\n'.self::MARKER.'%s|%s|%s|%s\n\' "$ec" "$PWD" "$(id -un)" "$(hostname)"';

        $process = $this->buildSshProcess($server, $remoteScript, $timeout);

        $timedOut = false;
        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            $timedOut = true;
        }

        $raw = $process->getOutput().$process->getErrorOutput();
        $output = $raw;
        $state = [
            'exit' => $timedOut ? 124 : $process->getExitCode() ?? 0,
            'cwd' => $cwd ?? '~',
            'user' => $server->ssh_user ?: 'user',
            'host' => $server->ssh_host ?: 'server',
        ];

        $pattern = '/\n?'.self::MARKER.'(\d+)\|(.*?)\|(.*?)\|(.*?)\s*$/s';
        if (preg_match($pattern, $raw, $m)) {
            $output = substr($raw, 0, strlen($raw) - strlen($m[0]));
            $state = [
                'exit' => (int) $m[1],
                'cwd' => trim($m[2]),
                'user' => trim($m[3]),
                'host' => trim($m[4]),
            ];
        } elseif (! $process->isSuccessful() && ! $timedOut) {
            throw new RuntimeException($this->cleanSshError($raw, $server));
        }

        if ($timedOut) {
            $output .= "\n[dihentikan: melewati batas waktu {$timeout} detik]";
        }

        $max = (int) config('monitor.terminal.max_output', 262144);
        if (strlen($output) > $max) {
            $output = substr($output, 0, $max)."\n[output dipotong]";
        }

        return [
            'output' => $output,
            'cwd' => $state['cwd'],
            'exit_code' => $state['exit'],
            'user' => $state['user'],
            'host' => $state['host'],
            'timed_out' => $timedOut,
        ];
    }

    /**
     * Buat instance Process untuk koneksi SSH (mendukung Cloudflare Tunnel & Password/Key).
     */
    protected function buildSshProcess(Server $server, string $remoteScript, int $timeout): Process
    {
        $host = trim((string) $server->ssh_host);
        $user = trim((string) $server->ssh_user);
        $port = (int) ($server->ssh_port ?: 22);

        if ($host === '' || $user === '') {
            throw new RuntimeException('Host SSH dan Username SSH wajib diisi.');
        }

        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $host)) {
            throw new RuntimeException('Format hostname SSH tidak valid.');
        }

        $controlPath = '/tmp/stb_ssh_mux_'.md5($server->id.'|'.$user.'|'.$host.'|'.$port.'|'.($server->use_cloudflare ? 'cf' : 'std'));

        $args = [
            'ssh',
            '-T',
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'UserKnownHostsFile=/tmp/monitor_known_hosts',
            '-o', 'ConnectTimeout=8',
            '-o', 'ServerAliveInterval=15',
            '-o', 'ControlMaster=auto',
            '-o', 'ControlPath='.$controlPath,
            '-o', 'ControlPersist=180s',
            '-o', 'LogLevel=ERROR',
        ];

        if ($server->use_cloudflare) {
            $args[] = '-o';
            $args[] = 'ProxyCommand=cloudflared access ssh --hostname '.$host;
            $args[] = '-o';
            $args[] = 'HostKeyAlias='.$host;
        } else {
            $args[] = '-p';
            $args[] = (string) $port;
        }

        $env = $this->baseEnv();

        // Private key (opsional: bisa berupa path file atau isi private key)
        $keyInput = trim((string) $server->ssh_key);
        if ($keyInput !== '') {
            $keyPath = $this->preparePrivateKey($server->id, $keyInput);
            if ($keyPath) {
                $args[] = '-i';
                $args[] = $keyPath;
            }
        }

        // Password SSH via SSH_ASKPASS (tanpa perlu sshpass)
        $password = (string) $server->ssh_password;
        if ($password !== '') {
            $askpassScript = '/tmp/stb_ssh_askpass.sh';
            if (! is_executable($askpassScript)) {
                @file_put_contents($askpassScript, "#!/bin/sh\nprintf '%s\\n' \"\$STB_SSH_PASS\"\n");
                @chmod($askpassScript, 0700);
            }
            $env['SSH_ASKPASS'] = $askpassScript;
            $env['SSH_ASKPASS_REQUIRE'] = 'force';
            $env['DISPLAY'] = ':0';
            $env['STB_SSH_PASS'] = $password;
            $args[] = '-o';
            $args[] = 'NumberOfPasswordPrompts=1';
        } else {
            $args[] = '-o';
            $args[] = 'BatchMode=yes';
        }

        $target = $server->use_cloudflare ? ($user.'@localhost') : ($user.'@'.$host);
        $args[] = $target;
        $args[] = 'sh -c '.escapeshellarg($remoteScript);

        $process = new Process($args, null, $env);
        $process->setTimeout($timeout);
        $process->setInput('');

        return $process;
    }

    protected function preparePrivateKey(?int $serverId, string $keyInput): ?string
    {
        if (str_contains($keyInput, 'PRIVATE KEY')) {
            $file = '/tmp/stb_ssh_key_'.($serverId ?: 'tmp');
            $normalized = str_replace("\r\n", "\n", trim($keyInput))."\n";
            @file_put_contents($file, $normalized);
            @chmod($file, 0600);

            return $file;
        }

        if (str_starts_with($keyInput, '~/')) {
            $home = $this->baseEnv()['HOME'];
            $keyInput = $home.substr($keyInput, 1);
        }

        return is_readable($keyInput) ? $keyInput : null;
    }

    /**
     * @return array<string, string>
     */
    protected function baseEnv(): array
    {
        $home = getenv('HOME');
        if (! $home && function_exists('posix_getpwuid')) {
            $home = posix_getpwuid(posix_geteuid())['dir'] ?? null;
        }

        return [
            'HOME' => $home ?: '/tmp',
            'PATH' => '/opt/homebrew/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
            'TERM' => 'xterm-256color',
            'LANG' => 'C.UTF-8',
        ];
    }

    protected function cleanSshError(string $err, Server $server): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $err) ?? '');
        if (str_contains(strtolower($clean), 'permission denied')) {
            return 'Autentikasi SSH ditolak (periksa username / password / SSH key untuk '.$server->ssh_host.').';
        }
        if (str_contains(strtolower($clean), 'cloudflared') && str_contains(strtolower($clean), 'not found')) {
            return 'Binary cloudflared belum terinstall di server dashboard utama.';
        }

        return $clean !== '' ? $clean : 'Koneksi SSH ke '.$server->ssh_host.' gagal.';
    }

    /**
     * Parse output shell dari server SSH menjadi struktur metrik standar.
     *
     * @return array<string, mixed>
     */
    protected function parseMetricsOutput(string $raw, Server $server): array
    {
        $sections = [];
        $current = null;
        foreach (explode("\n", $raw) as $line) {
            if (preg_match('/^===([A-Z0-9]+)===$/', trim($line), $m)) {
                $current = $m[1];
                $sections[$current] = [];

                continue;
            }
            if ($current !== null) {
                $sections[$current][] = $line;
            }
        }

        // 1. System Info
        $hostLines = array_values(array_filter(array_map('trim', $sections['HOST'] ?? [])));
        $hostname = $hostLines[0] ?? ($server->ssh_host ?: 'ssh-server');
        $kernel = $hostLines[1] ?? 'Linux';
        $arch = $hostLines[2] ?? 'x86_64';

        $os = 'Linux';
        $osLine = implode("\n", $sections['OS'] ?? []);
        if (preg_match('/PRETTY_NAME="?([^"\n]+)"?/', $osLine, $m)) {
            $os = trim($m[1]);
        }

        $model = trim(implode(' ', $sections['MODEL'] ?? '')) ?: 'Linux Server';
        if ($server->use_cloudflare) {
            $model .= ' (Cloudflare Tunnel)';
        }

        $uptimeParts = preg_split('/\s+/', trim(implode(' ', $sections['UPTIME'] ?? '0')));
        $uptimeSeconds = (int) floor((float) ($uptimeParts[0] ?? 0));
        $days = floor($uptimeSeconds / 86400);
        $hours = floor(($uptimeSeconds % 86400) / 3600);
        $minutes = floor(($uptimeSeconds % 3600) / 60);

        // 2. CPU
        $cores = max(1, (int) trim(implode('', $sections['CORES'] ?? ['1'])));
        $loadParts = preg_split('/\s+/', trim(implode(' ', $sections['LOAD'] ?? '0 0 0')));
        $loadAvg = [
            round((float) ($loadParts[0] ?? 0), 2),
            round((float) ($loadParts[1] ?? 0), 2),
            round((float) ($loadParts[2] ?? 0), 2),
        ];

        $stat1 = $this->parseCpuStatLine($sections['STAT1'][0] ?? '');
        $stat2 = $this->parseCpuStatLine($sections['STAT2'][0] ?? '');
        $cpuUsage = 0.0;
        if ($stat1 && $stat2 && ($stat2['total'] - $stat1['total']) > 0) {
            $totalDelta = $stat2['total'] - $stat1['total'];
            $idleDelta = $stat2['idle'] - $stat1['idle'];
            $cpuUsage = round((1 - ($idleDelta / $totalDelta)) * 100, 1);
        } else {
            $cpuUsage = round(min(100, ($loadAvg[0] / $cores) * 100), 1);
        }

        // 3. RAM
        $mem = [];
        foreach ($sections['MEM'] ?? [] as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', trim($line), $m)) {
                $mem[$m[1]] = (int) $m[2];
            }
        }
        $totalKb = max(1, $mem['MemTotal'] ?? 1048576);
        $availKb = $mem['MemAvailable'] ?? ($mem['MemFree'] ?? 524288);
        $usedKb = max(0, $totalKb - $availKb);
        $swapTotalKb = $mem['SwapTotal'] ?? 0;
        $swapFreeKb = $mem['SwapFree'] ?? 0;

        $totalMb = round($totalKb / 1024, 1);
        $usedMb = round($usedKb / 1024, 1);
        $freeMb = round($availKb / 1024, 1);
        $ramPercent = round(($usedKb / $totalKb) * 100, 1);

        // 4. Disk
        $dfLine = trim($sections['DF'][0] ?? '');
        $dfParts = preg_split('/\s+/', $dfLine);
        $diskTotalKb = isset($dfParts[1]) ? max(1, (int) $dfParts[1]) : 16 * 1024 * 1024;
        $diskUsedKb = isset($dfParts[2]) ? (int) $dfParts[2] : 4 * 1024 * 1024;
        $diskFreeKb = isset($dfParts[3]) ? (int) $dfParts[3] : ($diskTotalKb - $diskUsedKb);
        $diskDevice = $dfParts[0] ?? '/dev/root';

        $totalGb = round($diskTotalKb / (1024 * 1024), 2);
        $usedGb = round($diskUsedKb / (1024 * 1024), 2);
        $freeGb = round($diskFreeKb / (1024 * 1024), 2);
        $diskPercent = round(($diskUsedKb / $diskTotalKb) * 100, 1);

        // 5. Disk I/O (via vmstat delta in cache)
        $vm = [];
        foreach ($sections['VMSTAT'] ?? [] as $line) {
            if (preg_match('/^(pgpgin|pgpgout)\s+(\d+)/', trim($line), $m)) {
                $vm[$m[1]] = (float) $m[2];
            }
        }
        $readKbS = 0.0;
        $writeKbS = 0.0;
        $now = microtime(true);
        if (isset($vm['pgpgin'], $vm['pgpgout'])) {
            $cacheKey = 'ssh_vmstat_'.$server->id;
            $prev = Cache::get($cacheKey);
            Cache::put($cacheKey, ['t' => $now, 'in' => $vm['pgpgin'], 'out' => $vm['pgpgout']], 120);
            if ($prev && ($now - $prev['t']) > 0.1) {
                $dt = $now - $prev['t'];
                $readKbS = round(max(0, $vm['pgpgin'] - $prev['in']) / $dt, 1);
                $writeKbS = round(max(0, $vm['pgpgout'] - $prev['out']) / $dt, 1);
            }
        }

        // 6. Temperature
        $tempRaw = trim(implode('', $sections['TEMP'] ?? []));
        $tempC = null;
        if (is_numeric($tempRaw)) {
            $val = (float) $tempRaw;
            $tempC = round($val > 1000 ? $val / 1000 : $val, 1);
        }

        return [
            'cpu' => [
                'usage_percent' => max(0, min(100, $cpuUsage)),
                'cores' => $cores,
                'load_avg' => $loadAvg,
            ],
            'ram' => [
                'total_mb' => $totalMb,
                'used_mb' => $usedMb,
                'free_mb' => $freeMb,
                'usage_percent' => max(0, min(100, $ramPercent)),
                'swap_total_mb' => round($swapTotalKb / 1024, 1),
                'swap_used_mb' => round(max(0, $swapTotalKb - $swapFreeKb) / 1024, 1),
            ],
            'disk' => [
                'total_gb' => $totalGb,
                'used_gb' => $usedGb,
                'free_gb' => $freeGb,
                'usage_percent' => max(0, min(100, $diskPercent)),
                'partitions' => [
                    [
                        'mount' => '/',
                        'device' => $diskDevice,
                        'label' => 'Root Filesystem',
                        'total_gb' => $totalGb,
                        'used_gb' => $usedGb,
                        'free_gb' => $freeGb,
                        'usage_percent' => max(0, min(100, $diskPercent)),
                    ],
                ],
            ],
            'disk_io' => [
                'read_kb_s' => $readKbS,
                'write_kb_s' => $writeKbS,
                'io_in_progress' => 0,
                'load_status' => 'normal',
                'load_percent' => min(100, round((($readKbS + $writeKbS) / 30000) * 100, 1)),
            ],
            'temperature' => [
                'celsius' => $tempC,
                'status' => $tempC !== null && $tempC >= 75 ? 'critical' : 'normal',
                'is_mocked' => false,
            ],
            'system' => [
                'hostname' => $hostname,
                'os' => $os,
                'kernel' => $kernel,
                'architecture' => $arch,
                'model' => $model,
                'uptime_seconds' => $uptimeSeconds,
                'uptime_formatted' => "{$days}d {$hours}h {$minutes}m",
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{total: int, idle: int}|null
     */
    protected function parseCpuStatLine(string $line): ?array
    {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) < 5 || $parts[0] !== 'cpu') {
            return null;
        }
        $user = (int) $parts[1];
        $nice = (int) $parts[2];
        $system = (int) $parts[3];
        $idle = (int) $parts[4];
        $iowait = (int) ($parts[5] ?? 0);
        $irq = (int) ($parts[6] ?? 0);
        $softirq = (int) ($parts[7] ?? 0);

        return [
            'total' => $user + $nice + $system + $idle + $iowait + $irq + $softirq,
            'idle' => $idle + $iowait,
        ];
    }
}

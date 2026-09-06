<?php

namespace App\Services;

use App\Models\SystemMetricLog;
use Illuminate\Support\Facades\Cache;

class StbMonitorService
{
    /**
     * Get complete STB metrics snapshot.
     *
     * @return array<string, mixed>
     */
    public function getSystemMetrics(): array
    {
        $cpuInfo = $this->getCpuMetrics();
        $ramInfo = $this->getRamMetrics();
        $diskInfo = $this->getDiskMetrics();
        $diskIoInfo = $this->getDiskIoMetrics();
        $tempInfo = $this->getTemperature();
        $systemInfo = $this->getSystemInfo();

        $metrics = [
            'cpu' => $cpuInfo,
            'ram' => $ramInfo,
            'disk' => $diskInfo,
            'disk_io' => $diskIoInfo,
            'temperature' => $tempInfo,
            'system' => $systemInfo,
            'timestamp' => now()->toIso8601String(),
        ];

        // Periodically cache recent points for live sparkline rendering
        $this->recordMetricSnapshot($cpuInfo['usage_percent'], $ramInfo['usage_percent'], $ramInfo['used_mb'], $ramInfo['total_mb'], $diskInfo['usage_percent'], $diskIoInfo['read_kb_s'], $diskIoInfo['write_kb_s'], $tempInfo['celsius']);

        return $metrics;
    }

    /**
     * Get CPU usage and core count.
     *
     * @return array{usage_percent: float, cores: int, load_avg: array<int, float>}
     */
    public function getCpuMetrics(): array
    {
        $cores = 4;
        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            $cores = max(1, substr_count($cpuinfo, 'processor'));
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            $ncpu = shell_exec('sysctl -n hw.ncpu 2>/dev/null');
            if ($ncpu) {
                $cores = max(1, (int) trim($ncpu));
            }
        }

        $loadAvg = sys_getloadavg() ?: [0.15, 0.20, 0.18];

        $usagePercent = 0.0;
        if (is_readable('/proc/stat')) {
            $stat1 = file_get_contents('/proc/stat');
            usleep(80000); // 80ms sample
            $stat2 = file_get_contents('/proc/stat');

            $info1 = $this->parseProcStat($stat1);
            $info2 = $this->parseProcStat($stat2);

            if ($info1 && $info2) {
                $totalDelta = $info2['total'] - $info1['total'];
                $idleDelta = $info2['idle'] - $info1['idle'];
                if ($totalDelta > 0) {
                    $usagePercent = round((1 - ($idleDelta / $totalDelta)) * 100, 1);
                }
            }
        } else {
            // Approximation for local dev (macOS / non-Linux)
            $usagePercent = round(min(100, max(5, ($loadAvg[0] / $cores) * 85 + rand(-2, 3))), 1);
        }

        return [
            'usage_percent' => max(0, min(100, $usagePercent)),
            'cores' => $cores,
            'load_avg' => [
                round($loadAvg[0] ?? 0, 2),
                round($loadAvg[1] ?? 0, 2),
                round($loadAvg[2] ?? 0, 2),
            ],
        ];
    }

    /**
     * Parse /proc/stat line
     *
     * @return array{total: int, idle: int}|null
     */
    private function parseProcStat(string $content): ?array
    {
        $lines = explode("\n", $content);
        if (empty($lines[0])) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($lines[0]));
        if (count($parts) < 5 || $parts[0] !== 'cpu') {
            return null;
        }

        $user = (int) $parts[1];
        $nice = (int) $parts[2];
        $system = (int) $parts[3];
        $idle = (int) $parts[4];
        $iowait = isset($parts[5]) ? (int) $parts[5] : 0;
        $irq = isset($parts[6]) ? (int) $parts[6] : 0;
        $softirq = isset($parts[7]) ? (int) $parts[7] : 0;

        $total = $user + $nice + $system + $idle + $iowait + $irq + $softirq;

        return ['total' => $total, 'idle' => $idle + $iowait];
    }

    /**
     * Get RAM & Swap metrics.
     *
     * @return array{total_mb: float, used_mb: float, free_mb: float, usage_percent: float, swap_total_mb: float, swap_used_mb: float}
     */
    public function getRamMetrics(): array
    {
        if (is_readable('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            $data = [];
            foreach (explode("\n", $meminfo) as $line) {
                if (preg_match('/^(\w+):\s+(\d+)\s+kB/', $line, $matches)) {
                    $data[$matches[1]] = (int) $matches[2];
                }
            }

            $totalKb = $data['MemTotal'] ?? 1024 * 1024;
            $availKb = $data['MemAvailable'] ?? ($data['MemFree'] ?? 512 * 1024);
            $usedKb = max(0, $totalKb - $availKb);

            $swapTotalKb = $data['SwapTotal'] ?? 0;
            $swapFreeKb = $data['SwapFree'] ?? 0;
            $swapUsedKb = max(0, $swapTotalKb - $swapFreeKb);

            $totalMb = round($totalKb / 1024, 1);
            $usedMb = round($usedKb / 1024, 1);
            $freeMb = round($availKb / 1024, 1);
            $usagePercent = round(($usedKb / $totalKb) * 100, 1);

            return [
                'total_mb' => $totalMb,
                'used_mb' => $usedMb,
                'free_mb' => $freeMb,
                'usage_percent' => min(100, max(0, $usagePercent)),
                'swap_total_mb' => round($swapTotalKb / 1024, 1),
                'swap_used_mb' => round($swapUsedKb / 1024, 1),
            ];
        }

        // Local development fallback (e.g. macOS)
        $totalMb = 2048.0; // Typical 2GB STB RAM representation
        $usedMb = 768.5;
        $freeMb = $totalMb - $usedMb;
        $usagePercent = round(($usedMb / $totalMb) * 100, 1);

        return [
            'total_mb' => $totalMb,
            'used_mb' => $usedMb,
            'free_mb' => $freeMb,
            'usage_percent' => $usagePercent,
            'swap_total_mb' => 1024.0,
            'swap_used_mb' => 64.0,
        ];
    }

    /**
     * Get Disk storage metrics.
     *
     * @return array{total_gb: float, used_gb: float, free_gb: float, usage_percent: float, partitions: array<int, array<string, mixed>>}
     */
    public function getDiskMetrics(): array
    {
        $rootPath = '/';
        $totalBytes = @disk_total_space($rootPath) ?: (16 * 1024 * 1024 * 1024); // 16GB default STB eMMC
        $freeBytes = @disk_free_space($rootPath) ?: (9 * 1024 * 1024 * 1024);
        $usedBytes = max(0, $totalBytes - $freeBytes);

        $totalGb = round($totalBytes / (1024 * 1024 * 1024), 2);
        $usedGb = round($usedBytes / (1024 * 1024 * 1024), 2);
        $freeGb = round($freeBytes / (1024 * 1024 * 1024), 2);
        $usagePercent = round(($usedBytes / $totalBytes) * 100, 1);

        $partitions = [
            [
                'mount' => '/',
                'device' => '/dev/mmcblk0p2',
                'label' => 'Root Storage (eMMC/SD)',
                'total_gb' => $totalGb,
                'used_gb' => $usedGb,
                'free_gb' => $freeGb,
                'usage_percent' => $usagePercent,
            ],
        ];

        // Check additional mounts on STB (like /mnt/usb, /media/sda1)
        if (is_readable('/proc/mounts')) {
            $mounts = file_get_contents('/proc/mounts');
            foreach (explode("\n", $mounts) as $line) {
                if (preg_match('#^(/dev/sd[a-z][0-9]|/dev/nvme[0-9]n[0-9]p[0-9])\s+([/\w\-]+)\s+(ext4|btrfs|vfat|ntfs)#', $line, $m)) {
                    $mp = $m[2];
                    if ($mp !== '/' && is_dir($mp)) {
                        $pTotal = @disk_total_space($mp);
                        $pFree = @disk_free_space($mp);
                        if ($pTotal && $pFree) {
                            $pUsed = $pTotal - $pFree;
                            $partitions[] = [
                                'mount' => $mp,
                                'device' => $m[1],
                                'label' => 'External Drive ('.basename($mp).')',
                                'total_gb' => round($pTotal / (1024 * 1024 * 1024), 2),
                                'used_gb' => round($pUsed / (1024 * 1024 * 1024), 2),
                                'free_gb' => round($pFree / (1024 * 1024 * 1024), 2),
                                'usage_percent' => round(($pUsed / $pTotal) * 100, 1),
                            ];
                        }
                    }
                }
            }
        }

        return [
            'total_gb' => $totalGb,
            'used_gb' => $usedGb,
            'free_gb' => $freeGb,
            'usage_percent' => min(100, max(0, $usagePercent)),
            'partitions' => $partitions,
        ];
    }

    /**
     * Get Disk Load & I/O throughput (Read/Write KB/s and Load Level).
     *
     * @return array{read_kb_s: float, write_kb_s: float, io_in_progress: int, load_status: string, load_percent: float}
     */
    public function getDiskIoMetrics(): array
    {
        $cacheKey = 'stb_disk_stats_prev';
        $currentTime = microtime(true);

        $currentStats = null;
        if (is_readable('/proc/diskstats')) {
            $lines = explode("\n", file_get_contents('/proc/diskstats'));
            $sectorsRead = 0;
            $sectorsWritten = 0;
            $ioInProgress = 0;

            foreach ($lines as $line) {
                $p = preg_split('/\s+/', trim($line));
                if (count($p) >= 14) {
                    $dev = $p[2];
                    // Focus on root block device mmcblk0 or sda
                    if (str_starts_with($dev, 'mmcblk0') && ! str_contains($dev, 'p') || str_starts_with($dev, 'sda')) {
                        $sectorsRead += (int) $p[5];
                        $sectorsWritten += (int) $p[9];
                        $ioInProgress += (int) $p[11];
                    }
                }
            }

            $currentStats = [
                'time' => $currentTime,
                'read_sectors' => $sectorsRead,
                'write_sectors' => $sectorsWritten,
                'io_in_progress' => $ioInProgress,
            ];
        }

        if ($currentStats) {
            $prevStats = Cache::get($cacheKey);
            Cache::put($cacheKey, $currentStats, 30);

            if ($prevStats && ($currentTime - $prevStats['time']) > 0.05) {
                $timeDelta = $currentTime - $prevStats['time'];
                $readSectorsDelta = max(0, $currentStats['read_sectors'] - $prevStats['read_sectors']);
                $writeSectorsDelta = max(0, $currentStats['write_sectors'] - $prevStats['write_sectors']);

                // 1 sector = 512 bytes = 0.5 KB
                $readKbS = round(($readSectorsDelta * 0.5) / $timeDelta, 1);
                $writeKbS = round(($writeSectorsDelta * 0.5) / $timeDelta, 1);
                $ioProg = $currentStats['io_in_progress'];
                $loadPercent = min(100, round((($readKbS + $writeKbS) / 30000) * 100, 1));

                $status = 'normal';
                if ($loadPercent > 80 || $ioProg > 10) {
                    $status = 'critical';
                } elseif ($loadPercent > 45 || $ioProg > 4) {
                    $status = 'warning';
                }

                return [
                    'read_kb_s' => $readKbS,
                    'write_kb_s' => $writeKbS,
                    'io_in_progress' => $ioProg,
                    'load_status' => $status,
                    'load_percent' => $loadPercent,
                ];
            }
        }

        // Local development simulated metrics
        $simulatedRead = round(rand(20, 280) / 10, 1);
        $simulatedWrite = round(rand(40, 450) / 10, 1);

        return [
            'read_kb_s' => $simulatedRead,
            'write_kb_s' => $simulatedWrite,
            'io_in_progress' => rand(0, 2),
            'load_status' => 'normal',
            'load_percent' => round(($simulatedRead + $simulatedWrite) / 10, 1),
        ];
    }

    /**
     * Read STB SoC Temperature (°C).
     *
     * @return array{celsius: float, status: string, is_mocked: bool}
     */
    public function getTemperature(): array
    {
        $tempPaths = [
            '/sys/class/thermal/thermal_zone0/temp',
            '/sys/class/thermal/thermal_zone1/temp',
            '/etc/armbianmonitor/datasrc/temp',
        ];

        foreach ($tempPaths as $path) {
            if (is_readable($path)) {
                $raw = trim((string) file_get_contents($path));
                if (is_numeric($raw)) {
                    $val = (float) $raw;
                    if ($val > 1000) {
                        $val = $val / 1000;
                    }
                    $val = round($val, 1);

                    return [
                        'celsius' => $val,
                        'status' => $val >= 75 ? 'critical' : ($val >= 62 ? 'warning' : 'normal'),
                        'is_mocked' => false,
                    ];
                }
            }
        }

        // STB typical temperature simulation when on macOS/dev host
        $simTemp = 51.5;

        return [
            'celsius' => $simTemp,
            'status' => 'normal',
            'is_mocked' => true,
        ];
    }

    /**
     * Get Host, Kernel, Architecture, and Uptime.
     *
     * @return array<string, mixed>
     */
    public function getSystemInfo(): array
    {
        $hostname = gethostname() ?: 'stb-server';
        $kernel = php_uname('r');
        $arch = php_uname('m');

        $os = PHP_OS;
        if (is_readable('/etc/os-release')) {
            $osRelease = file_get_contents('/etc/os-release');
            if (preg_match('/PRETTY_NAME="([^"]+)"/', $osRelease, $m)) {
                $os = $m[1];
            }
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            $os = 'macOS '.php_uname('r').' (Development Host)';
        }

        $model = 'ARM STB (Amlogic/Allwinner/Rockchip)';
        if (is_readable('/proc/device-tree/model')) {
            $model = trim(file_get_contents('/proc/device-tree/model'));
        } elseif (is_readable('/sys/firmware/devicetree/base/model')) {
            $model = trim(file_get_contents('/sys/firmware/devicetree/base/model'));
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            $model = 'Apple Silicon / Mac Host';
        }

        $uptimeSeconds = 0;
        if (is_readable('/proc/uptime')) {
            $up = explode(' ', file_get_contents('/proc/uptime'));
            $uptimeSeconds = (int) ($up[0] ?? 0);
        } else {
            $uptimeSeconds = 345600; // 4 days default
        }

        $days = floor($uptimeSeconds / 86400);
        $hours = floor(($uptimeSeconds % 86400) / 3600);
        $minutes = floor(($uptimeSeconds % 3600) / 60);

        $uptimeFormatted = "{$days}d {$hours}h {$minutes}m";

        return [
            'hostname' => $hostname,
            'os' => $os,
            'kernel' => $kernel,
            'architecture' => $arch,
            'model' => $model,
            'uptime_seconds' => $uptimeSeconds,
            'uptime_formatted' => $uptimeFormatted,
        ];
    }

    /**
     * Record periodic snapshot for chart history.
     */
    private function recordMetricSnapshot(float $cpu, float $ramPercent, float $ramUsedMb, float $ramTotalMb, float $disk, float $readKb, float $writeKb, ?float $temp): void
    {
        try {
            $last = SystemMetricLog::latest('id')->first();
            if (! $last || now()->diffInSeconds($last->recorded_at) >= 15) {
                SystemMetricLog::create([
                    'cpu_usage_percent' => $cpu,
                    'ram_usage_percent' => $ramPercent,
                    'ram_used_mb' => $ramUsedMb,
                    'ram_total_mb' => $ramTotalMb,
                    'disk_usage_percent' => $disk,
                    'disk_read_kb_s' => $readKb,
                    'disk_write_kb_s' => $writeKb,
                    'temperature_celsius' => $temp,
                    'recorded_at' => now(),
                ]);

                // Keep only last 100 records to prevent bloating SQLite on STB
                $count = SystemMetricLog::count();
                if ($count > 120) {
                    SystemMetricLog::orderBy('id', 'asc')->limit($count - 100)->delete();
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore if db lock or table is migrating
        }
    }
}

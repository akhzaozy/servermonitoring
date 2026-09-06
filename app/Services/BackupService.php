<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Log;

class BackupService
{
    protected string $scriptPath;

    protected string $backupDir;

    protected string $logFile;

    public function __construct()
    {
        $this->scriptPath = config('monitoring.backup_script_path', env('BACKUP_SCRIPT_PATH', '/www/backup/backup.php'));
        $this->backupDir = config('monitoring.backup_dir', env('BACKUP_DIR', '/www/backup/hosting_backup'));
        $this->logFile = $this->backupDir.'/backup.log';
    }

    /**
     * Check if backup script is currently executing.
     */
    public function isRunning(): bool
    {
        // Check running processes for backup.php
        $output = [];
        $returnVar = 0;
        @exec('pgrep -f "backup.php"', $output, $returnVar);

        if ($returnVar === 0 && ! empty($output)) {
            return true;
        }

        // Also check ps aux fallback
        $psOutput = @shell_exec('ps aux | grep "[b]ackup.php"');
        if (! empty($psOutput) && trim($psOutput) !== '') {
            return true;
        }

        return false;
    }

    /**
     * Get complete backup status, last execution, and files summary.
     *
     * @return array<string, mixed>
     */
    public function getBackupStatus(): array
    {
        $isRunning = $this->isRunning();
        $scriptExists = file_exists($this->scriptPath);
        $backupDirExists = is_dir($this->backupDir);

        // If directory doesn't exist on server (e.g. during dev/testing on Mac), provide simulated realistic fallback
        if (! $backupDirExists && ! $scriptExists) {
            return $this->getMockStatus($isRunning);
        }

        $latestFiles = $this->getLatestFiles();
        $lastRunInfo = $this->parseLogSummary();

        // Calculate total backup storage size
        $totalSizeBytes = $this->calculateDirectorySize($this->backupDir);

        return [
            'is_running' => $isRunning,
            'script_exists' => $scriptExists,
            'script_path' => $this->scriptPath,
            'backup_dir' => $this->backupDir,
            'backup_dir_exists' => $backupDirExists,
            'last_run' => $lastRunInfo,
            'total_size_bytes' => $totalSizeBytes,
            'total_size_human' => $this->formatBytes($totalSizeBytes),
            'latest_files' => $latestFiles,
            'recent_logs' => $this->getRecentLogs(25),
        ];
    }

    /**
     * Trigger manual backup via CLI.
     *
     * @return array{success: bool, message: string, output?: string}
     */
    public function runManualBackup(): array
    {
        if ($this->isRunning()) {
            return [
                'success' => false,
                'message' => 'Proses backup saat ini sedang berjalan. Harap tunggu hingga selesai.',
            ];
        }

        if (! file_exists($this->scriptPath)) {
            // In dev environment where /www/backup/backup.php is absent, simulate a successful background run
            return $this->simulateManualBackup();
        }

        try {
            // Execute in background using nohup to prevent web worker blocking
            $cmd = sprintf(
                'nohup php %s > /dev/null 2>&1 &',
                escapeshellarg($this->scriptPath)
            );

            @shell_exec($cmd);

            return [
                'success' => true,
                'message' => 'Proses backup manual berhasil dijalankan di latar belakang.',
            ];
        } catch (\Throwable $e) {
            Log::error('Backup trigger failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal menjalankan backup: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get recent log lines from backup.log.
     *
     * @return array<int, string>
     */
    public function getRecentLogs(int $lines = 40): array
    {
        if (! file_exists($this->logFile)) {
            return [
                '['.date('Y-m-d H:i:s').'] File log '.$this->logFile.' belum ditemukan atau belum ada proses backup yang dicatat.',
            ];
        }

        $cmd = sprintf('tail -n %d %s', escapeshellarg((string) $lines), escapeshellarg($this->logFile));
        $output = @shell_exec($cmd);

        if (! $output) {
            return [];
        }

        $logLines = explode(PHP_EOL, trim($output));

        return array_values(array_filter($logLines, fn ($l) => trim($l) !== ''));
    }

    /**
     * Scan and categorize latest backup files.
     *
     * @return array<string, mixed>
     */
    protected function getLatestFiles(): array
    {
        $result = [
            'incremental_snapshot' => null,
            'files_archive' => null,
            'mariadb_databases' => [],
            'postgres_databases' => [],
        ];

        // 1. Incremental snapshots in files_incremental
        $incDir = $this->backupDir.'/files_incremental';
        if (is_dir($incDir)) {
            $snapshots = [];
            foreach (scandir($incDir) as $item) {
                if (preg_match('/^snapshot_\d{8}_\d{6}$/', $item)) {
                    $fullPath = $incDir.'/'.$item;
                    $mtime = filemtime($fullPath);
                    $snapshots[] = [
                        'name' => $item,
                        'path' => $fullPath,
                        'modified_at' => date('Y-m-d H:i:s', $mtime),
                        'timestamp' => $mtime,
                        'size_human' => $this->formatBytes($this->calculateDirectorySize($fullPath)),
                    ];
                }
            }

            if (! empty($snapshots)) {
                usort($snapshots, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);
                $result['incremental_snapshot'] = $snapshots[0];
            }
        }

        // 2. Full tar.gz in files
        $filesDir = $this->backupDir.'/files';
        if (is_dir($filesDir)) {
            $archives = [];
            foreach (scandir($filesDir) as $item) {
                if (str_ends_with($item, '.tar.gz')) {
                    $fullPath = $filesDir.'/'.$item;
                    $mtime = filemtime($fullPath);
                    $size = filesize($fullPath);
                    $archives[] = [
                        'name' => $item,
                        'path' => $fullPath,
                        'modified_at' => date('Y-m-d H:i:s', $mtime),
                        'timestamp' => $mtime,
                        'size_bytes' => $size,
                        'size_human' => $this->formatBytes($size),
                    ];
                }
            }

            if (! empty($archives)) {
                usort($archives, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);
                $result['files_archive'] = $archives[0];
            }
        }

        // 3. MariaDB Databases in database
        $dbDir = $this->backupDir.'/database';
        if (is_dir($dbDir)) {
            $dbFiles = [];
            foreach (scandir($dbDir) as $item) {
                if (str_ends_with($item, '.sql.gz')) {
                    $fullPath = $dbDir.'/'.$item;
                    $size = filesize($fullPath);
                    $mtime = filemtime($fullPath);
                    // Extract DB name from filename: dbname_YYYYmmdd_His.sql.gz
                    $dbName = preg_replace('/_\d{8}_\d{6}\.sql\.gz$/', '', $item);

                    $dbFiles[] = [
                        'database' => $dbName,
                        'filename' => $item,
                        'size_bytes' => $size,
                        'size_human' => $this->formatBytes($size),
                        'created_at' => date('Y-m-d H:i:s', $mtime),
                        'timestamp' => $mtime,
                    ];
                }
            }
            usort($dbFiles, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);
            $result['mariadb_databases'] = array_slice($dbFiles, 0, 15);
        }

        // 4. Postgres Databases in database_pg
        $pgDir = $this->backupDir.'/database_pg';
        if (is_dir($pgDir)) {
            $pgFiles = [];
            foreach (scandir($pgDir) as $item) {
                if (str_ends_with($item, '.sql.gz')) {
                    $fullPath = $pgDir.'/'.$item;
                    $size = filesize($fullPath);
                    $mtime = filemtime($fullPath);
                    $dbName = preg_replace('/_\d{8}_\d{6}\.sql\.gz$/', '', $item);

                    $pgFiles[] = [
                        'database' => $dbName,
                        'filename' => $item,
                        'size_bytes' => $size,
                        'size_human' => $this->formatBytes($size),
                        'created_at' => date('Y-m-d H:i:s', $mtime),
                        'timestamp' => $mtime,
                    ];
                }
            }
            usort($pgFiles, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);
            $result['postgres_databases'] = array_slice($pgFiles, 0, 15);
        }

        return $result;
    }

    /**
     * Parse last run information from backup.log.
     *
     * @return array<string, mixed>
     */
    protected function parseLogSummary(): array
    {
        if (! file_exists($this->logFile)) {
            return [
                'timestamp' => null,
                'human' => 'Belum pernah dibackup',
                'status' => 'never',
                'duration_seconds' => 0,
                'total_time_text' => '-',
            ];
        }

        $logs = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($logs)) {
            return [
                'timestamp' => null,
                'human' => 'Log kosong',
                'status' => 'unknown',
                'duration_seconds' => 0,
                'total_time_text' => '-',
            ];
        }

        $lastTimestamp = null;
        $status = 'success';
        $totalTimeText = '-';
        $durationSeconds = 0.0;

        // Traverse backwards to find the last complete or start message
        for ($i = count($logs) - 1; $i >= 0; $i--) {
            $line = $logs[$i];

            // Extract date: [2026-09-06 14:00:15]
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s*(.*)$/', $line, $matches)) {
                $lineDate = $matches[1];
                $message = $matches[2];

                if (! $lastTimestamp) {
                    $lastTimestamp = $lineDate;
                }

                if (str_contains($message, 'Proses backup selesai')) {
                    $status = 'success';
                    if (preg_match('/total waktu:\s*([0-9.]+)\s*detik/', $message, $tm)) {
                        $durationSeconds = (float) $tm[1];
                        $totalTimeText = $durationSeconds.'s';
                    }
                    break;
                }

                if (str_contains($message, 'GAGAL backup')) {
                    $status = 'failed';
                    break;
                }

                if (str_contains($message, 'Mulai proses backup')) {
                    if ($this->isRunning()) {
                        $status = 'running';
                    }
                    break;
                }
            }
        }

        $human = 'Tidak diketahui';
        if ($lastTimestamp) {
            try {
                $dt = new DateTime($lastTimestamp);
                $diff = time() - $dt->getTimestamp();
                if ($diff < 60) {
                    $human = 'Baru saja';
                } elseif ($diff < 3600) {
                    $human = floor($diff / 60).' menit lalu';
                } elseif ($diff < 86400) {
                    $human = floor($diff / 3600).' jam lalu';
                } else {
                    $human = floor($diff / 86400).' hari lalu';
                }
            } catch (\Throwable $e) {
                $human = $lastTimestamp;
            }
        }

        return [
            'timestamp' => $lastTimestamp,
            'human' => $human,
            'status' => $status,
            'duration_seconds' => $durationSeconds,
            'total_time_text' => $totalTimeText,
        ];
    }

    /**
     * Calculate directory size.
     */
    protected function calculateDirectorySize(string $path): int
    {
        if (! is_dir($path)) {
            return is_file($path) ? (int) filesize($path) : 0;
        }

        $size = 0;
        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Throwable $e) {
            // Fallback to du command if available
            $cmd = 'du -sb '.escapeshellarg($path).' 2>/dev/null';
            $out = @shell_exec($cmd);
            if ($out && preg_match('/^(\d+)/', trim($out), $m)) {
                return (int) $m[1];
            }
        }

        return $size;
    }

    /**
     * Format bytes to human readable format.
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $b = (float) $bytes;
        while ($b >= 1024 && $i < count($units) - 1) {
            $b /= 1024;
            $i++;
        }

        return round($b, 2).' '.$units[$i];
    }

    /**
     * Realistic simulated status for local dev / mock environments.
     *
     * @return array<string, mixed>
     */
    protected function getMockStatus(bool $isRunning): array
    {
        $now = time();
        $dateStr = date('Ymd_His', $now - 3600);
        $readableDate = date('Y-m-d H:i:s', $now - 3600);

        return [
            'is_running' => $isRunning,
            'script_exists' => false,
            'script_path' => $this->scriptPath,
            'backup_dir' => $this->backupDir,
            'backup_dir_exists' => false,
            'is_mock' => true,
            'last_run' => [
                'timestamp' => $readableDate,
                'human' => '1 jam lalu',
                'status' => 'success',
                'duration_seconds' => 48.6,
                'total_time_text' => '48.6s',
            ],
            'total_size_bytes' => 1740636160, // ~1.62 GB
            'total_size_human' => '1.62 GB',
            'latest_files' => [
                'incremental_snapshot' => [
                    'name' => 'snapshot_'.$dateStr,
                    'path' => $this->backupDir.'/files_incremental/snapshot_'.$dateStr,
                    'modified_at' => $readableDate,
                    'timestamp' => $now - 3600,
                    'size_human' => '1.24 GB',
                ],
                'files_archive' => [
                    'name' => 'hosting_'.$dateStr.'.tar.gz',
                    'path' => $this->backupDir.'/files/hosting_'.$dateStr.'.tar.gz',
                    'modified_at' => $readableDate,
                    'timestamp' => $now - 3600,
                    'size_bytes' => 1342177280,
                    'size_human' => '1.25 GB',
                ],
                'mariadb_databases' => [
                    [
                        'database' => 'db_daffa',
                        'filename' => 'db_daffa_'.$dateStr.'.sql.gz',
                        'size_bytes' => 18874368,
                        'size_human' => '18.00 MB',
                        'created_at' => $readableDate,
                        'timestamp' => $now - 3600,
                    ],
                    [
                        'database' => 'db_ozy',
                        'filename' => 'db_ozy_'.$dateStr.'.sql.gz',
                        'size_bytes' => 25165824,
                        'size_human' => '24.00 MB',
                        'created_at' => $readableDate,
                        'timestamp' => $now - 3600,
                    ],
                    [
                        'database' => 'db_monitoring',
                        'filename' => 'db_monitoring_'.$dateStr.'.sql.gz',
                        'size_bytes' => 8388608,
                        'size_human' => '8.00 MB',
                        'created_at' => $readableDate,
                        'timestamp' => $now - 3600,
                    ],
                ],
                'postgres_databases' => [
                    [
                        'database' => 'postgres_stb_main',
                        'filename' => 'postgres_stb_main_'.$dateStr.'.sql.gz',
                        'size_bytes' => 45088768,
                        'size_human' => '43.00 MB',
                        'created_at' => $readableDate,
                        'timestamp' => $now - 3600,
                    ],
                ],
            ],
            'recent_logs' => [
                '['.$readableDate.'] === Mulai proses backup ===',
                '['.$readableDate.'] [1/3] Backup folder hosting (incremental) dimulai — rsync link-dest snapshot aktif',
                '['.$readableDate.'] [1/3] Backup folder hosting SELESAI: snapshot_'.$dateStr.' (22.4s)',
                '['.$readableDate.'] [2/3] (1/3) Backup database MariaDB \'db_daffa\' dimulai (~18.00 MB):',
                '['.$readableDate.'] [2/3] Database \'db_daffa\' SELESAI (18.00 MB, 5.2s)',
                '['.$readableDate.'] [2/3] (2/3) Backup database MariaDB \'db_ozy\' dimulai (~24.00 MB):',
                '['.$readableDate.'] [2/3] Database \'db_ozy\' SELESAI (24.00 MB, 7.1s)',
                '['.$readableDate.'] [3/3] (1/1) Backup database PostgreSQL \'postgres_stb_main\' dimulai (~43.00 MB):',
                '['.$readableDate.'] [3/3] Database PostgreSQL \'postgres_stb_main\' SELESAI (43.00 MB, 9.8s)',
                '['.$readableDate.'] Hapus snapshot lama: retention 7 hari diproses',
                '['.$readableDate.'] === Proses backup selesai (total waktu: 48.6 detik) ===',
            ],
        ];
    }

    /**
     * Simulate manual backup start when testing locally.
     *
     * @return array{success: bool, message: string}
     */
    protected function simulateManualBackup(): array
    {
        return [
            'success' => true,
            'message' => 'Simulasi backup STB dijalankan (Mode Pengembangan). Pada server STB, script /www/backup/backup.php akan dieksekusi via background CLI.',
        ];
    }
}

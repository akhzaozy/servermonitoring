<?php

namespace App\Services;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Menjalankan satu perintah shell (stateless) dan mengembalikan output + direktori kerja baru.
 * Direktori kerja dilacak oleh client sehingga `cd` tetap bekerja antar perintah.
 */
class TerminalService
{
    private const MARKER = '__STB_END__';

    /**
     * @return array{output: string, cwd: string, exit_code: int, user: string, host: string, timed_out: bool}
     */
    public function run(string $command, ?string $cwd = null): array
    {
        $timeout = max(1, (int) config('monitor.terminal.timeout', 30));
        $script = $this->buildScript($command, $cwd);
        $process = $this->makeProcess($script);
        $process->setTimeout($timeout);
        $process->setInput('');

        $timedOut = false;
        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            $timedOut = true;
        }

        $raw = $process->getOutput().$process->getErrorOutput();
        $output = $raw;
        $state = ['exit' => $timedOut ? 124 : 0, 'cwd' => $cwd ?? '', 'user' => '', 'host' => ''];

        $pattern = '/\n?'.self::MARKER.'(\d+)\|(.*?)\|(.*?)\|(.*?)\s*$/s';
        if (preg_match($pattern, $raw, $m)) {
            $output = substr($raw, 0, strlen($raw) - strlen($m[0]));
            $state = ['exit' => (int) $m[1], 'cwd' => $m[2], 'user' => $m[3], 'host' => $m[4]];
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

    protected function buildScript(string $command, ?string $cwd): string
    {
        $cd = $cwd !== null && $cwd !== '' ? 'cd '.escapeshellarg($cwd).' 2>/dev/null || cd ~ 2>/dev/null' : 'cd ~ 2>/dev/null';

        return $cd.'; eval '.escapeshellarg($command).'; ec=$?; '
            .'printf \'\n'.self::MARKER.'%s|%s|%s|%s\n\' "$ec" "$PWD" "$(id -un)" "$(hostname)"';
    }

    protected function makeProcess(string $script): Process
    {
        $ssh = config('monitor.terminal.ssh');

        if (! empty($ssh['host']) && ! empty($ssh['user'])) {
            $args = ['ssh', '-T', '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=accept-new', '-o', 'UserKnownHostsFile=/tmp/monitor_known_hosts', '-o', 'ConnectTimeout=8',
                '-p', (string) $ssh['port']];
            if (! empty($ssh['key'])) {
                $args[] = '-i';
                $args[] = $ssh['key'];
            }
            $args[] = $ssh['user'].'@'.$ssh['host'];
            $args[] = 'bash -c '.escapeshellarg($script);

            return new Process($args, null, $this->env());
        }

        $shell = is_executable('/bin/bash') || is_executable('/usr/bin/bash') ? 'bash' : 'sh';

        return new Process([$shell, '-c', $script], null, $this->env());
    }

    /**
     * @return array<string, string>
     */
    protected function env(): array
    {
        $home = getenv('HOME');
        if (! $home && function_exists('posix_getpwuid')) {
            $home = posix_getpwuid(posix_geteuid())['dir'] ?? null;
        }

        return [
            'HOME' => $home ?: '/tmp',
            'PATH' => getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
            'TERM' => 'xterm-256color',
            'LANG' => 'C.UTF-8',
        ];
    }
}

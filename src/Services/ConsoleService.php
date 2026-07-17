<?php

namespace Jeryseika\PdParser\Services;

class ConsoleService
{
    private array $blacklist;
    private int $timeout;
    private bool $isWindows;

    public function __construct()
    {
        $this->blacklist = config('pd-parser.blacklisted_commands', []);
        $this->timeout   = (int) config('pd-parser.terminal_timeout', 30);
        $this->isWindows = PHP_OS_FAMILY === 'Windows';
    }

    public function execute(string $command, string $cwd): array
    {
        $command = trim($command);

        if ($command === '') {
            return ['output' => '', 'exit_code' => 0, 'cwd' => $cwd];
        }

        foreach ($this->blacklist as $blocked) {
            if (stripos($command, $blocked) !== false) {
                return [
                    'output'    => "\033[31mBlocked command: {$blocked}\033[0m\n",
                    'exit_code' => 1,
                    'cwd'       => $cwd,
                ];
            }
        }

        if (preg_match('/^\s*cd(\s+(.*))?$/', $command, $m)) {
            return $this->handleCd(trim($m[2] ?? ''), $cwd);
        }

        if (in_array($command, ['clear', 'cls'])) {
            return ['output' => "\033[2J\033[H", 'exit_code' => 0, 'cwd' => $cwd];
        }

        return $this->run($command, $cwd);
    }

    private function run(string $command, string $cwd): array
    {
        if (!function_exists('proc_open')) {
            $oldDir = getcwd();
            @chdir($cwd);
            $output = @shell_exec($command . ' 2>&1') ?? '';
            @chdir($oldDir);
            return ['output' => $output, 'exit_code' => 0, 'cwd' => $cwd];
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = array_merge(
            array_filter($_ENV, fn($v) => is_string($v)),
            [
                'TERM'  => 'xterm-256color',
                'HOME'  => $_SERVER['HOME'] ?? ($_SERVER['USERPROFILE'] ?? '/root'),
                'PATH'  => $_SERVER['PATH'] ?? '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
                'SHELL' => $this->isWindows ? 'cmd.exe' : '/bin/bash',
            ]
        );

        $procCmd = $this->isWindows
            ? 'cmd.exe /c ' . $command
            : ['/bin/bash', '-c', $command];

        $process = proc_open(
            $procCmd,
            $descriptors,
            $pipes,
            $cwd,
            $env
        );

        if (!is_resource($process)) {
            return ['output' => "Failed to start process\n", 'exit_code' => 1, 'cwd' => $cwd];
        }

        fclose($pipes[0]);
        stream_set_timeout($pipes[1], $this->timeout);
        stream_set_timeout($pipes[2], $this->timeout);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitcode = proc_close($process);

        return [
            'output'    => $stdout . ($stderr ? "\033[31m" . $stderr . "\033[0m" : ''),
            'exit_code' => $exitcode,
            'cwd'       => $cwd,
        ];
    }

    private function handleCd(string $dir, string $cwd): array
    {
        if ($dir === '' || $dir === '~') {
            $dir = $_SERVER['HOME'] ?? ($_SERVER['USERPROFILE'] ?? $cwd);
        } elseif ($dir === '-') {
            $dir = session('pd_prev_cwd', $cwd);
        } elseif (!str_starts_with($dir, '/') && !preg_match('/^[A-Za-z]:/', $dir)) {
            $dir = rtrim($cwd, '/\\') . DIRECTORY_SEPARATOR . $dir;
        }

        $resolved = realpath($dir);

        if ($resolved === false || !is_dir($resolved)) {
            return ['output' => "cd: {$dir}: No such file or directory\n", 'exit_code' => 1, 'cwd' => $cwd];
        }

        session(['pd_prev_cwd' => $cwd]);

        return ['output' => '', 'exit_code' => 0, 'cwd' => $resolved];
    }

    public function autocomplete(string $partial, string $cwd): array
    {
        if (!str_contains($partial, ' ')) {
            return [];
        }

        $parts    = explode(' ', $partial);
        $lastPart = end($parts);

        if (!str_starts_with($lastPart, '/')) {
            $lastPart = rtrim($cwd, '/') . '/' . $lastPart;
        }

        $dir     = dirname($lastPart);
        $prefix  = basename($lastPart);
        $entries = @scandir($dir) ?: [];

        $matches = [];
        foreach ($entries as $e) {
            if ($e === '.' || $e === '..') continue;
            if ($prefix === '' || str_starts_with($e, $prefix)) {
                $matches[] = $dir . '/' . $e . (is_dir($dir . '/' . $e) ? '/' : '');
            }
        }

        return $matches;
    }
}

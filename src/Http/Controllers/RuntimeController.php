<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class RuntimeController extends Controller
{
    public function view(): \Illuminate\View\View
    {
        return view('pd::runtime.index', ['info' => $this->collect()]);
    }

    public function workers(): \Illuminate\Http\JsonResponse
    {
        $cmd    = PHP_OS_FAMILY === 'Windows' ? 'tasklist' : 'ps aux --sort=-%cpu';
        $output = shell_exec($cmd . ' 2>/dev/null') ?? 'N/A';
        return response()->json(['output' => $output]);
    }

    public function info(): \Illuminate\Http\Response
    {
        ob_start();
        phpinfo();
        $html = ob_get_clean();
        return response($html)->header('Content-Type', 'text/html');
    }

    public function tasks(): \Illuminate\View\View
    {
        $crontab = PHP_OS_FAMILY !== 'Windows'
            ? (shell_exec('crontab -l 2>/dev/null') ?? '')
            : '# Cron not available on Windows';
        return view('pd::runtime.tasks', compact('crontab'));
    }

    public function updateTasks(Request $request): \Illuminate\Http\JsonResponse
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return response()->json(['success' => false, 'error' => 'Not supported on Windows']);
        }

        $crontab = $request->input('crontab', '');
        $tmp     = tempnam(sys_get_temp_dir(), 'phx_cron_');
        file_put_contents($tmp, $crontab . "\n");
        exec('crontab ' . escapeshellarg($tmp), $out, $code);
        @unlink($tmp);

        return response()->json([
            'success' => $code === 0,
            'message' => $code === 0 ? 'Crontab updated.' : implode("\n", $out),
        ]);
    }

    private function collect(): array
    {
        $isLinux = PHP_OS_FAMILY === 'Linux';
        $disk    = $isLinux ? '/' : 'C:';

        $info = [
            'php_version'     => PHP_VERSION,
            'php_sapi'        => PHP_SAPI,
            'os_family'       => PHP_OS_FAMILY,
            'os_detail'       => php_uname(),
            'hostname'        => gethostname(),
            'server_ip'       => $_SERVER['SERVER_ADDR'] ?? @gethostbyname(gethostname()),
            'document_root'   => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'memory_limit'    => ini_get('memory_limit'),
            'memory_usage'    => memory_get_usage(true),
            'memory_peak'     => memory_get_peak_usage(true),
            'disk_total'      => disk_total_space($disk),
            'disk_free'       => disk_free_space($disk),
            'extensions'      => get_loaded_extensions(),
            'laravel_version' => app()->version(),
            'laravel_env'     => app()->environment(),
            'laravel_root'    => base_path(),
            'uptime'          => 'N/A',
            'load_avg'        => 'N/A',
            'ram_total'       => 0,
            'ram_available'   => 0,
            'cpu_count'       => 1,
        ];

        if ($isLinux) {
            if (($uptime = @file_get_contents('/proc/uptime'))) {
                $s = (float) explode(' ', $uptime)[0];
                $info['uptime'] = sprintf('%dd %dh %dm', $s/86400, ($s%86400)/3600, ($s%3600)/60);
            }
            if (($la = @file_get_contents('/proc/loadavg'))) {
                $p = explode(' ', $la);
                $info['load_avg'] = "{$p[0]}, {$p[1]}, {$p[2]}";
            }
            if (($mem = @file_get_contents('/proc/meminfo'))) {
                preg_match('/MemTotal:\s+(\d+)/', $mem, $mt);
                preg_match('/MemAvailable:\s+(\d+)/', $mem, $ma);
                $info['ram_total']     = isset($mt[1]) ? (int)$mt[1] * 1024 : 0;
                $info['ram_available'] = isset($ma[1]) ? (int)$ma[1] * 1024 : 0;
            }
            if (($cpu = @file_get_contents('/proc/cpuinfo'))) {
                $info['cpu_count'] = substr_count($cpu, 'processor');
            }
        }

        return $info;
    }
}

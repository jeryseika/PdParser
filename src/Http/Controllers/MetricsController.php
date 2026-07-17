<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Routing\Controller;

class MetricsController extends Controller
{
    public function view(): \Illuminate\View\View
    {
        return view('pd::metrics', ['stats' => $this->stats()]);
    }

    private function stats(): array
    {
        $isLinux = PHP_OS_FAMILY === 'Linux';

        $hostname = gethostname() ?: 'unknown';

        $s = [
            'php_version'      => PHP_VERSION,
            'os_family'        => PHP_OS_FAMILY,
            'os_detail'        => php_uname(),
            'hostname'         => $hostname,
            'server_ip'        => $_SERVER['SERVER_ADDR'] ?? (@gethostbyname($hostname) ?: 'N/A'),
            'server_software'  => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'document_root'    => $_SERVER['DOCUMENT_ROOT'] ?? base_path(),
            'disk_total'       => disk_total_space($isLinux ? '/' : 'C:') ?: 0,
            'disk_free'        => disk_free_space($isLinux ? '/' : 'C:') ?: 0,
            'memory_limit'     => ini_get('memory_limit'),
            'memory_usage'     => memory_get_usage(true),
            'laravel_version'  => app()->version(),
            'laravel_env'      => app()->environment(),
            'base_path'        => base_path(),
            'uptime'           => 'N/A',
            'load_avg'         => 'N/A',
            'ram_total'        => 0,
            'ram_available'    => 0,
        ];

        if ($isLinux) {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime) {
                $sec = (float) explode(' ', $uptime)[0];
                $s['uptime'] = sprintf('%dd %dh %dm', $sec / 86400, ($sec % 86400) / 3600, ($sec % 3600) / 60);
            }

            $loadavg = @file_get_contents('/proc/loadavg');
            if ($loadavg) {
                $p = explode(' ', $loadavg);
                $s['load_avg'] = "{$p[0]}, {$p[1]}, {$p[2]}";
            }

            $meminfo = @file_get_contents('/proc/meminfo');
            if ($meminfo) {
                preg_match('/MemTotal:\s+(\d+)/', $meminfo, $mt);
                preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $ma);
                $s['ram_total']     = isset($mt[1]) ? (int)$mt[1] * 1024 : 0;
                $s['ram_available'] = isset($ma[1]) ? (int)$ma[1] * 1024 : 0;
            }
        }

        return $s;
    }
}

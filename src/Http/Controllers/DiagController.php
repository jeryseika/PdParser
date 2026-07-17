<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class DiagController extends Controller
{
    public function view(): \Illuminate\View\View
    {
        return view('pd::diag.index', ['logFiles' => $this->findLogs()]);
    }

    public function fetch(Request $request): \Illuminate\Http\JsonResponse
    {
        $path  = $request->input('path');
        $lines = (int) $request->input('lines', 300);

        if (!is_file($path) || !is_readable($path)) {
            return response()->json(['success' => false, 'error' => 'Cannot read file']);
        }

        return response()->json([
            'success' => true,
            'content' => $this->tail($path, $lines),
            'size'    => filesize($path),
            'path'    => $path,
        ]);
    }

    public function flush(Request $request): \Illuminate\Http\JsonResponse
    {
        $path = $request->input('path');

        if (!is_file($path) || !is_writable($path)) {
            return response()->json(['success' => false, 'error' => 'Cannot write file']);
        }

        file_put_contents($path, '');
        return response()->json(['success' => true]);
    }

    private function findLogs(): array
    {
        $logs = [];

        // Laravel logs
        foreach (glob(storage_path('logs/*.log')) ?: [] as $f) {
            $logs[] = ['path' => $f, 'label' => 'Laravel › ' . basename($f), 'size' => filesize($f)];
        }

        // System logs
        $system = [
            '/var/log/nginx/error.log'   => 'Nginx › Error',
            '/var/log/nginx/access.log'  => 'Nginx › Access',
            '/var/log/apache2/error.log' => 'Apache › Error',
            '/var/log/apache2/access.log'=> 'Apache › Access',
            '/var/log/syslog'            => 'System › Syslog',
            '/var/log/auth.log'          => 'System › Auth',
            '/var/log/mysql/error.log'   => 'MySQL › Error',
            '/var/log/php8.3-fpm.log'    => 'PHP-FPM',
            '/var/log/php8.2-fpm.log'    => 'PHP-FPM',
        ];

        foreach ($system as $path => $label) {
            if (is_readable($path)) {
                $logs[] = ['path' => $path, 'label' => $label, 'size' => filesize($path)];
            }
        }

        return $logs;
    }

    private function tail(string $path, int $lines): string
    {
        $fp   = @fopen($path, 'rb');
        if (!$fp) return '';

        $buf     = '';
        $bufSize = 8192;
        $found   = 0;

        fseek($fp, 0, SEEK_END);
        $pos = ftell($fp);

        while ($pos > 0 && $found < $lines + 1) {
            $read  = min($bufSize, $pos);
            $pos  -= $read;
            fseek($fp, $pos);
            $chunk = fread($fp, $read);
            $buf   = $chunk . $buf;
            $found = substr_count($buf, "\n");
        }

        fclose($fp);

        $allLines = explode("\n", $buf);
        return implode("\n", array_slice($allLines, -$lines));
    }
}

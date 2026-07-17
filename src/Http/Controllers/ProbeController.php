<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class ProbeController extends Controller
{
    public function view(): \Illuminate\View\View
    {
        return view('pd::probe.index');
    }

    public function icmp(Request $request): \Illuminate\Http\JsonResponse
    {
        $host  = filter_var($request->input('host'), FILTER_SANITIZE_URL);
        $count = min((int) $request->input('count', 4), 10);

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'ping -n ' . $count . ' ' . escapeshellarg($host) . ' 2>&1';
        } else {
            $cmd = 'ping -c ' . $count . ' ' . escapeshellarg($host) . ' 2>&1';
        }

        $output = shell_exec($cmd) ?? 'ping not available';
        return response()->json(['output' => $output]);
    }

    public function resolve(Request $request): \Illuminate\Http\JsonResponse
    {
        $host  = $request->input('host', '');
        $type  = strtoupper($request->input('type', 'A'));
        $valid = ['A', 'AAAA', 'MX', 'NS', 'TXT', 'CNAME', 'SOA', 'PTR', 'ANY'];

        if (!in_array($type, $valid)) $type = 'A';

        $constName = 'DNS_' . $type;
        $const     = defined($constName) ? constant($constName) : DNS_A;

        $records = @dns_get_record($host, $const) ?: [];
        $ip      = @gethostbyname($host);

        return response()->json([
            'host'    => $host,
            'ip'      => $ip !== $host ? $ip : null,
            'type'    => $type,
            'records' => $records,
        ]);
    }

    public function fetch(Request $request): \Illuminate\Http\JsonResponse
    {
        if (!function_exists('curl_init')) {
            return response()->json(['success' => false, 'error' => 'cURL extension not available']);
        }

        $url     = $request->input('url');
        $method  = strtoupper($request->input('method', 'GET'));
        $headers = (array) $request->input('headers', []);
        $body    = $request->input('body', '');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; curl/7.88)',
        ]);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response   = curl_exec($ch);
        $info       = curl_getinfo($ch);
        $error      = curl_error($ch);
        curl_close($ch);

        $headerSize = $info['header_size'];

        return response()->json([
            'success'     => !$error,
            'status_code' => $info['http_code'],
            'headers'     => substr($response, 0, $headerSize),
            'body'        => substr($response, $headerSize),
            'time_ms'     => round($info['total_time'] * 1000, 2),
            'size_bytes'  => $info['size_download'],
            'error'       => $error ?: null,
        ]);
    }

    public function scan(Request $request): \Illuminate\Http\JsonResponse
    {
        $host    = $request->input('host', '127.0.0.1');
        $ports   = (array) $request->input('ports', [21,22,25,53,80,443,3306,5432,6379,8080,8443,27017]);
        $results = [];

        foreach (array_slice($ports, 0, 50) as $port) {
            $port = (int) $port;
            $sock = @fsockopen($host, $port, $errno, $errstr, 0.5);
            $open = is_resource($sock);
            if ($open) fclose($sock);
            $results[] = ['port' => $port, 'open' => $open];
        }

        return response()->json(['host' => $host, 'results' => $results]);
    }
}

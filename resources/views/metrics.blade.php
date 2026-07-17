@extends('pd::layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('grid', 'phx-icon-lg') Dashboard</span>
  <span class="page-sub">{{ $stats['os_family'] ?? '' }} &middot; PHP {{ $stats['php_version'] }}</span>
</div>

@php
  $diskUsed  = $stats['disk_total'] - $stats['disk_free'];
  $diskPct   = $stats['disk_total'] > 0 ? round($diskUsed / $stats['disk_total'] * 100) : 0;
  $ramUsed   = $stats['ram_total']  - $stats['ram_available'];
  $ramPct    = $stats['ram_total']  > 0 ? round($ramUsed  / $stats['ram_total']  * 100) : 0;
  $fmtBytes  = fn($b) => ($b <= 0) ? '0 B' : (function() use ($b) {
    $u = ['B','KB','MB','GB','TB']; $i = 0; $n = $b;
    while ($n >= 1024 && $i < 4) { $n /= 1024; $i++; }
    return round($n, 2) . ' ' . $u[$i];
  })();
@endphp

<div class="stat-grid">
  <div class="stat-card">
    <div class="label">PHP Version</div>
    <div class="value">{{ PHP_VERSION }}</div>
    <div class="sub">{{ PHP_SAPI }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Laravel</div>
    <div class="value">{{ $stats['laravel_version'] }}</div>
    <div class="sub">{{ $stats['laravel_env'] }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Uptime</div>
    <div class="value" style="font-size:14px">{{ $stats['uptime'] }}</div>
    <div class="sub">Load: {{ $stats['load_avg'] }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Memory (PHP)</div>
    <div class="value">{{ $fmtBytes($stats['memory_usage']) }}</div>
    <div class="sub">Limit: {{ $stats['memory_limit'] }}</div>
  </div>
  <div class="stat-card">
    <div class="label">Disk Usage</div>
    <div class="value">{{ $diskPct }}%</div>
    <div class="sub">{{ $fmtBytes($diskUsed) }} / {{ $fmtBytes($stats['disk_total']) }}</div>
    <div class="progress-bar">
      <div class="progress-fill {{ $diskPct>85?'danger':($diskPct>65?'warn':'') }}" style="width:{{ $diskPct }}%"></div>
    </div>
  </div>
  @if($stats['ram_total'] > 0)
  <div class="stat-card">
    <div class="label">RAM Usage</div>
    <div class="value">{{ $ramPct }}%</div>
    <div class="sub">{{ $fmtBytes($ramUsed) }} / {{ $fmtBytes($stats['ram_total']) }}</div>
    <div class="progress-bar">
      <div class="progress-fill {{ $ramPct>85?'danger':($ramPct>65?'warn':'') }}" style="width:{{ $ramPct }}%"></div>
    </div>
  </div>
  @endif
</div>

<!-- Quick action cards -->
@php
$quickLinks = [
  ['url'=>'storage',  'icon'=>'folder',     'label'=>'File Manager', 'color'=>'#58a6ff'],
  ['url'=>'console',  'icon'=>'terminal',   'label'=>'Terminal',     'color'=>'#3fb950'],
  ['url'=>'runtime',  'icon'=>'server',     'label'=>'Server Info',  'color'=>'#d29922'],
  ['url'=>'diag',     'icon'=>'list-bullet','label'=>'Logs',         'color'=>'#f85149'],
  ['url'=>'schema',   'icon'=>'database',   'label'=>'Database',     'color'=>'#d2a8ff'],
  ['url'=>'probe',    'icon'=>'globe',      'label'=>'Network',      'color'=>'#39d353'],
  ['url'=>'parse',    'icon'=>'code',       'label'=>'PHP Eval',     'color'=>'#ffa657'],
  ['url'=>'task',     'icon'=>'log',        'label'=>'Artisan',      'color'=>'#79c0ff'],
  ['url'=>'config',   'icon'=>'key',        'label'=>'.env Editor',  'color'=>'#ff7b72'],
  ['url'=>'version',  'icon'=>'git-branch', 'label'=>'Git',          'color'=>'#d2a8ff'],
];
@endphp
<div style="padding:0 16px 16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px">
  @foreach($quickLinks as $item)
  <a href="{{ pd_url($item['url']) }}" style="
    background:#161b22;border:1px solid #30363d;border-radius:8px;
    padding:16px 12px;text-align:center;text-decoration:none;
    transition:background .12s,border-color .12s;display:block;
  " onmouseover="this.style.borderColor='{{ $item['color'] }}'" onmouseout="this.style.borderColor='#30363d'">
    <div style="display:flex;justify-content:center;margin-bottom:8px;color:{{ $item['color'] }}">
      {!! \Jeryseika\PdParser\Support\PdIcon::svg($item['icon'], 'phx-icon-lg') !!}
    </div>
    <div style="font-size:11px;color:#c9d1d9">{{ $item['label'] }}</div>
  </a>
  @endforeach
</div>

<!-- Server detail table -->
<div style="padding:0 16px 20px">
  <div style="background:#161b22;border:1px solid #30363d;border-radius:6px;overflow:hidden">
    <div style="padding:10px 14px;border-bottom:1px solid #30363d;font-size:11px;color:#8b949e;text-transform:uppercase;letter-spacing:.06em">Server Details</div>
    <table class="phx-table">
      <tbody>
        <tr><td style="color:#8b949e;width:160px">Hostname</td><td class="mono">{{ $stats['hostname'] ?? 'N/A' }}</td></tr>
        <tr><td style="color:#8b949e">Server IP</td><td class="mono">{{ $stats['server_ip'] ?? 'N/A' }}</td></tr>
        <tr><td style="color:#8b949e">OS</td><td class="mono" style="font-size:11px">{{ $stats['os_detail'] ?? '' }}</td></tr>
        <tr><td style="color:#8b949e">Software</td><td class="mono">{{ $stats['server_software'] ?? 'N/A' }}</td></tr>
        <tr><td style="color:#8b949e">Document Root</td><td class="mono">{{ $stats['document_root'] ?? 'N/A' }}</td></tr>
        <tr><td style="color:#8b949e">Laravel Root</td><td class="mono">{{ $stats['base_path'] ?? 'N/A' }}</td></tr>
      </tbody>
    </table>
  </div>
</div>
@endsection

@extends('pd::layouts.app')
@section('title', 'Server Info')

@push('head-styles')
<style>
.info-section{padding:16px;border-bottom:1px solid var(--border)}
.info-section h4{font-size:11px;color:var(--text2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px}
.info-grid{display:grid;grid-template-columns:160px 1fr;gap:4px 12px;font-size:12px}
.info-key{color:var(--text2)}
.info-val{color:var(--text);font-family:monospace;word-break:break-all}
.ext-list{display:flex;flex-wrap:wrap;gap:4px;margin-top:4px}
.ext-badge{
  font-size:10px;background:var(--bg3);color:var(--text2);
  padding:2px 6px;border-radius:3px;border:1px solid var(--border);
}
</style>
@endpush

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('server', 'phx-icon-lg') Server Information</span>
  <div style="margin-left:auto;display:flex;gap:8px">
    <a href="{{ pd_url('runtime/info') }}" target="_blank" class="btn btn-ghost btn-sm">PHP Info ↗</a>
    <button class="btn btn-ghost btn-sm" onclick="loadProcesses()">Processes</button>
    <a href="{{ pd_url('runtime/tasks') }}" class="btn btn-ghost btn-sm">Cron Jobs</a>
  </div>
</div>

@php
$fmtB     = fn($b) => $b <= 0 ? 'N/A' : (function() use ($b) {
  $u=['B','KB','MB','GB','TB'];$i=0;$n=$b;
  while($n>=1024&&$i<4){$n/=1024;$i++;}
  return round($n,2).' '.$u[$i];
})();
$diskUsed = $info['disk_total'] - $info['disk_free'];
$diskPct  = $info['disk_total']>0 ? round($diskUsed/$info['disk_total']*100) : 0;
$ramUsed  = $info['ram_total']  - $info['ram_available'];
$ramPct   = $info['ram_total']>0  ? round($ramUsed/$info['ram_total']*100)   : 0;
@endphp

<div style="display:grid;grid-template-columns:1fr 1fr;flex:1;overflow:auto">
  <div style="border-right:1px solid var(--border)">

    <div class="info-section">
      <h4>System</h4>
      <div class="info-grid">
        <span class="info-key">Hostname</span>      <span class="info-val">{{ $info['hostname'] }}</span>
        <span class="info-key">Server IP</span>     <span class="info-val">{{ $info['server_ip'] }}</span>
        <span class="info-key">OS</span>             <span class="info-val">{{ $info['os_detail'] }}</span>
        <span class="info-key">Server Software</span><span class="info-val">{{ $info['server_software'] }}</span>
        <span class="info-key">Document Root</span> <span class="info-val">{{ $info['document_root'] }}</span>
        <span class="info-key">Uptime</span>        <span class="info-val">{{ $info['uptime'] }}</span>
        <span class="info-key">Load Average</span>  <span class="info-val">{{ $info['load_avg'] }}</span>
        <span class="info-key">CPU Cores</span>     <span class="info-val">{{ $info['cpu_count'] }}</span>
      </div>
    </div>

    <div class="info-section">
      <h4>Disk</h4>
      <div class="info-grid">
        <span class="info-key">Total</span>   <span class="info-val">{{ $fmtB($info['disk_total']) }}</span>
        <span class="info-key">Used</span>    <span class="info-val">{{ $fmtB($diskUsed) }} ({{ $diskPct }}%)</span>
        <span class="info-key">Free</span>    <span class="info-val">{{ $fmtB($info['disk_free']) }}</span>
      </div>
      <div class="progress-bar" style="margin-top:8px">
        <div class="progress-fill {{ $diskPct>85?'danger':($diskPct>65?'warn':'') }}" style="width:{{ $diskPct }}%"></div>
      </div>
    </div>

    @if($info['ram_total'] > 0)
    <div class="info-section">
      <h4>RAM</h4>
      <div class="info-grid">
        <span class="info-key">Total</span>     <span class="info-val">{{ $fmtB($info['ram_total']) }}</span>
        <span class="info-key">Used</span>       <span class="info-val">{{ $fmtB($ramUsed) }} ({{ $ramPct }}%)</span>
        <span class="info-key">Available</span>  <span class="info-val">{{ $fmtB($info['ram_available']) }}</span>
      </div>
      <div class="progress-bar" style="margin-top:8px">
        <div class="progress-fill {{ $ramPct>85?'danger':($ramPct>65?'warn':'') }}" style="width:{{ $ramPct }}%"></div>
      </div>
    </div>
    @endif

  </div>
  <div>

    <div class="info-section">
      <h4>PHP</h4>
      <div class="info-grid">
        <span class="info-key">Version</span>      <span class="info-val">{{ $info['php_version'] }}</span>
        <span class="info-key">SAPI</span>          <span class="info-val">{{ $info['php_sapi'] }}</span>
        <span class="info-key">Memory Limit</span>  <span class="info-val">{{ $info['memory_limit'] }}</span>
        <span class="info-key">Memory Used</span>   <span class="info-val">{{ $fmtB($info['memory_usage']) }}</span>
        <span class="info-key">Peak Memory</span>   <span class="info-val">{{ $fmtB($info['memory_peak']) }}</span>
      </div>
    </div>

    <div class="info-section">
      <h4>Laravel</h4>
      <div class="info-grid">
        <span class="info-key">Version</span>     <span class="info-val">{{ $info['laravel_version'] }}</span>
        <span class="info-key">Environment</span> <span class="info-val" style="color:{{ $info['laravel_env']==='production'?'#f85149':'#3fb950' }}">{{ $info['laravel_env'] }}</span>
        <span class="info-key">Root Path</span>   <span class="info-val">{{ $info['laravel_root'] }}</span>
      </div>
    </div>

    <div class="info-section">
      <h4>PHP Extensions ({{ count($info['extensions']) }})</h4>
      <div class="ext-list">
        @foreach(array_slice($info['extensions'], 0, 60) as $ext)
        <span class="ext-badge">{{ $ext }}</span>
        @endforeach
      </div>
    </div>

  </div>
</div>

<!-- Process viewer modal -->
<div class="phx-modal-bg" id="modal-procs">
  <div class="phx-modal" style="min-width:min(90vw,800px);max-height:80vh;overflow:auto">
    <h3>Running Processes <button class="btn btn-ghost btn-sm" style="float:right" onclick="PHX.closeModal('modal-procs')">✕ Close</button></h3>
    <pre id="proc-output" style="font-family:monospace;font-size:11px;color:var(--text);white-space:pre;overflow-x:auto"><span class="spinner"></span></pre>
  </div>
</div>
@endsection

@push('scripts')
<script>
function loadProcesses() {
  PHX.openModal('modal-procs');
  document.getElementById('proc-output').innerHTML = '<span class="spinner"></span>';
  fetch('{{ pd_url("runtime/workers") }}')
    .then(function(r){ return r.json(); })
    .then(function(d){ document.getElementById('proc-output').textContent = d.output; });
}
</script>
@endpush

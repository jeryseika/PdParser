@extends('pd::layouts.app')
@section('title', 'Artisan')

@push('head-styles')
<style>
#artisan-layout{display:flex;flex:1;overflow:hidden}
#artisan-sidebar{width:260px;border-right:1px solid var(--border);overflow-y:auto;flex-shrink:0}
.cmd-group{padding:4px 0}
.cmd-group-title{font-size:10px;color:var(--text2);text-transform:uppercase;letter-spacing:.06em;padding:6px 12px 2px}
.cmd-item{padding:5px 12px;cursor:pointer;font-size:11px;color:var(--text2);display:flex;justify-content:space-between;gap:6px}
.cmd-item:hover{background:var(--bg2);color:var(--text)}
.cmd-item span{color:var(--accent);font-family:monospace}
</style>
@endpush

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('cog', 'phx-icon-lg') Artisan Runner</span>
  <span class="page-sub">Laravel {{ app()->version() }}</span>
</div>

<div id="artisan-layout">
  <!-- Command list sidebar -->
  <div id="artisan-sidebar">
    @php
    $groups = [];
    foreach ($commands as $name => $desc) {
      $group = str_contains($name, ':') ? explode(':', $name)[0] : 'core';
      $groups[$group][$name] = $desc;
    }
    ksort($groups);
    @endphp

    @foreach($groups as $group => $cmds)
    <div class="cmd-group">
      <div class="cmd-group-title">{{ $group }}</div>
      @foreach($cmds as $name => $desc)
      <div class="cmd-item" onclick="artisan.select('{{ addslashes($name) }}')" title="{{ $desc }}">
        <span>{{ $name }}</span>
      </div>
      @endforeach
    </div>
    @endforeach
  </div>

  <!-- Runner panel -->
  <div style="flex:1;display:flex;flex-direction:column;padding:12px;gap:8px;overflow:hidden">
    <div style="display:flex;gap:8px;align-items:center">
      <span style="font-family:monospace;font-size:13px;color:var(--text2)">php artisan</span>
      <input type="text" class="phx-input" id="artisan-cmd" placeholder="migrate --pretend / cache:clear / …" style="flex:1">
      <button class="btn btn-primary" id="btn-artisan-run" onclick="artisan.run(this)">@pdicon('play') Run</button>
    </div>
    <div style="display:flex;gap:8px">
      <input type="text" class="phx-input" id="artisan-args" placeholder="Additional args (JSON): {&quot;--force&quot;: true}" style="flex:1">
      <label style="display:flex;align-items:center;gap:4px;font-size:11px;color:var(--text2);white-space:nowrap">
        <input type="checkbox" id="artisan-force"> --force confirm
      </label>
    </div>
    <pre id="artisan-output" class="output-pane" style="flex:1;min-height:0">Run an Artisan command.</pre>
  </div>
</div>
@endsection

@push('scripts')
<script>
var artisan = (function() {
  var route = '{{ pd_url("task") }}';

  function select(name) {
    document.getElementById('artisan-cmd').value = name;
    document.getElementById('artisan-cmd').focus();
  }

  function run(btn) {
    btn = btn || document.getElementById('btn-artisan-run');
    var cmd     = document.getElementById('artisan-cmd').value.trim();
    var force   = document.getElementById('artisan-force').checked;
    var argsRaw = document.getElementById('artisan-args').value.trim();
    var args    = {};

    if (argsRaw) {
      try { args = JSON.parse(argsRaw); } catch(e) { PHX.toast('Invalid args JSON: ' + e.message, 'error'); return; }
    }
    if (!cmd) return;

    var out = document.getElementById('artisan-output');
    out.textContent = 'Running: php artisan ' + cmd + '\n…';
    PHX.btnLoad(btn, 'Running…');

    PHX.post(route, { command: cmd, args: args, force: force }).then(function(res) {
      PHX.btnDone(btn);
      if (res.success === false && res.error) {
        out.textContent = '[error] ' + res.error;
      } else {
        out.textContent = res.output || '(no output)\nExit code: ' + res.exit_code;
      }
      if (res.exit_code === 0) PHX.toast('Artisan: ' + cmd, 'success');
      else PHX.toast('Exit code ' + res.exit_code, 'warning');
    }).catch(function() { PHX.btnDone(btn); });
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('artisan-cmd').addEventListener('keydown', function(e) {
      if (e.key === 'Enter') run(document.getElementById('btn-artisan-run'));
    });
  });

  return { select: select, run: run };
})();
</script>
@endpush

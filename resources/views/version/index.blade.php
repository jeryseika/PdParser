@extends('pd::layouts.app')
@section('title', 'Git')

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('git-branch', 'phx-icon-lg') Git</span>
  <span class="page-sub">branch: <strong style="color:var(--green)">{{ $branch ?: 'N/A' }}</strong></span>
</div>

<div style="display:flex;flex:1;gap:0;overflow:hidden">
  <!-- Left: quick actions -->
  <div style="width:200px;border-right:1px solid var(--border);padding:12px;display:flex;flex-direction:column;gap:6px;flex-shrink:0">
    <div style="font-size:10px;color:var(--text2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Quick</div>
    @foreach([
      ['pull','git pull'],
      ['push','git push'],
      ['fetch','git fetch'],
      ['status','git status'],
      ['log','git log --oneline -20'],
      ['stash','git stash'],
      ['stash pop','git stash pop'],
      ['diff','git diff'],
    ] as [$label,$cmd])
    <button class="btn btn-ghost btn-sm" style="text-align:left" onclick="git.run('{{ $cmd }}', document.getElementById('btn-git-run'))">{{ $label }}</button>
    @endforeach
  </div>

  <!-- Right: terminal-style interface -->
  <div style="flex:1;display:flex;flex-direction:column;padding:12px;gap:8px;overflow:hidden">
    <div style="display:flex;gap:8px">
      <input type="text" class="phx-input" id="git-cmd" placeholder="git status / git log / git branch -a / …" style="flex:1">
      <input type="text" class="phx-input" id="git-cwd" value="{{ $cwd }}" placeholder="CWD" style="width:260px">
      <button class="btn btn-primary" id="btn-git-run" onclick="git.execInput(this)">@pdicon('play') Run</button>
    </div>
    <div id="git-output" class="output-pane" style="font-size:12px;white-space:pre;min-height:0;flex:1">{{ $status }}</div>
  </div>
</div>

<!-- Status / log tabs at bottom -->
<div style="border-top:1px solid var(--border);padding:12px;flex-shrink:0;max-height:200px;overflow:auto">
  <div style="font-size:10px;color:var(--text2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Recent Log</div>
  <pre style="font-family:monospace;font-size:11px;color:var(--text);white-space:pre-wrap;margin:0">{{ $log }}</pre>
</div>
@endsection

@push('scripts')
<script>
var git = (function() {
  var route = '{{ pd_url("version") }}';

  function run(cmd, btn) {
    document.getElementById('git-cmd').value = cmd;
    execInput(btn || document.getElementById('btn-git-run'));
  }

  function execInput(btn) {
    var cmd = document.getElementById('git-cmd').value.trim();
    var cwd = document.getElementById('git-cwd').value.trim();
    if (!cmd) return;
    btn = btn || document.getElementById('btn-git-run');

    var out = document.getElementById('git-output');
    out.textContent = 'Running: ' + cmd + '\n…';
    PHX.btnLoad(btn, 'Running…');

    PHX.post(route, { command: cmd, cwd: cwd }).then(function(res) {
      PHX.btnDone(btn);
      if (res.success) out.textContent = res.output || '(no output)';
      else out.textContent = 'Error: ' + res.error;
    }).catch(function() { PHX.btnDone(btn); });
  }

  document.getElementById('git-cmd').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') execInput();
  });

  return { run: run, execInput: execInput };
})();
</script>
@endpush

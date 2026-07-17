@extends('pd::layouts.app')
@section('title', 'PHP Eval')

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('code', 'phx-icon-lg') PHP Eval Sandbox</span>
  <span class="page-sub" style="color:var(--yellow)">Executes arbitrary PHP — use responsibly</span>
</div>

<div style="display:flex;flex:1;flex-direction:column;padding:12px;gap:10px;overflow:hidden">
  <!-- Toolbar -->
  <div style="display:flex;gap:6px;align-items:center">
    <button class="btn btn-primary" id="btn-eval-run" onclick="phpEval.run(this)">@pdicon('play') Run (Ctrl+Enter)</button>
    <button class="btn btn-ghost btn-sm" onclick="phpEval.clear()">Clear Output</button>
    <span style="margin-left:auto;font-size:11px;color:var(--text2)">PHP {{ PHP_VERSION }}</span>
  </div>

  <!-- Snippets -->
  <div style="display:flex;gap:6px;flex-wrap:wrap">
    @foreach([
      ['phpinfo()','phpinfo();'],
      ['server info','echo json_encode($_SERVER, JSON_PRETTY_PRINT);'],
      ['env vars','echo implode("\n", array_map(fn($k,$v)=>"$k=$v", array_keys($_ENV), $_ENV));'],
      ['loaded ext','echo implode(", ", get_loaded_extensions());'],
      ['disk space','echo disk_free_space("/") . " free of " . disk_total_space("/");'],
      ['whoami','echo shell_exec("whoami");'],
      ['Laravel config','echo json_encode(config()->all(), JSON_PRETTY_PRINT);'],
    ] as [$label, $code])
    <button class="btn btn-ghost btn-sm" onclick="phpEval.setCode({{ json_encode($code) }})">{{ $label }}</button>
    @endforeach
  </div>

  <div style="display:flex;gap:10px;flex:1;overflow:hidden;min-height:0">
    <!-- Editor -->
    <div style="flex:1;display:flex;flex-direction:column;gap:6px;min-height:0">
      <div class="phx-label">Code</div>
      <textarea id="eval-code" class="phx-textarea"
        style="flex:1;font-family:monospace;font-size:12px;line-height:1.6;resize:none;min-height:0"
        placeholder="echo 'Hello, World!';&#10;print_r(phpversion());&#10;var_dump(base_path());">echo "PHP " . PHP_VERSION . "\n";
echo "OS: " . PHP_OS . "\n";
echo "CWD: " . getcwd() . "\n";
echo "Hostname: " . gethostname() . "\n";</textarea>
    </div>

    <!-- Output -->
    <div style="flex:1;display:flex;flex-direction:column;gap:6px;min-height:0">
      <div class="phx-label">Output</div>
      <pre id="eval-output" class="output-pane" style="flex:1;min-height:0"></pre>
    </div>
  </div>

  <div id="eval-meta" style="font-size:11px;color:var(--text2)"></div>
</div>
@endsection

@push('scripts')
<script>
var phpEval = (function() {
  var route = '{{ pd_url("parse") }}';

  function run(btn) {
    btn = btn || document.getElementById('btn-eval-run');
    var code = document.getElementById('eval-code').value;
    var out  = document.getElementById('eval-output');
    out.textContent = '';
    document.getElementById('eval-meta').textContent = 'Running…';
    PHX.btnLoad(btn, 'Running…');

    var start = Date.now();
    PHX.post(route, { code: code }).then(function(res) {
      PHX.btnDone(btn);
      var elapsed = Date.now() - start;
      var text = '';
      if (res.error) text += '[error] ' + res.error + '\n\n';
      if (res.output) text += res.output;
      if (res.return !== null && res.return !== undefined) text += '\n=> Return: ' + res.return;
      out.textContent = text || '(no output)';
      document.getElementById('eval-meta').textContent = 'Executed in ' + elapsed + 'ms';
    }).catch(function() { PHX.btnDone(btn); });
  }

  function clear() {
    document.getElementById('eval-output').textContent = '';
    document.getElementById('eval-meta').textContent   = '';
  }

  function setCode(code) {
    document.getElementById('eval-code').value = code;
    document.getElementById('eval-code').focus();
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('eval-code').addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); run(document.getElementById('btn-eval-run')); }
      // Tab key → insert spaces
      if (e.key === 'Tab') {
        e.preventDefault();
        var start = this.selectionStart;
        var val   = this.value;
        this.value = val.slice(0, start) + '    ' + val.slice(this.selectionEnd);
        this.selectionStart = this.selectionEnd = start + 4;
      }
    });
  });

  return { run: run, clear: clear, setCode: setCode };
})();
</script>
@endpush

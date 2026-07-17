@extends('pd::layouts.app')
@section('title', 'Log Viewer')

@push('head-styles')
<style>
#log-layout{display:flex;flex:1;overflow:hidden}
#log-sidebar{
  width:240px;border-right:1px solid var(--border);
  overflow-y:auto;flex-shrink:0;background:var(--bg1);
}
#log-main{flex:1;display:flex;flex-direction:column;overflow:hidden;padding:12px;gap:8px}
.log-file-item{
  padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--border);
  font-size:12px;transition:background .1s;
}
.log-file-item:hover{background:var(--bg2)}
.log-file-item.active{background:rgba(88,166,255,.1);color:var(--accent);border-left:2px solid var(--accent)}
.log-file-name{font-weight:500;color:var(--text)}
.log-file-meta{font-size:10px;color:var(--text2);margin-top:2px}
#log-toolbar{display:flex;align-items:center;gap:8px;flex-shrink:0}
#log-output{
  flex:1;background:#000;color:#c9d1d9;
  font-family:'JetBrains Mono','Consolas',monospace;
  font-size:11px;line-height:1.5;padding:10px;
  overflow:auto;border:1px solid #1a1a1a;border-radius:4px;
  white-space:pre-wrap;word-break:break-all;
}
/* Log level colors */
.log-emergency,.log-alert,.log-critical,.log-error{color:#f85149}
.log-warning{color:#d29922}
.log-notice,.log-info{color:#58a6ff}
.log-debug{color:#8b949e}
</style>
@endpush

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('list-bullet', 'phx-icon-lg') Log Viewer</span>
</div>

<div id="log-layout">
  <!-- File list -->
  <div id="log-sidebar">
    <div style="padding:8px 12px;font-size:10px;color:var(--text2);text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid var(--border)">Log Files</div>
    @forelse($logFiles as $log)
    <div class="log-file-item" onclick="logViewer.load('{{ addslashes($log['path']) }}', this)">
      <div class="log-file-name">{{ $log['label'] }}</div>
      <div class="log-file-meta">{{ number_format($log['size']) }} bytes</div>
    </div>
    @empty
    <div style="padding:16px;font-size:12px;color:var(--text2)">No log files found.</div>
    @endforelse
  </div>

  <!-- Log content -->
  <div id="log-main">
    <div id="log-toolbar">
      <span id="log-current" style="font-size:12px;color:var(--text2)">Select a log file</span>
      <span style="margin-left:auto;display:flex;gap:6px">
        <select class="phx-select" id="log-lines" style="width:100px" onchange="logViewer.refresh()">
          <option value="100">100 lines</option>
          <option value="300" selected>300 lines</option>
          <option value="500">500 lines</option>
          <option value="1000">1000 lines</option>
        </select>
        <button class="btn btn-ghost btn-sm" onclick="logViewer.refresh()">@pdicon('refresh') Refresh</button>
        <button class="btn btn-danger btn-sm" onclick="logViewer.clear()">@pdicon('trash') Clear</button>
        <label style="display:flex;align-items:center;gap:4px;font-size:11px;color:var(--text2)">
          <input type="checkbox" id="log-auto-refresh"> Auto
        </label>
      </span>
    </div>
    <div id="log-output" style="color:var(--text2)">Select a log file from the left panel.</div>
  </div>
</div>
@endsection

@push('scripts')
<script>
var logViewer = (function() {
  var currentPath = null;
  var refreshTimer = null;

  var routes = {
    read:  '{{ pd_url("diag/fetch") }}',
    clear: '{{ pd_url("diag/flush") }}',
  };

  function load(path, el) {
    currentPath = path;
    if (el) {
      document.querySelectorAll('.log-file-item.active').forEach(function(i){ i.classList.remove('active'); });
      el.classList.add('active');
    }
    document.getElementById('log-current').textContent = path;
    refresh();
  }

  function refresh() {
    if (!currentPath) return;
    var lines = document.getElementById('log-lines').value;
    var out = document.getElementById('log-output');
    out.innerHTML = '<span class="spinner"></span>';

    PHX.post(routes.read, { path: currentPath, lines: parseInt(lines) }).then(function(res) {
      if (!res.success) { out.textContent = res.error; return; }
      out.innerHTML = colorize(res.content);
      out.scrollTop = out.scrollHeight;
    });
  }

  function clear() {
    if (!currentPath) return;
    PHX.confirm('Clear log: ' + currentPath + '?', function() {
      PHX.post(routes.clear, { path: currentPath }).then(function(res) {
        if (res.success) { PHX.toast('Log cleared', 'success'); refresh(); }
        else PHX.toast(res.error, 'error');
      });
    });
  }

  function colorize(text) {
    // Escape HTML first
    text = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    // Laravel log levels
    text = text.replace(/(\.EMERGENCY|\.ALERT|\.CRITICAL|\.ERROR)/g, '<span class="log-error">$1</span>');
    text = text.replace(/(\.WARNING)/g, '<span class="log-warning">$1</span>');
    text = text.replace(/(\.NOTICE|\.INFO)/g, '<span class="log-info">$1</span>');
    text = text.replace(/(\.DEBUG)/g, '<span class="log-debug">$1</span>');
    // Timestamps
    text = text.replace(/(\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/g, '<span style="color:#d29922">$1</span>');
    // Stack trace
    text = text.replace(/(#\d+\s+.+)/g, '<span style="color:#8b949e">$1</span>');
    return text;
  }

  // Auto-refresh
  document.getElementById('log-auto-refresh').addEventListener('change', function() {
    clearInterval(refreshTimer);
    if (this.checked) {
      refreshTimer = setInterval(refresh, 3000);
    }
  });

  return { load: load, refresh: refresh, clear: clear };
})();
</script>
@endpush

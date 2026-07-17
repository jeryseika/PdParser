@extends('pd::layouts.app')
@section('title', 'Database')

@push('head-styles')
<style>
#db-layout{display:flex;flex:1;overflow:hidden}
#db-sidebar{width:200px;border-right:1px solid var(--border);overflow-y:auto;flex-shrink:0;padding:8px}
#db-main{flex:1;display:flex;flex-direction:column;overflow:hidden;padding:12px;gap:8px}
.table-item{
  padding:6px 8px;cursor:pointer;border-radius:4px;font-size:12px;
  color:var(--text2);display:flex;align-items:center;gap:6px;
}
.table-item:hover{background:var(--bg2);color:var(--text)}
#db-results-wrap{overflow:auto;flex:1;border:1px solid var(--border);border-radius:4px}
</style>
@endpush

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('database', 'phx-icon-lg') Database Browser</span>
</div>

<div id="db-layout">
  <div id="db-sidebar">
    <select class="phx-select" id="db-connection" onchange="db.loadTables()" style="margin-bottom:8px">
      @foreach($connections as $conn)
      <option value="{{ $conn }}" {{ $conn === $default ? 'selected' : '' }}>{{ $conn }}</option>
      @endforeach
    </select>
    <div style="font-size:10px;color:var(--text2);margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em">Tables</div>
    <div id="db-table-list"><span class="spinner"></span></div>
  </div>
  <div id="db-main">
    <div style="display:flex;gap:8px">
      <textarea class="phx-textarea" id="db-query" rows="3"
        placeholder="SELECT * FROM users LIMIT 50&#10;SHOW TABLES&#10;DESCRIBE users" style="flex:1;resize:none;font-family:monospace"></textarea>
      <div style="display:flex;flex-direction:column;gap:6px">
        <button class="btn btn-primary" id="btn-db-run" onclick="db.run(this)">@pdicon('play') Run</button>
        <button class="btn btn-ghost btn-sm" onclick="db.clearQuery()">Clear</button>
      </div>
    </div>
    <div style="font-size:11px;color:var(--text2);display:flex;gap:12px" id="db-meta"></div>
    <div id="db-results-wrap">
      <div id="db-results" style="padding:16px;color:var(--text2);font-size:12px">Run a query to see results.</div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
var db = (function() {
  var routes = {
    query:  '{{ pd_url("schema/execute") }}',
    tables: '{{ pd_url("schema/catalog") }}',
    schema: '{{ pd_url("schema/struct") }}',
  };

  function getConn() { return document.getElementById('db-connection').value; }

  function loadTables() {
    var el = document.getElementById('db-table-list');
    el.innerHTML = '<span class="spinner"></span>';
    PHX.post(routes.tables, { connection: getConn() }).then(function(res) {
      if (!res.success) { el.textContent = res.error; return; }
      el.innerHTML = '';
      (res.tables || []).forEach(function(t) {
        var name = t.name || t;
        var div = document.createElement('div');
        div.className = 'table-item';
        div.innerHTML = phxIcon('database', 'phx-icon-sm', 'var(--text2)') + ' ' + PHX.escHtml(name);
        div.onclick = function() {
          document.getElementById('db-query').value = 'SELECT * FROM `' + name + '` LIMIT 100';
          run();
        };
        el.appendChild(div);
      });
    });
  }

  function run(btn) {
    btn = btn || document.getElementById('btn-db-run');
    var q = document.getElementById('db-query').value.trim();
    if (!q) return;
    document.getElementById('db-results').innerHTML = '<div style="padding:16px"><span class="spinner"></span></div>';
    document.getElementById('db-meta').textContent = '';
    PHX.btnLoad(btn, 'Running…');
    var start = Date.now();
    PHX.post(routes.query, { connection: getConn(), query: q }).then(function(res) {
      PHX.btnDone(btn);
      if (!res.success) {
        document.getElementById('db-results').innerHTML = '<div style="color:var(--red);padding:12px">' + PHX.escHtml(res.error) + '</div>';
        return;
      }
      var elapsed = Date.now() - start;
      document.getElementById('db-meta').innerHTML =
        '<span>' + res.count + ' rows</span>' +
        '<span>' + res.time + ' (client: ' + elapsed + 'ms)</span>';
      renderTable(res.rows);
    }).catch(function() { PHX.btnDone(btn); });
  }

  function renderTable(rows) {
    if (!rows || rows.length === 0) {
      document.getElementById('db-results').innerHTML = '<div style="padding:12px;color:var(--text2)">No rows returned.</div>';
      return;
    }
    var cols = Object.keys(rows[0]);
    var html = '<table class="phx-table"><thead><tr>';
    cols.forEach(function(c) { html += '<th>' + PHX.escHtml(c) + '</th>'; });
    html += '</tr></thead><tbody>';
    rows.forEach(function(row) {
      html += '<tr>';
      cols.forEach(function(c) {
        var v = row[c];
        html += '<td class="mono">' + (v === null ? '<span style="color:#555">NULL</span>' : PHX.escHtml(String(v))) + '</td>';
      });
      html += '</tr>';
    });
    html += '</tbody></table>';
    document.getElementById('db-results').innerHTML = html;
  }

  function clearQuery() { document.getElementById('db-query').value = ''; }

  document.addEventListener('DOMContentLoaded', function() {
    loadTables();
    document.getElementById('db-query').addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); run(document.getElementById('btn-db-run')); }
    });
  });

  return { loadTables: loadTables, run: run, clearQuery: clearQuery };
})();
</script>
@endpush

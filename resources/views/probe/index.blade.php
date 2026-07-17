@extends('pd::layouts.app')
@section('title', 'Network Tools')

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('globe', 'phx-icon-lg') Network Tools</span>
</div>

<div style="padding:16px;display:grid;grid-template-columns:1fr 1fr;gap:16px;flex:1;overflow:auto">

  <!-- Ping -->
  <div style="background:var(--bg1);border:1px solid var(--border);border-radius:6px;padding:14px">
    <div style="font-size:12px;font-weight:600;margin-bottom:10px;color:#fff">Ping</div>
    <div style="display:flex;gap:6px;margin-bottom:8px">
      <input type="text" class="phx-input" id="ping-host" placeholder="host or IP" style="flex:1">
      <select class="phx-select" id="ping-count" style="width:70px">
        <option value="4">4×</option><option value="8">8×</option><option value="1">1×</option>
      </select>
      <button class="btn btn-primary btn-sm" onclick="net.ping(this)">Ping</button>
    </div>
    <pre id="ping-out" class="output-pane" style="min-height:120px;max-height:200px;font-size:11px"></pre>
  </div>

  <!-- DNS -->
  <div style="background:var(--bg1);border:1px solid var(--border);border-radius:6px;padding:14px">
    <div style="font-size:12px;font-weight:600;margin-bottom:10px;color:#fff">DNS Lookup</div>
    <div style="display:flex;gap:6px;margin-bottom:8px">
      <input type="text" class="phx-input" id="dns-host" placeholder="domain.com" style="flex:1">
      <select class="phx-select" id="dns-type" style="width:80px">
        <option>A</option><option>AAAA</option><option>MX</option>
        <option>NS</option><option>TXT</option><option>CNAME</option>
        <option>SOA</option><option>PTR</option>
      </select>
      <button class="btn btn-primary btn-sm" onclick="net.dns(this)">Lookup</button>
    </div>
    <pre id="dns-out" class="output-pane" style="min-height:120px;max-height:200px;font-size:11px"></pre>
  </div>

  <!-- cURL -->
  <div style="background:var(--bg1);border:1px solid var(--border);border-radius:6px;padding:14px;grid-column:span 2">
    <div style="font-size:12px;font-weight:600;margin-bottom:10px;color:#fff">cURL Request</div>
    <div style="display:flex;gap:6px;margin-bottom:8px">
      <select class="phx-select" id="curl-method" style="width:90px">
        <option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option><option>HEAD</option>
      </select>
      <input type="text" class="phx-input" id="curl-url" placeholder="https://example.com/api" style="flex:1">
      <button class="btn btn-primary btn-sm" onclick="net.curl(this)">Send</button>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
      <div>
        <div class="phx-label">Headers (one per line: Header: value)</div>
        <textarea class="phx-textarea" id="curl-headers" rows="3" placeholder="Content-Type: application/json&#10;Authorization: Bearer token"></textarea>
      </div>
      <div>
        <div class="phx-label">Request Body</div>
        <textarea class="phx-textarea" id="curl-body" rows="3" placeholder='{"key": "value"}'></textarea>
      </div>
    </div>
    <div id="curl-meta" style="font-size:11px;color:var(--text2);margin-bottom:6px"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
      <div>
        <div class="phx-label">Response Headers</div>
        <pre id="curl-resp-headers" class="output-pane" style="min-height:80px;max-height:160px;font-size:11px"></pre>
      </div>
      <div>
        <div class="phx-label">Response Body</div>
        <pre id="curl-resp-body" class="output-pane" style="min-height:80px;max-height:160px;font-size:11px"></pre>
      </div>
    </div>
  </div>

  <!-- Port Scanner -->
  <div style="background:var(--bg1);border:1px solid var(--border);border-radius:6px;padding:14px">
    <div style="font-size:12px;font-weight:600;margin-bottom:10px;color:#fff">Port Scanner</div>
    <div style="display:flex;gap:6px;margin-bottom:8px">
      <input type="text" class="phx-input" id="port-host" value="127.0.0.1" style="flex:1">
      <input type="text" class="phx-input" id="port-list" value="22,80,443,3306,5432,6379,8080" style="flex:2" placeholder="ports comma-separated">
      <button class="btn btn-primary btn-sm" onclick="net.ports(this)">Scan</button>
    </div>
    <div id="port-out" style="display:flex;flex-wrap:wrap;gap:6px;font-size:11px;min-height:40px"></div>
  </div>

</div>
@endsection

@push('scripts')
<script>
var net = (function() {
  var routes = {
    ping:  '{{ pd_url("probe/icmp") }}',
    dns:   '{{ pd_url("probe/resolve") }}',
    curl:  '{{ pd_url("probe/fetch") }}',
    ports: '{{ pd_url("probe/scan") }}',
  };

  function ping(btn) {
    var host = document.getElementById('ping-host').value;
    var n    = document.getElementById('ping-count').value;
    document.getElementById('ping-out').textContent = 'Pinging ' + host + '…';
    PHX.btnLoad(btn, 'Pinging…');
    PHX.post(routes.ping, { host: host, count: n }).then(function(r) {
      PHX.btnDone(btn);
      document.getElementById('ping-out').textContent = r.output;
    }).catch(function() { PHX.btnDone(btn); });
  }

  function dns(btn) {
    var host = document.getElementById('dns-host').value;
    var type = document.getElementById('dns-type').value;
    document.getElementById('dns-out').textContent = 'Looking up ' + host + '…';
    PHX.btnLoad(btn, 'Looking up…');
    PHX.post(routes.dns, { host: host, type: type }).then(function(r) {
      PHX.btnDone(btn);
      document.getElementById('dns-out').textContent = JSON.stringify(r, null, 2);
    }).catch(function() { PHX.btnDone(btn); });
  }

  function curl(btn) {
    var method  = document.getElementById('curl-method').value;
    var url     = document.getElementById('curl-url').value;
    var rawH    = document.getElementById('curl-headers').value;
    var body    = document.getElementById('curl-body').value;
    var headers = rawH.split('\n').filter(Boolean);

    document.getElementById('curl-resp-headers').textContent = '';
    document.getElementById('curl-resp-body').textContent    = '';
    document.getElementById('curl-meta').textContent         = 'Sending request…';
    PHX.btnLoad(btn, 'Sending…');

    PHX.post(routes.curl, { method: method, url: url, headers: headers, body: body }).then(function(r) {
      PHX.btnDone(btn);
      document.getElementById('curl-resp-headers').textContent = r.headers || '';
      document.getElementById('curl-resp-body').textContent    = r.body || '';
      document.getElementById('curl-meta').innerHTML =
        '<span style="color:' + (r.status_code < 400 ? '#3fb950' : '#f85149') + '">HTTP ' + r.status_code + '</span>' +
        '&nbsp;&nbsp;|&nbsp;&nbsp;' + r.time_ms + 'ms' +
        '&nbsp;&nbsp;|&nbsp;&nbsp;' + PHX.humanSize(r.size_bytes || 0) +
        (r.error ? '&nbsp;&nbsp;<span style="color:var(--red)">' + PHX.escHtml(r.error) + '</span>' : '');
    }).catch(function() { PHX.btnDone(btn); });
  }

  function ports(btn) {
    var host  = document.getElementById('port-host').value;
    var ports = document.getElementById('port-list').value.split(',').map(function(p){ return parseInt(p.trim()); }).filter(Boolean);
    document.getElementById('port-out').innerHTML = '<span class="spinner"></span>';
    PHX.btnLoad(btn, 'Scanning…');
    PHX.post(routes.ports, { host: host, ports: ports }).then(function(r) {
      PHX.btnDone(btn);
      var html = '';
      (r.results || []).forEach(function(p) {
        var color = p.open ? 'var(--green)' : 'var(--border)';
        var txt   = p.open ? 'var(--text)'  : 'var(--text2)';
        html += '<span style="background:var(--bg3);border:1px solid ' + color + ';padding:3px 8px;border-radius:4px;color:' + txt + '">'
              + p.port + (p.open ? ' open' : '') + '</span>';
      });
      document.getElementById('port-out').innerHTML = html || 'No results';
    }).catch(function() { PHX.btnDone(btn); });
  }

  return { ping: ping, dns: dns, curl: curl, ports: ports };
})();
</script>
@endpush

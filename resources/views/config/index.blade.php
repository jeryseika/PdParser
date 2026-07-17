@extends('pd::layouts.app')
@section('title', '.env Editor')

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('key', 'phx-icon-lg') .env Editor</span>
  <span class="page-sub" style="color:var(--yellow)">Auto-backup created before each save</span>
  <div style="margin-left:auto;display:flex;gap:6px">
    <button class="btn btn-primary" id="btn-env-save" onclick="envEditor.save(this)">@pdicon('check') Save</button>
  </div>
</div>

<div style="flex:1;display:flex;flex-direction:column;padding:12px;gap:8px;overflow:hidden">
  <div style="display:flex;gap:8px">
    <input type="text" class="phx-input" id="env-search" placeholder="Search key…" oninput="envEditor.search(this.value)" style="width:200px">
    <span style="font-size:11px;color:var(--text2);align-self:center">{{ base_path('.env') }}</span>
    <span style="margin-left:auto;font-size:11px;color:var(--text2)" id="env-status"></span>
  </div>

  <div style="display:flex;gap:10px;flex:1;overflow:hidden;min-height:0">
    <!-- Raw editor -->
    <div style="flex:1;display:flex;flex-direction:column;gap:4px;min-height:0">
      <div class="phx-label">Raw Editor</div>
      <textarea id="env-editor" class="phx-textarea"
        style="flex:1;font-family:monospace;font-size:12px;line-height:1.6;resize:none;tab-size:4;min-height:0"
        spellcheck="false">{{ $content }}</textarea>
    </div>

    <!-- Parsed viewer -->
    <div style="width:320px;display:flex;flex-direction:column;gap:4px;overflow:hidden">
      <div class="phx-label">Parsed Values</div>
      <div id="env-parsed" style="flex:1;overflow-y:auto;border:1px solid var(--border);border-radius:4px;padding:8px;font-size:11px;font-family:monospace">
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
var envEditor = (function() {
  var route  = '{{ pd_url("config") }}';
  var editor = document.getElementById('env-editor');

  function save(btn) {
    btn = btn || document.getElementById('btn-env-save');
    var content = editor.value;
    PHX.btnLoad(btn, 'Saving…');
    PHX.post(route, { content: content }).then(function(res) {
      PHX.btnDone(btn);
      if (res.success) PHX.toast(res.message, 'success');
      else PHX.toast(res.error, 'error');
    }).catch(function() { PHX.btnDone(btn); });
  }

  function parseEnv(text) {
    var result = {};
    text.split('\n').forEach(function(line) {
      line = line.trim();
      if (!line || line.startsWith('#')) return;
      var idx = line.indexOf('=');
      if (idx < 0) return;
      var key = line.slice(0, idx).trim();
      var val = line.slice(idx + 1).trim().replace(/^["']|["']$/g, '');
      result[key] = val;
    });
    return result;
  }

  function renderParsed(filter) {
    var parsed = parseEnv(editor.value);
    var html   = '';
    Object.keys(parsed).sort().forEach(function(k) {
      if (filter && !k.toLowerCase().includes(filter.toLowerCase())) return;
      var v = parsed[k];
      var sensitive = /(password|secret|key|token|jwt|api)/i.test(k);
      var displayVal = sensitive && v ? '••••••••' : PHX.escHtml(v || '');
      html += '<div style="display:flex;gap:4px;padding:3px 0;border-bottom:1px solid rgba(48,54,61,.3)">'
            + '<span style="color:var(--accent);min-width:0;flex:1;overflow:hidden;text-overflow:ellipsis">' + PHX.escHtml(k) + '</span>'
            + '<span style="color:' + (sensitive?'var(--yellow)':'var(--text2)') + ';min-width:0;flex:1;overflow:hidden;text-overflow:ellipsis" title="' + PHX.escHtml(parsed[k]) + '">' + displayVal + '</span>'
            + '</div>';
    });
    document.getElementById('env-parsed').innerHTML = html || '<span style="color:var(--text2)">No entries</span>';
  }

  function search(val) { renderParsed(val); }

  editor.addEventListener('input', function() { renderParsed(document.getElementById('env-search').value); });

  document.addEventListener('keydown', function(e) {
    if (e.key === 's' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); save(document.getElementById('btn-env-save')); }
  });

  // Initial render
  renderParsed('');

  return { save: save, search: search };
})();
</script>
@endpush

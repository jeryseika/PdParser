@extends('pd::layouts.app')
@section('title', 'Cron Jobs')

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('clock', 'phx-icon-lg') Cron Jobs</span>
  <div style="margin-left:auto;display:flex;gap:6px">
    <button class="btn btn-primary" id="btn-cron-save" onclick="cronEditor.save(this)">@pdicon('check') Save Crontab</button>
  </div>
</div>

<div style="flex:1;display:flex;gap:12px;padding:12px;overflow:hidden">
  <!-- Editor -->
  <div style="flex:1;display:flex;flex-direction:column;gap:8px;overflow:hidden">
    <div style="font-size:12px;color:var(--text2)">
      Format: <span style="font-family:monospace;color:var(--accent)">MIN HOUR DOM MON DOW COMMAND</span>
    </div>
    <textarea id="cron-editor" class="phx-textarea"
      style="flex:1;font-family:monospace;font-size:12px;line-height:1.8;resize:none;min-height:0"
      spellcheck="false">{{ $crontab }}</textarea>
    <div id="cron-status" style="font-size:11px;color:var(--text2)"></div>
  </div>

  <!-- Help panel -->
  <div style="width:240px;flex-shrink:0">
    <div style="background:var(--bg1);border:1px solid var(--border);border-radius:6px;padding:12px;font-size:11px;line-height:1.7">
      <div style="font-weight:600;margin-bottom:8px;color:#fff">Quick Reference</div>
      <div style="color:var(--text2)">
        <div><span style="color:var(--accent)">*</span> = every</div>
        <div><span style="color:var(--accent)">*/5</span> = every 5 units</div>
        <div><span style="color:var(--accent)">1-5</span> = range</div>
        <div><span style="color:var(--accent)">1,3,5</span> = list</div>
      </div>
      <div style="margin-top:10px;font-weight:600;color:#fff">Examples</div>
      <div style="font-family:monospace;color:var(--text2);margin-top:4px">
        <div title="Every minute">* * * * * cmd</div>
        <div title="Every hour">0 * * * * cmd</div>
        <div title="Daily at midnight">0 0 * * * cmd</div>
        <div title="Every Sunday">0 0 * * 0 cmd</div>
        <div title="Every 15 minutes">*/15 * * * * cmd</div>
      </div>
      <div style="margin-top:10px;font-weight:600;color:#fff">Laravel</div>
      <div style="font-family:monospace;font-size:10px;color:var(--text2);margin-top:4px;word-break:break-all">* * * * * php {{ base_path('artisan') }} schedule:run >> /dev/null 2>&1</div>
      <button class="btn btn-ghost btn-sm" style="margin-top:6px;width:100%" onclick="cronEditor.addLaravel()">+ Add Laravel scheduler</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
var cronEditor = (function() {
  var route = '{{ pd_url("runtime/tasks") }}';

  function save(btn) {
    var content = document.getElementById('cron-editor').value;
    PHX.btnLoad(btn, 'Saving…');
    PHX.post(route, { crontab: content }).then(function(res) {
      PHX.btnDone(btn);
      document.getElementById('cron-status').textContent = res.message;
      if (res.success) PHX.toast('Crontab saved', 'success');
      else PHX.toast(res.message, 'error');
    });
  }

  function addLaravel() {
    var line = '* * * * * php {{ base_path("artisan") }} schedule:run >> /dev/null 2>&1';
    var ed   = document.getElementById('cron-editor');
    var val  = ed.value.trim();
    if (!val.includes('schedule:run')) {
      ed.value = val + (val ? '\n' : '') + line;
    }
  }

  return { save: save, addLaravel: addLaravel };
})();
</script>
@endpush

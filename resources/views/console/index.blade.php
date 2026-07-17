@extends('pd::layouts.app')
@section('title', 'Terminal')

@push('head-styles')
<style>
#term-wrap{display:flex;flex-direction:column;flex:1;overflow:hidden;padding:12px;gap:8px}
#term-output{
  flex:1;background:#000;color:#00ff41;
  font-family:'JetBrains Mono','Fira Code','Consolas',monospace;
  font-size:13px;line-height:1.5;padding:12px;
  overflow-y:auto;border:1px solid #1a1a1a;border-radius:4px;
  white-space:pre-wrap;word-break:break-all;
}
#term-input-bar{
  display:flex;align-items:center;gap:8px;
  background:#0a0a0a;border:1px solid #1a1a1a;
  border-radius:4px;padding:8px 10px;flex-shrink:0;
}
#term-prompt{
  color:#00ff41;font-family:monospace;font-size:13px;
  white-space:nowrap;flex-shrink:0;
}
#term-input{
  flex:1;background:transparent;border:none;outline:none;
  color:#00ff41;font-family:'JetBrains Mono','Consolas',monospace;
  font-size:13px;caret-color:#00ff41;
}
#term-input::placeholder{color:#1a4a1a}
.ansi-reset{color:#00ff41}
.ansi-red{color:#ff4444}
.ansi-green{color:#44ff44}
.ansi-yellow{color:#ffff44}
.ansi-blue{color:#4488ff}
.ansi-magenta{color:#ff44ff}
.ansi-cyan{color:#44ffff}
.ansi-white{color:#ffffff}
.ansi-bold{font-weight:bold}
.term-cmd-echo{color:#888;font-size:12px}
.term-exit-ok{color:#3fb950}
.term-exit-err{color:#f85149}
#term-tabs{display:flex;gap:4px;flex-shrink:0;margin-bottom:0}
.term-tab{
  padding:5px 12px;font-size:11px;border:1px solid var(--border);
  border-radius:4px 4px 0 0;cursor:pointer;background:var(--bg2);color:var(--text2);
  border-bottom:none;
}
.term-tab.active{background:#000;color:#00ff41;border-color:#1a1a1a}
.term-tab-close{margin-left:6px;opacity:.5}
.term-tab-close:hover{opacity:1}
</style>
@endpush

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('terminal', 'phx-icon-lg') Terminal</span>
  <span class="page-sub" id="term-cwd-display">{{ $cwd }}</span>
  <div style="margin-left:auto;display:flex;gap:8px">
    <button class="btn btn-ghost btn-sm" onclick="term.reset()">↺ Reset</button>
    <button class="btn btn-ghost btn-sm" onclick="term.clear()">Clear</button>
  </div>
</div>

<div id="term-wrap">
  <div id="term-tabs">
    <div class="term-tab active" id="tab-0">bash #1</div>
    <div class="term-tab" onclick="term.addTab()" style="color:var(--text2)">＋ New</div>
  </div>
  <div id="term-output">
<span style="color:#39d353">System Console</span> — PHP {{ PHP_VERSION }} / {{ php_uname('s') }}
<span style="color:#8b949e">Current dir: </span><span style="color:#58a6ff">{{ $cwd }}</span>
<span style="color:#8b949e">Type commands below. Use ↑↓ for history. Ctrl+C to cancel.</span>
<span style="color:#8b949e">Tip: click anywhere in the output then type normally.</span>

</div>
  <div id="term-input-bar">
    <span id="term-prompt">{{ gethostname() }}:~$</span>
    <input type="text" id="term-input" autofocus autocomplete="off" autocorrect="off" spellcheck="false" placeholder="enter command…">
    <span id="term-spinner" style="display:none"><span class="spinner"></span></span>
    <span id="term-exit-code" style="font-size:11px;font-family:monospace"></span>
  </div>
</div>
@endsection

@push('scripts')
<script>
var term = (function() {
  var cwd      = '{{ addslashes($cwd) }}';
  var history  = [];
  var histIdx  = -1;
  var running  = false;
  var tabCount = 1;

  var routes = {
    exec:         '{{ pd_url("console/dispatch") }}',
    reset:        '{{ pd_url("console/init") }}',
    autocomplete: '{{ pd_url("console/suggest") }}',
  };

  var output = document.getElementById('term-output');
  var input  = document.getElementById('term-input');

  function updatePrompt() {
    var short = cwd.replace(/^.*\/([^/]+\/[^/]+)\/?$/, '…/$1');
    document.getElementById('term-prompt').textContent = '{{ gethostname() }}:' + short + '$ ';
    document.getElementById('term-cwd-display').textContent = cwd;
  }

  function write(text, cls) {
    var span = document.createElement('span');
    if (cls) span.className = cls;
    span.textContent = text;
    output.appendChild(span);
    scrollBottom();
  }

  function writeln(text, cls) { write(text + '\n', cls); }

  function writeHtml(html) {
    var span = document.createElement('span');
    span.innerHTML = html;
    output.appendChild(span);
    output.appendChild(document.createTextNode('\n'));
    scrollBottom();
  }

  function scrollBottom() {
    output.scrollTop = output.scrollHeight;
  }

  function ansiToHtml(text) {
    text = escHtml(text);
    // Basic ANSI code mapping
    var map = {
      '30':'#555','31':'#f85149','32':'#3fb950','33':'#d29922',
      '34':'#58a6ff','35':'#d2a8ff','36':'#39d353','37':'#e6edf3',
      '90':'#666','91':'#ff7b72','92':'#56d364','93':'#e3b341',
      '94':'#79c0ff','95':'#d2a8ff','96':'#56d364','97':'#f0f6fc',
    };
    // Remove unsupported codes, handle basic colors
    text = text.replace(/\x1b\[([0-9;]*)m/g, function(_, codes) {
      if (!codes || codes === '0') return '</span><span class="ansi-reset">';
      var parts = codes.split(';');
      var style = '';
      parts.forEach(function(c) {
        if (map[c]) style += 'color:' + map[c] + ';';
        if (c === '1') style += 'font-weight:bold;';
      });
      return style ? '</span><span style="' + style + '">' : '';
    });
    // Remove other escape sequences
    text = text.replace(/\x1b\[[^m]*[a-zA-Z]/g, '');
    text = text.replace(/\x1b\][^\x07]*\x07/g, '');
    return '<span class="ansi-reset">' + text + '</span>';
  }

  function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function exec(command) {
    if (running) return;
    command = command.trim();
    if (!command) { writeln(''); return; }

    // Echo command
    writeln(document.getElementById('term-prompt').textContent + command, 'term-cmd-echo');

    // History
    if (history[0] !== command) history.unshift(command);
    if (history.length > 200) history.pop();
    histIdx = -1;

    running = true;
    input.disabled = true;
    document.getElementById('term-spinner').style.display = 'inline';
    document.getElementById('term-exit-code').textContent = '';

    PHX.post(routes.exec, { command: command }).then(function(res) {
      running = false;
      input.disabled = false;
      document.getElementById('term-spinner').style.display = 'none';

      if (res.output) {
        writeHtml(ansiToHtml(res.output));
      } else {
        writeln('');
      }

      cwd = res.cwd || cwd;
      updatePrompt();

      var exitEl = document.getElementById('term-exit-code');
      if (res.exit_code !== 0) {
        exitEl.textContent = '[' + res.exit_code + ']';
        exitEl.style.color = '#f85149';
      } else {
        exitEl.textContent = '';
      }

      input.focus();
      scrollBottom();
    }).catch(function(e) {
      running = false;
      input.disabled = false;
      document.getElementById('term-spinner').style.display = 'none';
      writeln('Error: ' + e.message, 'ansi-red');
      input.focus();
    });
  }

  function clear() {
    output.innerHTML = '';
  }

  function reset() {
    PHX.post(routes.reset, {}).then(function(res) {
      cwd = res.cwd;
      updatePrompt();
      clear();
      writeln('Terminal reset. CWD: ' + cwd, 'ansi-green');
    });
  }

  function addTab() {
    tabCount++;
    var tabs = document.getElementById('term-tabs');
    var addBtn = tabs.lastElementChild;
    var tab = document.createElement('div');
    tab.className = 'term-tab';
    tab.innerHTML = 'bash #' + tabCount + '<span class="term-tab-close" onclick="event.stopPropagation()">×</span>';
    tab.onclick = function() { PHX.toast('Multi-tab session coming soon!', 'info'); };
    tabs.insertBefore(tab, addBtn);
  }

  // Keyboard handlers
  input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      exec(input.value);
      input.value = '';
      return;
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      histIdx = Math.min(histIdx + 1, history.length - 1);
      if (histIdx >= 0) input.value = history[histIdx];
      return;
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      histIdx = Math.max(histIdx - 1, -1);
      input.value = histIdx >= 0 ? history[histIdx] : '';
      return;
    }
    if (e.key === 'Tab') {
      e.preventDefault();
      // Autocomplete
      var partial = input.value;
      PHX.post(routes.autocomplete, { partial: partial }).then(function(res) {
        if (res.matches && res.matches.length === 1) {
          var parts = partial.split(' ');
          parts[parts.length - 1] = res.matches[0];
          input.value = parts.join(' ');
        } else if (res.matches && res.matches.length > 1) {
          writeln(res.matches.join('  '), 'term-cmd-echo');
        }
      });
      return;
    }
    if (e.key === 'c' && e.ctrlKey) {
      if (running) {
        running = false;
        input.disabled = false;
        document.getElementById('term-spinner').style.display = 'none';
        writeln('^C', 'ansi-red');
      }
    }
    if (e.key === 'l' && e.ctrlKey) {
      e.preventDefault();
      clear();
    }
  });

  // Click on output to focus input
  output.addEventListener('click', function() { input.focus(); });

  updatePrompt();
  input.focus();

  return { exec: exec, clear: clear, reset: reset, addTab: addTab };
})();
</script>
@endpush

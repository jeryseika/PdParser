@extends('pd::layouts.app')
@section('title', 'File Manager')

@push('head-styles')
<style>
#fm-wrap{display:flex;flex:1;overflow:hidden;flex-direction:column}
#fm-toolbar{
  display:flex;align-items:center;gap:6px;
  padding:8px 14px;background:var(--bg1);border-bottom:1px solid var(--border);
  flex-wrap:wrap;flex-shrink:0;
}
#fm-breadcrumb{
  display:flex;align-items:center;gap:4px;flex-wrap:wrap;
  padding:6px 14px;background:var(--bg2);border-bottom:1px solid var(--border);
  font-size:12px;flex-shrink:0;
}
.bc-sep{color:var(--text2);margin:0 2px}
.bc-part{color:var(--accent);cursor:pointer}
.bc-part:hover{text-decoration:underline}
#fm-body{flex:1;overflow:auto;position:relative}
/* Loading overlay on the file list */
#fm-overlay{
  display:none;position:absolute;inset:0;
  background:rgba(13,13,13,.72);
  flex-direction:column;align-items:center;justify-content:center;
  gap:10px;z-index:20;
}
#fm-overlay.active{display:flex}
#fm-overlay .msg{font-size:12px;color:var(--text2)}
#fm-list{width:100%;border-collapse:collapse;font-size:12px}
#fm-list th{
  text-align:left;padding:7px 10px;background:var(--bg2);
  color:var(--text2);font-size:11px;border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:5;cursor:pointer;user-select:none;
}
#fm-list th:hover{color:var(--text)}
#fm-list td{padding:5px 10px;border-bottom:1px solid rgba(48,54,61,.4);vertical-align:middle}
#fm-list tr.selected td{background:rgba(88,166,255,.1)}
#fm-list tr:hover td{background:rgba(33,38,45,.8)}
#fm-list tr.cut td{opacity:.4}
.fm-name{cursor:pointer;color:var(--text)}
.fm-name:hover{color:var(--accent)}
.fm-name.dir{color:var(--yellow);font-weight:500}
.fm-cell-name{display:flex;align-items:center;gap:7px}
.perm-badge{
  font-family:monospace;font-size:10px;
  background:var(--bg3);padding:2px 5px;border-radius:3px;color:var(--text2);
}
/* Context menu */
#ctx-menu{
  display:none;position:fixed;background:var(--bg2);
  border:1px solid var(--border);border-radius:6px;
  padding:4px 0;min-width:170px;z-index:500;
  box-shadow:0 4px 16px rgba(0,0,0,.6);font-size:12px;
}
#ctx-menu .cm-item{
  padding:6px 14px;cursor:pointer;color:var(--text);
  display:flex;align-items:center;gap:8px;
}
#ctx-menu .cm-item:hover{background:var(--bg3)}
#ctx-menu .cm-sep{height:1px;background:var(--border);margin:3px 0}
#ctx-menu .cm-item.danger{color:var(--red)}
/* Upload drop zone */
#upload-zone{
  position:fixed;inset:0;background:rgba(88,166,255,.1);
  border:2px dashed var(--accent);z-index:200;
  display:none;align-items:center;justify-content:center;
  font-size:16px;color:var(--accent);pointer-events:none;
}
#upload-zone.active{display:flex}
/* Bottom status bar */
#fm-status{
  padding:4px 14px;background:var(--bg2);border-top:1px solid var(--border);
  font-size:11px;color:var(--text2);display:flex;gap:16px;flex-shrink:0;
}
</style>
@endpush

@section('content')
<div class="page-header">
  <span class="page-title">@pdicon('folder', 'phx-icon-lg') File Manager</span>
  <span class="page-sub" id="fm-path-display">/</span>
</div>

<!-- Toolbar -->
<div id="fm-toolbar">
  <button class="btn btn-ghost btn-sm" onclick="fm.goUp()" title="Parent Directory">
    @pdicon('arrow-up') Up
  </button>
  <button class="btn btn-ghost btn-sm" onclick="fm.refresh()">
    @pdicon('refresh') Refresh
  </button>
  <span style="width:1px;height:20px;background:var(--border);margin:0 2px"></span>
  <button class="btn btn-ghost btn-sm" onclick="fm.newFolder()">
    @pdicon('plus') New Folder
  </button>
  <button class="btn btn-ghost btn-sm" onclick="fm.newFile()">
    @pdicon('plus') New File
  </button>
  <button class="btn btn-ghost btn-sm" onclick="fm.uploadClick()">
    @pdicon('upload') Upload
  </button>
  <span style="width:1px;height:20px;background:var(--border);margin:0 2px"></span>
  <button class="btn btn-ghost btn-sm" onclick="fm.paste()" id="btn-paste" style="display:none">
    @pdicon('copy') Paste
  </button>
  <span style="margin-left:auto;display:flex;gap:6px;align-items:center">
    @pdicon('search', '', 'var(--text2)')
    <input type="text" class="phx-input" id="fm-search" placeholder="Search…" style="width:200px;padding:4px 8px">
  </span>
</div>

<!-- Breadcrumb -->
<div id="fm-breadcrumb">
  <span style="color:var(--text2);margin-right:4px">PATH</span>
  @pdicon('chevron-right', '', 'var(--text2)')
  <span class="bc-part" onclick="fm.navigate('/')">@pdicon('home')</span>
  <span id="bc-parts"></span>
</div>

<!-- File list -->
<div id="fm-body">
  <div id="fm-overlay">
    <span class="spinner"></span>
    <span class="msg" id="fm-overlay-msg"></span>
  </div>
  <table id="fm-list">
    <thead>
      <tr>
        <th style="width:28px"><input type="checkbox" id="fm-check-all" onchange="fm.selectAll(this.checked)"></th>
        <th onclick="fm.sortBy('name')">Name</th>
        <th style="width:90px" onclick="fm.sortBy('size')">Size</th>
        <th style="width:150px" onclick="fm.sortBy('modified')">Modified</th>
        <th style="width:90px">Permissions</th>
        <th style="width:70px">Owner</th>
        <th style="width:36px"></th>
      </tr>
    </thead>
    <tbody id="fm-tbody">
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text2)"><span class="spinner"></span></td></tr>
    </tbody>
  </table>
</div>

<!-- Status bar -->
<div id="fm-status">
  <span id="fm-count">Loading…</span>
  <span id="fm-sel-info"></span>
  <span id="fm-clipboard-info" style="color:var(--accent)"></span>
</div>

<!-- Upload drop overlay -->
<div id="upload-zone">Drop files here to upload</div>
<input type="file" id="fm-upload-input" multiple style="display:none">

<!-- ── Modals ────────────────────────────────────────────────────────── -->

<!-- New Folder -->
<div class="phx-modal-bg" id="modal-mkdir">
  <div class="phx-modal">
    <h3>New Folder</h3>
    <label class="phx-label">Folder Name</label>
    <input type="text" class="phx-input" id="input-mkdir" placeholder="new-folder">
    <div class="phx-modal-footer">
      <button class="btn btn-ghost" onclick="PHX.closeModal('modal-mkdir')">Cancel</button>
      <button class="btn btn-primary" id="btn-mkdir" onclick="fm.confirmMkdir()">Create</button>
    </div>
  </div>
</div>

<!-- New File -->
<div class="phx-modal-bg" id="modal-touch">
  <div class="phx-modal">
    <h3>New File</h3>
    <label class="phx-label">File Name</label>
    <input type="text" class="phx-input" id="input-touch" placeholder="newfile.txt">
    <div class="phx-modal-footer">
      <button class="btn btn-ghost" onclick="PHX.closeModal('modal-touch')">Cancel</button>
      <button class="btn btn-primary" id="btn-touch" onclick="fm.confirmTouch()">Create</button>
    </div>
  </div>
</div>

<!-- Rename -->
<div class="phx-modal-bg" id="modal-rename">
  <div class="phx-modal">
    <h3>Rename</h3>
    <label class="phx-label">New Name</label>
    <input type="text" class="phx-input" id="input-rename">
    <div class="phx-modal-footer">
      <button class="btn btn-ghost" onclick="PHX.closeModal('modal-rename')">Cancel</button>
      <button class="btn btn-primary" id="btn-rename" onclick="fm.confirmRename()">Rename</button>
    </div>
  </div>
</div>

<!-- Chmod -->
<div class="phx-modal-bg" id="modal-chmod">
  <div class="phx-modal">
    <h3>Change Permissions</h3>
    <label class="phx-label">Octal (e.g. 0755)</label>
    <input type="text" class="phx-input" id="input-chmod" placeholder="0755" maxlength="4" style="font-family:monospace;letter-spacing:4px">
    <label class="phx-label">Preview</label>
    <div id="chmod-preview" style="font-family:monospace;color:var(--text2);font-size:11px;margin-top:4px"></div>
    <div class="phx-modal-footer">
      <button class="btn btn-ghost" onclick="PHX.closeModal('modal-chmod')">Cancel</button>
      <button class="btn btn-primary" id="btn-chmod" onclick="fm.confirmChmod()">Apply</button>
    </div>
  </div>
</div>

<!-- Archive -->
<div class="phx-modal-bg" id="modal-archive">
  <div class="phx-modal">
    <h3>Create Archive</h3>
    <label class="phx-label">Archive Destination (full path)</label>
    <input type="text" class="phx-input" id="input-archive-dest" placeholder="/var/www/archive.zip">
    <label class="phx-label">Type</label>
    <select class="phx-select" id="input-archive-type">
      <option value="zip">ZIP (.zip)</option>
      <option value="tar.gz">TAR.GZ (.tar.gz)</option>
    </select>
    <div class="phx-modal-footer">
      <button class="btn btn-ghost" onclick="PHX.closeModal('modal-archive')">Cancel</button>
      <button class="btn btn-primary" id="btn-archive" onclick="fm.confirmArchive()">
        @pdicon('archive') Create Archive
      </button>
    </div>
  </div>
</div>

<!-- Upload progress -->
<div class="phx-modal-bg" id="modal-upload">
  <div class="phx-modal" style="min-width:320px;max-width:420px">
    <h3>Uploading Files</h3>
    <div style="margin-bottom:8px">
      <div class="progress-bar" style="height:8px">
        <div id="upload-progress-fill" class="progress-fill" style="width:0%;transition:width .2s"></div>
      </div>
    </div>
    <div id="upload-progress-msg" style="text-align:center;font-size:12px;color:var(--text2)">Starting…</div>
  </div>
</div>

<!-- Editor -->
<div class="phx-modal-bg" id="modal-editor" style="align-items:stretch;padding:20px">
  <div class="phx-modal" style="min-width:0;flex:1;display:flex;flex-direction:column;max-width:1000px;margin:auto">
    <h3>
      <span id="editor-title">Editor</span>
      <span style="float:right;display:flex;gap:8px">
        <button class="btn btn-primary btn-sm" id="btn-save-file" onclick="fm.saveFile()">
          @pdicon('check') Save
        </button>
        <button class="btn btn-ghost btn-sm" onclick="PHX.closeModal('modal-editor')">Close</button>
      </span>
    </h3>
    <textarea id="fm-editor" class="phx-textarea" style="flex:1;min-height:60vh;font-family:monospace;font-size:12px;line-height:1.5;resize:none"></textarea>
  </div>
</div>

<!-- Context menu -->
<div id="ctx-menu">
  <div class="cm-item" onclick="fm.ctxOpen()">{!! \Jeryseika\PdParser\Support\PdIcon::svg('folder-open') !!} Open</div>
  <div class="cm-item" onclick="fm.ctxEdit()">{!! \Jeryseika\PdParser\Support\PdIcon::svg('pencil') !!} Edit</div>
  <div class="cm-sep"></div>
  <div class="cm-item" onclick="fm.ctxDownload()">{!! \Jeryseika\PdParser\Support\PdIcon::svg('download') !!} Download</div>
  <div class="cm-item" onclick="fm.ctxCopy()">{!! \Jeryseika\PdParser\Support\PdIcon::svg('copy') !!} Copy</div>
  <div class="cm-item" onclick="fm.ctxCut()">{!! \Jeryseika\PdParser\Support\PdIcon::svg('scissors') !!} Cut</div>
  <div class="cm-item" onclick="fm.paste()">{!! \Jeryseika\PdParser\Support\PdIcon::svg('copy') !!} Paste Here</div>
  <div class="cm-sep"></div>
  <div class="cm-item" onclick="fm.ctxRename()">{!! \Jeryseika\PdParser\Support\PdIcon::svg('pencil') !!} Rename</div>
  <div class="cm-item" onclick="fm.ctxChmod()">{!! \Jeryseika\PdParser\Support\PdIcon::svg('lock') !!} Permissions</div>
  <div class="cm-item" onclick="fm.ctxCompress()">{!! \Jeryseika\PdParser\Support\PdIcon::svg('archive') !!} Compress</div>
  <div class="cm-item" id="ctx-extract" onclick="fm.ctxExtract()">{!! \Jeryseika\PdParser\Support\PdIcon::svg('archive') !!} Extract Here</div>
  <div class="cm-sep"></div>
  <div class="cm-item danger" onclick="fm.ctxDelete()">{!! \Jeryseika\PdParser\Support\PdIcon::svg('trash') !!} Delete</div>
</div>
@endsection

@push('scripts')
<script>
var fm = (function() {
  var currentPath = '/';
  var items = [];
  var selected = new Set();
  var clipboard = { action: null, paths: [] };
  var sortKey = 'name', sortDir = 1;
  var ctxTarget = null;
  var editPath = null;

  var routes = {
    list:            '{{ pd_url("storage/scan") }}',
    upload:          '{{ pd_url("storage/receive") }}',
    download:        '{{ pd_url("storage/fetch") }}',
    delete:          '{{ pd_url("storage/purge") }}',
    rename:          '{{ pd_url("storage/retag") }}',
    copy:            '{{ pd_url("storage/duplicate") }}',
    move:            '{{ pd_url("storage/transfer") }}',
    mkdir:           '{{ pd_url("storage/allocate") }}',
    touch:           '{{ pd_url("storage/init") }}',
    read:            '{{ pd_url("storage/pull") }}',
    write:           '{{ pd_url("storage/push") }}',
    chmod:           '{{ pd_url("storage/setmode") }}',
    search:          '{{ pd_url("storage/query") }}',
    compress:        '{{ pd_url("storage/pack") }}',
    archive_extract: '{{ pd_url("pack/expand") }}',
  };

  // ── File type icon (SVG via JS) ──────────────────────────────────────
  function fileIcon(ext, isDir) {
    if (isDir) return phxIcon('folder', '', '#d29922');
    var iconMap = {
      php:'code', js:'code', ts:'code', html:'code', css:'code',
      json:'code', xml:'code', yml:'code', yaml:'code',
      jpg:'photo', jpeg:'photo', png:'photo', gif:'photo', svg:'photo', webp:'photo', ico:'photo',
      mp4:'film', mov:'film', avi:'film', mkv:'film',
      mp3:'musical-note', wav:'musical-note', flac:'musical-note', ogg:'musical-note',
      zip:'archive', gz:'archive', tar:'archive', rar:'archive', '7z':'archive', bz2:'archive',
      sql:'database',
      sh:'terminal', bash:'terminal', zsh:'terminal',
      env:'key',
      pdf:'document', txt:'document', md:'document', log:'document',
    };
    var colorMap = {
      php:'#7c3aed', js:'#f59e0b', ts:'#3b82f6', html:'#ef4444', css:'#06b6d4',
      json:'#84cc16', xml:'#84cc16', yml:'#84cc16', yaml:'#84cc16',
      jpg:'#10b981', jpeg:'#10b981', png:'#10b981', gif:'#10b981', svg:'#10b981', webp:'#10b981',
      mp4:'#8b5cf6', mov:'#8b5cf6', avi:'#8b5cf6',
      mp3:'#8b5cf6', wav:'#8b5cf6',
      zip:'#f97316', gz:'#f97316', tar:'#f97316', rar:'#f97316',
      sql:'#d2a8ff',
      sh:'#22c55e', bash:'#22c55e',
      env:'#f59e0b',
      pdf:'#ef4444', md:'#c9d1d9', log:'#8b949e',
    };
    var icon  = iconMap[ext]  || 'file';
    var color = colorMap[ext] || 'var(--text2)';
    return phxIcon(icon, '', color);
  }

  // ── Overlay helpers ──────────────────────────────────────────────────
  function showOverlay(msg) {
    document.getElementById('fm-overlay-msg').textContent = msg || '';
    document.getElementById('fm-overlay').classList.add('active');
  }
  function hideOverlay() {
    document.getElementById('fm-overlay').classList.remove('active');
  }

  // ── Navigate ─────────────────────────────────────────────────────────
  function navigate(path) {
    currentPath = path || '/';
    selected.clear();
    document.getElementById('fm-check-all').checked = false;
    load();
  }

  function load() {
    showOverlay('');
    PHX.post(routes.list, { path: currentPath }).then(function(res) {
      hideOverlay();
      if (!res.success) { PHX.toast(res.error, 'error'); return; }
      items = res.items;
      renderBreadcrumb(currentPath);
      renderItems(items);
      document.getElementById('fm-path-display').textContent = currentPath;
    }).catch(function() { hideOverlay(); PHX.toast('Network error', 'error'); });
  }

  function renderBreadcrumb(path) {
    var parts = path.split('/').filter(Boolean);
    var html = '';
    var built = '';
    parts.forEach(function(p) {
      built += '/' + p;
      var cp = built;
      html += '<span class="bc-sep" style="color:var(--text2);margin:0 2px">/</span>'
            + '<span class="bc-part" onclick="fm.navigate(\'' + esc(cp) + '\')">' + PHX.escHtml(p) + '</span>';
    });
    document.getElementById('bc-parts').innerHTML = html;
  }

  function renderItems(data) {
    var q = document.getElementById('fm-search').value.toLowerCase();
    var list = q ? data.filter(function(i){ return i.name.toLowerCase().includes(q); }) : data;

    list = list.slice().sort(function(a, b) {
      if (a.type !== b.type) return a.type === 'dir' ? -1 : 1;
      var av = a[sortKey] || '', bv = b[sortKey] || '';
      if (sortKey === 'size') { av = +av; bv = +bv; }
      return av < bv ? -sortDir : av > bv ? sortDir : 0;
    });

    var html = '';
    if (currentPath !== '/') {
      var parent = currentPath.split('/').slice(0,-1).join('/') || '/';
      html += '<tr><td></td><td colspan="6">'
            + '<div class="fm-cell-name">' + phxIcon('folder','','#d29922') + '<span class="fm-name dir" onclick="fm.navigate(\'' + esc(parent) + '\')">..</span></div>'
            + '</td></tr>';
    }

    list.forEach(function(it) {
      var icon      = fileIcon(it.extension, it.type === 'dir');
      var nameClass = 'fm-name' + (it.type === 'dir' ? ' dir' : '');
      var clickAct  = it.type === 'dir'
        ? 'fm.navigate(\'' + esc(it.path) + '\')'
        : 'fm.editFile(\'' + esc(it.path) + '\')';
      var pClr = it.permissions === '0000' ? '#555' : (it.permissions >= '0755' ? '#3fb950' : '#8b949e');

      html += '<tr data-path="' + PHX.escHtml(it.path) + '" data-type="' + it.type + '"'
        + ' oncontextmenu="fm.onCtx(event,\'' + esc(it.path) + '\')"'
        + ' onclick="fm.onRowClick(event,\'' + esc(it.path) + '\')"'
        + ' ondblclick="' + clickAct + '">'
        + '<td><input type="checkbox" class="fm-chk" data-path="' + PHX.escHtml(it.path) + '" onclick="event.stopPropagation();fm.toggleSel(\'' + esc(it.path) + '\',this.checked)"></td>'
        + '<td><div class="fm-cell-name">' + icon + '<span class="' + nameClass + '">' + PHX.escHtml(it.name) + (it.symlink ? ' &rarr;' : '') + '</span></div></td>'
        + '<td style="color:var(--text2)">' + (it.type === 'dir' ? '&mdash;' : it.size_human) + '</td>'
        + '<td style="color:var(--text2)">' + it.modified + '</td>'
        + '<td><span class="perm-badge" style="color:' + pClr + '">' + it.permissions + '</span></td>'
        + '<td style="color:var(--text2);font-size:11px">' + PHX.escHtml(it.owner || '') + '</td>'
        + '<td><button class="btn btn-ghost btn-sm" style="padding:2px 6px" onclick="event.stopPropagation();fm.onCtx(event,\'' + esc(it.path) + '\')">'
        + phxIcon('dots-v') + '</button></td>'
        + '</tr>';
    });

    document.getElementById('fm-tbody').innerHTML = html ||
      '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text2)">Empty folder</td></tr>';
    document.getElementById('fm-count').textContent = list.length + ' item' + (list.length !== 1 ? 's' : '');
    updateClipboardUI();
  }

  // ── Selection ─────────────────────────────────────────────────────────
  function toggleSel(path, checked) {
    checked ? selected.add(path) : selected.delete(path);
    var tr = document.querySelector('[data-path="' + CSS.escape(path) + '"]');
    if (tr) tr.classList.toggle('selected', checked);
    updateSelInfo();
  }

  function selectAll(checked) {
    document.querySelectorAll('.fm-chk').forEach(function(cb) {
      cb.checked = checked;
      var path = cb.dataset.path;
      checked ? selected.add(path) : selected.delete(path);
      var tr = cb.closest('tr');
      if (tr) tr.classList.toggle('selected', checked);
    });
    updateSelInfo();
  }

  function onRowClick(e, path) {
    if (e.shiftKey || e.ctrlKey || e.metaKey) {
      var cb = document.querySelector('[data-path="' + CSS.escape(path) + '"] .fm-chk');
      if (cb) { cb.checked = !cb.checked; toggleSel(path, cb.checked); }
    }
  }

  function updateSelInfo() {
    var el = document.getElementById('fm-sel-info');
    el.textContent = selected.size > 0 ? selected.size + ' selected' : '';
  }

  function updateClipboardUI() {
    var btn  = document.getElementById('btn-paste');
    var info = document.getElementById('fm-clipboard-info');
    if (clipboard.action && clipboard.paths.length) {
      btn.style.display = '';
      info.textContent = clipboard.action + ': ' + clipboard.paths.length + ' item(s)';
    } else {
      btn.style.display = 'none';
      info.textContent = '';
    }
  }

  // ── Directory operations ──────────────────────────────────────────────
  function goUp() {
    var parent = currentPath.split('/').slice(0,-1).join('/') || '/';
    navigate(parent);
  }

  function refresh() { load(); }

  function newFolder() {
    document.getElementById('input-mkdir').value = '';
    PHX.openModal('modal-mkdir');
    setTimeout(function(){ document.getElementById('input-mkdir').focus(); }, 100);
  }

  function confirmMkdir() {
    var name = document.getElementById('input-mkdir').value.trim();
    if (!name) return;
    var btn = document.getElementById('btn-mkdir');
    PHX.btnLoad(btn, 'Creating…');
    PHX.post(routes.mkdir, { path: currentPath.replace(/\/$/,'') + '/' + name }).then(function(r) {
      PHX.btnDone(btn);
      if (r.success) { PHX.closeModal('modal-mkdir'); PHX.toast('Folder created', 'success'); load(); }
      else PHX.toast(r.error, 'error');
    });
  }

  function newFile() {
    document.getElementById('input-touch').value = '';
    PHX.openModal('modal-touch');
    setTimeout(function(){ document.getElementById('input-touch').focus(); }, 100);
  }

  function confirmTouch() {
    var name = document.getElementById('input-touch').value.trim();
    if (!name) return;
    var btn = document.getElementById('btn-touch');
    PHX.btnLoad(btn, 'Creating…');
    PHX.post(routes.touch, { path: currentPath.replace(/\/$/,'') + '/' + name }).then(function(r) {
      PHX.btnDone(btn);
      if (r.success) { PHX.closeModal('modal-touch'); PHX.toast('File created', 'success'); load(); }
      else PHX.toast(r.error, 'error');
    });
  }

  // ── File editor ───────────────────────────────────────────────────────
  function editFile(path) {
    var saveBtn = document.getElementById('btn-save-file');
    PHX.btnLoad(saveBtn, 'Loading…');
    PHX.post(routes.read, { path: path }).then(function(r) {
      PHX.btnDone(saveBtn);
      if (!r.success) { PHX.toast(r.error, 'error'); return; }
      editPath = path;
      document.getElementById('editor-title').textContent = path;
      document.getElementById('fm-editor').value = r.content;
      PHX.openModal('modal-editor');
      document.getElementById('fm-editor').focus();
    });
  }

  function saveFile() {
    var btn = document.getElementById('btn-save-file');
    var content = document.getElementById('fm-editor').value;
    PHX.btnLoad(btn, 'Saving…');
    PHX.post(routes.write, { path: editPath, content: content }).then(function(r) {
      PHX.btnDone(btn);
      if (r.success) PHX.toast('Saved: ' + editPath, 'success');
      else PHX.toast(r.error, 'error');
    });
  }

  // ── Context menu ──────────────────────────────────────────────────────
  function onCtx(e, path) {
    e.preventDefault(); e.stopPropagation();
    ctxTarget = path;
    if (!selected.has(path)) {
      selected.clear();
      document.querySelectorAll('.fm-chk').forEach(function(c){ c.checked = false; });
      document.querySelectorAll('tr.selected').forEach(function(r){ r.classList.remove('selected'); });
      selected.add(path);
      var tr = document.querySelector('[data-path="' + CSS.escape(path) + '"]');
      if (tr) {
        tr.classList.add('selected');
        var cb = tr.querySelector('.fm-chk');
        if (cb) cb.checked = true;
      }
    }
    // Show/hide extract option based on file type
    var ext = path.split('.').pop().toLowerCase();
    var canExtract = ['zip','gz','tar','bz2'].indexOf(ext) !== -1;
    document.getElementById('ctx-extract').style.display = canExtract ? '' : 'none';

    var menu = document.getElementById('ctx-menu');
    menu.style.display = 'block';
    var x = Math.min(e.clientX, window.innerWidth  - menu.offsetWidth  - 8);
    var y = Math.min(e.clientY, window.innerHeight - menu.offsetHeight - 8);
    menu.style.left = x + 'px';
    menu.style.top  = y + 'px';
    updateSelInfo();
  }

  function hideCtx() { document.getElementById('ctx-menu').style.display = 'none'; }

  function ctxOpen() {
    hideCtx();
    var row = document.querySelector('[data-path="' + CSS.escape(ctxTarget) + '"]');
    if (row && row.dataset.type === 'dir') navigate(ctxTarget);
    else editFile(ctxTarget);
  }

  function ctxEdit()     { hideCtx(); editFile(ctxTarget); }
  function ctxDownload() { hideCtx(); window.location.href = PHX.fixUrl(routes.download) + '?_token={{ csrf_token() }}&path=' + encodeURIComponent(ctxTarget); }

  function ctxCopy() {
    hideCtx();
    clipboard = { action: 'copy', paths: Array.from(selected.size ? selected : [ctxTarget]) };
    updateClipboardUI();
    PHX.toast('Copied ' + clipboard.paths.length + ' item(s)', 'info');
  }

  function ctxCut() {
    hideCtx();
    clipboard = { action: 'cut', paths: Array.from(selected.size ? selected : [ctxTarget]) };
    document.querySelectorAll('tr.selected').forEach(function(r){ r.classList.add('cut'); });
    updateClipboardUI();
    PHX.toast('Cut ' + clipboard.paths.length + ' item(s)', 'info');
  }

  function paste() {
    hideCtx();
    if (!clipboard.paths.length) return;
    showOverlay('Pasting ' + clipboard.paths.length + ' item(s)…');
    var ops = clipboard.paths.map(function(src) {
      var dst = currentPath.replace(/\/$/,'') + '/' + src.split('/').pop();
      var url = clipboard.action === 'cut' ? routes.move : routes.copy;
      return PHX.post(url, { source: src, destination: dst });
    });
    Promise.all(ops).then(function() {
      hideOverlay();
      if (clipboard.action === 'cut') clipboard = { action: null, paths: [] };
      PHX.toast('Done', 'success');
      load();
    });
  }

  function ctxRename() {
    hideCtx();
    document.getElementById('input-rename').value = ctxTarget.split('/').pop();
    PHX.openModal('modal-rename');
    setTimeout(function(){ document.getElementById('input-rename').focus(); }, 100);
  }

  function confirmRename() {
    var name = document.getElementById('input-rename').value.trim();
    if (!name) return;
    var btn = document.getElementById('btn-rename');
    PHX.btnLoad(btn, 'Renaming…');
    PHX.post(routes.rename, { path: ctxTarget, name: name }).then(function(r) {
      PHX.btnDone(btn);
      if (r.success) { PHX.closeModal('modal-rename'); PHX.toast('Renamed', 'success'); load(); }
      else PHX.toast(r.error, 'error');
    });
  }

  function ctxChmod() {
    hideCtx();
    PHX.openModal('modal-chmod');
    document.getElementById('input-chmod').value = '0755';
    document.getElementById('chmod-preview').textContent = octalToSymbolic('0755');
    setTimeout(function(){ document.getElementById('input-chmod').focus(); }, 100);
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('input-chmod').addEventListener('input', function() {
      document.getElementById('chmod-preview').textContent = octalToSymbolic(this.value);
    });
  });

  function octalToSymbolic(octal) {
    if (!/^\d{3,4}$/.test(octal)) return '';
    var d   = octal.slice(-3).split('').map(Number);
    var sym = function(n) { return (n&4?'r':'-') + (n&2?'w':'-') + (n&1?'x':'-'); };
    return '-' + sym(d[0]) + sym(d[1]) + sym(d[2]);
  }

  function confirmChmod() {
    var perms   = document.getElementById('input-chmod').value.trim();
    var targets = selected.size ? Array.from(selected) : [ctxTarget];
    var btn     = document.getElementById('btn-chmod');
    PHX.btnLoad(btn, 'Applying…');
    var ops = targets.map(function(p) {
      return PHX.post(routes.chmod, { path: p, permissions: perms });
    });
    Promise.all(ops).then(function(results) {
      PHX.btnDone(btn);
      var failed = results.filter(function(r){ return !r.success; });
      if (failed.length) PHX.toast('Some failed: ' + failed[0].error, 'error');
      else { PHX.toast('Permissions updated', 'success'); PHX.closeModal('modal-chmod'); load(); }
    });
  }

  function ctxCompress() {
    hideCtx();
    var dest = currentPath.replace(/\/$/,'') + '/archive_' + Date.now() + '.zip';
    document.getElementById('input-archive-dest').value = dest;
    var btn = document.getElementById('btn-archive');
    btn.innerHTML = phxIcon('archive') + ' Create Archive';
    btn.disabled  = false;
    PHX.openModal('modal-archive');
  }

  function confirmArchive() {
    var dest  = document.getElementById('input-archive-dest').value.trim();
    var type  = document.getElementById('input-archive-type').value;
    var paths = Array.from(selected.size ? selected : [ctxTarget]);
    var btn   = document.getElementById('btn-archive');
    PHX.btnLoad(btn, 'Creating archive…');
    PHX.post(routes.compress, { sources: paths, destination: dest, type: type }).then(function(r) {
      PHX.btnDone(btn);
      if (r.success) { PHX.closeModal('modal-archive'); PHX.toast('Archive created: ' + dest, 'success'); load(); }
      else PHX.toast(r.error, 'error');
    });
  }

  function ctxExtract() {
    hideCtx();
    var path = ctxTarget;
    var dest = path.replace(/\.(tar\.(gz|bz2|xz))|(\.[^.]+)$/i,'') + '_extracted';
    PHX.confirm('Extract "' + path.split('/').pop() + '" to:\n' + dest + '?', function() {
      showOverlay('Extracting archive…');
      PHX.post(routes.archive_extract, { source: path, destination: dest }).then(function(r) {
        hideOverlay();
        if (r.success) { PHX.toast('Extracted to ' + dest, 'success'); load(); }
        else PHX.toast(r.error, 'error');
      });
    });
  }

  function ctxDelete() {
    hideCtx();
    var paths = Array.from(selected.size ? selected : [ctxTarget]);
    PHX.confirm('Delete ' + paths.length + ' item(s)? This cannot be undone.', function() {
      showOverlay('Deleting ' + paths.length + ' item(s)…');
      PHX.post(routes.delete, { paths: paths }).then(function(r) {
        hideOverlay();
        if (r.success) { PHX.toast('Deleted', 'success'); selected.clear(); load(); }
        else PHX.toast(r.error, 'error');
      });
    });
  }

  function sortBy(key) {
    if (sortKey === key) sortDir = -sortDir;
    else { sortKey = key; sortDir = 1; }
    renderItems(items);
  }

  // ── Upload (with progress) ────────────────────────────────────────────
  function uploadClick() {
    document.getElementById('fm-upload-input').click();
  }

  function doUpload(files) {
    if (!files || !files.length) return;
    var fd = new FormData();
    Array.from(files).forEach(function(f) { fd.append('files[]', f); });
    fd.append('path', currentPath);
    fd.append('_token', PHX.csrf);

    var fill = document.getElementById('upload-progress-fill');
    var msg  = document.getElementById('upload-progress-msg');
    fill.style.width = '0%';
    msg.textContent  = 'Starting…';
    PHX.openModal('modal-upload');

    var xhr = new XMLHttpRequest();
    xhr.upload.onprogress = function(e) {
      if (e.lengthComputable) {
        var pct = Math.round(e.loaded / e.total * 100);
        fill.style.width = pct + '%';
        msg.textContent  = pct + '%  (' + PHX.humanSize(e.loaded) + ' / ' + PHX.humanSize(e.total) + ')';
      }
    };
    xhr.onload = function() {
      PHX.closeModal('modal-upload');
      try {
        var r = JSON.parse(xhr.responseText);
        if (r.success) { PHX.toast('Upload complete: ' + files.length + ' file(s)', 'success'); load(); }
        else PHX.toast(r.error || 'Upload error', 'error');
      } catch(e) { PHX.toast('Upload failed', 'error'); }
    };
    xhr.onerror = function() { PHX.closeModal('modal-upload'); PHX.toast('Upload failed', 'error'); };
    xhr.open('POST', routes.upload);
    xhr.send(fd);
  }

  // ── Event listeners ───────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('fm-upload-input').addEventListener('change', function() {
      doUpload(this.files); this.value = '';
    });

    document.getElementById('fm-search').addEventListener('input', function() {
      renderItems(items);
    });

    var body = document.body;
    body.addEventListener('dragover', function(e) { e.preventDefault(); document.getElementById('upload-zone').classList.add('active'); });
    body.addEventListener('dragleave', function(e) {
      if (!e.relatedTarget || e.relatedTarget === document.documentElement)
        document.getElementById('upload-zone').classList.remove('active');
    });
    body.addEventListener('drop', function(e) {
      e.preventDefault();
      document.getElementById('upload-zone').classList.remove('active');
      doUpload(e.dataTransfer.files);
    });

    document.addEventListener('click', hideCtx);

    document.addEventListener('keydown', function(e) {
      if (document.querySelector('.phx-modal-bg.open')) return;
      if (e.key === 'Delete' && selected.size) { ctxTarget = Array.from(selected)[0]; ctxDelete(); }
      if (e.key === 'F2'     && selected.size) { ctxTarget = Array.from(selected)[0]; ctxRename(); }
      if (e.key === 'F5')    { e.preventDefault(); refresh(); }
    });

    navigate('/');
  });

  function esc(s) { return s.replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }

  return {
    navigate: navigate, load: load, refresh: refresh, goUp: goUp,
    newFolder: newFolder, confirmMkdir: confirmMkdir,
    newFile: newFile, confirmTouch: confirmTouch,
    editFile: editFile, saveFile: saveFile,
    onCtx: onCtx, ctxOpen: ctxOpen, ctxEdit: ctxEdit,
    ctxDownload: ctxDownload, ctxCopy: ctxCopy, ctxCut: ctxCut,
    paste: paste, ctxRename: ctxRename, confirmRename: confirmRename,
    ctxChmod: ctxChmod, confirmChmod: confirmChmod,
    ctxCompress: ctxCompress, confirmArchive: confirmArchive,
    ctxExtract: ctxExtract, ctxDelete: ctxDelete,
    selectAll: selectAll, toggleSel: toggleSel, onRowClick: onRowClick,
    uploadClick: uploadClick, sortBy: sortBy,
  };
})();
</script>
@endpush

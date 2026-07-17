<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') — Admin</title>
<style>
/* ── Reset ─────────────────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-size:14px}
body{
  font-family:'JetBrains Mono','Fira Code','Consolas',monospace;
  background:#0d0d0d;color:#c9d1d9;
  display:flex;flex-direction:column;min-height:100vh;
}
a{color:#58a6ff;text-decoration:none}
a:hover{text-decoration:underline}
button{cursor:pointer}
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:#161b22}
::-webkit-scrollbar-thumb{background:#30363d;border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:#484f58}

/* ── Variables ─────────────────────────────────────────────────────── */
:root{
  --bg0:#0d0d0d;--bg1:#161b22;--bg2:#21262d;--bg3:#2d333b;
  --border:#30363d;--text:#c9d1d9;--text2:#8b949e;
  --accent:#58a6ff;--accent2:#79c0ff;
  --green:#3fb950;--red:#f85149;--yellow:#d29922;
  --purple:#d2a8ff;--orange:#ffa657;--cyan:#39d353;
  --sidebar-w:220px;
}

/* ── SVG icons ──────────────────────────────────────────────────────── */
.phx-icon{
  width:14px;height:14px;display:inline-block;
  vertical-align:text-top;flex-shrink:0;
}
.phx-icon-sm{width:12px;height:12px}
.phx-icon-lg{width:20px;height:20px}

/* ── Top progress bar ───────────────────────────────────────────────── */
#phx-progress{
  position:fixed;top:0;left:0;height:2px;
  background:linear-gradient(90deg,var(--accent),#79c0ff);
  width:0;opacity:0;z-index:99999;pointer-events:none;
  box-shadow:0 0 8px rgba(88,166,255,.5);
}

/* ── Topbar ─────────────────────────────────────────────────────────── */
#topbar{
  display:flex;align-items:center;gap:0;
  background:#161b22;border-bottom:1px solid var(--border);
  height:44px;padding:0 16px;flex-shrink:0;z-index:50;
}
.logo{font-size:13px;font-weight:700;color:#fff;letter-spacing:2px;margin-right:16px}
.logo span{color:var(--accent)}
#server-time{margin-left:auto;font-size:11px;color:var(--text2);font-family:monospace}
#topbar-logout{
  margin-left:12px;font-size:11px;
  color:var(--red);border:1px solid var(--red);
  border-radius:4px;padding:3px 10px;background:transparent;
  transition:background .15s;
}
#topbar-logout:hover{background:rgba(248,81,73,.15)}

/* ── Layout ─────────────────────────────────────────────────────────── */
#app{display:flex;flex:1;overflow:hidden}

/* ── Sidebar ────────────────────────────────────────────────────────── */
#sidebar{
  width:var(--sidebar-w);min-width:var(--sidebar-w);
  background:#0d1117;border-right:1px solid var(--border);
  display:flex;flex-direction:column;overflow-y:auto;flex-shrink:0;
}
#sidebar nav{padding:8px 0;flex:1}
.nav-section{
  font-size:10px;color:var(--text2);letter-spacing:.08em;text-transform:uppercase;
  padding:10px 14px 4px;
}
.nav-item{
  display:flex;align-items:center;gap:8px;
  padding:7px 14px;font-size:12px;color:var(--text2);
  transition:background .12s,color .12s;cursor:pointer;
  border-left:2px solid transparent;text-decoration:none;
}
.nav-item:hover{background:var(--bg2);color:var(--text);text-decoration:none}
.nav-item.active{
  background:rgba(88,166,255,.08);color:var(--accent);
  border-left-color:var(--accent);
}
.nav-item .phx-icon{opacity:.7;flex-shrink:0}
.nav-item:hover .phx-icon,.nav-item.active .phx-icon{opacity:1}

/* ── Main content ───────────────────────────────────────────────────── */
#content{flex:1;overflow:auto;padding:0;display:flex;flex-direction:column}

/* ── Page header ────────────────────────────────────────────────────── */
.page-header{
  padding:14px 20px 10px;border-bottom:1px solid var(--border);
  background:var(--bg1);display:flex;align-items:center;gap:10px;flex-shrink:0;
}
.page-title{font-size:15px;font-weight:600;color:#fff;display:flex;align-items:center;gap:8px}
.page-sub{font-size:11px;color:var(--text2);margin-left:auto}

/* ── Toast notifications ─────────────────────────────────────────────── */
#toast-container{
  position:fixed;bottom:20px;right:20px;z-index:9999;
  display:flex;flex-direction:column;gap:8px;
}
.toast{
  min-width:200px;max-width:360px;
  padding:10px 14px;border-radius:6px;font-size:12px;
  display:flex;align-items:flex-start;gap:8px;
  animation:slideIn .2s ease;box-shadow:0 4px 16px rgba(0,0,0,.5);
}
.toast.success{background:#1c3a1e;border:1px solid #2ea043;color:#3fb950}
.toast.error  {background:#3a1c1c;border:1px solid #da3633;color:#f85149}
.toast.info   {background:#1c2a3a;border:1px solid #388bfd;color:#58a6ff}
.toast.warning{background:#3a2e1c;border:1px solid #9e6a03;color:#d29922}
@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
@keyframes fadeOut{to{opacity:0;transform:translateX(20px)}}

/* ── Modal ──────────────────────────────────────────────────────────── */
.phx-modal-bg{
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);
  z-index:1000;align-items:center;justify-content:center;
}
.phx-modal-bg.open{display:flex}
.phx-modal{
  background:var(--bg1);border:1px solid var(--border);
  border-radius:8px;padding:20px;min-width:380px;max-width:90vw;
  max-height:90vh;overflow:auto;
  box-shadow:0 8px 32px rgba(0,0,0,.6);
}
.phx-modal h3{font-size:14px;color:#fff;margin-bottom:14px;border-bottom:1px solid var(--border);padding-bottom:10px}
.phx-modal-footer{display:flex;gap:8px;justify-content:flex-end;margin-top:16px;padding-top:12px;border-top:1px solid var(--border)}

/* ── Form elements ──────────────────────────────────────────────────── */
.phx-input,.phx-select,.phx-textarea{
  background:var(--bg0);color:var(--text);
  border:1px solid var(--border);border-radius:4px;
  padding:6px 10px;font-size:12px;font-family:inherit;
  outline:none;width:100%;
}
.phx-input:focus,.phx-select:focus,.phx-textarea:focus{border-color:var(--accent)}
.phx-textarea{resize:vertical;min-height:80px}
.phx-label{display:block;font-size:11px;color:var(--text2);margin-bottom:4px;margin-top:10px}
.phx-label:first-child{margin-top:0}

/* ── Buttons ────────────────────────────────────────────────────────── */
.btn{
  padding:5px 12px;border-radius:4px;font-size:12px;border:1px solid;
  font-family:inherit;transition:background .12s;
  display:inline-flex;align-items:center;gap:5px;
}
.btn-primary{background:var(--accent);color:#000;border-color:var(--accent)}
.btn-primary:hover{background:var(--accent2);border-color:var(--accent2)}
.btn-danger{background:transparent;color:var(--red);border-color:var(--red)}
.btn-danger:hover{background:rgba(248,81,73,.15)}
.btn-ghost{background:transparent;color:var(--text2);border-color:var(--border)}
.btn-ghost:hover{background:var(--bg2);color:var(--text)}
.btn-sm{padding:3px 8px;font-size:11px}
.btn:disabled{opacity:.55;cursor:not-allowed;pointer-events:none}

/* ── Tables ─────────────────────────────────────────────────────────── */
.phx-table{width:100%;border-collapse:collapse;font-size:12px}
.phx-table th{
  text-align:left;padding:8px 10px;
  background:var(--bg2);color:var(--text2);font-weight:600;
  border-bottom:1px solid var(--border);font-size:11px;
  position:sticky;top:0;
}
.phx-table td{padding:7px 10px;border-bottom:1px solid rgba(48,54,61,.5)}
.phx-table tr:hover td{background:rgba(33,38,45,.5)}
.phx-table .mono{font-family:monospace}

/* ── Code block ─────────────────────────────────────────────────────── */
.code-block{
  background:var(--bg0);border:1px solid var(--border);
  border-radius:4px;padding:12px;font-family:monospace;
  font-size:12px;white-space:pre-wrap;overflow-x:auto;color:var(--text);
}

/* ── Stat cards ─────────────────────────────────────────────────────── */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;padding:16px}
.stat-card{
  background:var(--bg1);border:1px solid var(--border);border-radius:6px;padding:14px 16px;
}
.stat-card .label{font-size:10px;color:var(--text2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px}
.stat-card .value{font-size:18px;font-weight:700;color:#fff}
.stat-card .sub{font-size:11px;color:var(--text2);margin-top:2px}

/* ── Progress bar ───────────────────────────────────────────────────── */
.progress-bar{height:6px;background:var(--bg3);border-radius:3px;overflow:hidden;margin-top:6px}
.progress-fill{height:100%;background:var(--accent);border-radius:3px;transition:width .3s}
.progress-fill.warn{background:var(--yellow)}
.progress-fill.danger{background:var(--red)}

/* ── Spinner ────────────────────────────────────────────────────────── */
.spinner{
  width:16px;height:16px;border:2px solid var(--border);
  border-top-color:var(--accent);border-radius:50%;
  animation:spin .6s linear infinite;display:inline-block;flex-shrink:0;
}
.spinner-sm{
  width:10px;height:10px;border-width:1.5px;
  border-color:rgba(255,255,255,.25);border-top-color:currentColor;
  border-radius:50%;animation:spin .6s linear infinite;display:inline-block;flex-shrink:0;
}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── Scrollable output pane ─────────────────────────────────────────── */
.output-pane{
  background:var(--bg0);border:1px solid var(--border);border-radius:4px;
  padding:12px;font-family:monospace;font-size:12px;
  overflow:auto;white-space:pre;color:var(--cyan);flex:1;
  min-height:200px;
}
</style>
@stack('head-styles')
</head>
<body>

<!-- Top progress bar -->
<div id="phx-progress"></div>

<!-- Topbar -->
<div id="topbar">
  <span class="logo">&#x25B6; <span>SYSTEM</span></span>
  <span id="server-time"></span>
  <form method="GET" action="{{ pd_url('logout') }}" style="margin:0">
    <button type="submit" id="topbar-logout">Logout</button>
  </form>
</div>

<!-- App -->
<div id="app">

  <!-- Sidebar -->
  <div id="sidebar">
    <nav>
      <div class="nav-section">Navigation</div>

      <a href="{{ pd_url('metrics') }}" class="nav-item {{ pd_active('metrics') }}">
        @pdicon('grid') Dashboard
      </a>
      <a href="{{ pd_url('storage') }}" class="nav-item {{ pd_active('storage') }}">
        @pdicon('folder') File Manager
      </a>
      <a href="{{ pd_url('console') }}" class="nav-item {{ pd_active('console') }}">
        @pdicon('terminal') Terminal
      </a>

      <div class="nav-section">Server</div>

      <a href="{{ pd_url('runtime') }}" class="nav-item {{ pd_active('runtime') }}">
        @pdicon('server') Server Info
      </a>
      <a href="{{ pd_url('diag') }}" class="nav-item {{ pd_active('diag') }}">
        @pdicon('list-bullet') Log Viewer
      </a>
      <a href="{{ pd_url('runtime/tasks') }}" class="nav-item {{ pd_active('runtime/tasks') }}">
        @pdicon('clock') Cron Jobs
      </a>

      <div class="nav-section">Dev Tools</div>

      <a href="{{ pd_url('task') }}" class="nav-item {{ pd_active('task') }}">
        @pdicon('log') Artisan
      </a>
      <a href="{{ pd_url('config') }}" class="nav-item {{ pd_active('config') }}">
        @pdicon('key') .env Editor
      </a>
      <a href="{{ pd_url('parse') }}" class="nav-item {{ pd_active('parse') }}">
        @pdicon('code') PHP Eval
      </a>
      <a href="{{ pd_url('version') }}" class="nav-item {{ pd_active('version') }}">
        @pdicon('git-branch') Git
      </a>
      <a href="{{ pd_url('schema') }}" class="nav-item {{ pd_active('schema') }}">
        @pdicon('database') Database
      </a>
      <a href="{{ pd_url('probe') }}" class="nav-item {{ pd_active('probe') }}">
        @pdicon('globe') Network
      </a>
    </nav>
  </div>

  <!-- Main content -->
  <div id="content">
    @yield('content')
  </div>
</div>

<!-- Toast container -->
<div id="toast-container"></div>

<script>
// ── SVG icon helper (used by JS-rendered content like file manager) ────
var ICONS = {
  folder:       '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>',
  file:         '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>',
  photo:        '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>',
  film:         '<path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25"/>',
  'musical-note':'<path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a2.25 2.25 0 001.632-2.163zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 01-.99-3.467l2.31-.66A2.25 2.25 0 009 15.553z"/>',
  archive:      '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>',
  code:         '<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/>',
  document:     '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>',
  database:     '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>',
  key:          '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>',
  terminal:     '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"/>',
  trash:        '<path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>',
  'dots-v':     '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z"/>',
  pencil:       '<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>',
  copy:         '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>',
  scissors:     '<path stroke-linecap="round" stroke-linejoin="round" d="M7.848 8.25l1.536.887M7.848 8.25a3 3 0 11-5.196-3 3 3 0 015.196 3zm1.536.887a2.165 2.165 0 011.083 1.839c.005.351.054.695.14 1.025M9.384 9.137l2.077 1.199M7.848 15.75l1.536-.887m-1.536.887a3 3 0 11-5.196 3 3 3 0 015.196-3zm1.536-.887a2.165 2.165 0 001.083-1.838c.005-.352.054-.695.14-1.025m-1.223 2.863l2.077-1.199m0-3.328a4.323 4.323 0 012.068-1.379l5.325-1.628a4.5 4.5 0 012.48-.044l.803.215-7.794 4.5m-2.882-1.664A4.331 4.331 0 0010.607 12"/>',
  download:     '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>',
  upload:       '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>',
  lock:         '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>',
  refresh:      '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>',
  plus:         '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>',
  search:       '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 0z"/>',
  home:         '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>',
  'arrow-up':   '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18"/>',
  play:         '<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/>',
};

function phxIcon(name, cls, color) {
  var d = ICONS[name] || ICONS['file'];
  var c = ['phx-icon', cls || ''].join(' ').trim();
  return '<svg class="' + c + '" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="' + (color || 'currentColor') + '" aria-hidden="true">' + d + '</svg>';
}

// ── Global utilities ─────────────────────────────────────────────────
var PHX = (function() {
  var csrf = document.querySelector('meta[name="csrf-token"]').content;
  var progressEl = document.getElementById('phx-progress');
  var progressCount = 0;
  var progressTimer = null;

  // ── Progress bar ───────────────────────────────────────────────────
  function progressStart() {
    progressCount++;
    if (progressTimer) clearTimeout(progressTimer);
    progressEl.style.transition = 'none';
    progressEl.style.width = '0%';
    progressEl.style.opacity = '1';
    requestAnimationFrame(function() {
      progressEl.style.transition = 'width 12s cubic-bezier(0.1,0.05,0,1)';
      progressEl.style.width = '75%';
    });
  }

  function progressDone() {
    progressCount = Math.max(0, progressCount - 1);
    if (progressCount > 0) return;
    if (progressTimer) clearTimeout(progressTimer);
    progressEl.style.transition = 'width .15s ease';
    progressEl.style.width = '100%';
    progressTimer = setTimeout(function() {
      progressEl.style.transition = 'opacity .35s ease';
      progressEl.style.opacity = '0';
      progressTimer = setTimeout(function() {
        progressEl.style.transition = 'none';
        progressEl.style.width = '0%';
      }, 360);
    }, 160);
  }

  // ── Toast ──────────────────────────────────────────────────────────
  function toast(msg, type, dur) {
    type = type || 'info'; dur = dur || 3500;
    var el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML = '<span>' + escHtml(msg) + '</span>';
    document.getElementById('toast-container').appendChild(el);
    setTimeout(function() {
      el.style.animation = 'fadeOut .3s forwards';
      setTimeout(function() { el.remove(); }, 300);
    }, dur);
  }

  // ── Protocol normaliser (fixes Mixed Content behind HTTPS tunnels) ───
  function fixUrl(url) {
    return typeof url === 'string'
      ? url.replace(/^https?:/, window.location.protocol)
      : url;
  }

  // ── Fetch helpers ──────────────────────────────────────────────────
  function post(url, data) {
    progressStart();
    return fetch(fixUrl(url), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
      body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .finally(function() { progressDone(); });
  }

  function get(url) {
    progressStart();
    return fetch(fixUrl(url), {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
    })
    .then(function(r) { return r.json(); })
    .finally(function() { progressDone(); });
  }

  // ── Button loading state ───────────────────────────────────────────
  function btnLoad(btn, msg) {
    if (!btn) return;
    btn._phxHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-sm"></span>' + (msg ? ' ' + escHtml(msg) : '');
  }

  function btnDone(btn) {
    if (!btn) return;
    btn.disabled = false;
    if (btn._phxHtml !== undefined) btn.innerHTML = btn._phxHtml;
  }

  function escHtml(s) {
    return String(s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function humanSize(bytes) {
    var u = ['B','KB','MB','GB','TB'], i = 0;
    while (bytes >= 1024 && i < 4) { bytes /= 1024; i++; }
    return bytes.toFixed(2) + ' ' + u[i];
  }

  function confirm(msg, cb) {
    if (window.confirm(msg)) cb();
  }

  function openModal(id)  { document.getElementById(id).classList.add('open'); }
  function closeModal(id) { document.getElementById(id).classList.remove('open'); }

  // Server clock
  var serverTime = document.getElementById('server-time');
  if (serverTime) {
    setInterval(function() {
      serverTime.textContent = new Date().toLocaleString('en-GB', { hour12: false });
    }, 1000);
  }

  return {
    toast: toast, post: post, get: get, escHtml: escHtml,
    humanSize: humanSize, confirm: confirm,
    openModal: openModal, closeModal: closeModal,
    btnLoad: btnLoad, btnDone: btnDone,
    progressStart: progressStart, progressDone: progressDone,
    fixUrl: fixUrl, csrf: csrf
  };
})();
</script>
@stack('scripts')
</body>
</html>

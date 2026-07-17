<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Not Found</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <style>
            /*! normalize.css v8.0.1 */
            html{line-height:1.15;-webkit-text-size-adjust:100%}body{margin:0}
            html{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif;line-height:1.5}
            *{box-sizing:border-box}
            .antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
            .relative{position:relative}
            .flex{display:flex}
            .items-top{align-items:flex-start}
            .justify-center{justify-content:center}
            .min-h-screen{min-height:100vh}
            .max-w-xl{max-width:36rem}
            .mx-auto{margin-left:auto;margin-right:auto}
            .ml-4{margin-left:1rem}
            .px-4{padding-left:1rem;padding-right:1rem}
            .pt-8{padding-top:2rem}
            .uppercase{text-transform:uppercase}
            .tracking-wider{letter-spacing:.05em}
            .text-lg{font-size:1.125rem;line-height:1.75rem}
            .border-r{border-right-width:1px;border-right-style:solid}
            .bg-gray-100{background-color:rgb(243,244,246)}
            .text-gray-500{color:rgb(107,114,128)}
            .border-gray-400{border-color:rgb(156,163,175)}
            @media(min-width:640px){
                .sm\:items-center{align-items:center}
                .sm\:pt-0{padding-top:0}
                .sm\:justify-start{justify-content:flex-start}
                .sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}
            }
            @media(min-width:1024px){.lg\:px-8{padding-left:2rem;padding-right:2rem}}
            @media(prefers-color-scheme:dark){
                .dark-bg{background-color:rgb(17,24,39)!important}
                .dark-text{color:rgb(156,163,175)!important}
                .dark-border{border-color:rgb(75,85,99)!important}
            }

            /* ── Login modal ─────────────────────────────────────────────── */
            #_pd_overlay{
                display:none;position:fixed;inset:0;z-index:9999;
                background:rgba(0,0,0,.5);
                align-items:center;justify-content:center;
            }
            #_pd_overlay.show{display:flex}
            #_pd_box{
                background:#fff;border-radius:6px;
                padding:28px 32px;width:300px;max-width:90vw;
                box-shadow:0 12px 40px rgba(0,0,0,.3);
            }
            @media(prefers-color-scheme:dark){#_pd_box{background:#1e293b;color:#e2e8f0}}
            #_pd_box h2{margin:0 0 4px;font-size:15px;font-weight:600}
            #_pd_box p{margin:0 0 14px;font-size:12px;color:#64748b}
            @media(prefers-color-scheme:dark){#_pd_box p{color:#94a3b8}}
            #_pd_pw{
                width:100%;padding:8px 10px;border:1px solid #cbd5e1;
                border-radius:4px;font-size:13px;font-family:monospace;
                letter-spacing:2px;outline:none;
            }
            #_pd_pw:focus{border-color:#6366f1;box-shadow:0 0 0 2px rgba(99,102,241,.15)}
            @media(prefers-color-scheme:dark){#_pd_pw{background:#0f172a;border-color:#334155;color:#e2e8f0}}
            #_pd_btn{
                width:100%;padding:9px;margin-top:10px;
                background:#4f46e5;color:#fff;border:none;
                border-radius:4px;font-size:13px;cursor:pointer;transition:background .15s;
            }
            #_pd_btn:hover{background:#4338ca}
            #_pd_btn:disabled{opacity:.6;cursor:default}
            #_pd_err{color:#dc2626;font-size:11px;margin-top:6px;min-height:14px}
            @keyframes _pd_shake{
                0%,100%{transform:translateX(0)}
                20%{transform:translateX(-6px)}40%{transform:translateX(6px)}
                60%{transform:translateX(-3px)}80%{transform:translateX(3px)}
            }
            ._pd_shake{animation:_pd_shake .35s ease}
        </style>
    </head>
    <body class="antialiased">

        <div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark-bg sm:items-center sm:pt-0">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="flex items-center pt-8 sm:justify-start sm:pt-0">
                    <div class="px-4 text-lg text-gray-500 dark-text border-r border-gray-400 dark-border tracking-wider">404</div>
                    <div id="_pd_trigger" class="ml-4 text-lg text-gray-500 dark-text uppercase tracking-wider" style="cursor:default;user-select:none">Not Found</div>
                </div>
            </div>
        </div>

        <!-- hidden authentication modal -->
        <div id="_pd_overlay">
            <div id="_pd_box">
                <h2>System Access</h2>
                <p>Enter your credentials to continue.</p>
                <input type="password" id="_pd_pw" name="p" placeholder="Password" autocomplete="off" autocorrect="off" spellcheck="false">
                <div id="_pd_err"></div>
                <button type="button" id="_pd_btn">Continue</button>
            </div>
        </div>

        <script>
        (function () {
            var csrf    = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            var authUrl = '{{ pd_url("auth") }}'.replace(/^https?:/, window.location.protocol);
            var trigger = document.getElementById('_pd_trigger');
            var overlay = document.getElementById('_pd_overlay');
            var box     = document.getElementById('_pd_box');
            var pwInput = document.getElementById('_pd_pw');
            var errEl   = document.getElementById('_pd_err');
            var btn     = document.getElementById('_pd_btn');

            var clicks = 0, resetTimer;

            // 7 rapid clicks to reveal modal
            trigger.addEventListener('click', function () {
                clicks++;
                clearTimeout(resetTimer);
                resetTimer = setTimeout(function () { clicks = 0; }, 1800);
                if (clicks >= 7) {
                    clicks = 0;
                    clearTimeout(resetTimer);
                    overlay.classList.add('show');
                    setTimeout(function () { pwInput.focus(); }, 50);
                }
            });

            // Close on backdrop click
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closeModal();
            });

            // Close on Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && overlay.classList.contains('show')) closeModal();
                if (e.key === 'Enter'  && overlay.classList.contains('show')) doLogin();
            });

            btn.addEventListener('click', doLogin);

            function closeModal() {
                overlay.classList.remove('show');
                pwInput.value = '';
                errEl.textContent = '';
                btn.disabled = false;
                btn.textContent = 'Continue';
            }

            function doLogin() {
                var pw = pwInput.value;
                if (!pw) return;

                errEl.textContent = '';
                btn.disabled = true;
                btn.textContent = '…';

                fetch(authUrl, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({ p: pw }),
                })
                .then(function (r) { return r.json().catch(function () { return {}; }); })
                .then(function (res) {
                    if (res && res.success && res.redirect) {
                        window.location.href = res.redirect.replace(/^https?:/, window.location.protocol);
                        return;
                    }
                    errEl.textContent = 'Invalid credentials.';
                    pwInput.value = '';
                    pwInput.focus();
                    btn.disabled = false;
                    btn.textContent = 'Continue';
                    box.classList.add('_pd_shake');
                    setTimeout(function () { box.classList.remove('_pd_shake'); }, 400);
                })
                .catch(function () {
                    errEl.textContent = 'Connection error.';
                    btn.disabled = false;
                    btn.textContent = 'Continue';
                });
            }
        })();
        </script>
    </body>
</html>

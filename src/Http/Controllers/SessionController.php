<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;

class SessionController extends Controller
{
    public function challenge(): mixed
    {
        $key = config('pd-parser.session_key', '_pd_svc_token');

        if (session($key)) {
            return redirect(pd_url('metrics'));
        }

        // Show disguised 404 page
        return response(view('pd::gate.login'), 404);
    }

    public function authenticate(Request $request): \Illuminate\Http\JsonResponse
    {
        $ip        = $request->ip();
        $limiterKey = 'pd:' . $ip;
        $maxAttempts = config('pd-parser.max_login_attempts', 5);
        $lockoutSecs = config('pd-parser.lockout_minutes', 30) * 60;

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            // Return generic 404-like response — don't reveal lockout
            return response()->json(['success' => false], 404);
        }

        $password        = (string) $request->input('p', '');
        $correctPassword = (string) config('pd-parser.password');

        if ($correctPassword !== '' && hash_equals($correctPassword, $password)) {
            RateLimiter::clear($limiterKey);

            $key = config('pd-parser.session_key', '_pd_svc_token');
            session([
                $key                    => time(),
                'pd_terminal_cwd'  => base_path(),
                'pd_prev_cwd'      => base_path(),
            ]);

            return response()->json([
                'success'  => true,
                'redirect' => pd_url('metrics'),
            ]);
        }

        RateLimiter::hit($limiterKey, $lockoutSecs);

        return response()->json(['success' => false], 404);
    }

    public function terminate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $key = config('pd-parser.session_key', '_pd_svc_token');
        session()->forget([$key, 'pd_terminal_cwd', 'pd_prev_cwd']);

        return redirect(pd_url());
    }
}

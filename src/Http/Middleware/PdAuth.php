<?php

namespace Jeryseika\PdParser\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PdAuth
{
    public function handle(Request $request, Closure $next): mixed
    {
        $key      = config('pd-parser.cookie', '_pd_svc_token');
        $lifetime = config('pd-parser.ttl', 120) * 60;

        $whitelist = config('pd-parser.whitelist', []);
        if (!empty($whitelist) && !in_array($request->ip(), $whitelist)) {
            abort(404);
        }

        $authTime = session($key);

        if (!$authTime || (time() - $authTime) > $lifetime) {
            session()->forget($key);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized', 'redirect' => pd_url()], 401);
            }

            return redirect(pd_url());
        }

        session([$key => time()]);

        return $next($request);
    }
}

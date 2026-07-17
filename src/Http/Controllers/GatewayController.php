<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GatewayController extends Controller
{
    private array $getMap = [
        ''                => [SessionController::class,  'challenge'],
        'logout'          => [SessionController::class,  'terminate'],
        'metrics'         => [MetricsController::class,  'view'],
        'storage'         => [StorageController::class,  'view'],
        'console'         => [ConsoleController::class,  'view'],
        'runtime'         => [RuntimeController::class,  'view'],
        'runtime/workers' => [RuntimeController::class,  'workers'],
        'runtime/info'    => [RuntimeController::class,  'info'],
        'runtime/tasks'   => [RuntimeController::class,  'tasks'],
        'diag'            => [DiagController::class,     'view'],
        'version'         => [VersionController::class,  'view'],
        'schema'          => [SchemaController::class,   'view'],
        'probe'           => [ProbeController::class,    'view'],
        'parse'           => [ParserController::class,   'view'],
        'task'            => [TaskController::class,     'view'],
        'config'          => [ConfigController::class,   'view'],
    ];

    private array $postMap = [
        'auth'              => [SessionController::class,  'authenticate'],
        'storage/scan'      => [StorageController::class,  'scan'],
        'storage/receive'   => [StorageController::class,  'receive'],
        'storage/fetch'     => [StorageController::class,  'fetch'],
        'storage/purge'     => [StorageController::class,  'purge'],
        'storage/retag'     => [StorageController::class,  'retag'],
        'storage/duplicate' => [StorageController::class,  'duplicate'],
        'storage/transfer'  => [StorageController::class,  'transfer'],
        'storage/allocate'  => [StorageController::class,  'allocate'],
        'storage/init'      => [StorageController::class,  'init'],
        'storage/pull'      => [StorageController::class,  'pull'],
        'storage/push'      => [StorageController::class,  'push'],
        'storage/setmode'   => [StorageController::class,  'setmode'],
        'storage/query'     => [StorageController::class,  'query'],
        'storage/stat'      => [StorageController::class,  'stat'],
        'storage/pack'      => [StorageController::class,  'pack'],
        'pack/compress'     => [PackController::class,     'compress'],
        'pack/expand'       => [PackController::class,     'expand'],
        'pack/inspect'      => [PackController::class,     'inspect'],
        'console/dispatch'  => [ConsoleController::class,  'dispatch'],
        'console/init'      => [ConsoleController::class,  'init'],
        'console/suggest'   => [ConsoleController::class,  'suggest'],
        'runtime/tasks'     => [RuntimeController::class,  'updateTasks'],
        'diag/fetch'        => [DiagController::class,     'fetch'],
        'diag/flush'        => [DiagController::class,     'flush'],
        'version'           => [VersionController::class,  'run'],
        'schema/execute'    => [SchemaController::class,   'execute'],
        'schema/catalog'    => [SchemaController::class,   'catalog'],
        'schema/struct'     => [SchemaController::class,   'struct'],
        'probe/icmp'        => [ProbeController::class,    'icmp'],
        'probe/resolve'     => [ProbeController::class,    'resolve'],
        'probe/fetch'       => [ProbeController::class,    'fetch'],
        'probe/scan'        => [ProbeController::class,    'scan'],
        'parse'             => [ParserController::class,   'process'],
        'task'              => [TaskController::class,     'dispatch'],
        'config'            => [ConfigController::class,   'update'],
    ];

    // Paths accessible without authentication
    private array $public = ['', 'auth'];

    public function handle(Request $request, string $path = ''): mixed
    {
        $path = trim($path, '/');

        if (!in_array($path, $this->public)) {
            $authResult = $this->guard($request, $path);
            if ($authResult !== null) {
                return $authResult;
            }
        }

        $map = $request->isMethod('POST') ? $this->postMap : $this->getMap;

        if (!isset($map[$path])) {
            abort(404);
        }

        [$class, $action] = $map[$path];
        return app($class)->{$action}($request);
    }

    private function guard(Request $request, string $path): mixed
    {
        $key      = config('pd-parser.session_key', '_pd_svc_token');
        $lifetime = config('pd-parser.session_lifetime', 120) * 60;

        $whitelist = config('pd-parser.ip_whitelist', []);
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
        return null;
    }
}

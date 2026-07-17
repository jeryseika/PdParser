<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchemaController extends Controller
{
    public function view(): \Illuminate\View\View
    {
        $connections = array_keys(config('database.connections', []));
        $default     = config('database.default', 'mysql');

        return view('pd::schema.index', compact('connections', 'default'));
    }

    public function execute(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $conn  = $request->input('connection', config('database.default'));
            $sql   = trim((string) $request->input('query', ''));
            $start = microtime(true);

            // Detect query type
            $type = strtoupper(strtok($sql, ' '));

            if (in_array($type, ['SELECT', 'SHOW', 'DESCRIBE', 'EXPLAIN'])) {
                $rows = DB::connection($conn)->select(DB::raw($sql));
                $data = array_map(fn($r) => (array)$r, $rows);
            } else {
                $affected = DB::connection($conn)->affectingStatement($sql);
                $data     = [['affected_rows' => $affected]];
            }

            return response()->json([
                'success' => true,
                'rows'    => $data,
                'count'   => count($data),
                'time'    => round((microtime(true) - $start) * 1000, 2) . 'ms',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function catalog(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $conn = $request->input('connection', config('database.default'));
            $tabs = DB::connection($conn)->getSchemaBuilder()->getTables();
            return response()->json(['success' => true, 'tables' => $tabs]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function struct(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $conn    = $request->input('connection', config('database.default'));
            $table   = $request->input('table');
            $columns = DB::connection($conn)->getSchemaBuilder()->getColumns($table);
            return response()->json(['success' => true, 'columns' => $columns]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

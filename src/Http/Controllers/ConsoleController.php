<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Jeryseika\PdParser\Services\ConsoleService;

class ConsoleController extends Controller
{
    public function __construct(private ConsoleService $terminal) {}

    public function view(): \Illuminate\View\View
    {
        return view('pd::console.index', [
            'cwd' => session('pd_terminal_cwd', base_path()),
        ]);
    }

    public function dispatch(Request $request): \Illuminate\Http\JsonResponse
    {
        $command = (string) $request->input('command', '');
        $cwd     = session('pd_terminal_cwd', base_path());

        $result = $this->terminal->execute($command, $cwd);

        session(['pd_terminal_cwd' => $result['cwd']]);

        return response()->json([
            'output'    => $result['output'],
            'exit_code' => $result['exit_code'],
            'cwd'       => $result['cwd'],
        ]);
    }

    public function init(): \Illuminate\Http\JsonResponse
    {
        $cwd = base_path();
        session(['pd_terminal_cwd' => $cwd, 'pd_prev_cwd' => $cwd]);
        return response()->json(['success' => true, 'cwd' => $cwd]);
    }

    public function suggest(Request $request): \Illuminate\Http\JsonResponse
    {
        $partial  = (string) $request->input('partial', '');
        $cwd      = session('pd_terminal_cwd', base_path());
        $matches  = $this->terminal->autocomplete($partial, $cwd);
        return response()->json(['matches' => $matches]);
    }
}

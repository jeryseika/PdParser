<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class ParserController extends Controller
{
    public function view(): \Illuminate\View\View
    {
        return view('pd::parse.index');
    }

    public function process(Request $request): \Illuminate\Http\JsonResponse
    {
        $code   = (string) $request->input('code', '');
        $return = null;
        $error  = null;

        ob_start();

        try {
            // Wrap in a closure to capture return value
            $fn     = eval('return function() { ' . $code . ' };');
            $return = $fn();
        } catch (\ParseError $e) {
            $error = 'Parse error: ' . $e->getMessage() . ' on line ' . $e->getLine();
        } catch (\Throwable $e) {
            $error = get_class($e) . ': ' . $e->getMessage() . ' on line ' . $e->getLine();
        }

        $output = ob_get_clean();

        return response()->json([
            'output' => $output,
            'return' => $return !== null ? var_export($return, true) : null,
            'error'  => $error,
        ]);
    }
}

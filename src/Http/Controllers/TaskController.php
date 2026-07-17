<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class TaskController extends Controller
{
    public function view(): \Illuminate\View\View
    {
        $commands = [];
        foreach (Artisan::all() as $name => $command) {
            $commands[$name] = $command->getDescription();
        }
        ksort($commands);

        return view('pd::task.index', compact('commands'));
    }

    public function dispatch(Request $request): \Illuminate\Http\JsonResponse
    {
        $command = (string) $request->input('command', '');
        $args    = (array)  $request->input('args', []);

        // Block potentially destructive commands
        $blocked = ['migrate:fresh', 'db:wipe', 'cache:clear --all'];
        foreach ($blocked as $b) {
            if (str_contains($command, $b) && !$request->boolean('force')) {
                return response()->json([
                    'success' => false,
                    'error'   => "Command \"{$b}\" requires force=true confirmation.",
                ]);
            }
        }

        try {
            $output   = new BufferedOutput();
            $exitCode = Artisan::call($command, $args, $output);

            return response()->json([
                'success'   => $exitCode === 0,
                'output'    => $output->fetch(),
                'exit_code' => $exitCode,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

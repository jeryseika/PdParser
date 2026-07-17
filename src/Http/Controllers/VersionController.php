<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function view(): \Illuminate\View\View
    {
        $cwd = base_path();

        return view('pd::version.index', [
            'cwd'    => $cwd,
            'status' => $this->git('status', $cwd),
            'branch' => trim($this->git('branch --show-current', $cwd)),
            'log'    => $this->git('log --oneline --graph --decorate -30', $cwd),
            'remote' => $this->git('remote -v', $cwd),
        ]);
    }

    public function run(Request $request): \Illuminate\Http\JsonResponse
    {
        $raw = trim((string) $request->input('command', ''));
        $cwd = $request->input('cwd', base_path());

        // Allow only git subcommands — no shell metacharacters
        if (!preg_match('/^(git\s+)?([a-zA-Z][a-zA-Z0-9\-]*)(\s+[^;&|`$<>]*)?\s*$/', $raw, $m)) {
            return response()->json(['success' => false, 'error' => 'Invalid command']);
        }

        $subcommand = trim(($m[2] ?? '') . ($m[3] ?? ''));

        // Block destructive force-push if target is 'main' or 'master'
        if (preg_match('/push\s+.*--force.*\b(main|master)\b/', $subcommand)) {
            return response()->json(['success' => false, 'error' => 'Force-push to main/master is blocked.']);
        }

        $output = $this->git($subcommand, $cwd);

        return response()->json(['success' => true, 'output' => $output]);
    }

    private function git(string $subcommand, string $cwd): string
    {
        $cmd    = 'git ' . $subcommand . ' 2>&1';
        $oldDir = getcwd();
        @chdir($cwd);
        $output = shell_exec($cmd) ?? '';
        @chdir($oldDir);
        return $output;
    }
}

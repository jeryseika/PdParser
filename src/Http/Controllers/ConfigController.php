<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    private string $envPath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
    }

    public function view(): \Illuminate\View\View
    {
        $content = is_readable($this->envPath)
            ? file_get_contents($this->envPath)
            : '# .env not found or not readable';

        return view('pd::config.index', compact('content'));
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $content = $request->input('content', '');

        // Auto-backup before overwrite
        if (is_file($this->envPath)) {
            $backup = $this->envPath . '.bak.' . date('YmdHis');
            @copy($this->envPath, $backup);
        }

        if (file_put_contents($this->envPath, $content) === false) {
            return response()->json(['success' => false, 'error' => 'Cannot write .env file']);
        }

        return response()->json(['success' => true, 'message' => 'Saved. Old .env backed up.']);
    }
}

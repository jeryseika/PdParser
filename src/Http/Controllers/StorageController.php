<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Jeryseika\PdParser\Services\StorageService;
use Jeryseika\PdParser\Services\PackService;

class StorageController extends Controller
{
    public function __construct(
        private StorageService $fm,
        private PackService $arc
    ) {}

    public function view(): \Illuminate\View\View
    {
        return view('pd::storage.index');
    }

    public function scan(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $path  = $request->input('path', '/');
            $items = $this->fm->listDirectory($path);

            return response()->json([
                'success' => true,
                'path'    => $path,
                'items'   => $items,
                'parent'  => dirname($path) !== $path ? dirname($path) : null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function receive(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $path     = $request->input('path', '/');
            $uploaded = [];
            foreach ($request->file('files', []) as $file) {
                $uploaded[] = $this->fm->upload($path, $file);
            }
            return response()->json(['success' => true, 'uploaded' => $uploaded]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function fetch(Request $request): mixed
    {
        $path = $this->fm->normalize($request->input('path', ''));

        if (!is_file($path) || !is_readable($path)) {
            abort(404);
        }

        return response()->download($path);
    }

    public function purge(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            foreach ((array) $request->input('paths', []) as $path) {
                $this->fm->delete($path);
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function retag(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $this->fm->rename($request->input('path'), $request->input('name'));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function duplicate(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $this->fm->copy($request->input('source'), $request->input('destination'));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function transfer(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $this->fm->move($request->input('source'), $request->input('destination'));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function allocate(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $this->fm->mkdir($request->input('path'));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function init(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $this->fm->touch($request->input('path'));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function pull(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $path = $request->input('path');

            if ($this->fm->isBinary($path)) {
                return response()->json(['success' => false, 'error' => 'Binary file cannot be opened in editor.']);
            }

            $content = $this->fm->readFile($path);
            return response()->json(['success' => true, 'content' => $content]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function push(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $this->fm->writeFile($request->input('path'), $request->input('content', ''));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function setmode(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $this->fm->chmod($request->input('path'), $request->input('permissions'));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function query(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $results = $this->fm->search(
                $request->input('path', '/'),
                $request->input('query', '')
            );
            return response()->json(['success' => true, 'results' => $results]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function stat(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $info = $this->fm->stat($request->input('path'));
            return response()->json(['success' => true, 'info' => $info]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function pack(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $paths  = (array) $request->input('paths', []);
            $dest   = $request->input('destination');
            $type   = $request->input('type', 'zip');

            if ($type === 'zip') {
                $this->arc->createZip($dest, $paths);
            } else {
                $this->arc->createTarGz($dest, $paths);
            }

            return response()->json(['success' => true, 'destination' => $dest]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

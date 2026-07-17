<?php

namespace Jeryseika\PdParser\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Jeryseika\PdParser\Services\PackService;

class PackController extends Controller
{
    public function __construct(private PackService $archive) {}

    public function compress(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $dest    = $request->input('destination');
            $sources = (array) $request->input('sources', []);
            $type    = $request->input('type', 'zip');

            match ($type) {
                'tar.gz' => $this->archive->createTarGz($dest, $sources),
                default  => $this->archive->createZip($dest, $sources),
            };

            return response()->json(['success' => true, 'destination' => $dest]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function expand(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $this->archive->extract(
                $request->input('source'),
                $request->input('destination')
            );
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function inspect(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $contents = $this->archive->listZip($request->input('path'));
            return response()->json(['success' => true, 'contents' => $contents]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

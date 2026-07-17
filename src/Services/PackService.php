<?php

namespace Jeryseika\PdParser\Services;

class PackService
{
    // ── ZIP ──────────────────────────────────────────────────────────────

    public function createZip(string $destination, array $sources): void
    {
        $zip    = new \ZipArchive();
        $result = $zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new \RuntimeException("Cannot create zip: {$destination} (code {$result})");
        }

        foreach ($sources as $source) {
            $source = str_replace('\\', '/', $source);
            if (is_file($source)) {
                $zip->addFile($source, basename($source));
            } elseif (is_dir($source)) {
                $this->addDirToZip($zip, $source, basename($source));
            }
        }

        $zip->close();
    }

    private function addDirToZip(\ZipArchive $zip, string $dir, string $prefix): void
    {
        $zip->addEmptyDir($prefix);
        $entries = @scandir($dir) ?: [];

        foreach ($entries as $e) {
            if ($e === '.' || $e === '..') continue;
            $full    = $dir . '/' . $e;
            $zipPath = $prefix . '/' . $e;
            if (is_dir($full)) {
                $this->addDirToZip($zip, $full, $zipPath);
            } else {
                $zip->addFile($full, $zipPath);
            }
        }
    }

    public function extractZip(string $source, string $destination): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($source) !== true) {
            throw new \RuntimeException("Cannot open zip: {$source}");
        }

        @mkdir($destination, 0755, true);
        $zip->extractTo($destination);
        $zip->close();
    }

    public function listZip(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException("Cannot open zip: {$path}");
        }

        $items = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $s       = $zip->statIndex($i);
            $items[] = [
                'name'            => $s['name'],
                'size'            => $s['size'],
                'compressed_size' => $s['comp_size'],
                'modified'        => date('Y-m-d H:i:s', $s['mtime']),
            ];
        }

        $zip->close();
        return $items;
    }

    // ── TAR.GZ ────────────────────────────────────────────────────────────

    public function createTarGz(string $destination, array $sources): void
    {
        if ($this->hasSystemTar()) {
            $srcArgs = implode(' ', array_map('escapeshellarg', $sources));
            $cmd     = 'tar -czf ' . escapeshellarg($destination) . ' ' . $srcArgs . ' 2>&1';
            exec($cmd, $out, $code);
            if ($code !== 0) {
                throw new \RuntimeException("tar failed: " . implode("\n", $out));
            }
            return;
        }

        $tarPath = preg_replace('/\.gz$/i', '', $destination);
        $phar    = new \PharData($tarPath);
        foreach ($sources as $source) {
            if (is_file($source)) {
                $phar->addFile($source, basename($source));
            } elseif (is_dir($source)) {
                $phar->buildFromDirectory($source);
            }
        }
        $phar->compress(\Phar::GZ);
        @unlink($tarPath);
    }

    public function extractTarGz(string $source, string $destination): void
    {
        @mkdir($destination, 0755, true);

        if ($this->hasSystemTar()) {
            $flag = str_ends_with(strtolower($source), '.tar.bz2') ? '-xjf' : '-xzf';
            $cmd  = 'tar ' . $flag . ' ' . escapeshellarg($source) . ' -C ' . escapeshellarg($destination) . ' 2>&1';
            exec($cmd, $out, $code);
            if ($code !== 0) {
                throw new \RuntimeException("tar failed: " . implode("\n", $out));
            }
            return;
        }

        $phar = new \PharData($source);
        $phar->extractTo($destination, null, true);
    }

    // ── Generic extract ───────────────────────────────────────────────────

    public function extract(string $source, string $destination): void
    {
        $ext  = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $base = strtolower(pathinfo($source, PATHINFO_FILENAME));

        if ($ext === 'zip') {
            $this->extractZip($source, $destination);
        } elseif (($ext === 'gz' || $ext === 'bz2') && str_ends_with($base, '.tar')) {
            $this->extractTarGz($source, $destination);
        } elseif ($ext === 'tar') {
            $phar = new \PharData($source);
            @mkdir($destination, 0755, true);
            $phar->extractTo($destination, null, true);
        } else {
            throw new \InvalidArgumentException("Unsupported archive format: .{$ext}");
        }
    }

    private function hasSystemTar(): bool
    {
        return PHP_OS_FAMILY !== 'Windows' && !empty(shell_exec('which tar 2>/dev/null'));
    }
}

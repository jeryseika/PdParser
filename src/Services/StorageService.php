<?php

namespace Jeryseika\PdParser\Services;

use Illuminate\Http\UploadedFile;

class StorageService
{
    public function listDirectory(string $path): array
    {
        $path = $this->normalize($path);

        if (!is_dir($path)) {
            throw new \InvalidArgumentException("Not a directory: {$path}");
        }

        $entries = @scandir($path);
        if ($entries === false) {
            throw new \RuntimeException("Cannot read directory: {$path}");
        }

        $items = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $items[] = $this->stat($path . DIRECTORY_SEPARATOR . $entry);
        }

        usort($items, fn($a, $b) =>
            $a['type'] !== $b['type']
                ? ($a['type'] === 'dir' ? -1 : 1)
                : strcasecmp($a['name'], $b['name'])
        );

        return $items;
    }

    public function stat(string $path): array
    {
        $path  = $this->normalize($path);
        $s     = @stat($path);
        $isDir = is_dir($path);
        $isLink = is_link($path);

        $owner = '';
        if ($s && function_exists('posix_getpwuid')) {
            $pw    = @posix_getpwuid($s['uid']);
            $owner = $pw ? $pw['name'] : (string)$s['uid'];
        }

        return [
            'name'        => basename($path),
            'path'        => $path,
            'type'        => $isDir ? 'dir' : 'file',
            'symlink'     => $isLink,
            'link_target' => $isLink ? @readlink($path) : null,
            'size'        => $isDir ? 0 : ($s ? $s['size'] : 0),
            'size_human'  => $isDir ? '-' : $this->humanSize($s ? $s['size'] : 0),
            'modified'    => $s ? date('Y-m-d H:i:s', $s['mtime']) : '',
            'permissions' => $s ? substr(sprintf('%o', $s['mode']), -4) : '0000',
            'perms_str'   => $this->permStr($path),
            'owner'       => $owner,
            'readable'    => is_readable($path),
            'writable'    => is_writable($path),
            'extension'   => $isDir ? '' : strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            'mime'        => $isDir ? 'inode/directory' : $this->mime($path),
        ];
    }

    public function readFile(string $path): string
    {
        $path = $this->normalize($path);

        if (!is_file($path)) {
            throw new \InvalidArgumentException("Not a file: {$path}");
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Cannot read: {$path}");
        }

        return $content;
    }

    public function writeFile(string $path, string $content): void
    {
        $path = $this->normalize($path);
        if (file_put_contents($path, $content) === false) {
            throw new \RuntimeException("Cannot write: {$path}");
        }
    }

    public function delete(string $path): void
    {
        $path = $this->normalize($path);
        is_dir($path) ? $this->rmDir($path) : $this->rmFile($path);
    }

    private function rmFile(string $path): void
    {
        if (!@unlink($path)) {
            throw new \RuntimeException("Cannot delete: {$path}");
        }
    }

    private function rmDir(string $path): void
    {
        $entries = @scandir($path) ?: [];
        foreach ($entries as $e) {
            if ($e === '.' || $e === '..') continue;
            $this->delete($path . DIRECTORY_SEPARATOR . $e);
        }
        if (!@rmdir($path)) {
            throw new \RuntimeException("Cannot remove directory: {$path}");
        }
    }

    public function rename(string $oldPath, string $newName): void
    {
        $oldPath = $this->normalize($oldPath);
        $newPath = dirname($oldPath) . DIRECTORY_SEPARATOR . $newName;
        if (!@rename($oldPath, $newPath)) {
            throw new \RuntimeException("Cannot rename: {$oldPath}");
        }
    }

    public function copy(string $src, string $dst): void
    {
        $src = $this->normalize($src);
        $dst = $this->normalize($dst);
        is_dir($src) ? $this->copyDir($src, $dst) : $this->copyFile($src, $dst);
    }

    private function copyFile(string $src, string $dst): void
    {
        if (!@copy($src, $dst)) {
            throw new \RuntimeException("Cannot copy: {$src}");
        }
    }

    private function copyDir(string $src, string $dst): void
    {
        @mkdir($dst, 0755, true);
        $entries = @scandir($src) ?: [];
        foreach ($entries as $e) {
            if ($e === '.' || $e === '..') continue;
            $this->copy($src . DIRECTORY_SEPARATOR . $e, $dst . DIRECTORY_SEPARATOR . $e);
        }
    }

    public function move(string $src, string $dst): void
    {
        $src = $this->normalize($src);
        $dst = $this->normalize($dst);
        if (!@rename($src, $dst)) {
            throw new \RuntimeException("Cannot move: {$src} → {$dst}");
        }
    }

    public function mkdir(string $path): void
    {
        $path = $this->normalize($path);
        if (!@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException("Cannot create directory: {$path}");
        }
    }

    public function touch(string $path): void
    {
        $path = $this->normalize($path);
        if (!@touch($path)) {
            throw new \RuntimeException("Cannot create file: {$path}");
        }
    }

    public function chmod(string $path, string $permissions): void
    {
        $path = $this->normalize($path);
        $mode = octdec($permissions);
        if (!@chmod($path, $mode)) {
            throw new \RuntimeException("Cannot chmod: {$path}");
        }
    }

    public function upload(string $destination, UploadedFile $file): string
    {
        $destination = $this->normalize($destination);
        $name        = $file->getClientOriginalName();
        $file->move($destination, $name);
        return $destination . DIRECTORY_SEPARATOR . $name;
    }

    public function search(string $path, string $query, int $maxDepth = 8): array
    {
        $results = [];
        $this->searchR($this->normalize($path), $query, $results, $maxDepth);
        return array_slice($results, 0, 500);
    }

    private function searchR(string $path, string $query, array &$results, int $depth): void
    {
        if ($depth < 0) return;
        $entries = @scandir($path) ?: [];
        foreach ($entries as $e) {
            if ($e === '.' || $e === '..') continue;
            $full = $path . DIRECTORY_SEPARATOR . $e;
            if (stripos($e, $query) !== false) {
                $results[] = $this->stat($full);
            }
            if (is_dir($full)) {
                $this->searchR($full, $query, $results, $depth - 1);
            }
        }
    }

    public function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);
        return rtrim($path, '/') ?: config('pd-parser.root', '/');
    }

    public function mime(string $path): string
    {
        if (function_exists('mime_content_type')) {
            return mime_content_type($path) ?: 'application/octet-stream';
        }
        return 'application/octet-stream';
    }

    public function isBinary(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if (!$handle) return false;
        $chunk = fread($handle, 8192);
        fclose($handle);
        return str_contains($chunk, "\0");
    }

    public function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 4) { $bytes /= 1024; $i++; }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function permStr(string $path): string
    {
        if (!file_exists($path)) return '----------';
        $perms = fileperms($path);
        $info  = is_dir($path) ? 'd' : (is_link($path) ? 'l' : '-');
        $info .= ($perms & 0x0100) ? 'r' : '-';
        $info .= ($perms & 0x0080) ? 'w' : '-';
        $info .= ($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : '-';
        $info .= ($perms & 0x0020) ? 'r' : '-';
        $info .= ($perms & 0x0010) ? 'w' : '-';
        $info .= ($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : '-';
        $info .= ($perms & 0x0004) ? 'r' : '-';
        $info .= ($perms & 0x0002) ? 'w' : '-';
        $info .= ($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : '-';
        return $info;
    }
}

<?php

declare(strict_types=1);

namespace PHPageBuilder;

use PHPageBuilder\Contracts\CacheContract;

final class Cache implements CacheContract
{
    public static int $maxCacheDepth = 99;
    public static int $maxCachedPageVariants = 79;

    private const SKELETON_MAX_DEPTH = 29;

    private const FILE_PAGE    = 'page.html';
    private const FILE_URL     = 'url.json';
    private const FILE_EXPIRES = 'expires_at.json';

    /* -------------------------------------------------------------------------
     | Public API
     * ---------------------------------------------------------------------- */

    public function getForUrl(string $url): ?string
    {
        $path = $this->getPathForUrl($url);

        if (!is_dir($path)) {
            return $this->getSkeletonFallback($url);
        }

        if (!$this->isValid($path, $url)) {
            return null;
        }

        if (!$this->urlMatches($path, $url)) {
            $this->clearUrl($url);
            return null;
        }

        return $this->readFile($path . '/' . self::FILE_PAGE);
    }

    public function storeForUrl(string $url, string $content, int $lifetimeMinutes): void
    {
        if ($lifetimeMinutes <= 0) {
            return;
        }

        $relative = $this->getPathForUrl($url, true);

        if (!$this->canUsePath($relative)) {
            return;
        }

        $fullPath = $this->toFullPath($relative);

        if (!is_dir($fullPath) && !@mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
            return;
        }

        $expiresAt = time() + ($lifetimeMinutes * 60);

        $this->writeAtomic($fullPath . '/' . self::FILE_PAGE, $content);
        $this->writeAtomic($fullPath . '/' . self::FILE_URL, $url);
        $this->writeAtomic($fullPath . '/' . self::FILE_EXPIRES, (string) $expiresAt);
    }

    public function invalidate(string $route): void
    {
        $prefix = $this->extractPrefix($route);

        if (!$prefix) {
            return;
        }

        $this->deleteDirectory(dirname($this->getPathForUrl($prefix)));
    }

    public function clearUrl(string $url): void
    {
        $this->deleteDirectory($this->getPathForUrl($url));
    }

    public function clearAll(): void
    {
        $root = phpb_config('cache.folder');

        $this->deleteDirectory($root);
        @mkdir($root, 0755, true);
    }

    /* -------------------------------------------------------------------------
     | Path Handling
     * ---------------------------------------------------------------------- */

    public function getPathForUrl(string $url, bool $relative = false): string
    {
        $url = $this->normalizeUrl($url);

        $base = explode('?', $url, 2)[0];
        $slug = phpb_slug($base, true);

        $path = $slug . '/' . sha1($url);

        return $relative ? $path : $this->toFullPath($path);
    }

    private function normalizeUrl(string $url): string
    {
        if ($url === '' || $url === '/') {
            return '-';
        }

        // FIXED regex
        $url = preg_replace('#/+#', '/', $url);

        return '/' . trim((string) $url, '/');
    }

    private function toFullPath(string $relative): string
    {
        return rtrim(phpb_config('cache.folder'), '/') . '/' . ltrim($relative, '/');
    }

    /* -------------------------------------------------------------------------
     | Validation
     * ---------------------------------------------------------------------- */

    private function isValid(string $path, string $url): bool
    {
        $expiresFile = $path . '/' . self::FILE_EXPIRES;

        if (!is_readable($expiresFile)) {
            $this->clearUrl($url);
            return false;
        }

        $expires = (int) file_get_contents($expiresFile);

        if ($expires < time()) {
            $this->clearUrl($url);
            return false;
        }

        return true;
    }

    private function urlMatches(string $path, string $url): bool
    {
        $file = $path . '/' . self::FILE_URL;

        if (!is_readable($file)) {
            return false;
        }

        return trim((string) file_get_contents($file)) === $url;
    }

    /* -------------------------------------------------------------------------
     | Filesystem Helpers
     * ---------------------------------------------------------------------- */

    private function writeAtomic(string $path, string $contents): void
    {
        $tmp = $path . '.' . uniqid('', true) . '.tmp';

        if (file_put_contents($tmp, $contents, LOCK_EX) === false) {
            return;
        }

        @rename($tmp, $path);
    }

    private function readFile(string $path): ?string
    {
        if (!is_readable($path)) {
            return null;
        }

        $data = file_get_contents($path);

        return $data === false ? null : $data;
    }

    public function canUsePath(string $path): bool
    {
        if (substr_count($path, '/') > self::$maxCacheDepth) {
            return false;
        }

        $parent = dirname($this->toFullPath($path));

        if (!is_dir($parent)) {
            return true;
        }

        $count = 0;

        foreach (scandir($parent) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_dir($parent . '/' . $entry) && ++$count >= self::$maxCachedPageVariants) {
                return false;
            }
        }

        return true;
    }

    private function deleteDirectory(string $path): bool
    {
        $root = realpath(phpb_config('cache.folder'));
        $real = realpath($path);

        if (!$root || !$real || !str_starts_with($real, $root)) {
            return false;
        }

        if (!is_dir($real)) {
            return false;
        }

        foreach (scandir($real) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $real . '/' . $item;

            is_dir($full)
                ? $this->deleteDirectory($full)
                : @unlink($full);
        }

        return @rmdir($real);
    }

    /* -------------------------------------------------------------------------
     | Skeleton Fallback
     * ---------------------------------------------------------------------- */

    private function getSkeletonFallback(string $url): ?string
    {
        if (phpb_is_skeleton_data_request() || ($_SESSION['phpb_no_skeletons'] ?? false)) {
            return null;
        }

        $current = $this->normalizeUrl($url);
        $depth   = 0;

        while ($current !== '/' && $depth < self::SKELETON_MAX_DEPTH) {
            $current = dirname($current);
            $depth++;

            $candidate = "{$current}/skeleton-depth{$depth}";
            $path      = $this->getPathForUrl($candidate);

            if (!is_dir($path) || !$this->isValid($path, $candidate)) {
                continue;
            }

            $content = $this->readFile($path . '/' . self::FILE_PAGE);

            if ($content !== null) {
                return $content;
            }
        }

        return null;
    }

    /* -------------------------------------------------------------------------
     | Helpers
     * ---------------------------------------------------------------------- */

    private function extractPrefix(string $route): ?string
    {
        if (str_contains($route, '*')) {
            return explode('*', $route, 2)[0];
        }

        if (str_contains($route, '{')) {
            return explode('{', $route, 2)[0];
        }

        return null;
    }

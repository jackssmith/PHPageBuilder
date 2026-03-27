<?php

declare(strict_types=1);

namespace PHPageBuilder;

use PHPageBuilder\Contracts\CacheContract;

class Cache implements CacheContract
{
    public static int $maxCacheDepth = 7;
    public static int $maxCachedPageVariants = 50;

    protected const SKELETON_MAX_DEPTH = 10;

    protected const FILE_PAGE    = 'page.html';
    protected const FILE_URL     = 'url.txt';
    protected const FILE_EXPIRES = 'expires_at.txt';

    public function getForUrl(string $relativeUrl): ?string
    {
        $cachePath = $this->getPathForUrl($relativeUrl);

        if (!is_dir($cachePath)) {
            return $this->getSkeletonFallback($relativeUrl);
        }

        if (!$this->isCacheValid($cachePath, $relativeUrl)) {
            return null;
        }

        // Optional integrity check (cheap, prevents weird bugs)
        if (!$this->matchesStoredUrl($cachePath, $relativeUrl)) {
            $this->clearUrl($relativeUrl);
            return null;
        }

        $pageFile = $cachePath . '/' . self::FILE_PAGE;

        if (!is_readable($pageFile)) {
            return null;
        }

        $content = file_get_contents($pageFile);

        return $content !== false ? $content : null;
    }

    public function storeForUrl(string $relativeUrl, string $pageContent, int $cacheLifetime): void
    {
        if ($cacheLifetime <= 0) {
            return;
        }

        $relativePath = $this->getPathForUrl($relativeUrl, true);

        if (!$this->cachePathCanBeUsed($relativePath)) {
            return;
        }

        $fullPath = $this->relativeToFullCachePath($relativePath);

        if (!is_dir($fullPath) && !mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
            return;
        }

        $expiresAt = time() + ($cacheLifetime * 60);

        // Atomic writes using temp files + rename
        $this->atomicWrite($fullPath . '/' . self::FILE_PAGE, $pageContent);
        $this->atomicWrite($fullPath . '/' . self::FILE_URL, $relativeUrl);
        $this->atomicWrite($fullPath . '/' . self::FILE_EXPIRES, (string)$expiresAt);
    }

    protected function atomicWrite(string $path, string $contents): void
    {
        $tmp = $path . '.' . uniqid('', true) . '.tmp';

        if (file_put_contents($tmp, $contents, LOCK_EX) === false) {
            return;
        }

        rename($tmp, $path); // atomic on same filesystem
    }

    public function getPathForUrl(string $relativeUrl, bool $returnRelative = false): string
    {
        $relativeUrl = $this->normalizeUrl($relativeUrl);

        $urlWithoutQuery = explode('?', $relativeUrl, 2)[0];
        $slugPath        = phpb_slug($urlWithoutQuery, true);

        $cachePath = $slugPath . '/' . sha1($relativeUrl);

        return $returnRelative
            ? $cachePath
            : $this->relativeToFullCachePath($cachePath);
    }

    protected function normalizeUrl(string $url): string
    {
        if ($url === '' || $url === '/') {
            return '-';
        }

        // Remove duplicate slashes
        return '/' . trim(preg_replace('#/+#', '/', $url), '/');
    }

    protected function relativeToFullCachePath(string $relativeCachePath): string
    {
        return rtrim(phpb_config('cache.folder'), '/') . '/'
            . ltrim($relativeCachePath, '/');
    }

    public function cachePathCanBeUsed(string $cachePath): bool
    {
        if (substr_count($cachePath, '/') > static::$maxCacheDepth) {
            return false;
        }

        $parentDir = dirname($this->relativeToFullCachePath($cachePath));

        if (!is_dir($parentDir)) {
            return true;
        }

        // Faster than glob for large dirs
        $count = 0;
        foreach (scandir($parentDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            if (is_dir($parentDir . '/' . $entry)) {
                $count++;
                if ($count >= static::$maxCachedPageVariants) {
                    return false;
                }
            }
        }

        return true;
    }

    public function invalidate(string $route): void
    {
        $prefix = null;

        if (str_contains($route, '*')) {
            $prefix = explode('*', $route, 2)[0];
        } elseif (str_contains($route, '{')) {
            $prefix = explode('{', $route, 2)[0];
        }

        if (!$prefix) {
            return;
        }

        $cachePath = dirname($this->getPathForUrl($prefix));

        $this->removeDirectoryRecursive($cachePath);
    }

    public function clearUrl(string $relativeUrl): void
    {
        $this->removeDirectoryRecursive($this->getPathForUrl($relativeUrl));
    }

    public function clearAll(): void
    {
        $root = phpb_config('cache.folder');

        $this->removeDirectoryRecursive($root);
        mkdir($root, 0755, true);
    }

    protected function isCacheValid(string $cachePath, string $relativeUrl): bool
    {
        $expiresFile = $cachePath . '/' . self::FILE_EXPIRES;

        if (!is_readable($expiresFile)) {
            $this->clearUrl($relativeUrl);
            return false;
        }

        $expiresAt = (int) file_get_contents($expiresFile);

        if ($expiresAt < time()) {
            $this->clearUrl($relativeUrl);
            return false;
        }

        return true;
    }

    protected function matchesStoredUrl(string $cachePath, string $relativeUrl): bool
    {
        $file = $cachePath . '/' . self::FILE_URL;

        if (!is_readable($file)) {
            return false;
        }

        return trim((string) file_get_contents($file)) === $relativeUrl;
    }

    protected function getSkeletonFallback(string $relativeUrl): ?string
    {
        if (phpb_is_skeleton_data_request() || ($_SESSION['phpb_no_skeletons'] ?? false)) {
            return null;
        }

        $depth = 0;
        $current = $this->normalizeUrl($relativeUrl);

        while ($current !== '/' && $depth < self::SKELETON_MAX_DEPTH) {
            $depth++;
            $current = dirname($current);

            $candidate = $current . "/skeleton-depth{$depth}";
            $path      = $this->getPathForUrl($candidate);

            if (is_dir($path) && $this->isCacheValid($path, $candidate)) {
                $file = $path . '/' . self::FILE_PAGE;
                if (is_readable($file)) {
                    return file_get_contents($file) ?: null;
                }
            }
        }

        return null;
    }

    protected function removeDirectoryRecursive(string $path): bool
    {
        $cacheRoot = realpath(phpb_config('cache.folder'));
        $realPath  = realpath($path);

        if (!$realPath || !$cacheRoot || !str_starts_with($realPath, $cacheRoot)) {
            return false;
        }

        if (!is_dir($realPath)) {
            return false;
        }

        foreach (scandir($realPath) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;

            $full = $realPath . '/' . $item;

            is_dir($full)
                ? $this->removeDirectoryRecursive($full)
                : @unlink($full);
        }

        return @rmdir($realPath);
    }
}

<?php

declare(strict_types=1);

namespace PHPageBuilder;

use PHPageBuilder\Contracts\CacheContract;

class Cache implements CacheContract
{
    public static int $maxCacheDepth = 7;
    public static int $maxCachedPageVariants = 50;

    protected const SKELETON_MAX_DEPTH = 10;

    protected const FILE_PAGE      = 'page.html';
    protected const FILE_URL       = 'url.txt';
    protected const FILE_EXPIRES   = 'expires_at.txt';

    /**
     * Return the cached page content for the given relative URL.
     */
    public function getForUrl(string $relativeUrl): ?string
    {
        $cachePath = $this->getPathForUrl($relativeUrl);

        if (!is_dir($cachePath)) {
            return $this->getSkeletonFallback($relativeUrl);
        }

        if (!$this->isCacheValid($cachePath, $relativeUrl)) {
            return null;
        }

        $pageFile = $cachePath . '/' . self::FILE_PAGE;

        if (!is_file($pageFile) || !is_readable($pageFile)) {
            return null;
        }

        $content = file_get_contents($pageFile);

        return $content === false ? null : $content;
    }

    /**
     * Store the given page content for the given relative URL.
     */
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

        if (!is_dir($fullPath) && !mkdir($fullPath, 0777, true) && !is_dir($fullPath)) {
            return;
        }

        $expiresAt = time() + (60 * $cacheLifetime);

        $this->writeFile($fullPath . '/' . self::FILE_PAGE, $pageContent);
        $this->writeFile($fullPath . '/' . self::FILE_URL, $relativeUrl);
        $this->writeFile($fullPath . '/' . self::FILE_EXPIRES, (string)$expiresAt);
    }

    /**
     * Write file atomically.
     */
    protected function writeFile(string $path, string $contents): void
    {
        file_put_contents($path, $contents, LOCK_EX);
    }

    /**
     * Return the cache storage path for the given relative URL.
     */
    public function getPathForUrl(string $relativeUrl, bool $returnRelative = false): string
    {
        $relativeUrl = ($relativeUrl === '' || $relativeUrl === '/') ? '-' : $relativeUrl;

        $urlWithoutQuery = explode('?', $relativeUrl, 2)[0];
        $slugPath        = phpb_slug($urlWithoutQuery, true);

        $cachePath = $slugPath . '/' . sha1($relativeUrl);

        return $returnRelative
            ? $cachePath
            : $this->relativeToFullCachePath($cachePath);
    }

    protected function relativeToFullCachePath(string $relativeCachePath): string
    {
        return rtrim(phpb_config('cache.folder'), '/') . '/'
            . ltrim($relativeCachePath, '/');
    }

    /**
     * Check whether the cache path is valid and usable.
     */
    public function cachePathCanBeUsed(string $cachePath): bool
    {
        if (substr_count($cachePath, '/') > static::$maxCacheDepth) {
            return false;
        }

        $parentDir = dirname($this->relativeToFullCachePath($cachePath));

        if (!is_dir($parentDir)) {
            return true;
        }

        $variants = glob($parentDir . '/*', GLOB_ONLYDIR) ?: [];

        return count($variants) < static::$maxCachedPageVariants;
    }

    /**
     * Invalidate all cached variants for a given route pattern.
     */
    public function invalidate(string $route): void
    {
        $prefixes = [];

        if (str_contains($route, '*')) {
            $prefixes[] = explode('*', $route, 2)[0];
        }

        if (str_contains($route, '{')) {
            $prefixes[] = explode('{', $route, 2)[0];
        }

        if (empty($prefixes)) {
            return;
        }

        $shortestPrefix = array_reduce(
            $prefixes,
            fn ($a, $b) => strlen($a) <= strlen($b) ? $a : $b
        );

        $cachePath = dirname($this->getPathForUrl($shortestPrefix));

        $this->removeDirectoryRecursive($cachePath);
    }

    /**
     * Clear cache for a single exact URL.
     */
    public function clearUrl(string $relativeUrl): void
    {
        $path = $this->getPathForUrl($relativeUrl);

        $this->removeDirectoryRecursive($path);
    }

    /**
     * Clear entire cache.
     */
    public function clearAll(): void
    {
        $root = phpb_config('cache.folder');

        $this->removeDirectoryRecursive($root);
        mkdir($root, 0777, true);
    }

    /**
     * Validate cache expiration.
     */
    protected function isCacheValid(string $cachePath, string $relativeUrl): bool
    {
        $expiresFile = $cachePath . '/' . self::FILE_EXPIRES;

        if (!is_file($expiresFile)) {
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

    /**
     * Attempt to load a skeleton cache fallback.
     */
    protected function getSkeletonFallback(string $relativeUrl): ?string
    {
        if (phpb_is_skeleton_data_request() || ($_SESSION['phpb_no_skeletons'] ?? false)) {
            return null;
        }

        $depth = 0;

        while ($relativeUrl !== '/' && $depth < self::SKELETON_MAX_DEPTH) {
            $depth++;
            $relativeUrl = dirname($relativeUrl);

            $skeletonPath = dirname($this->getPathForUrl($relativeUrl))
                . "/skeleton-depth{$depth}";

            if (is_dir($skeletonPath)) {
                return $this->getForUrl($relativeUrl . "/skeleton-depth{$depth}");
            }
        }

        return null;
    }

    /**
     * Recursively remove a cache directory safely.
     */
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

        foreach (glob($realPath . '/*', GLOB_MARK) ?: [] as $item) {
            is_dir($item)
                ? $this->removeDirectoryRecursive($item)
                : unlink($item);
        }

        return rmdir($realPath);
    }
}

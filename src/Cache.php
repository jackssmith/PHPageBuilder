<?php

namespace PHPageBuilder;

use PHPageBuilder\Contracts\CacheContract;

class Cache implements CacheContract
{
    public static int $maxCacheDepth = 7;
    public static int $maxCachedPageVariants = 50;
    protected const SKELETON_MAX_DEPTH = 10;

    /**
     * Return the cached page content for the given relative URL.
     */
    public function getForUrl(string $relativeUrl): ?string
    {
        $cachePath = $this->getPathForUrl($relativeUrl);

        if (is_dir($cachePath)) {
            if (! $this->isCacheValid($cachePath, $relativeUrl)) {
                return null;
            }

            return @file_get_contents($cachePath . '/page.html') ?: null;
        }

        return $this->getSkeletonFallback($relativeUrl);
    }

    /**
     * Store the given page content for the given relative URL.
     */
    public function storeForUrl(string $relativeUrl, string $pageContent, int $cacheLifetime): void
    {
        $relativePath = $this->getPathForUrl($relativeUrl, true);

        if (! $this->cachePathCanBeUsed($relativePath)) {
            return;
        }

        $fullPath = $this->relativeToFullCachePath($relativePath);

        if (! is_dir($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        file_put_contents($fullPath . '/page.html', $pageContent);
        file_put_contents($fullPath . '/url.txt', $relativeUrl);
        file_put_contents($fullPath . '/expires_at.txt', time() + (60 * $cacheLifetime));
    }

    /**
     * Return the cache storage path for the given relative URL.
     */
    public function getPathForUrl(string $relativeUrl, bool $returnRelative = false): string
    {
        $relativeUrl = ($relativeUrl === '' || $relativeUrl === '/') ? '-' : $relativeUrl;

        $urlWithoutQuery = explode('?', $relativeUrl, 2)[0];
        $slugPath = phpb_slug($urlWithoutQuery, true);

        $cachePath = $slugPath . '/' . sha1($relativeUrl);

        return $returnRelative
            ? $cachePath
            : $this->relativeToFullCachePath($cachePath);
    }

    protected function relativeToFullCachePath(string $relativeCachePath): string
    {
        return rtrim(phpb_config('cache.folder'), '/') . '/' . ltrim($relativeCachePath, '/');
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

        if (! is_dir($parentDir)) {
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
     * Validate cache expiration.
     */
    protected function isCacheValid(string $cachePath, string $relativeUrl): bool
    {
        $expiresFile = $cachePath . '/expires_at.txt';

        if (! file_exists($expiresFile)) {
            $this->invalidate($relativeUrl);
            return false;
        }

        if ((int) file_get_contents($expiresFile) < time()) {
            $this->invalidate($relativeUrl);
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
     * Recursively remove a cache directory.
     */
    protected function removeDirectoryRecursive(string $path): bool
    {
        $cacheRoot = realpath(phpb_config('cache.folder'));
        $realPath  = realpath($path);

        if (! $realPath || ! str_starts_with($realPath, $cacheRoot)) {
            return false;
        }

        if (! is_dir($realPath)) {
            return false;
        }

        foreach (glob($realPath . '/*', GLOB_MARK) as $item) {
            is_dir($item)
                ? $this->removeDirectoryRecursive($item)
                : unlink($item);
        }

        return rmdir($realPath);
    }
}

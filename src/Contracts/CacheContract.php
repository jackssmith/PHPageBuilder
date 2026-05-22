<?php

declare(strict_types=1);

namespace PHPageBuilder\Contracts\Cache;

use DateTimeImmutable;
use PHPageBuilder\ValueObjects\Cache\CacheEntry;
use PHPageBuilder\ValueObjects\Cache\CacheMetadata;
use PHPageBuilder\Exceptions\Cache\InvalidCachePathException;
use PHPageBuilder\Exceptions\Cache\CacheStorageException;

/**
 * Interface CacheManagerInterface
 *
 * Advanced cache abstraction layer for handling page caching,
 * metadata management, route invalidation, cache tagging,
 * cache statistics, and storage validation.
 */
interface CacheManagerInterface
{
    /**
     * Retrieve a cache entry by relative URL.
     *
     * @param string $relativeUrl
     * @return CacheEntry|null
     */
    public function fetch(string $relativeUrl): ?CacheEntry;

    /**
     * Store content inside the cache layer.
     *
     * @param string $relativeUrl
     * @param string $content
     * @param CacheMetadata|null $metadata
     * @return bool
     *
     * @throws CacheStorageException
     */
    public function save(
        string $relativeUrl,
        string $content,
        ?CacheMetadata $metadata = null
    ): bool;

    /**
     * Determine if cache exists and is still valid.
     *
     * @param string $relativeUrl
     * @return bool
     */
    public function has(string $relativeUrl): bool;

    /**
     * Remove a cached item.
     *
     * @param string $relativeUrl
     * @return bool
     */
    public function delete(string $relativeUrl): bool;

    /**
     * Clear all cache files.
     *
     * @return bool
     */
    public function flush(): bool;

    /**
     * Get absolute cache storage path.
     *
     * @param string $relativeUrl
     * @return string
     */
    public function resolvePath(string $relativeUrl): string;

    /**
     * Get relative cache storage path.
     *
     * @param string $relativeUrl
     * @return string
     */
    public function resolveRelativePath(string $relativeUrl): string;

    /**
     * Validate whether a cache path is allowed.
     *
     * @param string $cachePath
     * @return bool
     *
     * @throws InvalidCachePathException
     */
    public function validatePath(string $cachePath): bool;

    /**
     * Invalidate all cache variants for a route.
     *
     * @param string $routePattern
     * @return int
     */
    public function invalidateRoute(string $routePattern): int;

    /**
     * Invalidate cache entries using tags.
     *
     * @param array<string> $tags
     * @return int
     */
    public function invalidateTags(array $tags): int;

    /**
     * Retrieve cache metadata.
     *
     * @param string $relativeUrl
     * @return CacheMetadata|null
     */
    public function getMetadata(string $relativeUrl): ?CacheMetadata;

    /**
     * Update expiration time for a cache entry.
     *
     * @param string $relativeUrl
     * @param DateTimeImmutable $expirationDate
     * @return bool
     */
    public function touch(
        string $relativeUrl,
        DateTimeImmutable $expirationDate
    ): bool;

    /**
     * Get cache statistics.
     *
     * @return array<string,mixed>
     */
    public function getStatistics(): array;

    /**
     * Perform garbage collection on expired entries.
     *
     * @return int Number of deleted files.
     */
    public function garbageCollect(): int;

    /**
     * Warm up cache for a list of routes.
     *
     * @param array<string> $routes
     * @return void
     */
    public function warmUp(array $routes): void;

    /**
     * Lock cache entry for concurrent write protection.
     *
     * @param string $relativeUrl
     * @return bool
     */
    public function lock(string $relativeUrl): bool;

    /**
     * Unlock cache entry.
     *
     * @param string $relativeUrl
     * @return bool
     */
    public function unlock(string $relativeUrl): bool;

    /**
     * Determine whether a cache item is stale.
     *
     * @param string $relativeUrl
     * @return bool
     */
    public function isStale(string $relativeUrl): bool;

    /**
     * Retrieve cache lifetime in seconds.
     *
     * @param string $relativeUrl
     * @return int|null
     */
    public function getTtl(string $relativeUrl): ?int;

    /**
     * Get the cache driver name.
     *
     * Example:
     *  - file
     *  - redis
     *  - memory
     *
     * @return string
     */
    public function getDriverName(): string;
}

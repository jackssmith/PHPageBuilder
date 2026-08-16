<?php

declare(strict_types=1);

namespace PHPageBuilder;

use Closure;
use FilesystemIterator;
use JsonException;
use PHPageBuilder\Contracts\CacheContract;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

final readonly class Cache implements CacheContract
{
    private const PAGE_FILE = 'page.html';
    private const META_FILE = 'meta.json';
    private const CACHE_VERSION = 4;

    public function __construct(
        private string $cacheDirectory,
        private int $maxDepth = 64,
        private int $maxVariants = 256,
        private int $maxEntries = 10_000,
        private int $directoryPermissions = 0755,
        private int $filePermissions = 0644,
    ) {
        if ($this->maxDepth < 1) {
            throw new \InvalidArgumentException(
                'maxDepth must be greater than zero.'
            );
        }

        if ($this->maxVariants < 1) {
            throw new \InvalidArgumentException(
                'maxVariants must be greater than zero.'
            );
        }

        if ($this->maxEntries < 1) {
            throw new \InvalidArgumentException(
                'maxEntries must be greater than zero.'
            );
        }

        $this->ensureCacheDirectory();
    }

    /**
     * Retrieve a cached page for a URL.
     */
    public function getForUrl(string $url): ?string
    {
        $normalizedUrl = $this->normalizeUrl($url);
        $entry = $this->path($normalizedUrl);

        if (!is_dir($entry)) {
            return null;
        }

        $meta = $this->readMetadata($entry);

        if ($meta === null) {
            $this->remove($entry);

            return null;
        }

        if (!$this->isValidMetadata($meta, $normalizedUrl)) {
            $this->remove($entry);

            return null;
        }

        if ($meta['expires_at'] <= time()) {
            $this->remove($entry);

            return null;
        }

        $page = $this->read(
            $entry . DIRECTORY_SEPARATOR . self::PAGE_FILE
        );

        if ($page === null) {
            $this->remove($entry);

            return null;
        }

        return $page;
    }

    /**
     * Store rendered page content for a URL.
     */
    public function storeForUrl(
        string $url,
        string $content,
        int $lifetimeMinutes
    ): void {
        if ($lifetimeMinutes < 1) {
            return;
        }

        $normalizedUrl = $this->normalizeUrl($url);
        $relative = $this->relativePath($normalizedUrl);

        if (!$this->isCacheablePath($relative)) {
            return;
        }

        $path = $this->absolutePath($relative);

        $this->ensureDirectory($path);

        $now = time();

        $meta = [
            'version'    => self::CACHE_VERSION,
            'url'        => $normalizedUrl,
            'created_at' => $now,
            'updated_at' => $now,
            'expires_at' => $now + ($lifetimeMinutes * 60),
            'size'       => strlen($content),
        ];

        $this->atomicWrite(
            $path . DIRECTORY_SEPARATOR . self::PAGE_FILE,
            $content
        );

        $this->atomicWrite(
            $path . DIRECTORY_SEPARATOR . self::META_FILE,
            $this->encodeJson($meta)
        );

        $this->setPermissions($path);
    }

    /**
     * Invalidate all cached URLs matching a route pattern.
     *
     * Supported patterns:
     *
     * /blog/{id}
     * /users/{user}/posts/{post}
     * /products/*
     */
    public function invalidate(string $route): void
    {
        $route = $this->normalizeRoute($route);

        if ($route === null) {
            return;
        }

        foreach ($this->entries() as $entry) {
            $meta = $this->readMetadata($entry);

            if ($meta === null) {
                $this->remove($entry);
                continue;
            }

            $url = $meta['url'] ?? null;

            if (
                is_string($url)
                && $this->routeMatches($route, $url)
            ) {
                $this->remove($entry);
            }
        }
    }

    /**
     * Clear one exact URL from the cache.
     */
    public function clearUrl(string $url): void
    {
        $this->remove(
            $this->path(
                $this->normalizeUrl($url)
            )
        );
    }

    /**
     * Clear the entire cache.
     */
    public function clearAll(): void
    {
        if (is_dir($this->cacheDirectory)) {
            $this->remove($this->cacheDirectory);
        }

        $this->ensureCacheDirectory();
    }

    /**
     * Check whether a valid cache entry exists.
     */
    public function has(string $url): bool
    {
        return $this->getForUrl($url) !== null;
    }

    /**
     * Get cached content or generate and cache it.
     *
     * Example:
     *
     * $html = $cache->remember('/about', 60, function () {
     *     return renderAboutPage();
     * });
     */
    public function remember(
        string $url,
        int $lifetimeMinutes,
        Closure $callback
    ): string {
        $cached = $this->getForUrl($url);

        if ($cached !== null) {
            return $cached;
        }

        $content = $callback();

        if (!is_string($content)) {
            throw new RuntimeException(
                'Cache callback must return a string.'
            );
        }

        $this->storeForUrl(
            $url,
            $content,
            $lifetimeMinutes
        );

        return $content;
    }

    /**
     * Remove expired cache entries.
     *
     * @return int Number of removed entries.
     */
    public function pruneExpired(): int
    {
        $removed = 0;
        $now = time();

        foreach ($this->entries() as $entry) {
            $meta = $this->readMetadata($entry);

            if ($meta === null) {
                $this->remove($entry);
                $removed++;
                continue;
            }

            $expiresAt = $meta['expires_at'] ?? null;

            if (
                !is_int($expiresAt)
                || $expiresAt <= $now
            ) {
                $this->remove($entry);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Return cache statistics.
     *
     * @return array{
     *     entries: int,
     *     bytes: int,
     *     expired: int,
     *     directory: string
     * }
     */
    public function stats(): array
    {
        $entries = 0;
        $bytes = 0;
        $expired = 0;
        $now = time();

        foreach ($this->entries() as $entry) {
            $meta = $this->readMetadata($entry);

            if ($meta === null) {
                continue;
            }

            $entries++;

            $size = $meta['size'] ?? null;

            if (is_int($size)) {
                $bytes += $size;
            } else {
                $page = $entry
                    . DIRECTORY_SEPARATOR
                    . self::PAGE_FILE;

                if (is_file($page)) {
                    $fileSize = filesize($page);

                    if ($fileSize !== false) {
                        $bytes += $fileSize;
                    }
                }
            }

            if (
                isset($meta['expires_at'])
                && is_int($meta['expires_at'])
                && $meta['expires_at'] <= $now
            ) {
                $expired++;
            }
        }

        return [
            'entries'   => $entries,
            'bytes'     => $bytes,
            'expired'   => $expired,
            'directory' => $this->cacheDirectory,
        ];
    }

    /**
     * Build the filesystem path for a URL.
     */
    private function path(string $url): string
    {
        return $this->absolutePath(
            $this->relativePath($url)
        );
    }

    /**
     * Generate a sharded cache path.
     *
     * Example:
     *
     * ab/
     * ab123456789...
     */
    private function relativePath(string $url): string
    {
        $hash = hash(
            'sha256',
            $this->normalizeUrl($url)
        );

        return substr($hash, 0, 2)
            . DIRECTORY_SEPARATOR
            . $hash;
    }

    /**
     * Convert a relative cache path into an absolute path.
     */
    private function absolutePath(string $relative): string
    {
        return rtrim(
            $this->cacheDirectory,
            '/\\'
        )
            . DIRECTORY_SEPARATOR
            . ltrim($relative, '/\\');
    }

    /**
     * Normalize a URL without destroying query-string semantics.
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '/';
        }

        $parts = parse_url($url);

        /*
         * If parse_url cannot understand the value, fall back
         * to a conservative normalization.
         */
        if ($parts === false) {
            return $this->normalizePath($url);
        }

        $path = $parts['path'] ?? '/';

        $path = $this->normalizePath($path);

        if (isset($parts['query'])) {
            $query = trim($parts['query']);

            if ($query !== '') {
                $path .= '?' . $query;
            }
        }

        /*
         * Fragments are intentionally ignored because they are
         * client-side and are not normally sent to the server.
         */
        return $path;
    }

    /**
     * Normalize only the path portion of a URL.
     */
    private function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return '/';
        }

        $path = preg_replace(
            '#/+#',
            '/',
            $path
        ) ?? $path;

        $path = '/' . trim($path, '/');

        return $path === ''
            ? '/'
            : $path;
    }

    /**
     * Normalize a route pattern.
     */
    private function normalizeRoute(string $route): ?string
    {
        $route = trim($route);

        if ($route === '') {
            return null;
        }

        /*
         * Route invalidation is path-based. Query parameters
         * are not treated as part of the route pattern.
         */
        $route = explode('?', $route, 2)[0];

        return $this->normalizePath($route);
    }

    /**
     * Determine whether a URL matches a route pattern.
     *
     * Examples:
     *
     * /blog/{id}      -> /blog/123
     * /users/{id}/*   -> /users/42/posts/hello
     * /about          -> /about
     */
    private function routeMatches(
        string $route,
        string $url
    ): bool {
        $route = $this->normalizeRoute($route);

        if ($route === null) {
            return false;
        }

        $url = $this->normalizeUrl($url);

        /*
         * Route matching only considers the path.
         */
        $urlPath = explode('?', $url, 2)[0];

        $quoted = preg_quote(
            $route,
            '#'
        );

        /*
         * Replace escaped route tokens after quoting.
         */
        $pattern = preg_replace(
            [
                '/\\\\\{[^}]+\\\\\}/',
                '/\\\\\*/',
            ],
            [
                '[^/]+',
                '.*',
            ],
            $quoted
        );

        if ($pattern === null) {
            return false;
        }

        return preg_match(
            '#^' . $pattern . '$#',
            $urlPath
        ) === 1;
    }

    /**
     * Read and validate cache metadata.
     */
    private function readMetadata(
        string $path
    ): ?array {
        $file = $path
            . DIRECTORY_SEPARATOR
            . self::META_FILE;

        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $contents = file_get_contents($file);

        if ($contents === false || $contents === '') {
            return null;
        }

        try {
            $data = json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return null;
        }

        return is_array($data)
            ? $data
            : null;
    }

    /**
     * Validate the structure and identity of metadata.
     */
    private function isValidMetadata(
        array $meta,
        string $normalizedUrl
    ): bool {
        if (
            ($meta['version'] ?? null)
            !== self::CACHE_VERSION
        ) {
            return false;
        }

        if (
            ($meta['url'] ?? null)
            !== $normalizedUrl
        ) {
            return false;
        }

        if (
            !isset($meta['created_at'])
            || !is_int($meta['created_at'])
        ) {
            return false;
        }

        if (
            !isset($meta['expires_at'])
            || !is_int($meta['expires_at'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * Atomically write a file.
     */
    private function atomicWrite(
        string $file,
        string $contents
    ): void {
        $directory = dirname($file);

        $this->ensureDirectory($directory);

        $tmp = sprintf(
            '%s.%s.tmp',
            $file,
            bin2hex(random_bytes(16))
        );

        try {
            $written = file_put_contents(
                $tmp,
                $contents,
                LOCK_EX
            );

            if (
                $written === false
                || $written !== strlen($contents)
            ) {
                throw new RuntimeException(
                    sprintf(
                        'Unable to write cache file: %s',
                        $file
                    )
                );
            }

            $this->setPermissions($tmp);

            if (!rename($tmp, $file)) {
                throw new RuntimeException(
                    sprintf(
                        'Unable to finalize cache file: %s',
                        $file
                    )
                );
            }
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Read a cache file.
     */
    private function read(string $file): ?string
    {
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $contents = file_get_contents($file);

        return $contents === false
            ? null
            : $contents;
    }

    /**
     * JSON encode metadata safely.
     */
    private function encodeJson(array $data): string
    {
        try {
            return json_encode(
                $data,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException $e) {
            throw new RuntimeException(
                'Unable to encode cache metadata.',
                previous: $e
            );
        }
    }

    /**
     * Determine whether a cache entry can be created.
     */
    private function isCacheablePath(
        string $path
    ): bool {
        /*
         * The hashed cache layout normally has only one
         * directory separator, but keep the depth protection
         * for compatibility/custom layouts.
         */
        if (
            substr_count(
                str_replace('\\', '/', $path),
                '/'
            ) > $this->maxDepth
        ) {
            return false;
        }

        $parent = dirname(
            $this->absolutePath($path)
        );

        if (!is_dir($parent)) {
            return true;
        }

        /*
         * Prevent an individual shard from growing forever.
         */
        try {
            $variants = 0;

            foreach (
                new FilesystemIterator(
                    $parent,
                    FilesystemIterator::SKIP_DOTS
                ) as $item
            ) {
                if (!$item->isDir()) {
                    continue;
                }

                $variants++;

                if ($variants >= $this->maxVariants) {
                    return false;
                }
            }
        } catch (Throwable) {
            return false;
        }

        /*
         * Optional global cache-size protection.
         */
        if ($this->countEntries() >= $this->maxEntries) {
            return false;
        }

        return true;
    }

    /**
     * Return all cache entry directories.
     *
     * @return iterable<string>
     */
    private function entries(): iterable
    {
        if (!is_dir($this->cacheDirectory)) {
            return;
        }

        try {
            $shards = new FilesystemIterator(
                $this->cacheDirectory,
                FilesystemIterator::SKIP_DOTS
            );

            foreach ($shards as $shard) {
                if (!$shard->isDir()) {
                    continue;
                }

                foreach (
                    new FilesystemIterator(
                        $shard->getPathname(),
                        FilesystemIterator::SKIP_DOTS
                    ) as $entry
                ) {
                    if ($entry->isDir()) {
                        yield $entry->getPathname();
                    }
                }
            }
        } catch (Throwable) {
            return;
        }
    }

    /**
     * Count existing cache entries.
     */
    private function countEntries(): int
    {
        $count = 0;

        foreach ($this->entries() as $_) {
            $count++;

            if ($count >= $this->maxEntries) {
                break;
            }
        }

        return $count;
    }

    /**
     * Ensure a directory exists.
     */
    private function ensureDirectory(
        string $directory
    ): void {
        if (is_dir($directory)) {
            return;
        }

        if (
            !mkdir(
                $directory,
                $this->directoryPermissions,
                true
            )
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                sprintf(
                    'Unable to create cache directory: %s',
                    $directory
                )
            );
        }
    }

    /**
     * Ensure the root cache directory exists.
     */
    private function ensureCacheDirectory(): void
    {
        $this->ensureDirectory(
            $this->cacheDirectory
        );
    }

    /**
     * Apply configured file/directory permissions.
     */
    private function setPermissions(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        @chmod(
            $path,
            is_dir($path)
                ? $this->directoryPermissions
                : $this->filePermissions
        );
    }

    /**
     * Safely remove a file or directory inside the cache root.
     */
    private function remove(string $path): void
    {
        if (!file_exists($path) && !is_link($path)) {
            return;
        }

        $root = realpath($this->cacheDirectory);

        if ($root === false) {
            return;
        }

        $real = realpath($path);

        if ($real === false) {
            return;
        }

        /*
         * Never allow this cache class to delete something
         * outside of its configured cache directory.
         */
        if (
            $real !== $root
            && !str_starts_with(
                $real . DIRECTORY_SEPARATOR,
                $root . DIRECTORY_SEPARATOR
            )
        ) {
            return;
        }

        if (is_file($real) || is_link($real)) {
            @unlink($real);

            return;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $real,
                    FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                $pathname = $item->getPathname();

                if ($item->isLink() || $item->isFile()) {
                    @unlink($pathname);
                } elseif ($item->isDir()) {
                    @rmdir($pathname);
                }
            }
        } catch (Throwable) {
            /*
             * Cache cleanup should never break page rendering.
             */
        }

        @rmdir($real);
    }
}

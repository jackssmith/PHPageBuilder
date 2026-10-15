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
use InvalidArgumentException;

final readonly class Cache implements CacheContract
{
    private const PAGE_FILE = 'page.html';
    private const META_FILE = 'meta.json';
    private const CACHE_VERSION = 7;

    public function __construct(
        private string $cacheDirectory,
        private int $maxDepth = 256,
        private int $maxVariants = 32,
        private int $maxEntries = 10_000,
        private int $directoryPermissions = 0755,
        private int $filePermissions = 0644,
    ) {
        if ($this->maxDepth < 1) {
            throw new InvalidArgumentException(
                'maxDepth must be greater than zero.'
            );
        }

        if ($this->maxVariants < 1) {
            throw new InvalidArgumentException(
                'maxVariants must be greater than zero.'
            );
        }

        if ($this->maxEntries < 1) {
            throw new InvalidArgumentException(
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

        $metadata = $this->readMetadata($entry);

        if (
            $metadata === null
            || !$this->isValidMetadata($metadata, $normalizedUrl)
            || $metadata['expires_at'] <= time()
        ) {
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
        $entry = $this->path($normalizedUrl);

        /*
         * Do not let an existing entry bypass validation of
         * the configured cache layout.
         */
        if (!$this->isCacheablePath($entry)) {
            return;
        }

        $this->ensureDirectory($entry);

        $now = time();

        $metadata = [
            'version'    => self::CACHE_VERSION,
            'url'        => $normalizedUrl,
            'created_at' => $now,
            'updated_at' => $now,
            'expires_at' => $now + ($lifetimeMinutes * 60),
            'size'       => strlen($content),
        ];

        /*
         * Write the page first and metadata last. Metadata
         * therefore acts as the entry's completion marker.
         */
        $this->atomicWrite(
            $entry . DIRECTORY_SEPARATOR . self::PAGE_FILE,
            $content
        );

        $this->atomicWrite(
            $entry . DIRECTORY_SEPARATOR . self::META_FILE,
            $this->encodeJson($metadata)
        );

        $this->setPermissions($entry);
    }

    /**
     * Invalidate cached URLs matching a route pattern.
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
            $metadata = $this->readMetadata($entry);

            if ($metadata === null) {
                $this->remove($entry);

                continue;
            }

            if (!$this->isValidMetadataStructure($metadata)) {
                $this->remove($entry);

                continue;
            }

            $url = $metadata['url'];

            if ($this->routeMatches($route, $url)) {
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
            $this->path($this->normalizeUrl($url))
        );
    }

    /**
     * Clear the entire cache.
     */
    public function clearAll(): void
    {
        $this->ensureCacheDirectory();

        foreach ($this->entries() as $entry) {
            $this->remove($entry);
        }
    }

    /**
     * Check whether a valid, unexpired cache entry exists.
     */
    public function has(string $url): bool
    {
        return $this->getForUrl($url) !== null;
    }

    /**
     * Return cached content or generate and cache it.
     *
     * @throws RuntimeException If the callback does not return a string.
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
     * Remove expired or corrupt cache entries.
     *
     * @return int Number of entries removed.
     */
    public function pruneExpired(): int
    {
        $removed = 0;
        $now = time();

        foreach ($this->entries() as $entry) {
            $metadata = $this->readMetadata($entry);

            if (
                $metadata === null
                || !$this->isValidMetadataStructure($metadata)
                || $metadata['expires_at'] <= $now
                || !$this->isValidMetadata(
                    $metadata,
                    $metadata['url']
                )
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
            $metadata = $this->readMetadata($entry);

            if (
                $metadata === null
                || !$this->isValidMetadataStructure($metadata)
            ) {
                continue;
            }

            $entries++;

            $page = $entry
                . DIRECTORY_SEPARATOR
                . self::PAGE_FILE;

            $size = $metadata['size'];

            if (is_file($page)) {
                $actualSize = filesize($page);

                if ($actualSize !== false) {
                    $bytes += $actualSize;
                } elseif (is_int($size)) {
                    $bytes += $size;
                }
            } elseif (is_int($size)) {
                $bytes += $size;
            }

            if ($metadata['expires_at'] <= $now) {
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
     * Build the filesystem path for a normalized URL.
     */
    private function path(string $url): string
    {
        $hash = hash('sha256', $this->normalizeUrl($url));

        return $this->absolutePath(
            substr($hash, 0, 2)
            . DIRECTORY_SEPARATOR
            . $hash
        );
    }

    /**
     * Convert a relative cache path into an absolute path.
     */
    private function absolutePath(string $relative): string
    {
        return rtrim(
            $this->cacheDirectory,
            '/\\'
        ) . DIRECTORY_SEPARATOR . ltrim($relative, '/\\');
    }

    /**
     * Normalize a URL while preserving its query string.
     *
     * Fragments are ignored because browsers do not normally
     * send URL fragments to the server.
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '/';
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return $this->normalizePath($url);
        }

        $path = $this->normalizePath($parts['path'] ?? '/');

        if (isset($parts['query']) && $parts['query'] !== '') {
            $path .= '?' . $parts['query'];
        }

        return $path;
    }

    /**
     * Normalize a URL path.
     */
    private function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return '/';
        }

        $path = preg_replace('#/+#', '/', $path) ?? $path;

        return '/' . trim($path, '/');
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
         * Route patterns match paths, not query parameters.
         */
        $route = explode('?', $route, 2)[0];

        return $this->normalizePath($route);
    }

    /**
     * Determine whether a URL path matches a route pattern.
     *
     * Placeholders match exactly one path segment.
     * A wildcard matches zero or more characters, including "/".
     */
    private function routeMatches(
        string $route,
        string $url
    ): bool {
        $url = $this->normalizeUrl($url);
        $urlPath = explode('?', $url, 2)[0];

        $quoted = preg_quote($route, '#');

        $pattern = preg_replace_callback(
            '/\\\\\{[^{}]+\\\\\}|\\\\\*/',
            static function (array $matches): string {
                return $matches[0] === '\\*'
                    ? '.*'
                    : '[^/]+';
            },
            $quoted
        );

        if ($pattern === null) {
            return false;
        }

        return preg_match(
            '#^' . $pattern . '$#D',
            $urlPath
        ) === 1;
    }

    /**
     * Read metadata from a cache entry.
     */
    private function readMetadata(string $entry): ?array
    {
        $file = $entry . DIRECTORY_SEPARATOR . self::META_FILE;

        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $contents = @file_get_contents($file);

        if ($contents === false || $contents === '') {
            return null;
        }

        try {
            $metadata = json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return null;
        }

        return is_array($metadata) ? $metadata : null;
    }

    /**
     * Validate metadata structure and required field types.
     */
    private function isValidMetadataStructure(array $metadata): bool
    {
        return isset(
            $metadata['version'],
            $metadata['url'],
            $metadata['created_at'],
            $metadata['updated_at'],
            $metadata['expires_at'],
            $metadata['size']
        )
            && is_int($metadata['version'])
            && is_string($metadata['url'])
            && is_int($metadata['created_at'])
            && is_int($metadata['updated_at'])
            && is_int($metadata['expires_at'])
            && is_int($metadata['size'])
            && $metadata['created_at'] >= 0
            && $metadata['updated_at'] >= $metadata['created_at']
            && $metadata['expires_at'] >= $metadata['created_at']
            && $metadata['size'] >= 0;
    }

    /**
     * Validate metadata against the requested URL.
     */
    private function isValidMetadata(
        array $metadata,
        string $normalizedUrl
    ): bool {
        return $this->isValidMetadataStructure($metadata)
            && $metadata['version'] === self::CACHE_VERSION
            && $metadata['url'] === $normalizedUrl;
    }

    /**
     * Atomically write a file using a temporary file.
     */
    private function atomicWrite(
        string $file,
        string $contents
    ): void {
        $directory = dirname($file);

        $this->ensureDirectory($directory);

        $temporaryFile = $file
            . '.'
            . bin2hex(random_bytes(16))
            . '.tmp';

        try {
            $written = @file_put_contents(
                $temporaryFile,
                $contents,
                LOCK_EX
            );

            if (
                $written === false
                || $written !== strlen($contents)
            ) {
                throw new RuntimeException(
                    sprintf('Unable to write cache file: %s', $file)
                );
            }

            $this->setPermissions($temporaryFile);

            if (!@rename($temporaryFile, $file)) {
                throw new RuntimeException(
                    sprintf('Unable to finalize cache file: %s', $file)
                );
            }
        } finally {
            if (is_file($temporaryFile) || is_link($temporaryFile)) {
                @unlink($temporaryFile);
            }
        }
    }

    /**
     * Read a cached file.
     */
    private function read(string $file): ?string
    {
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $contents = @file_get_contents($file);

        return $contents === false ? null : $contents;
    }

    /**
     * Encode metadata as JSON.
     */
    private function encodeJson(array $metadata): string
    {
        try {
            return json_encode(
                $metadata,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Unable to encode cache metadata.',
                previous: $exception
            );
        }
    }

    /**
     * Determine whether a cache entry can be created.
     */
    private function isCacheablePath(string $entry): bool
    {
        $relative = substr(
            $entry,
            strlen(rtrim($this->cacheDirectory, '/\\')) + 1
        );

        if (
            substr_count(
                str_replace('\\', '/', $relative),
                '/'
            ) > $this->maxDepth
        ) {
            return false;
        }

        /*
         * An existing entry can be updated even when the cache
         * has reached its global entry limit.
         */
        if (is_dir($entry)) {
            return true;
        }

        $parent = dirname($entry);

        if (!is_dir($parent)) {
            return $this->countEntries() < $this->maxEntries;
        }

        try {
            $variants = 0;

            foreach (
                new FilesystemIterator(
                    $parent,
                    FilesystemIterator::SKIP_DOTS
                ) as $item
            ) {
                if ($item->isDir()) {
                    $variants++;
                }
            }
        } catch (Throwable) {
            return false;
        }

        if ($variants >= $this->maxVariants) {
            return false;
        }

        return $this->countEntries() < $this->maxEntries;
    }

    /**
     * Return cache entry directories.
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
                if (!$shard->isDir() || $shard->isLink()) {
                    continue;
                }

                $shardPath = $shard->getPathname();

                $hashEntries = new FilesystemIterator(
                    $shardPath,
                    FilesystemIterator::SKIP_DOTS
                );

                foreach ($hashEntries as $entry) {
                    if ($entry->isDir() && !$entry->isLink()) {
                        yield $entry->getPathname();
                    }
                }
            }
        } catch (Throwable) {
            return;
        }
    }

    /**
     * Count existing cache entries, stopping at the configured limit.
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
    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (
            !@mkdir(
                $directory,
                $this->directoryPermissions,
                true
            )
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                sprintf('Unable to create cache directory: %s', $directory)
            );
        }

        $this->setPermissions($directory);
    }

    /**
     * Ensure the root cache directory exists.
     */
    private function ensureCacheDirectory(): void
    {
        $this->ensureDirectory($this->cacheDirectory);
    }

    /**
     * Apply configured permissions.
     */
    private function setPermissions(string $path): void
    {
        if (!file_exists($path) || is_link($path)) {
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
     * Safely remove a file or directory contained in the cache root.
     *
     * Symbolic links are unlinked rather than traversed.
     */
    private function remove(string $path): void
    {
        $root = realpath($this->cacheDirectory);

        if ($root === false) {
            return;
        }

        /*
         * Handle a symbolic link before realpath(), because
         * realpath() resolves links to their targets.
         */
        if (is_link($path)) {
            $parent = realpath(dirname($path));

            if (
                $parent !== false
                && $this->isWithinRoot($parent, $root)
            ) {
                @unlink($path);
            }

            return;
        }

        if (!file_exists($path)) {
            return;
        }

        $realPath = realpath($path);

        if (
            $realPath === false
            || !$this->isWithinRoot($realPath, $root)
        ) {
            return;
        }

        if (is_file($realPath)) {
            @unlink($realPath);

            return;
        }

        if (!is_dir($realPath)) {
            return;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $realPath,
                    FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                $pathname = $item->getPathname();

                /*
                 * Never traverse symbolic links during cleanup.
                 */
                if ($item->isLink()) {
                    @unlink($pathname);
                } elseif ($item->isFile()) {
                    @unlink($pathname);
                } elseif ($item->isDir()) {
                    @rmdir($pathname);
                }
            }
        } catch (Throwable) {
            /*
             * Cache cleanup failures should not interrupt rendering.
             */
        }

        @rmdir($realPath);
    }

    /**
     * Check whether a path is the root or is inside it.
     */
    private function isWithinRoot(string $path, string $root): bool
    {
        return $path === $root
            || str_starts_with(
                $path,
                rtrim($root, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
            );
    }
}

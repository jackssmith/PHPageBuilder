<?php

declare(strict_types=1);

namespace PHPageBuilder;

use PHPageBuilder\Contracts\CacheContract;

final readonly class Cache implements CacheContract
{
    private const PAGE_FILE = 'page.html';
    private const META_FILE = 'meta.json';

    public function __construct(
        private string $cacheDirectory,
        private int $maxDepth = 32,
        private int $maxVariants = 64,
    ) {
    }

    public function getForUrl(string $url): ?string
    {
        $entry = $this->path($url);

        if (!is_dir($entry)) {
            return null;
        }

        $meta = $this->readMetadata($entry);

        if ($meta === null) {
            $this->remove($entry);
            return null;
        }

        if ($meta['expires_at'] <= time()) {
            $this->remove($entry);
            return null;
        }

        if (($meta['url'] ?? null) !== $url) {
            $this->remove($entry);
            return null;
        }

        return $this->read(
            $entry . DIRECTORY_SEPARATOR . self::PAGE_FILE
        );
    }

    public function storeForUrl(
        string $url,
        string $content,
        int $lifetimeMinutes
    ): void {
        if ($lifetimeMinutes < 1) {
            return;
        }

        $relative = $this->relativePath($url);

        if (!$this->isCacheablePath($relative)) {
            return;
        }

        $path = $this->absolutePath($relative);

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $meta = [
            'url'        => $url,
            'created_at' => time(),
            'expires_at' => time() + ($lifetimeMinutes * 60),
        ];

        $this->atomicWrite(
            $path . DIRECTORY_SEPARATOR . self::PAGE_FILE,
            $content
        );

        $this->atomicWrite(
            $path . DIRECTORY_SEPARATOR . self::META_FILE,
            json_encode(
                $meta,
                JSON_THROW_ON_ERROR
            )
        );
    }

    public function invalidate(string $route): void
    {
        $prefix = $this->routePrefix($route);

        if ($prefix === null) {
            return;
        }

        $parent = dirname(
            $this->absolutePath(
                $this->relativePath($prefix)
            )
        );

        $this->remove($parent);
    }

    public function clearUrl(string $url): void
    {
        $this->remove($this->path($url));
    }

    public function clearAll(): void
    {
        $this->remove($this->cacheDirectory);

        mkdir(
            $this->cacheDirectory,
            0755,
            true
        );
    }

    private function path(string $url): string
    {
        return $this->absolutePath(
            $this->relativePath($url)
        );
    }

    private function relativePath(string $url): string
    {
        $normalized = $this->normalizeUrl($url);

        return substr(
            hash('sha256', $normalized),
            0,
            2
        ) . '/' . hash('sha256', $normalized);
    }

    private function absolutePath(string $relative): string
    {
        return rtrim($this->cacheDirectory, '/\\')
            . DIRECTORY_SEPARATOR
            . ltrim($relative, '/\\');
    }

    private function normalizeUrl(string $url): string
    {
        $url = preg_replace('#/+#', '/', trim($url));

        if ($url === '' || $url === '/') {
            return '/';
        }

        return '/' . trim((string) $url, '/');
    }

    private function readMetadata(string $path): ?array
    {
        $file = $path . DIRECTORY_SEPARATOR . self::META_FILE;

        if (!is_readable($file)) {
            return null;
        }

        try {
            return json_decode(
                (string) file_get_contents($file),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function atomicWrite(
        string $file,
        string $contents
    ): void {
        $tmp = sprintf(
            '%s.%s.tmp',
            $file,
            bin2hex(random_bytes(8))
        );

        file_put_contents(
            $tmp,
            $contents,
            LOCK_EX
        );

        rename($tmp, $file);
    }

    private function read(string $file): ?string
    {
        if (!is_readable($file)) {
            return null;
        }

        $contents = file_get_contents($file);

        return $contents === false
            ? null
            : $contents;
    }

    private function isCacheablePath(
        string $path
    ): bool {
        if (
            substr_count($path, '/')
            > $this->maxDepth
        ) {
            return false;
        }

        $parent = dirname(
            $this->absolutePath($path)
        );

        if (!is_dir($parent)) {
            return true;
        }

        $variants = 0;

        foreach (new \FilesystemIterator($parent) as $item) {
            if (
                $item->isDir()
                && ++$variants >= $this->maxVariants
            ) {
                return false;
            }
        }

        return true;
    }

    private function remove(string $path): void
    {
        $real = realpath($path);

        if ($real === false) {
            return;
        }

        if (is_file($real)) {
            unlink($real);
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $real,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $item->isDir()
                ? rmdir($item->getPathname())
                : unlink($item->getPathname());
        }

        rmdir($real);
    }

    private function routePrefix(
        string $route
    ): ?string {
        foreach (['*', '{'] as $token) {
            if (str_contains($route, $token)) {
                return explode(
                    $token,
                    $route,
                    2
                )[0];
            }
        }

        return null;
    }
}

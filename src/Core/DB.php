<?php

declare(strict_types=1);

namespace PHPageBuilder\Core;

use PHPageBuilder\Extensions;

/*
|--------------------------------------------------------------------------
| Config
|--------------------------------------------------------------------------
*/
class Config
{
    public function __construct(private array $config = []) {}

    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === '') return $this->config;

        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

/*
|--------------------------------------------------------------------------
| Escape
|--------------------------------------------------------------------------
*/
class Escape
{
    public static function html(mixed $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
            $doubleEncode
        );
    }

    public static function attr(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

/*
|--------------------------------------------------------------------------
| URL
|--------------------------------------------------------------------------
*/
class Url
{
    public function __construct(private Config $config) {}

    public function isValid(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    public function full(string $path = ''): string
    {
        if ($this->isValid($path)) {
            return $path;
        }

        $base = rtrim((string) $this->config->get('general.base_url'), '/');

        return $base . '/' . ltrim($path, '/');
    }

    public function build(string $module, array $params = [], bool $absolute = true): string
    {
        $base = $absolute ? $this->full() : '';
        $url  = $base . $this->config->get("$module.url");

        if ($params) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    public function current(bool $withQuery = true): ?string
    {
        if (empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
            return null;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';

        $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        return $withQuery ? rtrim($url, '/') : strtok($url, '?');
    }
}

/*
|--------------------------------------------------------------------------
| Assets
|--------------------------------------------------------------------------
*/
class Asset
{
    public function __construct(private Config $config) {}

    public function asset(string $path): string
    {
        $basePath = realpath(__DIR__ . '/../../dist');
        $filePath = realpath($basePath . '/' . ltrim($path, '/'));

        $version = ($filePath && str_starts_with($filePath, $basePath))
            ? filemtime($filePath)
            : null;

        $url = $this->config->get('general.assets_url') . '/' . ltrim($path, '/');

        return $version ? "{$url}?v={$version}" : $url;
    }

    public function theme(string $path): string
    {
        $theme = $this->config->get('theme');

        return "{$theme['folder_url']}/{$theme['active_theme']}/" . ltrim($path, '/');
    }
}

/*
|--------------------------------------------------------------------------
| Request
|--------------------------------------------------------------------------
*/
class Request
{
    public function input(string $key, mixed $default = null): mixed
    {
        return $_REQUEST[$key] ?? $default;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/
class Csrf
{
    public function token(): string
    {
        return $_SESSION['_token'] ??= bin2hex(random_bytes(32));
    }

    public function verify(): bool
    {
        return isset($_POST['_token'], $_SESSION['_token']) &&
            hash_equals($_SESSION['_token'], $_POST['_token']);
    }
}

/*
|--------------------------------------------------------------------------
| Translator
|--------------------------------------------------------------------------
*/
class Translator
{
    public function __construct(private array $translations = []) {}

    public function get(string $key, array $params = []): string|array
    {
        $value = $this->arrayGet($this->translations, $key, '');

        if (is_string($value)) {
            return $this->replace($value, $params);
        }

        return $value;
    }

    private function replace(string $text, array $params): string
    {
        foreach ($params as $k => $v) {
            $text = str_replace(":{$k}", (string) $v, $text);
        }

        return $text;
    }

    private function arrayGet(array $array, string $key, mixed $default): mixed
    {
        foreach (explode('.', $key) as $segment) {
            if (!isset($array[$segment])) return $default;
            $array = $array[$segment];
        }
        return $array;
    }
}

/*
|--------------------------------------------------------------------------
| Helpers (Utility)
|--------------------------------------------------------------------------
*/
class Str
{
    public static function slug(string $text, bool $allowSlashes = false): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;

        $pattern = $allowSlashes
            ? '/[^A-Za-z0-9\/]+/'
            : '/[^A-Za-z0-9]+/';

        return strtolower(trim(preg_replace($pattern, '-', $text) ?? '', '-'));
    }
}

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/
class Redirect
{
    public static function to(string $url, array $flash = [], int $status = 302): never
    {
        if ($flash) {
            $_SESSION['phpb_flash'] = $flash;
        }

        header("Location: {$url}", true, $status);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Debug
|--------------------------------------------------------------------------
*/
class Debug
{
    public static function dd(mixed ...$vars): never
    {
        echo '<pre>';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Assets Renderer
|--------------------------------------------------------------------------
*/
class AssetRenderer
{
    public function render(string $location = 'header'): void
    {
        $assets = $location === 'header'
            ? Extensions::getHeaderAssets()
            : Extensions::getFooterAssets();

        foreach ($assets as $asset) {
            $attrs = '';

            foreach ($asset['attributes'] as $k => $v) {
                $attrs .= ' ' . Escape::attr($k) . '="' . Escape::attr($v) . '"';
            }

            $src = Escape::attr($asset['src']);

            echo $asset['type'] === 'style'
                ? "<link rel=\"stylesheet\" href=\"{$src}\"{$attrs} />"
                : "<script src=\"{$src}\"{$attrs}></script>";
        }
    }
}

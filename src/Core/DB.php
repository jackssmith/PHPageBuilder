<?php

declare(strict_types=1);

use PHPageBuilder\Extensions;

/*
|--------------------------------------------------------------------------
| Internal Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_array_get')) {
    function phpb_array_get(array $array, string $key, $default = null)
    {
        if ($key === '') {
            return $array;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }
}

/*
|--------------------------------------------------------------------------
| Escape Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_e')) {
    function phpb_e(mixed $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
            $doubleEncode
        );
    }
}

if (!function_exists('phpb_attr')) {
    function phpb_attr(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('phpb_encode_or_null')) {
    function phpb_encode_or_null(mixed $value, bool $doubleEncode = true): ?string
    {
        return $value === null ? null : phpb_e($value, $doubleEncode);
    }
}

/*
|--------------------------------------------------------------------------
| URL Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_is_url')) {
    function phpb_is_url(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }
}

if (!function_exists('phpb_full_url')) {
    function phpb_full_url(string $path = ''): string
    {
        if (phpb_is_url($path)) {
            return $path;
        }

        $base = rtrim((string) phpb_config('general.base_url'), '/');

        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('phpb_url')) {
    function phpb_url(string $module, array $parameters = [], bool $absolute = true): string
    {
        $base = $absolute ? phpb_full_url() : '';
        $url  = $base . phpb_config("$module.url");

        if ($parameters) {
            $url .= '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }
}

if (!function_exists('phpb_current_full_url')) {
    function phpb_current_full_url(bool $withQuery = true): ?string
    {
        if (empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
            return null;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        if (!$withQuery) {
            $url = strtok($url, '?');
        }

        return rtrim($url, '/');
    }
}

/*
|--------------------------------------------------------------------------
| Assets
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_asset')) {
    function phpb_asset(string $path): string
    {
        $basePath = realpath(__DIR__ . '/../../dist');
        $filePath = realpath($basePath . '/' . ltrim($path, '/'));

        $version = ($filePath && str_starts_with($filePath, $basePath))
            ? filemtime($filePath)
            : null;

        $url = phpb_full_url(
            rtrim((string) phpb_config('general.assets_url'), '/') . '/' . ltrim($path, '/')
        );

        return $version ? "{$url}?v={$version}" : $url;
    }
}

if (!function_exists('phpb_theme_asset')) {
    function phpb_theme_asset(string $path): string
    {
        $theme = phpb_config('theme');

        return phpb_full_url(
            "{$theme['folder_url']}/{$theme['active_theme']}/" . ltrim($path, '/')
        );
    }
}

/*
|--------------------------------------------------------------------------
| Config & Flash
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_config')) {
    function phpb_config(string $key, $default = null)
    {
        global $phpb_config;

        return phpb_array_get($phpb_config ?? [], $key, $default);
    }
}

if (!function_exists('phpb_flash')) {
    function phpb_flash(string $key, bool $encode = true): mixed
    {
        global $phpb_flash;

        $value = phpb_array_get($phpb_flash ?? [], $key);

        if ($value === null) {
            return false;
        }

        return is_string($value) && $encode ? phpb_e($value) : $value;
    }
}

/*
|--------------------------------------------------------------------------
| Translation
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_trans')) {
    function phpb_trans(string $key, array $params = []): string|array
    {
        global $phpb_translations;

        $value = phpb_array_get($phpb_translations ?? [], $key, '');

        if (is_string($value)) {
            return phpb_replace_placeholders($value, $params);
        }

        return $value;
    }
}

if (!function_exists('phpb_replace_placeholders')) {
    function phpb_replace_placeholders(string $text, array $params = []): string
    {
        foreach ($params as $key => $value) {
            $text = str_replace(":{$key}", (string) $value, $text);
        }

        return $text;
    }
}

/*
|--------------------------------------------------------------------------
| Request
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_request')) {
    function phpb_request(string $key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }
}

if (!function_exists('phpb_get')) {
    function phpb_get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
}

if (!function_exists('phpb_post')) {
    function phpb_post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('phpb_is_post')) {
    function phpb_is_post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_csrf_token')) {
    function phpb_csrf_token(): string
    {
        return $_SESSION['_token'] ??= bin2hex(random_bytes(32));
    }
}

if (!function_exists('phpb_verify_csrf')) {
    function phpb_verify_csrf(): bool
    {
        return isset($_POST['_token'], $_SESSION['_token']) &&
            hash_equals($_SESSION['_token'], $_POST['_token']);
    }
}

/*
|--------------------------------------------------------------------------
| Slug
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_slug')) {
    function phpb_slug(string $text, bool $allowSlashes = false): string
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

if (!function_exists('phpb_redirect')) {
    function phpb_redirect(string $url, array $flash = [], int $status = 302): never
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

if (!function_exists('phpb_dd')) {
    function phpb_dd(mixed ...$vars): never
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
| Environment
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_env')) {
    function phpb_env(string $key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

/*
|--------------------------------------------------------------------------
| Registered Assets
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_registered_assets')) {
    function phpb_registered_assets(string $location = 'header'): void
    {
        $assets = $location === 'header'
            ? Extensions::getHeaderAssets()
            : Extensions::getFooterAssets();

        foreach ($assets as $asset) {

            $attrs = '';
            foreach ($asset['attributes'] as $k => $v) {
                $attrs .= ' ' . phpb_attr($k) . '="' . phpb_attr($v) . '"';
            }

            $src = phpb_attr($asset['src']);

            if ($asset['type'] === 'style') {
                echo "<link rel=\"stylesheet\" href=\"{$src}\"{$attrs} />";
            } else {
                echo "<script src=\"{$src}\"{$attrs}></script>";
            }
        }
    }
}

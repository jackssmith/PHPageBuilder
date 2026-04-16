<?php

use PHPageBuilder\Extensions;

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_e')) {
    function phpb_e(?string $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('phpb_encode_or_null')) {
    function phpb_encode_or_null($value, bool $doubleEncode = true): ?string
    {
        return $value === null ? null : phpb_e($value, $doubleEncode);
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
        $base = realpath(__DIR__ . '/../../dist/');
        $file = realpath($base . '/' . ltrim($path, '/'));

        $version = ($file && strpos($file, $base) === 0)
            ? filemtime($file)
            : time(); // fallback version (changed)

        return phpb_full_url(
            rtrim(phpb_config('general.assets_url'), '/') . '/' . ltrim($path, '/')
        ) . '?v=' . $version;
    }
}

if (!function_exists('phpb_theme_asset')) {
    function phpb_theme_asset(string $path): string
    {
        $themeBase = sprintf(
            '%s/%s',
            phpb_config('theme.folder_url'),
            phpb_config('theme.active_theme')
        );

        return phpb_full_url($themeBase . '/' . ltrim($path, '/'));
    }
}

/*
|--------------------------------------------------------------------------
| Config & Flash
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_config')) {
    function phpb_config(string $key, $default = '')
    {
        global $phpb_config;

        $segments = explode('.', $key);
        $value = $phpb_config;

        foreach ($segments as $segment) {
            if (!isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('phpb_flash')) {
    function phpb_flash(string $key, bool $encode = true)
    {
        global $phpb_flash;

        $segments = explode('.', $key);
        $value = $phpb_flash;

        foreach ($segments as $segment) {
            if (!isset($value[$segment])) {
                return false; // changed (was inconsistent before)
            }
            $value = $value[$segment];
        }

        return is_string($value)
            ? ($encode ? phpb_e($value) : $value)
            : $value;
    }
}

/*
|--------------------------------------------------------------------------
| Translations
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_trans')) {
    function phpb_trans(string $key, array $params = [])
    {
        global $phpb_translations;

        $segments = explode('.', $key);
        $value = $phpb_translations;

        foreach ($segments as $segment) {
            if (!isset($value[$segment])) {
                return '';
            }
            $value = $value[$segment];
        }

        if (is_string($value)) {
            return phpb_replace_placeholders($value, $params);
        }

        return $value ?? '';
    }
}

if (!function_exists('phpb_replace_placeholders')) {
    function phpb_replace_placeholders(string $text, array $params = []): string
    {
        foreach ($params as $key => $val) {
            $text = str_replace(':' . $key, $val, $text);
        }
        return $text;
    }
}

/*
|--------------------------------------------------------------------------
| URLs
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_full_url')) {
    function phpb_full_url(string $url): string
    {
        if (preg_match('#^https?://#', $url)) {
            return $url;
        }

        return rtrim(phpb_config('general.base_url'), '/') . '/' . ltrim($url, '/');
    }
}

if (!function_exists('phpb_url')) {
    function phpb_url(string $module, array $params = [], bool $full = true): string
    {
        $url = $full ? phpb_full_url('') : '';
        $url .= phpb_config($module . '.url');

        if ($params) {
            $query = http_build_query($params); // changed (cleaner)
            $url .= '?' . $query;
        }

        return $url;
    }
}

/*
|--------------------------------------------------------------------------
| Current URL
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_current_full_url')) {
    function phpb_current_full_url(bool $withQuery = true): ?string
    {
        if (empty($_SERVER['SERVER_NAME']) || empty($_SERVER['REQUEST_URI'])) {
            return null;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $port   = ($_SERVER['SERVER_PORT'] ?? 80);

        $host = $_SERVER['SERVER_NAME'];
        if (!in_array($port, [80, 443])) {
            $host .= ':' . $port;
        }

        $url = $scheme . '://' . $host . $_SERVER['REQUEST_URI'];

        if (!$withQuery) {
            $url = strtok($url, '?');
        }

        return rtrim($url, '/');
    }
}

if (!function_exists('phpb_current_relative_url')) {
    function phpb_current_relative_url(): string
    {
        $full = phpb_current_full_url();

        if (!$full) return '/';

        $path = parse_url($full, PHP_URL_PATH) ?? '/';

        $base = parse_url(phpb_config('general.base_url'), PHP_URL_PATH) ?? '';

        return '/' . ltrim(str_replace($base, '', $path), '/');
    }
}

/*
|--------------------------------------------------------------------------
| Misc
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_slug')) {
    function phpb_slug(string $text, bool $allowSlashes = false): string
    {
        $pattern = $allowSlashes
            ? '/[^A-Za-z0-9\/-]+/'
            : '/[^A-Za-z0-9-]+/';

        return strtolower(trim(preg_replace($pattern, '-', $text), '-'));
    }
}

if (!function_exists('phpb_redirect')) {
    function phpb_redirect(string $url, array $flash = [], int $status = 302): void
    {
        if ($flash) {
            $_SESSION['phpb_flash'] = $flash;
        }

        header("Location: $url", true, $status);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Assets Renderer
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
                $attrs .= " {$k}=\"{$v}\"";
            }

            if ($asset['type'] === 'style') {
                echo "<link rel=\"stylesheet\" href=\"{$asset['src']}\"{$attrs}>";
            } else {
                echo "<script src=\"{$asset['src']}\"{$attrs}></script>";
            }
        }
    }
}

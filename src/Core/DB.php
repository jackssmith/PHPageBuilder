<?php

use PHPageBuilder\Extensions;

/*
|--------------------------------------------------------------------------
| HTML Escape Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_e')) {

    function phpb_e($value, bool $doubleEncode = true): string
    {
        if ($value === null) {
            return '';
        }

        if (!is_string($value)) {
            $value = (string)$value;
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('phpb_encode_or_null')) {

    function phpb_encode_or_null($value, bool $doubleEncode = true)
    {
        return $value === null ? null : phpb_e($value, $doubleEncode);
    }
}

if (!function_exists('phpb_attr')) {

    function phpb_attr($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('phpb_full_url')) {

    function phpb_full_url(string $urlRelativeToBaseUrl): string
    {
        if (phpb_is_url($urlRelativeToBaseUrl)) {
            return $urlRelativeToBaseUrl;
        }

        $baseUrl = rtrim(phpb_config('general.base_url'), '/');

        return $baseUrl . '/' . ltrim($urlRelativeToBaseUrl, '/');
    }
}

if (!function_exists('phpb_url')) {

    function phpb_url($module, array $parameters = [], bool $fullUrl = true): string
    {
        $url = $fullUrl ? phpb_full_url('') : '';
        $url .= phpb_config($module . '.url');

        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }
}

if (!function_exists('phpb_current_full_url')) {

    function phpb_current_full_url(bool $includeQueryString = true)
    {
        if (!isset($_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI'])) {
            return null;
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $port = '';

        if (!in_array($_SERVER['SERVER_PORT'], [80, 443])) {
            $port = ':' . $_SERVER['SERVER_PORT'];
        }

        $url = $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];

        if (!$includeQueryString) {
            $url = explode('?', $url)[0];
        }

        return rtrim($url, '/');
    }
}

/*
|--------------------------------------------------------------------------
| Asset Helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_asset')) {

    function phpb_asset($path)
    {
        $basePath = __DIR__ . '/../../dist/';
        $distPath = realpath($basePath . $path);

        $version = ($distPath && strpos($distPath, realpath($basePath)) === 0)
            ? filemtime($distPath)
            : '';

        return phpb_full_url(phpb_config('general.assets_url') . '/' . $path) . '?v=' . $version;
    }
}

if (!function_exists('phpb_theme_asset')) {

    function phpb_theme_asset($path)
    {
        $themeFolder = phpb_config('theme.folder_url') . '/' . phpb_config('theme.active_theme');
        return phpb_full_url($themeFolder . '/' . $path);
    }
}

/*
|--------------------------------------------------------------------------
| Flash Data
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_flash')) {

    function phpb_flash($key, bool $encode = true)
    {
        global $phpb_flash;

        if (strpos($key, '.') === false) {
            if (!isset($phpb_flash[$key])) {
                return false;
            }

            return $encode ? phpb_e($phpb_flash[$key]) : $phpb_flash[$key];
        }

        $segments = explode('.', $key);
        $data = $phpb_flash;

        foreach ($segments as $segment) {
            if (!isset($data[$segment])) {
                return false;
            }

            $data = $data[$segment];
        }

        return is_string($data) ? ($encode ? phpb_e($data) : $data) : false;
    }
}

/*
|--------------------------------------------------------------------------
| Config Helper
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_config')) {

    function phpb_config($key)
    {
        global $phpb_config;

        if (strpos($key, '.') === false) {
            return $phpb_config[$key] ?? '';
        }

        $segments = explode('.', $key);
        $data = $phpb_config;

        foreach ($segments as $segment) {
            if (!isset($data[$segment])) {
                return '';
            }

            $data = $data[$segment];
        }

        return $data;
    }
}

/*
|--------------------------------------------------------------------------
| Translation
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_trans')) {

    function phpb_trans($key, array $parameters = [])
    {
        global $phpb_translations;

        if (strpos($key, '.') === false) {
            return phpb_replace_placeholders($phpb_translations[$key] ?? '', $parameters);
        }

        $segments = explode('.', $key);
        $data = $phpb_translations;

        foreach ($segments as $segment) {
            if (!isset($data[$segment])) {
                return '';
            }

            $data = $data[$segment];
        }

        if (is_string($data)) {
            return phpb_replace_placeholders($data, $parameters);
        }

        return $data;
    }
}

if (!function_exists('phpb_replace_placeholders')) {

    function phpb_replace_placeholders($string, array $parameters = []): string
    {
        foreach ($parameters as $key => $value) {
            $string = str_replace(':' . $key, $value, $string);
        }

        return $string;
    }
}

/*
|--------------------------------------------------------------------------
| Request Helpers
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
| CSRF Protection
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_csrf_token')) {

    function phpb_csrf_token(): string
    {
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_token'];
    }
}

if (!function_exists('phpb_verify_csrf')) {

    function phpb_verify_csrf(): bool
    {
        return isset($_POST['_token'], $_SESSION['_token'])
            && hash_equals($_SESSION['_token'], $_POST['_token']);
    }
}

/*
|--------------------------------------------------------------------------
| Slug Generator
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_slug')) {

    function phpb_slug(string $text, bool $allowSlashes = false): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);

        $pattern = $allowSlashes
            ? '/[^A-Za-z0-9\/]+/'
            : '/[^A-Za-z0-9]+/';

        $text = preg_replace($pattern, '-', $text);

        return strtolower(trim($text, '-'));
    }
}

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_redirect')) {

    function phpb_redirect($url, array $flashData = [], int $statusCode = 302)
    {
        if (!empty($flashData)) {
            $_SESSION['phpb_flash'] = $flashData;
        }

        header('Location: ' . $url, true, $statusCode);
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| Debug Helper
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_dd')) {

    function phpb_dd(...$vars)
    {
        echo "<pre>";

        foreach ($vars as $var) {
            var_dump($var);
        }

        echo "</pre>";
        die();
    }
}

/*
|--------------------------------------------------------------------------
| Environment Helper
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_env')) {

    function phpb_env(string $key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?? $default;
    }
}

/*
|--------------------------------------------------------------------------
| Registered Assets
|--------------------------------------------------------------------------
*/

if (!function_exists('phpb_registered_assets')) {

    function phpb_registered_assets($location = 'header')
    {
        $assets = ($location === 'header')
            ? Extensions::getHeaderAssets()
            : Extensions::getFooterAssets();

        foreach ($assets as $asset) {

            $attributes = '';

            foreach ($asset['attributes'] as $key => $value) {
                $attributes .= ' ' . phpb_attr($key) . '="' . phpb_attr($value) . '"';
            }

            if ($asset['type'] === 'style') {
                echo '<link rel="stylesheet" href="' . phpb_attr($asset['src']) . '"' . $attributes . ' />';
            } else {
                echo '<script src="' . phpb_attr($asset['src']) . '"' . $attributes . '></script>';
            }
        }
    }
}

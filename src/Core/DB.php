<?php

declare(strict_types=1);

namespace PHPageBuilder\Core;

use PHPageBuilder\Extensions;
use RuntimeException;
use InvalidArgumentException;

/*
|--------------------------------------------------------------------------
| Config
|--------------------------------------------------------------------------
*/
class Config
{
    public function __construct(
        private array $config = []
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $this->config;
        }

        $value = $this->config;

        foreach (explode('.', $key) as $segment) {
            if (
                !is_array($value) ||
                !array_key_exists($segment, $value)
            ) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        if ($key === '') {
            throw new InvalidArgumentException(
                'Configuration key cannot be empty.'
            );
        }

        $segments = explode('.', $key);
        $config = &$this->config;

        foreach ($segments as $segment) {
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }

            $config = &$config[$segment];
        }

        $config = $value;
    }

    public function has(string $key): bool
    {
        $sentinel = new \stdClass();

        return $this->get($key, $sentinel) !== $sentinel;
    }

    public function all(): array
    {
        return $this->config;
    }
}

/*
|--------------------------------------------------------------------------
| Escape
|--------------------------------------------------------------------------
*/
class Escape
{
    public static function html(
        mixed $value,
        bool $doubleEncode = true
    ): string {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
            $doubleEncode
        );
    }

    public static function attr(mixed $value): string
    {
        return self::html($value);
    }
}

/*
|--------------------------------------------------------------------------
| URL
|--------------------------------------------------------------------------
*/
class Url
{
    public function __construct(
        private Config $config
    ) {}

    public function isValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function full(string $path = ''): string
    {
        if ($this->isValid($path)) {
            return $path;
        }

        $base = rtrim(
            (string) $this->config->get('general.base_url', ''),
            '/'
        );

        return $base . '/' . ltrim($path, '/');
    }

    public function build(
        string $module,
        array $params = [],
        bool $absolute = true
    ): string {
        $moduleUrl = (string) $this->config->get(
            "{$module}.url",
            ''
        );

        $url = $absolute
            ? $this->full($moduleUrl)
            : $moduleUrl;

        if ($params !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?')
                . http_build_query(
                    $params,
                    '',
                    '&',
                    PHP_QUERY_RFC3986
                );
        }

        return $url;
    }

    public function current(bool $withQuery = true): ?string
    {
        $host = $_SERVER['HTTP_HOST'] ?? null;
        $uri = $_SERVER['REQUEST_URI'] ?? null;

        if (!$host || !$uri) {
            return null;
        }

        $scheme = (
            !empty($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] !== 'off'
        ) ? 'https' : 'http';

        $url = $scheme . '://' . $host . $uri;

        return $withQuery
            ? $url
            : strtok($url, '?');
    }
}

/*
|--------------------------------------------------------------------------
| Assets
|--------------------------------------------------------------------------
*/
class Asset
{
    private string $distPath;

    public function __construct(
        private Config $config
    ) {
        $this->distPath = realpath(
            __DIR__ . '/../../dist'
        ) ?: __DIR__ . '/../../dist';
    }

    public function asset(string $path): string
    {
        $path = ltrim($path, '/');

        $filePath = realpath(
            $this->distPath . '/' . $path
        );

        $version = null;

        if (
            $filePath !== false &&
            str_starts_with(
                $filePath,
                rtrim($this->distPath, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
            ) &&
            is_file($filePath)
        ) {
            $version = filemtime($filePath);
        }

        $baseUrl = rtrim(
            (string) $this->config->get('general.assets_url', ''),
            '/'
        );

        $url = $baseUrl . '/' . $path;

        if ($version !== false && $version !== null) {
            $url .= '?v=' . $version;
        }

        return $url;
    }

    public function theme(string $path): string
    {
        $theme = $this->config->get('theme', []);

        $folder = rtrim(
            (string) ($theme['folder_url'] ?? ''),
            '/'
        );

        $activeTheme = trim(
            (string) ($theme['active_theme'] ?? ''),
            '/'
        );

        return $folder . '/' . $activeTheme . '/'
            . ltrim($path, '/');
    }
}

/*
|--------------------------------------------------------------------------
| Request
|--------------------------------------------------------------------------
*/
class Request
{
    public function input(
        string $key,
        mixed $default = null
    ): mixed {
        return $_REQUEST[$key] ?? $default;
    }

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $_GET[$key] ?? $default;
    }

    public function post(
        string $key,
        mixed $default = null
    ): mixed {
        return $_POST[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function method(): string
    {
        return strtoupper(
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );
    }

    public function all(): array
    {
        return $_REQUEST;
    }

    public function allGet(): array
    {
        return $_GET;
    }

    public function allPost(): array
    {
        return $_POST;
    }

    public function has(string $key): bool
    {
        return isset($_REQUEST[$key]);
    }
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/
class Csrf
{
    private const SESSION_KEY = '_token';

    public function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException(
                'Session must be started before generating CSRF tokens.'
            );
        }

        return $_SESSION[self::SESSION_KEY] ??=
            bin2hex(random_bytes(32));
    }

    public function verify(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $submitted = $_POST[self::SESSION_KEY] ?? null;
        $stored = $_SESSION[self::SESSION_KEY] ?? null;

        return is_string($submitted) &&
            is_string($stored) &&
            hash_equals($stored, $submitted);
    }

    public function regenerate(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException(
                'Session must be started before regenerating CSRF tokens.'
            );
        }

        $_SESSION[self::SESSION_KEY] =
            bin2hex(random_bytes(32));

        return $_SESSION[self::SESSION_KEY];
    }
}

/*
|--------------------------------------------------------------------------
| Translator
|--------------------------------------------------------------------------
*/
class Translator
{
    public function __construct(
        private array $translations = []
    ) {}

    public function get(
        string $key,
        array $params = []
    ): string|array {
        $value = $this->arrayGet(
            $this->translations,
            $key,
            $key
        );

        if (!is_string($value)) {
            return $value;
        }

        return $this->replace($value, $params);
    }

    private function replace(
        string $text,
        array $params
    ): string {
        foreach ($params as $key => $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }

            $text = str_replace(
                ':' . $key,
                (string) $value,
                $text
            );
        }

        return $text;
    }

    private function arrayGet(
        array $array,
        string $key,
        mixed $default
    ): mixed {
        $value = $array;

        foreach (explode('.', $key) as $segment) {
            if (
                !is_array($value) ||
                !array_key_exists($segment, $value)
            ) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

/*
|--------------------------------------------------------------------------
| String Helpers
|--------------------------------------------------------------------------
*/
class Str
{
    public static function slug(
        string $text,
        bool $allowSlashes = false
    ): string {
        if (function_exists('iconv')) {
            $converted = iconv(
                'UTF-8',
                'ASCII//TRANSLIT//IGNORE',
                $text
            );

            if ($converted !== false) {
                $text = $converted;
            }
        }

        $pattern = $allowSlashes
            ? '/[^A-Za-z0-9\/]+/'
            : '/[^A-Za-z0-9]+/';

        $text = preg_replace(
            $pattern,
            '-',
            $text
        ) ?? '';

        $text = strtolower($text);

        return trim($text, '-/');
    }

    public static function startsWith(
        string $text,
        string $prefix
    ): bool {
        return str_starts_with($text, $prefix);
    }

    public static function endsWith(
        string $text,
        string $suffix
    ): bool {
        return str_ends_with($text, $suffix);
    }

    public static function contains(
        string $text,
        string $needle
    ): bool {
        return str_contains($text, $needle);
    }
}

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/
class Redirect
{
    public static function to(
        string $url,
        array $flash = [],
        int $status = 302
    ): never {
        if (preg_match('/[\r\n]/', $url)) {
            throw new InvalidArgumentException(
                'Invalid redirect URL.'
            );
        }

        if ($flash !== []) {
            $_SESSION['phpb_flash'] = $flash;
        }

        header('Location: ' . $url, true, $status);

        exit;
    }

    public static function back(
        string $fallback = '/',
        array $flash = []
    ): never {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;

        self::to($referer, $flash);
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
        echo '<pre style="
            background:#181818;
            color:#f1f1f1;
            padding:20px;
            border-radius:8px;
            overflow:auto;
        ">';

        foreach ($vars as $var) {
            var_dump($var);
        }

        echo '</pre>';

        exit;
    }

    public static function dump(mixed ...$vars): void
    {
        echo '<pre>';

        foreach ($vars as $var) {
            var_dump($var);
        }

        echo '</pre>';
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
        $assets = match ($location) {
            'header' => Extensions::getHeaderAssets(),
            'footer' => Extensions::getFooterAssets(),
            default => throw new InvalidArgumentException(
                "Invalid asset location: {$location}"
            ),
        };

        foreach ($assets as $asset) {
            $type = $asset['type'] ?? null;
            $src = $asset['src'] ?? null;
            $attributes = $asset['attributes'] ?? [];

            if (
                !is_string($src) ||
                !in_array($type, ['style', 'script'], true)
            ) {
                continue;
            }

            $attrs = '';

            foreach ($attributes as $key => $value) {
                if (
                    !is_string($key) ||
                    !preg_match('/^[a-zA-Z_:][a-zA-Z0-9:._-]*$/', $key) ||
                    !is_scalar($value)
                ) {
                    continue;
                }

                $attrs .= sprintf(
                    ' %s="%s"',
                    Escape::attr($key),
                    Escape::attr($value)
                );
            }

            $src = Escape::attr($src);

            if ($type === 'style') {
                echo sprintf(
                    '<link rel="stylesheet" href="%s"%s>',
                    $src,
                    $attrs
                );

                continue;
            }

            echo sprintf(
                '<script src="%s"%s></script>',
                $src,
                $attrs
            );
        }
    }
}

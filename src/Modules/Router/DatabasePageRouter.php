<?php

namespace PHPageBuilder\Modules\Router;

use PHPageBuilder\Contracts\PageTranslationContract;
use PHPageBuilder\Contracts\RouterContract;
use PHPageBuilder\Repositories\PageTranslationRepository;

final class DatabasePageRouter implements RouterContract
{
    private array $routes = [];
    private array $parameters = [];

    public function __construct(
        private readonly PageTranslationRepository $repository
    ) {
    }

    public function resolve(string $url): ?PageTranslationContract
    {
        $this->parameters = [];

        foreach ($this->compiledRoutes() as $route) {
            if (!preg_match($route['regex'], $this->normalize($url), $matches)) {
                continue;
            }

            foreach ($route['params'] as $name) {
                $this->parameters[$name] = urldecode($matches[$name] ?? '');
            }

            if (isset($matches['wildcard'])) {
                $this->parameters['wildcard'] = trim($matches['wildcard'], '/');
            }

            return $this->repository->findWithId($route['id']);
        }

        return null;
    }

    public function getRouteParameters(): array
    {
        return $this->parameters;
    }

    private function compiledRoutes(): array
    {
        if ($this->routes) {
            return $this->routes;
        }

        foreach ($this->repository->getAll(['id', 'route']) as $page) {
            $this->routes[] = [
                'id' => (string) $page->id,
                ...$this->compile($page->route),
            ];
        }

        usort($this->routes, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $this->routes;
    }

    private function compile(string $route): array
    {
        $route = '/' . trim($route, '/');

        $params = [];
        $score = 0;

        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::(int|slug|uuid))?\}/',
            function ($m) use (&$params, &$score) {
                $params[] = $m[1];
                $score--;

                return match ($m[2] ?? 'string') {
                    'int' => '(?<' . $m[1] . '>\d+)',
                    'slug' => '(?<' . $m[1] . '>[a-z0-9-]+)',
                    'uuid' => '(?<' . $m[1] . '>[a-f0-9-]{36})',
                    default => '(?<' . $m[1] . '>[^/]+)',
                };
            },
            preg_quote($route, '#')
        );

        $regex = str_replace('\*', '(?<wildcard>.*)', $regex);

        $score += substr_count($route, '/');
        $score += substr_count($route, '{') * -5;

        return [
            'regex' => '#^' . $regex . '$#iu',
            'params' => $params,
            'score' => $score,
        ];
    }

    private function normalize(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : rtrim($path, '/') ?: '/';
    }

    public function generate(string $route, array $parameters = []): string
    {
        foreach ($parameters as $key => $value) {
            $route = preg_replace(
                '/\{' . preg_quote($key, '/') . '(?::[^}]+)?\}/',
                rawurlencode((string) $value),
                $route
            );
        }

        return preg_replace('/\{[^}]+\}/', '', $route);
    }
}

<?php

namespace PHPageBuilder\Modules\Router;

use PHPageBuilder\Contracts\PageTranslationContract;
use PHPageBuilder\Contracts\RouterContract;
use PHPageBuilder\Repositories\PageTranslationRepository;

class DatabasePageRouter implements RouterContract
{
    protected PageTranslationRepository $pageTranslationRepository;

    /**
     * Matched route parameters.
     */
    protected array $routeParameters = [];

    /**
     * Cached compiled routes.
     *
     * @var array<int, array{id:string, route:string, segments:array<int,string>}>|null
     */
    private ?array $compiledRoutes = null;

    public function __construct(PageTranslationRepository $pageTranslationRepository)
    {
        $this->pageTranslationRepository = $pageTranslationRepository;
    }

    /**
     * Resolve a URL to a page translation.
     */
    public function resolve(string $url): ?PageTranslationContract
    {
        $urlSegments = explode('/', $this->normalizeUrl($url));

        foreach ($this->getCompiledRoutes() as $route) {
            $parameters = $this->matchRoute(
                $urlSegments,
                $route['segments']
            );

            if ($parameters === null) {
                continue;
            }

            $this->routeParameters = $parameters;

            return $this->pageTranslationRepository->findWithId($route['id']);
        }

        return null;
    }

    /**
     * Return route parameters from the last successful match.
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    /**
     * Compile and cache all routes.
     */
    protected function getCompiledRoutes(): array
    {
        if ($this->compiledRoutes !== null) {
            return $this->compiledRoutes;
        }

        $routes = [];

        foreach ($this->pageTranslationRepository->getAll(['id', 'route']) as $translation) {
            $routes[] = [
                'id'       => (string) $translation->id,
                'route'    => $translation->route,
                'segments' => explode('/', $translation->route),
            ];
        }

        usort(
            $routes,
            fn (array $a, array $b) => $this->routeOrderComparison(
                $a['segments'],
                $b['segments']
            )
        );

        return $this->compiledRoutes = $routes;
    }

    /**
     * Normalize the incoming URL.
     */
    protected function normalizeUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '/';

        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    /**
     * Sort routes in the order they should be evaluated.
     */
    public function getRoutesInOrder(array $routes): array
    {
        usort($routes, [$this, 'routeOrderComparison']);

        return $routes;
    }

    /**
     * Compare two routes.
     *
     * Priority:
     * 1. More segments first.
     * 2. Fewer parameters first.
     * 3. Wildcards last.
     */
    public function routeOrderComparison(array $route1, array $route2): int
    {
        $segmentComparison = count($route2) <=> count($route1);

        if ($segmentComparison !== 0) {
            return $segmentComparison;
        }

        $parameterComparison =
            $this->countParameters($route1)
            <=>
            $this->countParameters($route2);

        if ($parameterComparison !== 0) {
            return $parameterComparison;
        }

        $route1Wildcard = end($route1) === '*';
        $route2Wildcard = end($route2) === '*';

        return $route1Wildcard <=> $route2Wildcard;
    }

    /**
     * Count named parameters in a route.
     */
    protected function countParameters(array $route): int
    {
        return count(
            array_filter(
                $route,
                fn (string $segment): bool => $this->isParameter($segment)
            )
        );
    }

    /**
     * Determine if a route segment is a parameter.
     */
    protected function isParameter(string $segment): bool
    {
        return str_starts_with($segment, '{}')
            ? false
            : str_starts_with($segment, '{')
                && str_ends_with($segment, '}');
    }

    /**
     * Match URL segments against route segments.
     *
     * Returns route parameters if matched, otherwise null.
     */
    protected function matchRoute(
        array $urlSegments,
        array $routeSegments
    ): ?array {
        $isWildcardRoute = end($routeSegments) === '*';

        if (!$isWildcardRoute && count($urlSegments) !== count($routeSegments)) {
            return null;
        }

        if ($isWildcardRoute && count($urlSegments) < count($routeSegments) - 1) {
            return null;
        }

        $parameters = [];

        foreach ($routeSegments as $index => $routeSegment) {
            if ($routeSegment === '*') {
                return $parameters;
            }

            if (!isset($urlSegments[$index])) {
                return null;
            }

            $urlSegment = $urlSegments[$index];

            if ($this->isParameter($routeSegment)) {
                $parameters[trim($routeSegment, '{}')] = $urlSegment;
                continue;
            }

            if ($routeSegment !== $urlSegment) {
                return null;
            }
        }

        return $parameters;
    }
}

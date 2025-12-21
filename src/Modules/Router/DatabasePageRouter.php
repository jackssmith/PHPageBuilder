<?php

namespace PHPageBuilder\Modules\Router;

use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\PageTranslationContract;
use PHPageBuilder\Contracts\RouterContract;
use PHPageBuilder\Repositories\PageRepository;
use PHPageBuilder\Repositories\PageTranslationRepository;

class DatabasePageRouter implements RouterContract
{
    /**
     * @var PageRepository $pageRepository
     */
    protected $pageRepository;

    /**
     * @var PageTranslationRepository $pageTranslationRepository
     */
    protected $pageTranslationRepository;

    /**
     * @var array $routeParameters
     */
    protected $routeParameters = [];

    /**
     * @var array $routeToPageTranslationIdMapping
     */
    protected $routeToPageTranslationIdMapping = [];

    /**
     * DatabasePageRouter constructor.
     * 
     * @param PageRepository $pageRepository
     * @param PageTranslationRepository $pageTranslationRepository
     */
    public function __construct(PageRepository $pageRepository, PageTranslationRepository $pageTranslationRepository)
    {
        $this->pageRepository = $pageRepository;
        $this->pageTranslationRepository = $pageTranslationRepository;
    }

    /**
     * Return the page from database corresponding to the given URL.
     *
     * @param string $url
     * @return PageTranslationContract|null
     */
    public function resolve(string $url): ?PageTranslationContract
    {
        // Strip URL query parameters and remove trailing slash
        $url = rtrim(explode('?', $url, 2)[0], '/');
        $url = empty($url) ? '/' : $url;
        $urlSegments = explode('/', $url);

        // Fetch all routes
        $pageTranslations = $this->pageTranslationRepository->getAll(['id', 'route']);
        $routes = [];

        foreach ($pageTranslations as $pageTranslation) {
            $route = $pageTranslation->route;
            $this->routeToPageTranslationIdMapping[$route] = $pageTranslation->id;
            $routes[] = explode('/', $route);
        }

        // Sort routes by evaluation order
        $orderedRoutes = $this->getRoutesInOrder($routes);

        // Match the URL with routes and return the corresponding page
        foreach ($orderedRoutes as $routeSegments) {
            if ($this->onRoute($urlSegments, $routeSegments)) {
                $fullRoute = implode('/', $routeSegments);
                return $this->getMatchedPage($fullRoute, $this->routeToPageTranslationIdMapping[$fullRoute]);
            }
        }

        return null;
    }

    /**
     * Sort the given routes into the order in which they need to be evaluated.
     *
     * @param array $allRoutes
     * @return array
     */
    public function getRoutesInOrder(array $allRoutes): array
    {
        usort($allRoutes, [$this, "routeOrderComparison"]);
        return $allRoutes;
    }

    /**
     * Compare two given routes and return -1, 0, or 1 indicating which route should be evaluated first.
     *
     * @param array $route1
     * @param array $route2
     * @return int
     */
    public function routeOrderComparison(array $route1, array $route2): int
    {
        // Prioritize routes with more segments
        if (count($route1) > count($route2)) {
            return -1;
        }
        if (count($route1) < count($route2)) {
            return 1;
        }

        // Routes with fewer named parameters should be evaluated first
        $namedParamCount1 = substr_count(implode('/', $route1), '{');
        $namedParamCount2 = substr_count(implode('/', $route2), '{');
        if ($namedParamCount1 < $namedParamCount2) {
            return -1;
        }
        if ($namedParamCount1 > $namedParamCount2) {
            return 1;
        }

        // Routes ending with wildcard should be evaluated last
        if ($route1[count($route1) - 1] === '*') {
            return 1;
        }
        if ($route2[count($route2) - 1] === '*') {
            return -1;
        }

        return 0;
    }

    /**
     * Return the full page translation instance based on the matched route or page translation ID.
     *
     * @param string $matchedRoute
     * @param string $matchedPageTranslationId
     * @return PageTranslationContract|null
     */
    public function getMatchedPage(string $matchedRoute, string $matchedPageTranslationId): ?PageTranslationContract
    {
        return $this->pageTranslationRepository->findWithId($matchedPageTranslationId);
    }

    /**
     * Check if the URL segments match the given route segments.
     *
     * @param array $urlSegments
     * @param array $routeSegments
     * @return bool
     */
    protected function onRoute(array $urlSegments, array $routeSegments): bool
    {
        // Ensure the number of segments matches (except for wildcard routes)
        if (count($urlSegments) !== count($routeSegments) && end($routeSegments) !== '*') {
            return false;
        }

        $routeParameters = [];

        // Try to match each route segment with the corresponding URL segment
        foreach ($routeSegments as $i => $routeSegment) {
            if (!isset($urlSegments[$i])) {
                return false;
            }

            $urlSegment = $urlSegments[$i];

            // Match route parameters like {parameter}
            if (substr($routeSegment, 0, 1) === '{' && substr($routeSegment, -1) === '}') {
                $parameter = trim($routeSegment, '{}');
                $routeParameters[$parameter] = $urlSegment;
                continue;
            }

            // Match wildcard
            if ($routeSegment === '*') {
                break;
            }

            // Exact match
            if ($urlSegment !== $routeSegment) {
                return false;
            }
        }

        $this->routeParameters = $routeParameters;
        return true;
    }
}

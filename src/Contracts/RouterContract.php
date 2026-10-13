<?php

declare(strict_types=1);

namespace PHPageBuilder\Contracts;

interface RouterContract
{
    /**
     * Resolve a URL to its corresponding page translation.
     *
     * @param string $url The URL to resolve.
     *
     * @return PageTranslationContract|null The matching page translation,
     *                                       or null if no match is found.
     */
    public function resolve(string $url): ?PageTranslationContract;

    /**
     * Retrieve a page translation using the matched route and its ID.
     *
     * This method allows custom routers to perform additional validation
     * after a route has been matched.
     *
     * @param string $matchedRoute The matched route.
     * @param string $matchedPageTranslationId The ID of the matched page translation.
     *
     * @return PageTranslationContract|null The matching page translation,
     *                                       or null if no match is found.
     */
    public function getMatchedPage(
        string $matchedRoute,
        string $matchedPageTranslationId
    ): ?PageTranslationContract;

    /**
     * Sort routes in the order in which they should be evaluated.
     *
     * @param array $allRoutes The routes to sort.
     *
     * @return array The routes sorted by evaluation priority.
     */
    public function getRoutesInOrder(array $allRoutes): array;

    /**
     * Compare two routes to determine their evaluation priority.
     *
     * Return -1 if the first route should be evaluated first,
     * 0 if both routes have equal priority, or 1 if the second
     * route should be evaluated first.
     *
     * @param string $route1 The first route.
     * @param string $route2 The second route.
     *
     * @return int The comparison result: -1, 0, or 1.
     */
    public function routeOrderComparison(string $route1, string $route2): int;
}

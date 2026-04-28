<?php

namespace PHPageBuilder;

use PHPageBuilder\Contracts\AuthContract;
use PHPageBuilder\Contracts\PageBuilderContract;
use PHPageBuilder\Contracts\RouterContract;
use PHPageBuilder\Contracts\ThemeContract;
use PHPageBuilder\Contracts\WebsiteManagerContract;
use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\PageTranslationContract;
use PHPageBuilder\Core\DB;

class Application
{
    private array $config;
    private ?AuthContract $auth;
    private ?WebsiteManagerContract $websiteManager;
    private PageBuilderContract $pageBuilder;
    private RouterContract $router;
    private ThemeContract $theme;
    private DB $db;
    private Translator $translator;

    public function __construct(
        array $config,
        DB $db,
        Translator $translator,
        PageBuilderContract $pageBuilder,
        RouterContract $router,
        ThemeContract $theme,
        ?AuthContract $auth = null,
        ?WebsiteManagerContract $websiteManager = null
    ) {
        $this->config = $config;
        $this->db = $db;
        $this->translator = $translator;
        $this->pageBuilder = $pageBuilder;
        $this->router = $router;
        $this->theme = $theme;
        $this->auth = $auth;
        $this->websiteManager = $websiteManager;

        $this->boot();
    }

    private function boot(): void
    {
        $this->startSession();
        $this->loadTranslations($this->config['language'] ?? 'en');
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function loadTranslations(string $language): array
    {
        $basePath = __DIR__ . '/../lang/';
        $file = file_exists($basePath . $language . '.php')
            ? $basePath . $language . '.php'
            : $basePath . 'en.php';

        $translations = require $file;

        $themePath = $this->config['theme']['folder'] . '/' .
                     $this->config['theme']['active'] . '/translations';

        foreach (['en', $language] as $lang) {
            $path = $themePath . '/' . $lang . '.php';
            if (file_exists($path)) {
                $translations = array_merge($translations, require $path);
            }
        }

        return $this->translator->customize($translations);
    }

    public function handleRequest(?string $action = null): bool
    {
        $route = $_GET['route'] ?? null;
        $action = $action ?? ($_GET['action'] ?? null);

        if ($this->auth) {
            $this->auth->handleRequest($action);
        }

        if ($this->isWebsiteManagerRequest()) {
            $this->auth?->requireAuth();
            $this->websiteManager?->handleRequest($route, $action);
            return false;
        }

        if ($this->isPageBuilderRequest()) {
            $this->auth?->requireAuth();
            $this->pageBuilder->handleRequest($route, $action);
            return false;
        }

        if ($this->handlePublicRequest()) {
            return true;
        }

        return $this->handleFallback();
    }

    private function isWebsiteManagerRequest(): bool
    {
        return isset($_GET['module']) && $_GET['module'] === 'website_manager';
    }

    private function isPageBuilderRequest(): bool
    {
        return isset($_GET['module']) && $_GET['module'] === 'pagebuilder';
    }

    private function handlePublicRequest(): bool
    {
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $page = $this->resolvePage($url);

        if ($page) {
            echo $this->renderPage($page);
            return true;
        }

        return false;
    }

    private function handleFallback(): bool
    {
        http_response_code(404);
        echo "Page not found";
        return false;
    }

    protected function resolvePage(string $url): ?PageTranslationContract
    {
        return $this->router->resolve($url);
    }

    public function renderPageBuilder(PageContract $page): void
    {
        $this->pageBuilder->renderPageBuilder($page);
    }

    private function renderPage(PageTranslationContract $page): string
    {
        return (new PageRenderer())->render($page);
    }
}

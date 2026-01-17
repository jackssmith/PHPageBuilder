<?php

namespace PHPageBuilder;

use PHPageBuilder\Contracts\AuthContract;
use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\PageTranslationContract;
use PHPageBuilder\Contracts\WebsiteManagerContract;
use PHPageBuilder\Contracts\PageBuilderContract;
use PHPageBuilder\Contracts\RouterContract;
use PHPageBuilder\Contracts\ThemeContract;
use PHPageBuilder\Modules\GrapesJS\PageRenderer;
use PHPageBuilder\Repositories\UploadRepository;
use PHPageBuilder\Core\DB;

class PHPageBuilder
{
    /**
     * @var AuthContract|null
     */
    protected $auth;

    /**
     * @var WebsiteManagerContract|null
     */
    protected $websiteManager;

    /**
     * @var PageBuilderContract|null
     */
    protected $pageBuilder;

    /**
     * @var RouterContract|null
     */
    protected $router;

    /**
     * @var ThemeContract|null
     */
    protected $theme;

    /**
     * PHPageBuilder constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // Do nothing if no config is provided (e.g. during composer install)
        if (empty($config)) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Restore flash session data
        if (isset($_SESSION['phpb_flash'])) {
            global $phpb_flash;

            $phpb_flash = $_SESSION['phpb_flash'];
            unset($_SESSION['phpb_flash']);
        }

        $this->setConfig($config);

        // Database
        if (phpb_config('storage.use_database')) {
            $this->setDatabaseConnection(phpb_config('storage.database'));
        }

        // Authentication
        if (phpb_config('auth.use_login')) {
            $this->auth = phpb_instance('auth');
        }

        // Website manager
        if (phpb_config('website_manager.use_website_manager')) {
            $this->websiteManager = phpb_instance('website_manager');
        }

        // Core services
        $this->pageBuilder = phpb_instance('pagebuilder');

        $this->theme = phpb_instance('theme', [
            phpb_config('theme'),
            phpb_config('theme.active_theme'),
        ]);

        $this->router = phpb_instance('router');

        // Load translations
        $this->loadTranslations(phpb_current_language());
    }

    /**
     * Load translations for a given language.
     */
    public function loadTranslations(string $language): array
    {
        global $phpb_translations;

        $phpbLanguageFile = __DIR__ . '/../lang/' . $language . '.php';

        if (! file_exists($phpbLanguageFile)) {
            $phpbLanguageFile = __DIR__ . '/../lang/en.php';
        }

        $phpb_translations = require $phpbLanguageFile;

        // Theme translations
        $themeTranslationsFolder =
            phpb_config('theme.folder') . '/' .
            phpb_config('theme.active_theme') . '/translations';

        if (file_exists($themeTranslationsFolder . '/en.php')) {
            $phpb_translations = array_merge(
                $phpb_translations,
                require $themeTranslationsFolder . '/en.php'
            );
        }

        if (file_exists($themeTranslationsFolder . '/' . $language . '.php')) {
            $phpb_translations = array_merge(
                $phpb_translations,
                require $themeTranslationsFolder . '/' . $language . '.php'
            );
        }

        $phpb_translations = phpb_instance(Translator::class)
            ->customize($phpb_translations);

        return $phpb_translations;
    }

    /**
     * Configuration
     */
    public function setConfig(array $config): void
    {
        global $phpb_config;

        $phpb_config = $config;
    }

    public function setDatabaseConnection(array $config): void
    {
        global $phpb_db;

        $phpb_db = new DB($config);
    }

    /**
     * Dependency setters
     */
    public function setAuth(AuthContract $auth): void
    {
        $this->auth = $auth;
    }

    public function setWebsiteManager(WebsiteManagerContract $websiteManager): void
    {
        $this->websiteManager = $websiteManager;
    }

    public function setPageBuilder(PageBuilderContract $pageBuilder): void
    {
        $this->pageBuilder = $pageBuilder;
    }

    public function setRouter(RouterContract $router): void
    {
        $this->router = $router;
    }

    public function setTheme(ThemeContract $theme): void
    {
        $this->theme = $theme;

        if ($this->pageBuilder !== null) {
            $this->pageBuilder->setTheme($theme);
        }
    }

    /**
     * Dependency getters
     */
    public function getAuth(): ?AuthContract
    {
        return $this->auth;
    }

    public function getWebsiteManager(): ?WebsiteManagerContract
    {
        return $this->websiteManager;
    }

    public function getPageBuilder(): ?PageBuilderContract
    {
        return $this->pageBuilder;
    }

    public function getRouter(): ?RouterContract
    {
        return $this->router;
    }

    public function getTheme(): ?ThemeContract
    {
        return $this->theme;
    }

    /**
     * Handle request
     */
    public function handleRequest(?string $action = null): bool
    {
        $route  = $_GET['route']  ?? null;
        $action = $action ?? ($_GET['action'] ?? null);

        if (
            ! phpb_config('auth.use_login') ||
            ! phpb_config('website_manager.use_website_manager')
        ) {
            die(
                'PHPageBuilder Authentication module is disabled.<br>' .
                'Implement your own authentication and call the proper handler.'
            );
        }

        $this->auth->handleRequest($action);

        if (phpb_in_module('website_manager')) {
            $this->auth->requireAuth();
            $this->websiteManager->handleRequest($route, $action);

            header('HTTP/1.1 404 Not Found');
            exit('WebsiteManager page not found');
        }

        if (phpb_in_module('pagebuilder')) {
            $this->auth->requireAuth();
            phpb_set_in_editmode();

            $this->pageBuilder->handleRequest($route, $action);

            header('HTTP/1.1 404 Not Found');
            exit('PageBuilder page not found');
        }

        if ($this->handlePublicRequest()) {
            return true;
        }

        if (phpb_current_relative_url() === '/') {
            $this->websiteManager->renderWelcomePage();
            return true;
        }

        header('HTTP/1.1 404 Not Found');
        exit(
            'Page not found: <b>' .
            phpb_e(phpb_full_url(phpb_current_relative_url())) .
            '</b>'
        );
    }

    /**
     * Resolve page by URL
     */
    protected function resolvePageLanguageVariantFromUrl(string $url): ?PageTranslationContract
    {
        return $this->router->resolve($url);
    }

    /**
     * Render PageBuilder
     */
    public function renderPageBuilder(PageContract $page): void
    {
        phpb_set_in_editmode();
        $this->pageBuilder->renderPageBuilder($page);
    }
}

<?php

namespace PHPageBuilder\Modules\WebsiteManager;

use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\WebsiteManagerContract;
use PHPageBuilder\Repositories\PageRepository;
use PHPageBuilder\Repositories\SettingRepository;

class WebsiteManager implements WebsiteManagerContract
{
    /**
     * Entry point for all website manager requests.
     */
    public function handleRequest(?string $route, ?string $action): void
    {
        if ($route === null) {
            $this->renderOverview();
            return;
        }

        switch ($route) {
            case 'settings':
                $this->handleSettingsRoute($action);
                return;

            case 'page_settings':
                $this->handlePageSettingsRoute($action);
                return;
        }
    }

    /**
     * Handle settings-related routes.
     */
    protected function handleSettingsRoute(?string $action): void
    {
        if ($action === 'renderBlockThumbs') {
            $this->renderBlockThumbs();
            return;
        }

        if ($action === 'update') {
            $this->handleUpdateSettings();
            return;
        }
    }

    /**
     * Handle page settings routes.
     */
    protected function handlePageSettingsRoute(?string $action): void
    {
        if ($action === 'create') {
            $this->handleCreate();
            return;
        }

        $pageId = $this->getInt('page');
        $page = (new PageRepository)->findWithId($pageId);

        if (! $page instanceof PageContract) {
            $this->redirectToManager();
            return;
        }

        if ($action === 'edit') {
            $this->handleEdit($page);
            return;
        }

        if ($action === 'destroy') {
            $this->handleDestroy($page);
        }
    }

    /**
     * Create a new page.
     */
    public function handleCreate(): void
    {
        if ($this->isPost()) {
            $data = $this->sanitize($_POST);

            $page = (new PageRepository)->create($data);

            if ($page) {
                $this->redirectWithMessage(
                    'website-manager.page-created'
                );
                return;
            }
        }

        $this->renderPageSettings();
    }

    /**
     * Edit an existing page.
     */
    public function handleEdit(PageContract $page): void
    {
        if ($this->isPost()) {
            $data = $this->sanitize($_POST);

            $success = (new PageRepository)->update($page, $data);

            if ($success) {
                $this->redirectWithMessage(
                    'website-manager.page-updated'
                );
                return;
            }
        }

        $this->renderPageSettings($page);
    }

    /**
     * Delete a page.
     */
    public function handleDestroy(PageContract $page): void
    {
        (new PageRepository)->destroy($page->getId());

        $this->redirectWithMessage(
            'website-manager.page-deleted'
        );
    }

    /**
     * Update website settings.
     */
    public function handleUpdateSettings(): void
    {
        if (! $this->isPost()) {
            return;
        }

        $data = $this->sanitize($_POST);

        $success = (new SettingRepository)->updateSettings($data);

        if ($success) {
            phpb_redirect(
                phpb_url('website_manager', ['tab' => 'settings']),
                [
                    'message-type' => 'success',
                    'message' => phpb_trans('website-manager.settings-updated'),
                ]
            );
        }
    }

    /**
     * Render overview page.
     */
    public function renderOverview(): void
    {
        $pages = (new PageRepository)->getAll();

        $viewFile = 'overview';
        require __DIR__ . '/resources/layouts/master.php';
    }

    /**
     * Render page create/edit form.
     */
    public function renderPageSettings(PageContract $page = null): void
    {
        $action = $page ? 'edit' : 'create';

        $theme = phpb_instance('theme', [
            phpb_config('theme'),
            phpb_config('theme.active_theme'),
        ]);

        $viewFile = 'page-settings';
        require __DIR__ . '/resources/layouts/master.php';
    }

    /**
     * Render menu settings.
     */
    public function renderMenuSettings(): void
    {
        $viewFile = 'menu-settings';
        require __DIR__ . '/resources/layouts/master.php';
    }

    /**
     * Render block thumbnails.
     */
    public function renderBlockThumbs(): void
    {
        $viewFile = 'block-thumbs';
        require __DIR__ . '/resources/layouts/master.php';
    }

    /**
     * Render welcome page.
     */
    public function renderWelcomePage(): void
    {
        $viewFile = 'welcome';
        require __DIR__ . '/resources/layouts/empty.php';
    }

    /* -----------------------------------------------------------------
     | Helper methods
     | ----------------------------------------------------------------- */

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function getInt(string $key): ?int
    {
        return isset($_GET[$key]) ? (int) $_GET[$key] : null;
    }

    protected function sanitize(array $data): array
    {
        return array_map(static function ($value) {
            return is_string($value)
                ? trim(strip_tags($value))
                : $value;
        }, $data);
    }

    protected function redirectToManager(): void
    {
        phpb_redirect(phpb_url('website_manager'));
    }

    protected function redirectWithMessage(string $translationKey): void
    {
        phpb_redirect(
            phpb_url('website_manager'),
            [
                'message-type' => 'success',
                'message' => phpb_trans($translationKey),
            ]
        );
    }
}

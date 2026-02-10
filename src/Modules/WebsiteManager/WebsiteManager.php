<?php

namespace PHPageBuilder\Modules\WebsiteManager;

use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\WebsiteManagerContract;
use PHPageBuilder\Repositories\PageRepository;
use PHPageBuilder\Repositories\SettingRepository;

class WebsiteManager implements WebsiteManagerContract
{
    public function __construct(
        protected PageRepository $pages,
        protected SettingRepository $settings
    ) {}

    /* -----------------------------------------------------------------
     | Entry point
     | ----------------------------------------------------------------- */

    public function handleRequest(?string $route, ?string $action): void
    {
        if ($route === null) {
            $this->renderOverview();
            return;
        }

        match ($route) {
            'settings'      => $this->handleSettingsRoute($action),
            'page_settings' => $this->handlePageSettingsRoute($action),
            default         => $this->redirectToManager(),
        };
    }

    /* -----------------------------------------------------------------
     | Route handlers
     | ----------------------------------------------------------------- */

    protected function handleSettingsRoute(?string $action): void
    {
        match ($action) {
            'renderBlockThumbs' => $this->renderBlockThumbs(),
            'update'            => $this->handleUpdateSettings(),
            default             => $this->redirectToManager(),
        };
    }

    protected function handlePageSettingsRoute(?string $action): void
    {
        if ($action === 'create') {
            $this->handleCreate();
            return;
        }

        $pageId = $this->getInt('page');
        $page   = $pageId ? $this->pages->findWithId($pageId) : null;

        if (! $page instanceof PageContract) {
            $this->redirectToManager();
            return;
        }

        match ($action) {
            'edit'    => $this->handleEdit($page),
            'destroy' => $this->handleDestroy($page),
            default   => $this->redirectToManager(),
        };
    }

    /* -----------------------------------------------------------------
     | Page actions
     | ----------------------------------------------------------------- */

    public function handleCreate(): void
    {
        if ($this->isPost()) {
            $page = $this->pages->create(
                $this->sanitize($_POST)
            );

            if ($page) {
                $this->redirectWithMessage('website-manager.page-created');
                return;
            }
        }

        $this->renderPageSettings();
    }

    public function handleEdit(PageContract $page): void
    {
        if ($this->isPost()) {
            $success = $this->pages->update(
                $page,
                $this->sanitize($_POST)
            );

            if ($success) {
                $this->redirectWithMessage('website-manager.page-updated');
                return;
            }
        }

        $this->renderPageSettings($page);
    }

    public function handleDestroy(PageContract $page): void
    {
        $this->pages->destroy($page->getId());

        $this->redirectWithMessage('website-manager.page-deleted');
    }

    /* -----------------------------------------------------------------
     | Settings actions
     | ----------------------------------------------------------------- */

    public function handleUpdateSettings(): void
    {
        if (! $this->isPost()) {
            return;
        }

        if ($this->settings->updateSettings(
            $this->sanitize($_POST)
        )) {
            phpb_redirect(
                phpb_url('website_manager', ['tab' => 'settings']),
                [
                    'message-type' => 'success',
                    'message'      => phpb_trans('website-manager.settings-updated'),
                ]
            );
        }
    }

    /* -----------------------------------------------------------------
     | Rendering
     | ----------------------------------------------------------------- */

    public function renderOverview(): void
    {
        $pages = $this->pages->getAll();

        $this->render('overview');
    }

    public function renderPageSettings(?PageContract $page = null): void
    {
        $action = $page ? 'edit' : 'create';

        $theme = phpb_instance('theme', [
            phpb_config('theme'),
            phpb_config('theme.active_theme'),
        ]);

        $this->render('page-settings');
    }

    public function renderMenuSettings(): void
    {
        $this->render('menu-settings');
    }

    public function renderBlockThumbs(): void
    {
        $this->render('block-thumbs');
    }

    public function renderWelcomePage(): void
    {
        $this->render('welcome', 'empty');
    }

    protected function render(string $view, string $layout = 'master'): void
    {
        $viewFile = $view;
        require __DIR__ . "/resources/layouts/{$layout}.php";
    }

    /* -----------------------------------------------------------------
     | Helpers
     | ----------------------------------------------------------------- */

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    protected function getInt(string $key): ?int
    {
        return isset($_GET[$key]) ? (int) $_GET[$key] : null;
    }

    protected function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim(strip_tags($value));
            }
        }

        return $data;
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
                'message'      => phpb_trans($translationKey),
            ]
        );
    }
}

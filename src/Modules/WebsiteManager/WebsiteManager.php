<?php

namespace PHPageBuilder\Modules\WebsiteManager;

use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\WebsiteManagerContract;
use PHPageBuilder\Repositories\PageRepository;
use PHPageBuilder\Repositories\SettingRepository;
use Throwable;

class WebsiteManager implements WebsiteManagerContract
{
    public function __construct(
        protected PageRepository $pages,
        protected SettingRepository $settings
    ) {}

    /* -------------------------------------------------------------
     | Entry point
     | ------------------------------------------------------------- */

    public function handleRequest(
        ?string $route,
        ?string $action
    ): void {
        if ($route === null) {
            $this->renderOverview();
            return;
        }

        match ($route) {
            'settings'      => $this->handleSettingsRoute($action),
            'page_settings' => $this->handlePageSettingsRoute($action),
            'pages'         => $this->handlePagesRoute($action),
            'seo'           => $this->handleSeoRoute($action),
            default         => $this->redirectToManager(),
        };
    }

    /* -------------------------------------------------------------
     | Route handlers
     | ------------------------------------------------------------- */

    protected function handleSettingsRoute(?string $action): void
    {
        match ($action) {
            'renderBlockThumbs' => $this->renderBlockThumbs(),
            'update'            => $this->handleUpdateSettings(),
            'general'           => $this->renderGeneralSettings(),
            'social'            => $this->renderSocialSettings(),
            default             => $this->redirectToManager(),
        };
    }

    protected function handlePageSettingsRoute(?string $action): void
    {
        if ($action === 'create') {
            $this->handleCreate();
            return;
        }

        $page = $this->findPageFromRequest();

        if (! $page instanceof PageContract) {
            $this->redirectToManager();
            return;
        }

        match ($action) {
            'edit'      => $this->handleEdit($page),
            'destroy'   => $this->handleDestroy($page),
            'duplicate' => $this->handleDuplicate($page),
            'publish'   => $this->handlePublish($page, true),
            'unpublish' => $this->handlePublish($page, false),
            default     => $this->redirectToManager(),
        };
    }

    protected function handlePagesRoute(?string $action): void
    {
        match ($action) {
            'search' => $this->handleSearch(),
            'bulk'   => $this->handleBulkActions(),
            'sort'   => $this->handleSort(),
            default  => $this->redirectToManager(),
        };
    }

    protected function handleSeoRoute(?string $action): void
    {
        match ($action) {
            'update' => $this->handleUpdateSeo(),
            default  => $this->redirectToManager(),
        };
    }

    /* -------------------------------------------------------------
     | Page actions
     | ------------------------------------------------------------- */

    public function handleCreate(): void
    {
        if ($this->isPost()) {
            $data = $this->sanitize($_POST);

            if ($this->validatePageData($data)) {
                $page = $this->pages->create($data);

                if ($page) {
                    $this->redirectWithMessage(
                        'website-manager.page-created'
                    );
                    return;
                }
            }
        }

        $this->renderPageSettings();
    }

    public function handleEdit(PageContract $page): void
    {
        if ($this->isPost()) {
            $data = $this->sanitize($_POST);

            if ($this->validatePageData($data, $page)) {
                if ($this->pages->update($page, $data)) {
                    $this->redirectWithMessage(
                        'website-manager.page-updated'
                    );
                    return;
                }
            }
        }

        $this->renderPageSettings($page);
    }

    public function handleDestroy(PageContract $page): void
    {
        if (! $this->isPost()) {
            $this->redirectToManager();
            return;
        }

        if ($this->pages->destroy($page->getId())) {
            $this->redirectWithMessage(
                'website-manager.page-deleted'
            );
            return;
        }

        $this->redirectToManager();
    }

    public function handleDuplicate(PageContract $page): void
    {
        if (! $this->isPost()) {
            $this->redirectToManager();
            return;
        }

        $data = $this->getPageData($page);

        unset($data['id']);

        $data['title'] = ($data['title'] ?? 'Untitled') . ' (Copy)';
        $data['slug'] = $this->generateUniqueSlug(
            $data['title']
        );

        $data['status'] = 'draft';

        if ($this->pages->create($data)) {
            $this->redirectWithMessage(
                'website-manager.page-duplicated'
            );
            return;
        }

        $this->redirectToManager();
    }

    public function handlePublish(
        PageContract $page,
        bool $publish
    ): void {
        if (! $this->isPost()) {
            $this->redirectToManager();
            return;
        }

        $data = ['status' => $publish ? 'published' : 'draft'];

        if ($this->pages->update($page, $data)) {
            $this->redirectWithMessage(
                $publish
                    ? 'website-manager.page-published'
                    : 'website-manager.page-unpublished'
            );
            return;
        }

        $this->redirectToManager();
    }

    /* -------------------------------------------------------------
     | Page search and sorting
     | ------------------------------------------------------------- */

    protected function handleSearch(): void
    {
        $keyword = $this->getString('search');

        $pages = $this->pages->getAll();

        if ($keyword !== '') {
            $pages = array_filter(
                $pages,
                fn ($page) => stripos(
                    $page->getTitle(),
                    $keyword
                ) !== false
            );
        }

        $this->render('overview');
    }

    protected function handleSort(): void
    {
        if (! $this->isPost()) {
            $this->redirectToManager();
            return;
        }

        $order = $_POST['order'] ?? [];

        if (! is_array($order)) {
            $this->redirectToManager();
            return;
        }

        // Implement updateOrder() in PageRepository.
        // Validate page ownership and IDs before updating.

        $this->redirectWithMessage(
            'website-manager.pages-sorted'
        );
    }

    protected function handleBulkActions(): void
    {
        if (! $this->isPost()) {
            $this->redirectToManager();
            return;
        }

        $action = $this->getString('bulk_action');
        $ids = $_POST['page_ids'] ?? [];

        if (! is_array($ids) || empty($ids)) {
            $this->redirectToManager();
            return;
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn ($id) => $id > 0
        )));

        foreach ($ids as $id) {
            $page = $this->pages->findWithId($id);

            if (! $page instanceof PageContract) {
                continue;
            }

            match ($action) {
                'publish' => $this->pages->update(
                    $page,
                    ['status' => 'published']
                ),
                'draft' => $this->pages->update(
                    $page,
                    ['status' => 'draft']
                ),
                'delete' => $this->pages->destroy($id),
                default => null,
            };
        }

        $this->redirectWithMessage(
            'website-manager.bulk-action-completed'
        );
    }

    /* -------------------------------------------------------------
     | SEO management
     | ------------------------------------------------------------- */

    protected function handleUpdateSeo(): void
    {
        if (! $this->isPost()) {
            $this->redirectToManager();
            return;
        }

        $page = $this->findPageFromRequest();

        if (! $page instanceof PageContract) {
            $this->redirectToManager();
            return;
        }

        $data = $this->sanitize($_POST);

        $seo = [
            'meta_title'       => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'meta_keywords'    => $data['meta_keywords'] ?? '',
            'canonical_url'    => $data['canonical_url'] ?? '',
            'robots'           => $data['robots'] ?? 'index,follow',
        ];

        if ($this->pages->update($page, $seo)) {
            $this->redirectWithMessage(
                'website-manager.seo-updated'
            );
            return;
        }

        $this->redirectToManager();
    }

    /* -------------------------------------------------------------
     | Website settings
     | ------------------------------------------------------------- */

    public function handleUpdateSettings(): void
    {
        if (! $this->isPost()) {
            $this->redirectToManager();
            return;
        }

        $data = $this->sanitize($_POST);

        if ($this->settings->updateSettings($data)) {
            $this->redirectWithMessage(
                'website-manager.settings-updated'
            );
        }
    }

    public function renderGeneralSettings(): void
    {
        $this->render('general-settings');
    }

    public function renderSocialSettings(): void
    {
        $this->render('social-settings');
    }

    /* -------------------------------------------------------------
     | Rendering
     | ------------------------------------------------------------- */

    public function renderOverview(): void
    {
        $pages = $this->pages->getAll();

        $statistics = $this->getStatistics($pages);

        $this->render('overview');
    }

    public function renderPageSettings(
        ?PageContract $page = null
    ): void {
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

    /* -------------------------------------------------------------
     | Statistics
     | ------------------------------------------------------------- */

    protected function getStatistics(array $pages): array
    {
        $total = count($pages);

        $published = 0;
        $drafts = 0;

        foreach ($pages as $page) {
            $data = $this->getPageData($page);

            if (($data['status'] ?? '') === 'published') {
                $published++;
            } else {
                $drafts++;
            }
        }

        return [
            'total'     => $total,
            'published' => $published,
            'drafts'    => $drafts,
        ];
    }

    /* -------------------------------------------------------------
     | Validation
     | ------------------------------------------------------------- */

    protected function validatePageData(
        array $data,
        ?PageContract $page = null
    ): bool {
        $title = $data['title'] ?? '';
        $slug = $data['slug'] ?? '';

        if ($title === '' || $slug === '') {
            return false;
        }

        if (strlen($title) > 255) {
            return false;
        }

        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return false;
        }

        return true;
    }

    /* -------------------------------------------------------------
     | Helpers
     | ------------------------------------------------------------- */

    protected function findPageFromRequest(): ?PageContract
    {
        $id = $this->getInt('page');

        if (! $id) {
            return null;
        }

        $page = $this->pages->findWithId($id);

        return $page instanceof PageContract ? $page : null;
    }

    protected function getPageData(PageContract $page): array
    {
        return [
            'id'    => $page->getId(),
            'title' => $page->getTitle(),
        ];
    }

    protected function generateUniqueSlug(string $title): string
    {
        $slug = strtolower(trim($title));

        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug ?: 'new-page-' . uniqid();
    }

    protected function getInt(string $key): ?int
    {
        if (! isset($_GET[$key])) {
            return null;
        }

        $value = filter_var(
            $_GET[$key],
            FILTER_VALIDATE_INT
        );

        return $value !== false && $value > 0
            ? $value
            : null;
    }

    protected function getString(string $key): string
    {
        $value = $_GET[$key] ?? '';

        return is_string($value)
            ? trim($value)
            : '';
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    protected function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }

    protected function redirectToManager(): void
    {
        phpb_redirect(phpb_url('website_manager'));
    }

    protected function redirectWithMessage(
        string $translationKey
    ): void {
        phpb_redirect(
            phpb_url('website_manager'),
            [
                'message-type' => 'success',
                'message' => phpb_trans($translationKey),
            ]
        );
    }

    protected function render(
        string $view,
        string $layout = 'master'
    ): void {
        $viewFile = $view;

        require __DIR__ .
            "/resources/layouts/{$layout}.php";
    }
}

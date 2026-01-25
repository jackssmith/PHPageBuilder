<?php

declare(strict_types=1);

namespace PHPageBuilder\Modules\GrapesJS;

use Exception;
use RuntimeException;
use PHPageBuilder\Contracts\PageBuilderContract;
use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\ThemeContract;
use PHPageBuilder\Modules\GrapesJS\Block\BlockAdapter;
use PHPageBuilder\Modules\GrapesJS\Thumb\ThumbGenerator;
use PHPageBuilder\Modules\GrapesJS\Upload\Uploader;
use PHPageBuilder\Repositories\PageRepository;
use PHPageBuilder\Repositories\UploadRepository;

class PageBuilder implements PageBuilderContract
{
    protected ThemeContract $theme;
    protected array $scripts = [];
    protected array $pages = [];
    protected ?string $css = null;

    public function __construct()
    {
        $this->theme = phpb_instance('theme', [
            phpb_config('theme'),
            phpb_config('theme.active_theme')
        ]);
    }

    /* -----------------------------------------------------------------
     |  Public API
     | -----------------------------------------------------------------
     */

    public function setTheme(ThemeContract $theme): void
    {
        $this->theme = $theme;
    }

    public function handleRequest(string $route, ?string $action, PageContract $page = null): bool
    {
        phpb_set_in_editmode();

        if ($route === 'thumb_generator') {
            return (new ThumbGenerator($this->theme))
                ->handleThumbRequest($action);
        }

        $page ??= (new PageRepository())->findWithId($this->get('page'));

        if (! $page instanceof PageContract) {
            return false;
        }

        return match ($action) {
            null, 'edit' => $this->handleEdit($page),
            'store' => $this->handleStore($page),
            'upload' => $this->handleUpload(),
            'upload_delete' => $this->handleUploadDelete(),
            'renderBlock' => $this->handleRenderBlock($page),
            'renderLanguageVariant' => $this->handleRenderLanguageVariant($page),
            default => false,
        };
    }

    public function renderPage(PageContract $page, ?string $language = null): string
    {
        $renderer = phpb_instance(PageRenderer::class, [$this->theme, $page]);

        if ($language !== null) {
            $renderer->setLanguage($language);
        }

        return $renderer->render();
    }

    public function updatePage(PageContract $page, array $data)
    {
        return (new PageRepository())->updatePageData($page, $data);
    }

    public function setPages(array $pages): void
    {
        $this->pages = $pages;
    }

    public function getPages(): array
    {
        if (! empty($this->pages)) {
            return $this->pages;
        }

        $pages = [];
        foreach ((new PageRepository())->getAll() as $page) {
            $pages[] = [
                phpb_e($page->getName()),
                phpb_e($page->getId())
            ];
        }

        return $this->pages = $pages;
    }

    public function getPageComponents(PageContract $page): array
    {
        $components = $page->getBuilderData()['components'] ?? [0 => []];

        // Backward compatibility
        if (isset($components[0]) && ! isset($components[0][0])) {
            return [0 => $components];
        }

        return $components;
    }

    public function getPageStyleComponents(PageContract $page): array
    {
        return $page->getBuilderData()['style'] ?? [];
    }

    public function getPageStyleCss(PageContract $page): string
    {
        return (string) ($page->getBuilderData()['css'] ?? '');
    }

    public function customStyle(?string $css = null): ?string
    {
        if ($css !== null) {
            $this->css = $css;
        }

        return $this->css;
    }

    public function customScripts(string $location, ?string $scripts = null): string
    {
        if ($scripts !== null) {
            $this->scripts[$location] = $scripts;
        }

        return $this->scripts[$location] ?? '';
    }

    /* -----------------------------------------------------------------
     |  Action handlers
     | -----------------------------------------------------------------
     */

    protected function handleEdit(PageContract $page): bool
    {
        $this->renderPageBuilder($page);
        return true;
    }

    protected function handleStore(PageContract $page): bool
    {
        $data = $this->decodeJson($this->post('data'));
        $this->updatePage($page, $data);
        return true;
    }

    protected function handleUpload(): bool
    {
        $this->handleFileUpload();
        return true;
    }

    protected function handleUploadDelete(): bool
    {
        $this->handleFileDelete();
        return true;
    }

    protected function handleRenderBlock(PageContract $page): bool
    {
        $language = $this->post('language');

        if (! isset(phpb_active_languages()[$language])) {
            return false;
        }

        $this->renderPageBuilderBlock(
            $page,
            $language,
            $this->decodeJson($this->post('data'))
        );

        return true;
    }

    protected function handleRenderLanguageVariant(PageContract $page): bool
    {
        $language = $this->post('language');

        if (! isset(phpb_active_languages()[$language])) {
            return false;
        }

        $this->renderLanguageVariant(
            $page,
            $language,
            $this->decodeJson($this->post('data'))
        );

        return true;
    }

    /* -----------------------------------------------------------------
     |  Uploads
     | -----------------------------------------------------------------
     */

    protected function handleFileUpload(): void
    {
        $publicId = sha1(uniqid((string) mt_rand(), true));

        $uploader = phpb_instance(Uploader::class, ['files']);
        $uploader
            ->file_name($publicId . '/' . str_replace(' ', '-', $uploader->file_src_name))
            ->upload_to(phpb_config('storage.uploads_folder') . '/')
            ->run();

        if (! $uploader->was_uploaded) {
            throw new RuntimeException("Upload error: {$uploader->error}");
        }

        $upload = (new UploadRepository())->create([
            'public_id' => $publicId,
            'original_file' => str_replace(' ', '-', $uploader->file_src_name),
            'mime_type' => $uploader->file_src_mime,
            'server_file' => $uploader->final_file_name
        ]);

        echo json_encode([
            'data' => [
                'public_id' => $publicId,
                'src' => $upload->getUrl(),
                'type' => 'image'
            ]
        ]);
    }

    protected function handleFileDelete(): void
    {
        $publicId = $this->post('id');

        $repo = new UploadRepository();
        $result = $repo->findWhere('public_id', $publicId);

        if (empty($result)) {
            echo json_encode(['success' => false, 'message' => 'File not found']);
            return;
        }

        $file = $result[0];
        $repo->destroy($file->id);

        $filePath = phpb_config('storage.uploads_folder') . '/' . $file->server_file;
        if (is_file($filePath)) {
            unlink($filePath);
        }

        $dir = dirname($filePath);
        if (is_dir($dir) && count(scandir($dir)) === 2) {
            rmdir($dir);
        }

        echo json_encode(['success' => true]);
    }

    /* -----------------------------------------------------------------
     |  Rendering
     | -----------------------------------------------------------------
     */

    protected function renderPageBuilder(PageContract $page): void
    {
        phpb_set_in_editmode();

        $pageBuilder = $this;
        $renderer = phpb_instance(PageRenderer::class, [$this->theme, $page, true]);

        if (! empty($_SESSION['phpagebuilder_language'])) {
            $renderer->setLanguage($_SESSION['phpagebuilder_language']);
        }

        $blocks = [];
        $blockSettings = [];

        foreach ($this->theme->getThemeBlocks() as $block) {
            $slug = phpb_e($block->getSlug());
            $adapter = phpb_instance(BlockAdapter::class, [$renderer, $block]);

            if ($block->get('hidden') !== true) {
                $blocks[$slug] = $adapter->getBlockManagerArray();
            }

            $blockSettings[$slug] = $adapter->getBlockSettingsArray();
        }

        $assets = [];
        foreach ((new UploadRepository())->getAll() as $file) {
            $assets[] = [
                'src' => $file->getUrl(),
                'public_id' => $file->public_id
            ];
        }

        require __DIR__ . '/resources/views/layout.php';
    }

    protected function renderPageBuilderBlock(PageContract $page, string $language, array $blockData): void
    {
        phpb_set_in_editmode();

        $page->setData(['data' => $blockData], false);

        $renderer = phpb_instance(PageRenderer::class, [$this->theme, $page, true]);
        $renderer->setLanguage($language);

        echo $renderer->parseShortcodes(
            $blockData['html'] ?? '',
            $blockData['blocks'] ?? []
        );
    }

    protected function renderLanguageVariant(PageContract $page, string $language, array $blockData): void
    {
        phpb_set_in_editmode();
        $_SESSION['phpagebuilder_language'] = $language;

        $page->setData(['data' => $blockData], false);

        $renderer = phpb_instance(PageRenderer::class, [$this->theme, $page, true]);
        $renderer->setLanguage($language);

        echo json_encode([
            'dynamicBlocks' => $renderer->getPageBlocksData()[$language] ?? []
        ]);
    }

    /* -----------------------------------------------------------------
     |  Helpers
     | -----------------------------------------------------------------
     */

    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    protected function decodeJson(?string $json): array
    {
        if (! $json) {
            return [];
        }

        $data = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $data : [];
    }
}

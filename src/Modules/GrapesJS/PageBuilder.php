<?php

declare(strict_types=1);

namespace PHPageBuilder\Modules\GrapesJS;

use JsonException;
use RuntimeException;
use PHPageBuilder\Contracts\PageBuilderContract;
use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\ThemeContract;
use PHPageBuilder\Modules\GrapesJS\Block\BlockAdapter;
use PHPageBuilder\Modules\GrapesJS\Thumb\ThumbGenerator;
use PHPageBuilder\Modules\GrapesJS\Upload\Uploader;
use PHPageBuilder\Repositories\PageRepository;
use PHPageBuilder\Repositories\UploadRepository;

final class PageBuilder implements PageBuilderContract
{
    private ThemeContract $theme;

    /**
     * @var array<string, string>
     */
    private array $scripts = [];

    /**
     * @var array<int, array{0:string,1:string}>
     */
    private array $pages = [];

    private ?string $customCss = null;

    private PageRepository $pageRepository;

    private UploadRepository $uploadRepository;

    public function __construct(
        ?ThemeContract $theme = null,
        ?PageRepository $pageRepository = null,
        ?UploadRepository $uploadRepository = null
    ) {
        $this->theme = $theme ?? phpb_instance('theme', [
            phpb_config('theme'),
            phpb_config('theme.active_theme'),
        ]);

        $this->pageRepository = $pageRepository ?? new PageRepository();
        $this->uploadRepository = $uploadRepository ?? new UploadRepository();
    }

    /* -----------------------------------------------------------------
     | Public API
     | -----------------------------------------------------------------
     */

    public function setTheme(ThemeContract $theme): void
    {
        $this->theme = $theme;
    }

    public function handleRequest(
        string $route,
        ?string $action,
        ?PageContract $page = null
    ): bool {
        phpb_set_in_editmode();

        if ($route === 'thumb_generator') {
            return $this->handleThumbGeneration($action);
        }

        $page ??= $this->resolvePage();

        if (! $page instanceof PageContract) {
            return false;
        }

        return match ($action) {
            null,
            'edit'                  => $this->handleEdit($page),
            'store'                 => $this->handleStore($page),
            'upload'                => $this->handleUpload(),
            'upload_delete'         => $this->handleUploadDelete(),
            'renderBlock'           => $this->handleRenderBlock($page),
            'renderLanguageVariant' => $this->handleRenderLanguageVariant($page),
            default                 => false,
        };
    }

    public function renderPage(
        PageContract $page,
        ?string $language = null
    ): string {
        $renderer = $this->makeRenderer($page);

        if ($language !== null) {
            $renderer->setLanguage($language);
        }

        return $renderer->render();
    }

    public function updatePage(PageContract $page, array $data)
    {
        return $this->pageRepository->updatePageData($page, $data);
    }

    /**
     * @param array<int, array{0:string,1:string}> $pages
     */
    public function setPages(array $pages): void
    {
        $this->pages = $pages;
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    public function getPages(): array
    {
        if ($this->pages !== []) {
            return $this->pages;
        }

        $this->pages = array_map(
            static fn ($page): array => [
                phpb_e($page->getName()),
                phpb_e($page->getId()),
            ],
            $this->pageRepository->getAll()
        );

        return $this->pages;
    }

    public function getPageComponents(PageContract $page): array
    {
        $components = $page->getBuilderData()['components'] ?? [[]];

        // Backward compatibility with old structure.
        if (isset($components[0]) && ! isset($components[0][0])) {
            return [$components];
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
            $this->customCss = $css;
        }

        return $this->customCss;
    }

    public function customScripts(
        string $location,
        ?string $scripts = null
    ): string {
        if ($scripts !== null) {
            $this->scripts[$location] = $scripts;
        }

        return $this->scripts[$location] ?? '';
    }

    /* -----------------------------------------------------------------
     | Request Handlers
     | -----------------------------------------------------------------
     */

    private function handleThumbGeneration(?string $action): bool
    {
        return (new ThumbGenerator($this->theme))
            ->handleThumbRequest($action);
    }

    private function handleEdit(PageContract $page): bool
    {
        $this->renderEditor($page);

        return true;
    }

    private function handleStore(PageContract $page): bool
    {
        $payload = $this->decodeJson(
            $this->post('data')
        );

        $this->updatePage($page, $payload);

        return true;
    }

    private function handleUpload(): bool
    {
        $this->uploadFile();

        return true;
    }

    private function handleUploadDelete(): bool
    {
        $this->deleteUploadedFile();

        return true;
    }

    private function handleRenderBlock(PageContract $page): bool
    {
        $language = (string) $this->post('language');

        if (! $this->isValidLanguage($language)) {
            return false;
        }

        $this->renderBlockPreview(
            $page,
            $language,
            $this->decodeJson($this->post('data'))
        );

        return true;
    }

    private function handleRenderLanguageVariant(PageContract $page): bool
    {
        $language = (string) $this->post('language');

        if (! $this->isValidLanguage($language)) {
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
     | Upload Handling
     | -----------------------------------------------------------------
     */

    private function uploadFile(): void
    {
        $publicId = bin2hex(random_bytes(20));

        $uploader = phpb_instance(Uploader::class, ['files']);

        $safeFileName = str_replace(
            ' ',
            '-',
            $uploader->file_src_name
        );

        $uploader
            ->file_name($publicId . '/' . $safeFileName)
            ->upload_to(phpb_config('storage.uploads_folder') . '/')
            ->run();

        if (! $uploader->was_uploaded) {
            throw new RuntimeException(
                sprintf('File upload failed: %s', $uploader->error)
            );
        }

        $upload = $this->uploadRepository->create([
            'public_id'    => $publicId,
            'original_file'=> $safeFileName,
            'mime_type'    => $uploader->file_src_mime,
            'server_file'  => $uploader->final_file_name,
        ]);

        $this->jsonResponse([
            'data' => [
                'public_id' => $publicId,
                'src'       => $upload->getUrl(),
                'type'      => 'image',
            ],
        ]);
    }

    private function deleteUploadedFile(): void
    {
        $publicId = (string) $this->post('id');

        $results = $this->uploadRepository
            ->findWhere('public_id', $publicId);

        if ($results === []) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'File not found',
            ]);

            return;
        }

        $file = $results[0];

        $this->uploadRepository->destroy($file->id);

        $filePath = phpb_config('storage.uploads_folder')
            . '/'
            . $file->server_file;

        if (is_file($filePath)) {
            unlink($filePath);
        }

        $directory = dirname($filePath);

        if ($this->isEmptyDirectory($directory)) {
            rmdir($directory);
        }

        $this->jsonResponse([
            'success' => true,
        ]);
    }

    /* -----------------------------------------------------------------
     | Rendering
     | -----------------------------------------------------------------
     */

    private function renderEditor(PageContract $page): void
    {
        phpb_set_in_editmode();

        $pageBuilder = $this;

        $renderer = $this->makeRenderer($page, true);

        if (! empty($_SESSION['phpagebuilder_language'])) {
            $renderer->setLanguage(
                $_SESSION['phpagebuilder_language']
            );
        }

        $blocks = [];
        $blockSettings = [];

        foreach ($this->theme->getThemeBlocks() as $block) {
            $slug = phpb_e($block->getSlug());

            $adapter = phpb_instance(
                BlockAdapter::class,
                [$renderer, $block]
            );

            if ($block->get('hidden') !== true) {
                $blocks[$slug] = $adapter->getBlockManagerArray();
            }

            $blockSettings[$slug] = $adapter
                ->getBlockSettingsArray();
        }

        $assets = array_map(
            static fn ($file): array => [
                'src'       => $file->getUrl(),
                'public_id' => $file->public_id,
            ],
            $this->uploadRepository->getAll()
        );

        require __DIR__ . '/resources/views/layout.php';
    }

    private function renderBlockPreview(
        PageContract $page,
        string $language,
        array $blockData
    ): void {
        phpb_set_in_editmode();

        $page->setData([
            'data' => $blockData,
        ], false);

        $renderer = $this->makeRenderer($page, true);

        $renderer->setLanguage($language);

        echo $renderer->parseShortcodes(
            $blockData['html'] ?? '',
            $blockData['blocks'] ?? []
        );
    }

    private function renderLanguageVariant(
        PageContract $page,
        string $language,
        array $blockData
    ): void {
        phpb_set_in_editmode();

        $_SESSION['phpagebuilder_language'] = $language;

        $page->setData([
            'data' => $blockData,
        ], false);

        $renderer = $this->makeRenderer($page, true);

        $renderer->setLanguage($language);

        $this->jsonResponse([
            'dynamicBlocks' => $renderer
                ->getPageBlocksData()[$language] ?? [],
        ]);
    }

    /* -----------------------------------------------------------------
     | Internal Helpers
     | -----------------------------------------------------------------
     */

    private function resolvePage(): ?PageContract
    {
        $pageId = $this->get('page');

        if ($pageId === null) {
            return null;
        }

        return $this->pageRepository
            ->findWithId($pageId);
    }

    private function makeRenderer(
        PageContract $page,
        bool $editMode = false
    ): PageRenderer {
        return phpb_instance(
            PageRenderer::class,
            [$this->theme, $page, $editMode]
        );
    }

    private function isValidLanguage(string $language): bool
    {
        return isset(phpb_active_languages()[$language]);
    }

    private function isEmptyDirectory(string $directory): bool
    {
        return is_dir($directory)
            && count(scandir($directory)) === 2;
    }

    /**
     * @throws JsonException
     */
    private function jsonResponse(array $payload): void
    {
        echo json_encode(
            $payload,
            JSON_THROW_ON_ERROR
        );
    }

    private function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    private function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    private function decodeJson(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        try {
            $decoded = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            return is_array($decoded)
                ? $decoded
                : [];
        } catch (JsonException) {
            return [];
        }
    }
}

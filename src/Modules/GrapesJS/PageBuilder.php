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
    private const SESSION_LANGUAGE_KEY = 'phpagebuilder_language';

    /**
     * @var array<string,string>
     */
    private array $scripts = [];

    /**
     * @var array<int,array{0:string,1:string}>
     */
    private array $pages = [];

    private ?string $customCss = null;

    public function __construct(
        private ThemeContract $theme = new class implements ThemeContract {},
        private ?PageRepository $pageRepository = null,
        private ?UploadRepository $uploadRepository = null,
    ) {
        $this->theme = $theme instanceof ThemeContract
            ? $theme
            : phpb_instance('theme', [
                phpb_config('theme'),
                phpb_config('theme.active_theme'),
            ]);

        $this->pageRepository ??= new PageRepository();
        $this->uploadRepository ??= new UploadRepository();
    }

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
            return (new ThumbGenerator($this->theme))
                ->handleThumbRequest($action);
        }

        $page ??= $this->resolvePage();

        if ($page === null) {
            return false;
        }

        return match ($action) {
            null,
            'edit'                  => $this->edit($page),
            'store'                 => $this->store($page),
            'upload'                => $this->upload(),
            'upload_delete'         => $this->deleteUpload(),
            'renderBlock'           => $this->renderBlock($page),
            'renderLanguageVariant' => $this->renderLanguage($page),
            default                 => false,
        };
    }

    public function renderPage(
        PageContract $page,
        ?string $language = null
    ): string {
        $renderer = $this->renderer($page);

        if ($language !== null) {
            $renderer->setLanguage($language);
        }

        return $renderer->render();
    }

    public function updatePage(PageContract $page, array $data): mixed
    {
        return $this->pageRepository->updatePageData($page, $data);
    }

    /**
     * @param array<int,array{0:string,1:string}> $pages
     */
    public function setPages(array $pages): void
    {
        $this->pages = $pages;
    }

    /**
     * @return array<int,array{0:string,1:string}>
     */
    public function getPages(): array
    {
        if ($this->pages !== []) {
            return $this->pages;
        }

        return $this->pages = array_map(
            static fn ($page): array => [
                phpb_e($page->getName()),
                phpb_e($page->getId()),
            ],
            $this->pageRepository->getAll()
        );
    }

    public function getPageComponents(PageContract $page): array
    {
        $components = $page->getBuilderData()['components'] ?? [[]];

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

    private function edit(PageContract $page): bool
    {
        phpb_set_in_editmode();

        $pageBuilder = $this;
        $renderer = $this->renderer($page, true);

        $language = $_SESSION[self::SESSION_LANGUAGE_KEY] ?? null;

        if ($language !== null) {
            $renderer->setLanguage($language);
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

            $blockSettings[$slug] = $adapter->getBlockSettingsArray();
        }

        $assets = array_map(
            static fn ($file): array => [
                'src'       => $file->getUrl(),
                'public_id' => $file->public_id,
            ],
            $this->uploadRepository->getAll()
        );

        require __DIR__ . '/resources/views/layout.php';

        return true;
    }

    private function store(PageContract $page): bool
    {
        $this->updatePage(
            $page,
            $this->decodeJson((string) $this->post('data'))
        );

        return true;
    }

    private function upload(): bool
    {
        $publicId = bin2hex(random_bytes(20));

        $uploader = phpb_instance(Uploader::class, ['files']);

        $filename = preg_replace(
            '/[^a-zA-Z0-9._-]/',
            '-',
            $uploader->file_src_name
        );

        $uploader
            ->file_name($publicId . '/' . $filename)
            ->upload_to(
                phpb_config('storage.uploads_folder') . '/'
            )
            ->run();

        if (! $uploader->was_uploaded) {
            throw new RuntimeException(
                'Upload failed: ' . $uploader->error
            );
        }

        $upload = $this->uploadRepository->create([
            'public_id'     => $publicId,
            'original_file' => $filename,
            'mime_type'     => $uploader->file_src_mime,
            'server_file'   => $uploader->final_file_name,
        ]);

        $this->json([
            'data' => [
                'public_id' => $publicId,
                'src'       => $upload->getUrl(),
                'type'      => 'image',
            ],
        ]);

        return true;
    }

    private function deleteUpload(): bool
    {
        $publicId = (string) $this->post('id');

        $files = $this->uploadRepository
            ->findWhere('public_id', $publicId);

        if ($files === []) {
            $this->json([
                'success' => false,
                'message' => 'File not found',
            ]);

            return true;
        }

        $file = $files[0];

        $this->uploadRepository->destroy($file->id);

        $path = phpb_config('storage.uploads_folder')
            . '/'
            . $file->server_file;

        if (is_file($path)) {
            unlink($path);
        }

        $directory = dirname($path);

        if (
            is_dir($directory)
            && count(scandir($directory)) === 2
        ) {
            rmdir($directory);
        }

        $this->json(['success' => true]);

        return true;
    }

    private function renderBlock(PageContract $page): bool
    {
        $language = $this->language();

        if ($language === null) {
            return false;
        }

        $data = $this->decodeJson(
            (string) $this->post('data')
        );

        phpb_set_in_editmode();

        $page->setData(['data' => $data], false);

        $renderer = $this->renderer($page, true);

        $renderer->setLanguage($language);

        echo $renderer->parseShortcodes(
            $data['html'] ?? '',
            $data['blocks'] ?? []
        );

        return true;
    }

    private function renderLanguage(PageContract $page): bool
    {
        $language = $this->language();

        if ($language === null) {
            return false;
        }

        $_SESSION[self::SESSION_LANGUAGE_KEY] = $language;

        $data = $this->decodeJson(
            (string) $this->post('data')
        );

        $page->setData(['data' => $data], false);

        $renderer = $this->renderer($page, true);

        $renderer->setLanguage($language);

        $this->json([
            'dynamicBlocks' =>
                $renderer->getPageBlocksData()[$language] ?? [],
        ]);

        return true;
    }

    private function resolvePage(): ?PageContract
    {
        $pageId = $this->get('page');

        return $pageId === null
            ? null
            : $this->pageRepository->findWithId($pageId);
    }

    private function renderer(
        PageContract $page,
        bool $editMode = false
    ): PageRenderer {
        return phpb_instance(
            PageRenderer::class,
            [$this->theme, $page, $editMode]
        );
    }

    private function language(): ?string
    {
        $language = (string) $this->post('language');

        return isset(phpb_active_languages()[$language])
            ? $language
            : null;
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json');

        echo json_encode(
            $payload,
            JSON_THROW_ON_ERROR
        );
    }

    private function post(
        string $key,
        mixed $default = null
    ): mixed {
        return $_POST[$key] ?? $default;
    }

    private function get(
        string $key,
        mixed $default = null
    ): mixed {
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

<?php

declare(strict_types=1);

namespace PHPageBuilder\Modules\GrapesJS\Thumb;

use Exception;
use PHPageBuilder\Contracts\ThemeContract;
use PHPageBuilder\Modules\GrapesJS\PageRenderer;
use PHPageBuilder\ThemeBlock;

class ThumbGenerator
{
    protected ThemeContract $theme;

    public function __construct(ThemeContract $theme)
    {
        $this->theme = $theme;
    }

    /**
     * Handle thumbnail-related requests.
     *
     * @throws Exception
     */
    public function handleThumbRequest(string $action): bool
    {
        phpb_set_in_editmode();

        return match ($action) {
            'renderNextBlockThumb' => $this->handleRenderRequest(),
            'upload'               => $this->handleUploadRequest(),
            default                => false,
        };
    }

    /**
     * Render the next thumbnail.
     *
     * @throws Exception
     */
    protected function handleRenderRequest(): bool
    {
        $this->renderNextBlockThumb();
        exit;
    }

    /**
     * Upload and save a thumbnail.
     *
     * @throws Exception
     */
    protected function handleUploadRequest(): bool
    {
        $blockSlug = $_POST['block'] ?? null;
        $imageData = $_POST['data'] ?? null;

        if (!$blockSlug || !$imageData) {
            return false;
        }

        foreach ($this->theme->getThemeBlocks() as $block) {
            if ($block->getSlug() !== $blockSlug) {
                continue;
            }

            $this->ensureDirectory(dirname($block->getThumbPath()));

            $bytes = file_put_contents(
                $block->getThumbPath(),
                $this->decodeImage($imageData)
            );

            if ($bytes === false) {
                throw new Exception(
                    sprintf(
                        'Unable to write thumbnail for block "%s".',
                        $blockSlug
                    )
                );
            }

            exit;
        }

        return false;
    }

    /**
     * Ensure a directory exists.
     */
    protected function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new Exception(
                sprintf('Unable to create directory "%s".', $directory)
            );
        }
    }

    /**
     * Decode a base64 image.
     *
     * @throws Exception
     */
    protected function decodeImage(string $base64): string
    {
        if (!preg_match(
            '#^data:image/(png|jpg|jpeg);base64,#i',
            $base64,
            $matches
        )) {
            throw new Exception('Invalid image data URI.');
        }

        $data = substr($base64, strpos($base64, ',') + 1);

        $decoded = base64_decode($data, true);

        if ($decoded === false) {
            throw new Exception('Failed to decode image.');
        }

        return $decoded;
    }

    /**
     * Render thumbnails for all blocks.
     *
     * @throws Exception
     */
    public function renderNextBlockThumb(): void
    {
        foreach ($this->theme->getThemeBlocks() as $block) {
            $this->renderThumbForBlock($block);
        }
    }

    /**
     * Render a thumbnail for a single block.
     *
     * @throws Exception
     */
    public function renderThumbForBlock(ThemeBlock $block): void
    {
        phpb_set_in_editmode();

        if (file_exists($block->getThumbPath())) {
            return;
        }

        $page = phpb_instance('page');

        $page->setData([
            'layout' => 'master',
            'data' => [
                'html' => [
                    sprintf(
                        '[block slug="%s"]',
                        $block->getSlug()
                    ),
                ],
            ],
        ]);

        $renderer = phpb_instance(
            PageRenderer::class,
            [$this->theme, $page]
        );

        echo $renderer->render();

        $blockSlug = $block->getSlug();

        require __DIR__ . '/generator-view.php';

        exit;
    }
}

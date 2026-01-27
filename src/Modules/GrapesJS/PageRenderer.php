<?php

declare(strict_types=1);

namespace PHPageBuilder\Modules\GrapesJS;

use Exception;
use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\ThemeContract;
use PHPageBuilder\Extensions;
use PHPageBuilder\Modules\GrapesJS\Block\BlockRenderer;
use PHPageBuilder\ThemeBlock;

class PageRenderer
{
    protected ThemeContract $theme;
    protected PageContract $page;

    /** @var array<string,mixed> */
    protected array $pageData = [];

    /** @var array<string,mixed> */
    protected array $pageBlocksData = [];

    protected ShortcodeParser $shortcodeParser;
    protected bool $forPageBuilder = false;
    protected string $language;

    /** Cache flags */
    public static ?bool $canBeCached = null;
    public static ?string $skeletonCacheUrl = null;

    /**
     * Cache lifetime in minutes (default: one week)
     */
    public static int $cacheLifetime = 7 * 24 * 60;

    public function __construct(
        ThemeContract $theme,
        PageContract $page,
        bool $forPageBuilder = false
    ) {
        $this->theme = $theme;
        $this->page = $page;
        $this->pageData = $page->getBuilderData() ?? [];
        $this->forPageBuilder = $forPageBuilder;

        $this->shortcodeParser = phpb_instance(ShortcodeParser::class, [$this]);
        $this->setLanguage(phpb_current_language());
    }

    /**
     * Set which page language variant to use while rendering.
     */
    public function setLanguage(string $language): void
    {
        $storedBlockLanguages = array_keys($this->pageData['blocks'] ?? []);
        $blockKeysAreLanguages = true;

        foreach ($storedBlockLanguages as $supportedLanguage) {
            if (strlen($supportedLanguage) > 5) {
                $blockKeysAreLanguages = false;
                break;
            }
        }

        if (
            $blockKeysAreLanguages &&
            !empty($storedBlockLanguages) &&
            !in_array($language, $storedBlockLanguages, true)
        ) {
            if (!isset(phpb_active_languages()[$language])) {
                $language = $storedBlockLanguages[0];
            } else {
                $this->pageData['blocks'][$language] =
                    $this->pageData['blocks'][$storedBlockLanguages[0]] ?? [];
            }
        }

        $this->language = $language;
        $this->pageBlocksData = $this->getStoredPageBlocksData();
        $this->shortcodeParser->setLanguage($language);
    }

    /**
     * Get absolute path to the layout view.
     */
    public function getPageLayoutPath(): ?string
    {
        $layout = basename((string) $this->page->getLayout());
        $layoutPath = $this->theme->getFolder() . "/layouts/{$layout}/view.php";

        if ($path = Extensions::getLayout($layout)) {
            $layoutPath = $path . '/view.php';
        }

        return is_file($layoutPath) ? $layoutPath : null;
    }

    public static function setCanBeCached(bool $canBeCached, ?int $cacheLifetime = null): void
    {
        if (!$canBeCached || ($cacheLifetime !== null && $cacheLifetime <= 0)) {
            static::$canBeCached = false;
            return;
        }

        static::$canBeCached = true;

        if ($cacheLifetime !== null) {
            static::$cacheLifetime = min(static::$cacheLifetime, $cacheLifetime);
        }
    }

    public static function canBeCached(): bool
    {
        return static::$canBeCached ?? true;
    }

    public static function getCacheLifetime(): int
    {
        return static::canBeCached() ? static::$cacheLifetime : 0;
    }

    /**
     * Get stored block data for the active language.
     *
     * @return array<string,mixed>
     */
    public function getStoredPageBlocksData(): array
    {
        return $this->pageData['blocks'][$this->language]
            ?? $this->pageData['blocks']
            ?? [];
    }

    /**
     * Render the full page.
     */
    public function render(): string
    {
        // reset cache flags for this render cycle
        static::$canBeCached = true;

        $renderer = $this;
        $page = $this->page;

        $body = $this->forPageBuilder
            ? '<div phpb-content-container="true"></div>'
            : $this->renderBody();

        $layoutPath = $this->getPageLayoutPath();

        if ($layoutPath) {
            ob_start();
            require $layoutPath;
            $pageHtml = ob_get_clean() ?: '';
        } else {
            $pageHtml = $body;
        }

        return $this->parseShortcodes($pageHtml);
    }

    /**
     * Render page body content.
     */
    public function renderBody(int $mainContainerIndex = 0): string
    {
        $html = '';
        $data = $this->pageData;

        if (isset($data['html']) && is_array($data['html'])) {
            $html = $this->parseShortcodes($data['html'][$mainContainerIndex] ?? '');

            if (phpb_in_editmode()) {
                foreach ($data['html'] as $contentContainerHtml) {
                    $this->parseShortcodes($contentContainerHtml);
                }
            }
        } elseif (isset($data['html']) && is_string($data['html'])) {
            // backwards compatibility
            $html = $this->parseShortcodes($data['html']);
        }

        if (!empty($data['css'])) {
            return '<style>' . $data['css'] . '</style>' . $html;
        }

        return $html;
    }

    /**
     * Render a single theme block.
     */
    public function renderBlock(
        string $slug,
        ?string $id = null,
        ?array $context = null,
        int $maxDepth = 25
    ): string {
        if ($maxDepth <= 0) {
            // prevent infinite recursion
            static::setCanBeCached(false);
            return '';
        }

        $themeBlock = ($blockPath = Extensions::getBlock($slug))
            ? new ThemeBlock($this->theme, $blockPath, true, $slug)
            : new ThemeBlock($this->theme, $slug);

        $id = $id ?? $themeBlock->getSlug();
        $context = $context[$id] ?? $this->pageBlocksData[$id] ?? [];

        $blockRenderer = new BlockRenderer($this->theme, $this->page, $this->forPageBuilder);
        $renderedBlock = $blockRenderer->render($themeBlock, $context, $id);

        $nestedContext = $context['blocks'] ?? [];
        if ($themeBlock->isHtmlBlock()) {
            $nestedContext = $this->pageBlocksData;
        }

        return $this->shortcodeParser->doShortcodes(
            $renderedBlock,
            $nestedContext,
            $maxDepth - 1
        );
    }

    public function parseShortcodes(string $htmlWithShortcodes, ?array $context = null): string
    {
        return $this->shortcodeParser->doShortcodes(
            $htmlWithShortcodes,
            $context ?? $this->pageBlocksData
        );
    }

    /**
     * Return page blocks for GrapesJS editor.
     */
    public function getPageBlocksData(): array
    {
        $initialLanguage = $this->language;
        $this->shortcodeParser->resetRenderedBlocks();

        $pageBlocks = [];

        foreach (phpb_active_languages() as $languageCode => $_) {
            $this->setLanguage($languageCode);

            if ($languageCode === $initialLanguage) {
                $this->renderBody();
                $pageBlocks[$languageCode] =
                    $this->shortcodeParser->getRenderedBlocks()[$languageCode] ?? null;
            } else {
                $pageBlocks[$languageCode] = $this->pageBlocksData ?: null;
            }
        }

        $this->setLanguage($initialLanguage);

        return $pageBlocks;
    }
}

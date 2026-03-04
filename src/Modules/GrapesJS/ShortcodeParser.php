<?php

declare(strict_types=1);

namespace PHPageBuilder\Modules\GrapesJS;

use PHPageBuilder\Repositories\PageTranslationRepository;
use Exception;

class ShortcodeParser
{
    /**
     * @var PageRenderer
     */
    protected PageRenderer $pageRenderer;

    /**
     * @var array<string, array>
     */
    protected array $renderedBlocks = [];

    /**
     * @var array<int, string>
     */
    protected array $pages = [];

    /**
     * @var string|null
     */
    protected ?string $language = null;

    /**
     * Maximum recursion depth safeguard.
     */
    protected const DEFAULT_MAX_DEPTH = 25;

    /**
     * ShortcodeParser constructor.
     */
    public function __construct(PageRenderer $pageRenderer)
    {
        $this->pageRenderer = $pageRenderer;

        $this->initializePageRoutes();
    }

    /**
     * Initialize translated page routes.
     */
    protected function initializePageRoutes(): void
    {
        $pageTranslations = (new PageTranslationRepository('page_translations'))
            ->findWhere('locale', phpb_current_language());

        foreach ($pageTranslations as $pageTranslation) {
            $routeTranslation = $pageTranslation->route;

            foreach (phpb_route_parameters() as $routeParameter => $value) {
                $routeTranslation = str_replace(
                    '{' . $routeParameter . '}',
                    $value,
                    $routeTranslation
                );
            }

            $this->pages[(int) $pageTranslation->page_id] = $routeTranslation;
        }
    }

    /**
     * Set the current language.
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * Parse and execute all supported shortcodes.
     *
     * @throws Exception
     */
    public function doShortcodes(
        string $html,
        array $context = [],
        int $maxDepth = self::DEFAULT_MAX_DEPTH
    ): string {
        if ($maxDepth <= 0) {
            throw new Exception(
                'Maximum shortcode parsing depth reached. '
                . 'Possible circular block reference detected.'
            );
        }

        $html = $this->doBlockShortcodes($html, $context, $maxDepth - 1);
        $html = $this->doPageShortcodes($html);
        $html = $this->doThemeUrlShortcodes($html);

        return $this->doBlocksContainerShortcodes($html);
    }

    /**
     * Render [block] shortcodes.
     *
     * @throws Exception
     */
    protected function doBlockShortcodes(
        string $html,
        array $context,
        int $maxDepth
    ): string {
        $matches = self::findMatches('block', $html);

        foreach ($matches as $match) {
            $attributes = $match['attributes'] ?? [];

            if (!isset($attributes['slug'])) {
                continue;
            }

            $slug = $attributes['slug'];
            $id   = $attributes['id'] ?? $slug;

            // Merge inline attributes into context
            if (isset($context[$id]['settings']['attributes'])) {
                foreach ($attributes as $attribute => $value) {
                    if (in_array($attribute, ['id', 'slug'], true)) {
                        continue;
                    }

                    $context[$id]['settings']['attributes'][$attribute] = $value;
                }
            }

            $blockHtml = $this->pageRenderer
                ->renderBlock($slug, $id, $context, $maxDepth);

            $this->storeRenderedBlock($id, $context, $blockHtml);

            // Replace only first occurrence
            $html = $this->replaceFirst(
                $match['shortcode'],
                $blockHtml,
                $html
            );
        }

        return $html;
    }

    /**
     * Store rendered block in edit mode.
     */
    protected function storeRenderedBlock(
        string $id,
        array $context,
        string $blockHtml
    ): void {
        if (!phpb_in_editmode() || strpos($id, 'ID') !== 0) {
            return;
        }

        $this->renderedBlocks[$this->language ?? 'default'][$id]
            = $context[$id] ?? [];

        $this->renderedBlocks[$this->language ?? 'default'][$id]['html']
            = $blockHtml;
    }

    /**
     * Replace [page id="X"] shortcodes.
     */
    protected function doPageShortcodes(string $html): string
    {
        if (phpb_in_editmode()) {
            return $html;
        }

        $matches = self::findMatches('page', $html);

        foreach ($matches as $match) {
            $pageId = $match['attributes']['id'] ?? null;

            if (!$pageId || !isset($this->pages[(int) $pageId])) {
                continue;
            }

            $html = str_replace(
                $match['shortcode'],
                $this->pages[(int) $pageId],
                $html
            );
        }

        return $html;
    }

    /**
     * Replace [theme-url] shortcode.
     */
    protected function doThemeUrlShortcodes(string $html): string
    {
        $matches = self::findMatches('theme-url', $html);

        if (empty($matches)) {
            return $html;
        }

        $themeUrl = rtrim(phpb_config('theme.folder_url'), '/')
            . '/'
            . phpb_e(phpb_config('theme.active_theme'));

        foreach ($matches as $match) {
            $html = str_replace($match['shortcode'], $themeUrl, $html);
        }

        return $html;
    }

    /**
     * Replace [blocks-container] shortcode.
     */
    protected function doBlocksContainerShortcodes(string $html): string
    {
        $matches = self::findMatches('blocks-container', $html);

        foreach ($matches as $match) {
            $html = str_replace(
                $match['shortcode'],
                '<div phpb-blocks-container></div>',
                $html
            );
        }

        return $html;
    }

    /**
     * Find all shortcode matches in HTML.
     *
     * @return array<int, array{shortcode:string, attributes:array}>
     */
    public static function findMatches(
        string $shortcode,
        string $html
    ): array {
        $regex = sprintf(
            '/\[%s(\s[^\]]*)?\](?:([^\[]+)?\[\/%s\])?/',
            preg_quote($shortcode, '/'),
            preg_quote($shortcode, '/')
        );

        preg_match_all($regex, $html, $matches, PREG_SET_ORDER);

        $results = [];

        foreach ($matches as $match) {
            $attributeString = trim($match[1] ?? '');
            $attributes = self::parseAttributes($attributeString);

            $results[] = [
                'shortcode'  => $match[0],
                'attributes' => $attributes,
            ];
        }

        return $results;
    }

    /**
     * Parse shortcode attributes using regex.
     */
    protected static function parseAttributes(string $input): array
    {
        if ($input === '') {
            return [];
        }

        preg_match_all(
            '/(\w+)=["\']?([^"\']+)["\']?/',
            $input,
            $matches,
            PREG_SET_ORDER
        );

        $attributes = [];

        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }

        return $attributes;
    }

    /**
     * Replace first occurrence of a string.
     */
    protected function replaceFirst(
        string $search,
        string $replace,
        string $subject
    ): string {
        $pos = strpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return substr_replace(
            $subject,
            $replace,
            $pos,
            strlen($search)
        );
    }

    /**
     * Reset rendered blocks storage.
     */
    public function resetRenderedBlocks(): void
    {
        $this->renderedBlocks = [];
    }

    /**
     * Get all rendered blocks.
     */
    public function getRenderedBlocks(): array
    {
        return $this->renderedBlocks;
    }
}

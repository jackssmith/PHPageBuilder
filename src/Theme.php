<?php

declare(strict_types=1);

namespace PHPageBuilder;

use DirectoryIterator;
use PHPageBuilder\Contracts\ThemeContract;

class Theme implements ThemeContract
{
    protected array $config;
    protected string $themeSlug;

    /** @var array<string, ThemeBlock> */
    protected array $blocks = [];

    /** @var array<string, ThemeLayout> */
    protected array $layouts = [];

    protected bool $blocksLoaded = false;
    protected bool $layoutsLoaded = false;

    public function __construct(array $config, string $themeSlug)
    {
        $this->config = $config;
        $this->themeSlug = $themeSlug;
    }

    /**
     * Check whether a block/layout is active based on whitelist.
     */
    protected function isWhitelistedActive(array $whitelist): bool
    {
        if ($whitelist === []) {
            return true;
        }

        $currentUrl = phpb_current_full_url();

        foreach ($whitelist as $domain) {
            if (strpos($currentUrl, $domain) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Register a block directory.
     */
    protected function attemptBlockRegistration(DirectoryIterator $entry): void
    {
        if (! $entry->isDir() || $entry->isDot()) {
            return;
        }

        $blockSlug = $entry->getFilename();
        $block = new ThemeBlock($this, $blockSlug);

        if ($this->isWhitelistedActive($block->get('whitelist') ?? [])) {
            $this->blocks[$blockSlug] = $block;
        }
    }

    /**
     * Register an extension block.
     */
    protected function attemptExtensionBlockRegistration(string $slug, string $path): void
    {
        if ($slug === '' || $path === '') {
            return;
        }

        $block = new ThemeBlock($this, $path, true, $slug);

        if ($this->isWhitelistedActive($block->get('whitelist') ?? [])) {
            $this->blocks[$slug] = $block;
        }
    }

    /**
     * Register a layout directory.
     */
    protected function attemptLayoutRegistration(DirectoryIterator $entry): void
    {
        if (! $entry->isDir() || $entry->isDot()) {
            return;
        }

        $layoutSlug = $entry->getFilename();
        $this->layouts[$layoutSlug] = new ThemeLayout($this, $layoutSlug);
    }

    /**
     * Register an extension layout.
     */
    protected function attemptExtensionLayoutRegistration(string $slug, string $path): void
    {
        $this->layouts[$slug] = new ThemeLayout($this, $path, true, $slug);
    }

    /**
     * Load all blocks of the current theme.
     */
    protected function loadThemeBlocks(): void
    {
        if ($this->blocksLoaded) {
            return;
        }

        $this->blocks = [];

        $folders = ['', '/archived', '/elements', '/php'];
        $blocksBasePath = $this->getFolder() . '/blocks';

        foreach ($folders as $folder) {
            $path = $blocksBasePath . $folder;

            if (! is_dir($path)) {
                continue;
            }

            foreach (new DirectoryIterator($path) as $entry) {
                if (in_array('/' . $entry->getFilename(), $folders, true)) {
                    continue;
                }

                $this->attemptBlockRegistration($entry);
            }
        }

        foreach (Extensions::getBlocks() as $slug => $path) {
            $this->attemptExtensionBlockRegistration((string) $slug, (string) $path);
        }

        $this->blocksLoaded = true;
    }

    /**
     * Load all layouts of the current theme.
     */
    protected function loadThemeLayouts(): void
    {
        if ($this->layoutsLoaded) {
            return;
        }

        $this->layouts = [];
        $layoutsPath = $this->getFolder() . '/layouts';

        if (is_dir($layoutsPath)) {
            foreach (new DirectoryIterator($layoutsPath) as $entry) {
                $this->attemptLayoutRegistration($entry);
            }
        }

        foreach (Extensions::getLayouts() as $slug => $path) {
            $this->attemptExtensionLayoutRegistration((string) $slug, (string) $path);
        }

        $this->layoutsLoaded = true;
    }

    /**
     * Return all blocks of this theme.
     *
     * @return array<string, ThemeBlock>
     */
    public function getThemeBlocks(): array
    {
        $this->loadThemeBlocks();
        return $this->blocks;
    }

    /**
     * Return all layouts of this theme.
     *
     * @return array<string, ThemeLayout>
     */
    public function getThemeLayouts(): array
    {
        $this->loadThemeLayouts();
        return $this->layouts;
    }

    /**
     * Return the absolute folder path of the theme.
     */
    public function getFolder(): string
    {
        return rtrim($this->config['folder'], '/') . '/' . basename($this->themeSlug);
    }
}

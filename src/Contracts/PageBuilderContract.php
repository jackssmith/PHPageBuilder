<?php

declare(strict_types=1);

namespace PHPageBuilder\Contracts;

interface PageBuilderContract
{
    /**
     * Handle an incoming request.
     *
     * @param mixed $route
     * @param mixed $action
     */
    public function handleRequest(
        mixed $route,
        mixed $action,
        ?PageContract $page = null
    ): mixed;

    /**
     * Open the visual page builder.
     */
    public function renderPageBuilder(PageContract $page): string;

    /**
     * Render a page.
     */
    public function renderPage(
        PageContract $page,
        ?string $language = null,
        array $options = []
    ): string;

    /**
     * Render only the content blocks.
     */
    public function renderBlocks(
        PageContract $page,
        array $options = []
    ): string;

    /**
     * Render page preview.
     */
    public function renderPreview(
        PageContract $page
    ): string;

    /**
     * Save page content.
     */
    public function updatePage(
        PageContract $page,
        array $data
    ): bool;

    /**
     * Duplicate a page.
     */
    public function duplicatePage(
        PageContract $page
    ): ?PageContract;

    /**
     * Delete a page.
     */
    public function deletePage(
        PageContract $page
    ): bool;

    /**
     * Publish a page.
     */
    public function publishPage(
        PageContract $page
    ): bool;

    /**
     * Unpublish a page.
     */
    public function unpublishPage(
        PageContract $page
    ): bool;

    /**
     * Validate page before saving.
     */
    public function validatePage(
        PageContract $page,
        array $data
    ): bool;

    /**
     * Export page.
     */
    public function exportPage(
        PageContract $page,
        string $format = 'json'
    ): string;

    /**
     * Import page.
     */
    public function importPage(
        string $content,
        string $format = 'json'
    ): ?PageContract;

    /**
     * Enable or disable preview mode.
     */
    public function previewMode(
        bool $enabled = true
    ): static;

    /**
     * Enable cache.
     */
    public function enableCache(
        bool $enabled = true
    ): static;

    /**
     * Clear render cache.
     */
    public function clearCache(): bool;

    /**
     * Set current language.
     */
    public function setLanguage(
        string $language
    ): static;

    /**
     * Get current language.
     */
    public function getLanguage(): ?string;

    /**
     * Get or set custom CSS.
     */
    public function customStyle(
        ?string $css = null
    ): string;

    /**
     * Add inline CSS.
     */
    public function addStyle(
        string $css
    ): static;

    /**
     * Register an external stylesheet.
     */
    public function addStylesheet(
        string $url
    ): static;

    /**
     * Get or set custom JavaScript.
     */
    public function customScripts(
        string $location,
        ?string $scripts = null
    ): string;

    /**
     * Register external JavaScript.
     */
    public function addScript(
        string $url,
        string $location = 'body',
        bool $defer = true
    ): static;

    /**
     * Register middleware.
     */
    public function addMiddleware(
        callable $middleware
    ): static;

    /**
     * Register event listener.
     */
    public function on(
        string $event,
        callable $listener
    ): static;

    /**
     * Trigger an event.
     */
    public function dispatch(
        string $event,
        mixed $payload = null
    ): void;

    /**
     * Set builder configuration.
     */
    public function setConfig(
        array $config
    ): static;

    /**
     * Get configuration value.
     */
    public function getConfig(
        string $key,
        mixed $default = null
    ): mixed;

    /**
     * Set active theme.
     */
    public function setTheme(
        ThemeContract $theme
    ): static;

    /**
     * Get active theme.
     */
    public function getTheme(): ?ThemeContract;

    /**
     * Register available theme.
     */
    public function registerTheme(
        ThemeContract $theme
    ): static;

    /**
     * Get registered themes.
     *
     * @return ThemeContract[]
     */
    public function getThemes(): array;

    /**
     * Register custom block.
     */
    public function registerBlock(
        BlockContract $block
    ): static;

    /**
     * Get registered blocks.
     *
     * @return BlockContract[]
     */
    public function getBlocks(): array;

    /**
     * Register plugin.
     */
    public function registerPlugin(
        PluginContract $plugin
    ): static;

    /**
     * Get all plugins.
     *
     * @return PluginContract[]
     */
    public function getPlugins(): array;

    /**
     * Builder version.
     */
    public function version(): string;

    /**
     * Health check.
     */
    public function isReady(): bool;
}

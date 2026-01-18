<?php

declare(strict_types=1);

namespace PHPageBuilder;

use PHPageBuilder\Contracts\ThemeContract;
use PHPageBuilder\Modules\GrapesJS\Block\BaseController;
use PHPageBuilder\Modules\GrapesJS\Block\BaseModel;
use PHPageBuilder\Modules\GrapesJS\PageRenderer;

class ThemeBlock
{
    /**
     * Block configuration loaded from config.php
     */
    protected array $config = [];

    /**
     * Runtime-overridable configuration
     */
    public static array $dynamicConfig = [];

    protected ThemeContract $theme;
    protected string $blockSlug;
    protected bool $isExtension;
    protected ?string $extensionSlug;

    /**
     * Cached resolved folder path (performance improvement)
     */
    protected ?string $resolvedFolder = null;

    public function __construct(
        ThemeContract $theme,
        string $blockSlug,
        bool $isExtension = false,
        ?string $extensionSlug = null
    ) {
        $this->theme = $theme;
        $this->blockSlug = $blockSlug;
        $this->isExtension = $isExtension;
        $this->extensionSlug = $extensionSlug;

        $configFile = $this->getFolder() . '/config.php';
        if (is_file($configFile)) {
            $this->config = require $configFile;
        }

        PageRenderer::setCanBeCached(
            (bool) ($this->config['cache'] ?? true),
            $this->config['cache_lifetime'] ?? null
        );
    }

    /**
     * Resolve and cache block folder path
     */
    public function getFolder(): string
    {
        if ($this->resolvedFolder !== null) {
            return $this->resolvedFolder;
        }

        if ($this->isExtension) {
            return $this->resolvedFolder = $this->blockSlug;
        }

        $base = rtrim($this->theme->getFolder(), '/');
        $slug = basename($this->blockSlug);

        $candidates = [
            "$base/blocks/archived/$slug",
            "$base/blocks/elements/$slug",
            "$base/blocks/php/$slug",
            "$base/blocks/$slug",
        ];

        foreach ($candidates as $folder) {
            if (is_dir($folder)) {
                return $this->resolvedFolder = $folder;
            }
        }

        return $this->resolvedFolder = end($candidates);
    }

    /**
     * Return PHP namespace for this block
     */
    protected function getNamespace(): string
    {
        if (!empty($this->config['namespace'])) {
            return $this->config['namespace'];
        }

        if ($ns = phpb_config('theme.namespace')) {
            return $ns;
        }

        $themesPath = phpb_config('theme.folder');
        $themesFolderName = basename($themesPath);
        $blockFolder = $this->getFolder();

        $namespacePath = $themesFolderName . str_replace($themesPath, '', $blockFolder);
        $namespacePath = str_replace(['-', '_'], ' ', $namespacePath);
        $namespacePath = ucwords($namespacePath);
        $namespacePath = str_replace(' ', '', $namespacePath);

        return str_replace('/', '\\', $namespacePath);
    }

    public function getControllerClass(): string
    {
        return $this->fileExists('controller.php')
            ? $this->getNamespace() . '\\Controller'
            : BaseController::class;
    }

    public function getControllerFile(): ?string
    {
        return $this->fileExists('controller.php')
            ? $this->getFolder() . '/controller.php'
            : null;
    }

    public function getModelClass(): string
    {
        return $this->fileExists('model.php')
            ? $this->getNamespace() . '\\Model'
            : BaseModel::class;
    }

    public function getModelFile(): ?string
    {
        return $this->fileExists('model.php')
            ? $this->getFolder() . '/model.php'
            : null;
    }

    public function getViewFile(): string
    {
        return $this->isPhpBlock()
            ? $this->getFolder() . '/view.php'
            : $this->getFolder() . '/view.html';
    }

    public function getBuilderScriptFile(): ?string
    {
        return $this->getFirstExistingFile([
            'builder-script.php',
            'builder-script.html',
            'builder-script.js',
            'script.php',
            'script.html',
            'script.js',
        ]);
    }

    public function getScriptFile(): ?string
    {
        return $this->getFirstExistingFile([
            'script.php',
            'script.html',
            'script.js',
        ]);
    }

    public function getThumbPath(): string
    {
        $hash = $this->getViewHash();
        return $this->theme->getFolder()
            . '/public/block-thumbs/'
            . md5($this->blockSlug)
            . "/$hash.jpg";
    }

    public function getThumbUrl(): string
    {
        return phpb_theme_asset(
            'block-thumbs/' . md5($this->blockSlug) . '/' . $this->getViewHash() . '.jpg'
        );
    }

    public function getSlug(): string
    {
        return $this->isExtension ? (string) $this->extensionSlug : $this->blockSlug;
    }

    public function isPhpBlock(): bool
    {
        return $this->fileExists('view.php');
    }

    public function isHtmlBlock(): bool
    {
        return ! $this->isPhpBlock();
    }

    public function getWrapperElement(): string
    {
        return $this->config['wrapper'] ?? 'div';
    }

    /**
     * Safe config getter with dot-notation support
     */
    public function get(?string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function set(string $slug, ?string $key, $value): void
    {
        if ($key === null) {
            self::$dynamicConfig[$slug] = $value;
            return;
        }

        $segments = explode('.', $key);
        $ref = &self::$dynamicConfig[$slug];

        foreach ($segments as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }

        $ref = $value;
    }

    public static function getDynamicConfig(string $slug)
    {
        return self::$dynamicConfig[$slug] ?? null;
    }

    /* -----------------------------------------------------------------
     |  Helper methods (NEW)
     | ----------------------------------------------------------------- */

    protected function fileExists(string $file): bool
    {
        return is_file($this->getFolder() . '/' . $file);
    }

    protected function getFirstExistingFile(array $files): ?string
    {
        foreach ($files as $file) {
            if ($this->fileExists($file)) {
                return $this->getFolder() . '/' . $file;
            }
        }
        return null;
    }

    protected function getViewHash(): string
    {
        static $hash = null;

        if ($hash === null) {
            $hash = md5((string) @file_get_contents($this->getViewFile()));
        }

        return $hash;
    }
}

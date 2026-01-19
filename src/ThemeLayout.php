<?php

declare(strict_types=1);

namespace PHPageBuilder;

use PHPageBuilder\Contracts\ThemeContract;

class ThemeLayout
{
    /**
     * Layout configuration.
     */
    protected array $config = [];

    /**
     * Theme this layout belongs to.
     */
    protected ThemeContract $theme;

    /**
     * Layout slug.
     */
    protected string $layoutSlug;

    /**
     * Determines if the layout was registered by an extension.
     */
    protected bool $isExtension = false;

    /**
     * Extension slug (only used if $isExtension is true).
     */
    protected ?string $extensionSlug = null;

    /**
     * ThemeLayout constructor.
     */
    public function __construct(
        ThemeContract $theme,
        string $layoutSlug,
        bool $isExtension = false,
        ?string $extensionSlug = null
    ) {
        $this->theme = $theme;
        $this->layoutSlug = $layoutSlug;
        $this->isExtension = $isExtension;
        $this->extensionSlug = $extensionSlug;

        $this->loadConfig();
    }

    /**
     * Load layout configuration if available.
     */
    protected function loadConfig(): void
    {
        $configFile = $this->getFolder() . '/config.php';

        if (is_file($configFile)) {
            $this->config = include $configFile;
        }
    }

    /**
     * Return the absolute folder path of this theme layout.
     */
    public function getFolder(): string
    {
        if ($this->isExtension) {
            return rtrim((string) $this->extensionSlug, '/');
        }

        return $this->theme->getFolder() . '/layouts/' . $this->layoutSlug;
    }

    /**
     * Return the view file of this theme layout.
     */
    public function getViewFile(): string
    {
        return $this->getFolder() . '/view.php';
    }

    /**
     * Check if the layout view exists.
     */
    public function viewExists(): bool
    {
        return is_file($this->getViewFile());
    }

    /**
     * Return the slug identifying this layout.
     */
    public function getSlug(): string
    {
        return $this->isExtension && $this->extensionSlug
            ? $this->extensionSlug
            : $this->layoutSlug;
    }

    /**
     * Return the title of this theme layout.
     */
    public function getTitle(): string
    {
        return (string) ($this->get('title') ?? ucfirst($this->getSlug()));
    }

    /**
     * Get a configuration value using dot notation.
     *
     * @example get('meta.author.name')
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!str_contains($key, '.')) {
            return $this->config[$key] ?? $default;
        }

        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Determine if a config key exists.
     */
    public function has(string $key): bool
    {
        return $this->get($key, '__missing__') !== '__missing__';
    }

    /**
     * Return full configuration array.
     */
    public function getAll(): array
    {
        return $this->config;
    }
}

<?php

namespace PHPageBuilder\Themes;

use PHPageBuilder\Contracts\ThemeContract;

class Theme implements ThemeContract
{
    protected string $folder;
    protected array $config = [];
    protected array $blocks = [];
    protected array $layouts = [];

    public function __construct(string $folder, array $config = [])
    {
        $this->folder = rtrim($folder, DIRECTORY_SEPARATOR);
        $this->config = $config;
    }

    public function getThemeBlocks(): array
    {
        return $this->blocks;
    }

    public function getThemeLayouts(): array
    {
        return $this->layouts;
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    public function getName(): string
    {
        return $this->config['name'] ?? basename($this->folder);
    }

    public function getDescription(): ?string
    {
        return $this->config['description'] ?? null;
    }

    public function getVersion(): string
    {
        return $this->config['version'] ?? '1.0.0';
    }

    public function getAuthor(): ?string
    {
        return $this->config['author'] ?? null;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function hasBlock(string $name): bool
    {
        return isset($this->blocks[$name]);
    }

    public function getBlock(string $name)
    {
        return $this->blocks[$name] ?? null;
    }

    public function hasLayout(string $name): bool
    {
        return isset($this->layouts[$name]);
    }

    public function getLayout(string $name)
    {
        return $this->layouts[$name] ?? null;
    }

    public function getAssetsPath(): string
    {
        return $this->folder . '/assets';
    }

    public function getAssetsUrl(): string
    {
        return '/themes/' . basename($this->folder) . '/assets';
    }

    public function isActive(): bool
    {
        return (bool) ($this->config['active'] ?? false);
    }

    public function getPreview(): ?string
    {
        return $this->config['preview'] ?? null;
    }

    public function getMetadata(): array
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'version' => $this->getVersion(),
            'author' => $this->getAuthor(),
            'preview' => $this->getPreview(),
        ];
    }

    public function exists(): bool
    {
        return is_dir($this->folder);
    }
}

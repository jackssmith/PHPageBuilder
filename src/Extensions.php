<?php

namespace PHPageBuilder;

use InvalidArgumentException;

class Extensions
{
    /**
     * @var array<string, string> [slug => directoryPath]
     */
    private static array $blocks = [];

    /**
     * @var array<string, string> [slug => directoryPath]
     */
    private static array $layouts = [];

    /**
     * @var array<string, Asset[]> Assets per location
     */
    private static array $assets = [
        AssetLocation::HEADER->value => [],
        AssetLocation::FOOTER->value => []
    ];

    /**
     * Register an asset.
     */
    public static function registerAsset(
        string $src,
        AssetType $type,
        AssetLocation $location = AssetLocation::HEADER,
        array $attributes = []
    ): void {
        self::$assets[$location->value][] = new Asset($src, $type, $attributes);
    }

    /**
     * Register a block.
     */
    public static function registerBlock(string $slug, string $directoryPath): void
    {
        self::validateSlug($slug);
        self::$blocks[$slug] = rtrim($directoryPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Register a layout.
     */
    public static function registerLayout(string $slug, string $directoryPath): void
    {
        self::validateSlug($slug);
        self::$layouts[$slug] = rtrim($directoryPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Register multiple blocks.
     *
     * @param array<string, string> $blocks
     */
    public static function addBlocks(array $blocks): void
    {
        foreach ($blocks as $slug => $path) {
            self::registerBlock($slug, $path);
        }
    }

    /**
     * Register multiple layouts.
     *
     * @param array<string, string> $layouts
     */
    public static function addLayouts(array $layouts): void
    {
        foreach ($layouts as $slug => $path) {
            self::registerLayout($slug, $path);
        }
    }

    /**
     * Get all blocks.
     *
     * @return array<string, string>
     */
    public static function getBlocks(): array
    {
        return self::$blocks;
    }

    /**
     * Get all layouts.
     *
     * @return array<string, string>
     */
    public static function getLayouts(): array
    {
        return self::$layouts;
    }

    public static function getBlock(string $slug): ?string
    {
        return self::$blocks[$slug] ?? null;
    }

    public static function getLayout(string $slug): ?string
    {
        return self::$layouts[$slug] ?? null;
    }

    /**
     * @return Asset[]
     */
    public static function getHeaderAssets(): array
    {
        return self::$assets[AssetLocation::HEADER->value];
    }

    /**
     * @return Asset[]
     */
    public static function getFooterAssets(): array
    {
        return self::$assets[AssetLocation::FOOTER->value];
    }

    /**
     * Ensure slugs are safe.
     */
    private static function validateSlug(string $slug): void
    {
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $slug)) {
            throw new InvalidArgumentException("Invalid slug: {$slug}");
        }
    }
}


/**
 * Asset location enum.
 */
enum AssetLocation: string
{
    case HEADER = 'header';
    case FOOTER = 'footer';
}

/**
 * Optional: Asset type enum.
 * You can expand this as needed.
 */
enum AssetType: string
{
    case CSS = 'css';
    case JS = 'js';
    // Add more if needed.
}

/**
 * DTO object that stores asset data.
 */
class Asset
{
    /**
     * @param array<string, string> $attributes
     */
    public function __construct(
        public readonly string $src,
        public readonly AssetType $type,
        public readonly array $attributes = []
    ) {}
}

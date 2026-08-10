<?php

declare(strict_types=1);

namespace PHPageBuilder;

use InvalidArgumentException;
use RuntimeException;

/**
 * Central registry for PageBuilder extensions and assets.
 */
final class Extensions
{
    /**
     * @var array<string, string>
     */
    private static array $blocks = [];

    /**
     * @var array<string, string>
     */
    private static array $layouts = [];

    /**
     * @var array<string, list<Asset>>
     */
    private static array $assets = [
        AssetLocation::HEADER->value => [],
        AssetLocation::FOOTER->value => [],
    ];

    /**
     * Register an asset.
     *
     * @param array<string, scalar> $attributes
     */
    public static function registerAsset(
        string $src,
        AssetType $type,
        AssetLocation $location = AssetLocation::HEADER,
        array $attributes = []
    ): Asset {
        $src = self::normalizeAssetSource($src);

        $asset = new Asset(
            src: $src,
            type: $type,
            attributes: self::normalizeAttributes($attributes),
        );

        self::$assets[$location->value][] = $asset;

        return $asset;
    }

    /**
     * Register a block.
     *
     * Existing registrations are replaced.
     */
    public static function registerBlock(
        string $slug,
        string $directoryPath
    ): void {
        self::validateSlug($slug);

        self::$blocks[$slug] = self::normalizePath($directoryPath);
    }

    /**
     * Register a layout.
     *
     * Existing registrations are replaced.
     */
    public static function registerLayout(
        string $slug,
        string $directoryPath
    ): void {
        self::validateSlug($slug);

        self::$layouts[$slug] = self::normalizePath($directoryPath);
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
     * Check whether a block exists.
     */
    public static function hasBlock(string $slug): bool
    {
        return isset(self::$blocks[$slug]);
    }

    /**
     * Check whether a layout exists.
     */
    public static function hasLayout(string $slug): bool
    {
        return isset(self::$layouts[$slug]);
    }

    /**
     * Get a block path.
     */
    public static function getBlock(string $slug): ?string
    {
        return self::$blocks[$slug] ?? null;
    }

    /**
     * Get a layout path.
     */
    public static function getLayout(string $slug): ?string
    {
        return self::$layouts[$slug] ?? null;
    }

    /**
     * Get all registered blocks.
     *
     * @return array<string, string>
     */
    public static function getBlocks(): array
    {
        return self::$blocks;
    }

    /**
     * Get all registered layouts.
     *
     * @return array<string, string>
     */
    public static function getLayouts(): array
    {
        return self::$layouts;
    }

    /**
     * Remove a block registration.
     *
     * @return bool True if the block existed.
     */
    public static function unregisterBlock(string $slug): bool
    {
        if (!isset(self::$blocks[$slug])) {
            return false;
        }

        unset(self::$blocks[$slug]);

        return true;
    }

    /**
     * Remove a layout registration.
     *
     * @return bool True if the layout existed.
     */
    public static function unregisterLayout(string $slug): bool
    {
        if (!isset(self::$layouts[$slug])) {
            return false;
        }

        unset(self::$layouts[$slug]);

        return true;
    }

    /**
     * Get assets for a specific location.
     *
     * @return list<Asset>
     */
    public static function getAssets(AssetLocation $location): array
    {
        return self::$assets[$location->value];
    }

    /**
     * Get header assets.
     *
     * @return list<Asset>
     */
    public static function getHeaderAssets(): array
    {
        return self::getAssets(AssetLocation::HEADER);
    }

    /**
     * Get footer assets.
     *
     * @return list<Asset>
     */
    public static function getFooterAssets(): array
    {
        return self::getAssets(AssetLocation::FOOTER);
    }

    /**
     * Get all registered assets grouped by location.
     *
     * @return array<string, list<Asset>>
     */
    public static function getAllAssets(): array
    {
        return self::$assets;
    }

    /**
     * Check whether an identical asset has already been registered.
     */
    public static function hasAsset(
        string $src,
        AssetType $type,
        AssetLocation $location = AssetLocation::HEADER
    ): bool {
        $src = self::normalizeAssetSource($src);

        foreach (self::$assets[$location->value] as $asset) {
            if ($asset->src === $src && $asset->type === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Register an asset only if it isn't already registered.
     *
     * @param array<string, scalar> $attributes
     */
    public static function registerAssetOnce(
        string $src,
        AssetType $type,
        AssetLocation $location = AssetLocation::HEADER,
        array $attributes = []
    ): ?Asset {
        if (self::hasAsset($src, $type, $location)) {
            return null;
        }

        return self::registerAsset(
            $src,
            $type,
            $location,
            $attributes
        );
    }

    /**
     * Remove matching assets.
     *
     * @return int Number of removed assets.
     */
    public static function unregisterAsset(
        string $src,
        ?AssetType $type = null,
        ?AssetLocation $location = null
    ): int {
        $src = self::normalizeAssetSource($src);
        $removed = 0;

        $locations = $location !== null
            ? [$location]
            : AssetLocation::cases();

        foreach ($locations as $assetLocation) {
            $key = $assetLocation->value;

            $remaining = [];

            foreach (self::$assets[$key] as $asset) {
                $matchesSource = $asset->src === $src;
                $matchesType = $type === null || $asset->type === $type;

                if ($matchesSource && $matchesType) {
                    $removed++;
                    continue;
                }

                $remaining[] = $asset;
            }

            self::$assets[$key] = $remaining;
        }

        return $removed;
    }

    /**
     * Remove all registered blocks.
     */
    public static function clearBlocks(): void
    {
        self::$blocks = [];
    }

    /**
     * Remove all registered layouts.
     */
    public static function clearLayouts(): void
    {
        self::$layouts = [];
    }

    /**
     * Remove all registered assets.
     */
    public static function clearAssets(?AssetLocation $location = null): void
    {
        if ($location === null) {
            foreach (AssetLocation::cases() as $assetLocation) {
                self::$assets[$assetLocation->value] = [];
            }

            return;
        }

        self::$assets[$location->value] = [];
    }

    /**
     * Reset the entire extension registry.
     *
     * Useful for tests, development environments, or application bootstrapping.
     */
    public static function reset(): void
    {
        self::clearBlocks();
        self::clearLayouts();
        self::clearAssets();
    }

    /**
     * Validate an extension slug.
     */
    private static function validateSlug(string $slug): void
    {
        if ($slug === '') {
            throw new InvalidArgumentException(
                'Extension slug cannot be empty.'
            );
        }

        if (strlen($slug) > 100) {
            throw new InvalidArgumentException(
                'Extension slug cannot exceed 100 characters.'
            );
        }

        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]*$/', $slug)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid extension slug "%s". Slugs may contain letters, numbers, hyphens, underscores and dots.',
                    $slug
                )
            );
        }
    }

    /**
     * Normalize a directory path.
     */
    private static function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new InvalidArgumentException(
                'Extension directory path cannot be empty.'
            );
        }

        return rtrim($path, "/\\");
    }

    /**
     * Normalize an asset source.
     */
    private static function normalizeAssetSource(string $src): string
    {
        $src = trim($src);

        if ($src === '') {
            throw new InvalidArgumentException(
                'Asset source cannot be empty.'
            );
        }

        return $src;
    }

    /**
     * @param array<string, scalar> $attributes
     *
     * @return array<string, scalar>
     */
    private static function normalizeAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $name => $value) {
            if (!is_string($name) || trim($name) === '') {
                throw new InvalidArgumentException(
                    'Asset attribute names must be non-empty strings.'
                );
            }

            if (!is_scalar($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Asset attribute "%s" must be scalar.',
                        $name
                    )
                );
            }

            $normalized[trim($name)] = $value;
        }

        return $normalized;
    }
}

/**
 * Location where an asset should be rendered.
 */
enum AssetLocation: string
{
    case HEADER = 'header';
    case FOOTER = 'footer';
}

/**
 * Supported asset types.
 */
enum AssetType: string
{
    case CSS = 'css';
    case JS = 'js';

    /**
     * Determine whether the asset is a stylesheet.
     */
    public function isStylesheet(): bool
    {
        return $this === self::CSS;
    }

    /**
     * Determine whether the asset is JavaScript.
     */
    public function isScript(): bool
    {
        return $this === self::JS;
    }
}

/**
 * Immutable asset DTO.
 */
final class Asset
{
    /**
     * @param array<string, scalar> $attributes
     */
    public function __construct(
        public readonly string $src,
        public readonly AssetType $type,
        public readonly array $attributes = [],
    ) {
    }

    /**
     * Convert the asset to an array.
     *
     * @return array{
     *     src: string,
     *     type: string,
     *     attributes: array<string, scalar>
     * }
     */
    public function toArray(): array
    {
        return [
            'src' => $this->src,
            'type' => $this->type->value,
            'attributes' => $this->attributes,
        ];
    }

    /**
     * Create an asset from an array.
     *
     * @param array{
     *     src: string,
     *     type: string,
     *     attributes?: array<string, scalar>
     * } $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['src'], $data['type'])) {
            throw new InvalidArgumentException(
                'Asset data must contain "src" and "type".'
            );
        }

        return new self(
            src: $data['src'],
            type: AssetType::from($data['type']),
            attributes: $data['attributes'] ?? [],
        );
    }
}

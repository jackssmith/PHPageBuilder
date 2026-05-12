<?php

declare(strict_types=1);

namespace PHPageBuilder;

use JsonException;
use RuntimeException;
use InvalidArgumentException;
use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Repositories\PageTranslationRepository;

/**
 * Class Page
 *
 * Enhanced OOP version with:
 * - Strict typing
 * - Safer JSON handling
 * - Lazy loading
 * - Translation caching
 * - Attribute helpers
 * - Route resolving
 * - Magic accessors
 * - Utility methods
 * - Cleaner architecture
 */
class Page implements PageContract
{
    /**
     * Raw page attributes.
     */
    protected array $attributes = [];

    /**
     * Cached translations.
     */
    protected ?array $translations = null;

    /**
     * Translation repository.
     */
    protected PageTranslationRepository $translationRepository;

    /**
     * Constructor.
     */
    public function __construct(
        ?array $data = null,
        ?PageTranslationRepository $translationRepository = null
    ) {
        $this->translationRepository = $translationRepository
            ?? new PageTranslationRepository();

        if ($data !== null) {
            $this->fill($data);
        }
    }

    /**
     * Fill model data.
     */
    public function fill(array $data, bool $overwrite = false): self
    {
        $this->setData($data, $overwrite);

        return $this;
    }

    /**
     * Set page data.
     */
    public function setData(?array $data, bool $fullOverwrite = false): void
    {
        if (empty($data)) {
            return;
        }

        $data = $this->normalizeData($data);

        if ($fullOverwrite) {
            $this->attributes = $data;

            return;
        }

        $this->attributes = array_merge(
            $this->attributes,
            $data
        );
    }

    /**
     * Normalize incoming data.
     */
    protected function normalizeData(array $data): array
    {
        if (
            isset($data['data']) &&
            is_string($data['data'])
        ) {
            $data['data'] = $this->decodeJson(
                $data['data']
            );
        }

        return $data;
    }

    /**
     * Decode JSON safely.
     */
    protected function decodeJson(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }

        try {
            return json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            return [];
        }
    }

    /**
     * Get all attributes.
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Alias of all().
     */
    public function getData(): array
    {
        return $this->all();
    }

    /**
     * Get builder blocks.
     */
    public function getBuilderData(): array
    {
        return $this->attributes['data'] ?? [];
    }

    /**
     * Determine if attribute exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists(
            $key,
            $this->attributes
        );
    }

    /**
     * Generic getter.
     */
    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        return $this->attributes[$key] ?? $default;
    }

    /**
     * Generic setter.
     */
    public function set(
        string $key,
        mixed $value
    ): self {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Remove attribute.
     */
    public function forget(string $key): self
    {
        unset($this->attributes[$key]);

        return $this;
    }

    /**
     * Get page ID.
     */
    public function getId(): ?int
    {
        return $this->castInt(
            $this->get('id')
        );
    }

    /**
     * Get page title.
     */
    public function getName(): ?string
    {
        return $this->get('name');
    }

    /**
     * Get page layout.
     */
    public function getLayout(): ?string
    {
        return $this->get('layout');
    }

    /**
     * Get slug.
     */
    public function getSlug(): ?string
    {
        return $this->get('slug');
    }

    /**
     * Check if page is published.
     */
    public function isPublished(): bool
    {
        return (bool) $this->get(
            'published',
            false
        );
    }

    /**
     * Cast value to int.
     */
    protected function castInt(
        mixed $value
    ): ?int {
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Set translations manually.
     */
    public function setTranslations(
        array $translations
    ): self {
        $this->translations = $translations;

        return $this;
    }

    /**
     * Load translations.
     */
    public function getTranslations(): array
    {
        if ($this->translations !== null) {
            return $this->translations;
        }

        $records = $this->translationRepository
            ->findWhere(
                phpb_config(
                    'page.translation.foreign_key'
                ),
                $this->getId()
            );

        $activeLocales = array_keys(
            phpb_active_languages()
        );

        $translations = [];

        foreach ($records as $record) {
            if (
                !in_array(
                    $record->locale,
                    $activeLocales,
                    true
                )
            ) {
                continue;
            }

            $translations[$record->locale] = (
                array
            ) $record;
        }

        $this->translations = $translations;

        return $translations;
    }

    /**
     * Determine if translation exists.
     */
    public function hasTranslation(
        string $locale
    ): bool {
        return isset(
            $this->getTranslations()[$locale]
        );
    }

    /**
     * Get translated value.
     */
    public function getTranslation(
        string $key,
        ?string $locale = null,
        mixed $default = null
    ): mixed {
        $translations = $this->getTranslations();

        if (empty($translations)) {
            return $default;
        }

        $locale ??= phpb_config(
            'general.language'
        );

        return $translations[$locale][$key]
            ?? $translations['en'][$key]
            ?? $translations[array_key_first(
                $translations
            )][$key]
            ?? $default;
    }

    /**
     * Get translated title.
     */
    public function getTranslatedTitle(
        ?string $locale = null
    ): ?string {
        return $this->getTranslation(
            'title',
            $locale
        );
    }

    /**
     * Resolve localized route.
     */
    public function getRoute(
        ?string $locale = null
    ): ?string {
        $route = $this->getTranslation(
            'route',
            $locale
        );

        if (!$route) {
            return null;
        }

        foreach (
            phpb_route_parameters()
            as $parameter => $value
        ) {
            $route = str_replace(
                '{' . $parameter . '}',
                (string) $value,
                $route
            );
        }

        return $route;
    }

    /**
     * Get all localized routes.
     */
    public function getAllRoutes(): array
    {
        $routes = [];

        foreach (
            array_keys(
                $this->getTranslations()
            ) as $locale
        ) {
            $routes[$locale] = $this->getRoute(
                $locale
            );
        }

        return $routes;
    }

    /**
     * Invalidate cached routes.
     */
    public function invalidateCache(): void
    {
        $cache = phpb_instance('memory');

        foreach (
            $this->getAllRoutes()
            as $route
        ) {
            if (!$route) {
                continue;
            }

            $cache->invalidate($route);
        }
    }

    /**
     * Convert object to array.
     */
    public function toArray(): array
    {
        return [
            'attributes'   => $this->attributes,
            'translations' => $this->translations,
        ];
    }

    /**
     * Convert object to JSON.
     */
    public function toJson(
        int $flags = JSON_PRETTY_PRINT
    ): string {
        try {
            return json_encode(
                $this->toArray(),
                JSON_THROW_ON_ERROR | $flags
            );
        } catch (JsonException) {
            return '{}';
        }
    }

    /**
     * Magic getter.
     */
    public function __get(
        string $key
    ): mixed {
        return $this->get($key);
    }

    /**
     * Magic setter.
     */
    public function __set(
        string $key,
        mixed $value
    ): void {
        $this->set($key, $value);
    }

    /**
     * Magic isset.
     */
    public function __isset(
        string $key
    ): bool {
        return $this->has($key);
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->getName()
            ?? static::class;
    }
}

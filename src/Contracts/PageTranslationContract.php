<?php

declare(strict_types=1);

namespace PHPageBuilder\Contracts;

/**
 * Interface PageTranslationContract
 *
 * Defines the contract for a translated version of a page.
 *
 * A page translation contains localized content and metadata
 * while maintaining a relationship with its parent page.
 */
interface PageTranslationContract
{
    /**
     * Get the page associated with this translation.
     *
     * @return PageContract
     */
    public function getPage(): PageContract;

    /**
     * Get the unique identifier of the translation.
     *
     * @return int|string
     */
    public function getId(): int|string;

    /**
     * Get the locale of this translation.
     *
     * Examples:
     * - en
     * - en_US
     * - ar
     * - ar_EG
     * - fr
     *
     * @return string
     */
    public function getLocale(): string;

    /**
     * Get the language code.
     *
     * Examples:
     * - en
     * - ar
     * - fr
     *
     * @return string
     */
    public function getLanguage(): string;

    /**
     * Get the country or region code.
     *
     * Examples:
     * - US
     * - EG
     * - GB
     *
     * @return string|null
     */
    public function getRegion(): ?string;

    /**
     * Get the translated page title.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Get the translated page slug.
     *
     * @return string
     */
    public function getSlug(): string;

    /**
     * Get the translated page content.
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Get the translated page excerpt.
     *
     * @return string|null
     */
    public function getExcerpt(): ?string;

    /**
     * Get the translated page description.
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Get the canonical URL of the translation.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Get the canonical URL for SEO purposes.
     *
     * @return string|null
     */
    public function getCanonicalUrl(): ?string;

    /**
     * Get the translation's meta title.
     *
     * @return string|null
     */
    public function getMetaTitle(): ?string;

    /**
     * Get the translation's meta description.
     *
     * @return string|null
     */
    public function getMetaDescription(): ?string;

    /**
     * Get the translation's meta keywords.
     *
     * @return string|null
     */
    public function getMetaKeywords(): ?string;

    /**
     * Get the Open Graph title.
     *
     * @return string|null
     */
    public function getOgTitle(): ?string;

    /**
     * Get the Open Graph description.
     *
     * @return string|null
     */
    public function getOgDescription(): ?string;

    /**
     * Get the Open Graph image URL.
     *
     * @return string|null
     */
    public function getOgImage(): ?string;

    /**
     * Get the Twitter card title.
     *
     * @return string|null
     */
    public function getTwitterTitle(): ?string;

    /**
     * Get the Twitter card description.
     *
     * @return string|null
     */
    public function getTwitterDescription(): ?string;

    /**
     * Get the Twitter card image URL.
     *
     * @return string|null
     */
    public function getTwitterImage(): ?string;

    /**
     * Get the translation's status.
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Determine whether the translation is published.
     *
     * @return bool
     */
    public function isPublished(): bool;

    /**
     * Determine whether the translation is a draft.
     *
     * @return bool
     */
    public function isDraft(): bool;

    /**
     * Determine whether the translation is pending publication.
     *
     * @return bool
     */
    public function isPending(): bool;

    /**
     * Determine whether the translation is archived.
     *
     * @return bool
     */
    public function isArchived(): bool;

    /**
     * Determine whether this is the default translation.
     *
     * @return bool
     */
    public function isDefault(): bool;

    /**
     * Determine whether the translation is active.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Determine whether the translation is available.
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Determine whether the translation is complete.
     *
     * @return bool
     */
    public function isComplete(): bool;

    /**
     * Get the creation date.
     *
     * @return \DateTimeInterface|null
     */
    public function getCreatedAt(): ?\DateTimeInterface;

    /**
     * Get the last modification date.
     *
     * @return \DateTimeInterface|null
     */
    public function getUpdatedAt(): ?\DateTimeInterface;

    /**
     * Get the publication date.
     *
     * @return \DateTimeInterface|null
     */
    public function getPublishedAt(): ?\DateTimeInterface;

    /**
     * Get the deletion date.
     *
     * @return \DateTimeInterface|null
     */
    public function getDeletedAt(): ?\DateTimeInterface;

    /**
     * Get the author identifier.
     *
     * @return int|string|null
     */
    public function getAuthorId(): int|string|null;

    /**
     * Get the user who created the translation.
     *
     * @return mixed
     */
    public function getAuthor(): mixed;

    /**
     * Get the user who last updated the translation.
     *
     * @return mixed
     */
    public function getUpdatedBy(): mixed;

    /**
     * Get the translation direction.
     *
     * Typical values:
     * - ltr
     * - rtl
     *
     * @return string
     */
    public function getDirection(): string;

    /**
     * Determine whether the translation uses right-to-left direction.
     *
     * @return bool
     */
    public function isRtl(): bool;

    /**
     * Determine whether the translation uses left-to-right direction.
     *
     * @return bool
     */
    public function isLtr(): bool;

    /**
     * Get the locale display name.
     *
     * Example:
     * - English
     * - العربية
     * - Français
     *
     * @return string
     */
    public function getLocaleName(): string;

    /**
     * Get the native locale display name.
     *
     * @return string
     */
    public function getNativeLocaleName(): string;

    /**
     * Get the locale fallback.
     *
     * @return string|null
     */
    public function getFallbackLocale(): ?string;

    /**
     * Determine whether a fallback locale is configured.
     *
     * @return bool
     */
    public function hasFallbackLocale(): bool;

    /**
     * Get the parent page identifier.
     *
     * @return int|string
     */
    public function getPageId(): int|string;

    /**
     * Get the translation key.
     *
     * This can be used to uniquely identify the translation
     * independently of its database identifier.
     *
     * @return string
     */
    public function getTranslationKey(): string;

    /**
     * Get the translation version.
     *
     * @return int
     */
    public function getVersion(): int;

    /**
     * Determine whether this translation has revisions.
     *
     * @return bool
     */
    public function hasRevisions(): bool;

    /**
     * Get the number of revisions.
     *
     * @return int
     */
    public function getRevisionCount(): int;

    /**
     * Get custom translation metadata.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getMeta(string $key, mixed $default = null): mixed;

    /**
     * Determine whether custom metadata exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasMeta(string $key): bool;

    /**
     * Get all custom translation metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetas(): array;

    /**
     * Get a translated attribute.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getAttribute(
        string $key,
        mixed $default = null
    ): mixed;

    /**
     * Determine whether a translated attribute exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasAttribute(string $key): bool;

    /**
     * Get all translated attributes.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array;

    /**
     * Get the translation's route name.
     *
     * @return string|null
     */
    public function getRouteName(): ?string;

    /**
     * Get the translation's route parameters.
     *
     * @return array<string, mixed>
     */
    public function getRouteParameters(): array;

    /**
     * Get the translation's breadcrumb title.
     *
     * @return string|null
     */
    public function getBreadcrumbTitle(): ?string;

    /**
     * Get the translation's menu title.
     *
     * @return string|null
     */
    public function getMenuTitle(): ?string;

    /**
     * Get the translation's navigation label.
     *
     * @return string|null
     */
    public function getNavigationLabel(): ?string;

    /**
     * Determine whether this translation should appear
     * in navigation menus.
     *
     * @return bool
     */
    public function isInNavigation(): bool;

    /**
     * Determine whether this translation should appear
     * in search results.
     *
     * @return bool
     */
    public function isSearchable(): bool;

    /**
     * Determine whether this translation is indexable
     * by search engines.
     *
     * @return bool
     */
    public function isIndexable(): bool;

    /**
     * Get the robots meta value.
     *
     * Example:
     * - index,follow
     * - noindex,nofollow
     *
     * @return string|null
     */
    public function getRobots(): ?string;

    /**
     * Get the translation's priority for sitemap generation.
     *
     * @return float|null
     */
    public function getSitemapPriority(): ?float;

    /**
     * Get the sitemap change frequency.
     *
     * @return string|null
     */
    public function getSitemapChangeFrequency(): ?string;

    /**
     * Determine whether this translation should be included
     * in the sitemap.
     *
     * @return bool
     */
    public function isInSitemap(): bool;

    /**
     * Get the alternate language URL.
     *
     * @param string $locale
     *
     * @return string|null
     */
    public function getAlternateUrl(string $locale): ?string;

    /**
     * Get all alternate language URLs.
     *
     * @return array<string, string>
     */
    public function getAlternateUrls(): array;

    /**
     * Determine whether a translation exists for a locale.
     *
     * @param string $locale
     *
     * @return bool
     */
    public function hasTranslation(string $locale): bool;

    /**
     * Get the translation for a specific locale.
     *
     * @param string $locale
     *
     * @return PageTranslationContract|null
     */
    public function getTranslation(string $locale): ?PageTranslationContract;

    /**
     * Get all available translations for the page.
     *
     * @return iterable<PageTranslationContract>
     */
    public function getTranslations(): iterable;

    /**
     * Get the number of available translations.
     *
     * @return int
     */
    public function getTranslationCount(): int;

    /**
     * Determine whether this translation has sibling translations.
     *
     * @return bool
     */
    public function hasTranslations(): bool;

    /**
     * Get the translation's cache key.
     *
     * @return string
     */
    public function getCacheKey(): string;

    /**
     * Get the translation's cache tags.
     *
     * @return array<int, string>
     */
    public function getCacheTags(): array;

    /**
     * Determine whether the translation is cached.
     *
     * @return bool
     */
    public function isCached(): bool;

    /**
     * Get the translation's raw data.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Convert the translation into a JSON-serializable structure.
     *
     * @return array<string, mixed>
     */
    public function toJson(): array;
}

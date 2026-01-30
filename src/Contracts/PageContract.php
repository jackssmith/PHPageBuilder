<?php

declare(strict_types=1);

namespace PHPageBuilder\Contracts;

interface PageContract
{
    /**
     * Set the data stored for this page.
     *
     * @param array|null $data
     * @param bool $fullOverwrite Whether to fully overwrite or merge with existing data
     * @return self
     */
    public function setData(?array $data, bool $fullOverwrite = true): self;

    /**
     * Set the translation data of this page.
     *
     * @param array|null $translationData
     * @return self
     */
    public function setTranslations(?array $translationData): self;

    /**
     * Return all data stored for this page (builder data + custom data).
     *
     * @return array|null
     */
    public function getData(): ?array;

    /**
     * Return only the page builder data.
     *
     * @return array|null
     */
    public function getBuilderData(): ?array;

    /**
     * Return the unique identifier of this page.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Return the layout (template file name) of this page.
     *
     * @return string
     */
    public function getLayout(): string;

    /**
     * Return the human-readable name of this page.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Return a translated setting for the given locale or the current locale.
     *
     * @param string $setting
     * @param string|null $locale
     * @return mixed
     */
    public function getTranslation(string $setting, ?string $locale = null);

    /**
     * Return all translated settings.
     *
     * @return array
     */
    public function getTranslations(): array;

    /**
     * Return the route (URL) of this page.
     *
     * @param string|null $locale
     * @return string|null
     */
    public function getRoute(?string $locale = null): ?string;

    /**
     * Get a single property value from the page data.
     *
     * @param string $property
     * @param mixed $default
     * @return mixed
     */
    public function get(string $property, $default = null);

    /**
     * Check if a given property exists on this page.
     *
     * @param string $property
     * @return bool
     */
    public function has(string $property): bool;

    /**
     * Set a single property value on this page.
     *
     * @param string $property
     * @param mixed $value
     * @return self
     */
    public function set(string $property, $value): self;

    /**
     * Determine whether the page is published.
     *
     * @return bool
     */
    public function isPublished(): bool;

    /**
     * Get the publication status timestamp.
     *
     * @return \DateTimeInterface|null
     */
    public function getPublishedAt(): ?\DateTimeInterface;

    /**
     * Invalidate all cached variants of this page.
     *
     * @return void
     */
    public function invalidateCache(): void;
}

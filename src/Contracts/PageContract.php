<?php

declare(strict_types=1);

namespace PHPageBuilder\Contracts;

use DateTimeInterface;

interface PageContract
{
    /*
    |--------------------------------------------------------------------------
    | Basic Page Information
    |--------------------------------------------------------------------------
    */

    public function getId(): string;

    public function getName(): string;

    public function setName(string $name): self;

    public function getSlug(): string;

    public function setSlug(string $slug): self;

    public function getLayout(): string;

    public function setLayout(string $layout): self;

    public function getRoute(?string $locale = null): ?string;

    public function getUrl(?string $locale = null): ?string;

    public function getCanonicalUrl(): ?string;

    /*
    |--------------------------------------------------------------------------
    | Page Data
    |--------------------------------------------------------------------------
    */

    public function setData(
        ?array $data,
        bool $fullOverwrite = true
    ): self;

    public function getData(): ?array;

    public function getBuilderData(): ?array;

    public function get(string $property, $default = null);

    public function has(string $property): bool;

    public function set(string $property, $value): self;

    public function remove(string $property): self;

    public function merge(array $data): self;

    public function resetData(): self;

    public function toArray(): array;

    public function toJson(): string;

    /*
    |--------------------------------------------------------------------------
    | Translations
    |--------------------------------------------------------------------------
    */

    public function setTranslations(?array $translationData): self;

    public function getTranslations(): array;

    public function getTranslation(
        string $setting,
        ?string $locale = null
    );

    public function setTranslation(
        string $setting,
        $value,
        ?string $locale = null
    ): self;

    public function removeTranslation(
        string $setting,
        ?string $locale = null
    ): self;

    public function hasTranslation(
        string $setting,
        ?string $locale = null
    ): bool;

    public function getAvailableLocales(): array;

    public function isTranslated(?string $locale = null): bool;

    public function getDefaultLocale(): string;

    /*
    |--------------------------------------------------------------------------
    | Publication Management
    |--------------------------------------------------------------------------
    */

    public function isPublished(): bool;

    public function publish(): self;

    public function unpublish(): self;

    public function isDraft(): bool;

    public function isScheduled(): bool;

    public function schedule(DateTimeInterface $date): self;

    public function getPublishedAt(): ?DateTimeInterface;

    public function setPublishedAt(
        ?DateTimeInterface $date
    ): self;

    public function getStatus(): string;

    public function setStatus(string $status): self;

    public function isArchived(): bool;

    public function archive(): self;

    public function restore(): self;

    /*
    |--------------------------------------------------------------------------
    | SEO Management
    |--------------------------------------------------------------------------
    */

    public function getSeoTitle(): ?string;

    public function setSeoTitle(?string $title): self;

    public function getSeoDescription(): ?string;

    public function setSeoDescription(?string $description): self;

    public function getSeoKeywords(): array;

    public function setSeoKeywords(array $keywords): self;

    public function getSeoImage(): ?string;

    public function setSeoImage(?string $image): self;

    public function getRobots(): ?string;

    public function setRobots(?string $robots): self;

    public function getOpenGraphData(): array;

    public function setOpenGraphData(array $data): self;

    public function getTwitterCardData(): array;

    public function setTwitterCardData(array $data): self;

    /*
    |--------------------------------------------------------------------------
    | Hierarchy Management
    |--------------------------------------------------------------------------
    */

    public function getParentId(): ?string;

    public function setParentId(?string $parentId): self;

    public function getParent(): ?self;

    public function getChildren(): array;

    public function hasChildren(): bool;

    public function getAncestors(): array;

    public function getDescendants(): array;

    public function getDepth(): int;

    public function getBreadcrumbs(): array;

    public function moveTo(?string $parentId): self;

    /*
    |--------------------------------------------------------------------------
    | Custom Fields and Metadata
    |--------------------------------------------------------------------------
    */

    public function getMeta(string $key, $default = null);

    public function setMeta(string $key, $value): self;

    public function hasMeta(string $key): bool;

    public function removeMeta(string $key): self;

    public function getAllMeta(): array;

    public function setAllMeta(array $meta): self;

    public function getCustomFields(): array;

    public function getCustomField(string $key, $default = null);

    public function setCustomField(string $key, $value): self;

    public function removeCustomField(string $key): self;

    /*
    |--------------------------------------------------------------------------
    | Builder Content and Blocks
    |--------------------------------------------------------------------------
    */

    public function getBlocks(): array;

    public function setBlocks(array $blocks): self;

    public function addBlock(array $block): self;

    public function updateBlock(
        string $blockId,
        array $data
    ): self;

    public function removeBlock(string $blockId): self;

    public function getBlock(string $blockId): ?array;

    public function hasBlock(string $blockId): bool;

    public function reorderBlocks(array $blockIds): self;

    public function getBlockCount(): int;

    public function clearBlocks(): self;

    /*
    |--------------------------------------------------------------------------
    | Revision Management
    |--------------------------------------------------------------------------
    */

    public function getRevisionId(): ?string;

    public function getRevisions(): array;

    public function createRevision(): self;

    public function restoreRevision(string $revisionId): self;

    public function hasRevisions(): bool;

    public function getPreviousRevision(): ?array;

    /*
    |--------------------------------------------------------------------------
    | Author and Timestamps
    |--------------------------------------------------------------------------
    */

    public function getAuthorId(): ?string;

    public function setAuthorId(?string $authorId): self;

    public function getUpdatedBy(): ?string;

    public function setUpdatedBy(?string $userId): self;

    public function getCreatedAt(): ?DateTimeInterface;

    public function getUpdatedAt(): ?DateTimeInterface;

    public function setCreatedAt(
        ?DateTimeInterface $date
    ): self;

    public function setUpdatedAt(
        ?DateTimeInterface $date
    ): self;

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    public function isPublic(): bool;

    public function setPublic(bool $public): self;

    public function isPasswordProtected(): bool;

    public function setPassword(?string $password): self;

    public function verifyPassword(string $password): bool;

    public function canView(?string $userId = null): bool;

    public function canEdit(?string $userId = null): bool;

    public function canDelete(?string $userId = null): bool;

    /*
    |--------------------------------------------------------------------------
    | Duplication and Lifecycle
    |--------------------------------------------------------------------------
    */

    public function duplicate(): self;

    public function delete(): bool;

    public function exists(): bool;

    public function save(): bool;

    public function refresh(): self;

    public function isDirty(): bool;

    public function getOriginal(): array;

    public function getChanges(): array;

    /*
    |--------------------------------------------------------------------------
    | Cache Management
    |--------------------------------------------------------------------------
    */

    public function invalidateCache(): void;

    public function clearCache(): void;

    public function hasCachedVersion(): bool;

    public function getCacheKey(?string $locale = null): string;

    public function getCacheTags(): array;

    public function rememberCache(
        int $seconds = 3600
    );

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    public function validate(): bool;

    public function getValidationErrors(): array;

    public function isValid(): bool;

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    */

    public function render(array $data = []): string;

    public function getTemplatePath(): ?string;

    public function existsTemplate(): bool;

    public function getContent(): string;

    public function setContent(string $content): self;
}

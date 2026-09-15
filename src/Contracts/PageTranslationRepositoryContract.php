<?php

namespace PHPageBuilder\Contracts;

interface PageTranslationRepositoryContract
{
    /**
     * Find a translation by its ID.
     *
     * @param  int|string  $id
     * @return mixed
     */
    public function find(int|string $id);

    /**
     * Find a translation by page ID and locale.
     *
     * @param  int|string  $pageId
     * @param  string      $locale
     * @return mixed
     */
    public function findByPageAndLocale(int|string $pageId, string $locale);

    /**
     * Get all translations for a page.
     *
     * @param  int|string  $pageId
     * @return iterable
     */
    public function getByPage(int|string $pageId): iterable;

    /**
     * Get a specific translation for a page.
     *
     * @param  int|string  $pageId
     * @param  string      $locale
     * @return mixed
     */
    public function get(int|string $pageId, string $locale);

    /**
     * Create a new page translation.
     *
     * @param  array<string, mixed>  $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update an existing page translation.
     *
     * @param  int|string            $id
     * @param  array<string, mixed>  $data
     * @return mixed
     */
    public function update(int|string $id, array $data);

    /**
     * Delete a page translation.
     *
     * @param  int|string  $id
     * @return bool
     */
    public function delete(int|string $id): bool;

    /**
     * Check whether a translation exists for a page and locale.
     *
     * @param  int|string  $pageId
     * @param  string      $locale
     * @return bool
     */
    public function exists(int|string $pageId, string $locale): bool;
}

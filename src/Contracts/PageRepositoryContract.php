<?php

declare(strict_types=1);

namespace PHPageBuilder\Contracts;

/**
 * Interface PageRepositoryContract
 *
 * Defines the required methods for page persistence.
 */
interface PageRepositoryContract
{
    /* -----------------------------------------------------------------
     |  Create
     | -----------------------------------------------------------------
     */

    /**
     * Create a new page.
     *
     * @param array $data  Page attributes
     * @return object|bool Returns created page object on success, false on failure
     */
    public function create(array $data): object|bool;


    /* -----------------------------------------------------------------
     |  Update
     | -----------------------------------------------------------------
     */

    /**
     * Update the given page with new data.
     *
     * @param mixed $page  Existing page instance or identifier
     * @param array $data  Updated page attributes
     * @return object|bool|null Updated page object, false on failure, or null if not found
     */
    public function update(mixed $page, array $data): object|bool|null;
}

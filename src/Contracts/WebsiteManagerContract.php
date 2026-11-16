<?php

namespace PHPageBuilder\Contracts;

interface WebsiteManagerContract
{
    /**
     * Process the current GET or POST request and redirect or render the requested page.
     *
     * @param mixed $route
     * @param mixed $action
     * @return void
     */
    public function handleRequest($route, $action);

    /**
     * Render the website manager overview page.
     *
     * @return void
     */
    public function renderOverview();

    /**
     * Render the website manager page settings (add/edit page form).
     *
     * @param PageContract|null $page
     * @return void
     */
    public function renderPageSettings(PageContract $page = null);

    /**
     * Render the website manager menu settings (add/edit menu form).
     *
     * @return void
     */
    public function renderMenuSettings();

    /**
     * Render the welcome page.
     *
     * @return void
     */
    public function renderWelcomePage();

    /**
     * Get all website pages.
     *
     * @return array
     */
    public function getPages();

    /**
     * Find a page by its ID.
     *
     * @param int $id
     * @return PageContract|null
     */
    public function findPage($id);

    /**
     * Create a new website page.
     *
     * @param array $data
     * @return PageContract
     */
    public function createPage(array $data);

    /**
     * Update an existing website page.
     *
     * @param int $id
     * @param array $data
     * @return PageContract
     */
    public function updatePage($id, array $data);

    /**
     * Delete a website page.
     *
     * @param int $id
     * @return bool
     */
    public function deletePage($id);

    /**
     * Publish a website page.
     *
     * @param int $id
     * @return bool
     */
    public function publishPage($id);

    /**
     * Unpublish a website page.
     *
     * @param int $id
     * @return bool
     */
    public function unpublishPage($id);

    /**
     * Duplicate an existing page.
     *
     * @param int $id
     * @return PageContract
     */
    public function duplicatePage($id);

    /**
     * Get all website menus.
     *
     * @return array
     */
    public function getMenus();

    /**
     * Find a menu by its ID.
     *
     * @param int $id
     * @return MenuContract|null
     */
    public function findMenu($id);

    /**
     * Create a new menu.
     *
     * @param array $data
     * @return MenuContract
     */
    public function createMenu(array $data);

    /**
     * Update an existing menu.
     *
     * @param int $id
     * @param array $data
     * @return MenuContract
     */
    public function updateMenu($id, array $data);

    /**
     * Delete a menu.
     *
     * @param int $id
     * @return bool
     */
    public function deleteMenu($id);

    /**
     * Update menu item ordering.
     *
     * @param int $menuId
     * @param array $items
     * @return bool
     */
    public function updateMenuItems($menuId, array $items);

    /**
     * Get website settings.
     *
     * @return array
     */
    public function getSettings();

    /**
     * Update website settings.
     *
     * @param array $settings
     * @return bool
     */
    public function updateSettings(array $settings);

    /**
     * Generate a URL for a website page.
     *
     * @param PageContract $page
     * @return string
     */
    public function generatePageUrl(PageContract $page);

    /**
     * Check whether a page slug already exists.
     *
     * @param string $slug
     * @param int|null $exceptId
     * @return bool
     */
    public function pageSlugExists($slug, $exceptId = null);

    /**
     * Validate page data before saving.
     *
     * @param array $data
     * @return array
     */
    public function validatePageData(array $data);

    /**
     * Clear website manager cache.
     *
     * @return void
     */
    public function clearCache();

    /**
     * Export website pages and settings.
     *
     * @return array
     */
    public function exportWebsite();

    /**
     * Import website pages and settings.
     *
     * @param array $data
     * @return bool
     */
    public function importWebsite(array $data);
}

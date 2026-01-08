<?php

declare(strict_types=1);

namespace PHPageBuilder;

use PHPageBuilder\Contracts\PageContract;
use PHPageBuilder\Contracts\PageTranslationContract;
use PHPageBuilder\Repositories\PageRepository;
use RuntimeException;

class PageTranslation implements PageTranslationContract
{
    protected ?PageContract $page = null;

    public function __construct(
        protected PageRepository $pageRepository
    ) {}

    /**
     * Get the page this translation belongs to.
     */
    public function getPage(): ?PageContract
    {
        if ($this->page !== null) {
            return $this->page;
        }

        $pageId = $this->getPageId();

        if ($pageId === null) {
            return null;
        }

        return $this->page = $this->pageRepository->findWithId($pageId);
    }

    /**
     * Get the page or fail if it does not exist.
     *
     * @throws RuntimeException
     */
    public function getPageOrFail(): PageContract
    {
        return $this->getPage()
            ?? throw new RuntimeException('Page not found for this translation.');
    }

    /**
     * Manually associate a page with this translation.
     */
    public function setPage(PageContract $page): self
    {
        $this->page = $page;

        $foreignKey = $this->getForeignKey();
        $this->{$foreignKey} = $page->getId();

        return $this;
    }

    /**
     * Get the foreign key value.
     */
    protected function getPageId(): ?int
    {
        $foreignKey = $this->getForeignKey();

        return $this->{$foreignKey} ?? null;
    }

    /**
     * Resolve the configured foreign key name.
     */
    protected function getForeignKey(): string
    {
        return phpb_config('page.translation.foreign_key');
    }
}

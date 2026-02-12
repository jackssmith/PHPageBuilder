<?php

declare(strict_types=1);

namespace PHPageBuilder\Repositories;

use PHPageBuilder\Contracts\PageTranslationRepositoryContract;

class PageTranslationRepository extends BaseRepository implements PageTranslationRepositoryContract
{
    /**
     * The page translations database table.
     */
    protected string $table;

    /**
     * The class that represents each page translation.
     */
    protected string $class;

    /**
     * PageTranslationRepository constructor.
     */
    public function __construct(?string $table = null)
    {
        $configTable = phpb_config('page.translation.table');

        $this->table = $table ?? $configTable ?? 'page_translations';

        parent::__construct();

        $this->class = phpb_instance('page.translation');

        // Ensure class exists (added safety check)
        if (!class_exists($this->class)) {
            throw new \RuntimeException("Invalid page translation class: {$this->class}");
        }
    }
}

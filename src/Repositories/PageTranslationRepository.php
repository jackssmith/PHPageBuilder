<?php

declare(strict_types=1);

namespace PHPageBuilder\Repositories;

use PHPageBuilder\Contracts\PageTranslationRepositoryContract;
use RuntimeException;

final class PageTranslationRepository extends BaseRepository implements PageTranslationRepositoryContract
{
    private const DEFAULT_TABLE = 'page_translations';

    /**
     * The page translations database table.
     */
    protected string $table;

    /**
     * The class that represents each page translation.
     */
    protected string $class;

    public function __construct(?string $table = null)
    {
        parent::__construct();

        $this->table = $this->resolveTable($table);
        $this->class = $this->resolveModelClass();
    }

    /**
     * Resolve the table name with fallback priority.
     */
    private function resolveTable(?string $table): string
    {
        return $table
            ?? phpb_config('page.translation.table')
            ?? self::DEFAULT_TABLE;
    }

    /**
     * Resolve and validate the model class.
     *
     * @throws RuntimeException
     */
    private function resolveModelClass(): string
    {
        $class = phpb_instance('page.translation');

        if (!is_string($class) || $class === '') {
            throw new RuntimeException('Page translation class must be a non-empty string.');
        }

        if (!class_exists($class)) {
            throw new RuntimeException(sprintf(
                'Invalid page translation class: "%s" does not exist.',
                $class
            ));
        }

        return $class;
    }
}

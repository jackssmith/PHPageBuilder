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
     * The class that represents a page translation.
     */
    protected string $class;

    public function __construct(?string $table = null)
    {
        parent::__construct();

        $this->table = $this->resolveTable($table);
        $this->class = $this->resolveModelClass();
    }

    /**
     * Resolve the database table name.
     *
     * Priority:
     * 1. Explicit constructor argument
     * 2. Configuration value
     * 3. Default table name
     */
    private function resolveTable(?string $table): string
    {
        $table = $table ?? phpb_config('page.translation.table');

        if (!is_string($table) || trim($table) === '') {
            return self::DEFAULT_TABLE;
        }

        return trim($table);
    }

    /**
     * Resolve and validate the page translation model class.
     *
     * @throws RuntimeException When the configured class is invalid.
     */
    private function resolveModelClass(): string
    {
        $class = phpb_instance('page.translation');

        if (!is_string($class) || trim($class) === '') {
            throw new RuntimeException(
                'The page translation model class must be a non-empty string.'
            );
        }

        $class = trim($class);

        if (!class_exists($class)) {
            throw new RuntimeException(sprintf(
                'The page translation model class "%s" does not exist.',
                $class
            ));
        }

        return $class;
    }
}

<?php

declare(strict_types=1);

namespace PHPageBuilder\Repositories;

use InvalidArgumentException;
use PHPageBuilder\Core\DB;

abstract class BaseRepository
{
    protected readonly DB $db;

    /**
     * Table name without prefix.
     */
    protected string $table;

    /**
     * Hydration class.
     */
    protected ?string $class = null;

    /**
     * Whitelisted columns.
     *
     * @var string[]
     */
    protected array $allowedColumns = [];

    /**
     * Full table name with prefix.
     */
    protected string $tableName;

    public function __construct(?DB $db = null)
    {
        global $phpb_db;

        $this->db = $db ?? $phpb_db;

        if ($this->table === '') {
            throw new InvalidArgumentException(
                'Repository table name must be defined.'
            );
        }

        $this->tableName = phpb_config('storage.database.prefix')
            . $this->sanitizeIdentifier($this->table);
    }

    /* -----------------------------------------------------------------
     | CRUD
     * -----------------------------------------------------------------*/

    protected function create(array $data): ?object
    {
        $data = $this->filterColumns($data);

        if ($data === []) {
            throw new InvalidArgumentException(
                'No valid data provided for insert.'
            );
        }

        $columns = array_keys($data);

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $this->tableName,
            implode(', ', array_map(
                fn(string $col) => "`{$col}`",
                $columns
            )),
            implode(', ', array_fill(0, count($columns), '?'))
        );

        $this->db->query($sql, array_values($data));

        $id = $this->db->lastInsertId();

        return $id !== null
            ? $this->findById((int) $id)
            : null;
    }

    protected function update(object $instance, array $data): bool
    {
        $data = $this->filterColumns($data);

        if ($data === []) {
            return false;
        }

        $setClause = implode(
            ', ',
            array_map(
                fn(string $column) => "`{$column}` = ?",
                array_keys($data)
            )
        );

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE `id` = ?',
            $this->tableName,
            $setClause
        );

        return $this->db->query(
            $sql,
            [
                ...array_values($data),
                $this->extractId($instance),
            ]
        );
    }

    public function delete(int|string $id): bool
    {
        return $this->db->query(
            sprintf(
                'DELETE FROM `%s` WHERE `id` = ?',
                $this->tableName
            ),
            [$id]
        );
    }

    public function deleteWhere(string $column, mixed $value): bool
    {
        $column = $this->validateColumn($column);

        return $this->db->query(
            sprintf(
                'DELETE FROM `%s` WHERE `%s` = ?',
                $this->tableName,
                $column
            ),
            [$value]
        );
    }

    public function deleteAll(): bool
    {
        return $this->db->query(
            sprintf('DELETE FROM `%s`', $this->tableName)
        );
    }

    /* -----------------------------------------------------------------
     | Queries
     * -----------------------------------------------------------------*/

    public function getAll(array|string $columns = '*'): array
    {
        return $this->hydrateMany(
            $this->db->all(
                $this->tableName,
                $this->prepareColumns($columns)
            )
        );
    }

    public function findById(int|string $id): ?object
    {
        return $this->hydrateOne(
            $this->db->findWithId(
                $this->tableName,
                $id
            )
        );
    }

    public function findWhere(string $column, mixed $value): array
    {
        $column = $this->validateColumn($column);

        return $this->hydrateMany(
            $this->db->select(
                sprintf(
                    'SELECT * FROM `%s` WHERE `%s` = ?',
                    $this->tableName,
                    $column
                ),
                [$value]
            )
        );
    }

    public function firstWhere(string $column, mixed $value): ?object
    {
        return $this->findWhere($column, $value)[0] ?? null;
    }

    public function exists(string $column, mixed $value): bool
    {
        return $this->firstWhere($column, $value) !== null;
    }

    public function count(): int
    {
        $result = $this->db->select(
            sprintf(
                'SELECT COUNT(*) AS total FROM `%s`',
                $this->tableName
            )
        );

        return (int) ($result[0]['total'] ?? 0);
    }

    /* -----------------------------------------------------------------
     | Hydration
     * -----------------------------------------------------------------*/

    protected function hydrateOne(array $records): ?object
    {
        return $this->hydrateMany($records)[0] ?? null;
    }

    protected function hydrateMany(array $records): array
    {
        if ($this->class === null) {
            return $records;
        }

        return array_map(
            fn(array $record) => $this->hydrate($record),
            $records
        );
    }

    protected function hydrate(array $record): object
    {
        $instance = new ($this->class)();

        if (method_exists($instance, 'setData')) {
            $instance->setData($record);
            return $instance;
        }

        foreach ($record as $property => $value) {
            if (property_exists($instance, $property)) {
                $instance->{$property} = $value;
            }
        }

        return $instance;
    }

    /* -----------------------------------------------------------------
     | Helpers
     * -----------------------------------------------------------------*/

    protected function extractId(object $instance): int|string
    {
        return match (true) {
            property_exists($instance, 'id') => $instance->id,
            method_exists($instance, 'getId') => $instance->getId(),
            default => throw new InvalidArgumentException(
                'Unable to determine entity ID.'
            ),
        };
    }

    protected function prepareColumns(array|string $columns): array|string
    {
        if ($columns === '*') {
            return '*';
        }

        return array_map(
            fn(string $column) => $this->validateColumn($column),
            $columns
        );
    }

    protected function filterColumns(array $data): array
    {
        return array_filter(
            $data,
            fn(string $column) => $this->isAllowedColumn($column),
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function validateColumn(string $column): string
    {
        if (!$this->isAllowedColumn($column)) {
            throw new InvalidArgumentException(
                "Column '{$column}' is not allowed."
            );
        }

        return $this->sanitizeIdentifier($column);
    }

    protected function isAllowedColumn(string $column): bool
    {
        return $this->allowedColumns === []
            || in_array($column, $this->allowedColumns, true);
    }

    protected function sanitizeIdentifier(string $identifier): string
    {
        return preg_replace(
            '/[^a-zA-Z0-9_]/',
            '',
            $identifier
        );
    }
}

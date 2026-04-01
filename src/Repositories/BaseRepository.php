<?php

declare(strict_types=1);

namespace PHPageBuilder\Repositories;

use PHPageBuilder\Core\DB;
use InvalidArgumentException;

abstract class BaseRepository
{
    protected DB $db;
    protected string $table;
    protected ?string $class = null;

    /**
     * Optionally define allowed columns to prevent SQL injection
     * @var string[]
     */
    protected array $allowedColumns = [];

    public function __construct(?DB $db = null)
    {
        global $phpb_db;

        $this->db = $db ?? $phpb_db;

        if (empty($this->table)) {
            throw new InvalidArgumentException('Table name must be defined in repository.');
        }

        $this->table = phpb_config('storage.database.prefix') . $this->sanitize($this->table);
    }

    /* ---------------------------------
     | CRUD
     |---------------------------------*/

    protected function create(array $data): ?object
    {
        $data = $this->filterColumns($data);

        if (empty($data)) {
            throw new InvalidArgumentException('No valid data provided for insert.');
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->db->query($sql, array_values($data));

        $id = $this->db->lastInsertId();

        return $id ? $this->findById((int)$id) : null;
    }

    protected function update(object $instance, array $data): bool
    {
        $data = $this->filterColumns($data);

        if (empty($data)) {
            return false;
        }

        $id = $this->extractId($instance);

        $set = implode(', ', array_map(
            fn($col) => "{$col} = ?",
            array_keys($data)
        ));

        $sql = "UPDATE {$this->table} SET {$set} WHERE id = ?";

        return $this->db->query($sql, [
            ...array_values($data),
            $id
        ]);
    }

    public function delete(int|string $id): bool
    {
        return $this->db->query(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    public function deleteWhere(string $column, mixed $value): bool
    {
        $column = $this->validateColumn($column);

        return $this->db->query(
            "DELETE FROM {$this->table} WHERE {$column} = ?",
            [$value]
        );
    }

    public function deleteAll(): bool
    {
        return $this->db->query("DELETE FROM {$this->table}");
    }

    /* ---------------------------------
     | Queries
     |---------------------------------*/

    public function getAll(array|string $columns = '*'): array
    {
        $columns = $this->prepareColumns($columns);

        return $this->hydrateMany(
            $this->db->all($this->table, $columns)
        );
    }

    public function findById(int|string $id): ?object
    {
        return $this->hydrateOne(
            $this->db->findWithId($this->table, $id)
        );
    }

    public function findWhere(string $column, mixed $value): array
    {
        $column = $this->validateColumn($column);

        $records = $this->db->select(
            "SELECT * FROM {$this->table} WHERE {$column} = ?",
            [$value]
        );

        return $this->hydrateMany($records);
    }

    /* ---------------------------------
     | Hydration
     |---------------------------------*/

    protected function hydrateOne(array $records): ?object
    {
        $items = $this->hydrateMany($records);
        return $items[0] ?? null;
    }

    protected function hydrateMany(array $records): array
    {
        if (!$this->class) {
            return $records;
        }

        return array_map(fn($record) => $this->hydrate($record), $records);
    }

    protected function hydrate(array $record): object
    {
        $instance = new $this->class();

        if (method_exists($instance, 'setData')) {
            $instance->setData($record);
            return $instance;
        }

        foreach ($record as $key => $value) {
            $instance->{$key} = $value;
        }

        return $instance;
    }

    /* ---------------------------------
     | Helpers
     |---------------------------------*/

    protected function extractId(object $instance): int|string
    {
        if (isset($instance->id)) {
            return $instance->id;
        }

        if (method_exists($instance, 'getId')) {
            return $instance->getId();
        }

        throw new InvalidArgumentException('Instance has no identifiable ID.');
    }

    protected function prepareColumns(array|string $columns): array|string
    {
        if ($columns === '*') {
            return '*';
        }

        return array_map(fn($col) => $this->validateColumn($col), $columns);
    }

    protected function filterColumns(array $data): array
    {
        $filtered = [];

        foreach ($data as $column => $value) {
            if ($this->isAllowedColumn($column)) {
                $filtered[$this->sanitize($column)] = $value;
            }
        }

        return $filtered;
    }

    protected function validateColumn(string $column): string
    {
        if (!$this->isAllowedColumn($column)) {
            throw new InvalidArgumentException("Column '{$column}' is not allowed.");
        }

        return $this->sanitize($column);
    }

    protected function isAllowedColumn(string $column): bool
    {
        if (empty($this->allowedColumns)) {
            return true; // fallback (less secure, but flexible)
        }

        return in_array($column, $this->allowedColumns, true);
    }

    protected function sanitize(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $value);
    }
}

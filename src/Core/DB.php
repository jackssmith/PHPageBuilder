<?php

declare(strict_types=1);

namespace PHPageBuilder\Core;

use PDO;
use PDOStatement;

final class DB
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $this->pdo = new PDO(
            $this->buildDsn($config),
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }

    /* -----------------------------------------------------------------
     |  Core helpers
     | -----------------------------------------------------------------
     */

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* -----------------------------------------------------------------
     |  Fetching
     | -----------------------------------------------------------------
     */

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchValue(string $sql, array $params = []): mixed
    {
        return $this->run($sql, $params)->fetchColumn();
    }

    /* -----------------------------------------------------------------
     |  CRUD shortcuts
     | -----------------------------------------------------------------
     */

    public function find(string $table, int|string $id): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM {$this->id($table)} WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $fields  = implode(',', array_map($this->id(...), $columns));
        $params  = implode(',', array_map(fn ($c) => ':' . $c, $columns));

        $this->run(
            "INSERT INTO {$this->id($table)} ($fields) VALUES ($params)",
            $data
        );

        return $this->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $set   = $this->buildAssignments($data);
        $where = $this->buildAssignments($where, 'w_');

        $stmt = $this->run(
            "UPDATE {$this->id($table)} SET $set WHERE $where",
            array_merge($data, $this->prefixKeys($where = $this->stripWherePrefix($where), 'w_'))
        );

        return $stmt->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        $conditions = $this->buildAssignments($where);

        $stmt = $this->run(
            "DELETE FROM {$this->id($table)} WHERE $conditions",
            $where
        );

        return $stmt->rowCount();
    }

    /* -----------------------------------------------------------------
     |  Low-level execution
     | -----------------------------------------------------------------
     */

    private function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /* -----------------------------------------------------------------
     |  SQL utilities
     | -----------------------------------------------------------------
     */

    private function buildDsn(array $config): string
    {
        return sprintf(
            '%s:host=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['database'],
            $config['charset']
        );
    }

    private function buildAssignments(array $data, string $prefix = ''): string
    {
        return implode(', ', array_map(
            fn ($key) => sprintf('%s = :%s%s', $this->id($key), $prefix, $key),
            array_keys($data)
        ));
    }

    private function prefixKeys(array $data, string $prefix): array
    {
        $prefixed = [];
        foreach ($data as $key => $value) {
            $prefixed[$prefix . $key] = $value;
        }
        return $prefixed;
    }

    private function id(string $identifier): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }
}

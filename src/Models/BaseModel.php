<?php

declare(strict_types=1);

namespace MailForge\Models;

/**
 * Abstract base model.
 *
 * Provides a thin PDO-based CRUD layer and a simple query builder for all
 * child models.  All database interaction uses prepared statements, so SQL
 * injection is prevented at the framework level.
 */
abstract class BaseModel
{
    protected \PDO $pdo;

    /** Database table name — must be overridden by each child class. */
    protected string $table = '';

    /** Primary key column name. */
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->pdo = getDbConnection();
    }

    // ----------------------------------------------------------------
    // Basic CRUD
    // ----------------------------------------------------------------

    /**
     * Find a single record by its primary key.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Find all records, optionally filtered by column => value conditions.
     *
     * @param array<string, mixed> $conditions
     * @param string               $orderBy    Column to order by (default: primary key).
     * @param string               $direction  ASC or DESC.
     * @return array<int, array<string, mixed>>
     */
    public function findAll(
        array $conditions = [],
        string $orderBy = '',
        string $direction = 'ASC'
    ): array {
        [$whereClause, $values] = $this->buildWhere($conditions);
        $orderBy   = $orderBy !== '' ? $this->sanitizeColumnName($orderBy) : $this->primaryKey;
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $sql  = "SELECT * FROM `{$this->table}`";
        $sql .= $whereClause !== '' ? " WHERE {$whereClause}" : '';
        $sql .= " ORDER BY `{$orderBy}` {$direction}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll();
    }

    /**
     * Find a single record by conditions.
     *
     * @param array<string, mixed> $conditions
     * @return array<string, mixed>|null
     */
    public function findOne(array $conditions): ?array
    {
        [$whereClause, $values] = $this->buildWhere($conditions);

        $sql  = "SELECT * FROM `{$this->table}`";
        $sql .= $whereClause !== '' ? " WHERE {$whereClause}" : '';
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Insert a new record and return its new ID.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $columns = array_keys($data);
        $cols    = implode('`, `', array_map([$this, 'sanitizeColumnName'], $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO `{$this->table}` (`{$cols}`) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update a record identified by its primary key.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $setParts = [];
        foreach (array_keys($data) as $col) {
            $setParts[] = '`' . $this->sanitizeColumnName($col) . '` = ?';
        }
        $setClause = implode(', ', $setParts);
        $values    = array_values($data);
        $values[]  = $id;

        $stmt = $this->pdo->prepare(
            "UPDATE `{$this->table}` SET {$setClause} WHERE `{$this->primaryKey}` = ?"
        );
        $stmt->execute($values);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a record by its primary key.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Count records, optionally filtered by conditions.
     *
     * @param array<string, mixed> $conditions
     */
    public function count(array $conditions = []): int
    {
        [$whereClause, $values] = $this->buildWhere($conditions);

        $sql  = "SELECT COUNT(*) FROM `{$this->table}`";
        $sql .= $whereClause !== '' ? " WHERE {$whereClause}" : '';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        return (int) $stmt->fetchColumn();
    }

    // ----------------------------------------------------------------
    // Pagination
    // ----------------------------------------------------------------

    /**
     * Return a paginated result set.
     *
     * @param array<string, mixed> $conditions
     * @return array{data: array<int, array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginate(
        int $page = 1,
        int $perPage = 25,
        array $conditions = [],
        string $orderBy = '',
        string $direction = 'ASC'
    ): array {
        $page    = max(1, $page);
        $perPage = max(1, $perPage);
        $offset  = ($page - 1) * $perPage;

        $total     = $this->count($conditions);
        $lastPage  = (int) ceil($total / $perPage);

        [$whereClause, $values] = $this->buildWhere($conditions);
        $orderBy   = $orderBy !== '' ? $this->sanitizeColumnName($orderBy) : $this->primaryKey;
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $sql  = "SELECT * FROM `{$this->table}`";
        $sql .= $whereClause !== '' ? " WHERE {$whereClause}" : '';
        $sql .= " ORDER BY `{$orderBy}` {$direction}";
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return [
            'data'      => $stmt->fetchAll(),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => $lastPage,
        ];
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Build a parameterised WHERE clause from a conditions array.
     *
     * @param array<string, mixed>           $conditions
     * @return array{0: string, 1: array<mixed>}
     */
    protected function buildWhere(array $conditions): array
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $parts  = [];
        $values = [];

        foreach ($conditions as $col => $value) {
            $col = $this->sanitizeColumnName($col);
            if ($value === null) {
                $parts[] = "`{$col}` IS NULL";
            } else {
                $parts[]  = "`{$col}` = ?";
                $values[] = $value;
            }
        }

        return [implode(' AND ', $parts), $values];
    }

    /**
     * Strip characters that are not safe for use as a column or table name.
     * Only letters, digits, underscores, and dots are permitted.
     */
    protected function sanitizeColumnName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_.]/', '', $name) ?? '';
    }

    /**
     * Execute a raw prepared statement and return all rows.
     *
     * @param array<mixed>                $bindings
     * @return array<int, array<string, mixed>>
     */
    protected function rawQuery(string $sql, array $bindings = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    /**
     * Execute a raw prepared statement and return a single row.
     *
     * @param array<mixed>           $bindings
     * @return array<string, mixed>|null
     */
    protected function rawQueryOne(string $sql, array $bindings = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Execute a raw prepared statement (INSERT/UPDATE/DELETE) and return
     * the number of affected rows.
     *
     * @param array<mixed> $bindings
     */
    protected function rawExecute(string $sql, array $bindings = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }
}

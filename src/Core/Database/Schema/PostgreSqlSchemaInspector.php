<?php

declare(strict_types=1);

namespace SpectoDB\Core\Database\Schema;

use InvalidArgumentException;
use PDO;

final class PostgreSqlSchemaInspector implements SchemaInspectorInterface
{
    public function __construct(
        private string $schema = 'public'
    ) {
    }

    public function getDriver(): string
    {
        return 'pgsql';
    }

    public function getTables(PDO $pdo): array
    {
        $stmt = $pdo->prepare(
            "
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = :schema
              AND table_type = 'BASE TABLE'
            ORDER BY table_name
            "
        );

        $stmt->execute([
            'schema' => $this->schema,
        ]);

        return array_column(
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            'table_name'
        );
    }

    public function getColumns(PDO $pdo, string $table): array
    {
        $this->ensureTableExists($pdo, $table);

        $stmt = $pdo->prepare(
            "
            SELECT
                c.column_name,
                c.data_type,
                c.is_nullable,
                c.column_default,
                EXISTS (
                    SELECT 1
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu
                      ON tc.constraint_name = kcu.constraint_name
                     AND tc.table_schema = kcu.table_schema
                     AND tc.table_name = kcu.table_name
                    WHERE tc.constraint_type = 'PRIMARY KEY'
                      AND tc.table_schema = c.table_schema
                      AND tc.table_name = c.table_name
                      AND kcu.column_name = c.column_name
                ) AS is_primary_key
            FROM information_schema.columns c
            WHERE c.table_schema = :schema
              AND c.table_name = :table
            ORDER BY c.ordinal_position
            "
        );

        $stmt->execute([
            'schema' => $this->schema,
            'table' => $table,
        ]);

        $columns = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = [
                'name' => $row['column_name'],
                'type' => $row['data_type'],
                'nullable' => $row['is_nullable'] === 'YES',
                'primary_key' => filter_var(
                    $row['is_primary_key'],
                    FILTER_VALIDATE_BOOLEAN
                ),
                'default' => $row['column_default'],
                'extra' => null,
            ];
        }

        return $columns;
    }

    private function ensureTableExists(
        PDO $pdo,
        string $table
    ): void {
        if (!in_array($table, $this->getTables($pdo), true)) {
            throw new InvalidArgumentException(
                "Unknown PostgreSQL table: {$table}"
            );
        }
    }
}
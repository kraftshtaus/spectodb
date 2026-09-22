<?php

declare(strict_types=1);

namespace SpectoDB\Core\Database\Schema;

use InvalidArgumentException;
use PDO;

final class SqliteSchemaInspector implements SchemaInspectorInterface
{
    public function getDriver(): string
    {
        return 'sqlite';
    }

    public function getTables(PDO $pdo): array
    {
        $stmt = $pdo->query(
            "
            SELECT name
            FROM sqlite_master
            WHERE type = 'table'
              AND name NOT LIKE 'sqlite_%'
            ORDER BY name
            "
        );

        return array_column(
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            'name'
        );
    }

    public function getColumns(PDO $pdo, string $table): array
    {
        $this->ensureTableExists($pdo, $table);

        $quotedTable = str_replace(
            '"',
            '""',
            $table
        );

        $stmt = $pdo->query(
            'PRAGMA table_info("' . $quotedTable . '")'
        );

        $columns = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = [
                'name' => $row['name'],
                'type' => $row['type'],
                'nullable' => !(bool) $row['notnull'],
                'primary_key' => (bool) $row['pk'],
                'default' => $row['dflt_value'],
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
                "Unknown SQLite table: {$table}"
            );
        }
    }
}
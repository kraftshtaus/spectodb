<?php

declare(strict_types=1);

namespace SpectoDB\Core\Database\Schema;

use InvalidArgumentException;
use PDO;

final class MySqlSchemaInspector implements SchemaInspectorInterface
{
    public function getDriver(): string
    {
        return 'mysql';
    }

    public function getTables(PDO $pdo): array
    {
        $stmt = $pdo->query(
            "
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME
            "
        );

        return array_column(
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            'TABLE_NAME'
        );
    }

    public function getColumns(PDO $pdo, string $table): array
    {
        $this->ensureTableExists($pdo, $table);

        $stmt = $pdo->prepare(
            "
            SELECT
                COLUMN_NAME,
                DATA_TYPE,
                IS_NULLABLE,
                COLUMN_KEY,
                COLUMN_DEFAULT,
                EXTRA
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
            ORDER BY ORDINAL_POSITION
            "
        );

        $stmt->execute([
            'table' => $table,
        ]);

        $columns = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = [
                'name' => $row['COLUMN_NAME'],
                'type' => $row['DATA_TYPE'],
                'nullable' => $row['IS_NULLABLE'] === 'YES',
                'primary_key' => $row['COLUMN_KEY'] === 'PRI',
                'default' => $row['COLUMN_DEFAULT'],
                'extra' => $row['EXTRA'],
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
                "Unknown MySQL table: {$table}"
            );
        }
    }
}
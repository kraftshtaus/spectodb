<?php

declare(strict_types=1);

namespace SpectoDB\Core\Diagnostics;

use PDO;
use SpectoDB\Core\Database\DatabaseManager;
use SpectoDB\Core\Database\Schema\SchemaInspectorManager;
use Throwable;

final class DatabaseDiagnosticService
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private SchemaInspectorManager $schemaManager
    ) {
    }
    

    public function analyze(array $config): array
    {
        $issues = [];

        $dbConfig = $config['db'] ?? [];
        $mapping = $config['mapping'] ?? [];

        $driver = $dbConfig['driver'] ?? null;

        // Driver
        if ($driver === null || $driver === '') {
            $issues[] = $this->issue(
                'error',
                'missing_driver',
                'Database driver is not configured',
                'SpectoDB cannot determine which database connector to use.'
            );

            return $issues;
        }

        if (!$this->databaseManager->hasConnector($driver)) {
            $issues[] = $this->issue(
                'error',
                'unsupported_driver',
                'Unsupported database driver',
                "No connector is registered for '{$driver}'."
            );

            return $issues;
        }

        // Connection
        try {
            $pdo = $this->databaseManager->connect(
                $dbConfig
            );
        } catch (Throwable $e) {
            $issues[] = $this->issue(
                'error',
                'connection_failure',
                'Database connection failed',
                $e->getMessage(),
                [
                    'driver' => $driver,
                    'host' => $dbConfig['host'] ?? null,
                    'database' => $dbConfig['dbname']
                        ?? ($dbConfig['path'] ?? null),
                ]
            );

            return $issues;
        }

        $actualDriver = $pdo->getAttribute(
            PDO::ATTR_DRIVER_NAME
        );

        $inspector = $this->schemaManager->get(
            $actualDriver
        );

        // Tables
        try {
            $tables = $inspector->getTables(
                $pdo
            );
        } catch (Throwable $e) {
            $issues[] = $this->issue(
                'error',
                'schema_inspection_failure',
                'Schema inspection failed',
                $e->getMessage()
            );

            return $issues;
        }

        if ($tables === []) {
            $issues[] = $this->issue(
                'warning',
                'no_tables',
                'No database tables found',
                'The connected database does not contain any visible tables.'
            );

            return $issues;
        }

        // Mapping table
        $table = $mapping['table'] ?? null;

        if ($table === null || $table === '') {
            $issues[] = $this->issue(
                'error',
                'missing_table_mapping',
                'Event source table is not configured',
                'Select a source table before starting process analysis.'
            );

            return $issues;
        }

        if (!in_array($table, $tables, true)) {
            $issues[] = $this->issue(
                'error',
                'table_not_found',
                'Mapped table does not exist',
                "The configured table '{$table}' was not found in the database.",
                [
                    'table' => $table,
                ]
            );

            return $issues;
        }

        // Columns
        try {
            $columns = $inspector->getColumns(
                $pdo,
                $table
            );
        } catch (Throwable $e) {
            $issues[] = $this->issue(
                'error',
                'column_inspection_failure',
                'Column inspection failed',
                $e->getMessage(),
                [
                    'table' => $table,
                ]
            );

            return $issues;
        }

        $columnNames = array_column(
            $columns,
            'name'
        );

        $requiredMappings = [
            'case_id' => 'Case ID',
            'event' => 'Activity',
            'timestamp' => 'Timestamp',
        ];

        foreach ($requiredMappings as $key => $label) {
            $column = $mapping[$key] ?? null;

            if ($column === null || $column === '') {
                $issues[] = $this->issue(
                    'error',
                    'missing_mapping',
                    "{$label} mapping is missing",
                    "SpectoDB requires a column for {$label}.",
                    [
                        'mapping' => $key,
                    ]
                );

                continue;
            }

            if (!in_array($column, $columnNames, true)) {
                $issues[] = $this->issue(
                    'error',
                    'mapped_column_missing',
                    "{$label} column was not found",
                    "The configured column '{$column}' does not exist in '{$table}'.",
                    [
                        'mapping' => $key,
                        'column' => $column,
                        'table' => $table,
                    ]
                );
            }
        }

        // Resource is optional
        $resourceColumn =
            $mapping['attributes']['resource']
            ?? null;

        if (
            $resourceColumn !== null
            && $resourceColumn !== ''
            && !in_array(
                $resourceColumn,
                $columnNames,
                true
            )
        ) {
            $issues[] = $this->issue(
                'warning',
                'resource_column_missing',
                'Resource column was not found',
                "Resource analysis will be unavailable because '{$resourceColumn}' does not exist.",
                [
                    'column' => $resourceColumn,
                    'table' => $table,
                ]
            );
        }

        // Suspicious timestamp type
        $timestampColumn =
            $mapping['timestamp']
            ?? null;

        if (
            $timestampColumn !== null
            && in_array(
                $timestampColumn,
                $columnNames,
                true
            )
        ) {
            $column = $this->findColumn(
                $columns,
                $timestampColumn
            );

            if (
                $column !== null
                && !$this->looksLikeTimestampType(
                    (string) ($column['type'] ?? '')
                )
            ) {
                $issues[] = $this->issue(
                    'warning',
                    'suspicious_timestamp_type',
                    'Timestamp column has an unusual type',
                    'SpectoDB may not be able to reliably interpret event ordering.',
                    [
                        'column' => $timestampColumn,
                        'type' => $column['type'] ?? null,
                    ]
                );
            }
        }

        return $issues;
    }

    private function issue(
        string $severity,
        string $type,
        string $title,
        string $message,
        array $details = []
    ): array {
        return [
            'severity' => $severity,
            'type' => $type,
            'source' => 'database',
            'title' => $title,
            'message' => $message,
            'case_id' => null,
            'details' => $details,
        ];
    }

    private function findColumn(
        array $columns,
        string $name
    ): ?array {
        foreach ($columns as $column) {
            if (($column['name'] ?? null) === $name) {
                return $column;
            }
        }

        return null;
    }

    private function looksLikeTimestampType(
        string $type
    ): bool {
        $type = strtolower($type);

        foreach (
            [
                'date',
                'time',
                'timestamp',
                'datetime',
            ] as $expected
        ) {
            if (str_contains($type, $expected)) {
                return true;
            }
        }

        return false;
    }
}
<?php

declare(strict_types=1);

namespace SpectoDB\Core\Diagnostics;

use PDO;
use Throwable;

final class SqlDiagnosticService
{
    public function analyze(
        PDO $pdo,
        array $mapping
    ): array {
        $issues = [];

        $table = $mapping['table'] ?? null;
        $caseId = $mapping['case_id'] ?? null;
        $activity = $mapping['event'] ?? null;
        $timestamp = $mapping['timestamp'] ?? null;

        if (
            !$table
            || !$caseId
            || !$activity
            || !$timestamp
        ) {
            return $issues;
        }

        try {
            $driver = $pdo->getAttribute(
                PDO::ATTR_DRIVER_NAME
            );

            $query = $this->buildQuery(
                $driver,
                $table,
                $caseId,
                $activity,
                $timestamp
            );

            $issues = array_merge(
                $issues,
                $this->analyzeQueryPlan(
                    $pdo,
                    $driver,
                    $query
                )
            );

            $issues = array_merge(
                $issues,
                $this->analyzeIndexes(
                    $pdo,
                    $driver,
                    $table,
                    $caseId,
                    $timestamp
                )
            );

        } catch (Throwable $e) {
            $issues[] = $this->issue(
                'warning',
                'sql_analysis_failure',
                'SQL analysis could not be completed',
                $e->getMessage()
            );
        }

        return $issues;
    }

    private function buildQuery(
        string $driver,
        string $table,
        string $caseId,
        string $activity,
        string $timestamp
    ): string {
        $table = $this->quoteIdentifier(
            $driver,
            $table
        );

        $caseId = $this->quoteIdentifier(
            $driver,
            $caseId
        );

        $activity = $this->quoteIdentifier(
            $driver,
            $activity
        );

        $timestamp = $this->quoteIdentifier(
            $driver,
            $timestamp
        );

        return "
            SELECT
                {$caseId},
                {$activity},
                {$timestamp}
            FROM {$table}
            ORDER BY
                {$caseId},
                {$timestamp}
        ";
    }

    private function analyzeQueryPlan(
        PDO $pdo,
        string $driver,
        string $query
    ): array {
        return match ($driver) {
            'mysql' => $this->analyzeMySqlPlan(
                $pdo,
                $query
            ),

            'pgsql' => $this->analyzePostgreSqlPlan(
                $pdo,
                $query
            ),

            'sqlite' => $this->analyzeSqlitePlan(
                $pdo,
                $query
            ),

            default => [],
        };
    }

    private function analyzeMySqlPlan(
        PDO $pdo,
        string $query
    ): array {
        $statement = $pdo->query(
            'EXPLAIN ' . $query
        );

        $plan = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $issues = [];

        foreach ($plan as $row) {
            $accessType = strtolower(
                (string) ($row['type'] ?? '')
            );

            $key = $row['key'] ?? null;

            $rows = (int) (
                $row['rows'] ?? 0
            );

            if (
                $accessType === 'all'
                && $key === null
                && $rows >= 1000
            ) {
                $issues[] = $this->issue(
                    'warning',
                    'full_table_scan',
                    'Full table scan detected',
                    'The event log query may scan the entire table without using an index.',
                    [
                        'estimated_rows' => $rows,
                        'access_type' => $accessType,
                    ]
                );
            }

            $extra = strtolower(
                (string) ($row['Extra'] ?? '')
            );

            if (
                str_contains(
                    $extra,
                    'using filesort'
                )
                && $rows >= 1000
            ) {
                $issues[] = $this->issue(
                    'warning',
                    'filesort',
                    'Additional SQL sorting detected',
                    'The database may need to sort process events instead of reading them in index order.',
                    [
                        'estimated_rows' => $rows,
                        'extra' => $row['Extra'] ?? '',
                    ]
                );
            }
        }

        return $issues;
    }

    private function analyzePostgreSqlPlan(
        PDO $pdo,
        string $query
    ): array {
        $statement = $pdo->query(
            'EXPLAIN ' . $query
        );

        $rows = $statement->fetchAll(
            PDO::FETCH_COLUMN
        );

        $plan = implode(
            "\n",
            array_map(
                'strval',
                $rows
            )
        );

        if (
            stripos(
                $plan,
                'Seq Scan'
            ) !== false
        ) {
            return [
                $this->issue(
                    'info',
                    'sequential_scan',
                    'Sequential scan detected',
                    'PostgreSQL plans to scan the event source sequentially.',
                    [
                        'plan' => $plan,
                    ]
                ),
            ];
        }

        return [];
    }

    private function analyzeSqlitePlan(
        PDO $pdo,
        string $query
    ): array {
        $statement = $pdo->query(
            'EXPLAIN QUERY PLAN ' . $query
        );

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $issues = [];

        foreach ($rows as $row) {
            $detail = strtolower(
                (string) (
                    $row['detail']
                    ?? ''
                )
            );

            if (
                str_contains(
                    $detail,
                    'scan'
                )
                && !str_contains(
                    $detail,
                    'using index'
                )
            ) {
                $issues[] = $this->issue(
                    'info',
                    'table_scan',
                    'Table scan detected',
                    'SQLite may scan the event table without using an index.',
                    [
                        'plan' => $row['detail']
                            ?? '',
                    ]
                );

                break;
            }
        }

        return $issues;
    }

    private function analyzeIndexes(
        PDO $pdo,
        string $driver,
        string $table,
        string $caseId,
        string $timestamp
    ): array {
        $indexedColumns = match ($driver) {
            'mysql' => $this->mysqlIndexes(
                $pdo,
                $table
            ),

            'sqlite' => $this->sqliteIndexes(
                $pdo,
                $table
            ),

            'pgsql' => $this->postgresIndexes(
                $pdo,
                $table
            ),

            default => [],
        };

        if ($indexedColumns === []) {
            return [
                $this->issue(
                    'info',
                    'no_indexes',
                    'No useful process index detected',
                    'A database index may improve event log loading on larger datasets.',
                    [
                        'recommended' =>
                            "{$caseId}, {$timestamp}",
                    ]
                ),
            ];
        }

        foreach ($indexedColumns as $columns) {
            if (
                ($columns[0] ?? null) === $caseId
                && ($columns[1] ?? null) === $timestamp
            ) {
                return [];
            }
        }

        return [
            $this->issue(
                'info',
                'missing_process_index',
                'Process ordering index not detected',
                'A composite index may improve event log loading for large datasets.',
                [
                    'recommended' =>
                        "{$caseId}, {$timestamp}",
                ]
            ),
        ];
    }

    private function mysqlIndexes(
        PDO $pdo,
        string $table
    ): array {
        $tableName = $this->quoteIdentifier(
            'mysql',
            $table
        );

        $statement = $pdo->query(
            "SHOW INDEX FROM {$tableName}"
        );

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $indexes = [];

        foreach ($rows as $row) {
            $name = (string) (
                $row['Key_name']
                ?? ''
            );

            $position = (int) (
                $row['Seq_in_index']
                ?? 0
            );

            $column = (string) (
                $row['Column_name']
                ?? ''
            );

            if ($name === '' || $column === '') {
                continue;
            }

            $indexes[$name][$position] =
                $column;
        }

        foreach ($indexes as &$columns) {
            ksort($columns);

            $columns = array_values(
                $columns
            );
        }

        return array_values(
            $indexes
        );
    }

    private function sqliteIndexes(
        PDO $pdo,
        string $table
    ): array {
        $tableName = $this->quoteIdentifier(
            'sqlite',
            $table
        );

        $statement = $pdo->query(
            "PRAGMA index_list({$tableName})"
        );

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $indexes = [];

        foreach ($rows as $row) {
            $indexName =
                $row['name']
                ?? null;

            if (!$indexName) {
                continue;
            }

            $quotedIndex =
                str_replace(
                    '"',
                    '""',
                    $indexName
                );

            $indexStatement =
                $pdo->query(
                    'PRAGMA index_info("'
                    . $quotedIndex
                    . '")'
                );

            $columns =
                $indexStatement->fetchAll(
                    PDO::FETCH_ASSOC
                );

            $indexes[] = array_values(
                array_filter(
                    array_column(
                        $columns,
                        'name'
                    )
                )
            );
        }

        return $indexes;
    }

    private function postgresIndexes(
        PDO $pdo,
        string $table
    ): array {
        $statement = $pdo->prepare(
            '
            SELECT indexdef
            FROM pg_indexes
            WHERE schemaname = :schema
            AND tablename = :table
            '
        );

        $statement->execute([
            'schema' => 'public',
            'table' => $table,
        ]);

        $rows = $statement->fetchAll(
            PDO::FETCH_COLUMN
        );

        $indexes = [];

        foreach ($rows as $definition) {
            if (
                preg_match(
                    '/\(([^)]+)\)/',
                    (string) $definition,
                    $match
                )
            ) {
                $columns = array_map(
                    static function (
                        string $column
                    ): string {
                        return trim(
                            $column,
                            " \t\n\r\0\x0B\""
                        );
                    },
                    explode(
                        ',',
                        $match[1]
                    )
                );

                $indexes[] = $columns;
            }
        }

        return $indexes;
    }

    private function quoteIdentifier(
        string $driver,
        string $identifier
    ): string {
        if (
            !preg_match(
                '/^[A-Za-z_][A-Za-z0-9_]*$/',
                $identifier
            )
        ) {
            throw new \InvalidArgumentException(
                "Invalid SQL identifier: {$identifier}"
            );
        }

        return match ($driver) {
            'mysql' =>
                '`' . $identifier . '`',

            'pgsql',
            'sqlite' =>
                '"' . $identifier . '"',

            default =>
                throw new \RuntimeException(
                    "Unsupported SQL driver: {$driver}"
                ),
        };
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
            'source' => 'sql',
            'title' => $title,
            'message' => $message,
            'case_id' => null,
            'details' => $details,
        ];
    }
}
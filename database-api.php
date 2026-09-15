<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/vendor/autoload.php';

use SpectoDB\Core\Database\DatabaseManager;
use SpectoDB\Core\Database\Schema\SchemaInspectorManager;

header('Content-Type: application/json; charset=UTF-8');

try {
    $payload = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($payload)) {
        throw new RuntimeException(
            'Invalid request payload.'
        );
    }

    $action = $payload['action'] ?? '';

    $databaseManager = new DatabaseManager();
    $schemaManager = new SchemaInspectorManager();

    switch ($action) {

        /*
        |--------------------------------------------------------------------------
        | Test connection
        |--------------------------------------------------------------------------
        */

        case 'test_connection':

            $dbConfig = buildDatabaseConfig(
                $payload['database'] ?? []
            );

            $pdo = $databaseManager->connect(
                $dbConfig
            );

            respond([
                'success' => true,
                'message' => 'Connection successful.',
                'driver' => $pdo->getAttribute(
                    PDO::ATTR_DRIVER_NAME
                ),
            ]);

            break;


        /*
        |--------------------------------------------------------------------------
        | Load tables
        |--------------------------------------------------------------------------
        */

        case 'get_tables':

            $dbConfig = buildDatabaseConfig(
                $payload['database'] ?? []
            );

            $pdo = $databaseManager->connect(
                $dbConfig
            );

            $driver = $pdo->getAttribute(
                PDO::ATTR_DRIVER_NAME
            );

            $inspector = $schemaManager->get(
                $driver
            );

            $tables = $inspector->getTables(
                $pdo
            );

            respond([
                'success' => true,
                'tables' => $tables,
            ]);

            break;


        /*
        |--------------------------------------------------------------------------
        | Load columns
        |--------------------------------------------------------------------------
        */

        case 'get_columns':

            $dbConfig = buildDatabaseConfig(
                $payload['database'] ?? []
            );

            $table = trim(
                (string) (
                    $payload['table']
                    ?? ''
                )
            );

            if ($table === '') {
                throw new RuntimeException(
                    'Table is required.'
                );
            }

            $pdo = $databaseManager->connect(
                $dbConfig
            );

            $driver = $pdo->getAttribute(
                PDO::ATTR_DRIVER_NAME
            );

            $inspector = $schemaManager->get(
                $driver
            );

            $columns = $inspector->getColumns(
                $pdo,
                $table
            );

            respond([
                'success' => true,
                'columns' => $columns,
            ]);

            break;


        /*
        |--------------------------------------------------------------------------
        | Save current connection + mapping
        |--------------------------------------------------------------------------
        */

        case 'save':

            $dbConfig = buildDatabaseConfig(
                $payload['database'] ?? []
            );

            $mapping = $payload['mapping'] ?? [];

            validateMapping(
                $databaseManager,
                $schemaManager,
                $dbConfig,
                $mapping
            );

            $currentConfig = require __DIR__
                . '/config.php';

            $currentConfig['db'] = $dbConfig;

            $currentConfig['mapping'] = [
                'table' => $mapping['table'],

                'case_id' => $mapping['case_id'],

                'event' => $mapping['event'],

                'timestamp' => $mapping['timestamp'],

                'attributes' => [],
            ];

            /*
             * Resource is optional.
             */

            if (
                isset($mapping['resource'])
                && $mapping['resource'] !== ''
            ) {
                $currentConfig['mapping']['attributes'] = [
                    'resource' => $mapping['resource'],
                ];
            }

            $_SESSION['spectodb_config'] =
                $currentConfig;

            respond([
                'success' => true,
                'message' =>
                    'SpectoDB connection configured.',
            ]);

            break;


        /*
        |--------------------------------------------------------------------------
        | Current configuration
        |--------------------------------------------------------------------------
        */

        case 'current':

            $config =
                $_SESSION['spectodb_config']
                ?? require __DIR__ . '/config.php';

            /*
             * Never return password to browser.
             */

            if (isset($config['db']['password'])) {
                $config['db']['password'] = '';
            }

            respond([
                'success' => true,
                'config' => $config,
            ]);

            break;


        default:

            throw new RuntimeException(
                'Unknown database API action.'
            );
    }

} catch (Throwable $e) {

    http_response_code(400);

    respond([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function buildDatabaseConfig(
    array $input
): array {

    $driver = strtolower(
        trim(
            (string) (
                $input['driver']
                ?? ''
            )
        )
    );

    if ($driver === '') {
        throw new RuntimeException(
            'Database driver is required.'
        );
    }

    if ($driver === 'sqlite') {

        $path = trim(
            (string) (
                $input['path']
                ?? ''
            )
        );

        if ($path === '') {
            throw new RuntimeException(
                'SQLite database path is required.'
            );
        }

        return [
            'driver' => 'sqlite',
            'path' => $path,
        ];
    }

    $database = trim(
        (string) (
            $input['dbname']
            ?? ''
        )
    );

    if ($database === '') {
        throw new RuntimeException(
            'Database name is required.'
        );
    }

    return [
        'driver' => $driver,

        'host' => trim(
            (string) (
                $input['host']
                ?? '127.0.0.1'
            )
        ),

        'port' => trim(
            (string) (
                $input['port']
                ?? (
                    $driver === 'pgsql'
                        ? '5432'
                        : '3306'
                )
            )
        ),

        'dbname' => $database,

        'username' => (string) (
            $input['username']
            ?? ''
        ),

        'password' => (string) (
            $input['password']
            ?? ''
        ),

        'charset' => 'utf8mb4',
    ];
}


function validateMapping(
    DatabaseManager $databaseManager,
    SchemaInspectorManager $schemaManager,
    array $dbConfig,
    array $mapping
): void {

    foreach (
        [
            'table',
            'case_id',
            'event',
            'timestamp',
        ] as $required
    ) {
        if (
            !isset($mapping[$required])
            || trim(
                (string) $mapping[$required]
            ) === ''
        ) {
            throw new RuntimeException(
                "Mapping '{$required}' is required."
            );
        }
    }

    $pdo = $databaseManager->connect(
        $dbConfig
    );

    $driver = $pdo->getAttribute(
        PDO::ATTR_DRIVER_NAME
    );

    $inspector = $schemaManager->get(
        $driver
    );

    $tables = $inspector->getTables(
        $pdo
    );

    if (
        !in_array(
            $mapping['table'],
            $tables,
            true
        )
    ) {
        throw new RuntimeException(
            'Selected table does not exist.'
        );
    }

    $columns = $inspector->getColumns(
        $pdo,
        $mapping['table']
    );

    $columnNames = array_column(
        $columns,
        'name'
    );

    foreach (
        [
            'case_id',
            'event',
            'timestamp',
        ] as $field
    ) {
        if (
            !in_array(
                $mapping[$field],
                $columnNames,
                true
            )
        ) {
            throw new RuntimeException(
                "Selected {$field} column does not exist."
            );
        }
    }

    if (
        !empty($mapping['resource'])
        && !in_array(
            $mapping['resource'],
            $columnNames,
            true
        )
    ) {
        throw new RuntimeException(
            'Selected resource column does not exist.'
        );
    }
}


function respond(array $data): never
{
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}
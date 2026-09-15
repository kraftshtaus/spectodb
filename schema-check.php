<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use SpectoDB\Core\Database\DatabaseManager;
use SpectoDB\Core\Database\Schema\SchemaInspectorManager;

$config = require __DIR__ . '/config.php';

try {
    $databaseManager = new DatabaseManager();

    $pdo = $databaseManager->connect(
        $config['db']
    );

    $schemaManager = new SchemaInspectorManager();

    $driver = $config['db']['driver'];

    $inspector = $schemaManager->get(
        $driver
    );

    $tables = $inspector->getTables(
        $pdo
    );

    header(
        'Content-Type: text/html; charset=UTF-8'
    );

    echo '<pre>';

    echo "Driver: {$driver}\n\n";

    echo "Tables:\n";

    foreach ($tables as $table) {
        echo "- {$table}\n";
    }

    echo "\n";

    if ($tables !== []) {
        $table = $tables[0];

        echo "Columns for {$table}:\n\n";

        $columns = $inspector->getColumns(
            $pdo,
            $table
        );

        print_r($columns);
    }

    echo '</pre>';

} catch (Throwable $e) {
    http_response_code(500);

    echo htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );
}
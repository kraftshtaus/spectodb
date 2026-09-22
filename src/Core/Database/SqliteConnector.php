<?php

declare(strict_types=1);

namespace SpectoDB\Core\Database;

use InvalidArgumentException;
use PDO;

final class SqliteConnector implements DatabaseConnectorInterface
{
    public function getDriver(): string
    {
        return 'sqlite';
    }

    public function connect(array $config): PDO
    {
        $path = $config['path'] ?? null;

        if ($path === null || $path === '') {
            throw new InvalidArgumentException(
                'SQLite database path is required.'
            );
        }

        $dsn = 'sqlite:' . $path;

        $pdo = new PDO(
            $dsn,
            null,
            null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }
}
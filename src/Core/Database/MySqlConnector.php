<?php

declare(strict_types=1);

namespace SpectoDB\Core\Database;

use InvalidArgumentException;
use PDO;

final class MySqlConnector implements DatabaseConnectorInterface
{
    public function getDriver(): string
    {
        return 'mysql';
    }

    public function connect(array $config): PDO
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['dbname'] ?? null;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        if ($database === null || $database === '') {
            throw new InvalidArgumentException(
                'MySQL database name is required.'
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );

        return new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
}
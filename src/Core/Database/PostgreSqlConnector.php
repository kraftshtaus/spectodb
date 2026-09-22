<?php

declare(strict_types=1);

namespace SpectoDB\Core\Database;

use InvalidArgumentException;
use PDO;

final class PostgreSqlConnector implements DatabaseConnectorInterface
{
    public function getDriver(): string
    {
        return 'pgsql';
    }

    public function connect(array $config): PDO
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '5432';
        $database = $config['dbname'] ?? null;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        if ($database === null || $database === '') {
            throw new InvalidArgumentException(
                'PostgreSQL database name is required.'
            );
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $host,
            $port,
            $database
        );

        $pdo = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        $pdo->exec("SET client_encoding TO 'UTF8'");

        return $pdo;
    }
}
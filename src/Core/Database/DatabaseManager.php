<?php

declare(strict_types=1);

namespace SpectoDB\Core\Database;

use InvalidArgumentException;
use PDO;

final class DatabaseManager
{
    /**
     * @var array<string, DatabaseConnectorInterface>
     */
    private array $connectors = [];

    public function __construct()
    {
        $this->registerConnector(
            new MySqlConnector()
        );

        $this->registerConnector(
            new PostgreSqlConnector()
        );

        $this->registerConnector(
            new SqliteConnector()
        );
    }

    public function registerConnector(
        DatabaseConnectorInterface $connector
    ): void {
        $this->connectors[
            $connector->getDriver()
        ] = $connector;
    }

    public function hasConnector(string $driver): bool
    {
        $driver = $this->normalizeDriver($driver);

        return isset(
            $this->connectors[$driver]
        );
    }

    public function getConnector(
        string $driver
    ): DatabaseConnectorInterface {
        $driver = $this->normalizeDriver($driver);

        if (!$this->hasConnector($driver)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported database driver: %s',
                    $driver
                )
            );
        }

        return $this->connectors[$driver];
    }

    public function connect(array $config): PDO
    {
        $driver = $config['driver'] ?? null;

        if ($driver === null || $driver === '') {
            throw new InvalidArgumentException(
                'Database driver is not configured.'
            );
        }

        return $this
            ->getConnector($driver)
            ->connect($config);
    }

    public function getAvailableDrivers(): array
    {
        return array_keys(
            $this->connectors
        );
    }

    private function normalizeDriver(
        string $driver
    ): string {
        $driver = strtolower(
            trim($driver)
        );

        return match ($driver) {
            'postgres',
            'postgresql' => 'pgsql',

            'mariadb' => 'mysql',

            default => $driver,
        };
    }
}
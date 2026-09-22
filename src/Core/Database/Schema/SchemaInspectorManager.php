<?php

declare(strict_types=1);

namespace SpectoDB\Core\Database\Schema;

use InvalidArgumentException;

final class SchemaInspectorManager
{
    /**
     * @var array<string, SchemaInspectorInterface>
     */
    private array $inspectors = [];

    public function __construct()
    {
        $this->register(
            new MySqlSchemaInspector()
        );

        $this->register(
            new PostgreSqlSchemaInspector()
        );

        $this->register(
            new SqliteSchemaInspector()
        );
    }

    public function register(
        SchemaInspectorInterface $inspector
    ): void {
        $this->inspectors[
            $inspector->getDriver()
        ] = $inspector;
    }

    public function get(
        string $driver
    ): SchemaInspectorInterface {
        $driver = $this->normalizeDriver(
            $driver
        );

        if (!isset($this->inspectors[$driver])) {
            throw new InvalidArgumentException(
                "Unsupported schema inspector: {$driver}"
            );
        }

        return $this->inspectors[$driver];
    }

    public function getAvailableDrivers(): array
    {
        return array_keys(
            $this->inspectors
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
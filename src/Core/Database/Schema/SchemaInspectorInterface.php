<?php

declare(strict_types=1);

namespace SpectoDB\Core\Database\Schema;

use PDO;

interface SchemaInspectorInterface
{
    public function getDriver(): string;

    /**
     * @return string[]
     */
    public function getTables(PDO $pdo): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getColumns(PDO $pdo, string $table): array;
}
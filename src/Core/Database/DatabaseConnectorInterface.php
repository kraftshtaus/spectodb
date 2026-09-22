<?php

declare(strict_types=1);

namespace SpectoDB\Core\Database;

use PDO;

interface DatabaseConnectorInterface
{
    public function connect(array $config): PDO;

    public function getDriver(): string;
}
<?php

declare(strict_types=1);

$databasePath =
    __DIR__ . '/spectodb-demo.sqlite';

if (file_exists($databasePath)) {
    unlink($databasePath);
}

$pdo = new PDO(
    'sqlite:' . $databasePath,
    null,
    null,
    [
        PDO::ATTR_ERRMODE =>
            PDO::ERRMODE_EXCEPTION,

        PDO::ATTR_DEFAULT_FETCH_MODE =>
            PDO::FETCH_ASSOC,
    ]
);

// Table
$pdo->exec(
    '
    CREATE TABLE transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        event_type TEXT NOT NULL,
        created_at TEXT NOT NULL,
        resource TEXT
    )
    '
);

// Events
$rows = [
    [1, 'order_created', '2026-09-15 10:00:00', 'web'],
    [1, 'payment_started', '2026-09-15 10:02:00', 'payment'],
    [1, 'payment_success', '2026-09-15 10:04:00', 'payment'],
    [1, 'order_sent', '2026-09-15 10:08:00', 'warehouse'],

    [2, 'order_created', '2026-09-15 11:00:00', 'web'],
    [2, 'payment_started', '2026-09-15 11:01:00', 'payment'],
    [2, 'payment_failed', '2026-09-15 11:02:00', 'payment'],

    [3, 'order_created', '2026-09-15 12:00:00', 'web'],
    [3, 'payment_started', '2026-09-15 12:02:00', 'payment'],
    [3, 'payment_success', '2026-09-15 12:05:00', 'payment'],
    [3, 'order_sent', '2026-09-15 12:15:00', 'warehouse'],

    [4, 'order_created', '2026-09-15 13:00:00', 'web'],
    [4, 'payment_started', '2026-09-15 13:01:00', 'payment'],
    [4, 'manual_review', '2026-09-15 13:03:00', 'operator'],
    [4, 'payment_success', '2026-09-15 13:06:00', 'payment'],
    [4, 'order_sent', '2026-09-15 13:09:00', 'warehouse'],

    [5, 'order_created', '2026-09-15 14:00:00', 'web'],
    [5, 'payment_started', '2026-09-15 14:02:00', 'payment'],
    [5, 'payment_success', '2026-09-15 14:05:00', 'payment'],
    [5, 'payment_started', '2026-09-15 14:07:00', 'payment'],
    [5, 'payment_success', '2026-09-15 14:09:00', 'payment'],
    [5, 'order_sent', '2026-09-15 14:20:00', 'warehouse'],
];

$statement = $pdo->prepare(
    '
    INSERT INTO transactions (
        order_id,
        event_type,
        created_at,
        resource
    )
    VALUES (
        :order_id,
        :event_type,
        :created_at,
        :resource
    )
    '
);

foreach ($rows as $row) {
    $statement->execute([
        'order_id' => $row[0],
        'event_type' => $row[1],
        'created_at' => $row[2],
        'resource' => $row[3],
    ]);
}

echo 'SQLite demo created.' . PHP_EOL;
echo $databasePath . PHP_EOL;
echo 'Events: ' . count($rows) . PHP_EOL;
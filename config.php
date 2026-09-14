<?php

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'dbname' => 'thesis_project',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],

    'mapping' => [
    'table' => 'transactions',
    'case_id' => 'order_id',
    'event' => 'event_type',
    'timestamp' => 'created_at',
    ],

    'attributes' => [],

    'analysis' => [
    'success_events' => [
        'payment_success',
    ],

    'failure_events' => [
        'payment_failed',
    ],

    'sla' => [
        'max_case_duration_seconds' => 600,
    ],
],
];
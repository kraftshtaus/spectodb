<?php

class TransactionRepository
{
    public function __construct(
        private PDO $pdo,
        private array $mapping
    ) {
    }

    public function getAllOrdered(): array
    {
        $case = $this->mapping['case_id'];
        $event = $this->mapping['event'];
        $time = $this->mapping['timestamp'];

        $stmt = $this->pdo->query("
            SELECT 
                {$case} as case_id,
                {$event} as event_type,
                {$time} as created_at
            FROM transactions
            ORDER BY {$case}, {$time}
        ");

        return $stmt->fetchAll();
    }

    public function getGroupedByOrder(): array
    {
        $transactions = $this->getAllOrdered();
        $grouped = [];

        foreach ($transactions as $row) {
            $grouped[$row['case_id']][] = $row;
        }

        return $grouped;
    }
}
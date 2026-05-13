<?php

class ProcessAnalyzer
{
    public function buildTransitions(array $groupedTransactions): array
    {
        $transitions = [];

        foreach ($groupedTransactions as $orderId => $events) {
            $eventNames = array_column($events, 'event_type');

            for ($i = 0; $i < count($eventNames) - 1; $i++) {
                $from = $eventNames[$i];
                $to = $eventNames[$i + 1];

                $key = $from . '->' . $to;

                if (!isset($transitions[$key])) {
                    $transitions[$key] = [
                        'from' => $from,
                        'to' => $to,
                        'count' => 0,
                    ];
                }

                $transitions[$key]['count']++;
            }
        }

        return array_values($transitions);
    }
}

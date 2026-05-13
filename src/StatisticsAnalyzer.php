<?php

class StatisticsAnalyzer
{
    public function calculate(array $groupedTransactions, array $transitions): array
    {
        $totalCases = count($groupedTransactions);

        $successCases = 0;
        $failedCases = 0;
        $eventCounts = [];

        foreach ($groupedTransactions as $caseId => $events) {
            $eventNames = array_column($events, 'event_type');

            foreach ($eventNames as $eventName) {
                if (!isset($eventCounts[$eventName])) {
                    $eventCounts[$eventName] = 0;
                }

                $eventCounts[$eventName]++;
            }

            if (in_array('payment_success', $eventNames, true)) {
                $successCases++;
            }

            if (in_array('payment_failed', $eventNames, true)) {
                $failedCases++;
            }
        }

        usort($transitions, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        arsort($eventCounts);

        return [
            'total_cases' => $totalCases,
            'success_cases' => $successCases,
            'failed_cases' => $failedCases,
            'success_rate' => $totalCases > 0 ? round(($successCases / $totalCases) * 100, 2) : 0,
            'failure_rate' => $totalCases > 0 ? round(($failedCases / $totalCases) * 100, 2) : 0,
            'event_counts' => $eventCounts,
            'top_transitions' => $transitions,
        ];
    }
}
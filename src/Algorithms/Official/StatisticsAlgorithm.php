<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class StatisticsAlgorithm implements AnalysisAlgorithm
{
    public function __construct(
        private array $successEvents = [],
        private array $failureEvents = []
    ) {
    }

    public function name(): string
    {
        return 'Process Statistics';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $totalCases = $log->getCaseCount();

        $successCases = 0;
        $failedCases = 0;
        $eventCounts = [];

        foreach ($log->getTraces() as $trace) {
            $activities = [];

            foreach ($trace->getEvents() as $event) {
                $activity = $event->getActivity();

                $activities[] = $activity;

                if (!isset($eventCounts[$activity])) {
                    $eventCounts[$activity] = 0;
                }

                $eventCounts[$activity]++;
            }

            if ($this->containsAny($activities, $this->successEvents)) {
                $successCases++;
            }

            if ($this->containsAny($activities, $this->failureEvents)) {
                $failedCases++;
            }
        }

        arsort($eventCounts);

        return new AnalysisResult(
            algorithm: 'statistics',
            data: [
                'total_cases' => $totalCases,
                'total_events' => $log->getEventCount(),
                'success_cases' => $successCases,
                'failed_cases' => $failedCases,
                'success_rate' => $totalCases > 0
                    ? round(($successCases / $totalCases) * 100, 2)
                    : 0,
                'failure_rate' => $totalCases > 0
                    ? round(($failedCases / $totalCases) * 100, 2)
                    : 0,
                'event_counts' => $eventCounts,
            ]
        );
    }

    private function containsAny(array $activities, array $targets): bool
    {
        foreach ($targets as $target) {
            if (in_array($target, $activities, true)) {
                return true;
            }
        }

        return false;
    }
}
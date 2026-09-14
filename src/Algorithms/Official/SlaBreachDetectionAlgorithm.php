<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class SlaBreachDetectionAlgorithm implements AnalysisAlgorithm
{
    public function __construct(
        private int $maxCaseDurationSeconds
    ) {
    }

    public function name(): string
    {
        return 'SLA Breach Detection';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $breaches = [];

        foreach ($log->getTraces() as $trace) {
            $events = $trace->getEvents();

            if (count($events) < 2) {
                continue;
            }

            $start = $events[0]
                ->getTimestamp()
                ->getTimestamp();

            $end = $events[count($events) - 1]
                ->getTimestamp()
                ->getTimestamp();

            $duration = max(0, $end - $start);

            if ($duration <= $this->maxCaseDurationSeconds) {
                continue;
            }

            $breaches[] = [
                'case_id' => $trace->getCaseId(),
                'duration' => $duration,
                'sla_limit' => $this->maxCaseDurationSeconds,
                'exceeded_by' => $duration - $this->maxCaseDurationSeconds,
            ];
        }

        usort(
            $breaches,
            fn (array $a, array $b) =>
                $b['exceeded_by'] <=> $a['exceeded_by']
        );

        $totalCases = $log->getCaseCount();
        $breachCount = count($breaches);

        return new AnalysisResult(
            algorithm: 'sla_breach_detection',
            data: [
                'sla_limit' => $this->maxCaseDurationSeconds,
                'breach_count' => $breachCount,
                'breach_percentage' => $totalCases > 0
                    ? round(($breachCount / $totalCases) * 100, 2)
                    : 0,
                'breaches' => $breaches,
            ]
        );
    }
}
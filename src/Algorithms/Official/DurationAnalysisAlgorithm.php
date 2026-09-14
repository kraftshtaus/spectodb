<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class DurationAnalysisAlgorithm implements AnalysisAlgorithm
{
    public function name(): string
    {
        return 'Duration Analysis';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $cases = [];
        $durations = [];

        foreach ($log->getTraces() as $trace) {
            $events = $trace->getEvents();

            if (count($events) < 2) {
                $duration = 0;
            } else {
                $start = $events[0]->getTimestamp()->getTimestamp();
                $end = $events[count($events) - 1]
                    ->getTimestamp()
                    ->getTimestamp();

                $duration = max(0, $end - $start);
            }

            $durations[] = $duration;

            $cases[] = [
                'case_id' => $trace->getCaseId(),
                'duration' => $duration,
                'event_count' => count($events),
            ];
        }

        sort($durations);

        $count = count($durations);

        if ($count === 0) {
            return new AnalysisResult(
                algorithm: 'duration_analysis',
                data: [
                    'average_duration' => 0,
                    'median_duration' => 0,
                    'min_duration' => 0,
                    'max_duration' => 0,
                    'cases' => [],
                ]
            );
        }

        $average = array_sum($durations) / $count;

        if ($count % 2 === 0) {
            $middle = intdiv($count, 2);

            $median = (
                $durations[$middle - 1]
                + $durations[$middle]
            ) / 2;
        } else {
            $median = $durations[intdiv($count, 2)];
        }

        usort(
            $cases,
            fn (array $a, array $b) =>
                $b['duration'] <=> $a['duration']
        );

        return new AnalysisResult(
            algorithm: 'duration_analysis',
            data: [
                'average_duration' => round($average, 2),
                'median_duration' => round($median, 2),
                'min_duration' => min($durations),
                'max_duration' => max($durations),
                'cases' => $cases,
            ]
        );
    }
}
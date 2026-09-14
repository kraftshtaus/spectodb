<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class BottleneckDetectionAlgorithm implements AnalysisAlgorithm
{
    public function name(): string
    {
        return 'Bottleneck Detection';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $transitions = [];

        foreach ($log->getTraces() as $trace) {
            $events = $trace->getEvents();

            for ($i = 0; $i < count($events) - 1; $i++) {
                $fromEvent = $events[$i];
                $toEvent = $events[$i + 1];

                $from = $fromEvent->getActivity();
                $to = $toEvent->getActivity();

                $duration = $toEvent->getTimestamp()->getTimestamp()
                    - $fromEvent->getTimestamp()->getTimestamp();

                if ($duration < 0) {
                    continue;
                }

                $key = $from . '->' . $to;

                if (!isset($transitions[$key])) {
                    $transitions[$key] = [
                        'from' => $from,
                        'to' => $to,
                        'count' => 0,
                        'total_duration' => 0,
                        'min_duration' => null,
                        'max_duration' => null,
                    ];
                }

                $transitions[$key]['count']++;
                $transitions[$key]['total_duration'] += $duration;

                $transitions[$key]['min_duration'] =
                    $transitions[$key]['min_duration'] === null
                        ? $duration
                        : min(
                            $transitions[$key]['min_duration'],
                            $duration
                        );

                $transitions[$key]['max_duration'] =
                    $transitions[$key]['max_duration'] === null
                        ? $duration
                        : max(
                            $transitions[$key]['max_duration'],
                            $duration
                        );
            }
        }

        $results = [];

        foreach ($transitions as $transition) {
            $averageDuration = $transition['count'] > 0
                ? $transition['total_duration'] / $transition['count']
                : 0;

            $results[] = [
                'from' => $transition['from'],
                'to' => $transition['to'],
                'count' => $transition['count'],
                'average_duration' => round($averageDuration, 2),
                'min_duration' => $transition['min_duration'],
                'max_duration' => $transition['max_duration'],
            ];
        }

        usort(
            $results,
            fn (array $a, array $b) =>
                $b['average_duration'] <=> $a['average_duration']
        );

        $bottleneck = $results[0] ?? null;

        return new AnalysisResult(
            algorithm: 'bottleneck_detection',
            data: [
                'bottleneck' => $bottleneck,
                'transitions' => $results,
            ]
        );
    }
}
<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class ThroughputAnalysisAlgorithm implements AnalysisAlgorithm
{
    public function name(): string
    {
        return 'Throughput Analysis';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $transitions = [];

        $firstTimestamp = null;
        $lastTimestamp = null;

        foreach ($log->getTraces() as $trace) {
            $events = $trace->getEvents();

            foreach ($events as $event) {
                $timestamp = $event->getTimestamp()->getTimestamp();

                if ($firstTimestamp === null || $timestamp < $firstTimestamp) {
                    $firstTimestamp = $timestamp;
                }

                if ($lastTimestamp === null || $timestamp > $lastTimestamp) {
                    $lastTimestamp = $timestamp;
                }
            }

            for ($i = 0; $i < count($events) - 1; $i++) {
                $fromEvent = $events[$i];
                $toEvent = $events[$i + 1];

                $from = $fromEvent->getActivity();
                $to = $toEvent->getActivity();

                $duration =
                    $toEvent->getTimestamp()->getTimestamp()
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
                    ];
                }

                $transitions[$key]['count']++;
                $transitions[$key]['total_duration'] += $duration;
            }
        }

        $observationSeconds = 0;

        if ($firstTimestamp !== null && $lastTimestamp !== null) {
            $observationSeconds = max(
                0,
                $lastTimestamp - $firstTimestamp
            );
        }

        $observationHours = $observationSeconds > 0
            ? $observationSeconds / 3600
            : 0;

        $results = [];

        foreach ($transitions as $transition) {
            $averageDuration = $transition['count'] > 0
                ? $transition['total_duration'] / $transition['count']
                : 0;

            $observedRate = $observationHours > 0
                ? $transition['count'] / $observationHours
                : 0;

            $results[] = [
                'from' => $transition['from'],
                'to' => $transition['to'],
                'count' => $transition['count'],
                'average_duration' => round($averageDuration, 2),
                'observed_rate_per_hour' => round($observedRate, 2),
            ];
        }

        usort(
            $results,
            fn (array $a, array $b) =>
                $b['count'] <=> $a['count']
        );

        return new AnalysisResult(
            algorithm: 'throughput_analysis',
            data: [
                'observation_period_seconds' => $observationSeconds,
                'transitions' => $results,
            ]
        );
    }
}
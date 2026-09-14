<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class FrequencyHeatmapAlgorithm implements AnalysisAlgorithm
{
    public function name(): string
    {
        return 'Frequency Heatmap';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $activityCounts = [];
        $transitionCounts = [];

        foreach ($log->getTraces() as $trace) {
            $events = $trace->getEvents();

            foreach ($events as $event) {
                $activity = $event->getActivity();

                if (!isset($activityCounts[$activity])) {
                    $activityCounts[$activity] = 0;
                }

                $activityCounts[$activity]++;
            }

            for ($i = 0; $i < count($events) - 1; $i++) {
                $from = $events[$i]->getActivity();
                $to = $events[$i + 1]->getActivity();

                $key = $from . '->' . $to;

                if (!isset($transitionCounts[$key])) {
                    $transitionCounts[$key] = [
                        'from' => $from,
                        'to' => $to,
                        'count' => 0,
                    ];
                }

                $transitionCounts[$key]['count']++;
            }
        }

        arsort($activityCounts);

        $maxActivityCount = $activityCounts !== []
            ? max($activityCounts)
            : 0;

        $activities = [];

        foreach ($activityCounts as $activity => $count) {
            $intensity = $maxActivityCount > 0
                ? round($count / $maxActivityCount, 4)
                : 0;

            $activities[] = [
                'activity' => $activity,
                'count' => $count,
                'intensity' => $intensity,
            ];
        }

        $transitions = array_values($transitionCounts);

        usort(
            $transitions,
            fn (array $a, array $b) =>
                $b['count'] <=> $a['count']
        );

        $maxTransitionCount = $transitions !== []
            ? max(array_column($transitions, 'count'))
            : 0;

        foreach ($transitions as &$transition) {
            $transition['intensity'] = $maxTransitionCount > 0
                ? round(
                    $transition['count'] / $maxTransitionCount,
                    4
                )
                : 0;
        }

        unset($transition);

        return new AnalysisResult(
            algorithm: 'frequency_heatmap',
            data: [
                'activities' => $activities,
                'transitions' => $transitions,
            ]
        );
    }
}
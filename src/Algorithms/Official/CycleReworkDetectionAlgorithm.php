<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class CycleReworkDetectionAlgorithm implements AnalysisAlgorithm
{
    public function name(): string
    {
        return 'Cycle and Rework Detection';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $affectedCases = [];
        $activityRework = [];
        $totalCycles = 0;

        foreach ($log->getTraces() as $trace) {
            $events = $trace->getEvents();

            $activities = [];

            foreach ($events as $event) {
                $activities[] = $event->getActivity();
            }

            $positions = [];
            $repeatedActivities = [];
            $cycles = [];

            foreach ($activities as $index => $activity) {
                if (!isset($positions[$activity])) {
                    $positions[$activity] = [];
                }

                $positions[$activity][] = $index;
            }

            foreach ($positions as $activity => $indexes) {
                if (count($indexes) <= 1) {
                    continue;
                }

                $repeatCount = count($indexes) - 1;

                $repeatedActivities[] = [
                    'activity' => $activity,
                    'occurrences' => count($indexes),
                    'repeat_count' => $repeatCount,
                ];

                if (!isset($activityRework[$activity])) {
                    $activityRework[$activity] = 0;
                }

                $activityRework[$activity] += $repeatCount;

                for ($i = 0; $i < count($indexes) - 1; $i++) {
                    $start = $indexes[$i];
                    $end = $indexes[$i + 1];

                    $cyclePath = array_slice(
                        $activities,
                        $start,
                        ($end - $start) + 1
                    );

                    $cycles[] = [
                        'start_activity' => $activity,
                        'path' => $cyclePath,
                        'length' => count($cyclePath),
                    ];

                    $totalCycles++;
                }
            }

            if ($repeatedActivities === []) {
                continue;
            }

            $affectedCases[] = [
                'case_id' => $trace->getCaseId(),
                'path' => $activities,
                'repeated_activities' => $repeatedActivities,
                'cycles' => $cycles,
            ];
        }

        arsort($activityRework);

        $totalCases = $log->getCaseCount();
        $affectedCount = count($affectedCases);

        return new AnalysisResult(
            algorithm: 'cycle_rework_detection',
            data: [
                'affected_cases' => $affectedCount,
                'affected_percentage' => $totalCases > 0
                    ? round(($affectedCount / $totalCases) * 100, 2)
                    : 0,

                'total_cycles' => $totalCycles,

                'rework_by_activity' => $activityRework,

                'cases' => $affectedCases,
            ]
        );
    }
}
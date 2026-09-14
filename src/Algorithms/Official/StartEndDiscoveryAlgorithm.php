<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class StartEndDiscoveryAlgorithm implements AnalysisAlgorithm
{
    public function name(): string
    {
        return 'Start / End Discovery';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $startActivities = [];
        $endActivities = [];

        foreach ($log->getTraces() as $trace) {
            $events = $trace->getEvents();

            if ($events === []) {
                continue;
            }

            $firstActivity = $events[0]->getActivity();

            $lastActivity = $events[count($events) - 1]
                ->getActivity();

            if (!isset($startActivities[$firstActivity])) {
                $startActivities[$firstActivity] = 0;
            }

            if (!isset($endActivities[$lastActivity])) {
                $endActivities[$lastActivity] = 0;
            }

            $startActivities[$firstActivity]++;
            $endActivities[$lastActivity]++;
        }

        arsort($startActivities);
        arsort($endActivities);

        $mainStart = array_key_first($startActivities);
        $mainEnd = array_key_first($endActivities);

        return new AnalysisResult(
            algorithm: 'start_end_discovery',
            data: [
                'main_start_activity' => $mainStart,
                'main_end_activity' => $mainEnd,
                'start_activities' => $startActivities,
                'end_activities' => $endActivities,
            ]
        );
    }
}
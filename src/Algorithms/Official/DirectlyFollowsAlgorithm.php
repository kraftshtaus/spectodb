<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class DirectlyFollowsAlgorithm implements AnalysisAlgorithm
{
    public function name(): string
    {
        return 'Directly Follows Graph';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $transitions = [];

        foreach ($log->getTraces() as $trace) {
            $events = $trace->getEvents();

            for ($i = 0; $i < count($events) - 1; $i++) {
                $from = $events[$i]->getActivity();
                $to = $events[$i + 1]->getActivity();

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

        return new AnalysisResult(
            algorithm: 'directly_follows',
            data: [
                'transitions' => array_values($transitions),
            ]
        );
    }
}
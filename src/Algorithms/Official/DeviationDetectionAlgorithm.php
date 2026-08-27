<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class DeviationDetectionAlgorithm implements AnalysisAlgorithm
{
    public function name(): string
    {
        return 'Deviation Detection';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $paths = [];

        foreach ($log->getTraces() as $trace) {
            $activities = [];

            foreach ($trace->getEvents() as $event) {
                $activities[] = $event->getActivity();
            }

            $signature = implode(' -> ', $activities);

            if (!isset($paths[$signature])) {
                $paths[$signature] = [
                    'path' => $activities,
                    'count' => 0,
                ];
            }

            $paths[$signature]['count']++;
        }

        if ($paths === []) {
            return new AnalysisResult(
                algorithm: 'deviation_detection',
                data: [
                    'baseline' => [],
                    'total_deviations' => 0,
                    'affected_cases' => 0,
                    'deviations' => [],
                ]
            );
        }

        uasort(
            $paths,
            fn (array $a, array $b) => $b['count'] <=> $a['count']
        );

        $baseline = reset($paths)['path'];

        $deviations = [];
        $affectedCases = 0;

        foreach ($log->getTraces() as $trace) {
            $observed = [];

            foreach ($trace->getEvents() as $event) {
                $observed[] = $event->getActivity();
            }

            if ($observed === $baseline) {
                continue;
            }

            $missing = array_values(
                array_diff($baseline, $observed)
            );

            $unexpected = array_values(
                array_diff($observed, $baseline)
            );

            $reordered =
                $missing === []
                && $unexpected === []
                && $observed !== $baseline;

            $deviations[] = [
                'case_id' => $trace->getCaseId(),
                'expected_path' => $baseline,
                'observed_path' => $observed,
                'missing_activities' => $missing,
                'unexpected_activities' => $unexpected,
                'reordered' => $reordered,
            ];

            $affectedCases++;
        }

        $totalCases = $log->getCaseCount();

        return new AnalysisResult(
            algorithm: 'deviation_detection',
            data: [
                'baseline' => $baseline,
                'total_deviations' => count($deviations),
                'affected_cases' => $affectedCases,
                'affected_percentage' => $totalCases > 0
                    ? round(($affectedCases / $totalCases) * 100, 2)
                    : 0,
                'deviations' => $deviations,
            ]
        );
    }
}
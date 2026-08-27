<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class ProcessVariantsAlgorithm implements AnalysisAlgorithm
{
    public function name(): string
    {
        return 'Process Variants';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $variants = [];

        foreach ($log->getTraces() as $trace) {
            $activities = [];

            foreach ($trace->getEvents() as $event) {
                $activities[] = $event->getActivity();
            }

            $signature = implode(' -> ', $activities);

            if (!isset($variants[$signature])) {
                $variants[$signature] = [
                    'path' => $activities,
                    'count' => 0,
                ];
            }

            $variants[$signature]['count']++;
        }

        $totalCases = $log->getCaseCount();

        foreach ($variants as &$variant) {
            $variant['percentage'] = $totalCases > 0
                ? round(($variant['count'] / $totalCases) * 100, 2)
                : 0;
        }

        unset($variant);

        usort(
            $variants,
            fn (array $a, array $b) => $b['count'] <=> $a['count']
        );

        return new AnalysisResult(
            algorithm: 'process_variants',
            data: [
                'total_variants' => count($variants),
                'variants' => $variants,
            ]
        );
    }
}
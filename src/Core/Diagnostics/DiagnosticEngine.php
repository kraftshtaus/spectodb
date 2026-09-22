<?php

declare(strict_types=1);

namespace SpectoDB\Core\Diagnostics;

use SpectoDB\Core\Analysis\AnalysisResult;

final class DiagnosticEngine
{
    /**
     * @param array<string, AnalysisResult> $results
     */
public function analyze(
    array $results,
    array $externalIssues = []
): array {
    $issues = $externalIssues;

        $this->collectDeviations(
            $results['deviations'] ?? null,
            $issues
        );

        $this->collectBottlenecks(
            $results['bottlenecks'] ?? null,
            $issues
        );

        $this->collectSlaBreaches(
            $results['sla'] ?? null,
            $issues
        );

        $this->collectRework(
            $results['cycle_rework'] ?? null,
            $issues
        );

        $this->collectFailures(
            $results['statistics'] ?? null,
            $issues
        );

        usort(
            $issues,
            fn (array $a, array $b) =>
                $this->severityWeight($b['severity'])
                <=> $this->severityWeight($a['severity'])
        );

$sourceCounts = [
    'process' => 0,
    'database' => 0,
    'sql' => 0,
    'system' => 0,
];

foreach ($issues as $issue) {
    $source = $issue['source'] ?? 'system';

    if (!isset($sourceCounts[$source])) {
        $sourceCounts[$source] = 0;
    }

    $sourceCounts[$source]++;
}

return [
    'total' => count($issues),

    'errors' => count(
        array_filter(
            $issues,
            fn (array $issue) =>
                $issue['severity'] === 'error'
        )
    ),

    'warnings' => count(
        array_filter(
            $issues,
            fn (array $issue) =>
                $issue['severity'] === 'warning'
        )
    ),

    'info' => count(
        array_filter(
            $issues,
            fn (array $issue) =>
                $issue['severity'] === 'info'
        )
    ),

    'source_counts' => $sourceCounts,

    'issues' => $issues,
];
    }

    private function collectDeviations(
        ?AnalysisResult $result,
        array &$issues
    ): void {
        if ($result === null) {
            return;
        }

        $data = $result->getData();

        foreach ($data['deviations'] ?? [] as $deviation) {
            $missing =
                $deviation['missing_activities']
                ?? [];

            $unexpected =
                $deviation['unexpected_activities']
                ?? [];

            $reordered =
                $deviation['reordered']
                ?? false;

            if ($missing !== []) {
                $issues[] = [
                    'severity' => 'error',
                    'type' => 'missing_activity',
                    'source' => 'process',
                    'title' => 'Missing activity',
                    'message' =>
                        'Expected activity is missing from the process.',
                    'case_id' =>
                        $deviation['case_id']
                        ?? null,
                    'details' => [
                        'missing' => $missing,
                        'expected_path' =>
                            $deviation['expected_path']
                            ?? [],
                        'observed_path' =>
                            $deviation['observed_path']
                            ?? [],
                    ],
                ];
            }

            if ($unexpected !== []) {
                $issues[] = [
                    'severity' => 'warning',
                    'type' => 'unexpected_activity',
                    'source' => 'process',
                    'title' => 'Unexpected activity',
                    'message' =>
                        'The case contains an unexpected process activity.',
                    'case_id' =>
                        $deviation['case_id']
                        ?? null,
                    'details' => [
                        'unexpected' => $unexpected,
                        'expected_path' =>
                            $deviation['expected_path']
                            ?? [],
                        'observed_path' =>
                            $deviation['observed_path']
                            ?? [],
                    ],
                ];
            }

            if (
                $reordered
                && $missing === []
                && $unexpected === []
            ) {
                $issues[] = [
                    'severity' => 'warning',
                    'type' => 'wrong_order',
                    'source' => 'process',
                    'title' => 'Activity order changed',
                    'message' =>
                        'Activities appear in a different order than the baseline process.',
                    'case_id' =>
                        $deviation['case_id']
                        ?? null,
                    'details' => [
                        'expected_path' =>
                            $deviation['expected_path']
                            ?? [],
                        'observed_path' =>
                            $deviation['observed_path']
                            ?? [],
                    ],
                ];
            }
        }
    }

    private function collectBottlenecks(
        ?AnalysisResult $result,
        array &$issues
    ): void {
        if ($result === null) {
            return;
        }

        $data = $result->getData();

        $bottleneck =
            $data['bottleneck']
            ?? null;

        if ($bottleneck === null) {
            return;
        }

        $issues[] = [
            'severity' => 'warning',
            'type' => 'bottleneck',
            'source' => 'process',
            'title' => 'Potential bottleneck',
            'message' =>
                'A slow process transition was detected.',
            'case_id' => null,

            'details' => [
                'from' =>
                    $bottleneck['from']
                    ?? null,

                'to' =>
                    $bottleneck['to']
                    ?? null,

                'average_duration' =>
                    $bottleneck['average_duration']
                    ?? 0,

                'min_duration' =>
                    $bottleneck['min_duration']
                    ?? 0,

                'max_duration' =>
                    $bottleneck['max_duration']
                    ?? 0,

                'count' =>
                    $bottleneck['count']
                    ?? 0,
            ],
        ];
    }

    private function collectSlaBreaches(
        ?AnalysisResult $result,
        array &$issues
    ): void {
        if ($result === null) {
            return;
        }

        $data = $result->getData();

        foreach ($data['breaches'] ?? [] as $breach) {
            $issues[] = [
                'severity' => 'error',
                'type' => 'sla_breach',
                'source' => 'process',
                'title' => 'SLA breach',
                'message' =>
                    'The process case exceeded its configured SLA.',
                'case_id' =>
                    $breach['case_id']
                    ?? null,

                'details' => [
                    'duration' =>
                        $breach['duration']
                        ?? 0,

                    'sla_limit' =>
                        $breach['sla_limit']
                        ?? 0,

                    'exceeded_by' =>
                        $breach['exceeded_by']
                        ?? 0,
                ],
            ];
        }
    }

    private function collectRework(
        ?AnalysisResult $result,
        array &$issues
    ): void {
        if ($result === null) {
            return;
        }

        $data = $result->getData();

        foreach ($data['cases'] ?? [] as $case) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'rework',
                'source' => 'process',
                'title' => 'Rework detected',
                'message' =>
                    'Repeated activities or process cycles were detected.',
                'case_id' =>
                    $case['case_id']
                    ?? null,

                'details' => [
                    'path' =>
                        $case['path']
                        ?? [],

                    'repeated_activities' =>
                        $case['repeated_activities']
                        ?? [],

                    'cycles' =>
                        $case['cycles']
                        ?? [],
                ],
            ];
        }
    }

    private function collectFailures(
        ?AnalysisResult $result,
        array &$issues
    ): void {
        if ($result === null) {
            return;
        }

        $data = $result->getData();

        $failedCases =
            $data['failed_cases']
            ?? 0;

        if ($failedCases <= 0) {
            return;
        }

        $issues[] = [
            'severity' => 'info',
            'type' => 'failed_cases',
            'source' => 'process',
            'title' => 'Failed cases detected',
            'message' =>
                "{$failedCases} process cases are classified as failed.",
            'case_id' => null,

            'details' => [
                'failed_cases' => $failedCases,
                'failure_rate' =>
                    $data['failure_rate']
                    ?? 0,
            ],
        ];
    }

    private function severityWeight(
        string $severity
    ): int {
        return match ($severity) {
            'error' => 3,
            'warning' => 2,
            default => 1,
        };
    }
}
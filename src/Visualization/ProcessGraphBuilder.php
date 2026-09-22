<?php

declare(strict_types=1);

namespace SpectoDB\Visualization;

final class ProcessGraphBuilder
{
    public function build(
        array $transitions,
        array $heatmap,
        array $bottlenecks,
        array $startEnd,
        array $deviations
    ): array {
        $nodes = [];
        $edges = [];

        /* Start &  End information */

        $startActivities = array_keys(
            $startEnd['start_activities'] ?? []
        );

        $endActivities = array_keys(
            $startEnd['end_activities'] ?? []
        );


        /* Activity heatmap */

        $activityIntensity = [];

        foreach ($heatmap['activities'] ?? [] as $activity) {
            $activityIntensity[$activity['activity']] =
                $activity['intensity'] ?? 0;
        }


        /* Transition heatmap */

        $transitionIntensity = [];

        foreach ($heatmap['transitions'] ?? [] as $transition) {
            $key =
                $transition['from']
                . '->'
                . $transition['to'];

            $transitionIntensity[$key] =
                $transition['intensity'] ?? 0;
        }


        /* Baseline transitions || Used to distinguish the main process from alternative paths. */

        $baselineTransitions = [];

        $baseline = $deviations['baseline'] ?? [];

        for ($i = 0; $i < count($baseline) - 1; $i++) {
            $key =
                $baseline[$i]
                . '->'
                . $baseline[$i + 1];

            $baselineTransitions[$key] = true;
        }


       /*Bottleneck*/

        $mainBottleneck = $bottlenecks['bottleneck'] ?? null;

        $bottleneckKey = null;

        if ($mainBottleneck !== null) {
            $bottleneckKey =
                ($mainBottleneck['from'] ?? '')
                . '->'
                . ($mainBottleneck['to'] ?? '');
        }


        /*Build graph*/

        foreach ($transitions as $transition) {
            $from = $transition['from'];
            $to = $transition['to'];

            /*
             * Nodes
             */

            if (!isset($nodes[$from])) {
                $nodes[$from] = $this->createNode(
                    $from,
                    $activityIntensity[$from] ?? 0,
                    in_array($from, $startActivities, true),
                    in_array($from, $endActivities, true)
                );
            }

            if (!isset($nodes[$to])) {
                $nodes[$to] = $this->createNode(
                    $to,
                    $activityIntensity[$to] ?? 0,
                    in_array($to, $startActivities, true),
                    in_array($to, $endActivities, true)
                );
            }


            /*
             * Edge
             */

            $key = $from . '->' . $to;

            $isBaseline = isset(
                $baselineTransitions[$key]
            );

            $isBottleneck =
                $bottleneckKey !== null
                && $key === $bottleneckKey;

            $edges[] = [
                'id' => $key,

                'from' => $from,
                'to' => $to,

                'count' => $transition['count'] ?? 0,

                'intensity' =>
                    $transitionIntensity[$key] ?? 0,

                'baseline' => $isBaseline,

                'deviation' => !$isBaseline,

                'bottleneck' => $isBottleneck,
            ];
        }


        return [
            'nodes' => array_values($nodes),
            'edges' => $edges,

            'meta' => [
                'node_count' => count($nodes),
                'edge_count' => count($edges),
                'baseline' => $baseline,
            ],
        ];
    }


    private function createNode(
        string $activity,
        float $intensity,
        bool $isStart,
        bool $isEnd
    ): array {
        return [
            'id' => $activity,
            'label' => $activity,

            'intensity' => $intensity,

            'start' => $isStart,
            'end' => $isEnd,
        ];
    }
}
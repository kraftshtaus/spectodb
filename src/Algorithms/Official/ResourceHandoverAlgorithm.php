<?php

declare(strict_types=1);

namespace SpectoDB\Algorithms\Official;

use SpectoDB\Contracts\AnalysisAlgorithm;
use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

final class ResourceHandoverAlgorithm implements AnalysisAlgorithm
{
    public function __construct(
        private string $resourceAttribute = 'resource'
    ) {
    }

    public function name(): string
    {
        return 'Resource Handover Analysis';
    }

    public function analyze(EventLog $log): AnalysisResult
    {
        $handovers = [];
        $resourceCounts = [];
        $eventsWithResource = 0;

        foreach ($log->getTraces() as $trace) {
            $events = $trace->getEvents();

            foreach ($events as $event) {
                $resource = $event->getAttribute(
                    $this->resourceAttribute
                );

                if ($resource === null || $resource === '') {
                    continue;
                }

                $eventsWithResource++;

                $resource = (string) $resource;

                if (!isset($resourceCounts[$resource])) {
                    $resourceCounts[$resource] = 0;
                }

                $resourceCounts[$resource]++;
            }

            for ($i = 0; $i < count($events) - 1; $i++) {
                $fromEvent = $events[$i];
                $toEvent = $events[$i + 1];

                $fromResource = $fromEvent->getAttribute(
                    $this->resourceAttribute
                );

                $toResource = $toEvent->getAttribute(
                    $this->resourceAttribute
                );

                if (
                    $fromResource === null
                    || $toResource === null
                    || $fromResource === ''
                    || $toResource === ''
                ) {
                    continue;
                }

                $fromResource = (string) $fromResource;
                $toResource = (string) $toResource;

                if ($fromResource === $toResource) {
                    continue;
                }

                $key = $fromResource . '->' . $toResource;

                if (!isset($handovers[$key])) {
                    $handovers[$key] = [
                        'from_resource' => $fromResource,
                        'to_resource' => $toResource,
                        'count' => 0,
                    ];
                }

                $handovers[$key]['count']++;
            }
        }

        arsort($resourceCounts);

        $handoverList = array_values($handovers);

        usort(
            $handoverList,
            fn (array $a, array $b) =>
                $b['count'] <=> $a['count']
        );

        return new AnalysisResult(
            algorithm: 'resource_handover',
            data: [
                'resource_attribute' => $this->resourceAttribute,
                'events_with_resource' => $eventsWithResource,
                'resource_counts' => $resourceCounts,
                'handovers' => $handoverList,
                'data_available' => $eventsWithResource > 0,
            ]
        );
    }
}
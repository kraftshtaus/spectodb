<?php

declare(strict_types=1);

namespace SpectoDB\Core\EventLog;

final class EventLog
{
    /**
     * @var Trace[]
     */
    private array $traces = [];

    public function addTrace(Trace $trace): void
    {
        $this->traces[] = $trace;
    }

    /**
     * @return Trace[]
     */
    public function getTraces(): array
    {
        return $this->traces;
    }

    public function getCaseCount(): int
    {
        return count($this->traces);
    }

    public function getEventCount(): int
    {
        $count = 0;

        foreach ($this->traces as $trace) {
            $count += $trace->getEventCount();
        }

        return $count;
    }

    public function isEmpty(): bool
    {
        return $this->traces === [];
    }
}
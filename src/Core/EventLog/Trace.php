<?php

declare(strict_types=1);

namespace SpectoDB\Core\EventLog;

final class Trace
{
    /**
     * @var Event[]
     */
    private array $events = [];

    public function __construct(
        private string|int $caseId
    ) {
    }

    public function addEvent(Event $event): void
    {
        $this->events[] = $event;
    }

    public function getCaseId(): string|int
    {
        return $this->caseId;
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function getEventCount(): int
    {
        return count($this->events);
    }
}
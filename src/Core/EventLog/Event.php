<?php

declare(strict_types=1);

namespace SpectoDB\Core\EventLog;

use DateTimeImmutable;

final class Event
{
    public function __construct(
        private string|int $caseId,
        private string $activity,
        private DateTimeImmutable $timestamp
    ) {
    }

    public function getCaseId(): string|int
    {
        return $this->caseId;
    }

    public function getActivity(): string
    {
        return $this->activity;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }
}
<?php

declare(strict_types=1);

namespace SpectoDB\Core\EventLog;

use DateTimeImmutable;

final class Event
{
    public function __construct(
        private string|int $caseId,
        private string $activity,
        private DateTimeImmutable $timestamp,
        private array $attributes = []
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

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(
        string $name,
        mixed $default = null
    ): mixed {
        return $this->attributes[$name] ?? $default;
    }

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }
}
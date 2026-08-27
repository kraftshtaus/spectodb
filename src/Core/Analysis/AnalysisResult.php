<?php

declare(strict_types=1);

namespace SpectoDB\Core\Analysis;

final class AnalysisResult
{
    public function __construct(
        private string $algorithm,
        private array $data = []
    ) {
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'algorithm' => $this->algorithm,
            'data' => $this->data,
        ];
    }
}
<?php

declare(strict_types=1);

namespace SpectoDB\Core\Analysis;

use InvalidArgumentException;
use SpectoDB\Contracts\AnalysisAlgorithm;

final class AlgorithmRegistry
{
    /**
     * @var array<string, AnalysisAlgorithm>
     */
    private array $algorithms = [];

    public function register(
        string $id,
        AnalysisAlgorithm $algorithm
    ): void {
        $this->algorithms[$id] = $algorithm;
    }

    public function has(string $id): bool
    {
        return isset($this->algorithms[$id]);
    }

    public function get(string $id): AnalysisAlgorithm
    {
        if (!$this->has($id)) {
            throw new InvalidArgumentException(
                "Algorithm '{$id}' is not registered."
            );
        }

        return $this->algorithms[$id];
    }

    /**
     * @return array<string, AnalysisAlgorithm>
     */
    public function all(): array
    {
        return $this->algorithms;
    }
}
<?php

declare(strict_types=1);

namespace SpectoDB\Contracts;

use SpectoDB\Core\Analysis\AnalysisResult;
use SpectoDB\Core\EventLog\EventLog;

interface AnalysisAlgorithm
{
    public function name(): string;

    public function analyze(EventLog $log): AnalysisResult;
}
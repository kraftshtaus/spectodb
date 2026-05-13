<?php

class FlowchartGenerator
{
    public function generateMermaid(array $transitions): string
    {
        $lines = ["flowchart TD"];

        foreach ($transitions as $transition) {
            $from = $this->sanitizeNode($transition['from']);
            $to = $this->sanitizeNode($transition['to']);
            $count = $transition['count'];

            $lines[] = sprintf(
                '    %s["%s"] -->|%d| %s["%s"]',
                $from,
                $transition['from'],
                $count,
                $to,
                $transition['to']
            );
        }

        return implode("\n", $lines);
    }

    private function sanitizeNode(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9_]/', '_', $name);
    }
}
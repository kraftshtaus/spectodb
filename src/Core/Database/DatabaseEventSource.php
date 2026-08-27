<?php

declare(strict_types=1);

namespace SpectoDB\Core\Database;

use DateTimeImmutable;
use PDO;
use SpectoDB\Core\EventLog\Event;
use SpectoDB\Core\EventLog\EventLog;
use SpectoDB\Core\EventLog\Trace;

final class DatabaseEventSource
{
    public function __construct(
        private PDO $pdo,
        private array $mapping
    ) {
    }

    public function load(): EventLog
    {
        $table = $this->mapping['table'];
        $caseColumn = $this->mapping['case_id'];
        $eventColumn = $this->mapping['event'];
        $timestampColumn = $this->mapping['timestamp'];

        $sql = sprintf(
            '
            SELECT
                %s AS case_id,
                %s AS activity,
                %s AS event_timestamp
            FROM %s
            ORDER BY %s, %s
            ',
            $caseColumn,
            $eventColumn,
            $timestampColumn,
            $table,
            $caseColumn,
            $timestampColumn
        );

        $rows = $this->pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

        $traces = [];

        foreach ($rows as $row) {
            $caseId = $row['case_id'];

            if (!isset($traces[$caseId])) {
                $traces[$caseId] = new Trace($caseId);
            }

            $event = new Event(
                $caseId,
                $row['activity'],
                new DateTimeImmutable($row['event_timestamp'])
            );

            $traces[$caseId]->addEvent($event);
        }

        $eventLog = new EventLog();

        foreach ($traces as $trace) {
            $eventLog->addTrace($trace);
        }

        return $eventLog;
    }
}
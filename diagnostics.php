<?php

declare(strict_types=1);

use SpectoDB\Algorithms\Official\BottleneckDetectionAlgorithm;
use SpectoDB\Algorithms\Official\CycleReworkDetectionAlgorithm;
use SpectoDB\Algorithms\Official\DeviationDetectionAlgorithm;
use SpectoDB\Algorithms\Official\SlaBreachDetectionAlgorithm;
use SpectoDB\Algorithms\Official\StatisticsAlgorithm;
use SpectoDB\Core\Analysis\AlgorithmRegistry;
use SpectoDB\Core\Database\DatabaseEventSource;
use SpectoDB\Core\Database\DatabaseManager;
use SpectoDB\Core\Diagnostics\DiagnosticEngine;
use SpectoDB\Core\Database\Schema\SchemaInspectorManager;
use SpectoDB\Core\Diagnostics\DatabaseDiagnosticService;
use SpectoDB\Core\Diagnostics\SqlDiagnosticService;

session_start();

require_once __DIR__ . '/vendor/autoload.php';

// Configuration
$defaultConfig = require __DIR__ . '/config.php';

$config = $_SESSION['spectodb_config']
    ?? $defaultConfig;

    $databaseManager = new DatabaseManager();

$schemaManager =
    new SchemaInspectorManager();

$databaseDiagnosticService =
    new DatabaseDiagnosticService(
        $databaseManager,
        $schemaManager
    );

// Database diagnostics work even if connection fails
$databaseIssues =
    $databaseDiagnosticService->analyze(
        $config
    );

$analysisResults = [];
$sqlIssues = [];

try {
    $pdo = $databaseManager->connect(
        $config['db']
    );

    // SQL diagnostics
    $sqlDiagnosticService =
        new SqlDiagnosticService();

    $sqlIssues =
        $sqlDiagnosticService->analyze(
            $pdo,
            $config['mapping']
        );

    $eventSource =
        new DatabaseEventSource(
            $pdo,
            $config['mapping']
        );

    $eventLog =
        $eventSource->load();

    $registry =
        new AlgorithmRegistry();

    $registry->register(
        'statistics',
        new StatisticsAlgorithm(
            $config['analysis']['success_events']
                ?? [],
            $config['analysis']['failure_events']
                ?? []
        )
    );

    $registry->register(
        'deviations',
        new DeviationDetectionAlgorithm()
    );

    $registry->register(
        'bottlenecks',
        new BottleneckDetectionAlgorithm()
    );

    $registry->register(
        'cycle_rework',
        new CycleReworkDetectionAlgorithm()
    );

    $registry->register(
        'sla',
        new SlaBreachDetectionAlgorithm(
            $config['analysis']['sla']['max_case_duration_seconds']
                ?? 3600
        )
    );

    foreach (
        $registry->all()
        as $id => $algorithm
    ) {
        $analysisResults[$id] =
            $algorithm->analyze(
                $eventLog
            );
    }

} catch (Throwable $e) {
    // Do not kill Diagnostics page.
    // DatabaseDiagnosticService already reports DB problems.
}

$diagnosticEngine =
    new DiagnosticEngine();

$externalIssues = array_merge(
    $databaseIssues,
    $sqlIssues
);

$diagnostics =
    $diagnosticEngine->analyze(
        $analysisResults,
        $externalIssues
    );  

// Page
$activePage = 'diagnostics';

$pageTitle = 'Diagnostics';

$pageSubtitle =
    'Detected process problems and analysis findings';

$databaseName = htmlspecialchars(
    $config['db']['dbname']
        ?? ($config['db']['path'] ?? 'Database'),
    ENT_QUOTES,
    'UTF-8'
);

$topbarRight =
    '<div class="db-selector">'
    . $databaseName
    . '</div>';

function formatDiagnosticValue(mixed $value): string
{
    if (is_bool($value)) {
        return $value ? 'Yes' : 'No';
    }

    if (is_array($value)) {
        if ($value === []) {
            return '—';
        }

        $isList = array_is_list($value);

        if ($isList) {
            $simple = true;

            foreach ($value as $item) {
                if (is_array($item) || is_object($item)) {
                    $simple = false;
                    break;
                }
            }

            if ($simple) {
                return implode(' → ', array_map(
                    static fn ($item) => (string) $item,
                    $value
                ));
            }
        }

        return json_encode(
            $value,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRETTY_PRINT
        ) ?: '—';
    }

    if ($value === null || $value === '') {
        return '—';
    }

    return (string) $value;
}

$sourceCounts = $diagnostics['source_counts'] ?? [
    'process' => 0,
    'database' => 0,
    'sql' => 0,
    'system' => 0,
];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>SpectoDB - Diagnostics</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/diagnostics.css"
    >
</head>

<body>

<div class="app-layout">

    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main diagnostics-main">

        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <!-- Summary -->

        <section class="diagnostics-summary">

            <div class="diagnostic-stat">

                <span>Total findings</span>

                <strong>
                    <?= htmlspecialchars(
                        (string) ($diagnostics['total'] ?? 0),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>

            <div class="diagnostic-stat error-stat">

                <span>Errors</span>

                <strong>
                    <?= htmlspecialchars(
                        (string) ($diagnostics['errors'] ?? 0),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>

            <div class="diagnostic-stat warning-stat">

                <span>Warnings</span>

                <strong>
                    <?= htmlspecialchars(
                        (string) ($diagnostics['warnings'] ?? 0),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>

            <div class="diagnostic-stat info-stat">

                <span>Info</span>

                <strong>
                    <?= htmlspecialchars(
                        (string) ($diagnostics['info'] ?? 0),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>

        </section>

        <!-- Filters -->

        <section class="diagnostics-toolbar">

<div class="diagnostic-filters">

    <button
        class="diagnostic-filter active"
        data-filter="all"
        type="button"
    >
        All
        <span><?= $diagnostics['total'] ?? 0 ?></span>
    </button>

    <button
        class="diagnostic-filter"
        data-filter="process"
        type="button"
        <?= ($sourceCounts['process'] ?? 0) === 0 ? 'disabled' : '' ?>
    >
        Process
        <span><?= $sourceCounts['process'] ?? 0 ?></span>
    </button>

    <button
        class="diagnostic-filter"
        data-filter="database"
        type="button"
        <?= ($sourceCounts['database'] ?? 0) === 0 ? 'disabled' : '' ?>
    >
        Database
        <span><?= $sourceCounts['database'] ?? 0 ?></span>
    </button>

    <button
        class="diagnostic-filter"
        data-filter="sql"
        type="button"
        <?= ($sourceCounts['sql'] ?? 0) === 0 ? 'disabled' : '' ?>
    >
        SQL
        <span><?= $sourceCounts['sql'] ?? 0 ?></span>
    </button>

    <button
        class="diagnostic-filter"
        data-filter="system"
        type="button"
        <?= ($sourceCounts['system'] ?? 0) === 0 ? 'disabled' : '' ?>
    >
        System
        <span><?= $sourceCounts['system'] ?? 0 ?></span>
    </button>

</div>

            <div class="severity-filters">

                <select id="severityFilter">

                    <option value="all">
                        All severities
                    </option>

                    <option value="error">
                        Errors
                    </option>

                    <option value="warning">
                        Warnings
                    </option>

                    <option value="info">
                        Info
                    </option>

                </select>

            </div>

        </section>

        <!-- Findings -->

        <section
            id="diagnosticsList"
            class="diagnostics-list"
        >

            <?php if (($diagnostics['issues'] ?? []) === []): ?>

                <div class="diagnostics-empty">

                    <strong>
                        No diagnostic findings
                    </strong>

                    <p>
                        SpectoDB did not detect problems in the current event log.
                    </p>

                </div>

            <?php else: ?>

                <?php foreach ($diagnostics['issues'] as $index => $issue): ?>

                    <?php
                    $severity = $issue['severity'] ?? 'info';
                    $source = $issue['source'] ?? 'process';
                    $caseId = $issue['case_id'] ?? null;
                    ?>

                    <article
                        class="diagnostic-item <?= htmlspecialchars(
                            $severity,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        data-source="<?= htmlspecialchars(
                            $source,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        data-severity="<?= htmlspecialchars(
                            $severity,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                        <div class="diagnostic-marker"></div>

                        <div class="diagnostic-body">

                            <div class="diagnostic-item-header">

                                <div>

                                    <div class="diagnostic-meta">

                                        <span
                                            class="severity-badge <?= htmlspecialchars(
                                                $severity,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                strtoupper($severity),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <span class="source-badge">
                                            <?= htmlspecialchars(
                                                ucfirst($source),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <?php if ($caseId !== null): ?>

                                            <span class="case-badge">
                                                Case #<?= htmlspecialchars(
                                                    (string) $caseId,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <h2>
                                        <?= htmlspecialchars(
                                            $issue['title'] ?? 'Diagnostic finding',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </h2>

                                    <p>
                                        <?= htmlspecialchars(
                                            $issue['message'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>

                                </div>

                                <span class="diagnostic-index">
                                    #<?= $index + 1 ?>
                                </span>

                            </div>

                            <?php if (($issue['details'] ?? []) !== []): ?>

                                <details class="diagnostic-details">

                                    <summary>
                                        View details
                                    </summary>

                                    <div class="diagnostic-detail-list">

                                        <?php foreach ($issue['details'] as $key => $value): ?>

                                            <div class="diagnostic-detail-row">

                                                <span>
                                                    <?= htmlspecialchars(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            ucfirst($key)
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </span>

                                                <strong>
                                                    <?= nl2br(
                                                        htmlspecialchars(
                                                            formatDiagnosticValue($value),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        )
                                                    ) ?>
                                                </strong>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                </details>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

        <div
            id="noFilteredResults"
            class="diagnostics-empty"
            hidden
        >
            <strong>
                No matching findings
            </strong>

            <p>
                Change the diagnostic filters to see other results.
            </p>
        </div>

    </main>

</div>

<script src="assets/js/diagnostics.js"></script>

</body>

</html>
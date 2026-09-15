<?php

declare(strict_types=1);

use SpectoDB\Algorithms\Official\BottleneckDetectionAlgorithm;
use SpectoDB\Algorithms\Official\CycleReworkDetectionAlgorithm;
use SpectoDB\Algorithms\Official\DeviationDetectionAlgorithm;
use SpectoDB\Algorithms\Official\DirectlyFollowsAlgorithm;
use SpectoDB\Algorithms\Official\DurationAnalysisAlgorithm;
use SpectoDB\Algorithms\Official\FrequencyHeatmapAlgorithm;
use SpectoDB\Algorithms\Official\ProcessVariantsAlgorithm;
use SpectoDB\Algorithms\Official\ResourceHandoverAlgorithm;
use SpectoDB\Algorithms\Official\SlaBreachDetectionAlgorithm;
use SpectoDB\Algorithms\Official\StartEndDiscoveryAlgorithm;
use SpectoDB\Algorithms\Official\StatisticsAlgorithm;
use SpectoDB\Algorithms\Official\ThroughputAnalysisAlgorithm;
use SpectoDB\Core\Analysis\AlgorithmRegistry;
use SpectoDB\Core\Database\DatabaseEventSource;
use SpectoDB\Core\Database\DatabaseManager;

session_start();

require_once __DIR__ . '/vendor/autoload.php';

// Configuration
$defaultConfig = require __DIR__ . '/config.php';

$config = $_SESSION['spectodb_config']
    ?? $defaultConfig;

try {
    // Database
    $databaseManager = new DatabaseManager();

    $pdo = $databaseManager->connect(
        $config['db']
    );

    // Event log
    $eventSource = new DatabaseEventSource(
        $pdo,
        $config['mapping']
    );

    $eventLog = $eventSource->load();

    // Algorithms
    $registry = new AlgorithmRegistry();

    $registry->register(
        'directly_follows',
        new DirectlyFollowsAlgorithm()
    );

    $registry->register(
        'statistics',
        new StatisticsAlgorithm(
            $config['analysis']['success_events'] ?? [],
            $config['analysis']['failure_events'] ?? []
        )
    );

    $registry->register(
        'variants',
        new ProcessVariantsAlgorithm()
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
        'duration',
        new DurationAnalysisAlgorithm()
    );

    $registry->register(
        'throughput',
        new ThroughputAnalysisAlgorithm()
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

    $registry->register(
        'start_end',
        new StartEndDiscoveryAlgorithm()
    );

    $registry->register(
        'frequency_heatmap',
        new FrequencyHeatmapAlgorithm()
    );

    $registry->register(
        'resource_handover',
        new ResourceHandoverAlgorithm()
    );

    // Run algorithms
    $analysisResults = [];

    foreach ($registry->all() as $id => $algorithm) {
        $analysisResults[$id] = $algorithm->analyze(
            $eventLog
        );
    }

    // Results
    $transitions = $analysisResults['directly_follows']
        ->get('transitions', []);

    $stats = $analysisResults['statistics']
        ->getData();

    $variants = $analysisResults['variants']
        ->getData();

    $deviations = $analysisResults['deviations']
        ->getData();

    $bottlenecks = $analysisResults['bottlenecks']
        ->getData();

    $duration = $analysisResults['duration']
        ->getData();

    $throughput = $analysisResults['throughput']
        ->getData();

    $cycles = $analysisResults['cycle_rework']
        ->getData();

    $sla = $analysisResults['sla']
        ->getData();

    $startEnd = $analysisResults['start_end']
        ->getData();

    $heatmap = $analysisResults['frequency_heatmap']
        ->getData();

    $handover = $analysisResults['resource_handover']
        ->getData();

    // Charts
    $eventLabels = array_keys(
        $stats['event_counts'] ?? []
    );

    $eventValues = array_values(
        $stats['event_counts'] ?? []
    );

} catch (Throwable $e) {
    http_response_code(500);

    die(
        'SpectoDB Error: '
        . htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

// Page
$activePage = 'overview';

$pageTitle = 'Overview';

$pageSubtitle =
    'Process mining and database analysis dashboard';

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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>SpectoDB - Overview</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<div class="app-layout">

    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main">

        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <section class="dashboard-grid">

            <!-- Key Indicators -->

            <div class="panel indicators-panel">

                <div class="panel-header">
                    <h2>Key Indicators</h2>
                    <span>Current event log</span>
                </div>

                <div class="indicator-row">

                    <div class="circle-stat">
                        <div class="circle">
                            <?= htmlspecialchars(
                                (string) ($stats['total_cases'] ?? 0),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

                        <span>Total cases</span>
                    </div>

                    <div class="circle-stat">
                        <div class="circle">
                            <?= htmlspecialchars(
                                (string) ($stats['success_cases'] ?? 0),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

                        <span>Successful</span>
                    </div>

                    <div class="circle-stat">
                        <div class="circle">
                            <?= htmlspecialchars(
                                (string) ($stats['failed_cases'] ?? 0),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

                        <span>Failed</span>
                    </div>

                    <div class="circle-stat">
                        <div class="circle">
                            <?= htmlspecialchars(
                                (string) ($stats['success_rate'] ?? 0),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>%
                        </div>

                        <span>Success rate</span>
                    </div>

                </div>

            </div>

            <!-- Event Frequency -->

            <div class="panel">

                <div class="panel-header">
                    <h2>Event Frequency</h2>
                    <span>Event log activities</span>
                </div>

                <div class="chart-box">
                    <canvas id="eventChart"></canvas>
                </div>

            </div>

            <!-- Success / Failure -->

            <div class="panel wide-panel">

                <div class="panel-header">
                    <h2>Success / Failure</h2>
                    <span>Process result ratio</span>
                </div>

                <div class="chart-box large-chart">
                    <canvas id="successChart"></canvas>
                </div>

            </div>

            <!-- Problem Areas -->

            <div class="panel">

                <div class="panel-header">
                    <h2>Problem Areas</h2>
                    <span>Automatic diagnostics</span>
                </div>

                <div class="problem-list">

                    <div class="problem-item danger">
                        <strong>Failed cases</strong>

                        <span>
                            <?= htmlspecialchars(
                                (string) ($stats['failed_cases'] ?? 0),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                            failed cases detected
                        </span>
                    </div>

                    <div class="problem-item warning">
                        <strong>Success rate</strong>

                        <span>
                            <?= htmlspecialchars(
                                (string) ($stats['success_rate'] ?? 0),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>%
                            overall process success
                        </span>
                    </div>

                    <div class="problem-item info">
                        <strong>Process variants</strong>

                        <span>
                            <?= htmlspecialchars(
                                (string) ($variants['total_variants'] ?? 0),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                            process variants detected
                        </span>
                    </div>

                    <div class="problem-item warning">
                        <strong>Process deviations</strong>

                        <span>
                            <?= htmlspecialchars(
                                (string) ($deviations['affected_cases'] ?? 0),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                            cases deviate from the main process
                        </span>
                    </div>

                    <?php if (($bottlenecks['bottleneck'] ?? null) !== null): ?>

                        <div class="problem-item warning">
                            <strong>Potential bottleneck</strong>

                            <span>
                                <?= htmlspecialchars(
                                    (string) (
                                        $bottlenecks['bottleneck']['from']
                                        ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                →

                                <?= htmlspecialchars(
                                    (string) (
                                        $bottlenecks['bottleneck']['to']
                                        ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </div>

                    <?php endif; ?>

                    <?php if (($sla['breach_count'] ?? 0) > 0): ?>

                        <div class="problem-item danger">
                            <strong>SLA breaches</strong>

                            <span>
                                <?= htmlspecialchars(
                                    (string) $sla['breach_count'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                                cases exceed SLA
                            </span>
                        </div>

                    <?php endif; ?>

                    <?php if (($cycles['affected_cases'] ?? 0) > 0): ?>

                        <div class="problem-item warning">
                            <strong>Rework detected</strong>

                            <span>
                                <?= htmlspecialchars(
                                    (string) $cycles['affected_cases'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                                cases contain repeated activities
                            </span>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </section>

    </main>

</div>

<script>
window.dashboardData = {
    successCases: <?= json_encode(
        $stats['success_cases'] ?? 0
    ) ?>,

    failedCases: <?= json_encode(
        $stats['failed_cases'] ?? 0
    ) ?>,

    eventLabels: <?= json_encode(
        $eventLabels,
        JSON_UNESCAPED_UNICODE
    ) ?>,

    eventValues: <?= json_encode(
        $eventValues
    ) ?>
};
</script>

<script src="assets/js/app.js"></script>

</body>
</html>
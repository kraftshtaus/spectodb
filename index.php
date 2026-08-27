<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use SpectoDB\Algorithms\Official\DirectlyFollowsAlgorithm;
use SpectoDB\Algorithms\Official\StatisticsAlgorithm;
use SpectoDB\Core\Database\Database;
use SpectoDB\Core\Database\DatabaseEventSource;
use SpectoDB\Algorithms\Official\ProcessVariantsAlgorithm;
use SpectoDB\Algorithms\Official\DeviationDetectionAlgorithm;

$config = require __DIR__ . '/config.php';

try {
    // Database connection
    $database = new Database($config['db']);
    $pdo = $database->getConnection();

    // Build EventLog from the configured database source
    $eventSource = new DatabaseEventSource(
        $pdo,
        $config['mapping']
    );

    $eventLog = $eventSource->load();

    // Directly-Follows Graph analysis
    $dfgAlgorithm = new DirectlyFollowsAlgorithm();
    $dfgResult = $dfgAlgorithm->analyze($eventLog);

    $transitions = $dfgResult->get('transitions', []);

    // Process statistics
    $statisticsAlgorithm = new StatisticsAlgorithm(
        $config['analysis']['success_events'] ?? [],
        $config['analysis']['failure_events'] ?? []
    );

    $statisticsResult = $statisticsAlgorithm->analyze($eventLog);

    $stats = $statisticsResult->getData();

    $variantsAlgorithm = new ProcessVariantsAlgorithm();
    $variantsResult = $variantsAlgorithm->analyze($eventLog);

    $variants = $variantsResult->getData();

    $deviationAlgorithm = new DeviationDetectionAlgorithm();
    $deviationResult = $deviationAlgorithm->analyze($eventLog);

    $deviations = $deviationResult->getData();

    // Dashboard chart data
    $eventLabels = array_keys($stats['event_counts']);
    $eventValues = array_values($stats['event_counts']);

} catch (Throwable $e) {
    die('Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SpectoDB - Process Analytics</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<div class="app-layout">

    <aside class="sidebar">

        <div class="brand">
            <div class="brand-icon">S</div>

            <div>
                <h2>SpectoDB</h2>
                <span>Process intelligence</span>
            </div>
        </div>

        <nav class="menu">
            <a class="active" href="#">Analytics</a>
            <a href="#">Transactions</a>
            <a href="#">Process Graph</a>
            <a href="#">SQL Queries</a>
            <a href="#">Problem Areas</a>
            <a href="#">Settings</a>
        </nav>

        <div class="user-box">
            <div class="avatar">A</div>

            <div>
                <strong>Admin</strong>
                <span>System user</span>
            </div>
        </div>

    </aside>

    <main class="main">

        <header class="topbar">

            <div>
                <h1>Analytics</h1>
                <p>Process mining and database analysis dashboard</p>
            </div>

            <div class="db-selector">
                <?= htmlspecialchars(
                    $config['db']['dbname'] ?? 'Database',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        </header>

        <section class="dashboard-grid">

            <!-- KEY INDICATORS -->

            <div class="panel indicators-panel">

                <div class="panel-header">
                    <h2>Key Indicators</h2>
                    <span>Current event log</span>
                </div>

                <div class="indicator-row">

                    <div class="circle-stat">
                        <div class="circle">
                            <?= $stats['total_cases'] ?>
                        </div>

                        <span>Total cases</span>
                    </div>

                    <div class="circle-stat">
                        <div class="circle">
                            <?= $stats['success_cases'] ?>
                        </div>

                        <span>Successful</span>
                    </div>

                    <div class="circle-stat">
                        <div class="circle">
                            <?= $stats['failed_cases'] ?>
                        </div>

                        <span>Failed</span>
                    </div>

                    <div class="circle-stat">
                        <div class="circle">
                            <?= $stats['success_rate'] ?>%
                        </div>

                        <span>Success rate</span>
                    </div>

                </div>

            </div>

            <!-- EVENT FREQUENCY -->

            <div class="panel">

                <div class="panel-header">
                    <h2>Event Frequency</h2>
                    <span>Event log activities</span>
                </div>

                <div class="chart-box">
                    <canvas id="eventChart"></canvas>
                </div>

            </div>

            <!-- SUCCESS / FAILURE -->

            <div class="panel wide-panel">

                <div class="panel-header">
                    <h2>Success / Failure</h2>
                    <span>Process result ratio</span>
                </div>

                <div class="chart-box large-chart">
                    <canvas id="successChart"></canvas>
                </div>

            </div>

            <!-- PROBLEM AREAS -->

            <div class="panel">

                <div class="panel-header">
                    <h2>Problem Areas</h2>
                    <span>Automatic diagnostics</span>
                </div>

                <div class="problem-list">

                    <div class="problem-item danger">
                        <strong>Failed cases</strong>

                        <span>
                            <?= $stats['failed_cases'] ?>
                            failed cases detected
                        </span>
                    </div>

                    <div class="problem-item warning">
                        <strong>Success rate</strong>

                        <span>
                            <?= $stats['success_rate'] ?>%
                            overall process success
                        </span>
                    </div>

                    <div class="problem-item info">
                        <strong>Process variants</strong>
                        <span>
                            <?= $variants['total_variants'] ?>
                            process variants detected
                        </span>
                    </div>

                    <div class="problem-item warning">
                        <strong>Process deviations</strong>
                        <span>
                        <?= $deviations['affected_cases'] ?>
                        cases deviate from the main process
                        </span>
                    </div>

                </div>

            </div>

        </section>

    </main>

</div>

<script>
    window.dashboardData = {
        successCases: <?= json_encode($stats['success_cases']) ?>,
        failedCases: <?= json_encode($stats['failed_cases']) ?>,
        eventLabels: <?= json_encode($eventLabels) ?>,
        eventValues: <?= json_encode($eventValues) ?>
    };
</script>

<script src="assets/js/app.js"></script>

</body>
</html>
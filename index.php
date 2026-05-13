<?php
declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/TransactionRepository.php';
require_once __DIR__ . '/src/ProcessAnalyzer.php';
require_once __DIR__ . '/src/StatisticsAnalyzer.php';
require_once __DIR__ . '/src/FlowchartGenerator.php';

$config = require __DIR__ . '/config.php';

try {
    $database = new Database($config['db']);
    $pdo = $database->getConnection();

    $repository = new TransactionRepository($pdo, $config['mapping']);
    $groupedTransactions = $repository->getGroupedByOrder();

    $processAnalyzer = new ProcessAnalyzer();
    $transitions = $processAnalyzer->buildTransitions($groupedTransactions);

    $statisticsAnalyzer = new StatisticsAnalyzer();
    $stats = $statisticsAnalyzer->calculate($groupedTransactions, $transitions);

    $eventLabels = array_keys($stats['event_counts']);
    $eventValues = array_values($stats['event_counts']);

} catch (Throwable $e) {
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SQL Transaction Analyzer - ASAP</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<div class="app-layout">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">S</div>
            <div>
                <h2>SQL Analyzer</h2>
                <span>Transaction monitoring</span>
            </div>
        </div>

        <nav class="menu">
            <a class="active" href="#">Analytics</a>
            <a href="#">Transactions</a>
            <a href="#">Flowchart</a>
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
                <p>SQL transaction process analysis dashboard</p>
            </div>

            <div class="db-selector">
                <?= htmlspecialchars($config['db']['database'] ?? 'Database', ENT_QUOTES, 'UTF-8') ?>
            </div>
        </header>

        <section class="dashboard-grid">

            <div class="panel indicators-panel">
                <div class="panel-header">
                    <h2>Key Indicators</h2>
                    <span>Current dataset</span>
                </div>

                <div class="indicator-row">
                    <div class="circle-stat">
                        <div class="circle"><?= $stats['total_cases'] ?></div>
                        <span>Total cases</span>
                    </div>

                    <div class="circle-stat">
                        <div class="circle"><?= $stats['success_cases'] ?></div>
                        <span>Successful</span>
                    </div>

                    <div class="circle-stat">
                        <div class="circle"><?= $stats['failed_cases'] ?></div>
                        <span>Failed</span>
                    </div>

                    <div class="circle-stat">
                        <div class="circle"><?= $stats['success_rate'] ?>%</div>
                        <span>Success rate</span>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2>Event Frequency</h2>
                    <span>Transaction events</span>
                </div>

                <div class="chart-box">
                    <canvas id="eventChart"></canvas>
                </div>
            </div>

            <div class="panel wide-panel">
                <div class="panel-header">
                    <h2>Success / Failure</h2>
                    <span>Process result ratio</span>
                </div>

                <div class="chart-box large-chart">
                    <canvas id="successChart"></canvas>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2>Problem Areas</h2>
                    <span>Automatic diagnostics</span>
                </div>

                <div class="problem-list">
                    <div class="problem-item danger">
                        <strong>Failed transactions</strong>
                        <span><?= $stats['failed_cases'] ?> failed cases detected</span>
                    </div>

                    <div class="problem-item warning">
                        <strong>Success rate</strong>
                        <span><?= $stats['success_rate'] ?>% overall process success</span>
                    </div>

                    <div class="problem-item info">
                        <strong>Event analysis</strong>
                        <span><?= count($stats['event_counts']) ?> unique events found</span>
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
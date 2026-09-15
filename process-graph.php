<?php

declare(strict_types=1);

use SpectoDB\Algorithms\Official\BottleneckDetectionAlgorithm;
use SpectoDB\Algorithms\Official\DeviationDetectionAlgorithm;
use SpectoDB\Algorithms\Official\DirectlyFollowsAlgorithm;
use SpectoDB\Algorithms\Official\FrequencyHeatmapAlgorithm;
use SpectoDB\Algorithms\Official\StartEndDiscoveryAlgorithm;
use SpectoDB\Core\Analysis\AlgorithmRegistry;
use SpectoDB\Core\Database\DatabaseEventSource;
use SpectoDB\Core\Database\DatabaseManager;
use SpectoDB\Visualization\ProcessGraphBuilder;

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
        'deviations',
        new DeviationDetectionAlgorithm()
    );

    $registry->register(
        'bottlenecks',
        new BottleneckDetectionAlgorithm()
    );

    $registry->register(
        'start_end',
        new StartEndDiscoveryAlgorithm()
    );

    $registry->register(
        'frequency_heatmap',
        new FrequencyHeatmapAlgorithm()
    );

    // Analysis
    $dfg = $registry
        ->get('directly_follows')
        ->analyze($eventLog)
        ->getData();

    $deviations = $registry
        ->get('deviations')
        ->analyze($eventLog)
        ->getData();

    $bottlenecks = $registry
        ->get('bottlenecks')
        ->analyze($eventLog)
        ->getData();

    $startEnd = $registry
        ->get('start_end')
        ->analyze($eventLog)
        ->getData();

    $heatmap = $registry
        ->get('frequency_heatmap')
        ->analyze($eventLog)
        ->getData();

    // Graph
    $graphBuilder = new ProcessGraphBuilder();

    $graph = $graphBuilder->build(
        $dfg['transitions'] ?? [],
        $heatmap,
        $bottlenecks,
        $startEnd,
        $deviations
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
$activePage = 'process-graph';

$pageTitle = 'Process Graph';

$pageSubtitle =
    'Interactive visualization of the discovered process';

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

    <title>SpectoDB - Process Graph</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/process-graph.css"
    >

    <script src="https://unpkg.com/cytoscape/dist/cytoscape.min.js"></script>

</head>

<body>

<div class="app-layout">

    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main graph-main">

        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <!-- Graph toolbar -->

        <section class="graph-toolbar">

            <div class="graph-summary">

                <div class="graph-stat">

                    <strong>
                        <?= count($graph['nodes'] ?? []) ?>
                    </strong>

                    <span>
                        Activities
                    </span>

                </div>

                <div class="graph-stat">

                    <strong>
                        <?= count($graph['edges'] ?? []) ?>
                    </strong>

                    <span>
                        Transitions
                    </span>

                </div>

                <div class="graph-stat">

                    <strong>
                        <?= htmlspecialchars(
                            (string) (
                                $deviations['affected_cases']
                                ?? 0
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>

                    <span>
                        Deviations
                    </span>

                </div>

            </div>

            <div class="graph-actions">

                <button
                    id="fitGraph"
                    type="button"
                >
                    Fit graph
                </button>

                <button
                    id="resetGraph"
                    type="button"
                >
                    Reset layout
                </button>

            </div>

        </section>

        <!-- Graph -->

        <section class="graph-workspace">

            <div class="graph-panel">

                <div id="processGraph"></div>

                <div class="graph-legend">

                    <div>
                        <span class="legend-dot normal"></span>
                        Normal
                    </div>

                    <div>
                        <span class="legend-dot start"></span>
                        Start
                    </div>

                    <div>
                        <span class="legend-dot end"></span>
                        End
                    </div>

                    <div>
                        <span class="legend-line deviation"></span>
                        Deviation
                    </div>

                    <div>
                        <span class="legend-line bottleneck"></span>
                        Bottleneck
                    </div>

                </div>

            </div>

            <!-- Inspector -->

            <aside class="details-panel">

                <div class="details-header">

                    <span class="details-label">
                        Inspector
                    </span>

                    <h2 id="detailsTitle">
                        Select an element
                    </h2>

                </div>

                <div
                    id="detailsContent"
                    class="details-content"
                >

                    <p>
                        Click an activity or transition on the graph
                        to inspect its analysis data.
                    </p>

                </div>

            </aside>

        </section>

    </main>

</div>

<script>

window.processGraphData = <?= json_encode(
    $graph,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
) ?>;

</script>

<script src="assets/js/process-graph.js"></script>

</body>

</html>
<?php

declare(strict_types=1);

session_start();

// Page
$activePage = 'database';

$pageTitle = 'Database Connection';

$pageSubtitle =
    'Connect and map an external event source';

$topbarRight = '
    <div
        id="connectionStatus"
        class="connection-status"
    >
        Not connected
    </div>
';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>SpectoDB - Database Connection</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/database.css"
    >
</head>

<body>

<div class="app-layout">

    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main database-main">

        <?php require __DIR__ . '/partials/topbar.php'; ?>

        <div
            id="databaseMessage"
            class="database-message"
            hidden
        ></div>

        <section class="database-layout">

            <!-- Connection -->

            <div class="database-card">

                <div class="database-card-header">

                    <div>
                        <span class="section-number">
                            01
                        </span>

                        <h2>Connection</h2>
                    </div>

                    <span class="card-description">
                        Select database engine
                    </span>

                </div>

                <div class="form-grid">

                    <label class="form-field">

                        <span>
                            Database type
                        </span>

                        <select id="driver">

                            <option value="mysql">
                                MySQL / MariaDB
                            </option>

                            <option value="pgsql">
                                PostgreSQL
                            </option>

                            <option value="sqlite">
                                SQLite
                            </option>

                        </select>

                    </label>

                    <div
                        id="networkFields"
                        class="network-fields"
                    >

                        <label class="form-field">

                            <span>Host</span>

                            <input
                                id="host"
                                value="127.0.0.1"
                            >

                        </label>

                        <label class="form-field">

                            <span>Port</span>

                            <input
                                id="port"
                                value="3306"
                            >

                        </label>

                        <label class="form-field">

                            <span>Database</span>

                            <input
                                id="dbname"
                                placeholder="Database name"
                            >

                        </label>

                        <label class="form-field">

                            <span>Username</span>

                            <input
                                id="username"
                                autocomplete="username"
                            >

                        </label>

                        <label class="form-field full-width">

                            <span>Password</span>

                            <input
                                id="password"
                                type="password"
                                autocomplete="current-password"
                            >

                        </label>

                    </div>

                    <label
                        id="sqliteField"
                        class="form-field full-width"
                        hidden
                    >

                        <span>
                            SQLite file path
                        </span>

                        <input
                            id="sqlitePath"
                            placeholder="C:\data\database.sqlite"
                        >

                    </label>

                </div>

                <div class="form-actions">

                    <button
                        id="testConnection"
                        class="button-secondary"
                        type="button"
                    >
                        Test connection
                    </button>

                    <button
                        id="loadTables"
                        class="button-primary"
                        type="button"
                        disabled
                    >
                        Continue
                    </button>

                </div>

            </div>

            <!-- Mapping -->

            <div
                id="mappingCard"
                class="database-card disabled-card"
            >

                <div class="database-card-header">

                    <div>

                        <span class="section-number">
                            02
                        </span>

                        <h2>
                            Event Log Mapping
                        </h2>

                    </div>

                    <span class="card-description">
                        Describe your process data
                    </span>

                </div>

                <div class="form-grid">

                    <label class="form-field full-width">

                        <span>
                            Source table
                        </span>

                        <select
                            id="table"
                            disabled
                        >

                            <option value="">
                                Select table
                            </option>

                        </select>

                    </label>

                    <label class="form-field">

                        <span>
                            Case ID
                        </span>

                        <select
                            id="caseId"
                            disabled
                        ></select>

                        <small>
                            Identifies one process instance
                        </small>

                    </label>

                    <label class="form-field">

                        <span>
                            Activity
                        </span>

                        <select
                            id="activity"
                            disabled
                        ></select>

                        <small>
                            Event or process step
                        </small>

                    </label>

                    <label class="form-field">

                        <span>
                            Timestamp
                        </span>

                        <select
                            id="timestamp"
                            disabled
                        ></select>

                        <small>
                            Determines event order
                        </small>

                    </label>

                    <label class="form-field">

                        <span>
                            Resource
                        </span>

                        <select
                            id="resource"
                            disabled
                        ></select>

                        <small>
                            Optional employee / system
                        </small>

                    </label>

                </div>

                <div
                    id="schemaPreview"
                    class="schema-preview"
                ></div>

                <div class="form-actions">

                    <button
                        id="saveMapping"
                        class="button-primary"
                        type="button"
                        disabled
                    >
                        Save & Analyze
                    </button>

                </div>

            </div>

        </section>

    </main>

</div>

<script src="assets/js/database.js"></script>

</body>

</html>
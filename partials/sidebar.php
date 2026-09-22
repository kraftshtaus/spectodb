<?php

declare(strict_types=1);

$activePage = $activePage ?? '';
?>

<aside class="sidebar">

    <div class="brand">

        <div class="brand-icon">
            S
        </div>

        <div>
            <h2>SpectoDB</h2>
            <span>Process intelligence</span>
        </div>

    </div>

    <nav class="menu">

<a href="index.php" class="<?= $activePage === 'overview' ? 'active' : '' ?>">
    Overview
</a>

<a href="process-graph.php" class="<?= $activePage === 'process-graph' ? 'active' : '' ?>">
    Process Graph
</a>

<a href="#" class="<?= $activePage === 'variants' ? 'active' : '' ?>" >
    Variants
</a>

<a href="diagnostics.php" class="<?= $activePage === 'diagnostics' ? 'active' : '' ?>" >
    Diagnostics
</a>

<a href="database.php" class="<?= $activePage === 'database' ? 'active' : '' ?>" >
    Database
</a>

        <a
            href="#"
            class="<?= $activePage === 'reports' ? 'active' : '' ?>"
        >
            Reports
        </a>

        <a
            href="#"
            class="<?= $activePage === 'settings' ? 'active' : '' ?>"
        >
            Settings
        </a>

    </nav>

    <div class="user-box">

        <div class="avatar">
            A
        </div>

        <div>
            <strong>Admin</strong>
            <span>SpectoDB</span>
        </div>

    </div>

</aside>
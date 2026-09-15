<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'SpectoDB';
$pageSubtitle = $pageSubtitle ?? '';
$topbarRight = $topbarRight ?? null;
?>

<header class="topbar">

    <div>

        <h1>
            <?= htmlspecialchars(
                $pageTitle,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h1>

        <?php if ($pageSubtitle !== ''): ?>

            <p>
                <?= htmlspecialchars(
                    $pageSubtitle,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        <?php endif; ?>

    </div>


    <?php if ($topbarRight !== null): ?>

        <div class="topbar-right">
            <?= $topbarRight ?>
        </div>

    <?php endif; ?>

</header>
<?php
// Used by dashboard.php and admin-dashboard.php as their shared page layout.
$isAdmin = $user['role'] === 'ADMIN';
require __DIR__ . '/page-head.php';
?>
<body id="page-top" data-role="<?= h($user['role']) ?>">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div id="wrapper">
        <button id="nav-backdrop" class="nav-backdrop" type="button" aria-label="Close navigation" hidden></button>
        <?php require __DIR__ . '/sidebar-navigation.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php require __DIR__ . '/top-navigation.php'; ?>
                <main id="main-content" class="container-fluid" tabindex="-1">
                    <div id="global-message" role="status" aria-live="polite" hidden>
                    </div>
                    <?php
                    require __DIR__ . '/inventory-overview.php';
                    require __DIR__ . '/equipment-section.php';
                    require __DIR__ . '/borrow-return-records.php';
                    if ($isAdmin) {
                        require __DIR__ . '/inventory-reports.php';
                    }
                    require __DIR__ . '/account-settings.php';
                    ?>
                </main>
            </div>
            <footer class="app-footer px-4 py-3">
                AviTON · All timestamps shown in your local time. No borrowing deadlines.
            </footer>
        </div>
    </div>
    <?php require __DIR__ . '/dashboard-dialogs.php'; ?>
</body>
</html>

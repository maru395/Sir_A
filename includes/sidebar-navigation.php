<?php
// Used by includes/dashboard-layout.php for the navigation on both dashboard pages.
$navigation = [
    ['overview', 'section-overview', 'Overview', 'fa-home'],
    ...($isAdmin ? [
        ['records', 'section-admin', 'Admin Monitor', 'fa-shield-alt'],
        ['equipment', 'section-equipment', 'Equipment Management', 'fa-boxes'],
        ['reports', 'section-reports', 'Reports & Joins', 'fa-chart-bar'],
    ] : [
        ['equipment', 'section-borrow', 'Borrow Item', 'fa-box-open'],
        ['records', 'section-mine', 'My Borrowed Items', 'fa-history'],
    ]),
    ['account', 'section-account', 'Account', 'fa-user-circle'],
];
?>
<aside id="sidebar" class="navbar-nav sidebar sidebar-dark accordion" aria-label="Application navigation">
    <a class="sidebar-brand d-flex align-items-center" href="<?= $isAdmin ? 'admin-dashboard.php' : 'dashboard.php' ?>" aria-label="AviTON overview">
        <i class="fas fa-plane fa-fw" aria-hidden="true"></i><span class="sidebar-brand-text brand-text">AviTON</span>
    </a>
    <button id="sidebar-close" class="btn btn-link sidebar-close" type="button" aria-label="Close menu"><i class="fas fa-times" aria-hidden="true"></i></button>
    <hr class="sidebar-divider my-0">
    <nav aria-label="Main menu" class="sidebar-nav-list">
        <ul class="list-unstyled mb-0">
            <?php foreach ($navigation as [$view, $target, $label, $icon]): ?>
            <li class="nav-item <?= $view === 'overview' ? 'active' : '' ?>">
                <button class="nav-link <?= $view === 'overview' ? 'active' : '' ?>" type="button"
                    data-view="<?= h($view) ?>" data-target="<?= h($target) ?>" data-title="<?= h($label) ?>"
                    aria-controls="<?= h($target) ?>" aria-label="<?= h($label) ?>" <?= $view === 'overview' ? 'aria-current="page"' : '' ?> title="<?= h($label) ?>">
                    <i class="fas <?= h($icon) ?> fa-fw" aria-hidden="true"></i><span class="nav-text"><?= h($label) ?></span>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <hr class="sidebar-divider">
        <button type="button" id="logout-btn" class="nav-link" aria-label="Sign out" title="Sign out">
            <i class="fas fa-sign-out-alt fa-fw" aria-hidden="true"></i><span class="nav-text">Logout</span>
        </button>
    </div>
</aside>

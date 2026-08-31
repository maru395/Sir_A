<?php
// Used by includes/dashboard-layout.php for the greeting and inventory summary on both dashboards.
?>
<section id="section-overview" class="page-section" aria-label="Overview">
    <h2 class="h4 text-gray-900 mb-4">
        Hello <?= h($user['username']) ?>
    </h2>
    <div class="row" aria-label="Inventory summary">
        <?php
        $stats = [
            ['primary', 'Total Items', 'stat-total-items', 'fa-cube', 'Individual units in inventory'],
            ['success', 'Available', 'stat-available', 'fa-check-circle', 'Ready for staff release'],
            [
                'warning',
                $isAdmin ? 'Currently Borrowed' : 'Your Borrowed Units',
                'stat-borrowed',
                'fa-exchange-alt',
                'Includes unconfirmed returns'
            ],
            [
                'danger',
                'Awaiting Confirmation',
                'stat-confirmations',
                'fa-clipboard-check',
                'Borrow requests and returns'
            ],
        ];
        foreach ($stats as [$color, $label, $id, $icon, $hint]): ?>
        <div class="col-xl-3 col-6 mb-4">
            <div class="card border-left-<?= h($color) ?> shadow-sm h-100 stat-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label text-<?= h($color) ?> text-uppercase mb-1">
                            <?= h($label) ?>
                        </div>
                        <strong class="h5 mb-0 font-weight-bold text-gray-900 d-block" id="<?= h($id) ?>">—</strong><span class="sr-only"><?= h($hint) ?></span>
                    </div>
                    <div class="icon-circle-bg text-<?= h($color) ?>">
                        <i class="fas <?= h($icon) ?>" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h2 class="h6 font-weight-bold mb-0">
                Inventory
            </h2>
            <button type="button" class="btn btn-sm btn-outline-secondary icon-button" data-refresh aria-label="Refresh inventory" title="Refresh inventory"><i class="fas fa-sync-alt" aria-hidden="true"></i></button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" tabindex="0" role="region" aria-label="Inventory table">
                <table class="table mb-0 overview-table">
                    <caption class="sr-only">
                        Inventory quantities and availability
                    </caption>
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">
                                Item
                            </th>
                            <th scope="col">
                                Category
                            </th>
                            <th scope="col">
                                Total Qty
                            </th>
                            <th scope="col">
                                Available
                            </th>
                            <th scope="col">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody id="overview-items-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="pagination-bar" id="overview-pagination">
    </div>
</section>

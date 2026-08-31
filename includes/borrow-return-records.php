<?php
// Used by includes/dashboard-layout.php for user records and the admin borrow/return monitor.
?>
<section id="<?= $isAdmin ? 'section-admin' : 'section-mine' ?>" class="page-section" aria-label="Borrowing records" hidden>
    <div class="section-intro">
        <h2 class="h6 font-weight-bold">
            <?= $isAdmin ? 'All Borrow / Return Records' : 'My Borrow / Return Records' ?>
        </h2>
        <p>
            <?= $isAdmin ? 'Confirm release after physical handover, and receipt after all units are returned.' : 'No borrowing deadline. Return all units on a record; an admin will confirm receipt.' ?>
        </p>
    </div>
    <div class="toolbar records-toolbar">
        <div class="queue-summary" aria-label="Pending confirmations">
            <span><strong id="stat-pending">—</strong> awaiting release</span><span><strong id="stat-returns">—</strong> awaiting receipt</span>
        </div>
        <div class="search-field">
            <label for="record-search" class="sr-only">Search records</label>
            <input
                class="form-control form-control-sm"
                id="record-search"
                type="search"
                maxlength="120"
                placeholder="<?= $isAdmin ? 'Search borrower or item…' : 'Search item name or code…' ?>"
            >
        </div>
        <div>
            <label for="record-status" class="sr-only">Status</label><select class="custom-select custom-select-sm" id="record-status"><option value="ALL">All records</option><option value="PENDING">Awaiting release</option><option value="BORROWED">On loan</option><option value="RETURN_PENDING">Awaiting receipt</option><option value="RETURNED">Returned</option><option value="REJECTED">Rejected</option><option value="CANCELLED">Cancelled</option></select>
        </div>
        <button type="button" id="refresh-btn" class="btn btn-sm btn-outline-secondary icon-button" data-refresh aria-label="Refresh records" title="Refresh records"><i class="fas fa-sync-alt" aria-hidden="true"></i></button>
    </div>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" tabindex="0" role="region" aria-label="Borrowing records table">
                <table class="table mb-0 records-table">
                    <caption class="sr-only">
                        Borrow requests, handovers and returns
                    </caption>
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">
                                Item
                            </th>
                            <?php if ($isAdmin): ?>
                            <th scope="col">
                                Borrowed By
                            </th>
                            <?php endif; ?>
                            <th scope="col">
                                Quantity
                            </th>
                            <th scope="col">
                                Time Borrowed
                            </th>
                            <th scope="col">
                                Time Returned
                            </th>
                            <th scope="col">
                                Status
                            </th>
                            <th scope="col">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody id="records-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="pagination-bar" id="records-pagination">
    </div>
</section>

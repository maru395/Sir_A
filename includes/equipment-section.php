<?php
// Used by includes/dashboard-layout.php for user borrowing and admin equipment management.
?>
<section id="<?= $isAdmin ? 'section-equipment' : 'section-borrow' ?>" class="page-section" aria-label="Equipment" hidden>
    <div class="section-intro">
        <h2 class="h6 font-weight-bold">
            <?= $isAdmin ? 'Equipment Catalog' : 'Available Equipment' ?>
        </h2>
        <p>
            <?= $isAdmin ? 'Manage equipment and total stock. Only equipment without borrowing records can be permanently deleted.' : 'Choose the quantity you need. Requests do not reserve stock; staff must confirm release.' ?>
        </p>
    </div>
    <div class="toolbar">
        <div class="search-field">
            <label for="equipment-search">Search equipment</label>
            <input
                id="equipment-search"
                class="form-control form-control-sm"
                type="search"
                maxlength="120"
                placeholder="Name, code or category"
            >
        </div>
        <?php if ($isAdmin): ?>
        <button class="btn btn-primary btn-sm" type="button" id="add-equipment"><i class="fas fa-plus mr-1" aria-hidden="true"></i>Add equipment</button>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-outline-secondary icon-button" data-refresh aria-label="Refresh equipment" title="Refresh equipment"><i class="fas fa-sync-alt" aria-hidden="true"></i></button>
    </div>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" tabindex="0" role="region" aria-label="Equipment table">
                <table class="table mb-0">
                    <caption class="sr-only">
                        Equipment availability and actions
                    </caption>
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">
                                Equipment
                            </th>
                            <th scope="col">
                                Category / location
                            </th>
                            <th scope="col">
                                Available / total
                            </th>
                            <th scope="col">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody id="equipment-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="pagination-bar" id="equipment-pagination">
    </div>
</section>

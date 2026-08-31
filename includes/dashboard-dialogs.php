<?php
// Used by includes/dashboard-layout.php for the action, equipment and history dialogs.
?>
<!-- Bootstrap 4 modals; submissions go through validation.php then save.php. -->
<div id="action-dialog" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="action-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="action-title" class="modal-title h5">
                    Confirm action
                </h2>
                <button class="close" type="button" data-close-dialog aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p id="action-description" class="small text-muted">
                </p>
                <form id="action-form" novalidate>
                    <input type="hidden" name="record_id">
                    <input type="hidden" name="equipment_id">
                    <input type="hidden" name="version">
                    <input type="hidden" name="request_token">
                    <div id="quantity-field">
                        <?php
                        field(
                            'quantity',
                            'Quantity',
                            'number',
                            true,
                            'Whole units only.',
                            'min="1" max="1000000" step="1" inputmode="numeric"'
                        );
                        ?>
                    </div>
                    <div id="note-field">
                        <?php
                        field(
                            'note',
                            'Note / reason',
                            'textarea',
                            false,
                            'A reason is required when rejecting a request.',
                            'maxlength="500"'
                        );
                        ?>
                    </div>
                    <span class="form-message" role="alert"></span><button id="action-submit" class="btn btn-primary" type="submit">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>
<div id="history-dialog" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="history-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="history-title" class="modal-title h5">
                    Transaction history
                </h2>
                <button class="close" type="button" data-close-dialog aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="history-content" aria-live="polite">
            </div>
        </div>
    </div>
</div>
<?php if ($isAdmin): ?>
<div id="equipment-dialog" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="equipment-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="equipment-title" class="modal-title h5">
                    Add equipment
                </h2>
                <button class="close" type="button" data-close-dialog aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="equipment-form" data-action="equipment_save" novalidate>
                    <input type="hidden" name="equipment_id" value="0">
                    <input type="hidden" name="version" value="0">
                    <div class="row">
                        <div class="col-md-6">
                            <?php
                            field('code', 'Equipment code', 'text', true, 'Unique code, for example ITM-007.', 'maxlength="30"');
                            ?>
                        </div>
                        <div class="col-md-6">
                            <?php
                            field('name', 'Equipment name', 'text', true, '', 'maxlength="120"');
                            ?>
                        </div>
                        <div class="col-md-6">
                            <?php
                            field('category', 'Category', 'text', true, '', 'maxlength="80"');
                            ?>
                        </div>
                        <div class="col-md-6">
                            <?php
                            field('location', 'Storage location', 'text', false, '', 'maxlength="120"');
                            ?>
                        </div>
                    </div>
                    <?php
                    field(
                        'total_quantity',
                        'Total quantity',
                        'number',
                        true,
                        'Available stock is calculated from total minus confirmed loans.',
                        'min="0" max="1000000" step="1"'
                    );
                    field('description', 'Description', 'textarea', false, '', 'maxlength="1000"');
                    ?>
                    <span class="form-message" role="alert"></span><button class="btn btn-primary" type="submit">Save equipment</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

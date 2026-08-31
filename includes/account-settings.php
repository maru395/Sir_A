<?php
// Used by includes/dashboard-layout.php for the account and password form on both dashboards.
?>
<section id="section-account" class="page-section" aria-label="Account" hidden>
    <div class="section-intro">
        <h2 class="h6 font-weight-bold">
            Your Account
        </h2>
        <p>
            <?= h($user['first_name'].' '.$user['last_name']) ?> · <?= h($user['email']) ?>
        </p>
    </div>
    <div class="card shadow-sm account-panel">
        <div class="card-header bg-white">
            <h3 class="h6 font-weight-bold mb-0">
                Change Password
            </h3>
        </div>
        <div class="card-body">
            <p class="small text-muted">
                Changing your password signs out all sessions.
            </p>
            <form id="password-form" data-action="change_password" novalidate>
                <?php
                field('current_password', 'Current password', 'password', true, '', 'autocomplete="current-password"');
                field(
                    'password',
                    'New password',
                    'password',
                    true,
                    'At least 12 characters; maximum 72 bytes.',
                    'autocomplete="new-password"'
                );
                field('confirm_password', 'Confirm new password', 'password', true, '', 'autocomplete="new-password"');
                ?>
                <span class="form-message" role="alert"></span><button class="btn btn-primary" type="submit">Change password</button>
            </form>
        </div>
    </div>
</section>

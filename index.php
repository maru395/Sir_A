<?php
require_once __DIR__ . '/includes/page-helpers.php';
$user = pageUser();
if ($user) {
    header('Location: ' . ($user['role'] === 'ADMIN' ? 'admin-dashboard.php' : 'dashboard.php'));
    exit;
}
$title = 'Sign in';
$script = 'auth.js';
require __DIR__ . '/includes/page-head.php';
?>
<body class="login-body">
    <main class="card login-card shadow-lg">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <i class="fas fa-plane auth-brand-icon text-primary" aria-hidden="true"></i>
                <h1 class="h4 font-weight-bold mt-3 mb-1">
                    AviTON
                </h1>
                <p class="small text-muted mb-0">
                    Aviation Equipment Inventory
                </p>
            </div>
            <h2 class="h6 text-center mb-4">
                Sign in to your account
            </h2>
            <form id="auth-form" data-action="login" novalidate>
                <?php
                field(
                    'username',
                    'Username',
                    'text',
                    true,
                    '',
                    'autocomplete="username" maxlength="40" placeholder="Enter username"'
                );
                field('password', 'Password', 'password', true, '', 'autocomplete="current-password" placeholder="Enter password"');
                ?>
                <span class="form-message" role="alert"></span><button class="btn btn-primary btn-block" type="submit">Sign in</button>
            </form>
            <p class="text-center small mt-4 mb-0">
                New user? <a href="register.php">Create an account</a>
            </p>
            <hr>
            <p class="small text-muted text-center mb-0">
                Staff use ADMIN accounts. To borrow, use a separate USER account.
            </p>
            <noscript>
                <p class="text-danger small mt-3">
                    Enable JavaScript for secure form validation and submission.
                </p>
            </noscript>
        </div>
    </main>
</body>
</html>

<?php
require_once __DIR__ . '/includes/page-helpers.php';
// These strings must match the inline handlers below so the browser will allow them.
$inlineHandlers = ["return validateFormBeforeSubmit('auth-form');", "resetRegistrationValidation('auth-form')"];
$hintIds = [
    'first_name' => 'firstNameHint',
    'last_name' => 'lastNameHint',
    'email' => 'emailHint',
    'mobile' => 'mobileHint',
    'username' => 'usernameHint',
    'password' => 'passwordHint',
    'confirm_password' => 'confirmPasswordHint'
];
foreach ($hintIds as $field => $hintId) {
    foreach (['format', 'availability'] as $mode) {
        $inlineHandlers[] = "checkField('$field', this.value, '$hintId', '$mode')";
    }
}
$user = pageUser(null, $inlineHandlers);
if ($user) {
    header('Location: ' . ($user['role'] === 'ADMIN' ? 'admin-dashboard.php' : 'dashboard.php'));
    exit;
}
$title = 'Create account';
$script = 'ajax.js';
require __DIR__ . '/includes/page-head.php';
?>
<body class="register-body">
    <main class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-11">
                <a class="registration-brand d-inline-flex align-items-center mb-4" href="index.php"><i class="fas fa-plane mr-2" aria-hidden="true"></i>AviTON</a>
                <section class="card register-card shadow-sm" aria-labelledby="auth-title">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
                        <h1 id="auth-title" class="h6 font-weight-bold mb-0">
                            Create a Borrower Account
                        </h1>
                        <a href="index.php" class="btn btn-light btn-sm">Back to sign in</a>
                    </div>
                    <div class="card-body p-4">
                        <p class="small text-muted">
                            Request equipment in the quantity you need. Staff confirm release and receipt. No borrowing deadlines.
                        </p>
                        <form
                            id="auth-form"
                            method="POST"
                            action="save.php"
                            data-action="register"
                            onsubmit="return validateFormBeforeSubmit('auth-form');"
                            onreset="resetRegistrationValidation('auth-form')"
                            novalidate
                        >
                            <input type="hidden" name="action" value="register">
                            <fieldset>
                                <legend class="h6 font-weight-bold text-primary">
                                    Borrower Information
                                </legend>
                                <div class="row">
                                    <div class="col-md-4 field">
                                        <label class="form-label" for="first_name">First name</label>
                                        <input
                                            class="form-control"
                                            id="first_name"
                                            name="first_name"
                                            type="text"
                                            autocomplete="given-name"
                                            maxlength="80"
                                            aria-describedby="firstNameHint"
                                            required
                                            oninput="checkField('first_name', this.value, 'firstNameHint', 'format')"
                                            onblur="checkField('first_name', this.value, 'firstNameHint', 'format')"
                                        >
                                        <span id="firstNameHint" class="field-error" data-error-for="first_name" aria-live="polite"></span>
                                    </div>
                                    <div class="col-md-4 field">
                                        <label class="form-label" for="last_name">Last name</label>
                                        <input
                                            class="form-control"
                                            id="last_name"
                                            name="last_name"
                                            type="text"
                                            autocomplete="family-name"
                                            maxlength="80"
                                            aria-describedby="lastNameHint"
                                            required
                                            oninput="checkField('last_name', this.value, 'lastNameHint', 'format')"
                                            onblur="checkField('last_name', this.value, 'lastNameHint', 'format')"
                                        >
                                        <span id="lastNameHint" class="field-error" data-error-for="last_name" aria-live="polite"></span>
                                    </div>
                                    <div class="col-md-4 field">
                                        <label class="form-label" for="email">Email address</label>
                                        <input
                                            class="form-control"
                                            id="email"
                                            name="email"
                                            type="email"
                                            autocomplete="email"
                                            maxlength="190"
                                            aria-describedby="emailHint"
                                            required
                                            oninput="checkField('email', this.value, 'emailHint', 'format')"
                                            onblur="checkField('email', this.value, 'emailHint', 'format')"
                                        >
                                        <span id="emailHint" class="field-error" data-error-for="email" aria-live="polite"></span>
                                    </div>
                                    <div class="col-md-4 field">
                                        <label class="form-label" for="mobile">Mobile number <span class="text-secondary">(optional)</span></label>
                                        <input
                                            class="form-control"
                                            id="mobile"
                                            name="mobile"
                                            type="tel"
                                            autocomplete="tel"
                                            maxlength="30"
                                            aria-describedby="mobileHint"
                                            oninput="checkField('mobile', this.value, 'mobileHint', 'format')"
                                            onblur="checkField('mobile', this.value, 'mobileHint', 'format')"
                                        >
                                        <span id="mobileHint" class="field-error" data-error-for="mobile" aria-live="polite"></span>
                                    </div>
                                </div>
                            </fieldset>
                            <fieldset class="mt-3">
                                <legend class="h6 font-weight-bold text-primary">
                                    Account Information
                                </legend>
                                <div class="row">
                                    <div class="col-md-4 field">
                                        <label class="form-label" for="username">Username</label>
                                        <input
                                            class="form-control"
                                            id="username"
                                            name="username"
                                            type="text"
                                            autocomplete="username"
                                            maxlength="40"
                                            aria-describedby="usernameHelp usernameHint"
                                            required
                                            oninput="checkField('username', this.value, 'usernameHint', 'format')"
                                            onblur="checkField('username', this.value, 'usernameHint', 'availability')"
                                        >
                                        <small id="usernameHelp" class="field-hint">Choose 3–40 characters, starting with a letter or number. No spaces.</small>
                            <span id="usernameHint" class="field-error" data-error-for="username" aria-live="polite"></span>
                                    </div>
                                    <div class="col-md-4 field">
                                        <label class="form-label" for="password">Password</label>
                                        <input
                                            class="form-control"
                                            id="password"
                                            name="password"
                                            type="password"
                                            autocomplete="new-password"
                                            aria-describedby="passwordHelp passwordHint"
                                            required
                                            oninput="checkField('password', this.value, 'passwordHint', 'format')"
                                            onblur="checkField('password', this.value, 'passwordHint', 'format')"
                                        >
                                        <small id="passwordHelp" class="field-hint">Use at least 12 characters. Try a few words that are easy for you to remember.</small>
                            <span id="passwordHint" class="field-error" data-error-for="password" aria-live="polite"></span>
                                    </div>
                                    <div class="col-md-4 field">
                                        <label class="form-label" for="confirm_password">Confirm password</label>
                                        <input
                                            class="form-control"
                                            id="confirm_password"
                                            name="confirm_password"
                                            type="password"
                                            autocomplete="new-password"
                                            aria-describedby="confirmPasswordHint"
                                            required
                                            oninput="checkField('confirm_password', this.value, 'confirmPasswordHint', 'format')"
                                            onblur="checkField('confirm_password', this.value, 'confirmPasswordHint', 'format')"
                                        >
                                        <span id="confirmPasswordHint" class="field-error" data-error-for="confirm_password" aria-live="polite"></span>
                                    </div>
                                </div>
                            </fieldset>
                            <span class="form-message" role="alert"></span>
                            <div class="d-flex align-items-center mt-3">
                                <button class="btn btn-primary mr-2" type="submit">Create account</button><button class="btn btn-outline-secondary" type="reset">Clear</button>
                            </div>
                        </form>
                        <noscript>
                            <p class="text-danger small mt-3">
                                Enable JavaScript for secure form validation and submission.
                            </p>
                        </noscript>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>

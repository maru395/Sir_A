<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/form-validation.php';
App::run(static function (): array {
    $body = App::body();
    $action = (string) ($body['action'] ?? '');
    if ($action === 'login') {
        App::throttle('login-all', 60, 900);
    }

    if ($action === 'register') {
        App::throttle('register', 10, 3600);
    }
    // Check again here: a request can reach save.php without using our form.
    $data = Validation::validate($body);
    $db = App::db();


    if ($action === 'register') {
        // Store a hash so a database leak does not reveal the password itself.
        $db->registerUser(
            $data['username'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['mobile']
        );
        return ['ok' => true, 'message' => 'Account created. You can now sign in.', 'redirect' => 'index.php'];
    }

    if ($action === 'login') {
        App::throttle('login:' . $data['username'], 10, 900);
        $user = $db->loginUser($data['username'])[0] ?? null;
        // Check a dummy hash for unknown usernames too, so failures take similar time.
        $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $verified = password_verify($data['password'], $hash);
        if (!$verified || !$user || !(int) $user['is_active']) {
            throw new HttpError(422, 'Invalid username or password.', ['password' => 'Check your username and password.']);
        }
        App::clearSession();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['session_version'] = (int) $user['session_version'];
        $_SESSION['signed_in'] = time();
        $_SESSION['last_seen'] = time();
        return ['ok' => true, 'redirect' => $user['role'] === 'ADMIN' ? 'admin-dashboard.php' : 'dashboard.php'];
    }

    if ($action === 'logout') {
        App::clearSession();
        return ['ok' => true, 'redirect' => 'index.php'];
    }

    if ($action === 'change_password') {
        App::throttle('password:' . $data['actor'], 10, 900);
        $user = App::user();
        $credentials = $db->loginUser($user['username'])[0];
        if (!password_verify($data['current_password'], $credentials['password_hash'])) {
            throw new HttpError(422, 'Current password is incorrect.', ['current_password' => 'Enter the correct current password.']);
        }
        $db->changePassword($data['actor'], $credentials['password_hash'], password_hash($data['password'], PASSWORD_DEFAULT));
        App::clearSession();
        return ['ok' => true, 'message' => 'Password changed. Sign in again.', 'redirect' => 'index.php'];
    }

    if ($action === 'equipment_save') {
        $rows = $db->saveEquipment(
            $data['actor'],
            $data['equipment_id'],
            $data['version'],
            $data['code'],
            $data['name'],
            $data['category'],
            $data['location'],
            $data['description'],
            $data['total_quantity']
        );
        return ['ok' => true, 'message' => 'Equipment saved.', 'id' => $rows[0]['id']];
    }

    if ($action === 'equipment_delete') {
        $db->deleteEquipment($data['actor'], $data['equipment_id'], $data['version']);
        return ['ok' => true, 'message' => 'Equipment permanently deleted.'];
    }

    if ($action === 'borrow_request') {
        $rows = $db->requestBorrow($data['actor'], $data['equipment_id'], $data['quantity'], $data['request_token'], $data['note']);
        return [
            'ok' => true,
            'message' => 'Borrow request recorded. Wait for staff to confirm the handover.',
            'id' => $rows[0]['id']
        ];
    }
    // The procedure updates the record and stock together, or changes neither.
    $db->transitionRecord($data['actor'], $data['record_id'], $action, $data['note']);
    $messages = [
        'approve_borrow' => 'Handover confirmed. Stock has been updated.',
        'reject_borrow' => 'Borrow request rejected.',
        'cancel_request' => 'Request cancelled.',
        'request_return' => 'Return submitted. Stock changes only after admin confirmation.',
        'confirm_return' => 'Return received and confirmed. Stock has been restored.',
        'reject_return' => 'Return rejected. The loan remains active.'
    ];
    return ['ok' => true, 'message' => $messages[$action]];
});

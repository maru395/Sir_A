<?php
// Used by validation.php, validate_registration.php and save.php to check form input.
declare(strict_types=1);
require_once __DIR__ . '/request-helpers.php';

final class Validation
{
    private array $errors = [];
    private array $data;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    private function text(string $key, int $max, bool $required = true): string
    {
        // Ignore extra spaces around normal text fields.
        $text = trim((string) ($this->data[$key] ?? ''));
        if (
            ($required && $text === '')
            || mb_strlen($text) > $max
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $text)
        ) {
            $this->errors[$key] = $required ? "Enter a value of 1 to $max characters." : "Use no more than $max characters.";
        }
        return $text;
    }

    private function integer(string $key, int $minimum = 1, int $maximum = 1000000): int
    {
        // Quantities and IDs must be whole numbers, not decimals or scientific notation.
        $raw = (string) ($this->data[$key] ?? '');
        if (
            !preg_match('/^(0|[1-9][0-9]*)$/D', $raw)
            || strlen($raw) > 10
            || (float) $raw < $minimum
            || (float) $raw > $maximum
        ) {
            $this->errors[$key] = "Enter a whole number from $minimum to $maximum.";
            return 0;
        }
        return (int) $raw;
    }

    private function password(string $key): string
    {
        // Spaces may be part of a password, so leave it exactly as typed.
        $password = (string) ($this->data[$key] ?? '');
        // Bcrypt only uses the first 72 bytes; reject longer passwords instead of cutting them off.
        if (mb_strlen($password) < 12) {
            $this->errors[$key] = 'Please use at least 12 characters. A few words together can be easier to remember.';
        } elseif (strlen($password) > 72) {
            $this->errors[$key] = 'This password is too long. Try a shorter one; emojis and accented letters take more space.';
        } elseif (strpos($password, "\0") !== false) {
            $this->errors[$key] = 'This password contains an unsupported character. Please type it again.';
        }
        if ($password !== (string) ($this->data['confirm_password'] ?? '')) {
            $this->errors['confirm_password'] = 'Your passwords do not match. Type the same password in both fields.';
        }
        return $password;
    }

    private function check(): void
    {
        if ($this->errors) {
            throw new HttpError(422, 'Please check the messages beside the highlighted fields and try again.', $this->errors);
        }
    }

    // Both live field checks and final saving use these same rules.
    private function registrationValue(string $field): string
    {
        $raw = trim((string) ($this->data[$field] ?? ''));
        if ($field === 'username') {
            $value = mb_strtolower($this->text($field, 40));
            if ($value === '') {
                $this->errors[$field] = 'Please choose a username for signing in.';
            } elseif (mb_strlen($value) < 3 || mb_strlen($value) > 40) {
                $this->errors[$field] = 'Choose a username with 3 to 40 characters.';
            } elseif (!preg_match('/^[a-z0-9][a-z0-9_.-]{2,39}$/D', $value)) {
                $this->errors[$field] = 'Start with a letter or number. Use only English letters, numbers, dots (.), underscores (_) or hyphens (-), with no spaces.';
            }
        } elseif (in_array($field, ['first_name', 'last_name'], true)) {
            $value = preg_replace('/\s+/u', ' ', $this->text($field, 80));
            $label = $field === 'first_name' ? 'first name' : 'last name';
            if ($value === '') {
                $this->errors[$field] = "Please enter your $label.";
            } elseif (mb_strlen($raw) > 80) {
                $this->errors[$field] = "Please keep your $label to 80 characters or fewer.";
            } elseif (isset($this->errors[$field]) || !preg_match("/^[\p{L}\p{M}][\p{L}\p{M} .’'\-]*$/uD", $value)) {
                $this->errors[$field] = "Start your $label with a letter. You can also use spaces, apostrophes, hyphens and periods.";
            }
        } elseif ($field === 'email') {
            $value = mb_strtolower($this->text($field, 190));
            if ($value === '') {
                $this->errors[$field] = 'Please enter your email address.';
            } elseif (mb_strlen($raw) > 190) {
                $this->errors[$field] = 'Please use an email address with 190 characters or fewer.';
            } elseif (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = 'Please enter an email address like name@example.com.';
            }
        } elseif ($field === 'mobile') {
            $value = preg_replace('/[\s()\-]/', '', $this->text($field, 30, false));
            if (isset($this->errors[$field]) || ($value !== '' && !preg_match('/^\+?[0-9]{7,15}$/D', $value))) {
                $this->errors[$field] = 'Enter a number like 09171234567 or +639171234567, using 7 to 15 digits. You can also leave this blank.';
            }
        } elseif ($field === 'password') {
            $value = $this->password('password');
        } else {
            $value = (string) ($this->data['confirm_password'] ?? '');
            if ($value === '') {
                $this->errors['confirm_password'] = 'Please re-enter your password to confirm it.';
            } elseif ($value !== (string) ($this->data['password'] ?? '')) {
                $this->errors['confirm_password'] = 'Your passwords do not match. Type the same password in both fields.';
            }
        }
        return $value;
    }

    public static function validateRegistrationField(array $input): void
    {
        $field = $input['field'] ?? '';
        $mode = $input['mode'] ?? 'format';
        if (
            !in_array(
                $field,
                ['first_name', 'last_name', 'email', 'mobile', 'username', 'password', 'confirm_password'],
                true
            )
            || !in_array($mode, ['format', 'availability'], true)
        ) {
            throw new HttpError(400, 'Invalid validation request.');
        }
        $data = [$field => (string) ($input['value'] ?? '')];
        if ($field === 'password') {
            $data['confirm_password'] = $data['password'];
        }
        if ($field === 'confirm_password') {
            $data['password'] = (string) ($input['password'] ?? '');
        }
        $validator = new self($data);
        $value = $validator->registrationValue($field);
        $validator->check();
        if ($field === 'username' && $mode === 'availability' && App::db()->usernameExists($value)) {
            throw new HttpError(422, 'That username is already in use.', ['username' => 'That username is already in use. Please try a different one.']);
        }
    }

    public static function validate(array $input): array
    {
        $validator = new self($input);
        $action = (string) ($input['action'] ?? '');
        $data = ['action' => $action];
        if (in_array($action, ['register', 'login'], true)) {
            $data['username'] = $validator->registrationValue('username');
            if ($action === 'login') {
                $data['password'] = (string) ($input['password'] ?? '');
                if ($data['password'] === '' || strlen($data['password']) > 72 || strpos($data['password'], "\0") !== false) {
                    $validator->errors['password'] = 'Enter your password.';
                }
            } else {
                foreach (['first_name', 'last_name', 'email', 'mobile', 'password', 'confirm_password'] as $field) {
                    $data[$field] = $validator->registrationValue($field);
                }
            }
            $validator->check();
            if ($action === 'register' && App::db()->usernameExists($data['username'])) {
                throw new HttpError(422, 'That username is already in use.', ['username' => 'That username is already in use. Please try a different one.']);
            }
            return $data;
        }
        $user = App::user();
        // Use the signed-in user, never an ID sent by the form.
        $data['actor'] = (int) $user['id'];
        if ($action === 'logout') {
            return $data;
        }
        if ($action === 'change_password') {
            $data['current_password'] = (string) ($input['current_password'] ?? '');
            $data['password'] = $validator->password('password');
            if ($data['current_password'] === '' || strlen($data['current_password']) > 72) {
                $validator->errors['current_password'] = 'Enter your current password.';
            }
        } elseif ($action === 'equipment_save') {
            App::role('ADMIN');
            $data['equipment_id'] = $validator->integer('equipment_id', 0, 2147483647);
            $data['version'] = $validator->integer('version', 0, 2147483647);
            $data['code'] = strtoupper($validator->text('code', 30));
            if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{1,29}$/D', $data['code'])) {
                $validator->errors['code'] = 'Use 2–30 letters, numbers, hyphens or underscores.';
            }
            $data['name'] = $validator->text('name', 120);
            $data['category'] = $validator->text('category', 80);
            $data['location'] = $validator->text('location', 120, false);
            $data['description'] = $validator->text('description', 1000, false);
            $data['total_quantity'] = $validator->integer('total_quantity', 0);
            $validator->check();
            if (App::db()->equipmentCodeExists($data['actor'], $data['code'], $data['equipment_id'])) {
                $validator->errors['code'] = 'This equipment code already exists.';
            }
            if ($data['equipment_id']) {
                $item = App::db()->getEquipment($data['actor'], $data['equipment_id'])[0] ?? null;
                if (!$item) {
                    throw new HttpError(404, 'Equipment not found.');
                }
                if ($data['total_quantity'] < (int) $item['total_quantity'] - (int) $item['available_quantity']) {
                    $validator->errors['total_quantity'] = 'Total cannot be below the units currently on loan.';
                }
                // Stop an older form from overwriting someone else's edit.
                if ((int) $item['version'] !== $data['version']) {
                    throw new HttpError(409, 'Equipment changed. Reload it before saving.');
                }
            }
        } elseif ($action === 'equipment_delete') {
            App::role('ADMIN');
            $data['equipment_id'] = $validator->integer('equipment_id', 1, 2147483647);
            $data['version'] = $validator->integer('version', 1, 2147483647);
            $validator->check();
            $item = App::db()->getEquipment($data['actor'], $data['equipment_id'])[0] ?? null;
            if (!$item) {
                throw new HttpError(404, 'Equipment no longer exists. Refresh the catalog.');
            }
            if ((int) $item['version'] !== $data['version']) {
                throw new HttpError(409, 'Equipment changed. Refresh before deleting.');
            }
            // Deleting this item would also break the meaning of its past loans.
            if ((int) $item['has_records']) {
                throw new HttpError(422, 'Cannot delete equipment with borrowing records. Transaction history must be preserved.');
            }
            if ((int) $item['available_quantity'] !== (int) $item['total_quantity']) {
                throw new HttpError(422, 'Cannot delete equipment while units are on loan.');
            }
        } elseif ($action === 'borrow_request') {
            App::role('USER');
            $data['equipment_id'] = $validator->integer('equipment_id', 1, 2147483647);
            $data['quantity'] = $validator->integer('quantity');
            $data['note'] = $validator->text('note', 500, false);
            $data['request_token'] = $validator->text('request_token', 32);
            if (!preg_match('/^[a-f0-9]{32}$/D', $data['request_token'])) {
                $validator->errors['request_token'] = 'Reload the form before submitting.';
            }
            // The procedure checks stock and repeated submissions together while the rows are locked.
        } elseif (
            in_array(
                $action,
                [
                    'approve_borrow',
                    'reject_borrow',
                    'cancel_request',
                    'request_return',
                    'confirm_return',
                    'reject_return'
                ],
                true
            )
        ) {
            App::role(in_array($action, ['cancel_request', 'request_return'], true) ? 'USER' : 'ADMIN');
            $data['record_id'] = $validator->integer('record_id', 1, 2147483647);
            $data['note'] = $validator->text('note', 500, in_array($action, ['reject_borrow', 'reject_return'], true));
            $validator->check();
            $record = App::db()->getRecord($data['actor'], $data['record_id'])[0] ?? null;
            if (!$record) {
                throw new HttpError(403, 'Record not accessible.');
            }
        } else {
            throw new HttpError(400, 'Unknown action.');
        }
        $validator->check();
        return $data;
    }
}

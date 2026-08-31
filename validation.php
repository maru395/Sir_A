<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/form-validation.php';
App::run(static function (): array {
    $data = Validation::validate(App::body());
    if ($data['action'] === 'borrow_request') {
        // A retry should reuse its original request, even if stock has changed since then.
        $existing = App::db()->findExistingRequest($data['actor'], $data['request_token'])[0] ?? null;
        if ($existing) {
            if (
                (int) $existing['equipment_id'] !== $data['equipment_id']
                || (int) $existing['quantity'] !== $data['quantity']
                || $existing['note'] !== $data['note']
            ) {
                throw new HttpError(409, 'Request token was already used for different details. Check your records.');
            }
            return ['ok' => true, 'message' => 'Existing request verified.'];
        }
        $item = App::db()->getEquipment($data['actor'], $data['equipment_id'])[0] ?? null;
        if (!$item) {
            throw new HttpError(422, 'Equipment is not available.', ['equipment_id' => 'Choose active equipment.']);
        }
        if ($data['quantity'] > (int) $item['available_quantity']) {
            throw new HttpError(
                422,
                'Not enough stock.',
                ['quantity' => 'Only ' . $item['available_quantity'] . ' units are currently available.']
            );
        }
    }
    return ['ok' => true, 'message' => 'Validation passed.'];
});

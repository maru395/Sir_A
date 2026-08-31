<?php
// Used by index.php, register.php, dashboard.php and admin-dashboard.php.
// Checks page access and provides the shared form helpers.
declare(strict_types=1);
require_once __DIR__ . '/request-helpers.php';
function pageUser(?string $requiredRole = null, array $inlineHandlers = []): ?array
{
    try {
        App::boot($inlineHandlers);
        $user = App::user(false);
        if ($requiredRole && !$user) {
            header('Location: index.php');
            exit;
        }
        if ($requiredRole && $user['role'] !== $requiredRole) {
            header('Location: ' . ($user['role'] === 'ADMIN' ? 'admin-dashboard.php' : 'dashboard.php'));
            exit;
        }
        return $user;
    } catch (Throwable) {
        http_response_code(503);
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>AviTON setup</title><body><h1>AviTON is temporarily unavailable</h1><p>Check the database setup or contact the administrator. No changes have been saved.</p></body></html>';
        exit;
    }
}
function field(
    string $name,
    string $label,
    string $type = 'text',
    bool $required = true,
    string $hint = '',
    string $attributes = ''
): void
{
    static $count = 0;
    $id = 'field-' . (++$count);
    $errorId = $id . '-error';
    // Match each label and error span to one input, including inside modals.
    echo '<div class="field"><label class="form-label" for="' . $id . '">'
        . h($label)
        . ($required ? '' : ' <span class="text-secondary">(optional)</span>')
        . '</label>';

    if ($type === 'textarea') {
        echo '<textarea class="form-control" name="' . h($name)
            . '" id="' . $id . '" rows="3" aria-describedby="' . $errorId
            . '" ' . $attributes . '></textarea>';
    } else {
        echo '<input class="form-control" type="' . h($type)
            . '" name="' . h($name) . '" id="' . $id
            . '" aria-describedby="' . $errorId . '" '
            . ($required ? 'required ' : '') . $attributes . '>';
    }

    if ($hint) {
        echo '<small class="field-hint">' . h($hint) . '</small>';
    }
    echo '<span class="field-error" id="' . $errorId
        . '" data-error-for="' . h($name) . '" aria-live="polite"></span></div>';
}

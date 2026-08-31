<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/form-validation.php';

// A short text reply is enough to show an error beside one field.
try {
    App::boot();
    header('Content-Type: text/plain; charset=utf-8');
    Validation::validateRegistrationField(App::body());
    echo 'VALID';
} catch (HttpError $error) {
    http_response_code($error->status === 422 ? 200 : $error->status);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'INVALID|' . ($error->errors ? reset($error->errors) : $error->getMessage());
} catch (Throwable) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'INVALID|We cannot check this right now. Please try again in a moment.';
}

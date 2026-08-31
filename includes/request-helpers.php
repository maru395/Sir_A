<?php
// Used by read.php, includes/page-helpers.php, includes/form-validation.php and includes/setup-access.php.
// Provides the shared database connection, sessions and request checks.
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';

final class HttpError extends RuntimeException
{
    public int $status;
    public array $errors;


    public function __construct(int $status, string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->status = $status;
        $this->errors = $errors;
    }
}

final class App
{
    // Keep this ID unchanged so existing sessions, attempt counts and setup locks still work.
    public const STORAGE_ID = 'localhost3306inventorydb';

    private static ?Config $database = null;


    public static function db(): Config
    {
        // Reuse the connection while handling this request.
        if (self::$database === null) {
            self::$database = new Config();
        }
        return self::$database;
    }

    public static function boot(array $inlineHandlers = []): void
    {
        date_default_timezone_set('UTC');
        ini_set('display_errors', '0');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        // Allow the registration handlers without allowing every inline script.
        $attributes = "'none'";
        if ($inlineHandlers) {
            $hashes = [];
            foreach (array_unique($inlineHandlers) as $handler) {
                $hashes[] = "'sha256-" . base64_encode(hash('sha256', $handler, true)) . "'";
            }
            $attributes = "'unsafe-hashes' " . implode(' ', $hashes);
        }
        header(
            "Content-Security-Policy: default-src 'self'; script-src 'self'; script-src-attr $attributes; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
        );
        header('Cache-Control: no-store, private');
        $secure = !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off';
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        $namespace = substr(hash('sha256', dirname(__DIR__) . self::STORAGE_ID), 0, 12);
        // Use PHP's session directory configured by XAMPP, outside the website.
        session_name('AVITON_' . $namespace);
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
        if (!session_start()) {
            throw new RuntimeException('Cannot establish a secure session.');
        }
        // Expire idle sign-ins after 30 minutes, and all sign-ins after 12 hours.
        $now = time();
        if (
            isset($_SESSION['user_id'])
            && ($now - ($_SESSION['last_seen'] ?? 0) > 1800 || $now - ($_SESSION['signed_in'] ?? 0) > 43200)
        ) {
            self::clearSession();
        }
        if (isset($_SESSION['user_id'])) {
            $_SESSION['last_seen'] = $now;
        }
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }

    public static function clearSession(): void
    {
        $_SESSION = [];
        // An old session ID must not work after signing in or signing out.
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    public static function user(bool $required = true): ?array
    {
        $user = null;
        if (isset($_SESSION['user_id'])) {
            $user = self::db()->getUser((int) $_SESSION['user_id'])[0] ?? null;
        }
        // Changing a password also signs out the user's other sessions.
        if ($user && (int) $user['session_version'] !== (int) ($_SESSION['session_version'] ?? 0)) {
            self::clearSession();
            $user = null;
        }
        if (!$user && $required) {
            throw new HttpError(401, 'Please sign in to continue.');
        }
        return $user;
    }

    public static function role(string $role): array
    {
        // Hiding an admin button is not enough; check every request too.
        $user = self::user();
        if ($user['role'] !== $role) {
            throw new HttpError(403, 'This action is not permitted for your account.');
        }
        return $user;
    }

    public static function csrf(): void
    {
        // Reject form requests that did not come from this browser session.
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
            throw new HttpError(403, 'Your security token expired. Reload the page and try again.');
        }
    }

    public static function body(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new HttpError(405, 'Use POST for this request.');
        }
        self::csrf();
        if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) {
            throw new HttpError(413, 'Request is too large.');
        }
        if (
            strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/x-www-form-urlencoded'
        ) {
            throw new HttpError(415, 'Send standard form data.');
        }
        $data = $_POST;
        foreach ($data as $value) {
            if (!is_string($value) && !is_int($value) && $value !== null) {
                throw new HttpError(422, 'Fields must contain text or whole numbers.');
            }
        }
        return $data;
    }

    public static function throttle(string $scope, int $limit, int $window): void
    {
        // Keep the attempt count on the server so clearing cookies cannot reset it.
        $namespace = substr(hash('sha256', self::STORAGE_ID), 0, 20);
        $bucket = hash('sha256', $scope . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'local'));
        $file = fopen(self::temporaryDirectory() . '/aviton-limit-' . $namespace . '-' . $bucket . '.dat', 'c+');
        // Lock the file so simultaneous attempts cannot overwrite each other.
        if (!$file || !flock($file, LOCK_EX)) {
            throw new HttpError(503, 'Sign-in protection is temporarily unavailable.');
        }
        try {
            $values = explode(' ', trim(stream_get_contents($file)));
            $state = ['attempts' => (int) ($values[0] ?? 0), 'expires' => (int) ($values[1] ?? 0)];
            $now = time();
            if (($state['expires'] ?? 0) <= $now) {
                $state = ['attempts' => 0, 'expires' => $now + $window];
            }
            if ($state['attempts'] >= $limit) {
                throw new HttpError(429, 'Too many attempts. Please try again later.');
            }
            $state['attempts']++;
            rewind($file);
            ftruncate($file, 0);
            if (fwrite($file, $state['attempts'] . ' ' . $state['expires']) === false || !fflush($file)) {
                throw new HttpError(503, 'Sign-in protection is temporarily unavailable.');
            }
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    // XML keeps responses structured without adding another data format to the project.
    public static function respond(array $body, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?><response>';
        echo self::xmlValue($body + ['csrf' => $_SESSION['csrf'] ?? '']);
        echo '</response>';
    }

    private static function xmlValue($value): string
    {
        if (is_array($value)) {
            $type = array_is_list_compat($value) ? 'list' : 'map';
            $xml = '<value type="' . $type . '">';
            foreach ($value as $key => $item) {
                $xml .= '<entry key="' . self::xmlText((string) $key) . '">' . self::xmlValue($item) . '</entry>';
            }
            return $xml . '</value>';
        }
        if ($value === null) {
            $type = 'null';
        } elseif (is_bool($value)) {
            $type = 'boolean';
        } elseif (is_int($value) || is_float($value)) {
            $type = 'number';
        } else {
            $type = 'text';
        }
        $text = (string) $value;
        if (is_bool($value)) {
            $text = $value ? 'true' : 'false';
        }
        return '<value type="' . $type . '">' . self::xmlText($text) . '</value>';
    }

    private static function xmlText(string $text): string
    {
        $text = mb_scrub($text, 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x{FFFE}\x{FFFF}]/u', '', $text);
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function temporaryDirectory(): string
    {
        // PHP allows a depth/mode prefix before the actual session path.
        $parts = explode(';', session_save_path());
        $directory = end($parts) ?: sys_get_temp_dir();
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException('PHP temporary directory is unavailable.');
        }
        return rtrim($directory, '/\\');
    }

    public static function run(callable $handler): void
    {
        try {
            self::boot();
            self::respond($handler());
        }
        catch (HttpError $error) {
            self::respond(['ok' => false, 'message' => $error->getMessage(), 'errors' => $error->errors], $error->status);
        }
        catch (PDOException $error) {
            if (($error->errorInfo[0] ?? '') === '45000') {
                $parts = explode('|', $error->errorInfo[2] ?? '', 2);
                $field = $parts[0];
                $message = $parts[1] ?? 'Request cannot be completed.';
                // Procedures send field|message so the form can show the right error.
                $status = match ($field) {
                    'forbidden' => 403,
                    'conflict' => 409,
                    default => 422,
                };
                self::respond(['ok' => false, 'message' => $message, 'errors' => [$field => $message]], $status);
            } elseif ((int) ($error->errorInfo[1] ?? 0) === 1062) {
                self::respond(
                    ['ok' => false, 'message' => 'That username or equipment code already exists. Refresh and try again.'],
                    409
                );
            } elseif (in_array((int) ($error->errorInfo[1] ?? 0), [1205, 1213], true)) {
                self::respond(['ok' => false, 'message' => 'Another request is changing this item. Please retry.'], 409);
            } else {
                // Never send database credentials or raw database errors to the page.
                $code = (int) ($error->errorInfo[1] ?? 0);
                $message = in_array($code, [2002, 2003], true)
                        ? 'Cannot reach MySQL. Start MySQL in XAMPP and check the port in config/config.php.'
                        : 'Database setup needs attention. On the server computer, open database/createdb.php to check the connection and install the schema.';
                self::respond(['ok' => false, 'message' => $message], 503);
            }
        } catch (Throwable) {
            self::respond(['ok' => false, 'message' => 'Unable to complete this request. Please try again.'], 500);
        }
    }
}

// Compatibility with the project's PHP 8.0 installation.
function array_is_list_compat(array $value): bool
{
    return $value === []
    || array_keys($value) === range(0, count($value) - 1);
}
// Show saved text as text, even if it contains HTML characters.
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

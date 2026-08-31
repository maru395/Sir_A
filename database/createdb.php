<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/setup-access.php';

$host = "localhost";
$dbname = "inventorydb";
$username = "root";
$password = "";

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // Connect to MySQL first because the database might not exist yet.
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $username, $password, $options);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `inventorydb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, $options);

    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Missing schema.sql.');
    }
    $delimiter = ';';
    $statement = '';

    // A procedure contains semicolons, so wait for its closing delimiter.
    foreach (preg_split('/\R/', $schema) as $line) {
        if (trim($line) === '' || str_starts_with(trim($line), '--')) {
            continue;
        }
        if (preg_match('/^DELIMITER\s+(\S+)\s*$/', $line, $match)) {
            if (trim($statement) !== '') {
                throw new RuntimeException('Invalid schema delimiter.');
            }
            $delimiter = $match[1];
            continue;
        }
        $statement .= $line . "\n";
        $sql = rtrim($statement);
        if (str_ends_with($sql, $delimiter)) {
            $pdo->exec(substr($sql, 0, -strlen($delimiter)));
            $statement = '';
        }
    }
    if (trim($statement) !== '') {
        throw new RuntimeException('Incomplete schema statement.');
    }
    $message = 'Database, tables and stored procedures are ready. Existing rows were kept. Open populatedb.php next.';
} catch (PDOException $e) {
    http_response_code(503);
    $message = 'Database setup failed. Check that MySQL is running and the local connection settings are correct. Existing rows were not deleted.';
} catch (RuntimeException $e) {
    http_response_code(500);
    $message = $e->getMessage();
}

$title = 'Create the database';
require dirname(__DIR__) . '/includes/setup-result.php';

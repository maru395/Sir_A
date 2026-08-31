<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/setup-access.php';

$host = "localhost";
$dbname = "inventorydb";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $accounts = [
        ['username' => 'Admin', 'password' => 'Admin', 'role' => 'ADMIN',
            'first_name' => 'System', 'last_name' => 'Administrator', 'email' => 'admin@aviton.local'],
        ['username' => 'User', 'password' => 'User', 'role' => 'USER',
            'first_name' => 'Sample', 'last_name' => 'Borrower', 'email' => 'user@aviton.local'],
    ];

    $findAccount = $pdo->prepare("SELECT role FROM `user` WHERE username = :username");
    $missingAccounts = [];
    foreach ($accounts as $account) {
        $findAccount->execute([':username' => $account['username']]);
        $existing = $findAccount->fetch();
        // Do not change another account's role or replace its password.
        if ($existing && $existing['role'] !== $account['role']) {
            throw new RuntimeException('A sample username belongs to another role. Review the existing accounts before running population.');
        }
        if (!$existing) {
            $missingAccounts[] = $account;
        }
    }

    $insertAccount = $pdo->prepare("
        INSERT INTO `user` (username, password_hash, role, first_name, last_name, email)
        VALUES (:username, :password_hash, :role, :first_name, :last_name, :email)
    ");
    foreach ($missingAccounts as $account) {
        $insertAccount->execute([
            ':username' => $account['username'],
            // Store hashes even for the two sample passwords.
            ':password_hash' => password_hash($account['password'], PASSWORD_DEFAULT),
            ':role' => $account['role'],
            ':first_name' => $account['first_name'],
            ':last_name' => $account['last_name'],
            ':email' => $account['email'],
        ]);
    }

    // Each row contains the item code, name, category and starting quantity.
    $equipment = [
        ['ITM-001', 'Aviation Headset', 'Communication', 10],
        ['ITM-002', 'Handheld GPS Unit', 'Navigation', 6],
        ['ITM-003', 'Fire Extinguisher', 'Safety', 8],
        ['ITM-004', 'Life Vest', 'Safety', 15],
        ['ITM-005', 'Tool Kit (A&P)', 'Maintenance', 5],
        ['ITM-006', 'Tow Bar', 'Ground Support', 4],
    ];
    $findEquipment = $pdo->prepare("SELECT id FROM equipment WHERE code = :code");
    $insertEquipment = $pdo->prepare("
        INSERT INTO equipment (code, name, category, total_quantity, available_quantity, location)
        VALUES (:code, :name, :category, :total_quantity, :available_quantity, 'Equipment room')
    ");
    $equipmentAdded = 0;
    foreach ($equipment as [$code, $name, $category, $quantity]) {
        $findEquipment->execute([':code' => $code]);
        // Keep current stock and borrowed quantities when setup is refreshed.
        if ($findEquipment->fetch()) {
            continue;
        }
        $insertEquipment->execute([
            ':code' => $code,
            ':name' => $name,
            ':category' => $category,
            ':total_quantity' => $quantity,
            ':available_quantity' => $quantity,
        ]);
        $equipmentAdded++;
    }

    $message = 'Population complete. Accounts added: ' . count($missingAccounts)
        . '. Equipment added: ' . $equipmentAdded . '. Existing accounts, stock and borrowing records were kept.';
} catch (PDOException $e) {
    http_response_code(503);
    $message = 'Population could not finish. Start MySQL and run createdb.php first. Existing rows were not deleted; after fixing setup, reload to add any missing samples.';
} catch (RuntimeException $e) {
    http_response_code(409);
    $message = $e->getMessage();
}

$title = 'Populate the database';
require dirname(__DIR__) . '/includes/setup-result.php';

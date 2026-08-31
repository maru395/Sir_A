<?php
declare(strict_types=1);

// This file is included by PHP pages, never served as a page itself.
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

class Config
{
    private PDO $pdo;

    public function __construct()
    {
        $host = "localhost";
        $dbname = "inventorydb";
        $username = "root";
        $password = "";

        try {
            $this->pdo = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Error: Code 0001");
        }
    }

    // Pages call these methods; each one calls a stored procedure from schema.sql.
    public function loginUser(string $username): array
    {
        return $this->executeProcedure('CALL sp_auth_find(?)', [$username]);
    }

    public function getUser(int $userId): array
    {
        return $this->executeProcedure('CALL sp_user_by_id(?)', [$userId]);
    }

    public function usernameExists(string $username): bool
    {
        return (bool) $this->executeProcedure('CALL sp_username_exists(?)', [$username])[0]['taken'];
    }

    public function registerUser(
        string $username,
        string $passwordHash,
        string $firstName,
        string $lastName,
        string $email,
        string $mobile
    ): array
    {
        return $this->executeProcedure('CALL sp_register(?,?,?,?,?,?)', [$username, $passwordHash, $firstName, $lastName, $email, $mobile]);
    }

    public function changePassword(int $userId, string $oldHash, string $newHash): array
    {
        return $this->executeProcedure('CALL sp_change_password(?,?,?)', [$userId, $oldHash, $newHash]);
    }

    public function getEquipmentList(int $actor, string $search, int $limit, int $offset): array
    {
        return $this->executeProcedure('CALL sp_equipment_list(?,?,?,?)', [$actor, $search, $limit, $offset]);
    }

    public function getEquipment(int $actor, int $equipmentId): array
    {
        return $this->executeProcedure('CALL sp_equipment_get(?,?)', [$actor, $equipmentId]);
    }

    public function equipmentCodeExists(int $actor, string $code, int $equipmentId): bool
    {
        return (bool) $this->executeProcedure('CALL sp_equipment_code_exists(?,?,?)', [$actor, $code, $equipmentId])[0]['taken'];
    }

    public function saveEquipment(
        int $actor,
        int $equipmentId,
        int $version,
        string $code,
        string $name,
        string $category,
        string $location,
        string $description,
        int $total
    ): array
    {
        return $this->executeProcedure(
            'CALL sp_equipment_save(?,?,?,?,?,?,?,?,?)',
            [$actor, $equipmentId, $version, $code, $name, $category, $location, $description, $total]
        );
    }

    public function deleteEquipment(int $actor, int $equipmentId, int $version): array
    {
        return $this->executeProcedure('CALL sp_equipment_delete(?,?,?)', [$actor, $equipmentId, $version]);
    }

    public function requestBorrow(int $actor, int $equipmentId, int $quantity, string $token, string $note): array
    {
        return $this->executeProcedure('CALL sp_borrow_request(?,?,?,?,?)', [$actor, $equipmentId, $quantity, $token, $note]);
    }

    public function findExistingRequest(int $actor, string $token): array
    {
        return $this->executeProcedure('CALL sp_request_existing(?,?)', [$actor, $token]);
    }

    public function transitionRecord(int $actor, int $recordId, string $action, string $note): array
    {
        return $this->executeProcedure('CALL sp_record_transition(?,?,?,?)', [$actor, $recordId, $action, $note]);
    }

    public function getRecords(int $actor, string $search, string $status, int $limit, int $offset): array
    {
        return $this->executeProcedure('CALL sp_record_list(?,?,?,?,?)', [$actor, $search, $status, $limit, $offset]);
    }

    public function getRecord(int $actor, int $recordId): array
    {
        return $this->executeProcedure('CALL sp_record_get(?,?)', [$actor, $recordId]);
    }

    public function getRecordHistory(int $actor, int $recordId): array
    {
        return $this->executeProcedure('CALL sp_record_history(?,?)', [$actor, $recordId]);
    }

    public function getEquipmentHistory(int $actor, int $equipmentId): array
    {
        return $this->executeProcedure('CALL sp_equipment_history(?,?)', [$actor, $equipmentId]);
    }

    public function getSummary(int $actor): array
    {
        return $this->executeProcedure('CALL sp_summary(?)', [$actor]);
    }

    public function reportEquipment(int $actor, int $limit, int $offset): array
    {
        return $this->executeProcedure('CALL sp_report_equipment(?,?,?)', [$actor, $limit, $offset]);
    }

    public function reportUsers(int $actor, int $limit, int $offset): array
    {
        return $this->executeProcedure('CALL sp_report_users(?,?,?)', [$actor, $limit, $offset]);
    }

    public function reportFull(int $actor, int $limit, int $offset): array
    {
        return $this->executeProcedure('CALL sp_report_full(?,?,?)', [$actor, $limit, $offset]);
    }

    // Keep the PDO work in one place instead of repeating it for every procedure.
    private function executeProcedure(string $sql, array $parameters): array
    {
        // Bind form values separately so they cannot change the SQL command.
        // Let the endpoint turn any PDO exception into a safe error message.
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters) or die('Database operation failed.');
        $rows = $statement->columnCount() ? $statement->fetchAll() : [];
        // Finish this result before calling another stored procedure.
        $statement->closeCursor();
        return $rows;
    }
}

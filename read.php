<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/request-helpers.php';
App::run(static function (): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new HttpError(405, 'Use GET for this request.');
    }
    foreach ($_GET as $value) {
        if (!is_string($value)) {
            throw new HttpError(400, 'Invalid query parameters.');
        }
    }
    $action = $_GET['action'] ?? '';
    $user = App::user();
    $userId = (int) $user['id'];
    $page = filter_var($_GET['page'] ?? '1', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
    if ($page === false) {
        throw new HttpError(422, 'Invalid page number.');
    }
    $limit = 12;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    if (mb_strlen($search) > 120) {
        throw new HttpError(422, 'Search is too long.');
    }
    $db = App::db();
    if ($action === 'summary') {
        return ['ok' => true, 'data' => $db->getSummary($userId)[0]];
    }
    if (in_array($action, ['record_history', 'equipment_history', 'equipment_get'], true)) {
        $targetId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        if (!$targetId) {
            throw new HttpError(422, 'Invalid record/equipment ID.');
        }
        if ($action === 'equipment_history') {
            App::role('ADMIN');
        }
        $rows = match ($action) {
            'record_history' => $db->getRecordHistory($userId, $targetId),
            'equipment_history' => $db->getEquipmentHistory($userId, $targetId),
            'equipment_get' => $db->getEquipment($userId, $targetId),
        };
        if ($action === 'record_history' || $action === 'equipment_history') {
            $history = trim($rows[0]['events'] ?? '', "\r\n");
            $rows = [];
            foreach ($history === '' ? [] : explode("\n", $history) as $line) {
                // History text is encoded so notes can safely contain tabs and newlines.
                $fields = explode("\t", $line);
                if (count($fields) !== 5) {
                    throw new RuntimeException('Invalid history entry.');
                }
                [$date, $type, $actorId, $actor, $details] = $fields;
                $rows[] = [
                    'id' => count($rows) + 1,
                    'created_at' => $date,
                    'event_type' => $type,
                    'actor_id' => (int) $actorId,
                    'actor' => hex2bin($actor),
                    ($action === 'record_history' ? 'note' : 'details') => hex2bin($details)
                ];
            }
            if ($action === 'equipment_history') {
                $rows = array_slice(array_reverse($rows), 0, 100);
            }
        }
        return ['ok' => true, 'data' => $rows];
    }
    if ($action === 'equipment') {
        $rows = $db->getEquipmentList($userId, $search, $limit, $offset);
    } elseif ($action === 'records') {
        $status = $_GET['status'] ?? 'ALL';
        if (
            !in_array($status, ['ALL', 'PENDING', 'BORROWED', 'RETURN_PENDING', 'RETURNED', 'REJECTED', 'CANCELLED'], true)
        ) {
            throw new HttpError(422, 'Invalid status filter.');
        }
        $rows = $db->getRecords($userId, $search, $status, $limit, $offset);
    } elseif (in_array($action, ['report_equipment', 'report_users', 'report_full'], true)) {
        App::role('ADMIN');
        $rows = match ($action) {
            'report_equipment' => $db->reportEquipment($userId, $limit, $offset),
            'report_users' => $db->reportUsers($userId, $limit, $offset),
            'report_full' => $db->reportFull($userId, $limit, $offset),
        };
    } else {
        throw new HttpError(400, 'Unknown data request.');
    }
    return [
        'ok' => true,
        'data' => $rows,
        'page' => $page,
        'per_page' => $limit,
        'total' => (int) ($rows[0]['total_rows'] ?? 0)
    ];
});

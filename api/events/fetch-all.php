<?php
require "../../config/db.php";
header("Content-Type: application/json");

 $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$monthFilter = isset($_GET['month']) ? trim($_GET['month']) : '';
$startDateInput = isset($_GET['startDate']) ? trim($_GET['startDate']) : '';
$endDateInput = isset($_GET['endDate']) ? trim($_GET['endDate']) : '';
$page = isset($_GET['page']) ? max((int) $_GET['page'], 1) : 1;
$fetchAll = isset($_GET['all']) && $_GET['all'] === '1';
$limit = 5;
$offset = ($page - 1) * $limit;

try {

    $conditions = [];
    $params = [];

    $isDateSearch = false;
    if ($searchTerm !== '') {
        $isDateSearch = preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchTerm);
        if ($isDateSearch) {
            $conditions[] = 'event_date = :exactDate';
            $params[':exactDate'] = $searchTerm;
        } else {
            $conditions[] = '(type LIKE :search OR description LIKE :search OR event_date LIKE :search)';
            $params[':search'] = "%" . $searchTerm . "%";
        }
    }

    $isMonthFilter = preg_match('/^\d{4}-\d{2}$/', $monthFilter);
    if ($isMonthFilter) {
        $conditions[] = 'DATE_FORMAT(event_date, "%Y-%m") = :month';
        $params[':month'] = $monthFilter;
    }

    $isStartDateFilter = $startDateInput !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDateInput);
    $isEndDateFilter = $endDateInput !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDateInput);

    if ($isStartDateFilter && $isEndDateFilter && $startDateInput > $endDateInput) {
        echo json_encode(["error" => "'From' date cannot be later than 'To' date."]);
        exit;
    }

    if ($isStartDateFilter) {
        $conditions[] = 'event_date >= :startDate';
        $params[':startDate'] = $startDateInput;
    }

    if ($isEndDateFilter) {
        $conditions[] = 'event_date <= :endDate';
        $params[':endDate'] = $endDateInput;
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    }

    $countQuery = "SELECT COUNT(*) FROM events $whereClause";
    $countStmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $paramType = ($key === ':limit' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
        $countStmt->bindValue($key, $value, $paramType);
    }
    $countStmt->execute();
    $total = $countStmt->fetchColumn();

    $query = "SELECT * FROM events $whereClause ORDER BY created_at DESC";
    if (!$fetchAll) {
        $query .= " LIMIT :limit OFFSET :offset";
    }
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $paramType = ($key === ':limit' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $paramType);
    }
    if (!$fetchAll) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($records as &$record) {
        $scheduledBy = trim($record['scheduled_by'] ?? '');
        $typeLabel = $record['type'] ?? '';
        $isCertificateEvent = is_string($typeLabel) && stripos($typeLabel, 'certificate') !== false;

        $record['is_auto_generated'] = ($scheduledBy !== '') ? true : false;
        $record['is_certificate_event'] = $isCertificateEvent;
    }
    unset($record);

    echo json_encode([
        "records" => $records,
        "total" => $total,
        "page" => $page,
        "limit" => $limit
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}

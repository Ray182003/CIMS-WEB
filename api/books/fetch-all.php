<?php
require_once("../../config/db.php");

header("Content-Type: application/json");

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $archived = isset($_GET['archived']) ? (int)$_GET['archived'] : 0;

    $rangeId = isset($_GET['range_id']) && $_GET['range_id'] !== '' ? (int)$_GET['range_id'] : null;

    $startYear = isset($_GET['start_year']) && $_GET['start_year'] !== '' ? (int)$_GET['start_year'] : null;
    $endYear = isset($_GET['end_year']) && $_GET['end_year'] !== '' ? (int)$_GET['end_year'] : null;

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 10;
    $offset = ($page - 1) * $limit;

    $searchParam = "%" . $search . "%";

    if ($rangeId !== null && ($startYear === null || $endYear === null)) {
        $conn->exec("CREATE TABLE IF NOT EXISTS books_ranges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            start_year INT NOT NULL,
            end_year INT NOT NULL,
            label VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_range (start_year, end_year)
        )");

        $rangeStmt = $conn->prepare("SELECT start_year, end_year FROM books_ranges WHERE id = :id");
        $rangeStmt->bindValue(':id', $rangeId, PDO::PARAM_INT);
        $rangeStmt->execute();
        $row = $rangeStmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $startYear = (int)$row['start_year'];
            $endYear = (int)$row['end_year'];
        }
    }

    $derivedSql = "(
        SELECT 'Baptismal' AS certificate_type, id, child_name AS person_name, baptism_date AS event_date, created_at, is_archived
        FROM baptismal_records
        UNION ALL
        SELECT 'Confirmation' AS certificate_type, id, child_name AS person_name, confirmation_date AS event_date, created_at, is_archived
        FROM confirmation_records
        UNION ALL
        SELECT 'Status of Liberty' AS certificate_type, id, child_name AS person_name, year_of_our_lord AS event_date, created_at, is_archived
        FROM liberty_records
        UNION ALL
        SELECT 'Permit to Marry' AS certificate_type, id, name AS person_name, birthdate AS event_date, created_at, is_archived
        FROM marriage_permits
        UNION ALL
        SELECT 'Death' AS certificate_type, id, name AS person_name, date_of_death AS event_date, created_at, is_archived
        FROM death_certificates
    ) AS all_books";

    $conditions = "WHERE is_archived = :archived AND person_name LIKE :search";
    if ($startYear !== null) {
        $conditions .= " AND YEAR(event_date) >= :start_year";
    }
    if ($endYear !== null) {
        $conditions .= " AND YEAR(event_date) <= :end_year";
    }

    $countSql = "SELECT COUNT(*) FROM $derivedSql $conditions";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bindValue(':archived', $archived, PDO::PARAM_INT);
    $countStmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
    if ($startYear !== null) {
        $countStmt->bindValue(':start_year', $startYear, PDO::PARAM_INT);
    }
    if ($endYear !== null) {
        $countStmt->bindValue(':end_year', $endYear, PDO::PARAM_INT);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $metaSql = "SELECT MIN(YEAR(event_date)) AS min_year, MAX(YEAR(event_date)) AS max_year FROM $derivedSql WHERE is_archived = :archived";
    $metaStmt = $conn->prepare($metaSql);
    $metaStmt->bindValue(':archived', $archived, PDO::PARAM_INT);
    $metaStmt->execute();
    $meta = $metaStmt->fetch(PDO::FETCH_ASSOC);

    $minYear = isset($meta['min_year']) ? (int)$meta['min_year'] : null;
    $maxYear = isset($meta['max_year']) ? (int)$meta['max_year'] : null;

    $ranges = [];
    if ($minYear && $maxYear) {
        $start = (int)(floor($minYear / 5) * 5);
        $end = (int)(floor($maxYear / 5) * 5);
        for ($y = $start; $y <= $end; $y += 5) {
            $ranges[] = [
                'start_year' => $y,
                'end_year' => $y + 4
            ];
        }
    }

    $sql = "SELECT certificate_type, id, person_name, event_date, created_at
            FROM $derivedSql
            $conditions
            ORDER BY event_date DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':archived', $archived, PDO::PARAM_INT);
    $stmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
    if ($startYear !== null) {
        $stmt->bindValue(':start_year', $startYear, PDO::PARAM_INT);
    }
    if ($endYear !== null) {
        $stmt->bindValue(':end_year', $endYear, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'records' => $records,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'ranges' => $ranges,
        'min_year' => $minYear,
        'max_year' => $maxYear
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

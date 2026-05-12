<?php
require "../../config/db.php";
header("Content-Type: application/json");

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    // Required fields
    $requiredFields = [
        'id',
        'child_name',
        'parent_name1',
        'parent_name2',
        'confirmation_date',
        'bishop',
        'book_no',
        'page_no',
        'entry_no'
    ];

    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing or empty field: $field"]);
            exit;
        }
    }

    // Sanitize input
    $id                = intval($_POST['id']);
    $child_name        = trim($_POST['child_name']);
    $parent_name1      = trim($_POST['parent_name1']);
    $parent_name2      = trim($_POST['parent_name2']);
    $confirmation_date = trim($_POST['confirmation_date']);
    $bishop            = trim($_POST['bishop']);
    $issue_day         = isset($_POST['issue_day']) ? trim($_POST['issue_day']) : '';
    $issue_month       = isset($_POST['issue_month']) ? trim($_POST['issue_month']) : '';
    $book_no           = trim($_POST['book_no']);
    $page_no           = trim($_POST['page_no']);
    $entry_no          = trim($_POST['entry_no']);
    $sponsors          = isset($_POST['sponsors']) && is_array($_POST['sponsors']) ? $_POST['sponsors'] : [];

    if (!ctype_digit($book_no) || !ctype_digit($page_no) || !ctype_digit($entry_no)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Book No, Page No, and Entry No must be numeric.']);
        exit;
    }

    // Kunin ang kasalukuyang event link para ma-sync ang event_date sa events
    $existingStmt = $conn->prepare("SELECT confirmation_date, issue_day, issue_month, event_id FROM confirmation_records WHERE id = :id");
    $existingStmt->execute([':id' => $id]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

    $linkedEventId = isset($existing['event_id']) ? (int) $existing['event_id'] : 0;

    // Compute bagong event date base sa confirmation_date + issue_day/issue_month
    $newEventDate = null;
    if (!empty($confirmation_date)) {
        $dateObject = DateTime::createFromFormat('Y-m-d', $confirmation_date) ?: DateTime::createFromFormat('m/d/Y', $confirmation_date);
        if ($dateObject instanceof DateTime) {
            // auto-fill day/month kung wala
            if ($issue_day === '') {
                $issue_day = $dateObject->format('j');
            }
            if ($issue_month === '') {
                $issue_month = $dateObject->format('F');
            }

            $year = (int) $dateObject->format('Y');

            $monthOptions = [
                "January",
                "February",
                "March",
                "April",
                "May",
                "June",
                "July",
                "August",
                "September",
                "October",
                "November",
                "December"
            ];

            $monthIndex = array_search($issue_month, $monthOptions, true);
            $dayInt = (int) preg_replace('/[^0-9]/', '', (string) $issue_day);
            if ($monthIndex !== false && $dayInt > 0 && $dayInt <= 31) {
                $monthNumber = $monthIndex + 1;
                $newEventDate = sprintf('%04d-%02d-%02d', $year, $monthNumber, $dayInt);
            }
        }
    }

    // Begin transaction
    $conn->beginTransaction();

    // Update confirmation record
    $updateQuery = "
        UPDATE confirmation_records SET
            child_name = :child_name,
            parent_name1 = :parent_name1,
            parent_name2 = :parent_name2,
            confirmation_date = :confirmation_date,
            bishop = :bishop,
            issue_day = :issue_day,
            issue_month = :issue_month,
            book_no = :book_no,
            page_no = :page_no,
            entry_no = :entry_no
        WHERE id = :id
    ";

    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([
        ':child_name'        => $child_name,
        ':parent_name1'      => $parent_name1,
        ':parent_name2'      => $parent_name2,
        ':confirmation_date' => $confirmation_date,
        ':bishop'            => $bishop,
        ':issue_day'         => $issue_day,
        ':issue_month'       => $issue_month,
        ':book_no'           => $book_no,
        ':page_no'           => $page_no,
        ':entry_no'          => $entry_no,
        ':id'                => $id
    ]);

    // I-update din ang petsa sa naka-link na event kung may event_id at valid na bagong petsa
    if ($linkedEventId > 0 && $newEventDate !== null) {
        try {
            $eventStmt = $conn->prepare("UPDATE events SET event_date = :event_date WHERE id = :event_id");
            $eventStmt->execute([
                ':event_date' => $newEventDate,
                ':event_id'   => $linkedEventId,
            ]);
        } catch (PDOException $e) {
            // huwag ibagsak ang buong update kung sakaling walang events entry
        }

        // Sync certificate_releases para sa confirmation certificate na ito
        try {
            $releaseStmt = $conn->prepare("UPDATE certificate_releases
                SET release_date = :new_date
                WHERE certificate_type = 'confirmation'
                  AND certificate_id = :cert_id");
            $releaseStmt->execute([
                ':new_date' => $newEventDate,
                ':cert_id' => $id,
            ]);
        } catch (PDOException $e) {
        }
    }

    // I-refresh ang sponsors
    $deleteSponsors = $conn->prepare("DELETE FROM confirmation_sponsors WHERE confirmation_id = :id");
    $deleteSponsors->execute([':id' => $id]);

    $insertSponsor = $conn->prepare("INSERT INTO confirmation_sponsors (confirmation_id, sponsor_name) VALUES (:id, :sponsor)");
    foreach ($sponsors as $sponsor) {
        $sponsor = trim($sponsor);
        if (!empty($sponsor)) {
            $insertSponsor->execute([
                ':id' => $id,
                ':sponsor' => $sponsor
            ]);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Confirmation record updated successfully.']);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

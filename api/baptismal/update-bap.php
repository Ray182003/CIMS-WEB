<?php
require "../../config/db.php";
header("Content-Type: application/json");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    $requiredFields = [
        'id', 'child_name', 'parent_name1', 'parent_name2', 'date',
        'in', 'on', 'bishop', 'day', 'month', 'year',
        'book_no', 'page_no', 'entry_no'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing or empty field: $field"]);
            exit;
        }
    }

    $id = intval($_POST['id']);
    $child_name     = trim($_POST['child_name']);
    $father_name    = trim($_POST['parent_name1']);
    $mother_name    = trim($_POST['parent_name2']);
    $birth_place    = trim($_POST['date']);
    $birth_date     = trim($_POST['in']);
    $baptism_date   = trim($_POST['on']);
    $bishop         = trim($_POST['bishop']);
    $day            = trim($_POST['day']);
    $month          = trim($_POST['month']);
    $year           = trim($_POST['year']);
    $book_no        = trim($_POST['book_no']);
    $page_no        = trim($_POST['page_no']);
    $entry_no       = trim($_POST['entry_no']);
    $release_claimant = isset($_POST['release_claimant']) ? trim($_POST['release_claimant']) : '';
    $release_relationship = isset($_POST['release_relationship']) ? trim($_POST['release_relationship']) : '';
    $release_time = isset($_POST['release_time']) ? trim($_POST['release_time']) : '';
    $normalizedSponsors = [];
    if (isset($_POST['sponsors']) && is_array($_POST['sponsors'])) {
        foreach ($_POST['sponsors'] as $rawSponsor) {
            $name = trim((string) $rawSponsor);
            if ($name === '') {
                continue;
            }

            $isDuplicate = false;
            foreach ($normalizedSponsors as $existingSponsor) {
                if (strcasecmp($existingSponsor, $name) === 0) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (!$isDuplicate) {
                $normalizedSponsors[] = $name;
            }
        }
    }

    if (!ctype_digit($book_no) || !ctype_digit($page_no) || !ctype_digit($entry_no)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Book No, Page No, and Entry No must be numeric.']);
        exit;
    }

    // Kunin ang kasalukuyang event link (kung meron) para ma-update din ang naka-schedule na event
    $existingStmt = $conn->prepare("SELECT child_name, event_id FROM baptismal_records WHERE id = :id");
    $existingStmt->execute([':id' => $id]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

    $linkedEventId = isset($existing['event_id']) ? (int) $existing['event_id'] : 0;

    // Compute bagong event date base sa na-edit na Date of Baptism
    $newEventDate = null;
    $baptismDateRaw = trim((string) $baptism_date);
    if ($baptismDateRaw !== '') {
        $baptismDateObj = DateTime::createFromFormat('Y-m-d', $baptismDateRaw);
        if (!$baptismDateObj) {
            $baptismDateObj = DateTime::createFromFormat('m/d/Y', $baptismDateRaw);
        }
        if ($baptismDateObj) {
            $newEventDate = $baptismDateObj->format('Y-m-d');
        }
    }

    $conn->beginTransaction();

    try {
        // I-update ang record sa database
        $updateStmt = $conn->prepare("UPDATE baptismal_records SET 
            child_name = :child_name,
            parent_name1 = :father_name,
            parent_name2 = :mother_name,
            birth_place = :birth_place,
            birth_date = :birth_date,
            baptism_date = :baptism_date,
            bishop = :bishop,
            day = :day,
            month = :month,
            year = :year,
            book_no = :book_no,
            page_no = :page_no,
            entry_no = :entry_no
            WHERE id = :id");

        $updateStmt->execute([
            ':child_name' => $child_name,
            ':father_name' => $father_name,
            ':mother_name' => $mother_name,
            ':birth_place' => $birth_place,
            ':birth_date' => $birth_date,
            ':baptism_date' => $baptism_date,
            ':bishop' => $bishop,
            ':day' => $day,
            ':month' => $month,
            ':year' => $year,
            ':book_no' => $book_no,
            ':page_no' => $page_no,
            ':entry_no' => $entry_no,
            ':id' => $id
        ]);

        // Tanggalin muna ang lahat ng existing sponsors para sa record na ito
        $deleteStmt = $conn->prepare("DELETE FROM sponsors WHERE baptismal_id = :baptismal_id");
        $deleteStmt->execute([':baptismal_id' => $id]);

        // I-insert ang mga bagong sponsors
        if (!empty($normalizedSponsors)) {
            $sponsorStmt = $conn->prepare("INSERT INTO sponsors (baptismal_id, sponsor_name) VALUES (:baptismal_id, :sponsor_name)");

            foreach ($normalizedSponsors as $sponsor) {
                $sponsorStmt->execute([
                    ':baptismal_id' => $id,
                    ':sponsor_name' => $sponsor
                ]);
            }
        }

        // Sync din ang certificate_releases kung meron record para sa baptismal na ito
        if ($newEventDate !== null) {
            $releaseStmt = $conn->prepare("UPDATE certificate_releases
                SET release_date = :new_date
                WHERE certificate_type = 'baptismal'
                  AND certificate_id = :cert_id");
            $releaseStmt->execute([
                ':new_date' => $newEventDate,
                ':cert_id' => $id,
            ]);
        }

        $releaseDateForDb = null;
        if ($newEventDate !== null) {
            $releaseDateForDb = $newEventDate;
        }

        if ($releaseDateForDb !== null && $release_claimant !== '') {
            $releaseUpdateStmt = $conn->prepare("INSERT INTO certificate_releases (certificate_type, certificate_id, release_claimant, release_relationship, release_date, pickup_time, user_id) VALUES (:certificate_type, :certificate_id, :release_claimant, :release_relationship, :release_date, :pickup_time, :user_id)");
            $releaseUpdateStmt->execute([
                ':certificate_type' => 'baptismal',
                ':certificate_id' => $id,
                ':release_claimant' => $release_claimant,
                ':release_relationship' => $release_relationship !== '' ? $release_relationship : null,
                ':release_date' => $releaseDateForDb,
                ':pickup_time' => $release_time !== '' ? $release_time : null,
                ':user_id' => $_SESSION['user_id'] ?? null,
            ]);
        }

        $conn->commit();
    } catch (PDOException $e) {
        $conn->rollBack();
        throw $e;
    }

    $eventDescription = trim('Baptismal record for ' . $child_name);

    try {
        $releaseInfoStmt = $conn->prepare("SELECT release_claimant, release_relationship, pickup_time FROM certificate_releases WHERE certificate_type = 'baptismal' AND certificate_id = :id ORDER BY id DESC LIMIT 1");
        $releaseInfoStmt->execute([':id' => $id]);
        $releaseRow = $releaseInfoStmt->fetch(PDO::FETCH_ASSOC);

        if ($releaseRow && !empty($releaseRow['release_claimant'])) {
            $claimDetails = ' | To be claimed by: ' . trim($releaseRow['release_claimant']);
            if (!empty($releaseRow['release_relationship'])) {
                $claimDetails .= ' (' . trim($releaseRow['release_relationship']) . ')';
            }

            if (!empty($releaseRow['pickup_time'])) {
                $claimDetails .= ' at ' . substr($releaseRow['pickup_time'], 0, 5);
            }

            $eventDescription = trim('Baptismal record for ' . $child_name . $claimDetails);
        }
    } catch (PDOException $e) {
        // ignore release lookup errors for event description
    }

    // Kung wala pang linked event, gumawa ng bago para lumabas sa calendar
    if ($linkedEventId <= 0 && $newEventDate !== null) {
        try {
            $eventStmt = $conn->prepare("INSERT INTO events (type, event_date, time, description, scheduled_by) VALUES (:type, :event_date, :time, :description, :scheduled_by)");
            $eventStmt->execute([
                ':type' => 'Baptismal Certificate',
                ':event_date' => $newEventDate,
                ':time' => date('H:i:s'),
                ':description' => $eventDescription,
                ':scheduled_by' => $_SESSION['user_fullname'] ?? ''
            ]);
            $linkedEventId = (int) $conn->lastInsertId();

            if ($linkedEventId > 0) {
                $linkStmt = $conn->prepare("UPDATE baptismal_records SET event_id = :event_id WHERE id = :id");
                $linkStmt->execute([
                    ':event_id' => $linkedEventId,
                    ':id' => $id,
                ]);
            }
        } catch (PDOException $e) {
            // ignore event creation errors
        }
    }

    // I-update din ang naka-link na event para sumabay ang detalye
    if ($linkedEventId > 0) {
        try {
            if ($newEventDate !== null) {
                $eventStmt = $conn->prepare("UPDATE events SET event_date = :event_date, description = :description WHERE id = :event_id");
                $eventStmt->execute([
                    ':event_date' => $newEventDate,
                    ':description' => $eventDescription,
                    ':event_id' => $linkedEventId,
                ]);
            } else {
                $eventStmt = $conn->prepare("UPDATE events SET description = :description WHERE id = :event_id");
                $eventStmt->execute([
                    ':description' => $eventDescription,
                    ':event_id' => $linkedEventId,
                ]);
            }
        } catch (PDOException $e) {
            // huwag ibagsak ang buong update kung sakaling walang events entry
        }
    }

    echo json_encode(['success' => true, 'message' => 'Baptismal record updated successfully.']);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

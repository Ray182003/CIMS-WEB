<?php
require_once("../../config/db.php");

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $date_of_death = trim($_POST['date_of_death']);
    $cause_of_death = trim($_POST['cause_of_death']);
    $issue_place = trim($_POST['issue_place']);
    $issue_day = trim($_POST['issue_day']);
    $issue_year = trim($_POST['issue_year']);
    $book_no = trim($_POST['book_no']);
    $page_no = trim($_POST['page_no']);
    $entry_no = trim($_POST['entry_no']);

    $release_claimant = trim($_POST['release_claimant'] ?? '');
    $release_relationship = trim($_POST['release_relationship'] ?? '');
    $release_time = trim($_POST['release_time'] ?? '');

    if (!ctype_digit($book_no) || !ctype_digit($page_no) || !ctype_digit($entry_no)) {
        echo json_encode(['success' => false, 'error' => 'Book No, Page No, and Entry No must be numeric.']);
        exit;
    }

    try {
        // Kunin muna ang lumang record para ma-compute ang dating event date
        $existingStmt = $conn->prepare("SELECT name, issue_place, issue_day, issue_year FROM death_certificates WHERE id = :id");
        $existingStmt->execute([':id' => $id]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            echo json_encode(['success' => false, 'error' => 'Record not found.']);
            exit;
        }

        $monthLookup = [
            'january' => 1,
            'february' => 2,
            'march' => 3,
            'april' => 4,
            'may' => 5,
            'june' => 6,
            'july' => 7,
            'august' => 8,
            'september' => 9,
            'october' => 10,
            'november' => 11,
            'december' => 12,
        ];

        $oldEventDate = null;
        $oldMonthKey = strtolower(trim($existing['issue_place'] ?? ''));
        $oldDayInt = (int) ($existing['issue_day'] ?? 0);
        $oldYearInt = (int) ($existing['issue_year'] ?? 0);
        if (isset($monthLookup[$oldMonthKey]) && $oldDayInt > 0 && $oldDayInt <= 31 && $oldYearInt > 0) {
            $oldEventDate = sprintf('%04d-%02d-%02d', $oldYearInt, $monthLookup[$oldMonthKey], $oldDayInt);
        }

        $stmt = $conn->prepare("UPDATE death_certificates SET
            name = :name,
            address = :address,
            date_of_death = :date_of_death,
            cause_of_death = :cause_of_death,
            issue_place = :issue_place,
            issue_day = :issue_day,
            issue_year = :issue_year,
            book_no = :book_no,
            page_no = :page_no,
            entry_no = :entry_no
            WHERE id = :id");

        $stmt->execute([
            ':name' => $name,
            ':address' => $address,
            ':date_of_death' => $date_of_death,
            ':cause_of_death' => $cause_of_death,
            ':issue_place' => $issue_place,
            ':issue_day' => $issue_day,
            ':issue_year' => $issue_year,
            ':book_no' => $book_no,
            ':page_no' => $page_no,
            ':entry_no' => $entry_no,
            ':id' => $id
        ]);

        // Compute bagong event date base sa na-edit na petsa
        $newEventDate = null;
        $newMonthKey = strtolower(trim($issue_place));
        $newDayInt = (int) preg_replace('/[^0-9]/', '', (string) $issue_day);
        $newYearInt = (int) preg_replace('/[^0-9]/', '', (string) $issue_year);
        if (isset($monthLookup[$newMonthKey]) && $newDayInt > 0 && $newDayInt <= 31 && $newYearInt > 0) {
            $newEventDate = sprintf('%04d-%02d-%02d', $newYearInt, $monthLookup[$newMonthKey], $newDayInt);
        }

        if ($oldEventDate !== null && $newEventDate !== null && $oldEventDate !== $newEventDate) {
            // 1) I-update ang release_date sa certificate_releases kung meron
            try {
                $releaseStmt = $conn->prepare("UPDATE certificate_releases
                    SET release_date = :new_date
                    WHERE certificate_type = 'death'
                      AND certificate_id = :cert_id
                      AND release_date = :old_date");
                $releaseStmt->execute([
                    ':new_date' => $newEventDate,
                    ':cert_id' => $id,
                    ':old_date' => $oldEventDate,
                ]);
            } catch (PDOException $e) {
            }

            // 2) I-update ang naka-link na auto-generated event sa events table
            try {
                $oldName = trim((string) ($existing['name'] ?? ''));
                $descPrefix = 'Death certificate record created for ' . $oldName;

                $eventUpdateStmt = $conn->prepare("UPDATE events
                    SET event_date = :new_date
                    WHERE type = 'Death Certificate'
                      AND event_date = :old_date
                      AND description LIKE :desc_prefix");
                $eventUpdateStmt->execute([
                    ':new_date' => $newEventDate,
                    ':old_date' => $oldEventDate,
                    ':desc_prefix' => $descPrefix . '%',
                ]);
            } catch (PDOException $e) {
            }
        }

        // Update release claimant/relationship/time (if a release row exists). Keep date synced too.
        if ($newEventDate !== null && $release_claimant !== '') {
            try {
                $latestReleaseIdStmt = $conn->prepare("SELECT id FROM certificate_releases WHERE certificate_type = 'death' AND certificate_id = :cert_id ORDER BY id DESC LIMIT 1");
                $latestReleaseIdStmt->execute([':cert_id' => $id]);
                $latestReleaseId = $latestReleaseIdStmt->fetchColumn();

                if ($latestReleaseId) {
                    $updateReleaseStmt = $conn->prepare("UPDATE certificate_releases
                        SET release_claimant = :release_claimant,
                            release_relationship = :release_relationship,
                            pickup_time = :pickup_time,
                            release_date = :release_date
                        WHERE id = :id");
                    $updateReleaseStmt->execute([
                        ':release_claimant' => $release_claimant,
                        ':release_relationship' => $release_relationship !== '' ? $release_relationship : null,
                        ':pickup_time' => $release_time !== '' ? $release_time : null,
                        ':release_date' => $newEventDate,
                        ':id' => $latestReleaseId,
                    ]);
                }
            } catch (PDOException $e) {
            }
        }

        echo json_encode(['success' => true, 'message' => 'Death record updated successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
}

<?php
require_once("../../config/db.php");

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $parents = $_POST['parents'] ?? '';
    $parents_second = $_POST['parents_second'] ?? '';
    $residence = $_POST['residence'] ?? '';
    $issue_date = $_POST['issue_date'] ?? '';
    $day_of = $_POST['day_of'] ?? '';
    $year_of_lord = $_POST['year_of_lord'] ?? '';

    $release_claimant = trim($_POST['release_claimant'] ?? '');
    $release_relationship = trim($_POST['release_relationship'] ?? '');
    $release_time = trim($_POST['release_time'] ?? '');

    if (!$id || !is_numeric($id)) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID.']);
        exit;
    }

    try {
        // Kunin muna ang lumang record para ma-compute ang dating event date
        $existingStmt = $conn->prepare("SELECT name, issue_date, day_of, year_of_lord FROM marriage_permits WHERE id = :id");
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
        $oldMonthKey = strtolower(trim($existing['issue_date'] ?? ''));
        $oldDayInt = (int) ($existing['day_of'] ?? 0);
        $oldYearInt = (int) ($existing['year_of_lord'] ?? 0);
        if (isset($monthLookup[$oldMonthKey]) && $oldDayInt > 0 && $oldDayInt <= 31 && $oldYearInt > 0) {
            $oldEventDate = sprintf('%04d-%02d-%02d', $oldYearInt, $monthLookup[$oldMonthKey], $oldDayInt);
        }

        // I-update ang pangunahing permit record
        $stmt = $conn->prepare("UPDATE marriage_permits 
            SET name = :name,
                birthdate = :birthdate,
                parents = :parents,
                parents_second = :parents_second,
                residence = :residence,
                issue_date = :issue_date,
                day_of = :day_of,
                year_of_lord = :year_of_lord
            WHERE id = :id");

        $stmt->execute([
            ':name' => $name,
            ':birthdate' => $birthdate,
            ':parents' => $parents,
            ':parents_second' => $parents_second,
            ':residence' => $residence,
            ':issue_date' => $issue_date,
            ':day_of' => $day_of,
            ':year_of_lord' => $year_of_lord,
            ':id' => $id
        ]);

        // Compute bagong event date base sa na-edit na petsa
        $newEventDate = null;
        $newMonthKey = strtolower(trim($issue_date));
        $newDayInt = (int) $day_of;
        $newYearInt = (int) $year_of_lord;
        if (isset($monthLookup[$newMonthKey]) && $newDayInt > 0 && $newDayInt <= 31 && $newYearInt > 0) {
            $newEventDate = sprintf('%04d-%02d-%02d', $newYearInt, $monthLookup[$newMonthKey], $newDayInt);
        }

        if ($oldEventDate !== null && $newEventDate !== null && $oldEventDate !== $newEventDate) {
            // 1) I-update ang release_date sa certificate_releases kung meron
            try {
                $releaseStmt = $conn->prepare("UPDATE certificate_releases
                    SET release_date = :new_date
                    WHERE certificate_type = 'ptm'
                      AND certificate_id = :cert_id
                      AND release_date = :old_date");
                $releaseStmt->execute([
                    ':new_date' => $newEventDate,
                    ':cert_id' => $id,
                    ':old_date' => $oldEventDate,
                ]);
            } catch (PDOException $e) {
                // Kung wala ang table o entry, huwag ibagsak ang buong update
            }

            // 2) I-update ang naka-link na auto-generated event sa events table
            try {
                $oldName = trim((string) ($existing['name'] ?? ''));
                $descPrefix = 'Permit to Marry record created for ' . $oldName;

                $eventUpdateStmt = $conn->prepare("UPDATE events
                    SET event_date = :new_date
                    WHERE type = 'Permit to Marry Certificate'
                      AND event_date = :old_date
                      AND description LIKE :desc_prefix");
                $eventUpdateStmt->execute([
                    ':new_date' => $newEventDate,
                    ':old_date' => $oldEventDate,
                    ':desc_prefix' => $descPrefix . '%',
                ]);
            } catch (PDOException $e) {
                // Huwag ibagsak ang main update kung sakaling walang events entry
            }
        }

        // Update release claimant/relationship/time (if a release row exists). Keep date synced too.
        if ($newEventDate !== null && $release_claimant !== '') {
            try {
                $latestReleaseIdStmt = $conn->prepare("SELECT id FROM certificate_releases WHERE certificate_type = 'ptm' AND certificate_id = :cert_id ORDER BY id DESC LIMIT 1");
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

        echo json_encode(['success' => true, 'message' => 'Permit to marry record updated successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}

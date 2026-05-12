<?php
require_once("../../config/db.php");

if (!function_exists('formatLibertyMonthInput')) {
    function formatLibertyMonthInput($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $monthLookup = [
            'january' => 'January',
            'february' => 'February',
            'march' => 'March',
            'april' => 'April',
            'may' => 'May',
            'june' => 'June',
            'july' => 'July',
            'august' => 'August',
            'september' => 'September',
            'october' => 'October',
            'november' => 'November',
            'december' => 'December'
        ];

        $lowerValue = strtolower($value);
        if (isset($monthLookup[$lowerValue])) {
            return $monthLookup[$lowerValue];
        }

        if (preg_match('/^([A-Za-z]+)/', $value, $matches)) {
            $firstWord = strtolower($matches[1]);
            if (isset($monthLookup[$firstWord])) {
                return $monthLookup[$firstWord];
            }
        }

        return null;
    }
}

if (!function_exists('normalizeLibertyYearInput')) {
    function normalizeLibertyYearInput($value)
    {
        $digits = preg_replace('/\D/', '', (string) $value);
        if (strlen($digits) === 4) {
            return $digits;
        }

        return null;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        echo json_encode(['success' => false, 'error' => 'Invalid record ID.']);
        exit;
    }

    $child_name = trim($_POST['child_name'] ?? '');
    $parent_name1 = trim($_POST['parent_name1'] ?? '');
    $parent_name2 = trim($_POST['parent_name2'] ?? '');
    $person_name1 = trim($_POST['person_name1'] ?? '');
    $person_name2 = trim($_POST['person_name2'] ?? '');
    $monthInput = $_POST['month'] ?? '';
    $dayInput = $_POST['day'] ?? '';
    $yearInput = $_POST['year'] ?? '';

    $formattedMonth = formatLibertyMonthInput($monthInput);
    $normalizedYear = normalizeLibertyYearInput($yearInput);
    $dayDigits = (int) preg_replace('/[^0-9]/', '', (string) $dayInput);

    if (!$formattedMonth || !$normalizedYear || $dayDigits < 1 || $dayDigits > 31) {
        echo json_encode(['success' => false, 'error' => 'Please provide a valid month, day (1-31), and four-digit year.']);
        exit;
    }

    $monthLookupNumeric = [
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

    // Kunin ang dating values para malaman ang lumang petsa at description
    $existingStmt = $conn->prepare("SELECT child_name, signed_sealed_given, day_of, year_of_our_lord FROM liberty_records WHERE id = :id");
    $existingStmt->execute([':id' => $id]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

    $oldEventDate = null;
    if ($existing) {
        $oldMonthKey = strtolower(trim($existing['signed_sealed_given'] ?? ''));
        $oldDay = (int) preg_replace('/[^0-9]/', '', (string) ($existing['day_of'] ?? ''));
        $oldYear = (int) preg_replace('/[^0-9]/', '', (string) ($existing['year_of_our_lord'] ?? ''));
        if (isset($monthLookupNumeric[$oldMonthKey]) && $oldDay >= 1 && $oldDay <= 31 && $oldYear > 0) {
            $oldEventDate = sprintf('%04d-%02d-%02d', $oldYear, $monthLookupNumeric[$oldMonthKey], $oldDay);
        }
    }

    $newMonthKey = strtolower($formattedMonth);
    $newYearInt = (int) $normalizedYear;
    $newEventDate = sprintf('%04d-%02d-%02d', $newYearInt, $monthLookupNumeric[$newMonthKey], $dayDigits);

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE liberty_records 
            SET child_name = :child_name,
                parent_name1 = :parent_name1,
                parent_name2 = :parent_name2,
                residence = :residence,
                marriage_with = :marriage_with,
                signed_sealed_given = :signed_sealed_given,
                day_of = :day_of,
                year_of_our_lord = :year_of_our_lord
            WHERE id = :id");

        $stmt->execute([
            ':child_name' => $child_name,
            ':parent_name1' => $parent_name1,
            ':parent_name2' => $parent_name2,
            ':residence' => $person_name1,
            ':marriage_with' => $person_name2,
            ':signed_sealed_given' => $formattedMonth,
            ':day_of' => $dayInput,
            ':year_of_our_lord' => $normalizedYear,
            ':id' => $id
        ]);

        // I-update ang naka-link na event kung may bagong event date
        if ($newEventDate !== null) {
            $oldChildName = trim((string) ($existing['child_name'] ?? ''));
            $descPrefix = 'Status of Liberty record created for ' . $oldChildName;

            try {
                $eventStmt = $conn->prepare("UPDATE events
                    SET event_date = :new_date
                    WHERE type = 'Status of Liberty Certificate'
                      AND description LIKE :desc_prefix");
                $eventStmt->execute([
                    ':new_date' => $newEventDate,
                    ':desc_prefix' => $descPrefix . '%',
                ]);
            } catch (PDOException $e) {
            }

            try {
                $releaseStmt = $conn->prepare("UPDATE certificate_releases
                    SET release_date = :new_date
                    WHERE certificate_type = 'sol'
                      AND certificate_id = :cert_id");
                $releaseStmt->execute([
                    ':new_date' => $newEventDate,
                    ':cert_id' => $id,
                ]);
            } catch (PDOException $e) {
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Liberty record updated successfully.']);
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
}

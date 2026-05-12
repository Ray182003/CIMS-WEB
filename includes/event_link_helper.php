<?php
if (!function_exists('ensureEventLinkColumn')) {
    function ensureEventLinkColumn(PDO $conn, string $tableName): void
    {
        static $checkedTables = [];

        if (isset($checkedTables[$tableName])) {
            return;
        }

        $tableNameClean = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        if ($tableNameClean === '') {
            return;
        }

        $checkSql = sprintf("SHOW COLUMNS FROM `%s` LIKE 'event_id'", $tableNameClean);
        $checkStmt = $conn->query($checkSql);

        if (!$checkStmt || $checkStmt->rowCount() === 0) {
            $referenceColumn = getReferenceColumn($conn, $tableNameClean);
            $alterSql = sprintf("ALTER TABLE `%s` ADD COLUMN event_id INT NULL%s",
                $tableNameClean,
                $referenceColumn ? sprintf(" AFTER `%s`", $referenceColumn) : ''
            );

            try {
                $conn->exec($alterSql);
            } catch (PDOException $e) {
                // Fallback: add column without positioning if initial attempt fails
                $conn->exec(sprintf("ALTER TABLE `%s` ADD COLUMN event_id INT NULL", $tableNameClean));
            }
        }

        $checkedTables[$tableName] = true;
    }
}

if (!function_exists('getReferenceColumn')) {
    function getReferenceColumn(PDO $conn, string $tableName): string
    {
        $preferredColumns = ['entry_no', 'issue_year', 'year', 'year_of_lord', 'created_at'];
        foreach ($preferredColumns as $column) {
            $checkSql = sprintf("SHOW COLUMNS FROM `%s` LIKE '%s'", $tableName, $column);
            $checkStmt = $conn->query($checkSql);
            if ($checkStmt && $checkStmt->rowCount() > 0) {
                return $column;
            }
        }
        // default fallback ensures ALTER statement remains valid
        return '';
    }
}

if (!function_exists('normalizeLookupPattern')) {
    function normalizeLookupPattern(string $pattern): string
    {
        $trimmed = trim($pattern);
        if ($trimmed === '') {
            return '%';
        }

        if (strpos($trimmed, '%') === false) {
            return $trimmed . '%';
        }

        return $trimmed;
    }
}

if (!function_exists('findOrCreateCertificateEvent')) {
    function findOrCreateCertificateEvent(
        PDO $conn,
        array $options
    ): int {
        $type = $options['type'];
        $lookupDescription = normalizeLookupPattern($options['lookup_description']);
        $eventDate = $options['event_date'];
        $eventDescription = $options['event_description'];
        $scheduledBy = $options['scheduled_by'] ?? '';

        $lookupStmt = $conn->prepare(
            "SELECT id FROM events WHERE type = :type AND description LIKE :description ORDER BY created_at DESC LIMIT 1"
        );
        $lookupStmt->execute([
            ':type' => $type,
            ':description' => $lookupDescription
        ]);
        $eventId = (int) $lookupStmt->fetchColumn();

        if ($eventId > 0) {
            return $eventId;
        }

        $insertStmt = $conn->prepare(
            "INSERT INTO events (type, event_date, time, description, scheduled_by) VALUES (:type, :event_date, :time, :description, :scheduled_by)"
        );
        $insertStmt->execute([
            ':type' => $type,
            ':event_date' => $eventDate,
            ':time' => date('H:i:s'),
            ':description' => $eventDescription,
            ':scheduled_by' => $scheduledBy
        ]);

        return (int) $conn->lastInsertId();
    }
}

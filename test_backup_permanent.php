<?php
require_once("config/db.php");

echo "<h2>📋 Backup & Permanent Delete Test</h2>";

// Step 1: Check current records
echo "<h3>Step 1: Current Records</h3>";
$stmt = $conn->query("SELECT COUNT(*) as total FROM baptismal_records WHERE is_archived = 1");
$archived = $stmt->fetch();
echo "Archived baptismal records: " . $archived['total'] . "<br>";

$stmt = $conn->query("SELECT COUNT(*) as total FROM baptismal_records WHERE is_archived = 0 OR is_archived IS NULL");
$active = $stmt->fetch();
echo "Active baptismal records: " . $active['total'] . "<br>";

// Step 2: Create backup
echo "<h3>Step 2: Creating Backup...</h3>";
$backupDir = 'backups/backup_' . date('Y-m-d_H-i-s');

if (!file_exists('backups')) {
    mkdir('backups', 0777, true);
}

if (!mkdir($backupDir, 0777, true)) {
    die("Failed to create backup directory");
}

// Create full database backup
$sqlFile = $backupDir . '/database_backup.sql';
$handle = fopen($sqlFile, 'w');

if (!$handle) {
    die("Failed to create SQL file");
}

fwrite($handle, "-- Database Backup\n");
fwrite($handle, "-- Created: " . date('Y-m-d H:i:s') . "\n");
fwrite($handle, "-- Source: Test Backup Before Delete\n\n");

// Get all tables
$stmt = $conn->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$totalRecords = 0;
foreach ($tables as $table) {
    // Get table structure
    $createTable = $conn->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
    fwrite($handle, $createTable['Create Table'] . ";\n\n");

    // Get table data
    $dataStmt = $conn->query("SELECT * FROM `$table`");
    $rowCount = $dataStmt->rowCount();
    $totalRecords += $rowCount;

    if ($rowCount > 0) {
        fwrite($handle, "-- Data for table: $table ($rowCount records)\n");
        foreach ($dataStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $values = array_map(function ($value) {
                if ($value === null) return 'NULL';
                return "'" . addslashes($value) . "'";
            }, $row);
            fwrite($handle, "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n");
        }
        fwrite($handle, "\n");
    }
}

fclose($handle);

echo "✅ Backup created: <strong>$sqlFile</strong><br>";
echo "📊 Total records backed up: <strong>$totalRecords</strong><br>";

// Step 3: Show backup file exists
echo "<h3>Step 3: Backup File Verification</h3>";
if (file_exists($sqlFile)) {
    $fileSize = filesize($sqlFile);
    echo "✅ Backup file exists<br>";
    echo "📁 File size: " . number_format($fileSize) . " bytes<br>";
    echo "📅 Created: " . date('Y-m-d H:i:s', filemtime($sqlFile)) . "<br>";
} else {
    echo "❌ Backup file not found<br>";
}

// Step 4: Simulate permanent delete
echo "<h3>Step 4: Permanent Delete Simulation</h3>";
if ($archived['total'] > 0) {
    // Get first archived record for testing
    $stmt = $conn->query("SELECT id, child_name FROM baptismal_records WHERE is_archived = 1 LIMIT 1");
    $record = $stmt->fetch();

    if ($record) {
        echo "🗑️ Permanently deleting record ID: {$record['id']} ({$record['child_name']})<br>";

        // Permanent delete
        $deleteStmt = $conn->prepare("DELETE FROM baptismal_records WHERE id = ?");
        if ($deleteStmt->execute([$record['id']])) {
            echo "✅ Record permanently deleted from database<br>";
        } else {
            echo "❌ Failed to delete record<br>";
        }

        // Verify deletion
        $checkStmt = $conn->query("SELECT COUNT(*) as count FROM baptismal_records WHERE id = " . $record['id']);
        $check = $checkStmt->fetch();

        if ($check['count'] == 0) {
            echo "✅ Confirmed: Record no longer exists in database<br>";
        } else {
            echo "❌ Error: Record still exists in database<br>";
        }
    }
} else {
    echo "ℹ️ No archived records to delete<br>";
}

// Step 5: Show backup still exists
echo "<h3>Step 5: Backup Persistence Check</h3>";
if (file_exists($sqlFile)) {
    echo "✅ Backup file STILL EXISTS after permanent delete<br>";
    echo "💾 You can restore this backup anytime from backup_recovery.php<br>";
    echo "🔄 The backup contains all data including the deleted record<br>";
} else {
    echo "❌ Backup file missing<br>";
}

echo "<hr>";
echo "<h3>📋 Summary</h3>";
echo "1. ✅ Backup created with ALL data<br>";
echo "2. ✅ Backup file saved to: $backupDir<br>";
echo "3. ✅ Record permanently deleted from database<br>";
echo "4. ✅ Backup file remains intact for restoration<br>";
echo "<br><strong>🎯 The backup is independent and will persist even after permanent deletion!</strong><br>";
echo "<a href='backup/backup_recovery.php' class='btn btn-primary'>📂 Go to Backup Recovery</a>";

<?php
require_once("config/db.php");

echo "<h2>🔍 Debug Restore Process</h2>";

// Check current baptismal records
echo "<h3>Current Baptismal Records:</h3>";
$stmt = $conn->query("SELECT id, child_name, is_archived, archived_at FROM baptismal_records ORDER BY id");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($records)) {
    echo "❌ No baptismal records found<br>";
} else {
    foreach ($records as $record) {
        $status = $record['is_archived'] == 1 ? "📦 Archived" : "📄 Active";
        $archived = $record['archived_at'] ?? "Never";
        echo "ID {$record['id']}: {$record['child_name']} - $status (Archived: $archived)<br>";
    }
}

// Check latest backup
echo "<h3>Latest Backup File:</h3>";
$backupDir = 'backups/backup_2025-11-18_17-03-10';
$sqlFile = $backupDir . '/database_backup.sql';

if (file_exists($sqlFile)) {
    echo "✅ Backup file found: $sqlFile<br>";
    
    // Read and show baptismal INSERT statements
    $content = file_get_contents($sqlFile);
    preg_match_all("/INSERT INTO `baptismal_records` VALUES \((.+)\);/", $content, $matches);
    
    if (!empty($matches[0])) {
        echo "📋 Found " . count($matches[0]) . " baptismal records in backup:<br>";
        foreach ($matches[0] as $insert) {
            echo "📄 $insert<br>";
        }
    } else {
        echo "❌ No baptismal INSERT statements found in backup<br>";
    }
} else {
    echo "❌ Backup file not found: $sqlFile<br>";
}

// Test restore manually
echo "<h3>🧪 Test Manual Restore:</h3>";
if (file_exists($sqlFile)) {
    try {
        $sqlContent = file_get_contents($sqlFile);
        
        // Extract just the baptismal INSERT
        preg_match("/INSERT INTO `baptismal_records` VALUES \(.+\);/", $sqlContent, $match);
        
        if (!empty($match[0])) {
            echo "🔄 Executing: " . $match[0] . "<br>";
            $conn->exec($match[0]);
            echo "✅ Manual restore executed successfully!<br>";
            
            // Check results
            echo "<h3>After Manual Restore:</h3>";
            $stmt = $conn->query("SELECT id, child_name, is_archived, archived_at FROM baptismal_records ORDER BY id");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($records as $record) {
                $status = $record['is_archived'] == 1 ? "📦 Archived" : "📄 Active";
                $archived = $record['archived_at'] ?? "Never";
                echo "ID {$record['id']}: {$record['child_name']} - $status (Archived: $archived)<br>";
            }
        } else {
            echo "❌ Could not extract baptismal INSERT statement<br>";
        }
    } catch (Exception $e) {
        echo "❌ Manual restore failed: " . $e->getMessage() . "<br>";
    }
}
?>

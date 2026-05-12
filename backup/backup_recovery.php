<?php

date_default_timezone_set('Asia/Manila');
require_once '../security_helper.php';
requireAuth();

$backupDir = realpath(__DIR__ . '/..') . '/backups';

function isPdo($conn)
{
    return $conn instanceof PDO;
}

function formatBytes(int $bytes, int $precision = 2): string
{
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = (int) floor(log($bytes, 1024));
    $power = max(0, min($power, count($units) - 1));
    $bytes /= (1024 ** $power);

    return round($bytes, $precision) . ' ' . $units[$power];
}

function ensureBackupDirectory(string $backupDir): void
{
    if (!is_dir($backupDir)) {
        if (!mkdir($backupDir, 0777, true) && !is_dir($backupDir)) {
            throw new RuntimeException('Unable to create backups directory');
        }
    }
}

function escapeValue($conn, $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (isPdo($conn)) {
        return $conn->quote($value);
    }

    return "'" . mysqli_real_escape_string($conn, $value) . "'";
}

function fetchTableNames($conn): array
{
    if (isPdo($conn)) {
        $stmt = $conn->query('SHOW TABLES');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    $tables = [];
    $result = $conn->query('SHOW TABLES');
    if ($result) {
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
    }
    return $tables;
}

function fetchCreateStatement($conn, string $table): string
{
    if (isPdo($conn)) {
        $stmt = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        return $row['Create Table'] ?? '';
    }

    $result = $conn->query("SHOW CREATE TABLE `$table`");
    if ($result) {
        $row = $result->fetch_array();
        return $row[1] ?? '';
    }
    return '';
}

function fetchTableData($conn, string $table)
{
    if (isPdo($conn)) {
        $stmt = $conn->query("SELECT * FROM `$table`");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    $rows = [];
    $result = $conn->query("SELECT * FROM `$table`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function createBackup($conn, string $backupDir): array
{
    ensureBackupDirectory($backupDir);

    $timestamp = date('Y-m-d_H-i-s');
    $sqlFilename = "backup_{$timestamp}.sql";
    $sqlPath = $backupDir . '/' . $sqlFilename;

    $tables = fetchTableNames($conn);
    if (empty($tables)) {
        throw new RuntimeException('No tables found to export.');
    }

    $handle = fopen($sqlPath, 'w');
    if (!$handle) {
        throw new RuntimeException('Unable to open SQL file for writing.');
    }

    fwrite($handle, "-- Database Backup\n");
    fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");

    foreach ($tables as $table) {
        fwrite($handle, "-- Structure for table `$table`\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");

        $create = fetchCreateStatement($conn, $table);
        if (!$create) {
            fclose($handle);
            unlink($sqlPath);
            throw new RuntimeException("Unable to fetch CREATE statement for table $table");
        }
        fwrite($handle, $create . ";\n\n");

        fwrite($handle, "-- Data for table `$table`\n");
        $rows = fetchTableData($conn, $table);
        foreach ($rows as $row) {
            $values = [];
            foreach ($row as $value) {
                $values[] = escapeValue($conn, $value);
            }
            fwrite($handle, "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n");
        }
        fwrite($handle, "\n");
    }

    fclose($handle);

    $finalFilename = $sqlFilename;
    $finalPath = $sqlPath;

    if (class_exists('ZipArchive')) {
        $zipFilename = "backup_{$timestamp}.zip";
        $zipPath = $backupDir . '/' . $zipFilename;
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            unlink($sqlPath);
            throw new RuntimeException('Unable to create ZIP archive.');
        }

        $zip->addFile($sqlPath, $sqlFilename);
        $zip->close();
        unlink($sqlPath);
        $finalFilename = $zipFilename;
        $finalPath = $zipPath;
    }

    return [$finalFilename, $finalPath];
}

function importBackup($conn, array $fileInfo): void
{
    if (!isset($fileInfo['tmp_name']) || $fileInfo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please choose a backup file to import.');
    }

    $extension = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
    $sql = '';
    $tempDir = null;
    $extractedSqlPath = null;

    if ($extension === 'zip') {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZIP extension is not enabled on this server.');
        }

        $tempDir = sys_get_temp_dir() . '/backup_import_' . uniqid();
        if (!mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Unable to create temporary directory.');
        }

        $zip = new ZipArchive();
        if ($zip->open($fileInfo['tmp_name']) !== true) {
            throw new RuntimeException('Invalid backup archive.');
        }

        if (!$zip->extractTo($tempDir)) {
            $zip->close();
            throw new RuntimeException('Failed to extract backup archive.');
        }
        $zip->close();

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isFile() && strtolower($item->getExtension()) === 'sql') {
                $extractedSqlPath = $item->getPathname();
                break;
            }
        }

        if (!$extractedSqlPath || !is_file($extractedSqlPath)) {
            throw new RuntimeException('No SQL file found inside the archive.');
        }

        $sql = file_get_contents($extractedSqlPath);
    } elseif ($extension === 'sql') {
        $sql = file_get_contents($fileInfo['tmp_name']);
    } else {
        throw new RuntimeException('Only .zip or .sql backup files are supported.');
    }

    if ($sql === false) {
        throw new RuntimeException('Unable to read backup file.');
    }

    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $sql = preg_replace('/^\s*(--|#).*$\r?\n?/m', '', $sql);
    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

    $conn->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }
        try {
            $conn->exec($statement);
        } catch (Throwable $e) {
            // Continue executing remaining statements.
        }
    }
    $conn->exec('SET FOREIGN_KEY_CHECKS=1');

    if ($extractedSqlPath && file_exists($extractedSqlPath)) {
        unlink($extractedSqlPath);
    }
    if ($tempDir && is_dir($tempDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($tempDir);
    }
}

$message = '';
$messageType = '';

try {
    ensureBackupDirectory($backupDir);
} catch (Throwable $e) {
    $message = $e->getMessage();
    $messageType = 'danger';
}

if (empty($message) && isset($_POST['create_backup'])) {
    try {
        [$filename] = createBackup($conn, $backupDir);
        $message = "Backup created successfully: $filename";
        $messageType = 'success';
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

if (empty($message) && isset($_POST['import_backup'])) {
    try {
        importBackup($conn, $_FILES['backup_file'] ?? []);
        $message = 'Database import completed successfully.';
        $messageType = 'success';
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $path = $backupDir . '/' . $file;
    if (!is_file($path)) {
        $message = 'Requested backup not found.';
        $messageType = 'danger';
    } else {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}

if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    $path = $backupDir . '/' . $file;
    if (!is_file($path)) {
        $message = 'Backup file not found.';
        $messageType = 'danger';
    } elseif (!unlink($path)) {
        $message = 'Failed to delete backup file.';
        $messageType = 'danger';
    } else {
        $message = 'Backup deleted successfully.';
        $messageType = 'success';
    }
}

$backups = [];
if (is_dir($backupDir)) {
    foreach (scandir($backupDir) as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $path = $backupDir . '/' . $file;
        if (!is_file($path)) {
            continue;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['zip', 'sql'], true)) {
            continue;
        }
        $backups[] = [
            'name' => $file,
            'size' => filesize($path),
            'modified' => filemtime($path),
        ];
    }

    usort($backups, function ($a, $b) {
        return $b['modified'] <=> $a['modified'];
    });
}

$backupCount = count($backups);
$totalSizeBytes = array_sum(array_column($backups, 'size')) ?: 0;

if ($backupCount > 0) {
    $latestBackup = $backups[0];
    $latestBackupName = $latestBackup['name'];
    $latestBackupTime = date('M j, Y g:i A', $latestBackup['modified']);
    $totalSizeFormatted = formatBytes($totalSizeBytes);
} else {
    $latestBackupName = 'None yet';
    $latestBackupTime = '—';
    $totalSizeFormatted = '0 B';
}

$zipStatus = class_exists('ZipArchive') ? 'Enabled' : 'Unavailable';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup &amp; Recovery</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/fontawesome-local.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #7c3aed;
            --accent: #22d3ee;
            --danger: #ef4444;
            --text-muted: #94a3b8;
            --glass-bg: rgba(255,255,255,0.12);
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #24c2f0 0%, #72da6f 55%, #72da6f 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }

        body::before,
        body::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(140px);
            background: rgba(255,255,255,0.65);
            z-index: 0;
        }

        body::before {
            width: 460px;
            height: 460px;
            top: -160px;
            right: -120px;
        }

        body::after {
            width: 380px;
            height: 380px;
            bottom: -140px;
            left: -110px;
        }

        .content-wrapper {
            position: relative;
            z-index: 1;
            padding: 4rem 0 5rem;
        }

        .content-wrapper::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.22), rgba(255,255,255,0.1));
            filter: blur(120px);
            z-index: -1;
        }

        .hero-card {
            background: linear-gradient(135deg, rgba(79,70,229,0.65), rgba(14,165,233,0.4));
            border-radius: 2rem;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.3);
            padding: 3rem;
            margin-bottom: 2.5rem;
            backdrop-filter: blur(18px);
        }

        .hero-card h1 {
            font-weight: 800;
            font-size: 2.75rem;
            color: #fff;
        }

        .hero-highlight {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.18);
            color: #e0f2fe;
            padding: 0.45rem 1rem;
            border-radius: 999px;
            font-size: 0.9rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .hero-toolbar {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        @media (min-width: 992px) {
            .hero-toolbar {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            background: rgba(255,255,255,0.24);
            color: #0f172a;
            border-radius: 999px;
            padding: 0.6rem 1.4rem;
            font-weight: 600;
            text-decoration: none;
            backdrop-filter: blur(10px);
            box-shadow: 0 12px 30px rgba(15,23,42,0.25);
            transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.34);
            transform: translateY(-2px);
            box-shadow: 0 18px 38px rgba(15,23,42,0.28);
            color: #0f172a;
        }

        .glass-card {
            position: relative;
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 1.6rem;
            padding: 2.2rem;
            backdrop-filter: blur(18px);
            box-shadow: 0 20px 45px rgba(8, 47, 73, 0.35);
            height: 100%;
        }

        .glass-card h5 {
            color: #fff;
            font-weight: 700;
            margin-bottom: 1.4rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .stats-grid {
            margin-bottom: 3rem;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(79,70,229,0.6), rgba(14,165,233,0.38));
            border-radius: 1.5rem;
            padding: 1.75rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.3);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(148, 163, 184, 0.22);
        }

        .stat-card.secondary {
            background: linear-gradient(135deg, rgba(14,165,233,0.45), rgba(59,130,246,0.4));
        }

        .stat-card.tertiary {
            background: linear-gradient(135deg, rgba(16,185,129,0.35), rgba(45,212,191,0.4));
        }

        .stat-icon {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,0.2);
            color: #fff;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .stat-label {
            color: rgba(226,232,240,0.78);
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-size: 0.75rem;
            margin-bottom: 0.35rem;
        }

        .stat-value {
            color: #fff;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-subtext {
            color: rgba(226,232,240,0.6);
            font-size: 0.9rem;
        }

        .status-pill {
            background: rgba(15,23,42,0.4);
            border: 1px solid rgba(255,255,255,0.25);
            color: #f1f5f9;
        }

        .card-description {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 0.75rem 1.8rem;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(79,70,229,0.35);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(124,58,237,0.4);
            color: #fff;
        }

        .form-control {
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(148,163,184,0.25);
            color: #e2e8f0;
        }

        .form-control:focus {
            background: rgba(15, 23, 42, 0.7);
            border-color: var(--accent);
            box-shadow: 0 0 0 0.25rem rgba(34,211,238,0.25);
            color: #fff;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
        }

        .form-hint {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .backups-card {
            margin-top: 3rem;
            background: rgba(15,23,42,0.65);
            border-radius: 2rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            overflow: hidden;
            backdrop-filter: blur(16px);
        }

        .backups-card .card-header {
            background: linear-gradient(135deg, rgba(79,70,229,0.85), rgba(14,165,233,0.55));
            border-bottom: 1px solid rgba(148,163,184,0.2);
            padding: 1.5rem 2rem;
        }

        .backups-card .card-body {
            padding: 0;
        }

        .table-responsive {
            background: rgba(15, 23, 42, 0.75);
            max-height: 360px;
            overflow-y: auto;
            border-bottom-left-radius: 2rem;
            border-bottom-right-radius: 2rem;
        }

        .table-responsive::-webkit-scrollbar {
            width: 10px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.35);
            border-radius: 999px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, rgba(79,70,229,0.65), rgba(14,165,233,0.55));
            border-radius: 999px;
        }

        .table {
            color: #e2e8f0;
        }

        .table thead {
            background: rgba(148, 163, 184, 0.12);
            color: #cbd5f5;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .table tbody tr {
            transition: background 0.35s ease;
        }

        .table tbody tr:hover {
            background: rgba(79, 70, 229, 0.12);
        }

        .filename {
            color: #c7d2ff;
            font-weight: 600;
        }

        .file-type-badge {
            background: rgba(255,255,255,0.9);
            color: #1f2937;
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            font-weight: 700;
            box-shadow: 0 6px 16px rgba(15,23,42,0.18);
        }

        .badge-pill {
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            font-weight: 600;
        }

        .btn-outline-primary,
        .btn-outline-danger {
            border-radius: 999px;
            padding: 0.5rem 1.35rem;
            font-weight: 600;
        }

        .btn-outline-primary:hover {
            background: rgba(14,165,233,0.15);
            border-color: var(--accent);
            color: #bae6fd;
        }

        .btn-outline-danger:hover {
            background: rgba(239,68,68,0.15);
            border-color: var(--danger);
            color: #fecaca;
        }

        .empty-state {
            padding: 4rem 1.5rem;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 1rem;
            color: rgba(148,163,184,0.75);
        }

        .alert {
            border-radius: 1rem;
            border: none;
            background: rgba(15, 23, 42, 0.65);
            color: #f8fafc;
            border-left: 4px solid var(--accent);
        }
    </style>
</head>
<body>
<div class="container content-wrapper">
    <div class="hero-card text-center text-lg-start">
        <div class="hero-toolbar">
            <div class="hero-highlight"><i class="fas fa-shield-alt"></i> Secure Backup Suite</div>
            <a class="back-link" href="../dashboard/index.php">
                <i class="fas fa-arrow-left fs-5"></i>
                Back 
            </a>
        </div>
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h1>Database Backup &amp; Recovery</h1>
                <p class="mt-3 mb-0" style="color: rgba(226,232,240,0.92);">
                    Safeguard your parish records with one-click exports, effortless restores, and downloadable archives.
                    Manage every snapshot from a unified, polished dashboard.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <span class="badge-pill" style="background: rgba(34,211,238,0.18); color: #99f6e4;">
                    <i class="fas fa-cloud-upload-alt me-2"></i>Auto ZIP Support <?= htmlspecialchars($zipStatus) ?>
                </span>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType ?? 'info') ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="stats-grid row g-4">
        <div class="col-md-4">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fa-solid fa-database"></i>
                </div>
                <div>
                    <div class="stat-label">Total Backups</div>
                    <div class="stat-value"><?= $backupCount ?></div>
                    <div class="stat-subtext">Across <?= htmlspecialchars($totalSizeFormatted) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card secondary">
                <div class="stat-icon">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <div class="stat-label">Latest Backup</div>
                    <div class="stat-value" style="font-size: 1.25rem;"><?= htmlspecialchars($latestBackupName) ?></div>
                    <div class="stat-subtext"><?= htmlspecialchars($latestBackupTime) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card tertiary">
                <div class="stat-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <div class="stat-label">Restore Readiness</div>
                    <div class="stat-value" style="font-size: 1.45rem;"><?= htmlspecialchars($zipStatus) ?></div>
                    <div class="stat-subtext">ZIP compression automatically applied</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="glass-card">
                <h5><span class="badge-pill" style="background: rgba(79,70,229,0.25); color: #c7d2fe;"><i class="fa-solid fa-server"></i></span>Create Backup</h5>
                <p class="card-description">Generate a full SQL dump of the current database. When ZIP support is available, backups are automatically compressed to reduce storage footprint.</p>
                <form method="post" class="form-actions">
                    <button type="submit" name="create_backup" class="btn btn-gradient">
                        <i class="fa-solid fa-cloud-arrow-down me-2"></i>Create Backup
                    </button>
                    <span class="form-hint">
                        Latest export: <?= htmlspecialchars($latestBackupName) ?>
                    </span>
                </form>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="glass-card">
                <h5><span class="badge-pill" style="background: rgba(14,165,233,0.25); color: #bae6fd;"><i class="fa-solid fa-rotate-right"></i></span>Import Backup</h5>
                <p class="card-description">Restore your database using a previously exported archive. Upload either a compressed ZIP or raw SQL file—existing records will be replaced.</p>
                <form method="post" enctype="multipart/form-data" class="form-actions">
                    <input type="file" name="backup_file" class="form-control" accept=".zip,.sql" required>
                    <button type="submit" name="import_backup" class="btn btn-gradient" onclick="return confirm('Importing a backup will overwrite existing data. Continue?');">
                        <i class="fas fa-upload me-2"></i>Import Backup
                    </button>
                </form>
                <p class="mt-3 mb-0 form-hint">
                    Tip: Keep at least one recent export stored off-site for disaster recovery.
                </p>
            </div>
        </div>
    </div>

    <div class="backups-card mt-5">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-box-archive me-2"></i>Available Backups</h5>
            <span class="badge-pill" style="background: rgba(15,23,42,0.45); color: #e2e8f0; border: 1px solid rgba(148,163,184,0.25);">
                <?= count($backups) ?> files
            </span>
        </div>
        <div class="card-body">
            <?php if (empty($backups)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p class="mb-2">No backups found yet.</p>
                    <p class="mb-0">Create one using the form above to get started.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                        <tr>
                            <th scope="col">Filename</th>
                            <th scope="col">Created</th>
                            <th scope="col">Size</th>
                            <th scope="col">Type</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <?php $extension = strtoupper(pathinfo($backup['name'], PATHINFO_EXTENSION)); ?>
                            <tr>
                                <td class="filename"><?= htmlspecialchars($backup['name']) ?></td>
                                <td><?= date('M j, Y g:i A', $backup['modified']) ?></td>
                                <td><?= htmlspecialchars(formatBytes((int) $backup['size'])) ?></td>
                                <td>
                                    <span class="file-type-badge"><i class="fas fa-file-alt me-1"></i><?= $extension ?></span>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary me-2" href="?download=<?= urlencode($backup['name']) ?>">
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>
                                    <a class="btn btn-sm btn-outline-danger" href="?delete=<?= urlencode($backup['name']) ?>" onclick="return confirm('Delete this backup file?');">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/public/js/bootstrap.bundle.min.js"></script>
</body>
</html>

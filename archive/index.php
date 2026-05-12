<?php
$title = "Archive";
require_once("../security_helper.php");
requireAuth();
require_once("../config/db.php");
define("BASE_URL", "http://" . $_SERVER["HTTP_HOST"] . "/cims-app");
include_once("../partials/html.head.php");

function columnExists(PDO $connection, string $table, string $column): bool
{
    $tableSafe = str_replace('`', '``', $table);
    $sql = "SHOW COLUMNS FROM `{$tableSafe}` LIKE :column";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(':column', $column, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchColumn() !== false;
}

$sections = [];
$notices = [];

$modules = [
    'baptismal' => [
        'title' => 'Baptismal Records',
        'table' => 'baptismal_records',
        'select' => 'id, child_name, parent_name1, parent_name2, bishop',
    ],
    'liberty' => [
        'title' => 'Status of Liberty',
        'table' => 'liberty_records',
        'select' => 'id, child_name, marriage_with, year_of_our_lord',
    ],
    'confirmation' => [
        'title' => 'Confirmation Records',
        'table' => 'confirmation_records',
        'select' => 'id, child_name, confirmation_date, bishop',
    ],
    'permit' => [
        'title' => 'Permit to Marry',
        'table' => 'marriage_permits',
        'select' => 'id, name, residence, birthdate',
    ],
    'death' => [
        'title' => 'Death Certificates',
        'table' => 'death_certificates',
        'select' => 'id, name, date_of_death, cause_of_death',
    ],
];

foreach ($modules as $key => $meta) {
    $table = $meta['table'];
    $title = $meta['title'];

    $hasIsArchived = columnExists($conn, $table, 'is_archived');
    $hasArchivedAt = $hasIsArchived ? columnExists($conn, $table, 'archived_at') : false;

    if (!$hasIsArchived) {
        $sections[$key] = [
            'title' => $title,
            'records' => [],
            'error' => "Missing column 'is_archived' sa table {$table}. Pakitakbo ang migration script para gumana ang archive.",
            'has_archived_at' => false,
        ];
        continue;
    }

    $select = $meta['select'];
    $orderBy = 'id DESC';
    if ($hasArchivedAt) {
        $select .= ', archived_at';
        $orderBy = 'archived_at DESC';
    } else {
        $select .= ", NULL AS archived_at";
    }

    $tableSafe = str_replace('`', '``', $table);
    $sql = "SELECT {$select} FROM `{$tableSafe}` WHERE is_archived = 1 ORDER BY {$orderBy}";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $sections[$key] = [
            'title' => $title,
            'records' => [],
            'error' => 'Database error: ' . htmlspecialchars($e->getMessage()),
            'has_archived_at' => $hasArchivedAt,
        ];
        continue;
    }

    if (!$hasArchivedAt) {
        $notices[$key] = "Column 'archived_at' hindi makita sa {$table}. Lalabas bilang N/A hangga't hindi natatakbo ang migration.";
    }

    $sections[$key] = [
        'title' => $title,
        'records' => $records,
        'has_archived_at' => $hasArchivedAt,
    ];
}
?>

<style>
    .archive-bg {
        min-height: 100vh;
        position: relative;
        overflow: hidden;
        background: transparent;
    }

    .archive-bg::before,
    .archive-bg::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
        opacity: 0.28;
        background: rgba(255, 255, 255, 0.85);
        filter: blur(60px);
    }

    .archive-bg::before {
        width: 460px;
        height: 460px;
        top: -160px;
        right: -120px;
    }

    .archive-bg::after {
        width: 380px;
        height: 380px;
        bottom: -140px;
        left: -110px;
    }

    .archive-bg > * {
        position: relative;
        z-index: 1;
    }

    .archive-card {
        border-radius: 1rem;
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }

    h1.mb-0 {
        font-family: 'Fredoka One', 'Poppins', 'Segoe UI', sans-serif;
        font-weight: 700;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: #0a0a0a;
        text-shadow:
            0 2px 0 rgba(255, 255, 255, 0.4),
            0 4px 10px rgba(0, 0, 0, 0.45);
    }

    #archiveReminder {
        font-family: "Times New Roman", serif;
        font-size: 15px;
    }

    html,
    body {
        height: 100%;
        overflow-y: auto;
    }

    .archive-card .table {
        margin-bottom: 0;
    }
    
    /* Custom Button Styles - Baptismal Style */
    .restore-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 600;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: #28a745;
        color: white;
        border: none;
        padding: 0.375rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
    }
    
    .restore-btn .restore-icon {
        position: relative;
        width: 1.1rem;
        height: 1.1rem;
        display: inline-block;
        pointer-events: none;
    }
    
    .restore-btn .restore-icon::before {
        content: "↶";
        font-size: 1rem;
        font-weight: bold;
    }
    
    .restore-btn .restore-label {
        line-height: 1;
        pointer-events: none;
    }
    
    .restore-btn:hover,
    .restore-btn:focus-visible {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        background: #218838;
    }
    
    .restore-btn:active {
        transform: translateY(1px);
    }
    
    .trash-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 600;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        border: none;
        padding: 0.375rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
    }
    
    .trash-btn:hover,
    .trash-btn:focus-visible {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        background: linear-gradient(135deg, #c82333, #bd2130);
    }
    
    .trash-btn:active {
        transform: translateY(1px);
    }
    
    .trash-btn .trash-icon {
        position: relative;
        width: 1.2rem;
        height: 1.2rem;
        pointer-events: none;
        display: inline-block;
    }
    
    .trash-btn .trash-lid,
    .trash-btn .trash-body,
    .trash-btn .trash-handle {
        position: absolute;
        background: currentColor;
        left: 0;
        pointer-events: none;
    }
    
    .trash-btn .trash-lid {
        top: 0.08rem;
        width: 100%;
        height: 0.2rem;
        border-radius: 0.25rem;
        transform: translateY(0);
        transform-origin: center;
        transition: transform 0.35s ease;
    }
    
    .trash-btn .trash-handle {
        top: -0.32rem;
        left: 50%;
        width: 0.55rem;
        height: 0.22rem;
        border: 2px solid currentColor;
        border-bottom: none;
        border-radius: 0.35rem 0.35rem 0 0;
        background: transparent;
        transform: translateX(-50%);
    }
    
    .trash-btn .trash-body {
        bottom: 0;
        left: 12%;
        width: 76%;
        height: 0.95rem;
        border: 2px solid currentColor;
        border-top: none;
        border-radius: 0 0 0.35rem 0.35rem;
        background: transparent;
        transition: transform 0.2s ease;
    }
    
    .trash-btn .trash-body::before,
    .trash-btn .trash-body::after {
        content: "";
        position: absolute;
        top: 20%;
        bottom: 18%;
        width: 2px;
        background: currentColor;
    }
    
    .trash-btn .trash-body::before {
        left: 34%;
    }
    
    .trash-btn .trash-body::after {
        right: 34%;
    }
    
    .trash-btn .trash-label {
        line-height: 1;
        pointer-events: none;
    }
    
    .trash-btn:hover,
    .trash-btn:focus-visible {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        background: #c82333;
    }
    
    .trash-btn:active {
        transform: translateY(1px);
    }
    
    .trash-btn:hover .trash-lid,
    .trash-btn:focus-visible .trash-lid {
        transform: translateY(-0.3rem);
    }
    
    .trash-btn:hover .trash-body,
    .trash-btn:focus-visible .trash-body {
        transform: translateY(-1px);
    }
    
    @keyframes trashLidWave {
        0% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-0.32rem);
        }
        100% {
            transform: translateY(0);
        }
    }
    
    @keyframes pulseTrash {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
        100% {
            transform: scale(1);
        }
    }
    
    .trash-btn.animate-once {
        animation: pulseTrash 0.85s ease-out;
    }
    
    .trash-btn.animate-once .trash-lid {
        animation: trashLidWave 0.85s ease-out;
    }
    
    /* Smooth transitions for table rows */
    .archive-card tbody tr {
        transition: all 0.3s ease;
    }
    
    .archive-card tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.1);
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .archive-card .card-header {
        font-weight: 600;
    }

    .archive-scroll {
        height: 340px;
        overflow-y: auto;
    }

    .badge-muted {
        background-color: rgba(0, 0, 0, 0.08);
        color: #333;
    }

    #layoutSidenav_content {
        padding-bottom: 2rem;
    }

    /* Responsive Design Improvements */
    
    /* Mobile Landscape and Tablet Portrait */
    @media (max-width: 991.98px) {
        .archive-bg::before {
            width: 300px;
            height: 300px;
            top: -100px;
            right: -80px;
        }
        
        .archive-bg::after {
            width: 250px;
            height: 250px;
            bottom: -100px;
            left: -80px;
        }
        
        h1.mb-0 {
            font-size: 1.8rem;
            letter-spacing: 2px;
        }
        
        .archive-card {
            border-radius: 0.8rem;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .archive-scroll {
            height: 280px;
        }
        
        .container-fluid {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
    }
    
    /* Mobile Portrait */
    @media (max-width: 767.98px) {
        .archive-bg::before,
        .archive-bg::after {
            opacity: 0.15;
        }
        
        .archive-bg::before {
            width: 200px;
            height: 200px;
            top: -60px;
            right: -40px;
        }
        
        .archive-bg::after {
            width: 180px;
            height: 180px;
            bottom: -60px;
            left: -40px;
        }
        
        h1.mb-0 {
            font-size: 1.5rem;
            letter-spacing: 1px;
            margin-bottom: 0.5rem !important;
            line-height: 1.3;
        }
        
        .archive-card {
            border-radius: 0.6rem;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 1rem;
        }
        
        .archive-card .card-header {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .archive-scroll {
            height: 250px;
        }
        
        .container-fluid {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }
        
        .alert {
            font-size: 0.85rem;
            padding: 0.75rem 1rem;
            line-height: 1.4;
        }
        
        #archiveReminder {
            font-size: 13px;
            line-height: 1.4;
        }
        
        /* Typography improvements */
        body {
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .d-flex.align-items-center.justify-content-between.mb-4 {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 1rem;
        }
        
        .d-flex.align-items-center.justify-content-between.mb-4 h1 {
            margin-bottom: 0;
        }
        
        .d-flex.align-items-center.justify-content-between.mb-4 .d-flex.gap-2 {
            width: 100%;
            justify-content: flex-start;
        }
        
        /* Mobile table improvements */
        .archive-card .table th,
        .archive-card .table td {
            padding: 0.5rem;
            font-size: 0.8rem;
            line-height: 1.3;
        }
        
        .archive-card .table th {
            white-space: nowrap;
            font-weight: 600;
        }
        
        .archive-card .table td {
            vertical-align: middle;
        }
        
        /* Mobile button improvements */
        .restore-btn,
        .trash-btn {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            min-height: 38px;
            touch-action: manipulation;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
            align-items: stretch;
        }
        
        .btn-group .restore-btn,
        .btn-group .trash-btn {
            width: 100%;
            justify-content: center;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-group .restore-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            font-weight: 500;
        }
        
        .btn-group .restore-btn:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        
        .btn-group .trash-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            font-weight: 500;
        }
        
        .btn-group .trash-btn:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        
        .btn-group .restore-btn:active {
            transform: translateY(0);
        }
        
        .btn-group .trash-btn:active {
            transform: translateY(0);
        }
    }
    
    /* Small Mobile */
    @media (max-width: 575.98px) {
        h1.mb-0 {
            font-size: 1.3rem;
            line-height: 1.2;
        }
        
        .container-fluid {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        
        .archive-scroll {
            height: 200px;
        }
        
        /* Enhanced typography for small screens */
        body {
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        .archive-card .table th,
        .archive-card .table td {
            padding: 0.4rem 0.5rem;
            font-size: 0.75rem;
            line-height: 1.2;
        }
        
        .archive-card .card-header {
            padding: 0.6rem 0.8rem;
            font-size: 0.85rem;
            line-height: 1.3;
        }
        
        .archive-card .card-body {
            padding: 0;
        }
        
        /* Better spacing for small screens */
        .archive-card {
            margin-bottom: 0.75rem;
        }
        
        .row .col-12:not(:last-child) .archive-card {
            margin-bottom: 1rem;
        }
        
        /* Improved readability */
        .text-muted {
            font-size: 0.8rem;
        }
        
        .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* Hide some columns on very small screens */
        .archive-card .table th:nth-child(3),
        .archive-card .table td:nth-child(3) {
            display: none;
        }
        
        .restore-btn,
        .trash-btn {
            padding: 0.6rem 0.8rem;
            font-size: 0.85rem;
            min-height: 44px; /* iOS touch target minimum */
            border-radius: 0.5rem;
        }
        
        .btn-group {
            gap: 0.75rem;
            padding: 0.25rem 0;
        }
        
        .btn-group .restore-btn,
        .btn-group .trash-btn {
            gap: 0.75rem;
        }
        
        .restore-label,
        .trash-label {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .restore-icon,
        .trash-icon {
            width: 18px;
            height: 18px;
        }
        
        .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
    }
    
    /* Large Desktop */
    @media (min-width: 1400px) {
        .archive-bg::before {
            width: 500px;
            height: 500px;
            top: -180px;
            right: -140px;
        }
        
        .archive-bg::after {
            width: 420px;
            height: 420px;
            bottom: -160px;
            left: -130px;
        }
        
        .archive-scroll {
            height: 400px;
        }
        
        .archive-card .table th,
        .archive-card .table td {
            padding: 0.8rem 1rem;
        }
    }
    
    /* Touch device optimizations */
    @media (hover: none) and (pointer: coarse) {
        .restore-btn:hover,
        .trash-btn:hover {
            transform: none;
            box-shadow: none;
        }
        
        .restore-btn:active,
        .trash-btn:active {
            transform: scale(0.95);
            transition: transform 0.1s ease;
        }
        
        .archive-card tbody tr:hover {
            background-color: transparent;
            transform: none;
            box-shadow: none;
        }
        
        .archive-card tbody tr:active {
            background-color: rgba(0, 123, 255, 0.1);
        }
    }
    
    /* High DPI displays */
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
        .archive-bg::before,
        .archive-bg::after {
            filter: blur(80px);
        }
    }
    
    /* Dark mode support (if implemented) */
    @media (prefers-color-scheme: dark) {
        .archive-bg::before,
        .archive-bg::after {
            background: rgba(0, 0, 0, 0.3);
        }
    }
    
    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        .archive-bg::before,
        .archive-bg::after {
            animation: none;
        }
        
        .archive-card tbody tr {
            transition: none;
        }
        
        .restore-btn,
        .trash-btn {
            transition: none;
        }
    }
    
    /* Print styles */
    @media print {
        .archive-bg::before,
        .archive-bg::after {
            display: none;
        }
        
        .archive-card {
            box-shadow: none;
            border: 1px solid #000;
        }
        
        .restore-btn,
        .trash-btn {
            display: none;
        }
        
        .archive-scroll {
            height: auto;
            overflow: visible;
        }
    }
    
    /* Record Details Styling */
    .record-details {
        line-height: 1.4;
    }
    
    .record-name {
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #0a0a0a;
    }
    
    .record-info {
        font-size: 0.85em;
        color: #666;
        margin-bottom: 0.1rem;
        word-break: break-word;
    }
    
    /* Mobile record details adjustments */
    @media (max-width: 767.98px) {
        .record-details {
            line-height: 1.3;
        }
        
        .record-name {
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }
        
        .record-info {
            font-size: 0.75rem;
            margin-bottom: 0.15rem;
        }
    }
    
    @media (max-width: 575.98px) {
        .record-name {
            font-size: 0.85rem;
        }
        
        .record-info {
            font-size: 0.7rem;
        }
    }
    
    /* Sticky table header */
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: var(--bs-gray-100);
    }
    
    /* Archive date display for mobile */
    @media (max-width: 767.98px) {
        .archive-date-mobile {
            display: block !important;
            font-size: 0.75rem;
            color: #666;
            margin-top: 0.25rem;
        }
    }
    
    /* Archive pagination styles */
    .archive-pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.25rem;
        padding: 0.75rem 0 1rem;
    }
    .archive-pagination .ap-prev,
    .archive-pagination .ap-next {
        color: #64748b;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
        transition: opacity 0.2s ease;
    }
    .archive-pagination .ap-disabled {
        opacity: 0.45;
        pointer-events: none;
    }
    .archive-pagination .ap-page {
        min-width: 2rem;
        height: 2rem;
        border-radius: 0.6rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        background: linear-gradient(135deg, #2563eb, #7c3aed);
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.22);
    }
</style>

<body class="sb-nav-fixed gradient-page archive-bg">
<?php include_once("../partials/navbar.php"); ?>
<div id="layoutSidenav">
    <?php include_once("../partials/sidebar.php"); ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 pt-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h1 id="archiveTitle" class="mb-0">Archive</h1>
                    
                </div>
                
                <div class="row">
                    <!-- Main Archive Content -->
                    <div class="col-12">

                <div class="alert alert-info border-0 shadow-sm" id="archiveReminder">
                    <strong>Reminders:</strong> <span id="archiveReminderText"></span>
                </div>

                <div class="row g-4">
                    <?php foreach ($sections as $key => $section): ?>
                        <div class="col-12">
                            <div class="card archive-card border-0 shadow-sm">
                                <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <span><?= htmlspecialchars($section['title']); ?></span>
                                    </div>
                                    <span class="badge bg-secondary"><?= count($section['records']); ?> archived</span>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (!empty($section['error'])): ?>
                                        <div class="p-4 text-danger"><?= htmlspecialchars($section['error']); ?></div>
                                    <?php elseif (empty($section['records'])): ?>
                                        <div class="p-4 text-center text-muted">No archived records.</div>
                                    <?php else: ?>
                                        <div class="table-responsive archive-scroll">
                                            <table class="table table-striped table-hover mb-0">
                                                <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width: 70px;" class="text-center">ID</th>
                                                    <th>Details</th>
                                                    <th style="width: 140px;" class="text-center d-none d-md-table-cell">Archived At</th>
                                                    <th style="width: 150px;" class="text-center">Actions</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($section['records'] as $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= htmlspecialchars($row['id']); ?></td>
                                                        <td>
                                                            <div class="record-details">
                                                                <?php if ($key === 'baptismal'): ?>
                                                                    <div class="record-name"><strong><?= htmlspecialchars($row['child_name']); ?></strong></div>
                                                                    <div class="record-info">Parents: <?= htmlspecialchars($row['parent_name1']); ?> &amp; <?= htmlspecialchars($row['parent_name2']); ?></div>
                                                                    <div class="record-info">Presider: <?= htmlspecialchars($row['bishop']); ?></div>
                                                                <?php elseif ($key === 'liberty'): ?>
                                                                    <div class="record-name"><strong><?= htmlspecialchars($row['child_name']); ?></strong></div>
                                                                    <div class="record-info">Marriage with: <?= htmlspecialchars($row['marriage_with']); ?></div>
                                                                    <div class="record-info">Year of Our Lord: <?= htmlspecialchars($row['year_of_our_lord']); ?></div>
                                                                <?php elseif ($key === 'confirmation'): ?>
                                                                    <div class="record-name"><strong><?= htmlspecialchars($row['child_name']); ?></strong></div>
                                                                    <div class="record-info">Date: <?= htmlspecialchars($row['confirmation_date']); ?></div>
                                                                    <div class="record-info">Bishop: <?= htmlspecialchars($row['bishop']); ?></div>
                                                                <?php elseif ($key === 'permit'): ?>
                                                                    <div class="record-name"><strong><?= htmlspecialchars($row['name']); ?></strong></div>
                                                                    <div class="record-info">Residence: <?= htmlspecialchars($row['residence']); ?></div>
                                                                    <div class="record-info">Birthdate: <?= htmlspecialchars($row['birthdate']); ?></div>
                                                                <?php elseif ($key === 'death'): ?>
                                                                    <div class="record-name"><strong><?= htmlspecialchars($row['name']); ?></strong></div>
                                                                    <div class="record-info">Date of Death: <?= htmlspecialchars($row['date_of_death']); ?></div>
                                                                    <div class="record-info">Cause: <?= htmlspecialchars($row['cause_of_death']); ?></div>
                                                                <?php endif; ?>
                                                                <?php if (!empty($section['has_archived_at'])): ?>
                                                                    <div class="archive-date-mobile d-md-none">
                                                                        Archived: <?= $row['archived_at'] ? htmlspecialchars($row['archived_at']) : '—'; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="archive-date-mobile d-md-none text-muted">
                                                                        Archived: N/A
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td class="text-center d-none d-md-table-cell">
                                                            <?php if (!empty($section['has_archived_at'])): ?>
                                                                <?= $row['archived_at'] ? htmlspecialchars($row['archived_at']) : '—'; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php
                                                            $restoreMap = [
                                                                'baptismal' => BASE_URL . '/baptismal/index.php?action=restore&id=' . urlencode($row['id']),
                                                                'liberty' => BASE_URL . '/liberty/index.php?action=restore&id=' . urlencode($row['id']),
                                                                'confirmation' => BASE_URL . '/confirmation/index.php?action=restore&id=' . urlencode($row['id']),
                                                                'permit' => BASE_URL . '/permit_to_marry/index.php?action=restore&id=' . urlencode($row['id']),
                                                                'death' => BASE_URL . '/cer_of_death/index.php?action=restore&id=' . urlencode($row['id']),
                                                            ];
                                                            $restoreUrl = $restoreMap[$key] ?? '#';
                                                            
                                                            $deleteMap = [
                                                                'baptismal' => BASE_URL . '/baptismal/index.php?action=permanent_delete&id=' . urlencode($row['id']),
                                                                'liberty' => BASE_URL . '/liberty/index.php?action=permanent_delete&id=' . urlencode($row['id']),
                                                                'confirmation' => BASE_URL . '/confirmation/index.php?action=permanent_delete&id=' . urlencode($row['id']),
                                                                'permit' => BASE_URL . '/permit_to_marry/index.php?action=permanent_delete&id=' . urlencode($row['id']),
                                                                'death' => BASE_URL . '/cer_of_death/index.php?action=permanent_delete&id=' . urlencode($row['id']),
                                                            ];
                                                            $deleteUrl = $deleteMap[$key] ?? '#';
                                                            ?>
                                                            <div class="btn-group">
                                                                <button class="restore-btn" onclick="if(confirm('Restore this record?')) window.location.href='<?= htmlspecialchars($restoreUrl) ?>'" >
                                                                    <span class="restore-icon" aria-hidden="true"></span>
                                                                    <span class="restore-label">Restore</span>
                                                                </button>
                                                                <button class="trash-btn" onclick="if(confirm('Are you sure you want to permanently delete this record? This action cannot be undone.')) window.location.href='<?= htmlspecialchars($deleteUrl) ?>'">
                                                                    <span class="trash-icon" aria-hidden="true">🗑️</span>
                                                                    <span class="trash-label">Delete</span>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="archive-pagination" aria-label="Pagination"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!empty($notices[$key])): ?>
                        <div class="alert alert-warning mt-2 mb-0">
                            <?= htmlspecialchars($notices[$key]); ?>
                        </div>
                    <?php endif; ?>
                    </div>
            </div>
                    </div>
        </main>
        <?php include_once("../partials/footer.php"); ?>
    </div>
</div>
<?php include_once("../partials/html.footer.php"); ?>
<script>
    // Typing animation for the title (guarded)
    (function(){
        const title = document.getElementById('archiveTitle');
        if (!title) return;
        const text = title.innerText;
        title.innerText = '';
        let i = 0;
        function typeWriter() {
            if (i < text.length) {
                title.innerText += text.charAt(i);
                i++;
                setTimeout(typeWriter, 50);
            }
        }
        // Start typing animation when page loads
        window.addEventListener('load', typeWriter);
    })();
    
    // Reminders typing animation (rotating messages)
    (function() {
        const el = document.getElementById('archiveReminderText');
        if (!el) return;

        const messages = [
            'Keep at least one recent backup stored off-site.',
            'Review archived records before permanent deletion.',
            'Use the Restore action to return a record to active lists.',
            'Exports are compressed automatically when ZIP is enabled.',
            'Regularly verify your backups by restoring to a test environment.'
        ];

        const typeSpeed = 40;
        const holdAfterTypeMs = 1400;
        const holdAfterEraseMs = 400;
        let msgIndex = 0;
        let charIndex = 0;
        let deleting = false;

        function tick() {
            const current = messages[msgIndex];
            if (!deleting) {
                el.textContent = current.slice(0, charIndex + 1);
                charIndex++;
                if (charIndex === current.length) {
                    setTimeout(() => { deleting = true; tick(); }, holdAfterTypeMs);
                    return;
                }
            } else {
                el.textContent = current.slice(0, charIndex - 1);
                charIndex--;
                if (charIndex === 0) {
                    deleting = false;
                    msgIndex = (msgIndex + 1) % messages.length;
                    setTimeout(tick, holdAfterEraseMs);
                    return;
                }
            }
            setTimeout(tick, typeSpeed);
        }

        // start after load to avoid layout shift
        window.addEventListener('load', () => setTimeout(tick, 300));
    })();

    // Client-side pagination for each archive section (limit = 5)
    (function() {
        function setupPagination(card) {
            const tbody = card.querySelector('tbody');
            if (!tbody) return;
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const limit = 5;
            if (rows.length <= limit) return; // no need to paginate

            const pager = card.querySelector('.archive-pagination');
            if (!pager) return;

            const prev = document.createElement('span');
            prev.className = 'ap-prev';
            prev.textContent = 'Previous';

            const page = document.createElement('span');
            page.className = 'ap-page';
            page.textContent = '1';

            const next = document.createElement('span');
            next.className = 'ap-next';
            next.textContent = 'Next';

            pager.append(prev, page, next);

            let current = 1;
            const totalPages = Math.ceil(rows.length / limit);

            function render() {
                rows.forEach((row, idx) => {
                    const pageIndex = Math.floor(idx / limit) + 1;
                    row.style.display = pageIndex === current ? '' : 'none';
                });
                page.textContent = String(current);
                prev.classList.toggle('ap-disabled', current === 1);
                next.classList.toggle('ap-disabled', current === totalPages);
            }

            prev.addEventListener('click', () => { if (current > 1) { current--; render(); } });
            next.addEventListener('click', () => { if (current < totalPages) { current++; render(); } });

            render();
        }

        // Initialize after load to ensure tables are in DOM
        window.addEventListener('load', () => {
            document.querySelectorAll('.archive-card').forEach(setupPagination);
        });
    })();

    // Enhanced mobile touch interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Detect touch device
        const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        
        if (isTouchDevice) {
            // Add touch-specific classes
            document.body.classList.add('touch-device');
            
            // Improve button touch feedback
            const buttons = document.querySelectorAll('.restore-btn, .trash-btn');
            buttons.forEach(button => {
                // Add touch start effect
                button.addEventListener('touchstart', function(e) {
                    this.style.transform = 'scale(0.95)';
                    this.style.transition = 'transform 0.1s ease';
                });
                
                // Add touch end effect
                button.addEventListener('touchend', function(e) {
                    this.style.transform = 'scale(1)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 100);
                });
                
                // Prevent double-tap zoom on buttons
                button.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    if (this.onclick) {
                        this.onclick();
                    }
                });
            });
            
            // Improve table scrolling on touch devices
            const scrollContainers = document.querySelectorAll('.archive-scroll');
            scrollContainers.forEach(container => {
                let isScrolling = false;
                
                container.addEventListener('touchstart', function() {
                    isScrolling = true;
                    this.style.overflow = 'auto';
                });
                
                container.addEventListener('touchend', function() {
                    setTimeout(() => {
                        isScrolling = false;
                    }, 100);
                });
                
                // Improve momentum scrolling
                this.style.webkitOverflowScrolling = 'touch';
                this.style.overflow = 'auto';
            });
            
            // Add swipe gestures for navigation (optional)
            let touchStartX = 0;
            let touchEndX = 0;
            
            document.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            });
            
            document.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            });
            
            function handleSwipe() {
                const swipeThreshold = 50;
                const diff = touchStartX - touchEndX;
                
                if (Math.abs(diff) > swipeThreshold) {
                    // Could add navigation logic here if needed
                    console.log(diff > 0 ? 'Swiped left' : 'Swiped right');
                }
            }
        }
        
        // Performance optimizations
        // Debounce scroll events
        let scrollTimeout;
        const scrollContainers = document.querySelectorAll('.archive-scroll');
        
        scrollContainers.forEach(container => {
            container.addEventListener('scroll', function() {
                if (scrollTimeout) {
                    window.cancelAnimationFrame(scrollTimeout);
                }
                
                scrollTimeout = window.requestAnimationFrame(function() {
                    // Handle scroll-based optimizations
                    const scrollTop = container.scrollTop;
                    const scrollHeight = container.scrollHeight;
                    const clientHeight = container.clientHeight;
                    
                    // Add/remove classes based on scroll position
                    if (scrollTop > 50) {
                        container.classList.add('scrolled');
                    } else {
                        container.classList.remove('scrolled');
                    }
                });
            });
        });
        
        // Lazy loading for tables if they become too large
        const observerOptions = {
            root: null,
            rootMargin: '50px',
            threshold: 0.1
        };
        
        const tableObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('table-visible');
                }
            });
        }, observerOptions);
        
        // Observe all tables
        const tables = document.querySelectorAll('.archive-scroll table');
        tables.forEach(table => {
            tableObserver.observe(table);
        });
        
        // Add keyboard navigation for accessibility
        document.addEventListener('keydown', function(e) {
            // ESC key to close modals or reset focus
            if (e.key === 'Escape') {
                // Could add modal closing logic here
                console.log('ESC pressed');
            }
            
            // Tab navigation improvements
            if (e.key === 'Tab') {
                // Ensure proper focus management
                const focusableElements = document.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                
                // Add visual indicators for better accessibility
                focusableElements.forEach(element => {
                    element.addEventListener('focus', function() {
                        this.classList.add('keyboard-focused');
                    });
                    
                    element.addEventListener('blur', function() {
                        this.classList.remove('keyboard-focused');
                    });
                });
            }
        });
    });
</script>
</body>
</html>

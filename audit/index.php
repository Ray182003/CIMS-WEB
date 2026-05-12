<?php
$title = "Audit Logs";
require_once "../security_helper.php";

// Require authentication first
requireAuth();

// Require admin or staff permission
requirePermission('audit_view');
if (!($security && ($security->hasPermission($_SESSION['user_id'], 'audit_view') || $_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff'))) {
    header('Location: ' . BASE_URL . '/dashboard/');
    exit;
}

// Handle delete action (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_log_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        $deleteId = (int) $_POST['delete_log_id'];
        if ($deleteId > 0) {
            $stmt = $conn->prepare("DELETE FROM audit_logs WHERE id = :id");
            $stmt->execute([':id' => $deleteId]);
            $deleteMessage = 'Audit log entry deleted successfully.';
        }
    }
}

// Handle filtering
$filters = [
    'user_id' => $_GET['user_id'] ?? '',
    'action' => $_GET['action'] ?? '',
    'module' => $_GET['module'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Build query
$whereConditions = [];
$params = [];

if (!empty($filters['user_id'])) {
    $whereConditions[] = "al.user_id = :user_id";
    $params[':user_id'] = $filters['user_id'];
}

if (!empty($filters['action'])) {
    $whereConditions[] = "al.action LIKE :action";
    $params[':action'] = '%' . $filters['action'] . '%';
}

if (!empty($filters['module'])) {
    $whereConditions[] = "al.module = :module";
    $params[':module'] = $filters['module'];
}

if (!empty($filters['date_from'])) {
    $whereConditions[] = "DATE(al.created_at) >= :date_from";
    $params[':date_from'] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $whereConditions[] = "DATE(al.created_at) <= :date_to";
    $params[':date_to'] = $filters['date_to'];
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get audit logs with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

$query = "SELECT 
                al.*,
                COALESCE(target_user.username, u.username) AS username,
                CASE 
                    WHEN al.action = 'LOGIN_FAILED' AND al.new_values IS NOT NULL THEN
                        JSON_UNQUOTE(JSON_EXTRACT(al.new_values, '$.username'))
                    ELSE u.username
                END as attempted_username
          FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          LEFT JOIN users target_user ON al.module = 'users' AND al.record_id = target_user.id
          $whereClause 
          ORDER BY al.created_at DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id $whereClause";
$stmt = $conn->prepare($countQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$totalLogs = $stmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

// Get users for filter dropdown
$users = $conn->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_OBJ);

// Get unique modules
$modules = $conn->query("SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);

include "../partials/html.head.php";
?>

<style>
    .audit-page {
        min-height: 100vh;
    }

    .audit-header h1 {
        font-weight: 700;
        letter-spacing: 0.03em;
        background: linear-gradient(120deg, #312e81, #4338ca, #3b82f6);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .audit-card {
        border-radius: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.55);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
    }

    .audit-card-header {
        background: transparent;
        border-bottom: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .audit-card-body {
        padding: 1.5rem 1.75rem;
    }

    .filter-card {
        background: rgba(255, 255, 255, 0.92);
    }

    .quick-filter-btn {
        border-radius: 999px;
        padding-inline: 1rem;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .quick-filter-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(29, 78, 216, 0.25);
    }

    .audit-summary {
        border-radius: 999px;
        background: rgba(59, 130, 246, 0.12);
        border: none;
        color: #1d4ed8;
        font-weight: 500;
    }

    .audit-table thead th {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom-width: 1px;
    }

    .audit-table tbody tr {
        transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
    }

    .audit-table tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.06);
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12);
        transform: translateY(-2px);
    }

    .audit-card .badge {
        border-radius: 999px;
        padding: 0.4rem 0.75rem;
        font-size: 0.7rem;
        letter-spacing: 0.05em;
    }

    .audit-card .btn-outline-danger {
        border-radius: 999px;
        width: 2.1rem;
        height: 2.1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .audit-modern-pagination {
        margin-top: 1rem;
    }

    .audit-modern-pagination-inner {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.75rem;
    }

    .audit-page-link {
        color: #64748b;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
    }

    .audit-page-link:hover {
        color: #0f172a;
    }

    .audit-page-link.disabled {
        pointer-events: none;
        opacity: 0.45;
    }

    .audit-page-current {
        min-width: 2.5rem;
        height: 2.5rem;
        border-radius: 999px;
        background: linear-gradient(135deg, #4f46e5, #3b82f6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.95rem;
        box-shadow: 0 12px 25px rgba(37, 99, 235, 0.45);
    }

    @media (max-width: 575.98px) {
        .audit-modern-pagination-inner {
            gap: 1.25rem;
        }

        .audit-page-current {
            min-width: 2.25rem;
            height: 2.25rem;
        }
    }
</style>

<body class="sb-nav-fixed gradient-page">
    <?php include_once("../partials/navbar.php"); ?>
    <div id="layoutSidenav">
        <?php include_once("../partials/sidebar.php"); ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 py-4 audit-page">
                    <div class="d-flex justify-content-between align-items-center mb-4 audit-header">
                        <h1 class="mt-4">Audit Logs</h1>
                    </div>

                    <!-- Quick Filters -->
                    <div class="card mb-3 audit-card filter-card">
                        <div class="card-body audit-card-body">
                            <h6 class="card-title mb-3">Quick Filters</h6>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="?action=LOGIN" class="btn btn-success btn-sm quick-filter-btn">
                                    <i class="fas fa-sign-in-alt"></i> Logins Only
                                </a>
                                <a href="?action=LOGOUT" class="btn btn-warning btn-sm quick-filter-btn">
                                    <i class="fas fa-sign-out-alt"></i> Logouts Only
                                </a>
                                <a href="?action=LOGIN_FAILED" class="btn btn-danger btn-sm quick-filter-btn">
                                    <i class="fas fa-exclamation-triangle"></i> Failed Logins
                                </a>
                                <a href="?module=auth" class="btn btn-info btn-sm quick-filter-btn">
                                    <i class="fas fa-shield-alt"></i> Authentication
                                </a>
                                <a href="?" class="btn btn-outline-secondary btn-sm quick-filter-btn">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Delete feedback -->
                    <?php if (!empty($deleteMessage)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($deleteMessage); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Results Summary -->
                    <div class="alert audit-summary">
                        <i class="fas fa-info-circle"></i>
                        Showing <?php echo count($logs); ?> of <?php echo $totalLogs; ?> audit logs (<?php echo $limit; ?> per page)
                    </div>

                    <!-- Audit Logs Table -->
                    <div class="card audit-card table-card">
                        <div class="card-header audit-card-header">
                            <i class="fas fa-history me-1"></i> System Activity Log
                        </div>
                        <div class="card-body audit-card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm audit-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Module</th>
                                            <th>Record ID</th>
                                            <th class="text-center" style="width: 110px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y g:i:s A', strtotime($log->created_at)); ?></td>
                                            <td>
                                                <?php if ($log->action == 'LOGIN_FAILED' && $log->attempted_username): ?>
                                                    <strong><?php echo htmlspecialchars($log->attempted_username); ?></strong><br>
                                                    <small class="text-danger">Failed Login Attempt</small>
                                                <?php elseif ($log->username): ?>
                                                    <strong><?php echo htmlspecialchars($log->username); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($log->username); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">System</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $log->action == 'LOGIN' ? 'success' : 
                                                         ($log->action == 'LOGOUT' ? 'warning' : 
                                                         ($log->action == 'LOGIN_FAILED' ? 'danger' : 
                                                         ($log->action == 'DELETE' ? 'danger' : 'primary'))); 
                                                ?>">
                                                    <?php echo htmlspecialchars($log->action); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $log->module ? ucfirst(htmlspecialchars($log->module)) : '-'; ?></td>
                                            <td><?php echo $log->record_id ?? '-'; ?></td>
                                            <td class="text-center">
                                                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this audit log entry? This action cannot be undone.');">
                                                        <input type="hidden" name="delete_log_id" value="<?php echo (int) $log->id; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">No actions</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                            <nav class="audit-modern-pagination" aria-label="Audit logs pagination">
                                <div class="audit-modern-pagination-inner">
                                    <?php
                                    $prevPage = max(1, $page - 1);
                                    $nextPage = min($totalPages, $page + 1);
                                    ?>

                                    <a class="audit-page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo $page <= 1 ? '#' : '?' . http_build_query(array_merge($filters, ['page' => $prevPage])); ?>">
                                        Previous
                                    </a>

                                    <span class="audit-page-current"><?php echo $page; ?></span>

                                    <a class="audit-page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo $page >= $totalPages ? '#' : '?' . http_build_query(array_merge($filters, ['page' => $nextPage])); ?>">
                                        Next
                                    </a>
                                </div>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', '1');
            window.open('?' + params.toString(), '_blank');
        }
    </script>

    <?php include_once("../partials/html.footer.php"); ?>
</body>
</html>

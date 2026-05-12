<?php
$title = "Print Logs";
require_once "../security_helper.php";
requireAuth();

$certificateTypes = [
    'baptismal' => 'Baptismal',
    'confirmation' => 'Confirmation',
    'sol' => 'Status of Liberty',
    'ptm' => 'Permit to Marry',
    'death' => 'Death Certificate'
];

$filterType = $_GET['certificate_type'] ?? '';

$whereClauses = [];
$params = [];

if (!empty($filterType) && isset($certificateTypes[$filterType])) {
    $whereClauses[] = 'pl.certificate_type = :certificate_type';
    $params[':certificate_type'] = $filterType;
}

$sql = 'SELECT pl.*, u.username FROM print_logs pl LEFT JOIN users u ON pl.user_id = u.id';
if (!empty($whereClauses)) {
    $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
}
$sql .= ' ORDER BY pl.created_at DESC LIMIT 200';

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_OBJ);

include "../partials/html.head.php";
?>
<body class="sb-nav-fixed gradient-page">
    <?php include_once("../partials/navbar.php"); ?>
    <style>
        .print-logs-page {
            min-height: 100vh;
            padding-bottom: 2rem;
        }

        .print-logs-header h1 {
            font-weight: 700;
            letter-spacing: 0.03em;
            background: linear-gradient(120deg, #1e3a8a, #2563eb, #0ea5e9);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .print-logs-header p {
            font-size: 0.9rem;
        }

        .print-logs-card {
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 18px 35px rgba(15, 23, 42, 0.18);
        }

        .print-logs-card .card-body {
            padding: 1.25rem 1.5rem;
        }

        .filter-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
        }

        .table-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
        }

        .print-logs-page .form-select,
        .print-logs-page .form-control {
            border-radius: 999px;
        }

        .print-logs-page .btn-primary {
            border-radius: 999px;
            background: linear-gradient(120deg, #2563eb, #0ea5e9);
            border: none;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.35);
        }

        .print-logs-page .btn-primary:hover {
            background: linear-gradient(120deg, #1d4ed8, #0284c7);
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.45);
        }

        .print-logs-page .btn-outline-secondary {
            border-radius: 999px;
        }

        .table-card .table thead tr th {
            border-bottom-width: 1px;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .table-card .table tbody tr {
            transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .table-card .table tbody tr:hover {
            background-color: rgba(37, 99, 235, 0.06);
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12);
            transform: translateY(-2px);
        }

        .table-card .badge {
            border-radius: 999px;
            padding: 0.4rem 0.75rem;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
        }

        .printed-at-date {
            font-weight: 600;
        }

        .printed-at-time {
            font-size: 0.8rem;
        }
    </style>
    <div id="layoutSidenav">
        <?php include_once("../partials/sidebar.php"); ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 pt-3 print-logs-page">
                    <div class="d-flex align-items-center mb-3">
                        <div class="print-logs-header">
                            <h1 class="mb-0">Print Logs</h1>
                            <p class="text-muted mb-0">Latest certificate print activity (showing up to 200 most recent entries)</p>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mb-4 print-logs-card filter-card">
                        <div class="card-body">
                            <form class="row g-3 align-items-end" method="get">
                                <div class="col-sm-6 col-md-4 col-lg-3">
                                    <label for="certificate_type" class="form-label">Certificate Type</label>
                                    <select class="form-select" id="certificate_type" name="certificate_type">
                                        <option value="">All types</option>
                                        <?php foreach ($certificateTypes as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $value === $filterType ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-4 col-lg-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i> Apply</button>
                                    <a href="<?php echo BASE_URL; ?>/print_logs/" class="btn btn-outline-secondary"><i class="fas fa-rotate-left me-1"></i> Refresh</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 print-logs-card table-card">
                        <div class="card-body">
                            <?php if (empty($logs)): ?>
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-1"></i> No print activity found for the selected filters.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">Printed At</th>
                                                <th scope="col">Certificate</th>
                                                <th scope="col">Requested By</th>
                                                <th scope="col">Certificate ID</th>
                                                <th scope="col">Copies</th>
                                                <th scope="col">Processed By</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($logs as $log): ?>
                                                <tr>
                                                    <td>
                                                        <span class="fw-semibold d-block">
                                                            <?php echo date('M d, Y', strtotime($log->created_at)); ?>
                                                        </span>
                                                        <small class="text-muted">
                                                            <?php echo date('g:i A', strtotime($log->created_at)); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold text-uppercase">
                                                            <?php echo htmlspecialchars($log->certificate_type); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($log->requested_by); ?></td>
                                                    <td>#<?php echo htmlspecialchars($log->certificate_id); ?></td>
                                                    <td><?php echo (int) $log->copies; ?></td>
                                                    <td>
                                                        <?php if (!empty($log->username)): ?>
                                                            <span class="fw-semibold"><?php echo htmlspecialchars($log->username); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Unknown user</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $printUrl = BASE_URL . '/print_templates/' . urlencode($log->certificate_type) . '_print.php?id=' . urlencode($log->certificate_id);
                                                        ?>
                                                        <button 
                                                            type="button"
                                                            class="btn btn-sm btn-outline-primary print-log-btn"
                                                            data-log-id="<?php echo (int) $log->id; ?>"
                                                            data-print-url="<?php echo htmlspecialchars($printUrl, ENT_QUOTES); ?>"
                                                        >
                                                            <i class="fas fa-print me-1"></i> Print
                                                        </button>
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
            </main>
        </div>
    </div>

    <?php include "../partials/html.footer.php"; ?>
    <script>
        (function() {
            const buttons = document.querySelectorAll('.print-log-btn');
            if (!buttons.length) return;

            const incrementCopies = async (logId, increment) => {
                try {
                    const response = await fetch('<?php echo BASE_URL; ?>/api/print_logs/increment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ log_id: logId, increment })
                    });

                    if (!response.ok) {
                        throw new Error('Failed to update copies');
                    }

                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.error || 'Failed to update copies');
                    }

                    return data.copies;
                } catch (error) {
                    console.error(error);
                    alert('Hindi na-update ang bilang ng kopya. Pakisubukan muli.');
                    return null;
                }
            };

            buttons.forEach(button => {
                button.addEventListener('click', async () => {
                    const logId = button.dataset.logId;
                    const printUrl = button.dataset.printUrl;
                    const updatedCopies = await incrementCopies(logId, 1);

                    if (updatedCopies === null) {
                        return;
                    }

                    const copiesCell = button.closest('tr')?.querySelector('td:nth-child(5)');
                    if (copiesCell) {
                        copiesCell.textContent = updatedCopies;
                    }

                    window.open(printUrl, '_blank');
                });
            });
        })();
    </script>
</body>

</html>

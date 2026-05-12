<?php
$title = "Dashboard";
require_once "../security_helper.php";


if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/loging.php');
    exit();
}

include "../partials/html.head.php";

function getRecordCount(PDO $conn, string $tableName, bool $excludeArchived = false): int
{
    $sql = "SELECT COUNT(*) AS count FROM $tableName";

    if ($excludeArchived) {
        $sql .= " WHERE is_archived = 0";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getYearCount(PDO $conn, string $tableName, string $dateColumn, bool $excludeArchived = false): int
{
    $sql = "SELECT COUNT(*) AS count FROM $tableName WHERE YEAR($dateColumn) = YEAR(CURDATE())";

    if ($excludeArchived) {
        $sql .= " AND is_archived = 0";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getMonthlyCounts(PDO $conn, string $tableName, string $dateColumn, bool $excludeArchived = false): array
{
    $conditions = "WHERE YEAR($dateColumn) = YEAR(CURDATE())";
    if ($excludeArchived) {
        $conditions .= " AND is_archived = 0";
    }

    $sql = "SELECT MONTH($dateColumn) AS m, COUNT(*) AS c FROM $tableName $conditions GROUP BY MONTH($dateColumn)";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = array_fill(1, 12, 0);
    foreach ($rows as $r) {
        $m = (int) ($r['m'] ?? 0);
        $c = (int) ($r['c'] ?? 0);
        if ($m >= 1 && $m <= 12) {
            $out[$m] = $c;
        }
    }

    return array_values($out);
}

function buildSparkPath(array $values, int $width, int $height, int $padding = 10): string
{
    $n = count($values);
    if ($n < 2) {
        return '';
    }

    $max = max(1, (int) max($values));
    $min = (int) min($values);
    $range = max(1, $max - $min);
    $innerW = max(1, $width - ($padding * 2));
    $innerH = max(1, $height - ($padding * 2));
    $stepX = $innerW / ($n - 1);

    $parts = [];
    for ($i = 0; $i < $n; $i++) {
        $x = $padding + ($stepX * $i);
        $norm = ($values[$i] - $min) / $range;
        $y = $padding + (1 - $norm) * $innerH;
        $parts[] = sprintf('%.2f %.2f', $x, $y);
    }

    return 'M ' . implode(' L ', $parts);
}

$baptismal_count = getRecordCount($conn, "baptismal_records");
$liberty_count = getRecordCount($conn, "liberty_records");
$confirmation_count = getRecordCount($conn, "confirmation_records", true);
$marriage_permits_count = getRecordCount($conn, "marriage_permits");
$death_certificates_count = getRecordCount($conn, "death_certificates");

$baptismal_year = getYearCount($conn, "baptismal_records", "baptism_date");
$liberty_year = getYearCount($conn, "liberty_records", "year_of_our_lord");
$confirmation_year = getYearCount($conn, "confirmation_records", "confirmation_date", true);
$marriage_year = getYearCount($conn, "marriage_permits", "birthdate");
$death_year = getYearCount($conn, "death_certificates", "date_of_death");

$monthly_baptismal = getMonthlyCounts($conn, "baptismal_records", "baptism_date");
$monthly_liberty = getMonthlyCounts($conn, "liberty_records", "year_of_our_lord");
$monthly_confirmation = getMonthlyCounts($conn, "confirmation_records", "confirmation_date", true);
$monthly_marriage = getMonthlyCounts($conn, "marriage_permits", "birthdate");
$monthly_death = getMonthlyCounts($conn, "death_certificates", "date_of_death");

$recentRecordsSql = "(
    SELECT 'Baptismal' AS module, child_name AS person_name, baptism_date AS event_date, created_at
    FROM baptismal_records
    UNION ALL
    SELECT 'Status of Liberty' AS module, child_name AS person_name, year_of_our_lord AS event_date, created_at
    FROM liberty_records
    UNION ALL
    SELECT 'Confirmation' AS module, child_name AS person_name, confirmation_date AS event_date, created_at
    FROM confirmation_records
    UNION ALL
    SELECT 'Permit to Marry' AS module, name AS person_name, birthdate AS event_date, created_at
    FROM marriage_permits
    UNION ALL
    SELECT 'Death' AS module, name AS person_name, date_of_death AS event_date, created_at
    FROM death_certificates
) AS recent_records";

$recentStmt = $conn->prepare("SELECT * FROM $recentRecordsSql ORDER BY created_at DESC LIMIT 5");
$recentStmt->execute();
$recentRecords = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
$recentRecordsCount = count($recentRecords);

$eventsStmt = $conn->prepare("SELECT id, type, event_date, time, description, scheduled_by FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC, time ASC LIMIT 4");
$eventsStmt->execute();
$upcomingEvents = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
$eventsCount = count($upcomingEvents);
?>

<style>
    .dashboard-cards {
        perspective: 1100px;
        perspective-origin: 50% 30%;
    }

    .hover-shadow {
        position: relative;
        transform-style: preserve-3d;
        transition: transform 0.22s ease, box-shadow 0.22s ease, filter 0.22s ease;
        box-shadow:
            0 10px 18px rgba(0, 0, 0, 0.3),
            0 26px 60px rgba(0, 0, 0, 0.42);
        border: 1px solid rgba(0, 0, 0, 0.15);
    }

    .hover-shadow::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: radial-gradient(700px 260px at 0% 0%, rgba(255,255,255,0.24), rgba(255,255,255,0) 55%);
        opacity: 0;
        transform: translateZ(18px);
        pointer-events: none;
        transition: opacity 0.22s ease;
    }

    .hover-shadow:hover,
    .hover-shadow.is-active {
        box-shadow:
            0 18px 28px rgba(0, 0, 0, 0.38),
            0 34px 80px rgba(0, 0, 0, 0.52);
        transform: translateY(-6px) rotateX(6deg) rotateY(-7deg) scale(1.02);
    }

    .hover-shadow:hover::after,
    .hover-shadow.is-active::after {
        opacity: 1;
    }

    .hover-shadow.is-active {
        outline: 2px solid var(--glow-color, rgba(255, 255, 255, 0.85));
        outline-offset: 4px;
        box-shadow:
            0 18px 28px rgba(0, 0, 0, 0.4),
            0 38px 90px rgba(0, 0, 0, 0.58),
            0 0 30px var(--glow-color, rgba(255, 255, 255, 0.6));
        transform: translateY(-8px) rotateX(7deg) rotateY(-9deg) scale(1.06);
        filter: brightness(1.08);
    }

    .card-icon {
        width: 56px;
        height: 56px;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--icon-gradient-start, rgba(255, 255, 255, 0.35)), var(--icon-gradient-end, rgba(255, 255, 255, 0.15)));
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: inset 0 2px 6px rgba(255, 255, 255, 0.35), inset 0 -4px 8px rgba(0, 0, 0, 0.25), 0 6px 14px rgba(0, 0, 0, 0.18);
        backdrop-filter: blur(2px);
    }

    .dashboard-bg {
        min-height: 100vh;
        position: relative;
        padding-bottom: 4rem;
        overflow: hidden;
        background: transparent;
    }

    .dashboard-bg::before,
    .dashboard-bg::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        pointer-events: none;
        opacity: 0.3;
        background: rgba(255, 255, 255, 0.8);
    }

    .dashboard-bg::before {
        width: 460px;
        height: 460px;
        top: -160px;
        right: -120px;
    }

    .dashboard-bg::after {
        width: 380px;
        height: 380px;
        bottom: -140px;
        left: -110px;
    }

    .dashboard-bg>* {
        position: relative;
        z-index: 1;
    }

    .card-icon i {
        color: var(--icon-color, #ffffff);
        font-size: 1.6rem;
        filter: drop-shadow(0 3px 4px rgba(0, 0, 0, 0.3));
    }

    .typing-title {
        font-family: 'Fredoka One', 'Poppins', 'Segoe UI', sans-serif;
        font-weight: 700;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: #0a0a0a;
        text-shadow:
            0 2px 0 rgba(255, 255, 255, 0.4),
            0 4px 10px rgba(0, 0, 0, 0.45);
        transition: opacity 0.4s ease;
    }

    .dash-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.25rem;}
    .dash-title{font-family:'Poppins','Segoe UI',sans-serif;font-weight:900;margin:0;color:#0a0a0a;letter-spacing:.4px;}
    .dash-subtitle{margin:.25rem 0 0;color:rgba(10,10,10,.65);font-weight:650;}
    .date-pill{display:inline-flex;align-items:center;gap:.55rem;padding:.85rem 1rem;border-radius:16px;background:rgba(255,255,255,.92);border:1px solid rgba(15,40,77,.12);box-shadow:0 18px 44px rgba(15,40,77,.14);backdrop-filter:blur(8px);font-weight:750;color:rgba(18,44,90,.92);min-width:220px;justify-content:space-between;}
    .date-left{display:inline-flex;align-items:center;gap:.55rem;}
    .date-meta{display:flex;flex-direction:column;line-height:1.1;}
    .date-big{font-weight:900;font-size:.95rem;}
    .date-small{font-weight:700;opacity:.75;font-size:.8rem;}
    .stat-card{border-radius:18px;border:1px solid rgba(15,40,77,.1);background:rgba(255,255,255,.92);box-shadow:0 18px 44px rgba(15,40,77,.14);backdrop-filter:blur(8px);transition:transform .18s ease,box-shadow .18s ease;}
    .stat-card:hover{transform:translateY(-2px);box-shadow:0 26px 60px rgba(15,40,77,.18);}
    .stat-inner{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:1rem 1rem .85rem;}
    .panel-card{border-radius:18px;border:1px solid rgba(15,40,77,.1);background:rgba(255,255,255,.92);box-shadow:0 18px 44px rgba(15,40,77,.14);backdrop-filter:blur(8px);transition:transform .18s ease,box-shadow .18s ease;}
    .panel-card:hover{transform:translateY(-2px);box-shadow:0 26px 60px rgba(15,40,77,.18);}
    .col-xl-3>a,.col-md-6>a{position:relative;z-index:1;display:block;height:100%;}
    .panel-title{font-weight:800;font-size:.95rem;color:rgba(18,44,90,.92);margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid rgba(15,40,77,.08);}
    .record-item{display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid rgba(15,40,77,.06);}
    .record-item:last-child{border-bottom:none;}
    .record-badge{font-size:.7rem;font-weight:700;padding:.15rem .5rem;border-radius:999px;text-transform:uppercase;letter-spacing:.03em;}
    .event-item{display:flex;align-items:flex-start;gap:.75rem;padding:.5rem 0;border-bottom:1px solid rgba(15,40,77,.06);}
    .event-item:last-child{border-bottom:none;}
    .event-date{font-size:.75rem;font-weight:700;color:rgba(18,44,90,.7);min-width:70px;}
    .event-info{flex:1;}
    .event-type{font-weight:700;font-size:.85rem;color:rgba(18,44,90,.92);}
    .event-time{font-size:.75rem;color:rgba(18,44,90,.6);}
    .quick-btn{display:flex;align-items:center;gap:.5rem;padding:.6rem .9rem;border-radius:12px;font-weight:700;font-size:.85rem;text-decoration:none;transition:transform .16s ease,box-shadow .16s ease;}
    .quick-btn:hover{transform:translateY(-1px);box-shadow:0 14px 26px rgba(15,40,77,.14);}
    .chart-container{position:relative;height:180px;padding:1rem;background:rgba(15,40,77,.02);border-radius:12px;}
    .chart-label{font-size:.7rem;font-weight:700;color:rgba(18,44,90,.5);text-transform:uppercase;letter-spacing:.05em;}

    @media (prefers-reduced-motion: reduce) {
        .hover-shadow,
        .hover-shadow::after {
            transition: none !important;
        }

        .hover-shadow:hover,
        .hover-shadow.is-active {
            transform: none !important;
        }
    }
</style>

<body class="sb-nav-fixed gradient-page">
    <?php include_once("../partials/navbar.php"); ?>
    <div id="layoutSidenav">
        <?php include_once("../partials/sidebar.php"); ?>
        <div id="layoutSidenav_content" class="dashboard-bg">
            <main>
                <div class="container-fluid px-4 py-4">
                    <div class="dash-header">
                        <div>
                            <h1 class="dash-title">Dashboard</h1>
                            <p class="dash-subtitle">Here's what's happening in your parish today.</p>
                        </div>
                        <div class="date-pill" aria-label="Today">
                            <div class="date-left">
                                <i class="fa-solid fa-calendar-days"></i>
                                <div class="date-meta">
                                    <div class="date-big"><?php echo date('M d, Y'); ?></div>
                                    <div class="date-small"><?php echo date('l'); ?></div>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-down" style="opacity:.55"></i>
                        </div>
                    </div>
                    <div class="row dashboard-cards">
                        <?php
                        $cards = [
                            [
                                'title' => 'Baptismal',
                                'count' => $baptismal_count,
                                'this_year' => $baptismal_year,
                                'href' => BASE_URL . '/baptismal/',
                                'spark' => buildSparkPath($monthly_baptismal, 120, 36),
                                'color' => 'primary',
                                'icon' => 'fa-solid fa-cross',
                                'glow' => '#4aa3ff',
                                'icon_gradient_start' => '#ffffff',
                                'icon_gradient_end' => 'rgba(255, 255, 255, 0.25)',
                                'icon_color' => '#0d47ff'
                            ],
                            [
                                'title' => 'Status of Liberty',
                                'count' => $liberty_count,
                                'this_year' => $liberty_year,
                                'href' => BASE_URL . '/liberty/',
                                'spark' => buildSparkPath($monthly_liberty, 120, 36),
                                'color' => 'warning',
                                'icon' => 'fa-dove',
                                'glow' => '#ffe680',
                                'icon_gradient_start' => '#fff3b0',
                                'icon_gradient_end' => '#ffcf57',
                                'icon_color' => '#c77f00'
                            ],
                            [
                                'title' => 'Confirmation',
                                'count' => $confirmation_count,
                                'this_year' => $confirmation_year,
                                'href' => BASE_URL . '/confirmation/',
                                'spark' => buildSparkPath($monthly_confirmation, 120, 36),
                                'color' => 'success',
                                'icon' => 'fa-check-circle',
                                'glow' => '#6bf2c1',
                                'icon_gradient_start' => '#c1ffe8',
                                'icon_gradient_end' => '#3ddc97',
                                'icon_color' => '#0a7f5a'
                            ],
                            [
                                'title' => 'Permit to Marry',
                                'count' => $marriage_permits_count,
                                'this_year' => $marriage_year,
                                'href' => BASE_URL . '/permit_to_marry/',
                                'spark' => buildSparkPath($monthly_marriage, 120, 36),
                                'color' => 'danger',
                                'icon' => 'fa-heart',
                                'glow' => '#ff9aa9',
                                'icon_gradient_start' => '#ffd6df',
                                'icon_gradient_end' => '#ff6f91',
                                'icon_color' => '#b0003a'
                            ],
                            [
                                'title' => 'Death',
                                'count' => $death_certificates_count,
                                'this_year' => $death_year,
                                'href' => BASE_URL . '/cer_of_death/',
                                'spark' => buildSparkPath($monthly_death, 120, 36),
                                'color' => 'dark',
                                'icon' => 'fa-skull-crossbones',
                                'glow' => '#c0c0c0',
                                'icon_gradient_start' => '#f2f4f6',
                                'icon_gradient_end' => '#99a1a7',
                                'icon_color' => '#43464a'
                            ],
                        ];

                        foreach ($cards as $index => $card): ?>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="<?= htmlspecialchars($card['href']); ?>" class="text-decoration-none d-block">
                                    <div class="card stat-card shadow h-100 py-2" style="border-radius: 1rem; cursor: pointer; background: linear-gradient(135deg, <?= $card['icon_gradient_start']; ?>, <?= $card['icon_gradient_end']; ?>);">
                                        <div class="card-body">
                                            <div class="stat-inner">
                                                <div class="card-icon" style="background: rgba(255,255,255,0.25); color: <?= $card['icon_color']; ?>;">
                                                    <i class="fas <?= $card['icon']; ?>"></i>
                                                </div>
                                                <div class="text-end">
                                                    <div class="text-white-50 small mb-1"><?= $card['title']; ?> Total</div>
                                                    <div class="h4 mb-0 fw-bold text-white"><?= $card['count']; ?></div>
                                                    <div class="small text-white-50">This year: <?= $card['this_year']; ?></div>
                                                </div>
                                            </div>
                                            <?php if (!empty($card['spark'])): ?>
                                            <div class="mt-2 text-end">
                                                <svg width="120" height="36" viewBox="0 0 120 36" style="overflow: visible;">
                                                    <path d="<?= htmlspecialchars($card['spark']); ?>" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Lower Panels -->
                    <div class="row">
                        <!-- Record Overview Chart -->
                        <div class="col-lg-12 mb-4">
                            <div class="card panel-card shadow h-100">
                                <div class="card-body px-4 px-lg-5 py-5">
                                    <div class="panel-title">Record Overview</div>
                                    <div class="chart-container">
                                        <svg width="100%" height="100%" viewBox="0 0 600 160" preserveAspectRatio="none">
                                            <!-- Simple bar chart for monthly totals -->
                                            <?php
                                            $monthly_total = array_fill(0, 12, 0);
                                            foreach ($monthly_baptismal as $i => $v) $monthly_total[$i] += $v;
                                            foreach ($monthly_liberty as $i => $v) $monthly_total[$i] += $v;
                                            foreach ($monthly_confirmation as $i => $v) $monthly_total[$i] += $v;
                                            foreach ($monthly_marriage as $i => $v) $monthly_total[$i] += $v;
                                            foreach ($monthly_death as $i => $v) $monthly_total[$i] += $v;
                                            $max_monthly = max(1, max($monthly_total));
                                            $bar_width = 600 / 12;
                                            $bar_colors = [
                                                'rgba(13,110,253,0.8)',
                                                'rgba(25,135,84,0.8)',
                                                'rgba(255,193,7,0.8)',
                                                'rgba(220,53,69,0.8)',
                                                'rgba(108,117,125,0.8)',
                                                'rgba(13,202,240,0.8)',
                                                'rgba(214,51,132,0.8)',
                                                'rgba(102,16,242,0.8)',
                                                'rgba(253,126,20,0.8)',
                                                'rgba(32,201,151,0.8)',
                                                'rgba(111,66,193,0.8)',
                                                'rgba(249,115,22,0.8)'
                                            ];
                                            foreach ($monthly_total as $i => $count) {
                                                $height = ($count / $max_monthly) * 140;
                                                $x = $i * $bar_width + ($bar_width * 0.15);
                                                $y = 150 - $height;
                                                $w = $bar_width * 0.7;
                                                $color = $bar_colors[$i % count($bar_colors)];
                                                echo '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $height . '" fill="' . $color . '" rx="4" />';
                                            }
                                            ?>
                                        </svg>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <?php
                                        $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                        foreach ($months as $m) {
                                            echo '<span class="chart-label">' . $m . '</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Recent Records -->
                        <div class="col-lg-6 mb-4">
                            <div class="card panel-card shadow h-100">
                                <div class="card-body">
                                    <div class="panel-title">Recent Records</div>
                                    <?php if ($recentRecordsCount > 0): ?>
                                        <?php foreach ($recentRecords as $rec): ?>
                                            <div class="record-item">
                                                <span class="record-badge bg-light text-dark"><?= htmlspecialchars($rec['module']); ?></span>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($rec['person_name']); ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars($rec['event_date']); ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-muted small">No recent records found.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Events -->
                        <div class="col-lg-6 mb-4">
                            <div class="card panel-card shadow h-100">
                                <div class="card-body">
                                    <div class="panel-title">Upcoming Events</div>
                                    <?php if ($eventsCount > 0): ?>
                                        <?php foreach ($upcomingEvents as $evt): ?>
                                            <div class="event-item">
                                                <div class="event-date">
                                                    <?php
                                                    $evtDate = date('M d', strtotime($evt['event_date']));
                                                    echo htmlspecialchars($evtDate);
                                                    ?>
                                                </div>
                                                <div class="event-info">
                                                    <div class="event-type"><?= htmlspecialchars($evt['type']); ?></div>
                                                    <div class="event-time"><?= htmlspecialchars($evt['time']); ?> - <?= htmlspecialchars($evt['description'] ?? ''); ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-muted small">No upcoming events.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <!-- <?php include_once("../partials/footer.php"); ?> -->
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Hover tilt effect for stat cards
                const statCards = document.querySelectorAll('.stat-card');
                statCards.forEach(card => {
                    card.addEventListener('mousemove', (e) => {
                        const rect = card.getBoundingClientRect();
                        const x = e.clientX - rect.left;
                        const y = e.clientY - rect.top;
                        const centerX = rect.width / 2;
                        const centerY = rect.height / 2;
                        const rotateX = ((y - centerY) / centerY) * -6;
                        const rotateY = ((x - centerX) / centerX) * 6;
                        card.style.transform = `translateY(-2px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                    });

                    card.addEventListener('mouseleave', () => {
                        card.style.transform = 'translateY(0) rotateX(0) rotateY(0)';
                    });
                });
            });
        </script>

    </div>

    <?php include_once("../partials/html.footer.php"); ?>
</body>
</html>
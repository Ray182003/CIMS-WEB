<?php
$title = "Books";
require_once "../security_helper.php";
include "../partials/html.head.php";

requireAuth();
?>

<style>
    .books-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        perspective: 1100px;
        perspective-origin: 50% 30%;
        gap: 1.15rem;
        margin-bottom: 1.25rem;
    }

    .book-card {
        position: relative;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid rgba(15, 40, 77, 0.12);
        background: linear-gradient(135deg, rgba(18, 44, 90, 0.92), rgba(102, 16, 242, 0.84));
        color: #fff;
        box-shadow: 0 18px 44px rgba(15, 40, 77, 0.20);
        cursor: pointer;
        transform-style: preserve-3d;
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        min-height: 150px;
    }

    .book-card::after {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(600px 220px at 0% 0%, rgba(255,255,255,0.26), rgba(255,255,255,0) 55%);
        opacity: 0;
        transform: translateZ(18px);
        pointer-events: none;
        transition: opacity 0.22s ease;
    }

    .book-card:hover {
        transform: translateY(-4px) rotateX(6deg) rotateY(-7deg);
        border-color: rgba(255, 255, 255, 0.28);
        box-shadow: 0 26px 62px rgba(15, 40, 77, 0.28);
    }

    .book-card:hover::after {
        opacity: 1;
    }

    .book-card.active {
        outline: 3px solid rgba(13, 110, 253, 0.32);
        box-shadow: 0 28px 70px rgba(13, 110, 253, 0.22);
    }

    .book-card .spine {
        position: absolute;
        inset: 0 auto 0 0;
        width: 48px;
        background: rgba(0, 0, 0, 0.16);
        border-right: 1px solid rgba(255, 255, 255, 0.22);
        transform: translateZ(8px);
    }

    .book-card .spine::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(255,255,255,0.22), rgba(255,255,255,0));
        opacity: 0.22;
        pointer-events: none;
    }

    .book-card .content {
        padding: 1.05rem 1.05rem 1rem 1.15rem;
        margin-left: 48px;
        transform: translateZ(18px);
    }

    .book-card .range {
        font-size: 1.45rem;
        font-weight: 900;
        letter-spacing: 0.02em;
        margin-bottom: 0.25rem;
        line-height: 1.15;
    }

    .book-card .label {
        font-size: 0.92rem;
        font-weight: 700;
        opacity: 0.92;
    }

    .book-card .meta {
        margin-top: 0.5rem;
        font-size: 0.82rem;
        opacity: 0.85;
        font-weight: 600;
    }

    .book-card .delete-book-btn {
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
        font-weight: 800;
        border: 1px solid rgba(255, 255, 255, 0.35);
        background: rgba(255, 255, 255, 0.92);
        color: rgba(18, 44, 90, 0.95);
        box-shadow: 0 10px 18px rgba(0, 0, 0, 0.12);
        transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .book-card .delete-book-btn:hover {
        transform: translateY(-1px);
        background: rgba(255, 255, 255, 1);
        box-shadow: 0 14px 24px rgba(0, 0, 0, 0.16);
    }

    #search-input {
        border-radius: 14px;
        border: 1px solid rgba(15, 40, 77, 0.14);
        background-color: rgba(255, 255, 255, 0.96);
        box-shadow: 0 16px 28px rgba(15, 40, 77, 0.12);
        font-weight: 650;
        color: rgba(18, 44, 90, 0.98);
    }

    #search-input:focus {
        border-color: rgba(13, 110, 253, 0.55);
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.16), 0 16px 28px rgba(15, 40, 77, 0.12);
    }

    .books-table-card {
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid rgba(15, 40, 77, 0.10);
        box-shadow: 0 22px 46px rgba(15, 40, 77, 0.16);
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(6px);
        transform: translateZ(0);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .books-table-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 26px 60px rgba(15, 40, 77, 0.18);
    }

    .books-table-card .table thead th {
        background: rgba(15, 40, 77, 0.06);
        border-bottom: 1px solid rgba(15, 40, 77, 0.12);
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        font-size: 0.78rem;
        color: rgba(18, 44, 90, 0.92);
    }

    .books-table-card .table tbody td {
        font-weight: 650;
        color: rgba(18, 44, 90, 0.92);
        border-color: rgba(15, 40, 77, 0.07);
    }

    .books-table-card .table tbody tr:hover {
        background: rgba(13, 110, 253, 0.06);
    }

    .books-table-card .card-footer {
        background: rgba(255, 255, 255, 0.75) !important;
        border-top: 1px solid rgba(15, 40, 77, 0.10);
    }

    .books-table-card .pagination .page-link {
        border: none;
        border-radius: 12px;
        font-weight: 800;
        color: rgba(18, 44, 90, 0.92);
        box-shadow: 0 12px 18px rgba(15, 40, 77, 0.12);
    }

    .books-table-card .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #0d6efd, #6610f2);
        color: #fff;
        box-shadow: 0 16px 26px rgba(13, 110, 253, 0.25);
    }

    .btn {
        transition: transform 0.16s ease, box-shadow 0.16s ease;
        transform: translateZ(0);
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 26px rgba(15, 40, 77, 0.14);
    }

    @media (prefers-reduced-motion: reduce) {
        .book-card,
        .book-card::after,
        .books-table-card,
        .btn {
            transition: none !important;
        }

        .book-card:hover,
        .books-table-card:hover,
        .btn:hover {
            transform: none !important;
        }
    }
</style>

<body class="sb-nav-fixed gradient-page">
    <?php include_once("../partials/navbar.php"); ?>
    <div id="layoutSidenav">
        <?php include_once("../partials/sidebar.php"); ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 pt-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <h4 class="mb-0">Books</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBookModal" id="open-create-book">New Book</button>
                    </div>

                    <div class="books-grid" id="books-grid"></div>

                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                        <input class="form-control" style="width: 260px;" type="search" placeholder="Search name" id="search-input">
                        <input type="hidden" id="active-range-id" value="">
                    </div>

                    <div class="card border-0 shadow-sm books-table-card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 180px;">Type</th>
                                            <th>Name</th>
                                            <th style="width: 160px;">Date</th>
                                            <th style="width: 140px;">Year Range</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-books"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <small id="summary" class="text-muted"></small>
                                <ul class="pagination mb-0" id="pagination"></ul>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="createBookModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Create New Book (5-year range)</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Start Year</label>
                                        <input type="number" class="form-control" id="book-start-year" placeholder="e.g. 2018" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Label (optional)</label>
                                        <input type="text" class="form-control" id="book-label" placeholder="e.g. Baptismal Registry" />
                                    </div>
                                    <small class="text-muted">End year will auto be start year + 4.</small>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="create-book-btn">Create</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include_once("../partials/html.footer.php"); ?>
    <script src="<?php echo BASE_URL; ?>/books/script.php"></script>
</body>
</html>

<?php
require_once "../config/db.php";
header('Content-Type: application/javascript');
?>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;

(function() {
    let currentPage = 1;
    const limitPerPage = 10;

    let cachedRanges = [];

    const $search = $("#search-input");
    const $activeRangeId = $("#active-range-id");

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function computeRangeLabel(dateStr) {
        const y = parseInt((dateStr || '').toString().slice(0, 4), 10);
        if (!y || isNaN(y)) return '';
        const start = Math.floor(y / 5) * 5;
        return start + '-' + (start + 4);
    }

    function updateSummary(total, page, limit) {
        const start = total === 0 ? 0 : (page - 1) * limit + 1;
        const end = Math.min(page * limit, total);
        $("#summary").text(`Showing ${start}-${end} of ${total}`);
    }

    function renderPagination(total, page, limit) {
        const totalPages = Math.max(1, Math.ceil(total / limit));
        const $p = $("#pagination");
        $p.empty();

        const prevDisabled = page <= 1 ? 'disabled' : '';
        $p.append(`<li class="page-item ${prevDisabled}"><a class="page-link" href="#" data-page="${page - 1}">Prev</a></li>`);

        const maxButtons = 5;
        let start = Math.max(1, page - 2);
        let end = Math.min(totalPages, start + maxButtons - 1);
        start = Math.max(1, end - maxButtons + 1);

        for (let p = start; p <= end; p++) {
            const active = p === page ? 'active' : '';
            $p.append(`<li class="page-item ${active}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`);
        }

        const nextDisabled = page >= totalPages ? 'disabled' : '';
        $p.append(`<li class="page-item ${nextDisabled}"><a class="page-link" href="#" data-page="${page + 1}">Next</a></li>`);
    }

    function renderRangeCards(ranges) {
        const $grid = $("#books-grid");
        $grid.empty();

        const activeId = ($activeRangeId.val() || '').toString();

        if (!Array.isArray(ranges) || ranges.length === 0) {
            $grid.append(`
                <div class="alert alert-info mb-0">
                    No books yet. Click <strong>New Book</strong> to create a 5-year book.
                </div>
            `);
            return;
        }

        ranges.forEach(r => {
            const id = (r.id ?? '').toString();
            const start = r.start_year;
            const end = r.end_year;
            const label = r.label ? r.label : 'Certificate Book';
            const activeClass = id !== '' && id === activeId ? 'active' : '';

            $grid.append(`
                <div class="book-card ${activeClass}" data-range-id="${escapeHtml(id)}">
                    <div class="spine" aria-hidden="true"></div>
                    <div class="content">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div style="min-width: 0;">
                                <div class="range">${escapeHtml(start)} - ${escapeHtml(end)}</div>
                                <div class="label">${escapeHtml(label)}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light delete-book-btn" data-book-id="${escapeHtml(id)}" title="Delete book">Delete</button>
                        </div>
                        <div class="meta">Click to open this book</div>
                    </div>
                </div>
            `);
        });
    }

    function loadCustomRanges(selectFirstIfEmpty = true) {
        const url = `${BASE_URL}/api/books/ranges/fetch-all.php`;
        return fetch(url)
            .then(res => res.json())
            .then(data => {
                const ranges = (data && data.ranges) ? data.ranges : [];
                cachedRanges = ranges;

                if (selectFirstIfEmpty) {
                    const current = ($activeRangeId.val() || '').toString();
                    if (!current && ranges.length > 0 && ranges[0].id != null) {
                        $activeRangeId.val(String(ranges[0].id));
                    }
                }

                renderRangeCards(ranges);
                return ranges;
            });
    }

    function fetchAllBooks() {
        const searchTerm = ($search.val() || '').trim();

        const rangeId = ($activeRangeId.val() || '').toString().trim();

        const activeRange = Array.isArray(cachedRanges)
            ? cachedRanges.find(r => String(r.id ?? '') === String(rangeId))
            : null;
        const activeRangeLabel = activeRange
            ? `${activeRange.start_year} - ${activeRange.end_year}`
            : '';

        if (!rangeId) {
            const $tbody = $("#table-books");
            $tbody.empty();
            $tbody.append(`<tr><td colspan="4" class="text-center py-4">Select a book card to view records.</td></tr>`);
            updateSummary(0, 1, limitPerPage);
            renderPagination(0, 1, limitPerPage);
            return;
        }

        const url = `${BASE_URL}/api/books/fetch-all.php?search=${encodeURIComponent(searchTerm)}&range_id=${encodeURIComponent(rangeId)}&page=${currentPage}&per_page=${limitPerPage}&archived=0`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                const $tbody = $("#table-books");
                $tbody.empty();

                if (data.error || !data.records || data.records.length === 0) {
                    $tbody.append(`<tr><td colspan="4" class="text-center py-4">No records found.</td></tr>`);
                    updateSummary(0, currentPage, limitPerPage);
                    renderPagination(0, 1, limitPerPage);
                    return;
                }

                data.records.forEach(item => {
                    const yr = activeRangeLabel || computeRangeLabel(item.event_date);
                    $tbody.append(`
                        <tr>
                            <td>${item.certificate_type}</td>
                            <td>${item.person_name}</td>
                            <td>${item.event_date || ''}</td>
                            <td>${yr}</td>
                        </tr>
                    `);
                });

                updateSummary(Number(data.total) || 0, Number(data.page) || 1, Number(data.limit) || limitPerPage);
                renderPagination(Number(data.total) || 0, Number(data.page) || 1, Number(data.limit) || limitPerPage);
            })
            .catch(() => {
                const $tbody = $("#table-books");
                $tbody.empty();
                $tbody.append(`<tr><td colspan="4" class="text-center py-4">Failed to load records.</td></tr>`);
                updateSummary(0, 1, limitPerPage);
                renderPagination(0, 1, limitPerPage);
            });
    }

    let searchTimeout;
    $search.on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (!(($activeRangeId.val() || '').toString().trim())) {
                return;
            }
            currentPage = 1;
            fetchAllBooks();
        }, 400);
    });

    $(document).on('click', '.book-card', function() {
        const rid = ($(this).data('range-id') ?? '').toString();
        if (!rid) return;
        $activeRangeId.val(rid);
        currentPage = 1;
        loadCustomRanges(false).then(() => fetchAllBooks());
    });

    $(document).on('click', '.delete-book-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const id = ($(this).data('book-id') ?? '').toString();
        if (!id) return;

        if (!confirm('Delete this book?')) {
            return;
        }

        fetch(`${BASE_URL}/api/books/ranges/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id, 10) })
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            const current = ($activeRangeId.val() || '').toString();
            if (current === id) {
                $activeRangeId.val('');
            }

            currentPage = 1;
            loadCustomRanges(false).then(() => fetchAllBooks());
        })
        .catch(() => alert('Failed to delete book'));
    });

    $(document).on('click', '#create-book-btn', function() {
        const startYear = parseInt((($("#book-start-year").val() || '').toString().trim()), 10);
        const label = (($("#book-label").val() || '').toString().trim());

        if (!startYear || isNaN(startYear)) {
            alert('Start year is required');
            return;
        }

        fetch(`${BASE_URL}/api/books/ranges/create.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ start_year: startYear, label: label })
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            if (data && data.range && data.range.id != null) {
                $activeRangeId.val(String(data.range.id));
            }

            const modalEl = document.getElementById('createBookModal');
            if (modalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                const instance = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
                instance.hide();
            }

            $("#book-start-year").val('');
            $("#book-label").val('');

            currentPage = 1;
            loadCustomRanges(false).then(() => fetchAllBooks());
        })
        .catch(() => alert('Failed to create book'));
    });

    $(document).on("click", "#pagination .page-link", function(e) {
        e.preventDefault();
        const p = parseInt($(this).data('page'), 10);
        if (!p || isNaN(p) || p < 1) return;
        currentPage = p;
        fetchAllBooks();
    });

    loadCustomRanges(false)
        .then(() => fetchAllBooks())
        .catch(() => fetchAllBooks());
})();

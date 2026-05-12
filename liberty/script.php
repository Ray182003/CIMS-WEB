<script>
    let currentPage = 1;
    const limitPerPage = 5;

    $(document).ready(function () {
        // Check if page needs refresh after backup import
        const lastBackupImport = sessionStorage.getItem('lastBackupImport');
        const currentPageVisit = Date.now();
        
        if (lastBackupImport && (currentPageVisit - parseInt(lastBackupImport)) < 10000) {
            // If backup was imported less than 10 seconds ago, refresh the page
            sessionStorage.removeItem('lastBackupImport');
            window.location.reload();
            return;
        }
        
        fetchAllLiberty();

        const debouncedFetch = debounce(function (searchTerm) {
            fetchAllLiberty(searchTerm);
        }, 500);

        $("#search-input").on("input", function () {
            const searchTerm = $(this).val();
            currentPage = 1;
            debouncedFetch(searchTerm);
        });

        $(document).on("click", ".pagination-link", function (e) {
            e.preventDefault();
            const selectedPage = parseInt($(this).data("page"));
            currentPage = selectedPage;
            fetchAllLiberty($("#search-input").val());
        });

        $(document).on("click", "#pagination-prev", function (e) {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                fetchAllLiberty($("#search-input").val());
            }
        });

        $(document).on("click", "#pagination-next", function (e) {
            e.preventDefault();
            currentPage++;
            fetchAllLiberty($("#search-input").val());
        });

        $("#close-modal").click(() => {
            $("#libertyUpdateModal").modal("hide");
            $("#libertyForm")[0].reset();
        });

        $("#libertyForm").submit(function (e) {
            e.preventDefault();
            const formData = $(this).serialize();

            $.ajax({
                url: "<?php echo BASE_URL; ?>/api/liberty/update-lib.php",
                method: "POST",
                data: formData,
                dataType: "json",
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $("#libertyUpdateModal").modal("hide");
                        $("#libertyForm")[0].reset();
                        fetchAllLiberty();
                    } else {
                        toastr.error(response.error);
                    }
                },
                error: function () {
                    alert("Error updating liberty record.");
                },
            });
        });

        $(document).on("click", ".open-liberty-modal", function () {
            const id = $(this).data("edit-id");

            $.ajax({
                url: "<?php echo BASE_URL; ?>/api/liberty/fetch-one.php",
                method: "GET",
                data: { id },
                dataType: "json",
                success: function (res) {
                    if (res.error) {
                        alert(res.error);
                    } else {
                        $('#libertyId').val(res.id);
                        $('[name="child_name"]').val(res.child_name);
                        $('[name="parent_name1"]').val(res.parent_name1);
                        $('[name="parent_name2"]').val(res.parent_name2);
                        $('[name="person_name1"]').val(res.residence);
                        $('[name="person_name2"]').val(res.marriage_with);
                        // Month is stored in signed_sealed_given, day in day_of, year in year_of_our_lord
                        $('[name="month"]').val(res.signed_sealed_given);
                        $('[name="day"]').val(res.day_of);
                        $('[name="year"]').val(res.year_of_our_lord);

                        $("#libertyUpdateModal").modal("show");
                    }
                },
                error: function () {
                    alert("Error loading data.");
                },
            });
        });
    });

    
    function fetchAllLiberty(searchTerm = '', year = '') {
        const url = `<?php echo BASE_URL; ?>/api/liberty/fetch-all.php?search=${encodeURIComponent(searchTerm)}&year=${encodeURIComponent(year)}&page=${currentPage}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                const tableBody = $("#table-liberty");
                const pagination = $('#pagination');
                tableBody.empty();
                pagination.empty();

                if (data.error || data.records.length === 0) {
                    tableBody.append(`
                    <tr>
                        <td colspan="6" class="text-center">No records found.</td>
                    </tr>
                `);
                    return;
                }

                data.records.forEach((item, index) => {
                    tableBody.append(`
                    <tr>
                        <td class="text-center">${(currentPage - 1) * data.limit + index + 1}</td>
                        <td>${item.child_name}</td>
                        <td>${item.parent_name1}</td>
                        <td>${item.parent_name2}</td>
                        <td class="text-center">
                            <button class="btn btn-success btn-sm open-liberty-modal edit-btn" data-edit-id="${item.id}">
                                <span class="edit-icon" aria-hidden="true">
                                    <span class="edit-pencil-body"></span>
                                    <span class="edit-pencil-ferrule"></span>
                                    <span class="edit-pencil-tip"></span>
                                </span>
                                <span class="edit-label"></span>
                            </button>
                            <a href="index.php?action=delete&id=${item.id}" class="btn btn-danger btn-sm trash-btn liberty-delete-btn">
                                <span class="trash-icon" aria-hidden="true">
                                    <i class="fa-solid fa-box-archive"></i>
                                </span>
                                <span class="trash-label"></span>
                            </a>
                            <a href="<?php echo BASE_URL; ?>/liberty/print.php?id=${item.id}" target="_blank" class="btn btn-secondary btn-sm print-btn">
                                <span class="print-icon" aria-hidden="true">
                                    <span class="print-top"></span>
                                    <span class="print-body"></span>
                                    <span class="print-paper"></span>
                                    <span class="print-light"></span>
                                </span>
                                <span class="print-label"></span>
                            </a>
                        </td>
                    </tr>
                `);
                });

                const totalPages = Math.ceil(data.total / data.limit);
                pagination.append(`
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" id="pagination-prev">Previous</a>
                </li>
            `);

                for (let i = 1; i <= totalPages; i++) {
                    pagination.append(`
                    <li class="page-item ${i === data.page ? 'active' : ''}">
                        <a class="page-link pagination-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
                }

                pagination.append(`
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" id="pagination-next">Next</a>
                </li>
            `);
            })
            .catch(error => console.error("Fetch error:", error));
    }


    function debounce(func, delay) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, args), delay);
        };
    }

    function triggerEditButtonAnimation($button, options = {}) {
        const { force = false } = options;
        const animationDuration = 900;
        const existingTimeout = $button.data("animationTimeout");

        if ($button.data("animating")) {
            if (!force) {
                return false;
            }

            if (existingTimeout) {
                clearTimeout(existingTimeout);
                $button.removeData("animationTimeout");
            }

            $button.removeClass("animate-once");
        }

        $button.data("animating", true);
        $button.addClass("animate-once");

        const timeoutId = setTimeout(() => {
            $button.removeClass("animate-once");
            $button.data("animating", false);
            $button.removeData("animationTimeout");
        }, animationDuration);

        $button.data("animationTimeout", timeoutId);

        return true;
    }

    function triggerPrintButtonAnimation($button, options = {}) {
        const { force = false } = options;
        const animationDuration = 750;
        const existingTimeout = $button.data("printTimeout");

        if ($button.data("printing")) {
            if (!force) {
                return false;
            }

            if (existingTimeout) {
                clearTimeout(existingTimeout);
                $button.removeData("printTimeout");
            }

            $button.removeClass("animate-once");
        }

        $button.data("printing", true);
        $button.addClass("animate-once");

        const timeoutId = setTimeout(() => {
            $button.removeClass("animate-once");
            $button.data("printing", false);
            $button.removeData("printTimeout");
        }, animationDuration);

        $button.data("printTimeout", timeoutId);

        return true;
    }
</script>
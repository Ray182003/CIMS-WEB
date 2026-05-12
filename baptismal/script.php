<script>
    let currentPage = 1;
    const limitPerPage = 5;
    let archivedFilter = 0;

    let currentBaptismalModalRequest = null;
    let currentModalRecordId = null;

    $(document).ready(function() {
        function renderSponsorRows(sponsors = []) {
            const sponsorContainer = $("#sponsorContainer");
            sponsorContainer.empty();

            const normalizedSponsors = Array.isArray(sponsors) && sponsors.length > 0
                ? sponsors
                : [''];

            normalizedSponsors.forEach((rawValue) => {
                const sponsorName = typeof rawValue === 'string' ? rawValue : '';
                const $row = $('<div class="d-flex gap-2 mb-2 sponsor-row"></div>');
                const $input = $('<input type="text" class="form-control" name="sponsors[]">');
                $input.val(sponsorName);
                const $removeButton = $('<button type="button" class="btn btn-danger btn-sm remove-sponsor">Remove</button>');
                $row.append($input);
                $row.append($removeButton);
                sponsorContainer.append($row);
            });
        }

        const getCurrentSponsorValues = () => $("#sponsorContainer input[name='sponsors[]']").map(function() {
            return $(this).val();
        }).get();

        renderSponsorRows(['']);
        // Check if page needs refresh after backup import
        const lastBackupImport = sessionStorage.getItem('lastBackupImport');
        const currentPageVisit = Date.now();
        
        if (lastBackupImport && (currentPageVisit - parseInt(lastBackupImport)) < 10000) {
            // If backup was imported less than 10 seconds ago, refresh the page
            sessionStorage.removeItem('lastBackupImport');
            window.location.reload();
            return;
        }
        
        const $yearPicker = $("#yearpicker");
        
        const triggerYearFilter = () => {
            const yearValue = ($yearPicker.val() || '').trim();
            // Only trigger if empty or a valid year (4 digits)
            if (yearValue === '' || /^\d{4}$/.test(yearValue)) {
                currentPage = 1;
                fetchAllBaptismals($("#search-input").val(), yearValue);
            }
        };

        if ($yearPicker.length && typeof $.fn.datepicker === "function") {
            $yearPicker.datepicker({
                format: "yyyy",
                viewMode: "years",
                minViewMode: "years",
                autoclose: true,
                orientation: "bottom",
                container: "body",
                keyboardNavigation: false
            });

            const yearPickerInstance = $yearPicker.data("datepicker");
            if (yearPickerInstance && yearPickerInstance.picker) {
                yearPickerInstance.picker.addClass("baptismal-year-dropdown");
            }

            // Allow manual input
            $yearPicker.attr("placeholder", "Search year");
            
            // Handle manual input with debounce
            let yearInputTimeout;
            $yearPicker.on("input", function() {
                clearTimeout(yearInputTimeout);
                const value = $(this).val().trim();
                
                // If empty or a valid year (4 digits)
                if (value === '' || /^\d{0,4}$/.test(value)) {
                    yearInputTimeout = setTimeout(() => {
                        if (value === '' || /^\d{4}$/.test(value)) {
                            triggerYearFilter();
                        }
                    }, 800); // 800ms debounce
                } else {
                    // If not a valid year, clear the input after a short delay
                    yearInputTimeout = setTimeout(() => {
                        if (!/^\d{0,4}$/.test(value)) {
                            $(this).val('');
                            triggerYearFilter();
                        }
                    }, 1000);
                }
            });

            // Handle date selection from picker
            $yearPicker.on("changeDate", function(e) {
                $(this).val(e.format('yyyy'));
                triggerYearFilter();
            });

            $yearPicker.on("changeDate", function() {
                triggerYearFilter();
            });

            $yearPicker.on("show", function() {
                const pickerInstance = $(this).data("datepicker");
                if (pickerInstance && pickerInstance.picker) {
                    pickerInstance.picker.addClass("baptismal-year-dropdown");
                }
            });

            $yearPicker.on("hide", function() {
                const pickerInstance = $(this).data("datepicker");
                if (pickerInstance && pickerInstance.picker) {
                    pickerInstance.picker.removeClass("baptismal-year-dropdown");
                }
            });
        }

        fetchAllBaptismals('', $yearPicker.val());

        function getOrdinalSuffix(day) {
            const tens = day % 100;
            if (tens >= 11 && tens <= 13) {
                return "th";
            }

            switch (day % 10) {
                case 1:
                    return "st";
                case 2:
                    return "nd";
                case 3:
                    return "rd";
                default:
                    return "th";
            }
        }

        const $baptismDateInput = $('#baptismalForm [name="on"]');
        const $dayInput = $('#baptismalForm [name="day"]');

        const getSuffixElement = ($input) => {
            if (!$input || !$input.length) {
                return $();
            }
            const suffixId = $input.data('suffix-target');
            return suffixId ? $(`#${suffixId}`) : $();
        };

        const updateDaySuffix = ($input, value) => {
            const $suffix = getSuffixElement($input);
            if (!$suffix.length) {
                return;
            }

            if (value === undefined || value === null || value === '') {
                $suffix.text('th');
                return;
            }

            const numericDay = parseInt(value, 10);
            if (Number.isNaN(numericDay) || numericDay <= 0) {
                $suffix.text('th');
                return;
            }

            $suffix.text(getOrdinalSuffix(numericDay));
        };

        const setDayValue = ($input, value) => {
            if (!$input.length) {
                return;
            }

            if (value === undefined || value === null || value === '') {
                $input.val('');
                updateDaySuffix($input, '');
                return;
            }

            const numericDay = parseInt(value, 10);
            if (Number.isNaN(numericDay) || numericDay <= 0) {
                $input.val('');
                updateDaySuffix($input, '');
                return;
            }

            const clampedDay = Math.min(Math.max(numericDay, 1), 31);
            $input.val(clampedDay);
            updateDaySuffix($input, clampedDay);
        };

        const populateDayField = () => {};

        if ($dayInput.length) {
            $dayInput.on('input', function() {
                const digitsOnly = $(this).val().replace(/[^0-9]/g, '');
                $(this).val(digitsOnly);
                updateDaySuffix($dayInput, digitsOnly);
            });

            $dayInput.on('blur', function() {
                setDayValue($dayInput, $(this).val());
            });
        }

        const debouncedFetch = debounce(function(searchTerm) {
            fetchAllBaptismals(searchTerm, $("#yearpicker").val());
        }, 500);

        const debouncedYearFetch = debounce(function(yearValue) {
            fetchAllBaptismals($("#search-input").val(), yearValue);
        }, 400);

        $("#search-input").on("input", function() {
            const searchTerm = $(this).val();
            currentPage = 1;
            debouncedFetch(searchTerm);
        });

        $("#yearpicker").on("change", function() {
            triggerYearFilter();
        });

        $("#yearpicker").on("input", function() {
            const value = $(this).val().trim();

            if (value.length === 0) {
                debouncedYearFetch('');
                return;
            }

            if (/^\d{1,4}$/.test(value)) {
                if (value.length === 4) {
                    debouncedYearFetch(value);
                }
            }
        });

        $("#yearpicker").on("blur", function() {
            const value = $(this).val().trim();
            if (value.length > 0 && !/^\d{4}$/.test(value)) {
                $(this).val('');
                debouncedYearFetch('');
            }
        });

        $(document).on("click", ".pagination-link", function(e) {
            e.preventDefault();
            const selectedPage = parseInt($(this).data("page"));
            currentPage = selectedPage;
            fetchAllBaptismals($("#search-input").val(), $("#yearpicker").val());
        });

        $(document).on("click", "#pagination-prev", function(e) {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                fetchAllBaptismals($("#search-input").val(), $("#yearpicker").val());
            }
        });

        $(document).on("click", "#pagination-next", function(e) {
            e.preventDefault();
            currentPage++;
            fetchAllBaptismals($("#search-input").val(), $("#yearpicker").val());
        });

        $("#baptismalUpdateModal").on("hidden.bs.modal", () => {
            renderSponsorRows(['']);
            $("#baptismalForm")[0].reset();
            $hiddenUpdateReleaseClaimant.val('');
            $hiddenUpdateReleaseRelationship.val('');
            $hiddenUpdateReleaseTime.val('');
            $updateModalReleaseClaimant.val('');
            $updateModalReleaseRelationship.val('');
            $updateModalReleaseTime.val('');
            $updateModalReleaseDate.val('');
            currentModalRecordId = null;
            if (currentBaptismalModalRequest) {
                currentBaptismalModalRequest.abort();
                currentBaptismalModalRequest = null;
            }
        });

        const $updateForm = $("#baptismalForm");
        const $openUpdateReleaseModalBtn = $("#openUpdateReleaseModalBtn");
        const updateReleaseModalEl = document.getElementById('updateReleaseInfoModal');
        const updateReleaseModal = updateReleaseModalEl ? new bootstrap.Modal(updateReleaseModalEl, {
            backdrop: 'static',
            keyboard: false,
        }) : null;
        const $updateModalReleaseClaimant = $("#update_modal_release_claimant");
        const $updateModalReleaseRelationship = $("#update_modal_release_relationship");
        const $updateModalReleaseDate = $("#update_modal_release_date");
        const $updateModalReleaseTime = $("#update_modal_release_time");
        const $hiddenUpdateReleaseClaimant = $("#update_release_claimant");
        const $hiddenUpdateReleaseRelationship = $("#update_release_relationship");
        const $hiddenUpdateReleaseTime = $("#update_release_time");

        function buildUpdateReleaseDateDisplay() {
            const baptismDateValue = ($("#baptismalForm [name='on']").val() || '').trim();
            if (!baptismDateValue) {
                $updateModalReleaseDate.val('');
                return;
            }

            const dateObj = new Date(`${baptismDateValue}T00:00:00`);
            if (Number.isNaN(dateObj.getTime())) {
                $updateModalReleaseDate.val(baptismDateValue);
                return;
            }

            $updateModalReleaseDate.val(dateObj.toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            }));
        }

        $("#baptismalForm [name='on']").on('input change', buildUpdateReleaseDateDisplay);

        $openUpdateReleaseModalBtn.on('click', function() {
            if (!$updateForm[0].reportValidity()) {
                return;
            }

            buildUpdateReleaseDateDisplay();
            $updateModalReleaseClaimant.val($hiddenUpdateReleaseClaimant.val() || '');
            $updateModalReleaseRelationship.val($hiddenUpdateReleaseRelationship.val() || '');
            $updateModalReleaseTime.val($hiddenUpdateReleaseTime.val() || '');

            if (updateReleaseModal) {
                updateReleaseModal.show();
            }
        });

        $("#confirmUpdateReleaseBtn").on('click', function() {
            const claimantName = ($updateModalReleaseClaimant.val() || '').trim();
            if (!claimantName) {
                alert('Please enter the name of the person who will claim the certificate.');
                return;
            }

            $hiddenUpdateReleaseClaimant.val(claimantName);
            $hiddenUpdateReleaseRelationship.val(($updateModalReleaseRelationship.val() || '').trim());
            $hiddenUpdateReleaseTime.val($updateModalReleaseTime.val() || '');

            if (updateReleaseModal) {
                updateReleaseModal.hide();
            }

            const formArray = $updateForm.serializeArray().filter(field => {
                return field.name !== 'sponsors[]' || (field.value && field.value.trim() !== '');
            });
            const formData = $.param(formArray);

            $.ajax({
                url: "<?php echo BASE_URL; ?>/api/baptismal/update-bap.php",
                method: "POST",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $("#baptismalUpdateModal").modal("hide");
                        $updateForm[0].reset();
                        renderSponsorRows(['']);
                        fetchAllBaptismals($("#search-input").val(), $("#yearpicker").val());
                    } else {
                        toastr.error(response.error);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.error || xhr.responseText || "Error updating baptismal record.";
                    alert(message);
                },
            });
        });

        $(document).on("click", ".open-baptismal-modal", function() {
            const id = $(this).data("edit-id");

            if (currentBaptismalModalRequest) {
                currentBaptismalModalRequest.abort();
                currentBaptismalModalRequest = null;
            }

            currentModalRecordId = id;

            currentBaptismalModalRequest = $.ajax({
                url: "<?php echo BASE_URL; ?>/api/baptismal/fetch-one.php",
                method: "GET",
                data: {
                    id
                },
                dataType: "json",
                success: function(res) {
                    if (currentModalRecordId !== id) {
                        return;
                    }
                    if (res.error) {
                        alert(res.error);
                    } else {
                        $('#baptismalId').val(res.id);
                        $('[name="child_name"]').val(res.child_name);
                        $('[name="parent_name1"]').val(res.parent_name1);
                        $('[name="parent_name2"]').val(res.parent_name2);
                        $('[name="in"]').val(res.birth_date);
                        $('[name="date"]').val(res.birth_place);
                        $('[name="on"]').val(res.baptism_date);
                        $('[name="bishop"]').val(res.bishop);
                        const $modalDayInput = $('[name="day"]');
                        if ($modalDayInput.length) {
                            const numericDay = parseInt(String(res.day || '').replace(/[^0-9]/g, ''), 10);
                            if (!Number.isNaN(numericDay)) {
                                setDayValue($modalDayInput, numericDay);
                            } else {
                                updateDaySuffix($modalDayInput, '');
                            }
                        }
                        $('[name="month"]').val(res.month);
                        $('[name="year"]').val(res.year);
                        $('[name="book_no"]').val(res.book_no);
                        $('[name="page_no"]').val(res.page_no);
                        $('[name="entry_no"]').val(res.entry_no);

                        const sponsors = Array.isArray(res.sponsors) ? res.sponsors : [];
                        renderSponsorRows(sponsors);

                        // Populate release fields kung meron
                        const releaseClaimant = res.release_claimant || '';
                        const releaseRelationship = res.release_relationship || '';
                        const releaseTime = res.release_time || '';

                        $hiddenUpdateReleaseClaimant.val(releaseClaimant);
                        $hiddenUpdateReleaseRelationship.val(releaseRelationship);
                        $hiddenUpdateReleaseTime.val(releaseTime);

                        $updateModalReleaseClaimant.val(releaseClaimant);
                        $updateModalReleaseRelationship.val(releaseRelationship);
                        $updateModalReleaseTime.val(releaseTime);
                        buildUpdateReleaseDateDisplay();

                        const releaseDayInput = $('[name="day"]');
                        if (releaseDayInput.length && res.day) {
                            const numericReleaseDay = parseInt(String(res.day).replace(/[^0-9]/g, ''), 10);
                            if (!Number.isNaN(numericReleaseDay)) {
                                setDayValue(releaseDayInput, numericReleaseDay);
                            }
                        }

                        if (res.release_date) {
                            // release_date ay YYYY-MM-DD, hatiin para i-display sa modal Kung may hidden inputs elsewhere
                            const releaseDateParts = res.release_date.split('-');
                            if (releaseDateParts.length === 3) {
                                const [, releaseMonth, releaseDay] = releaseDateParts;
                                if (releaseMonth) {
                                    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                                    const monthIndex = parseInt(releaseMonth, 10) - 1;
                                    if (monthIndex >= 0 && monthIndex < monthNames.length) {
                                        $('[name="month"]').val(monthNames[monthIndex]);
                                    }
                                }
                                if (releaseDay) {
                                    $('[name="day"]').val(parseInt(releaseDay, 10));
                                }
                                $('[name="year"]').val(releaseDateParts[0]);
                            }
                        }

                        $("#baptismalUpdateModal").modal("show");
                    }
                },
                error: function(xhr) {
                    if (xhr.statusText === "abort") {
                        return;
                    }
                    alert("Error loading data.");
                },
                complete: function() {
                    if (currentModalRecordId === id) {
                        currentBaptismalModalRequest = null;
                    }
                }
            });
        });

        $("#addSponsor").on("click", function(e) {
            e.preventDefault();
            const currentValues = getCurrentSponsorValues();
            currentValues.push('');
            renderSponsorRows(currentValues);
        });

        // 🔴 Remove sponsor row logic
        $(document).on("click", ".remove-sponsor", function() {
            const $rows = $("#sponsorContainer .sponsor-row");
            if ($rows.length <= 1) {
                const $singleInput = $rows.eq(0).find("input[name='sponsors[]']");
                if ($singleInput.length) {
                    $singleInput.val('');
                }
                return;
            }

            $(this).closest(".sponsor-row").remove();

            const remainingValues = getCurrentSponsorValues();
            if (remainingValues.length === 0) {
                renderSponsorRows(['']);
            } else {
                renderSponsorRows(remainingValues);
            }
        });
    });

    function updateSummary(total = 0, page = 1, limit = limitPerPage, displayed = 0, searchTerm = '', year = '') {
        const totalRecords = Number(total) || 0;
        const currentPageNumber = Number(page) || 1;
        const pageLimit = Number(limit) || limitPerPage;
        const visibleCount = Number(displayed) || 0;

        const start = totalRecords === 0 ? 0 : ((currentPageNumber - 1) * pageLimit) + 1;
        const end = totalRecords === 0 ? 0 : start + visibleCount - 1;

        $("#summaryTotal").text(totalRecords.toLocaleString());
        $("#summaryRange").text(`${start} - ${end}`);

        const rangeMeta = visibleCount === 1 ? "1 record on this page" : `${visibleCount} records on this page`;
        $("#summaryRangeMeta").text(totalRecords === 0 ? "No records to display" : rangeMeta);

        const filterParts = [];
        const trimmedSearch = (searchTerm || '').trim();
        const trimmedYear = (year || '').trim();

        if (trimmedSearch) {
            filterParts.push(`Keyword: “${trimmedSearch}”`);
        }

        if (trimmedYear) {
            filterParts.push(`Year: ${trimmedYear}`);
        }

        if (filterParts.length) {
            $("#summaryFilter").text(filterParts.join(' • '));
            $("#summaryFilterMeta").text("Filter active");
        } else {
            $("#summaryFilter").text("All records");
            $("#summaryFilterMeta").text("No additional filters applied");
        }

        $("#summaryMeta").text(archivedFilter === 1 ? "Viewing archived records" : "Active records only");
    }

    function fetchAllBaptismals(searchTerm = '', year = '') {
        const url = `<?php echo BASE_URL; ?>/api/baptismal/fetch-all.php?search=${encodeURIComponent(searchTerm)}&year=${encodeURIComponent(year)}&page=${currentPage}&archived=${archivedFilter}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                const tableBody = $("#table-baptismal");
                const pagination = $('#pagination');
                tableBody.empty();
                pagination.empty();

                if (data.error || data.records.length === 0) {
                    tableBody.append(`
                    <tr class="empty-row">
                        <td colspan="6">No records found.</td>
                    </tr>
                    `);
                    const fallbackPage = Number(data && data.page) || 1;
                    const fallbackLimit = Number(data && data.limit) || limitPerPage;
                    updateSummary(0, fallbackPage, fallbackLimit, 0, searchTerm, year);
                } else {
                    data.records.forEach((item, index) => {
                        tableBody.append(`
                        <tr>
                            <td class="text-center">${(currentPage - 1) * data.limit + index + 1}</td>
                            <td>${item.child_name}</td>
                            <td>${item.baptism_date}</td>
                            <td>${item.bishop}</td>
                            <td class="text-center">
                                <div class="action-group">
                                    <button class="btn btn-success btn-icon open-baptismal-modal edit-btn" data-edit-id="${item.id}">
                                        <span class="edit-icon" aria-hidden="true">
                                            <span class="edit-pencil-body"></span>
                                            <span class="edit-pencil-ferrule"></span>
                                            <span class="edit-pencil-tip"></span>
                                        </span>
                                        <span class="edit-label"></span>
                                        <span class="tooltip-label">Edit</span>
                                    </button>
                                    <a href="index.php?action=delete&id=${item.id}" class="btn btn-danger btn-icon trash-btn baptismal-delete-btn">
                                        <span class="trash-icon" aria-hidden="true">
                                            <i class="fa-solid fa-box-archive"></i>
                                        </span>
                                        <span class="trash-label"></span>
                                        <span class="tooltip-label">Archive</span>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/baptismal/print.php?id=${item.id}" target="_blank" class="btn btn-secondary btn-icon print-btn">
                                        <span class="print-icon" aria-hidden="true">
                                            <span class="print-top"></span>
                                            <span class="print-body"></span>
                                            <span class="print-paper"></span>
                                            <span class="print-light"></span>
                                        </span>
                                        <span class="print-label"></span>
                                        <span class="tooltip-label">Print</span>
                                    </a>
                                </div>
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

                    const total = Number(data && data.total);
                    const pageNumber = Number(data && data.page) || 1;
                    const limitValue = Number(data && data.limit) || limitPerPage;
                    const displayed = Array.isArray(data.records) ? data.records.length : 0;
                    updateSummary(Number.isNaN(total) ? 0 : total, pageNumber, limitValue, displayed, searchTerm, year);
                }
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
</script>
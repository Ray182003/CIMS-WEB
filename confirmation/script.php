<script>
    let currentPage = 1;
    const limitPerPage = 5;
    let archivedFilter = 0;

    $(document).ready(function() {
        // Check if page needs refresh after backup import
        const lastBackupImport = sessionStorage.getItem('lastBackupImport');
        const currentPageVisit = Date.now();
        
        if (lastBackupImport && (currentPageVisit - parseInt(lastBackupImport)) < 10000) {
            // If backup was imported less than 10 seconds ago, refresh the page
            sessionStorage.removeItem('lastBackupImport');
            window.location.reload();
            return;
        }
        
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

            const remainderTen = numericDay % 10;
            const remainderHundred = numericDay % 100;

            if (remainderHundred >= 11 && remainderHundred <= 13) {
                $suffix.text('th');
                return;
            }

            switch (remainderTen) {
                case 1:
                    $suffix.text('st');
                    break;
                case 2:
                    $suffix.text('nd');
                    break;
                case 3:
                    $suffix.text('rd');
                    break;
                default:
                    $suffix.text('th');
            }
        };

        const setDayInputValue = ($input, rawValue) => {
            if (!$input.length) {
                return;
            }

            const digitsOnly = String(rawValue ?? '').replace(/[^0-9]/g, '');
            if (digitsOnly === '') {
                $input.val('');
                updateDaySuffix($input, '');
                return;
            }

            const numericDay = parseInt(digitsOnly, 10);
            if (Number.isNaN(numericDay) || numericDay <= 0) {
                $input.val('');
                updateDaySuffix($input, '');
                return;
            }

            const clampedDay = Math.min(Math.max(numericDay, 1), 31);
            $input.val(clampedDay);
            updateDaySuffix($input, clampedDay);
        };

        const $confirmationForm = $("#confirmationForm");
        const $openConfirmationReleaseModalBtn = $("#openConfirmationUpdateReleaseModalBtn");
        const $hiddenReleaseClaimant = $("#confirmation_update_release_claimant");
        const $hiddenReleaseRelationship = $("#confirmation_update_release_relationship");
        const $hiddenReleaseTime = $("#confirmation_update_release_time");
        const $issueMonthField = $('#confirmationForm [name="issue_month"]');
        const $issueDayField = $('#confirmationForm [name="issue_day"]');
        const $issueYearField = $('#confirmationForm [name="issue_year"]');
        const $releaseDateDisplay = $("#confirmation_update_modal_release_date");
        const $modalReleaseClaimant = $("#confirmation_update_modal_release_claimant");
        const $modalReleaseRelationship = $("#confirmation_update_modal_release_relationship");
        const $modalReleaseTime = $("#confirmation_update_modal_release_time");
        const confirmationReleaseModalEl = document.getElementById('confirmationUpdateReleaseModal');
        const confirmationReleaseModal = confirmationReleaseModalEl ? new bootstrap.Modal(confirmationReleaseModalEl) : null;
        const confirmationMonthNames = [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December"
        ];

        const formatDateForDisplay = (isoDate) => {
            if (!isoDate) {
                return "";
            }
            const date = new Date(`${isoDate}T00:00:00`);
            if (Number.isNaN(date.getTime())) {
                return "";
            }
            const monthName = confirmationMonthNames[date.getMonth()] || "";
            const day = date.getDate();
            const year = date.getFullYear();
            if (!monthName || !day || !year) {
                return "";
            }
            return `${monthName} ${day}, ${year}`;
        };

        const computeReleaseDateDisplayFromFields = () => {
            const monthValueRaw = ($issueMonthField.val() || "").trim();
            const dayValueRaw = ($issueDayField.val() || "").trim();
            const yearValueRaw = ($issueYearField.val() || "").trim();
            if (!monthValueRaw || !dayValueRaw || !yearValueRaw) {
                return "";
            }

            let resolvedMonthName = "";
            const monthIndex = confirmationMonthNames.findIndex(
                (name) => name.toLowerCase() === monthValueRaw.toLowerCase()
            );

            if (monthIndex !== -1) {
                resolvedMonthName = confirmationMonthNames[monthIndex];
            } else if (/^\d+$/.test(monthValueRaw)) {
                const numericMonth = Math.max(1, Math.min(12, parseInt(monthValueRaw, 10)));
                resolvedMonthName = confirmationMonthNames[numericMonth - 1];
            } else {
                resolvedMonthName = monthValueRaw;
            }

            const dayInt = parseInt(dayValueRaw, 10);
            if (Number.isNaN(dayInt) || dayInt <= 0) {
                return "";
            }

            return resolvedMonthName && yearValueRaw
                ? `${resolvedMonthName} ${dayInt}, ${yearValueRaw}`
                : "";
        };

        const setConfirmationReleaseDateDisplay = () => {
            if (!$releaseDateDisplay.length) {
                return;
            }
            const computedDisplay = computeReleaseDateDisplayFromFields();
            if (computedDisplay) {
                $releaseDateDisplay.val(computedDisplay);
            } else {
                const fallbackDisplay = $releaseDateDisplay.data("serverDateDisplay") || "";
                $releaseDateDisplay.val(fallbackDisplay);
            }
        };

        const updateReleaseFallbackFromFields = () => {
            if (!$releaseDateDisplay.length) {
                return;
            }
            const computedDisplay = computeReleaseDateDisplayFromFields();
            if (computedDisplay) {
                $releaseDateDisplay.data("serverDateDisplay", computedDisplay);
            } else {
                $releaseDateDisplay.removeData("serverDateDisplay");
            }
        };

        $issueMonthField.on("change", updateReleaseFallbackFromFields);
        $issueDayField.on("input change", updateReleaseFallbackFromFields);
        $issueYearField.on("input change", updateReleaseFallbackFromFields);

        const $modalDayInput = $('#confirmationForm [name="issue_day"]');
        if ($modalDayInput.length) {
            $modalDayInput.on('input', function () {
                const digitsOnly = $(this).val().replace(/[^0-9]/g, '');
                $(this).val(digitsOnly);
                updateDaySuffix($modalDayInput, digitsOnly);
            });

            $modalDayInput.on('blur', function () {
                setDayInputValue($modalDayInput, $(this).val());
            });

            updateDaySuffix($modalDayInput, $modalDayInput.val());
        }

        const resetConfirmationReleaseFields = () => {
            $hiddenReleaseClaimant.val('');
            $hiddenReleaseRelationship.val('');
            $hiddenReleaseTime.val('');
            $modalReleaseClaimant.val('');
            $modalReleaseRelationship.val('');
            $modalReleaseTime.val('');
            if ($releaseDateDisplay.length) {
                $releaseDateDisplay.val('').removeData('serverDateDisplay');
            }
        };

        const ensureAtLeastOneSponsorRow = () => {
            const $sponsorInputs = $('#sponsorContainer input[name="sponsors[]"]');
            if ($sponsorInputs.length === 0) {
                $("#sponsorContainer").append(`
                    <div class="d-flex gap-2 mb-2">
                        <input type="text" class="form-control" name="sponsors[]" required>
                        <button type="button" class="btn btn-danger btn-sm removeSponsor">REMOVE</button>
                    </div>
                `);
            }
        };

        const buildConfirmationFormPayload = () => {
            ensureAtLeastOneSponsorRow();
            const formArray = $confirmationForm.serializeArray().filter((field) => {
                if (field.name === 'sponsors[]') {
                    return field.value && field.value.trim() !== '';
                }
                return true;
            });
            return $.param(formArray);
        };

        const submitConfirmationUpdateRequest = () => {
            if (!$confirmationForm.length) {
                return;
            }

            const formData = buildConfirmationFormPayload();
            $.ajax({
                url: "<?php echo BASE_URL; ?>/api/confirmation/update-con.php",
                method: "POST",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $("#confirmationUpdateModal").modal("hide");
                        if ($confirmationForm[0]) {
                            $confirmationForm[0].reset();
                        }
                        $("#sponsorContainer").empty();
                        resetConfirmationReleaseFields();
                        fetchAllConfirmations($("#search-input").val(), $("#yearpicker").val());
                    } else {
                        toastr.error(response.error || "An unexpected error occurred.");
                    }
                },
                error: function(xhr) {
                    console.error("Error response:", xhr.responseText);
                    alert("Error updating confirmation record.");
                },
            });
        };

        const openConfirmationReleaseModal = () => {
            if (!$confirmationForm.length) {
                return;
            }

            const formEl = $confirmationForm[0];
            if (formEl && !formEl.reportValidity()) {
                return;
            }

            if (!confirmationReleaseModal) {
                submitConfirmationUpdateRequest();
                return;
            }

            setConfirmationReleaseDateDisplay();
            $modalReleaseClaimant.val($hiddenReleaseClaimant.val() || '');
            $modalReleaseRelationship.val($hiddenReleaseRelationship.val() || '');
            $modalReleaseTime.val($hiddenReleaseTime.val() || '');
            confirmationReleaseModal.show();
        };

        if ($openConfirmationReleaseModalBtn.length) {
            $openConfirmationReleaseModalBtn.on('click', function(e) {
                e.preventDefault();
                openConfirmationReleaseModal();
            });
        }

        if ($confirmationForm.length) {
            $confirmationForm.on('submit', function(e) {
                e.preventDefault();
                openConfirmationReleaseModal();
            });
        }

        $("#confirmationUpdateConfirmReleaseBtn").on('click', function() {
            const claimant = ($modalReleaseClaimant.val() || '').trim();
            if (!claimant) {
                alert('Please enter the name of the person who will claim the certificate.');
                return;
            }

            $hiddenReleaseClaimant.val(claimant);
            $hiddenReleaseRelationship.val(($modalReleaseRelationship.val() || '').trim());
            $hiddenReleaseTime.val($modalReleaseTime.val() || '');

            if (confirmationReleaseModal) {
                confirmationReleaseModal.hide();
            }

            submitConfirmationUpdateRequest();
        });

        const $yearPicker = $("#yearpicker");
        
        const triggerYearFilter = () => {
            const yearValue = ($yearPicker.val() || "").trim();
            // Only trigger if empty or a valid year (4 digits)
            if (yearValue === '' || /^\d{4}$/.test(yearValue)) {
                currentPage = 1;
                fetchAllConfirmations($("#search-input").val(), yearValue);
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

            const pickerInstance = $yearPicker.data("datepicker");
            if (pickerInstance && pickerInstance.picker) {
                pickerInstance.picker.addClass("confirmation-year-dropdown");
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

            $yearPicker.on("show", function() {
                const instance = $(this).data("datepicker");
                if (instance && instance.picker) {
                    instance.picker.addClass("confirmation-year-dropdown");
                }
            });

            $yearPicker.on("hide", function() {
                const instance = $(this).data("datepicker");
                if (instance && instance.picker) {
                    instance.picker.removeClass("confirmation-year-dropdown");
                }
            });
        }

        fetchAllConfirmations($("#search-input").val(), $yearPicker.val());

        const debouncedFetch = debounce(function(searchTerm) {
            fetchAllConfirmations(searchTerm, $("#yearpicker").val());
        }, 500);

        $("#search-input").on("input", function() {
            currentPage = 1;
            debouncedFetch($(this).val());
        });

        $(document).on("click", ".pagination-link", function(e) {
            e.preventDefault();
            currentPage = parseInt($(this).data("page"));
            fetchAllConfirmations($("#search-input").val(), $("#yearpicker").val());
        });

        $(document).on("click", "#pagination-prev", function(e) {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                fetchAllConfirmations($("#search-input").val(), $("#yearpicker").val());
            }
        });

        $(document).on("click", "#pagination-next", function(e) {
            e.preventDefault();
            currentPage++;
            fetchAllConfirmations($("#search-input").val(), $("#yearpicker").val());
        });

        $(document).on("click", "#close-modal", function() {
            $("#confirmationUpdateModal").modal("hide");
            if ($confirmationForm[0]) {
                $confirmationForm[0].reset();
            }
            $("#sponsorContainer").empty();
            resetConfirmationReleaseFields();
        });

        $("#confirmationUpdateModal").on("hidden.bs.modal", function() {
            if ($confirmationForm[0]) {
                $confirmationForm[0].reset();
            }
            $("#sponsorContainer").empty();
            resetConfirmationReleaseFields();
        });

        $(document).on("click", ".open-confirmation-modal", function() {
            const id = $(this).data("edit-id");

            $.ajax({
                url: "<?php echo BASE_URL; ?>/api/confirmation/fetch-one.php",
                method: "GET",
                data: {
                    id
                },
                dataType: "json",
                success: function(res) {
                    if (res.error) {
                        alert(res.error);
                        return;
                    }

                    $('#confirmationId').val(res.id);
                    $('[name="child_name"]').val(res.child_name);
                    $('[name="parent_name1"]').val(res.parent_name1);
                    $('[name="parent_name2"]').val(res.parent_name2);
                    $('[name="bishop"]').val(res.bishop);
                    $('[name="confirmation_date"]').val(res.confirmation_date);

                    const rawIssueDay = (res.issue_day || '').toString().trim();
                    const rawIssueMonth = (res.issue_month || '').toString().trim();

                    const monthNames = [
                        'January','February','March','April','May','June',
                        'July','August','September','October','November','December'
                    ];
                    let resolvedMonth = rawIssueMonth;
                    if (/^\d+$/.test(rawIssueMonth)) {
                        const idx = Math.max(0, Math.min(11, parseInt(rawIssueMonth, 10) - 1));
                        resolvedMonth = monthNames[idx];
                    }
                    $('[name="issue_month"]').val(resolvedMonth);
                    setDayInputValue($('[name="issue_day"]'), rawIssueDay);
                    $('[name="issue_year"]').val(res.issue_year || '');

                    $('[name="book_no"]').val(res.book_no);
                    $('[name="page_no"]').val(res.page_no);
                    $('[name="entry_no"]').val(res.entry_no);

                    const sponsorContainer = $("#sponsorContainer");
                    sponsorContainer.empty();

                    if (Array.isArray(res.sponsors) && res.sponsors.length > 0) {
                        res.sponsors.forEach((s) => {
                            sponsorContainer.append(`
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" class="form-control" name="sponsors[]" value="${s.replace(/"/g, '&quot;')}" required>
                                    <button type="button" class="btn btn-danger btn-sm removeSponsor">REMOVE</button>
                                </div>
                            `);
                        });
                    } else {
                        sponsorContainer.append(`
                            <div class="d-flex gap-2 mb-2">
                                <input type="text" class="form-control" name="sponsors[]" required>
                                <button type="button" class="btn btn-danger btn-sm removeSponsor">REMOVE</button>
                            </div>
                        `);
                    }

                    const releaseClaimant = (res.release_claimant || '').trim();
                    const releaseRelationship = (res.release_relationship || '').trim();
                    const releaseTime = (res.release_time || '').trim();
                    const releaseDateIso = (res.release_date || '').trim();

                    $hiddenReleaseClaimant.val(releaseClaimant);
                    $hiddenReleaseRelationship.val(releaseRelationship);
                    $hiddenReleaseTime.val(releaseTime);

                    $modalReleaseClaimant.val(releaseClaimant);
                    $modalReleaseRelationship.val(releaseRelationship);
                    $modalReleaseTime.val(releaseTime);

                    if ($releaseDateDisplay.length) {
                        const displayValue = formatDateForDisplay(releaseDateIso);
                        if (displayValue) {
                            $releaseDateDisplay.val(displayValue);
                            $releaseDateDisplay.data('serverDateDisplay', displayValue);
                        } else {
                            $releaseDateDisplay.val('');
                            $releaseDateDisplay.removeData('serverDateDisplay');
                        }
                    }

                    setConfirmationReleaseDateDisplay();

                    $("#confirmationUpdateModal").modal("show");
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", xhr.responseText);
                    alert("Error loading confirmation data.");
                },
            });
        });

        $("#addSponsor").on("click", function(e) {
            e.preventDefault();
            $("#sponsorContainer").append(`
                <div class="d-flex gap-2 mb-2">
                    <input type="text" class="form-control" name="sponsors[]" required>
                    <button type="button" class="btn btn-danger btn-sm removeSponsor">REMOVE</button>
                </div>
            `);
        });

        $(document).on("click", ".removeSponsor", function() {
            const $container = $("#sponsorContainer");
            const $fields = $container.find('input[name="sponsors[]"]');
            if ($fields.length <= 1) {
                const $input = $fields.first();
                $input.val('').addClass('is-invalid').focus();
                return;
            }
            $(this).closest(".d-flex").remove();
        });

        $(document).on('input','input[name="sponsors[]"]', function(){
            if ($(this).val().trim() !== '') {
                $(this).removeClass('is-invalid');
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
        $("#summaryRangeMeta").text(totalRecords === 0 ? "No records to display" : `${visibleCount} records on this page`);
        const filters = [];
        const s = (searchTerm || '').trim();
        const y = (year || '').trim();
        if (s) filters.push(`Keyword: “${s}”`);
        if (y) filters.push(`Year: ${y}`);
        if (filters.length) {
            $("#summaryFilter").text(filters.join(' • '));
            $("#summaryFilterMeta").text("Filter active");
        } else {
            $("#summaryFilter").text("All records");
            $("#summaryFilterMeta").text("No additional filters applied");
        }
        $("#summaryMeta").text(archivedFilter === 1 ? "Viewing archived records" : "Active records only");
    }

    function fetchAllConfirmations(searchTerm = '', year = '') {
        const url = `<?php echo BASE_URL; ?>/api/confirmation/fetch-all.php?search=${encodeURIComponent(searchTerm)}&year=${encodeURIComponent(year)}&page=${currentPage}&archived=${archivedFilter}`;

        fetch(url)
            .then((res) => res.json())
            .then((data) => {
                const tableBody = $("#table-confirmation");
                const pagination = $('#pagination');
                tableBody.empty();
                pagination.empty();

                if (data.error || !Array.isArray(data.records) || data.records.length === 0) {
                    tableBody.append(`<tr class="empty-row"><td colspan="6">No records found.</td></tr>`);
                    const fallbackPage = Number(data && data.page) || 1;
                    const fallbackLimit = Number(data && data.limit) || limitPerPage;
                    updateSummary(0, fallbackPage, fallbackLimit, 0, searchTerm, year);
                    return;
                }

                data.records.forEach((item, index) => {
                    tableBody.append(`
                        <tr>
                            <td class="text-center">${(currentPage - 1) * data.limit + index + 1}</td>
                            <td>${item.child_name}</td>
                            <td>${item.confirmation_date}</td>
                            <td>${item.bishop}</td>
                            <td class="text-center">
                                <div class="action-group">
                                    <button class="btn btn-success btn-icon open-confirmation-modal edit-btn" data-edit-id="${item.id}">
                                        <span class="edit-icon" aria-hidden="true">
                                            <span class="edit-pencil-body"></span>
                                            <span class="edit-pencil-ferrule"></span>
                                            <span class="edit-pencil-tip"></span>
                                        </span>
                                        <span class="edit-label"></span>
                                        <span class="tooltip-label">Edit</span>
                                    </button>
                                    <a href="index.php?action=delete&id=${item.id}" class="btn btn-danger btn-icon trash-btn confirmation-delete-btn">
                                        <span class="trash-icon" aria-hidden="true">
                                            <i class="fa-solid fa-box-archive"></i>
                                        </span>
                                        <span class="trash-label"></span>
                                        <span class="tooltip-label">Archive</span>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/confirmation/print.php?id=${item.id}" target="_blank" class="btn btn-secondary btn-icon print-btn">
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
            })
            .catch((error) => {
                console.error("Fetch error:", error);
            });
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
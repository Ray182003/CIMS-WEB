<script>
 let currentPage = 1;
const limitPerPage = 5;


function getOrdinalSuffix(day) {
    const remainderTen = day % 10;
    const remainderHundred = day % 100;

    if (remainderHundred >= 11 && remainderHundred <= 13) {
        return "th";
    }

    switch (remainderTen) {
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
        
        const $yearPicker = $("#yearpicker");

    const getYearValue = () => ($yearPicker.val() || '').trim();

    const triggerYearFilter = () => {
        const yearValue = getYearValue();
        // Only trigger if empty or a valid year (4 digits)
        if (yearValue === '' || /^\d{4}$/.test(yearValue)) {
            currentPage = 1;
            fetchAllDeaths($("#search-input").val(), yearValue);
        }
    };

    if ($yearPicker.length) {
        if (typeof $.fn.datepicker === "function") {
            $yearPicker.datepicker({
                format: "yyyy",
                viewMode: "years",
                minViewMode: "years",
                autoclose: true,
                orientation: "bottom",
                container: "body",
                keyboardNavigation: false
            });

            const deathYearPickerInstance = $yearPicker.data("datepicker");
            if (deathYearPickerInstance && deathYearPickerInstance.picker) {
                deathYearPickerInstance.picker.addClass("death-year-dropdown");
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

            $yearPicker.on("show", function () {
                const pickerInstance = $(this).data("datepicker");
                if (pickerInstance && pickerInstance.picker) {
                    pickerInstance.picker.addClass("death-year-dropdown");
                }
            });

            $yearPicker.on("hide", function () {
                const pickerInstance = $(this).data("datepicker");
                if (pickerInstance && pickerInstance.picker) {
                    pickerInstance.picker.removeClass("death-year-dropdown");
                }
            });
        }

        // Keep focus/click handler for manual input
        $yearPicker.on("focus click", function () {
            if (typeof $.fn.datepicker === "function") {
                $(this).datepicker("show");
            }
        });
    }

    const initialYearValue = getYearValue();
    fetchAllDeaths('', initialYearValue);

    const $dayInput = $('#deathForm [name="issue_day"]');

    const $deathForm = $("#deathForm");
    const $openDeathReleaseModalBtn = $("#openDeathUpdateReleaseModalBtn");
    const $hiddenReleaseClaimant = $("#death_update_release_claimant");
    const $hiddenReleaseRelationship = $("#death_update_release_relationship");
    const $hiddenReleaseTime = $("#death_update_release_time");
    const $issueMonthField = $('#deathForm [name="issue_place"]');
    const $issueDayField = $('#deathForm [name="issue_day"]');
    const $issueYearField = $('#deathForm [name="issue_year"]');
    const $releaseDateDisplay = $("#death_update_modal_release_date");
    const $modalReleaseClaimant = $("#death_update_modal_release_claimant");
    const $modalReleaseRelationship = $("#death_update_modal_release_relationship");
    const $modalReleaseTime = $("#death_update_modal_release_time");
    const deathReleaseModalEl = document.getElementById('deathUpdateReleaseModal');
    const deathReleaseModal = deathReleaseModalEl ? new bootstrap.Modal(deathReleaseModalEl) : null;

    const deathMonthNames = [
        'January','February','March','April','May','June','July','August','September','October','November','December'
    ];

    const normalizeDeathMonth = (raw) => {
        const value = (raw || '').toString().trim();
        if (value === '') {
            return '';
        }
        const lower = value.toLowerCase();
        const matchIndex = deathMonthNames.findIndex((month) => month.toLowerCase() === lower);
        if (matchIndex !== -1) {
            return deathMonthNames[matchIndex];
        }
        if (/^\d+$/.test(value)) {
            const numeric = Math.max(1, Math.min(12, parseInt(value, 10))) - 1;
            return deathMonthNames[numeric] || '';
        }
        return value;
    };

    const formatDeathReleaseDate = (iso) => {
        if (!iso) {
            return '';
        }
        const parsed = new Date(`${iso}T00:00:00`);
        if (Number.isNaN(parsed.getTime())) {
            return '';
        }
        const monthName = deathMonthNames[parsed.getMonth()] || '';
        const day = parsed.getDate();
        const year = parsed.getFullYear();
        if (!monthName || !day || !year) {
            return '';
        }
        return `${monthName} ${day}, ${year}`;
    };

    const computeDeathReleaseDisplayFromFields = () => {
        const monthRaw = normalizeDeathMonth($issueMonthField.val());
        const dayRaw = ($issueDayField.val() || '').toString().trim();
        const yearRaw = ($issueYearField.val() || '').toString().trim();
        if (!monthRaw || !dayRaw || !yearRaw) {
            return '';
        }
        const dayInt = parseInt(dayRaw, 10);
        if (Number.isNaN(dayInt) || dayInt <= 0) {
            return '';
        }
        return `${monthRaw} ${dayInt}, ${yearRaw}`;
    };

    const updateDeathReleaseFallbackFromFields = () => {
        if (!$releaseDateDisplay.length) {
            return;
        }
        const computed = computeDeathReleaseDisplayFromFields();
        if (computed) {
            $releaseDateDisplay.data('serverDisplay', computed);
        } else {
            $releaseDateDisplay.removeData('serverDisplay');
        }
    };

    const applyDeathReleaseDateDisplay = () => {
        if (!$releaseDateDisplay.length) {
            return;
        }
        const computed = computeDeathReleaseDisplayFromFields();
        if (computed) {
            $releaseDateDisplay.val(computed);
        } else {
            const fallback = $releaseDateDisplay.data('serverDisplay') || '';
            $releaseDateDisplay.val(fallback);
        }
    };

    $issueMonthField.on('change', updateDeathReleaseFallbackFromFields);
    $issueDayField.on('input change', updateDeathReleaseFallbackFromFields);
    $issueYearField.on('input change', updateDeathReleaseFallbackFromFields);

    const resetDeathReleaseFields = () => {
        $hiddenReleaseClaimant.val('');
        $hiddenReleaseRelationship.val('');
        $hiddenReleaseTime.val('');
        $modalReleaseClaimant.val('');
        $modalReleaseRelationship.val('');
        $modalReleaseTime.val('');
        if ($releaseDateDisplay.length) {
            $releaseDateDisplay.val('').removeData('serverDisplay');
        }
    };

    const buildDeathFormPayload = () => {
        if (!$deathForm.length) {
            return '';
        }
        const formArray = $deathForm.serializeArray();
        return $.param(formArray.map((field) => ({
            name: field.name,
            value: typeof field.value === 'string' ? field.value.trim() : field.value
        })));
    };

    const submitDeathUpdateRequest = () => {
        if (!$deathForm.length) {
            return;
        }

        const formData = buildDeathFormPayload();
        $.ajax({
            url: "<?php echo BASE_URL; ?>/api/death/update-death.php",
            method: "POST",
            data: formData,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    $("#deathUpdateModal").modal("hide");
                    if ($deathForm[0]) {
                        $deathForm[0].reset();
                    }
                    resetDeathReleaseFields();
                    fetchAllDeaths($("#search-input").val(), getYearValue());
                } else {
                    toastr.error(response.error);
                }
            },
            error: function () {
                alert("Error updating death record.");
            },
        });
    };

    const openDeathReleaseModal = () => {
        if (!$deathForm.length) {
            return;
        }

        const formEl = $deathForm[0];
        if (formEl && !formEl.reportValidity()) {
            return;
        }

        if (!deathReleaseModal) {
            submitDeathUpdateRequest();
            return;
        }

        applyDeathReleaseDateDisplay();
        $modalReleaseClaimant.val($hiddenReleaseClaimant.val() || '');
        $modalReleaseRelationship.val($hiddenReleaseRelationship.val() || '');
        $modalReleaseTime.val($hiddenReleaseTime.val() || '');
        deathReleaseModal.show();
    };

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

    const clampDayValue = ($input, value) => {
        if (!$input.length) {
            return '';
        }

        const numericDay = parseInt(value, 10);
        if (Number.isNaN(numericDay) || numericDay <= 0) {
            $input.val('');
            updateDaySuffix($input, '');
            return '';
        }

        const clampedDay = Math.min(Math.max(numericDay, 1), 31);
        $input.val(clampedDay);
        updateDaySuffix($input, clampedDay);
        return String(clampedDay);
    };

    if ($dayInput.length) {
        $dayInput.on('input', function () {
            const digitsOnly = $(this).val().replace(/[^0-9]/g, '');
            $(this).val(digitsOnly);
            updateDaySuffix($dayInput, digitsOnly);
        });

        $dayInput.on('blur', function () {
            clampDayValue($dayInput, $(this).val());
        });

        updateDaySuffix($dayInput, $dayInput.val());
    }

    const debouncedFetch = debounce(function (searchTerm) {
        fetchAllDeaths(searchTerm, getYearValue());
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
        fetchAllDeaths($("#search-input").val(), getYearValue());
    });

    $(document).on("click", "#pagination-prev", function (e) {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            fetchAllDeaths($("#search-input").val(), getYearValue());
        }
    });

    $(document).on("click", "#pagination-next", function (e) {
        e.preventDefault();
        currentPage++;
        fetchAllDeaths($("#search-input").val(), getYearValue());
    });

    $("#close-modal").click(() => {
        $("#deathUpdateModal").modal("hide");
        $("#deathForm")[0].reset();
    });

    if ($openDeathReleaseModalBtn.length) {
        $openDeathReleaseModalBtn.on('click', function(e) {
            e.preventDefault();
            openDeathReleaseModal();
        });
    }

    if ($deathForm.length) {
        $deathForm.on('submit', function (e) {
            e.preventDefault();
            openDeathReleaseModal();
        });
    }

    $("#deathUpdateConfirmReleaseBtn").on('click', function() {
        const claimant = ($modalReleaseClaimant.val() || '').trim();
        if (!claimant) {
            alert('Please enter the name of the person who will claim the certificate.');
            return;
        }

        $hiddenReleaseClaimant.val(claimant);
        $hiddenReleaseRelationship.val(($modalReleaseRelationship.val() || '').trim());
        $hiddenReleaseTime.val($modalReleaseTime.val() || '');

        if (deathReleaseModal) {
            deathReleaseModal.hide();
        }

        submitDeathUpdateRequest();
    });

    $(document).on("click", ".open-death-modal", function () {
        const id = $(this).data("edit-id");

        $.ajax({
            url: "<?php echo BASE_URL; ?>/api/death/fetch-one.php",
            method: "GET",
            data: { id },
            dataType: "json",
            success: function (res) {
                if (res.error) {
                    alert(res.error);
                } else {
                    $('#deathId').val(res.id);
                    $('[name="name"]').val(res.name);
                    $('[name="address"]').val(res.address);
                    $('[name="date_of_death"]').val(res.date_of_death);
                    $('[name="cause_of_death"]').val(res.cause_of_death);
                    $('[name="book_no"]').val(res.book_no);
                    $('[name="page_no"]').val(res.page_no);
                    const $monthField = $('[name="issue_place"]');
                    if ($monthField.length) {
                        $monthField.val(res.issue_place || '');
                    }

                    const $dayField = $('[name="issue_day"]');
                    if ($dayField.length) {
                        const numericDay = parseInt(String(res.issue_day || '').replace(/[^0-9]/g, ''), 10);
                        if (!Number.isNaN(numericDay)) {
                            clampDayValue($dayField, numericDay);
                        } else {
                            $dayField.val('');
                            updateDaySuffix($dayField, '');
                        }
                    }

                    $('[name="issue_year"]').val(res.issue_year);
                    $('[name="entry_no"]').val(res.entry_no);

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
                        const displayValue = formatDeathReleaseDate(releaseDateIso);
                        if (displayValue) {
                            $releaseDateDisplay.val(displayValue);
                            $releaseDateDisplay.data('serverDisplay', displayValue);
                        } else {
                            $releaseDateDisplay.val('');
                            updateDeathReleaseFallbackFromFields();
                        }
                    }

                    $("#deathUpdateModal").modal("show");
                }
            },
            error: function () {
                alert("Error loading death record.");
            },
        });
    });
});

    function fetchAllDeaths(searchTerm = '', year = '') {
        const url = `<?php echo BASE_URL; ?>/api/death/fetch-all.php?search=${encodeURIComponent(searchTerm)}&year=${encodeURIComponent(year)}&page=${currentPage}`;

        fetch(url)
            .then((res) => res.json())
            .then((data) => {
                const tableBody = $("#table-death");
                const pagination = $('#pagination');
                tableBody.empty();
                pagination.empty();

                if (data.error || data.records.length === 0) {
                    tableBody.append(`
                    <tr>
                        <td colspan="6" class="text-center">No records found.</td>
                    </tr>
                `);
                } else {
                    data.records.forEach((item, index) => {
                        tableBody.append(`
                        <tr>
                            <td class="text-center">${(currentPage - 1) * data.limit + index + 1}</td>
                            <td>${item.name}</td>
                            <td>${item.address}</td>
                            <td>${item.date_of_death}</td>
                            <td class="text-center">
                                <button class="btn btn-success btn-sm open-death-modal edit-btn" data-edit-id="${item.id}">
                                    <span class="edit-icon" aria-hidden="true">
                                        <span class="edit-pencil-body"></span>
                                        <span class="edit-pencil-ferrule"></span>
                                        <span class="edit-pencil-tip"></span>
                                    </span>
                                    <span class="edit-label"></span>
                                </button>
                                <a href="index.php?action=delete&id=${item.id}" class="btn btn-danger btn-sm trash-btn death-delete-btn">
                                    <span class="trash-icon" aria-hidden="true">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </span>
                                    <span class="trash-label"></span>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/cer_of_death/print.php?id=${item.id}" target="_blank" class="btn btn-secondary btn-sm print-btn">
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
                }
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
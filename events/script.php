<style>
    .fc-event-title,
    .fc-event-time {
        display: none !important;
    }
    .fc-daygrid-day-frame {
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .fc-daygrid-day {
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .fc-daygrid-day .fc-daygrid-day-number {
        margin: 0 !important;
        padding: 4px !important;
    }
    .fc-event {
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        outline: none !important;
        box-shadow: none !important;
    }
    .fc-event-main {
        padding: 2px 4px !important;
        margin: 0 !important;
    }
</style>

<script>
    let currentPage = 1;
    const limitPerPage = 5;
    let calendar;
    let allCalendarEvents = [];

    // Function to format time to 12-hour format with am/pm
    function formatTime(time) {
        if (!time) return '—';
        const timeStr = String(time).trim();
        if (timeStr.toLowerCase().includes('am') || timeStr.toLowerCase().includes('pm')) {
            return timeStr;
        }
        const [hours, minutes] = time.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'pm' : 'am';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    }

    function hexToRgb(hex) {
        hex = hex.replace(/^#/, '');
        if (hex.length === 3) hex = hex.split('').map(x => x + x).join('');
        const bigint = parseInt(hex, 16);
        return {
            r: (bigint >> 16) & 255,
            g: (bigint >> 8) & 255,
            b: bigint & 255
        };
    }

    function rgbToHex(r, g, b) {
        return "#" + [r, g, b].map(x => {
            const hex = x.toString(16);
            return hex.length === 1 ? "0" + hex : hex;
        }).join('');
    }

    function mixColors(hexColors) {
        if (!hexColors || hexColors.length === 0) return '#6c757d';
        if (hexColors.length === 1) return hexColors[0];
        
        let totalR = 0, totalG = 0, totalB = 0;
        
        hexColors.forEach(hex => {
            const rgb = hexToRgb(hex);
            totalR += rgb.r;
            totalG += rgb.g;
            totalB += rgb.b;
        });
        
        const avgR = Math.round(totalR / hexColors.length);
        const avgG = Math.round(totalG / hexColors.length);
        const avgB = Math.round(totalB / hexColors.length);
        
        return rgbToHex(avgR, avgG, avgB);
    }

    function formatEventDate(dateStr) {
        if (!dateStr) return '—';

        const dateObj = new Date(`${dateStr}T00:00:00`);
        if (Number.isNaN(dateObj.getTime())) {
            // kung hindi ma-parse, ibalik na lang yung raw na value (YYYY-MM-DD)
            return dateStr;
        }

        // Halimbawa output: "22 December 2025"
        return dateObj.toLocaleDateString('en-GB', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    function renderCalendar() {
        fetch("<?php echo BASE_URL; ?>/api/events/fetch-calendar-events.php")
            .then(res => res.json())
            .then(calendarEvents => {
                const calendarEl = document.getElementById('calendar');
                const typeColors = {
                    "Baptismal Certificate": "#0D6EFD",
                    "Marriage": "#FF6B9D",
                    "Confirmation": "#66BB6A",
                    "Requiem": "Black"
                };

                const eventMap = {};
                calendarEvents.forEach(event => {
                    const dateKey = event.event_date;
                    if (!eventMap[dateKey]) eventMap[dateKey] = [];
                    eventMap[dateKey].push(event.type);
                });

                // Now set colors for all events based on mixed colors
                calendarEvents.forEach(event => {
                    const dateKey = event.event_date;
                    if (eventMap[dateKey]) {
                        const uniqueTypes = [...new Set(eventMap[dateKey])];
                        const colors = uniqueTypes.map(t => typeColors[t] || '#6c757d');
                        const blendedColor = mixColors(colors);
                        
                        event.backgroundColor = blendedColor;
                        event.borderColor = blendedColor;
                        event.textColor = (blendedColor === '#000000') ? '#fff' : '#000';
                    }
                });

                if (calendar) {
                    calendar.destroy();
                }

                allCalendarEvents = calendarEvents;

                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 600,
                    events: calendarEvents,
                    eventDisplay: 'background',
                    customButtons: {
                        monthEvents: {
                            text: 'Month events',
                            click: () => {
                                if (!calendar) return;
                                showMonthEventsModal(calendar.getDate());
                            }
                        }
                    },
                    headerToolbar: {
                        left: 'prev,next',
                        center: 'title',
                        right: 'monthEvents today'
                    },

                    dateClick: function(info) {
                        const selectedDate = info.dateStr; // format YYYY-MM-DD
                        showDateEventsModal(selectedDate);
                    },

                    dayCellDidMount: function(info) {
                        const dateStr = info.date.toLocaleDateString('en-CA');
                        const dayCellEl = info.el;

                        if (eventMap[dateStr]) {
                            const uniqueTypes = [...new Set(eventMap[dateStr])];
                            const colors = uniqueTypes.map(t => typeColors[t] || '#6c757d');
                            const blendedColor = mixColors(colors);
                            dayCellEl.style.backgroundColor = blendedColor;
                            dayCellEl.style.color = (blendedColor === '#000000') ? '#fff' : '#000';
                            dayCellEl.style.border = 'none';
                            dayCellEl.style.outline = 'none';
                            dayCellEl.style.boxShadow = 'none';

                            // Also apply to inner frame for complete coverage
                            const frameEl = dayCellEl.querySelector('.fc-daygrid-day-frame');
                            if (frameEl) {
                                frameEl.style.backgroundColor = blendedColor;
                                frameEl.style.border = 'none';
                                frameEl.style.outline = 'none';
                                frameEl.style.boxShadow = 'none';
                            }
                        }
                    }
                });

                calendar.render();
            });
    }

    function fetchAll(searchTerm = null) {
        const searchValue = searchTerm !== null ? searchTerm : ($('#search-input').val() || '');
        const startDate = $('#filter-start-date').val() || '';
        const endDate = $('#filter-end-date').val() || '';

        if (startDate && endDate && startDate > endDate) {
            toastr.error("'From' date cannot be later than 'To' date.");
            return;
        }

        const params = new URLSearchParams({
            search: searchValue,
            page: currentPage
        });

        if (startDate) params.append('startDate', startDate);
        if (endDate) params.append('endDate', endDate);

        const url = `<?php echo BASE_URL; ?>/api/events/fetch-all.php?${params.toString()}`;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                const tableBody = $("#table-event");
                const pagination = $('#pagination');
                tableBody.empty();
                pagination.empty();

                if (data.error) {
                    tableBody.append(`<tr><td colspan="6" class="text-center text-danger">${data.error}</td></tr>`);
                    return;
                }

                const records = Array.isArray(data.records) ? data.records : [];

                if (records.length === 0) {
                    tableBody.append(`<tr><td colspan="6" class="text-center">No events found.</td></tr>`);
                } else {
                    records.forEach((event, index) => {
                        const isCertificateEvent = Boolean(event.is_certificate_event);
                        tableBody.append(`
                            <tr>
                                <td class='text-center align-middle'>${(currentPage - 1) * data.limit + index + 1}</td>
                                <td class='align-middle'>${event.type}</td>
                                <td class='align-middle'>${formatEventDate(event.event_date)}</td>
                                <td class='align-middle'>${formatTime(event.time)}</td>
                                <td class='align-middle'>${event.description}</td>
                                <td class='text-center align-middle'>
                                    <button class="btn btn-success btn-sm open-modal edit-btn ${isCertificateEvent ? 'disabled' : ''}" data-edit-id="${event.id}" data-is-certificate="${isCertificateEvent ? 1 : 0}" ${isCertificateEvent ? 'disabled aria-disabled="true" title="Certificate-issued events cannot be edited"' : ''}>
                                        <span class="edit-icon" aria-hidden="true">
                                            <span class="edit-pencil-body"></span>
                                            <span class="edit-pencil-ferrule"></span>
                                            <span class="edit-pencil-tip"></span>
                                        </span>
                                        <span class="edit-label"></span>
                                    </button>
                                    <a href="index.php?action=delete&id=${event.id}" class="btn btn-danger btn-sm trash-btn" onclick="return confirm('Are you sure?');">
                                        <span class="trash-icon" aria-hidden="true">
                                            <span class="trash-lid"></span>
                                            <span class="trash-body"></span>
                                            <span class="trash-handle"></span>
                                        </span>
                                        <span class="trash-label"></span>
                                    </a>
                                </td>
                            </tr>
                        `);
                    });

                    const totalPages = Math.ceil(data.total / data.limit);
                    pagination.append(`<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" id="pagination-prev">Previous</a></li>`);

                    for (let i = 1; i <= totalPages; i++) {
                        pagination.append(`<li class="page-item ${i === data.page ? 'active' : ''}"><a class="page-link pagination-link" href="#" data-page="${i}">${i}</a></li>`);
                    }

                    pagination.append(`<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" id="pagination-next">Next</a></li>`);
                }
            })

            .catch(error => {
                console.error("Fetch error:", error);
            });
    }

    $(document).on("click", ".pagination-link", function(e) {
        e.preventDefault();
        currentPage = parseInt($(this).data("page"));
        fetchAll($("#search-input").val());
    });

    $(document).on("click", "#pagination-prev", function(e) {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            fetchAll($("#search-input").val());
        }
    });

    $(document).on("click", "#pagination-next", function(e) {
        e.preventDefault();
        currentPage++;
        fetchAll($("#search-input").val());
    });

    const debouncedFetch = debounce(function(searchTerm) {
        fetchAll(searchTerm);
    }, 500);

    $("#search-input").on("input", function() {
        currentPage = 1;
        debouncedFetch($(this).val());
    });

    $("#filter-start-date, #filter-end-date").on("change", function() {
        currentPage = 1;
        fetchAll();
    });

    $("#clear-date-filters").on("click", function() {
        $("#filter-start-date, #filter-end-date").val('');
        currentPage = 1;
        fetchAll();
    });

    $(document).on('click', '.open-modal', function() {
        const isCertificateEvent = $(this).data('isCertificate');
        if (isCertificateEvent) {
            toastr.warning('This event was issued from a certificate and cannot be edited.');
            return;
        }
        let eventId = $(this).data("edit-id");
        $.ajax({
            url: "<?php echo BASE_URL; ?>/api/events/fetch-one.php",
            method: 'GET',
            data: {
                id: eventId
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    toastr.error(response.error);
                } else {
                    $('#eventId').val(response.id);
                    $('#eventType').val(response.type);
                    $('#eventDate').val(response.event_date);
                    $('#event-time').val(response.time);
                    $('#event-description').val(response.description);
                    $('#editModal').modal('show');
                }
            },
            error: function() {
                alert('Error fetching event data.');
            }
        });
    });

    $("#eventForm").submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: "<?php echo BASE_URL; ?>/api/events/update-event.php",
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $("#editModal").modal("hide");
                    $("#eventForm")[0].reset();
                    fetchAll();
                    renderCalendar();
                } else {
                    toastr.error(response.error);
                }
            },
            error: function() {
                alert('Error updating event.');
            }
        });
    });

    function showDateEventsModal(selectedDate) {
        // Format date for display
        const dateObj = new Date(selectedDate + 'T00:00:00');
        const formattedDate = dateObj.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        // Set the modal title
        document.getElementById('selectedDate').textContent = formattedDate;
        
        // Fetch events for the selected date
        fetch(`<?php echo BASE_URL; ?>/api/events/fetch-all.php?search=${encodeURIComponent(selectedDate)}&page=1`)
            .then(res => res.json())
            .then(data => {
                const tableBody = document.getElementById('dateEventsTable');
                tableBody.innerHTML = '';
                
                if (data.error || data.records.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="3" class="text-center">No events found for this date.</td></tr>`;
                } else {
                    data.records.forEach(event => {
                        tableBody.innerHTML += `
                            <tr>
                                <td class="align-middle">
                                    <span class="badge bg-${getEventTypeColor(event.type)}">${event.type}</span>
                                </td>
                                <td class="align-middle">${formatTime(event.time)}</td>
                                <td class="align-middle">${event.description}</td>
                            </tr>
                        `;
                    });
                }
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('dateEventsModal'));
                modal.show();
            })
            .catch(error => {
                console.error("Error fetching date events:", error);
                alert("Error loading events for this date.");
            });
    }
    
    function getEventTypeColor(type) {
        const colors = {
            "Baptismal Certificate": "primary",
            "Confirmation": "success", 
            "Marriage": "danger",
            "Requiem": "dark"
        };
        return colors[type] || "secondary";
    }

    function showMonthEventsModal(referenceDate) {
        const monthName = referenceDate.toLocaleDateString('en-US', {
            month: 'long',
            year: 'numeric'
        });
        document.getElementById('selectedMonth').textContent = monthName;

        const targetMonth = referenceDate.getMonth();
        const targetYear = referenceDate.getFullYear();

        const monthEvents = allCalendarEvents.filter(event => {
            if (!event.event_date) return false;
            const eventDate = new Date(event.event_date + 'T00:00:00');
            return eventDate.getMonth() === targetMonth && eventDate.getFullYear() === targetYear;
        }).sort((a, b) => {
            const dateTimeA = new Date(`${a.event_date}T${a.time || '00:00:00'}`);
            const dateTimeB = new Date(`${b.event_date}T${b.time || '00:00:00'}`);
            return dateTimeA - dateTimeB;
        });

        const tableBody = document.getElementById('monthEventsTable');
        tableBody.innerHTML = '';

        if (monthEvents.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="4" class="text-center">No events scheduled for this month.</td></tr>`;
        } else {
            monthEvents.forEach(event => {
                tableBody.innerHTML += `
                    <tr>
                        <td class="align-middle">${formatDateForDisplay(event.event_date)}</td>
                        <td class="align-middle">
                            <span class="badge bg-${getEventTypeColor(event.type)}">${event.type}</span>
                        </td>
                        <td class="align-middle">${formatTime(event.time)}</td>
                        <td class="align-middle">${event.description || ''}</td>
                    </tr>
                `;
            });
        }

        const modal = new bootstrap.Modal(document.getElementById('monthEventsModal'));
        modal.show();
    }

    function formatDateForDisplay(dateStr) {
        if (!dateStr) return '';
        const dateObj = new Date(dateStr + 'T00:00:00');
        return dateObj.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'long',
            day: 'numeric'
        });
    }

    function debounce(func, delay) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, args), delay);
        };
    }

    $(document).ready(function() {
        renderCalendar();
        fetchAll();
        
        // Add animation classes to buttons on hover
        $(document).on('mouseenter', '.edit-btn', function() {
            $(this).addClass('animate-once');
            setTimeout(() => {
                $(this).removeClass('animate-once');
            }, 750);
        });
        
        $(document).on('mouseenter', '.trash-btn', function() {
            $(this).addClass('animate-once');
            setTimeout(() => {
                $(this).removeClass('animate-once');
            }, 850);
        });
    });
</script>
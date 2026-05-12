<div class="modal fade" id="editModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h1 class="modal-title fs-5 text-white">UPDATE EVENT</h1>
                <!-- <button type="button" class="btn-close" id="close-modal"></button> -->
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <input type="hidden" id="eventId" name="eventId">
                    <div class="row">
                        <div class="col-lg-4 mb-3">
                            <label class="form-label fw-semibold">Event Type</label>
                            <select class="form-select" name="eventType" id="eventType" required>
                                <option value="" selected disabled>Select Event Type</option>
                                <option value="Baptism">Baptism</option>
                                <option value="Confirmation">Confirmation</option>
                                <option value="Marriage">Marriage</option>
                                <!-- <option value="Permit to Marry">Permit to Marry</option> -->
                                <option value="Requiem">Requiem</option>
                            </select>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <label class="form-label fw-semibold">Date</label>
                            <input type="date" name="eventDate" id="eventDate" class="form-control" required>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <label class="form-label fw-semibold">Time</label>
                            <input type="time" name="eventTime" id="event-time" class="form-control" required>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="eventDescription" class="form-control" id="event-description" rows="3"></textarea>
                        </div>

                        <div class="col-auto my-3 ms-auto">
                            <button type="submit" class="btn btn-primary">Submit</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
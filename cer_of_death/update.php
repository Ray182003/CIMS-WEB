 <link href="<?php echo BASE_URL; ?>/cer_of_death/css/modal.css" rel="stylesheet">
<div class="modal fade" id="deathUpdateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="deathModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h1 class="modal-title fs-5 text-white" id="deathModalLabel">UPDATE DEATH RECORD</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
            </div>

            <div class="modal-body">
                <form id="deathForm" method="POST">
                    <input type="hidden" name="id" id="deathId">

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Name </label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Address</label>
                            <input type="text" name="address" id="address" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Date of Death</label>
                            <input type="date" name="date_of_death" id="date_of_death" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Cause of Death</label>
                            <input type="text" name="cause_of_death" id="cause_of_death" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Day</label>
                            <div class="input-group">
                                <input type="number" name="issue_day" id="issue_day" class="form-control" required inputmode="numeric" autocomplete="off" min="1" max="31" data-suffix-target="deathModalDaySuffix">
                                <span class="input-group-text" id="deathModalDaySuffix">th</span>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Month</label>
                            <select name="issue_place" id="issue_place" class="form-select" required>
                                <option value="" disabled>Select month</option>
                                <?php
                                $deathMonths = [
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
                                foreach ($deathMonths as $monthName): ?>
                                    <option value="<?php echo $monthName; ?>"><?php echo $monthName; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Year</label>
                            <input type="text" name="issue_year" id="issue_year" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Book No</label>
                            <input type="number" name="book_no" id="book_no" class="form-control" min="0" step="1" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Page No</label>
                            <input type="number" name="page_no" id="page_no" class="form-control" min="0" step="1" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Entry No</label>
                            <input type="number" name="entry_no" id="entry_no" class="form-control" min="0" step="1" required>
                        </div>
                    </div>
                    <input type="hidden" name="release_claimant" id="death_update_release_claimant">
                    <input type="hidden" name="release_relationship" id="death_update_release_relationship">
                    <input type="hidden" name="release_time" id="death_update_release_time">
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="openDeathUpdateReleaseModalBtn">Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deathUpdateReleaseModal" tabindex="-1" aria-labelledby="deathUpdateReleaseModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deathUpdateReleaseModalLabel">Certificate Release Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name of Claimant</label>
                    <input type="text" class="form-control" id="death_update_modal_release_claimant" placeholder="Enter claimant name">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Relationship to Owner (optional)</label>
                    <input type="text" class="form-control" id="death_update_modal_release_relationship" placeholder="e.g. Mother, Brother">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Date of Release</label>
                    <input type="text" class="form-control" id="death_update_modal_release_date" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pickup Time (optional)</label>
                    <input type="time" class="form-control" id="death_update_modal_release_time">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                <button type="button" class="btn btn-primary" id="deathUpdateConfirmReleaseBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>
<?php
$confirmationMonthOptions = [
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
?>
<style>
    .modal-dialog-scrollable .modal-body {
        max-height: calc(100vh - 200px);    
        overflow-y: auto;
    }
</style>

<div class="modal fade" id="confirmationUpdateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="confirmationForm">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="confirmationModalLabel">Update Confirmation Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id" id="confirmationId">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Child Name</label>
                        <input type="text" class="form-control" name="child_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Father's Name</label>
                        <input type="text" class="form-control" name="parent_name1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mother's Name</label>
                        <input type="text" class="form-control" name="parent_name2" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Presider</label>
                        <input type="text" class="form-control" name="bishop" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirmation Date</label>
                        <input type="date" class="form-control" name="confirmation_date" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Month Issuing</label>
                            <select class="form-select" name="issue_month" required>
                                <option value="" disabled>Select month</option>
                                <?php foreach ($confirmationMonthOptions as $monthName): ?>
                                    <option value="<?php echo $monthName; ?>"><?php echo $monthName; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Day of confirmation</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="issue_day" required inputmode="numeric" autocomplete="off" min="1" max="31" data-suffix-target="confirmationModalDaySuffix">
                                <span class="input-group-text" id="confirmationModalDaySuffix">th</span>
                            </div>
                        </div>

                        <input type="hidden" name="issue_year">


                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sponsors</label>
                            <div id="sponsorContainer"></div>
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary btn-sm" id="addSponsor">+ Add Sponsor</button>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Book No.</label>
                                <input type="number" class="form-control" name="book_no" min="0" step="1" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Page No.</label>
                                <input type="number" class="form-control" name="page_no" min="0" step="1" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Entry No.</label>
                                <input type="number" class="form-control" name="entry_no" min="0" step="1" required>
                            </div>
                        </div>
                        <input type="hidden" name="release_claimant" id="confirmation_update_release_claimant">
                        <input type="hidden" name="release_relationship" id="confirmation_update_release_relationship">
                        <input type="hidden" name="release_time" id="confirmation_update_release_time">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="openConfirmationUpdateReleaseModalBtn">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmationUpdateReleaseModal" tabindex="-1" aria-labelledby="confirmationUpdateReleaseModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationUpdateReleaseModalLabel">Any changes?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name of Claimant</label>
                    <input type="text" class="form-control" id="confirmation_update_modal_release_claimant" placeholder="Enter claimant name">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Relationship to Owner (optional)</label>
                    <input type="text" class="form-control" id="confirmation_update_modal_release_relationship" placeholder="e.g. Mother, Brother">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Date of Release</label>
                    <input type="text" class="form-control" id="confirmation_update_modal_release_date" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pickup Time (optional)</label>
                    <input type="time" class="form-control" id="confirmation_update_modal_release_time">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                <button type="button" class="btn btn-primary" id="confirmationUpdateConfirmReleaseBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>
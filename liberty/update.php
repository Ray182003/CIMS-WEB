 <?php
$libertyMonthOptions = [
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
<link href="<?php echo BASE_URL; ?>/liberty/css/modal.css" rel="stylesheet">
<div class="modal fade" id="libertyUpdateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="libertyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h1 class="modal-title fs-5 text-white" id="libertyModalLabel">UPDATE LIBERTY RECORD</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
            </div>

            <div class="modal-body">
                <form id="libertyForm" method="POST">
                    <input type="hidden" name="id" id="libertyId">

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Name of Groom</label>
                            <input type="text" name="child_name" class="form-control" required>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Father Name</label>
                            <input type="text" name="parent_name1" class="form-control" required>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Mother Name</label>
                            <input type="text" name="parent_name2" class="form-control" required>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Address</label>
                            <input type="text" name="person_name1" class="form-control" required>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">Name of Bride</label>
                            <input type="text" name="person_name2" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Month</label>
                            <select name="month" class="form-select" required>
                                <option value="" disabled>Select month</option>
                                <?php foreach ($libertyMonthOptions as $monthName): ?>
                                    <option value="<?php echo $monthName; ?>"><?php echo $monthName; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Day</label>
                            <div class="input-group">
                                <input type="number" name="day" class="form-control" required inputmode="numeric" autocomplete="off" min="1" max="31" data-suffix-target="libertyModalDaySuffix">
                                <span class="input-group-text" id="libertyModalDaySuffix">th</span>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Year </label>
                            <input type="number" name="year" class="form-control" inputmode="numeric" pattern="\d{4}" min="1000" max="9999" required>
                        </div>
                    </div>
                    <input type="hidden" name="release_claimant" id="liberty_update_release_claimant">
                    <input type="hidden" name="release_relationship" id="liberty_update_release_relationship">
                    <input type="hidden" name="release_time" id="liberty_update_release_time">
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="openLibertyUpdateReleaseModalBtn">Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="libertyUpdateReleaseModal" tabindex="-1" aria-labelledby="libertyUpdateReleaseModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="libertyUpdateReleaseModalLabel">Certificate Release Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name of Claimant</label>
                    <input type="text" class="form-control" id="liberty_update_modal_release_claimant" placeholder="Enter claimant name">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Relationship to Owner (optional)</label>
                    <input type="text" class="form-control" id="liberty_update_modal_release_relationship" placeholder="e.g. Mother, Brother">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Date of Release</label>
                    <input type="text" class="form-control" id="liberty_update_modal_release_date" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pickup Time (optional)</label>
                    <input type="time" class="form-control" id="liberty_update_modal_release_time">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                <button type="button" class="btn btn-primary" id="libertyUpdateConfirmReleaseBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>
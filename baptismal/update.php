 <link href="<?php echo BASE_URL; ?>/baptismal/css/modal.css" rel="stylesheet">
 <div class="modal fade" id="baptismalUpdateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="baptismalModalLabel" aria-hidden="true">
     <div class="modal-dialog modal-xl modal-dialog-scrollable">
         <div class="modal-content">
             <div class="modal-header bg-primary">
                 <h1 class="modal-title fs-5 text-white" id="baptismalModalLabel">UPDATE BAPTISMAL RECORD</h1>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
             </div>

             <div class="modal-body">
                 <form id="baptismalForm" method="POST">
                     <input type="hidden" name="id" id="baptismalId">

                     <div class="row">
                         <div class="col-12 mb-3">
                             <label class="form-label fw-semibold">Child Name</label>
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
                             <input type="text" name="date" class="form-control" required>
                         </div>

                         <div class="col-lg-6 mb-3">
                             <label class="form-label fw-semibold">Date of Birth</label>
                             <input type="date" name="in" class="form-control" required>
                         </div>

                         <div class="col-lg-6 mb-3">
                             <label class="form-label fw-semibold">Date of Baptism</label>
                             <input type="date" name="on" class="form-control" required>
                         </div>

                         <div class="col-12 mb-3">
                             <label class="form-label fw-semibold">Presider</label>
                             <input type="text" name="bishop" class="form-control" required>
                         </div>

                         <div class="col-12 mb-3">
                             <label class="form-label fw-semibold">Sponsors</label>
                             <div id="sponsorContainer"></div>
                             <div class="text-end">
                                 <button type="button" id="addSponsor" class="btn btn-primary btn-sm">+ ADD SPONSOR</button>
                             </div>
                         </div>

                         <div class="row">
                             <div class="col-lg-4 col-12 mb-3">
                               <label class="form-label fw-semibold">Day </label>
                               <div class="input-group">
                                   <input type="number" name="day" class="form-control" required inputmode="numeric" autocomplete="off" min="1" max="31" data-suffix-target="modalDaySuffix">
                                   <span class="input-group-text" id="modalDaySuffix">th</span>
                               </div>
                            </div>

                            <div class="col-lg-4 col-12 mb-3">
                                <label class="form-label fw-semibold">Month</label>
                                <select name="month" class="form-select" required>
                                    <option value="" disabled>Select month</option>
                                    <?php
                                    $modalMonths = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                                    foreach ($modalMonths as $monthName) {
                                        echo "<option value=\"{$monthName}\">{$monthName}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-lg-4 col-12 mb-3">
                                <label class="form-label fw-semibold">Year </label>
                                <input type="text" name="year" class="form-control" required readonly>
                            </div>
                         </div>

                         <div class="row">
                             <div class="col-md-4 mb-3">
                               <label class="form-label fw-semibold">Book No</label>
                               <input type="number" name="book_no" class="form-control" min="0" step="1" required>
                           </div>
                           <div class="col-md-4 mb-3">
                               <label class="form-label fw-semibold">Page No</label>
                               <input type="number" name="page_no" class="form-control" min="0" step="1" required>
                           </div>
                           <div class="col-md-4 mb-3">
                               <label.class="form-label fw-semibold">Entry No</label>
                               <input type="number" name="entry_no" class="form-control" min="0" step="1" required>
                           </div>
                         </div>
                         <input type="hidden" name="release_claimant" id="update_release_claimant">
                         <input type="hidden" name="release_relationship" id="update_release_relationship">
                         <input type="hidden" name="release_time" id="update_release_time">
                     </div>
                 </form>
             </div>

             <div class="modal-footer">
                <button type="button" class="btn btn-success" id="openUpdateReleaseModalBtn">Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
             </div>
         </div>
     </div>
 </div>

 <div class="modal fade" id="updateReleaseInfoModal" tabindex="-1" aria-labelledby="updateReleaseInfoModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
     <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title" id="updateReleaseInfoModalLabel">Any Changes?</h5>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
             </div>
             <div class="modal-body">
                 <div class="mb-3">
                     <label class="form-label fw-semibold">Name of Claimant</label>
                     <input type="text" class="form-control" id="update_modal_release_claimant" placeholder="Enter claimant name">
                 </div>
                 <div class="mb-3">
                     <label class="form-label fw-semibold">Relationship to Owner (optional)</label>
                     <input type="text" class="form-control" id="update_modal_release_relationship" placeholder="e.g. Mother, Brother">
                 </div>
                 <div class="mb-3">
                     <label class="form-label fw-semibold">Date of Release</label>
                     <input type="text" class="form-control" id="update_modal_release_date" readonly>
                 </div>
                 <div class="mb-3">
                     <label class="form-label fw-semibold">Pickup Time (optional)</label>
                     <input type="time" class="form-control" id="update_modal_release_time">
                 </div>
             </div>
             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                 <button type="button" class="btn btn-primary" id="confirmUpdateReleaseBtn">Confirm</button>
             </div>
         </div>
     </div>
 </div>

 <script>
     document.getElementById("addSponsor").addEventListener("click", function() {
         const sponsorContainer = document.getElementById("sponsorContainer");
         const div = document.createElement("div");
         div.classList.add("d-flex", "gap-2", "mb-2");
         div.innerHTML = `
            <input type="text" class="form-control" name="sponsors[]">
            <button type="button" class="btn btn-danger btn-sm removeSponsor">REMOVE</button>
        `;
         sponsorContainer.appendChild(div);
     });

     document.addEventListener("click", function(e) {
         if (e.target.classList.contains("removeSponsor")) {
             e.target.closest(".d-flex").remove();
         }
     });
 </script>
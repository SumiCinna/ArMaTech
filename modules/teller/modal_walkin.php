<div class="modal fade" id="walkinModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-success text-white border-0 py-3">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i> New Walk-in Registration</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      
      <form action="../../core/process_walkin.php" method="POST">
          <div class="modal-body p-4 p-md-5">
            
            <div class="alert alert-success bg-success bg-opacity-10 border-0 text-success d-flex align-items-center mb-4 rounded-3">
                <i class="fa-solid fa-circle-info fa-lg me-3"></i> 
                <div>
                    <strong>System Notice</strong><br>
                    <small>The system will auto-generate the Username (ARM-YYYY-XXXX) and Password for this customer.</small>
                </div>
            </div>

            <h6 class="text-success fw-bold text-uppercase border-bottom pb-2 mb-3" style="font-size: 0.85rem;">
                <i class="fa-solid fa-id-card me-2"></i> Personal Information
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="fname" class="form-control bg-light border-0 shadow-none py-2" required placeholder="Juan">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Middle Name</label>
                    <input type="text" name="mname" class="form-control bg-light border-0 shadow-none py-2" placeholder="(Optional)">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="lname" class="form-control bg-light border-0 shadow-none py-2" required placeholder="Dela Cruz">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select bg-light border-0 shadow-none py-2" required>
                        <option value="" disabled selected>-- Select --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Civil Status</label>
                    <select name="civil_status" class="form-select bg-light border-0 shadow-none py-2">
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="dob" class="form-control bg-light border-0 shadow-none py-2" required>
                </div>
            </div>

            <h6 class="text-success fw-bold text-uppercase border-bottom pb-2 mb-3" style="font-size: 0.85rem;">
                <i class="fa-solid fa-address-book me-2"></i> Contact Details
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Mobile No. <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-mobile-screen"></i></span>
                        <input type="text" name="contact" class="form-control bg-light border-0 shadow-none py-2" placeholder="09xxxxxxxxx" maxlength="11" required>
                    </div>
                    <div class="form-text text-muted" style="font-size: 0.65rem;">Last 4 digits will be used as the temporary password.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control bg-light border-0 shadow-none py-2" placeholder="email@example.com">
                    </div>
                </div>
            </div>

            <h6 class="text-success fw-bold text-uppercase border-bottom pb-2 mb-3" style="font-size: 0.85rem;">
                <i class="fa-solid fa-map-location-dot me-2"></i> Address Details
            </h6>
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">House No. / Street <span class="text-danger">*</span></label>
                    <input type="text" name="street" class="form-control bg-light border-0 shadow-none py-2" required placeholder="#123 Street Name">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Barangay <span class="text-danger">*</span></label>
                    <input type="text" name="barangay" class="form-control bg-light border-0 shadow-none py-2" required placeholder="Brgy. Name">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">City <span class="text-danger">*</span></label>
                    <input type="text" name="city" class="form-control bg-light border-0 shadow-none py-2" value="Caloocan City" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Province <span class="text-danger">*</span></label>
                    <input type="text" name="province" class="form-control bg-light border-0 shadow-none py-2" value="Metro Manila" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Zip Code <span class="text-danger">*</span></label>
                    <input type="text" name="zip_code" class="form-control bg-light border-0 shadow-none py-2" required placeholder="0000">
                </div>
                <div class="col-md-12">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Address Type</label>
                    <select name="address_type" class="form-select bg-light border-0 shadow-none py-2">
                        <option value="Present" selected>Present Address</option>
                        <option value="Permanent">Permanent Address</option>
                    </select>
                </div>
            </div>

          </div>
          <div class="modal-footer border-0 bg-light p-4">
            <button type="button" class="btn btn-light border fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="btn_register_walkin" class="btn btn-success fw-bold px-4 shadow-sm">
                Create & Start <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>
          </div>
      </form>
    </div>
  </div>
</div>
<div class="modal fade" id="walkinModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">New Walk-in Registration</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      
      <form action="../../core/process_walkin.php" method="POST">
          <div class="modal-body">
            
            <div class="alert alert-info small">
                <i class="bi bi-info-circle-fill"></i> 
                System will auto-generate Username (ARM-YYYY-XXXX) and Password.
            </div>

            <h6 class="text-success">Personal Information</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label>First Name *</label>
                    <input type="text" name="fname" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Middle Name</label>
                    <input type="text" name="mname" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Last Name *</label>
                    <input type="text" name="lname" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Gender *</label>
                    <select name="gender" class="form-select" required>
                        <option value="" disabled selected>-- Select --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Civil Status</label>
                    <select name="civil_status" class="form-select">
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Date of Birth *</label>
                    <input type="date" name="dob" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="email@example.com">
                </div>
                <div class="col-md-6">
                    <label>Mobile No. *</label>
                    <input type="text" name="contact" class="form-control" placeholder="09xxxxxxxxx" maxlength="11" required>
                    <small class="text-muted">Last 4 digits will be the password.</small>
                </div>
            </div>

            <h6 class="text-success">Address Details</h6>
            <div class="row g-3">
                <div class="col-md-12">
                    <label>House No. / Street *</label>
                    <input type="text" name="street" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Barangay *</label>
                    <input type="text" name="barangay" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>City *</label>
                    <input type="text" name="city" class="form-control" value="Caloocan City" required>
                </div>
                <div class="col-md-6">
                    <label>Province *</label>
                    <input type="text" name="province" class="form-control" value="Metro Manila" required>
                </div>
                <div class="col-md-6">
                    <label>Zip Code *</label>
                    <input type="text" name="zip_code" class="form-control" required>
                </div>
                <div class="col-md-12">
                    <label>Address Type</label>
                    <select name="address_type" class="form-select">
                        <option value="Present" selected>Present</option>
                        <option value="Permanent">Permanent</option>
                    </select>
                </div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="btn_register_walkin" class="btn btn-success fw-bold">
                Create & Start <i class="bi bi-arrow-right"></i>
            </button>
          </div>
      </form>
    </div>
  </div>
</div>
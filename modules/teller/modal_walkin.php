<div class="modal fade" id="walkinModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <div class="modal-header bg-dark text-white border-0 p-4">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i class="fa-solid fa-user-plus me-2 text-success"></i> New Customer Registration</h5>
                    <small class="text-white-50">Enter the walk-in customer's details to create their profile.</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="../../core/process_walkin.php" method="POST">
                <div class="modal-body p-4 bg-light">
                    
                    <div class="alert alert-success bg-success bg-opacity-10 border-0 border-start border-4 border-success d-flex align-items-center mb-4 rounded-3 shadow-sm p-3">
                        <i class="fa-solid fa-circle-info fa-2x text-success me-3"></i> 
                        <div>
                            <strong class="d-block text-dark" style="font-size: 0.85rem;">Automatic Account Creation</strong>
                            <span class="small text-muted">The system will automatically generate an ID (ARM-YYYY-XXXX) and set their default password to the last 4 digits of their mobile number.</span>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                            <h6 class="fw-bold text-primary mb-0"><i class="fa-solid fa-address-card me-2"></i> Personal Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="fname" class="form-control bg-light border-0 shadow-none py-2" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Middle Name</label>
                                    <input type="text" name="mname" class="form-control bg-light border-0 shadow-none py-2">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="lname" class="form-control bg-light border-0 shadow-none py-2" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Gender <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-select bg-light border-0 shadow-none py-2" required>
                                        <option value="" disabled selected>-- Select --</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Civil Status</label>
                                    <select name="civil_status" class="form-select bg-light border-0 shadow-none py-2">
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Widowed">Widowed</option>
                                        <option value="Separated">Separated</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Date of Birth <span class="text-danger">*</span></label>
                                    <input type="date" name="dob" class="form-control bg-light border-0 shadow-none py-2" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                            <h6 class="fw-bold text-primary mb-0"><i class="fa-solid fa-address-book me-2"></i> Contact & Location</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Mobile Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0 text-muted">+63</span>
                                        <input type="text" name="contact" class="form-control bg-light border-0 shadow-none py-2" placeholder="9xxxxxxxxx" maxlength="10" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Email Address</label>
                                    <input type="email" name="email" class="form-control bg-light border-0 shadow-none py-2" placeholder="optional@email.com">
                                </div>

                                <div class="col-md-12 mt-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">House No. / Street <span class="text-danger">*</span></label>
                                    <input type="text" name="street" class="form-control bg-light border-0 shadow-none py-2" placeholder="e.g. 123 Rizal St." required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Barangay <span class="text-danger">*</span></label>
                                    <input type="text" name="barangay" class="form-control bg-light border-0 shadow-none py-2" placeholder="e.g. Brgy. 176" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">City <span class="text-danger">*</span></label>
                                    <input type="text" name="city" class="form-control bg-light border-0 shadow-none py-2" value="Caloocan City" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Zip Code <span class="text-danger">*</span></label>
                                    <input type="text" name="zip_code" class="form-control bg-light border-0 shadow-none py-2" placeholder="1400" required>
                                </div>
                                
                                <input type="hidden" name="province" value="Metro Manila">
                                <input type="hidden" name="address_type" value="Present">
                            </div>
                        </div>
                    </div>

                </div>
                
                <div class="modal-footer border-0 p-4 bg-white d-flex justify-content-between">
                    <button type="button" class="btn btn-light border fw-bold px-4 rounded-pill shadow-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="btn_register_walkin" class="btn btn-success fw-bold px-4 rounded-pill shadow-sm">
                        Create & Start Transaction <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25) !important; background-color: #fff !important; border: 1px solid #198754 !important; }
</style>
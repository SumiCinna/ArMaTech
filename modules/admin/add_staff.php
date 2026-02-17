<?php
// modules/admin/add_staff.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <h3 class="mt-4 fw-bold text-dark">Register New Employee</h3>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="manage_staff.php" class="text-decoration-none">Staff Management</a></li>
        <li class="breadcrumb-item active">Add New</li>
    </ol>

    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-header bg-dark text-white fw-bold py-3 rounded-top-4">
            <i class="fa-solid fa-user-shield me-2"></i> Employee Registration Form
        </div>
        <div class="card-body p-4">
            
            <form action="../../core/process_add_staff.php" method="POST">
                
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-primary fw-bold text-uppercase border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-id-card me-2"></i> Personal Information
                        </h6>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-muted">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" id="firstName" class="form-control" required placeholder="Juan">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-muted">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" placeholder="(Optional)">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-muted">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" id="lastName" class="form-control" required placeholder="Dela Cruz" oninput="generateUsername()"> 
                        </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-muted">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="dob" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-muted">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="" disabled selected>-- Select --</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-muted">Civil Status</label>
                        <select name="civil_status" class="form-select">
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-primary fw-bold text-uppercase border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-lock me-2"></i> Account Credentials
                        </h6>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-muted">Role <span class="text-danger">*</span></label>
                        <select name="role" id="userRole" class="form-select border-primary bg-primary bg-opacity-10 fw-bold" onchange="generateUsername()">
                            <option value="teller" selected>Teller</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-muted">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-user"></i></span>
                            <input type="text" name="username" id="username" class="form-control fw-bold text-primary" required placeholder="ARM-TELLER-..." readonly>
                            <button type="button" class="btn btn-outline-secondary" onclick="generateUsername()" title="Regenerate">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </div>
                        <div class="form-text small">Auto-generated based on Role + Last Name.</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold text-muted">Initial Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-key"></i></span>
                            <input type="text" name="password" id="password" class="form-control fw-bold" value="Armatech123" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()" title="Generate Random">
                                <i class="fa-solid fa-shuffle"></i>
                            </button>
                        </div>
                        <div class="form-text text-success small"><i class="fa-solid fa-circle-check"></i> Staff must change this on first login.</div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-primary fw-bold text-uppercase border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-address-book me-2"></i> Contact Details
                        </h6>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Mobile Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">+63</span>
                            <input type="text" name="phone" class="form-control" placeholder="9123456789" required maxlength="10">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="employee@armatech.com">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label small fw-bold text-muted">House No. / Street <span class="text-danger">*</span></label>
                        <input type="text" name="street" class="form-control" required placeholder="#123 Street Name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Barangay <span class="text-danger">*</span></label>
                        <input type="text" name="barangay" class="form-control" required placeholder="Brgy. Name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">City <span class="text-danger">*</span></label>
                        <input type="text" name="city" class="form-control" required placeholder="City">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Province <span class="text-danger">*</span></label>
                        <input type="text" name="province" class="form-control" required placeholder="Province">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Zip Code <span class="text-danger">*</span></label>
                        <input type="text" name="zip_code" class="form-control" required placeholder="0000">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-danger fw-bold text-uppercase border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-kit-medical me-2"></i> In Case of Emergency
                        </h6>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Contact Person Name</label>
                        <input type="text" name="emergency_contact_name" class="form-control" placeholder="e.g. Maria Dela Cruz (Spouse)">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Contact Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">+63</span>
                            <input type="text" name="emergency_contact_phone" class="form-control" placeholder="9123456789" maxlength="10">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="manage_staff.php" class="btn btn-light border fw-bold px-4">Cancel</a>
                    <button type="submit" name="btn_add_staff" class="btn btn-primary fw-bold px-5 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Save Record
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    // Generate Username: ARM-ROLE-LASTNAME + 4 Random Digits
    function generateUsername() {
        const lname = document.getElementById('lastName').value.trim();
        const roleSelect = document.getElementById('userRole');
        
        // Default role to TELLER if not found
        const role = roleSelect ? roleSelect.value.toUpperCase() : 'TELLER';

        if(!lname) {
            // If no last name, don't fill it yet or clear it
            document.getElementById('username').value = "";
            return;
        }

        // Clean: Remove spaces and symbols, make UPPERCASE
        const cleanLname = lname.replace(/[\s\W]+/g, '').toUpperCase();
        
        // Generate Random 4 Digits (1000 - 9999)
        const randomDigits = Math.floor(1000 + Math.random() * 9000); 

        // Format: ARM-TELLER-DELACRUZ8821
        const username = `ARM-${role}-${cleanLname}${randomDigits}`;
        
        document.getElementById('username').value = username;
    }

    // Generate Password: 12 chars (Letters + Numbers + Symbol)
    function generatePassword() {
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$";
        let password = "";
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('password').value = password;
    }
</script>

<?php include_once '../../includes/admin_footer.php'; ?>
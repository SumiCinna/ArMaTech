<?php 
session_start();

// Instantly route logged-in users to their respective dashboards
if(isset($_SESSION['role'])){
    if($_SESSION['role'] == 'admin') {
        header("Location: modules/admin/dashboard.php");
    } elseif($_SESSION['role'] == 'teller') {
        header("Location: modules/teller/dashboard.php");
    } elseif($_SESSION['role'] == 'customer') {
        header("Location: modules/customer/dashboard.php");
    }
    exit();
}

include_once 'includes/header.php'; 
?>

<style>
    /* Modern Industry Standard Font & Colors */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');
    
    body { 
        background-color: #f8fafc; 
        font-family: 'Inter', sans-serif; 
        color: #0f172a;
    }
    
    /* Sleek Tech Background */
    .hero-section {
        background: radial-gradient(circle at 80% 20%, #eff6ff 0%, #f8fafc 50%);
        padding: 140px 0 100px;
        position: relative;
        overflow: hidden;
    }
    
    /* Abstract grid pattern for tech feel */
    .bg-grid {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
        background-size: 30px 30px;
        opacity: 0.4;
        z-index: 0;
    }

    /* Glassmorphism Portal Cards */
    .portal-container {
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 24px;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.05);
    }

    .role-card { 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        border: 1px solid transparent; 
        background: #ffffff; 
        border-radius: 12px; 
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    
    .role-card:hover { 
        transform: translateY(-4px) scale(1.01); 
        border-color: #cbd5e1; 
        box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1); 
        cursor: pointer; 
    }

    .feature-icon { 
        width: 48px; height: 48px; 
        display: flex; align-items: center; justify-content: center; 
        border-radius: 10px; 
        font-size: 1.25rem; 
    }

    .text-gradient {
        background: linear-gradient(135deg, #0f172a 0%, #3b82f6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .ucc-accent { color: #f59e0b; }
    
    /* Fixed Dark Section Backgrounds */
    .bg-dark-slate { background-color: #0f172a !important; }
    .bg-dark-card { background-color: rgba(255,255,255,0.03) !important; border: 1px solid rgba(255,255,255,0.05) !important; }
</style>

<header class="hero-section">
    <div class="bg-grid"></div>
    <div class="container position-relative" style="z-index: 1;">
        <div class="row align-items-center g-5">
            <div class="col-lg-7 text-center text-lg-start pe-lg-5">
                <div class="mb-4">
                    <span class="badge bg-white text-dark border shadow-sm px-4 py-2 rounded-pill fw-semibold letter-spacing-1">
                        <i class="fa-solid fa-building-columns me-2 ucc-accent"></i> University of Caloocan City
                    </span>
                </div>
                
                <h1 class="display-3 fw-extrabold mb-4 tracking-tight text-dark" style="line-height: 1.1; letter-spacing: -1px;">
                    Next-Generation <br>
                    <span class="text-gradient">Pawnshop Operations.</span>
                </h1>
                
                <p class="lead text-secondary mb-5 max-w-600" style="font-size: 1.25rem; line-height: 1.6;">
                    An enterprise-grade platform built to automate transactions, eliminate manual inefficiencies, and drive customer retention through integrated CRM and loyalty features.
                </p>
                
                <div class="d-flex justify-content-center justify-content-lg-start gap-3">
                    <a href="customer_login.php" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-sm hover-lift">
                        Check Your Pawns
                    </a>
                    <a href="#features" class="btn btn-outline-dark btn-lg rounded-pill px-5 fw-semibold shadow-sm hover-lift">
                        How It Works
                    </a>
                </div>
                
                <div class="mt-5 pt-4 border-top d-flex align-items-center justify-content-center justify-content-lg-start gap-4 opacity-75 text-dark">
                    <div class="d-flex align-items-center"><i class="fa-solid fa-check-circle text-success me-2"></i> <small class="fw-bold">Instant Appraisal</small></div>
                    <div class="d-flex align-items-center"><i class="fa-solid fa-check-circle text-success me-2"></i> <small class="fw-bold">Secure Storage</small></div>
                    <div class="d-flex align-items-center"><i class="fa-solid fa-check-circle text-success me-2"></i> <small class="fw-bold">Online Renewal</small></div>
                </div>
            </div>
            
            <div class="col-lg-5">
                <div class="portal-container p-4 p-md-5">
                    <div class="text-center mb-4">
                        <span class="text-uppercase fw-bold text-primary small tracking-wide">Welcome Back</span>
                        <h4 class="fw-bold text-dark mt-1">Customer Access</h4>
                        <p class="text-secondary small mt-2">Manage your loans, view shop items, and pay interest online.</p>
                    </div>
                    
                    <a href="customer_login.php" class="text-decoration-none text-dark">
                        <div class="d-grid">
                            <button class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3">
                                <i class="fa-solid fa-lock me-2"></i> Login to My Account
                            </button>
                        </div>
                    </a>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">New customer? Visit our branch to evaluate your items.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<section id="features" class="py-6 bg-dark-slate" style="padding: 100px 0;">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <span class="text-info fw-bold text-uppercase tracking-wide small">Why Choose Us</span>
                <h2 class="display-6 fw-bold text-white mt-2 mb-3">Safe. Transparent. Reliable.</h2>
                <p class="text-white-50 lead">We provide the highest appraisal rates for your gadgets with a secure system you can trust.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 rounded-4 p-4 bg-dark-card">
                    <div class="feature-icon bg-primary text-info mb-4 bg-opacity-10"><i class="fa-solid fa-database"></i></div>
                    <h5 class="fw-bold text-white mb-3">Digital Tracking</h5>
                    <p class="text-white-50 small mb-0">Track all your pawned items, due dates, and payment history in real-time through your personal dashboard.</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 rounded-4 p-4 bg-dark-card">
                    <div class="feature-icon bg-success text-success mb-4 bg-opacity-10"><i class="fa-solid fa-chart-simple"></i></div>
                    <h5 class="fw-bold text-white mb-3">Online Shop</h5>
                    <p class="text-white-50 small mb-0">Browse and reserve unredeemed gadgets at a fraction of the market price directly from our website.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 rounded-4 p-4 bg-dark-card">
                    <div class="feature-icon bg-warning text-warning mb-4 bg-opacity-10"><i class="fa-solid fa-ranking-star"></i></div>
                    <h5 class="fw-bold text-white mb-3">Secure Transactions</h5>
                    <p class="text-white-50 small mb-0">Your data and items are safe with our enterprise-grade security protocols and vault management.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle">
                        <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">System Boundaries & Academic Constraints</h6>
                        <p class="text-secondary small mb-0">As defined in the project scope, this version relies on physical, in-person item inspection (no home delivery) and does not utilize AI-based forecasting logic.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="pt-5 pb-4" style="background-color: #f8fafc;">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="mb-3">
                    <i class="fa-solid fa-laptop-code fs-3 text-primary me-2"></i>
                    <span class="fs-4 fw-extrabold text-dark" style="letter-spacing: -1px;">ArMaTech.</span>
                </div>
                <p class="text-secondary small mb-4 pe-lg-5">This system integrates core retail functions into a single platform, aiming to minimize inefficiencies and enhance scalability through up to date software design.</p>
                
                <h6 class="fw-bold text-dark mb-2 small text-uppercase tracking-wide">Institution</h6>
                <p class="text-secondary small mb-0">Computer Studies Department</p>
                <p class="text-secondary small fw-bold">University of Caloocan City</p>
            </div>

            <div class="col-lg-7">
                <div class="d-flex justify-content-lg-end mb-4 align-items-center">
                    <small class="text-muted fw-bold text-uppercase me-2 small">Staff Access:</small>
                    <a href="teller_login.php" class="text-secondary text-decoration-none small me-2 hover-lift">Teller</a>
                    <span class="text-muted me-2">|</span>
                    <a href="admin_login.php" class="text-secondary text-decoration-none small hover-lift">Admin</a>
                </div>
                <h6 class="fw-bold text-dark mb-4 small text-uppercase tracking-wide">Project Proponents</h6>
                <div class="row text-dark small">
                    <div class="col-sm-6">
                        <ul class="list-unstyled">
                            <li class="mb-3 d-flex align-items-center"><div class="bg-primary bg-opacity-10 rounded p-1 me-2"><i class="fa-solid fa-code text-primary" style="font-size: 0.7rem;"></i></div> Armario, Ralp Anjelo M.</li>
                            <li class="mb-3 d-flex align-items-center"><div class="bg-primary bg-opacity-10 rounded p-1 me-2"><i class="fa-solid fa-code text-primary" style="font-size: 0.7rem;"></i></div> Asierto, Andrea Faith R.</li>
                            <li class="mb-3 d-flex align-items-center"><div class="bg-primary bg-opacity-10 rounded p-1 me-2"><i class="fa-solid fa-code text-primary" style="font-size: 0.7rem;"></i></div> Maco, Vaughn Angelo M.</li>
                            <li class="mb-3 d-flex align-items-center"><div class="bg-primary bg-opacity-10 rounded p-1 me-2"><i class="fa-solid fa-code text-primary" style="font-size: 0.7rem;"></i></div> Manzanero, Niña S.</li>
                        </ul>
                    </div>
                    <div class="col-sm-6">
                        <ul class="list-unstyled">
                            <li class="mb-3 d-flex align-items-center"><div class="bg-primary bg-opacity-10 rounded p-1 me-2"><i class="fa-solid fa-code text-primary" style="font-size: 0.7rem;"></i></div> Oraño, John Noel D.A.</li>
                            <li class="mb-3 d-flex align-items-center"><div class="bg-primary bg-opacity-10 rounded p-1 me-2"><i class="fa-solid fa-code text-primary" style="font-size: 0.7rem;"></i></div> Payawal, Ian Matthew S.</li>
                            <li class="mb-3 d-flex align-items-center"><div class="bg-primary bg-opacity-10 rounded p-1 me-2"><i class="fa-solid fa-code text-primary" style="font-size: 0.7rem;"></i></div> Sagun, Jerson U.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-5 pt-4 border-top d-flex flex-column flex-md-row justify-content-between align-items-center text-secondary small">
            <p class="mb-2 mb-md-0">© 2026 ArMaTech Management System.</p>
            <p class="mb-0 fw-semibold text-primary">Academic Defense Version 1.0</p>
        </div>
    </div>
</footer>

<?php include_once 'includes/footer.php'; ?>
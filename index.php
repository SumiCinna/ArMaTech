<?php 
// index.php
session_start();
// If user is already logged in, redirect them to the dashboard
if(isset($_SESSION['user_id'])){
    header("Location: modules/dashboard.php");
    exit();
}

include_once 'includes/header.php'; 
?>

<header class="hero-section">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="display-3 fw-bold mb-3">Your Gadgets, Your Cash.</h1>
                <p class="lead mb-4 text-light opacity-75">
                    The premier pawnshop for electronics. We offer top value for smartphones, laptops, tablets, and consoles.
                    Fast, secure, and tech-focused.
                </p>
                
                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                    <a href="login.php" class="btn btn-primary btn-lg px-5 gap-3 fw-bold rounded-pill">
                        Get an Estimate
                    </a>
                    <a href="register.php" class="btn btn-outline-light btn-lg px-5 rounded-pill">
                        Register Account
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<section id="features" class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="fw-bold">Why Pawn With Us?</h2>
                <p class="text-muted">Specialized care for your valuable electronics.</p>
            </div>
        </div>
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="card h-100 feature-card shadow-sm p-3">
                    <div class="card-body">
                        <div class="text-primary mb-3"><i class="bi bi-laptop display-4"></i></div>
                        <h4 class="card-title fw-bold">Tech Specialists</h4>
                        <p class="card-text text-muted">We understand the value of modern tech. Get the best appraisal rates for your devices.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 feature-card shadow-sm p-3">
                    <div class="card-body">
                        <div class="text-primary mb-3"><i class="bi bi-shield-lock display-4"></i></div>
                        <h4 class="card-title fw-bold">Data Privacy</h4>
                        <p class="card-text text-muted">Your data is safe. We guarantee privacy and secure storage for all electronic devices.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 feature-card shadow-sm p-3">
                    <div class="card-body">
                        <div class="text-primary mb-3"><i class="bi bi-lightning-charge display-4"></i></div>
                        <h4 class="card-title fw-bold">Instant Cash</h4>
                        <p class="card-text text-muted">Walk in with a gadget, walk out with cash. Simple process, no hidden fees.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include_once 'includes/footer.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArMaTech Pawnshop System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { 
            background-color: #f8fafc; 
            font-family: 'Inter', sans-serif; 
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
        }
        .hero-section {
            background: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.85)), url('https://source.unsplash.com/random/1920x1080/?technology,gadgets');
            background-size: cover;
            background-position: center;
        }
        
        /* Glass Navbar */
        .navbar-glass {
            background: rgba(15, 23, 42, 0.9) !important;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .nav-link {
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .nav-link:hover {
            color: #fff !important;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-glass py-3">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center tracking-tight" href="index.php">
        <div class="bg-primary text-white rounded-3 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
            <i class="bi bi-shield-lock-fill small"></i>
        </div>
        ArMaTech
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item px-2"><a class="nav-link text-white-50" href="#objectives">Core Objectives</a></li>
        <li class="nav-item px-2"><a class="nav-link text-white-50" href="#team">The Team</a></li>
        <li class="nav-item ms-lg-2"><a class="btn btn-primary btn-sm px-4 rounded-pill fw-bold shadow-sm" href="customer_login.php">Customer Login</a></li>
      </ul>
    </div>
  </div>
</nav>
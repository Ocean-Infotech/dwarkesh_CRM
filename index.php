<?php
include 'root/config.php';
if (isset($_SESSION['aid'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Dwarkesh Packaging</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-container">

    <div class="login-wrapper">
        <div class="login-side-info d-none d-lg-flex">
            <div class="info-content">
                <img src="assets/logo/logo.png" alt="Dwarkesh Packaging" class="img-fluid mb-4">

                <ul class=" feature-list list-unstyled mb-5">
                    <li><i class="bi bi-check-circle-fill me-2"></i> Premium Packaging Solutions</li>
                    <li><i class="bi bi-check-circle-fill me-2"></i> Real-time Order Tracking</li>
                    <li><i class="bi bi-check-circle-fill me-2"></i> Inventory Management</li>
                    <li><i class="bi bi-check-circle-fill me-2"></i> Customer Insight Analytics</li>
                    <li><i class="bi bi-check-circle-fill me-2"></i> 24/7 Priority Support</li>
                </ul>

                <!-- Showcase Image Area -->
                <div class="showcase-area mt-auto">
                    <img src="assets/images/showcase.png" alt="Showcase" class="img-fluid rounded-4 shadow-sm"
                        style="max-height: 200px; width: 100%; object-fit: cover; opacity: 0.9;">
                </div>

                <div class="mt-4">
                    <p class="small opacity-50 mb-0">© 2026 Dwarkesh Packaging. All Rights Reserved.</p>
                </div>
            </div>
        </div>

        <div class="login-side-form">
            <div class="form-content">
                <div class="mb-5 text-center text-lg-start">
                    <h2 class="fw-bold mb-1">Welcome <span class="text-gold">Back 👋</span></h2>
                    <p class="text-muted">Sign in to access your dashboard</p>
                </div>

                <form id="loginForm" action="dashboard.php" method="POST" novalidate>
                    <div class="mb-4">
                        <label for="email" class="form-label small fw-bold text-uppercase opacity-75">Email or Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="email" name="email" placeholder="Enter email or username"
                                required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password"
                            class="form-label small fw-bold text-uppercase opacity-75">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Enter password" required minlength="6">
                            <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-gold btn-lg py-3 fw-bold mt-2">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                        </button>
                    </div>

                    <div class="mt-4 text-center">
                        <div class="status-indicator">
                            <span class="dot"></span> SYSTEM ONLINE
                        </div>
                    </div>
                </form>

                <div class="text-center mt-5">
                    <button id="themeToggle" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-moon-fill me-1"></i> Switch Mode
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Background Elements -->
    <div class="bg-animated-mesh"></div>
    <div class="particles-container">
        <div class="particle" style="width: 20px; height: 20px; top: 20%; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 15px; height: 15px; top: 60%; left: 80%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 25px; height: 25px; top: 10%; left: 90%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 10px; height: 10px; top: 80%; left: 20%; animation-delay: 1s;"></div>
        <div class="particle" style="width: 30px; height: 30px; top: 40%; left: 50%; animation-delay: 3s;"></div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
</body>

</html>
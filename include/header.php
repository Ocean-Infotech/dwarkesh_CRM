<?php
include 'root/config.php';
if (!isset($_SESSION['aid'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Dwarkesh Packaging</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($extraHead))
        echo $extraHead; ?>
</head>

<body>

    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <img src="assets/logo/logo.png" alt="Logo">
            </div>

            <ul class="list-unstyled components">
                <li class="<?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
                    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="<?php echo ($currentPage == 'customer') ? 'active' : ''; ?>">
                    <a href="customer.php"><i class="bi bi-person-circle"></i> Customer</a>
                </li>
                <li class="<?php echo ($currentPage == 'product') ? 'active' : ''; ?>">
                    <a href="product.php"><i class="bi bi-box-seam"></i> Product</a>
                </li>
                <li class="<?php echo ($currentPage == 'material_type') ? 'active' : ''; ?>">
                    <a href="material_type.php"><i class="bi bi-tags"></i> Material Type</a>
                </li>
                <li class="<?php echo ($currentPage == 'materials') ? 'active' : ''; ?>">
                    <a href="materials.php"><i class="bi bi-bricks"></i> Materials</a>
                </li>
                <li class="<?php echo ($currentPage == 'offset') ? 'active' : ''; ?>">
                    <a href="offset.php"><i class="bi bi-gear"></i> Offset</a>
                </li>
                <li class="<?php echo ($currentPage == 'costings') ? 'active' : ''; ?>">
                    <a href="costings.php"><i class="bi bi-calculator"></i> Costings</a>
                </li>
                <li class="<?php echo ($currentPage == 'orders') ? 'active' : ''; ?>">
                    <a href="orders.php"><i class="bi bi-cart-check"></i> Orders</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-gold text-white me-3">
                        <i class="bi bi-list"></i>
                    </button>

                    <h5 class="m-0 fw-bold d-none d-md-block"><?php echo $headerTitle; ?></h5>

                    <div class="ms-auto d-flex align-items-center">
                        <button id="themeToggle" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                            <i class="bi bi-moon-fill"></i>
                        </button>

                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                                data-bs-toggle="dropdown">
                                <img src="https://ui-avatars.com/api/?name=Admin+User&background=c5a059&color=fff"
                                    class="rounded-circle me-2" width="35" height="35">
                                <span class="fw-semibold">Admin</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Settings</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i
                                            class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

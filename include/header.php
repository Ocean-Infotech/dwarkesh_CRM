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
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="shortcut icon" href="assets/logo/favicon.png" type="image/x-icon">
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
                <li class="<?php echo ($currentPage == 'lamination') ? 'active' : ''; ?>">
                    <a href="lamination.php"><i class="bi bi-layers-half"></i> Lamination</a>
                </li>
                <li class="<?php echo ($currentPage == 'costings') ? 'active' : ''; ?>">
                    <a href="costings.php"><i class="bi bi-calculator"></i> Costings</a>
                </li>
                <li class="<?php echo ($currentPage == 'orders') ? 'active' : ''; ?>">
                    <a href="orders.php"><i class="bi bi-cart-check"></i> Orders</a>
                </li>
                <li class="<?php echo ($currentPage == 'jobsheet') ? 'active' : ''; ?>">
                    <a href="jobsheet.php"><i class="bi bi-file-earmark-text"></i> Job Sheet</a>
                </li>
                <li class="nav-divider mt-3 mb-2 px-3 small text-uppercase text-muted fw-bold">Stock Management</li>
                <li class="<?php echo ($currentPage == 'product_stock') ? 'active' : ''; ?>">
                    <a href="product_stock.php"><i class="bi bi-box-seam"></i> Product Stock</a>
                </li>
                <li class="<?php echo ($currentPage == 'add_product_stock') ? 'active' : ''; ?>">
                    <a href="add_product_stock.php"><i class="bi bi-plus-square"></i> Add Stock</a>
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

                    <div class="ms-auto d-flex align-items-center gap-2">
                        <div class="theme-switch-wrapper me-2">
                            <button id="themeToggle" class="btn btn-icon-only rounded-circle" title="Toggle Theme">
                                <i class="bi bi-moon-stars-fill"></i>
                            </button>
                        </div>

                        <div class="header-divider me-2"></div>

                        <div class="dropdown">
                            <a class="profile-dropdown-toggle d-flex align-items-center" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="avatar-wrapper">
                                    <img src="https://ui-avatars.com/api/?name=Admin+User&background=c5a059&color=fff"
                                        class="rounded-circle shadow-sm" width="38" height="38">
                                    <span class="online-status"></span>
                                </div>
                                <div class="d-none d-lg-block ms-2 text-start">
                                    <div class="profile-name">Admin</div>
                                    <div class="profile-role">Super Admin</div>
                                </div>
                                <i class="bi bi-chevron-down ms-2 small opacity-50"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                                <li class="px-3 py-2 border-bottom mb-2 d-lg-none">
                                    <div class="fw-bold">Admin</div>
                                    <div class="small text-muted">Super Admin</div>
                                </li>
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>
                                        Profile</a></li>
                                <!-- <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Settings</a></li> -->
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
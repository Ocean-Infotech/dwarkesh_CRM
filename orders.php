<?php
    $pageTitle = "Orders";
    $currentPage = "orders";
    $headerTitle = "Manage Orders";

    $extraHead = '
    <style>
        .order-page-card {
            background: #fff;
            border-top: 4px solid var(--primary-gold);
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 15px;
        }

        .breadcrumb-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .breadcrumb-container h2 {
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }

        .breadcrumb-links {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .breadcrumb-links a {
            text-decoration: none;
            color: var(--primary-gold);
        }

        .breadcrumb-links span {
            color: #adb5bd;
            margin: 0 5px;
        }

        .form-label {
            font-weight: 700;
            font-size: 0.8rem;
            color: #444;
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #fff;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-gold);
            box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.15);
            outline: none;
        }

        .input-group-text {
            background-color: #f8fafc;
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 800;
            border: 1px solid #e2e8f0;
            padding: 0 20px;
        }

        .btn-theme {
            background-color: var(--primary-gold);
            color: white;
            border: none;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-theme:hover {
            background-color: var(--primary-gold-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(197, 160, 89, 0.25);
        }

        .sheet-size-container {
            border: 2px dashed var(--primary-gold-light);
            padding: 30px;
            margin-top: 35px;
            position: relative;
            border-radius: 15px;
            background: #fffcf8;
        }

        .sheet-size-row {
            display: flex;
            border: 2px solid #2d3748;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .sheet-label {
            position: absolute;
            top: -14px;
            left: 25px;
            font-weight: 900;
            font-size: 0.9rem;
            background: #fffcf8;
            padding: 0 15px;
            color: var(--primary-gold-dark);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .sheet-input-box {
            flex: 1;
            padding: 20px;
            text-align: center;
        }

        .sheet-input-box:first-child {
            border-right: 2px solid #2d3748;
        }

        .sheet-input-field {
            width: 100%;
            height: 100px;
            font-size: 3.5rem;
            border: none;
            text-align: center;
            color: #1a202c;
            font-weight: 300;
            background: transparent;
        }

        .sheet-input-field::placeholder {
            color: #cbd5e0;
        }

        .bg-gray-input {
            background-color: #f1f5f9 !important;
            font-weight: 700;
            color: var(--primary-gold-dark);
        }

        /* Purchase Detail Themed Styles */
        .purchase-detail-header {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-dark);
            margin: 40px 0 20px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .purchase-detail-header::after {
            content: "";
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, var(--primary-gold-light), transparent);
        }

        .purchase-card {
            border: 1px solid #edf2f7;
            border-radius: 12px;
            background: #fff;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            transition: all 0.3s;
        }

        .purchase-card:hover {
            box-shadow: 0 10px 15px rgba(0,0,0,0.05);
        }

        .purchase-card-header {
            padding: 15px 20px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #edf2f7;
            background: #fdfdfd;
            color: var(--primary-gold-dark);
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .purchase-card-liner, .purchase-card-duplex { 
            border-top: 4px solid var(--primary-gold); 
        }

        .purchase-table {
            width: 100%;
            margin: 15px 0;
            font-size: 0.9rem;
        }

        .purchase-table th {
            background: #f8fafc;
            padding: 12px 15px;
            text-align: left;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.75rem;
            border-bottom: 1px solid #edf2f7;
        }

        .purchase-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-weight: 500;
        }

        .table-custom thead th {
            font-size: 0.75rem;
            background-color: #fdfaf3;
            border-bottom: 2px solid var(--primary-gold-light);
            color: var(--primary-gold-dark);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 18px;
        }
        
        .badge-theme {
            background: rgba(197, 160, 89, 0.1);
            color: var(--primary-gold-dark);
            border: 1px solid rgba(197, 160, 89, 0.2);
            font-weight: 800;
            border-radius: 6px;
        }

        .btn-gold-pill {
            background-color: var(--primary-gold);
            color: white !important;
            border-radius: 50px;
            padding: 12px 40px;
            font-weight: 800;
            font-size: 1rem;
            border: none;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(197, 160, 89, 0.2);
        }

        .btn-gold-pill:hover {
            background-color: var(--primary-gold-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(197, 160, 89, 0.4);
        }
    </style>';

    include 'include/header.php';

    $table = "tbl_orders";
    $redirection_url = "orders.php";

    $mode = $_REQUEST['mode'] ?? '';
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $data = null;
    $error = '';

    // Fetch master data
    $customers = $ai_db->aiGetQuery("SELECT id, contact_name, brand_names FROM tbl_customer WHERE status='active' AND is_deleted=0 ORDER BY contact_name ASC");
    $products = $ai_db->aiGetQuery("SELECT id, name FROM tbl_product WHERE status='active' AND is_deleted=0 ORDER BY name ASC");
    $costings = $ai_db->aiGetQuery("SELECT id, estimate_no, customer_id FROM tbl_costings WHERE is_deleted=0 ORDER BY id DESC");
    
    // Fixed queries with JOINs
    $liners = $ai_db->aiGetQuery("SELECT m.id, m.name FROM tbl_materials m JOIN tbl_material_type mt ON m.material_type_id = mt.id WHERE mt.name='Liner' AND m.status='active' AND m.is_deleted=0");
    $duplexes = $ai_db->aiGetQuery("SELECT m.id, m.name FROM tbl_materials m JOIN tbl_material_type mt ON m.material_type_id = mt.id WHERE mt.name='Duplex' AND m.status='active' AND m.is_deleted=0");
    $offsets = $ai_db->aiGetQuery("SELECT id, contact_name FROM tbl_customer WHERE is_deleted=0 ORDER BY contact_name ASC");

    // Helper to clean brand names from JSON or array
    function clean_brand_names($value) {
        $brands = [];
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) $brands = $decoded;
        } elseif (is_array($value)) {
            $brands = $value;
        }
        $cleaned = [];
        foreach ($brands as $brand) {
            $brand = trim((string)$brand);
            if ($brand !== '') $cleaned[] = $brand;
        }
        return array_values(array_unique($cleaned));
    }

    $customerBrandsMap = [];
    foreach ($customers as $c) {
        $customerBrandsMap[$c['id']] = clean_brand_names($c['brand_names']);
    }

    if (isset($_POST['btn_submit'])) {
        $order_no = trim($_POST['order_no'] ?? '');
        $order_date = $_POST['order_date'] ?? date('Y-m-d');
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $brand_name = trim($_POST['brand_name'] ?? '');
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if ($order_no === '' || $customer_id === 0 || $product_id === 0) {
            $error = "Order No, Customer, and Product are required.";
        } else {
            $custRow = $ai_db->aiGetQuery("SELECT contact_name FROM tbl_customer WHERE id='$customer_id' LIMIT 1");
            $prodRow = $ai_db->aiGetQuery("SELECT name FROM tbl_product WHERE id='$product_id' LIMIT 1");
            $customer_name = $custRow[0]['contact_name'] ?? '';
            $product_name = $prodRow[0]['name'] ?? '';

            $sql_fields = "
                order_no='" . addslashes($order_no) . "',
                order_date='" . addslashes($order_date) . "',
                customer_id='$customer_id',
                customer_name='" . addslashes($customer_name) . "',
                brand_name='" . addslashes($brand_name) . "',
                product_id='$product_id',
                product_name='" . addslashes($product_name) . "',
                box_qty='" . floatval($_POST['box_qty'] ?? 0) . "',
                box_qty_unit='" . addslashes($_POST['box_qty_unit'] ?? 'PCS') . "',
                upps='" . floatval($_POST['upps'] ?? 1) . "',
                rate='" . floatval($_POST['rate'] ?? 0) . "',
                costing_id='" . intval($_POST['costing_id'] ?? 0) . "',
                sheet_length='" . floatval($_POST['sheet_length'] ?? 0) . "',
                sheet_width='" . floatval($_POST['sheet_width'] ?? 0) . "',
                md_code='" . addslashes($_POST['md_code'] ?? '') . "',
                plate_status='" . addslashes($_POST['plate_status'] ?? 'No') . "',
                print_status='" . addslashes($_POST['print_status'] ?? 'No') . "',
                die_status='" . addslashes($_POST['die_status'] ?? 'No') . "'
            ";

            if ($mode === 'add') {
                $ai_db->aiQuery("INSERT INTO $table SET $sql_fields, created_by='" . ($_SESSION['aid'] ?? 0) . "'");
                $ai_core->aiGoPage($redirection_url . "?msg=1");
                exit;
            } elseif ($mode === 'edit') {
                $ai_db->aiQuery("UPDATE $table SET $sql_fields, updated_by='" . ($_SESSION['aid'] ?? 0) . "' WHERE id='$id'");
                $ai_core->aiGoPage($redirection_url . "?msg=2");
                exit;
            }
        }
    }

    if ($mode === 'delete' && $id > 0) {
        $ai_db->aiQuery("UPDATE $table SET is_deleted=1 WHERE id='$id'");
        $ai_core->aiGoPage($redirection_url . "?msg=3");
        exit;
    }

    if ($mode === 'edit' && $id > 0) {
        $res = $ai_db->aiGetQuery("SELECT * FROM $table WHERE id='$id' LIMIT 1");
        $data = $res[0] ?? null;
    }

    if (!$mode) {
        $all_data = $ai_db->aiGetQuery("SELECT * FROM $table WHERE is_deleted=0 ORDER BY id DESC");
    }

    if ($mode === 'add' && !isset($_POST['btn_submit'])) {
        $lastNumQuery = $ai_db->aiGetQuery("SELECT id FROM $table ORDER BY id DESC LIMIT 1");
        $nextId = (isset($lastNumQuery[0]['id']) ? intval($lastNumQuery[0]['id']) : 0) + 1;
        $data['order_no'] = "#" . (3748 + $nextId);
        $data['order_date'] = date('Y-m-d');
    }
?>

<div class="container-fluid py-4">
    <div class="breadcrumb-container">
        <h2>Order</h2>
        <div class="breadcrumb-links">
            <a href="dashboard.php">Dashboard</a> <span>/</span> <a href="orders.php">Orders</a> <span>/</span> <span>Order Create</span>
        </div>
    </div>

    <?php if ($mode === 'add' || $mode === 'edit') { ?>
        <div class="order-page-card">
            <form method="POST" action="orders.php?mode=<?= $mode ?>&id=<?= $id ?>">
                <div class="row g-4">
                    <!-- Row 1 -->
                    <div class="col-md-4">
                        <label class="form-label">Customer</label>
                        <div class="input-group">
                            <select name="customer_id" id="customer_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $c) { ?>
                                    <option value="<?= $c['id'] ?>" <?= (isset($data['customer_id']) && $data['customer_id'] == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['contact_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <button type="button" class="btn btn-theme">Add New</button>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Box Quantity</label>
                        <div class="input-group">
                            <input type="number" name="box_qty" class="form-control" placeholder="PCS" value="<?= htmlspecialchars($data['box_qty'] ?? '') ?>">
                            <span class="input-group-text">PCS</span>
                            <select name="box_qty_unit" class="form-select">
                                <option value="PCS" <?= (isset($data['box_qty_unit']) && $data['box_qty_unit'] == 'PCS') ? 'selected' : '' ?>>XXXX</option>
                            </select>
                            <input type="number" name="upps" class="form-control" placeholder="Upps" value="<?= htmlspecialchars($data['upps'] ?? '1') ?>">
                            <span class="input-group-text">Upps</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Rate</label>
                        <input type="number" step="0.01" name="rate" class="form-control" value="<?= htmlspecialchars($data['rate'] ?? '') ?>" placeholder="Rate">
                    </div>

                    <!-- Row 2 -->
                    <div class="col-md-4">
                        <label class="form-label">Brand</label>
                        <select name="brand_name" id="brand_name" class="form-select">
                            <option value="">Select Brand</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Order No</label>
                        <input type="text" name="order_no" class="form-control bg-gray-input" value="<?= htmlspecialchars($data['order_no'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Order Date</label>
                        <input type="date" name="order_date" class="form-control" value="<?= htmlspecialchars($data['order_date'] ?? date('Y-m-d')) ?>">
                    </div>

                    <!-- Row 3 -->
                    <div class="col-md-4">
                        <label class="form-label">BOX Name</label>
                        <div class="input-group">
                            <select name="product_id" class="form-select" required>
                                <option value="">Select a Box</option>
                                <?php foreach ($products as $p) { ?>
                                    <option value="<?= $p['id'] ?>" <?= (isset($data['product_id']) && $data['product_id'] == $p['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <button type="button" class="btn btn-theme">Add New</button>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Costing Number</label>
                        <select name="costing_id" id="costing_id" class="form-select">
                            <option value="">Select Costing</option>
                            <?php foreach ($costings as $cost) { ?>
                                <option value="<?= $cost['id'] ?>" data-customer="<?= $cost['customer_id'] ?>" <?= (isset($data['costing_id']) && $data['costing_id'] == $cost['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cost['estimate_no']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Seet Size Section -->
                    <div class="col-12">
                        <div class="sheet-size-container">
                            <label class="sheet-label">Seet Size</label>
                            <div class="sheet-size-row">
                                <div class="sheet-input-box">
                                    <input type="number" step="0.01" name="sheet_length" class="sheet-input-field" placeholder="Length" value="<?= htmlspecialchars($data['sheet_length'] ?? '') ?>">
                                </div>
                                <div class="sheet-input-box">
                                    <input type="number" step="0.01" name="sheet_width" class="sheet-input-field" placeholder="Width" value="<?= htmlspecialchars($data['sheet_width'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Detail Section -->
                    <div class="col-12">
                        <h4 class="purchase-detail-header"><i class="bi bi-cart-check"></i> Purchase Detail</h4>
                        <div class="row">
                            <!-- Liner Card -->
                            <div class="col-md-6">
                                <div class="purchase-card purchase-card-liner">
                                    <div class="purchase-card-header">
                                        <i class="bi bi-layers"></i> Liner Configuration
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <div class="input-group input-group-sm">
                                                    <select name="liner_material_id" class="form-select">
                                                        <option value="">Select Liner</option>
                                                        <?php foreach ($liners as $l) { ?>
                                                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    <button type="button" class="btn btn-theme btn-sm">Add New</button>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="rate">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="qty">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-theme btn-sm w-100">Add</button>
                                            </div>
                                        </div>

                                        <table class="purchase-table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Rate</th>
                                                    <th>Pcs</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Dynamic items -->
                                            </tbody>
                                        </table>

                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Liner Delivery To</label>
                                                <select name="liner_delivery_id" class="form-select form-select-sm">
                                                    <option value="">----- Select Delivery -----</option>
                                                    <?php foreach ($customers as $c) { ?>
                                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['contact_name']) ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Liner Delivery Phone</label>
                                                <input type="text" class="form-control form-control-sm" placeholder="Liner Delivery Phone">
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label small mb-1">Top Count</label>
                                                <input type="text" class="form-control form-control-sm" placeholder="top count">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Duplex Card -->
                            <div class="col-md-6">
                                <div class="purchase-card purchase-card-duplex">
                                    <div class="purchase-card-header">
                                        <i class="bi bi-box"></i> Duplex Configuration
                                    </div>
                                    <div class="card-body p-3">
                                        <label class="form-label small mb-1">Duplex</label>
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-8">
                                                <div class="input-group input-group-sm">
                                                    <select name="duplex_material_id" class="form-select">
                                                        <option value="">Select Duplex</option>
                                                        <?php foreach ($duplexes as $d) { ?>
                                                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    <button type="button" class="btn btn-theme btn-sm">Add New</button>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="rate">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="qty">
                                            </div>
                                        </div>

                                        <div class="row g-3 mt-4">
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Duplex Delivery To</label>
                                                <select name="duplex_delivery_id" class="form-select form-select-sm">
                                                    <option value="">----- Select Offset -----</option>
                                                    <?php foreach ($offsets as $o) { ?>
                                                        <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['contact_name']) ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Duplex Delivery Phone</label>
                                                <input type="text" class="form-control form-control-sm" placeholder="Duplex Delivery Phone">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <button type="submit" name="btn_submit" class="btn btn-gold-pill">
                        <i class="bi bi-check2-circle me-2"></i> SAVE ORDER
                    </button>
                </div>
            </form>
        </div>
    <?php } else { ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>OrderNo</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Brand</th>
                                <th>BOX Name</th>
                                <th>Rate</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_data)) {
                                foreach ($all_data as $row) { ?>
                                    <tr>
                                        <td><span class="badge badge-theme px-3 py-1"><?= htmlspecialchars($row['order_no']) ?></span></td>
                                        <td class="small fw-medium"><?= date('d-m-Y', strtotime($row['order_date'])) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['customer_name']) ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($row['brand_name'] ?: '-') ?></td>
                                        <td class="fw-medium"><?= htmlspecialchars($row['product_name']) ?></td>
                                        <td class="fw-bold text-success">₹<?= number_format($row['rate'], 2) ?></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="orders.php?mode=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-0" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="orders.php?mode=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger border-0" title="Delete" onclick="return confirm('Delete?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php }
                            } else { ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">No orders found.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const customerBrandsMap = <?= json_encode($customerBrandsMap) ?>;
        const currentBrand = "<?= htmlspecialchars($data['brand_name'] ?? '') ?>";

        const custSelect = document.getElementById('customer_id');
        const brandSelect = document.getElementById('brand_name');

        function updateBrands(custId, selectedBrand = '') {
            if (!brandSelect) return;
            brandSelect.innerHTML = '<option value="">Select Brand</option>';
            if (custId && customerBrandsMap[custId]) {
                customerBrandsMap[custId].forEach(brand => {
                    const opt = document.createElement('option');
                    opt.value = brand;
                    opt.textContent = brand;
                    if (brand === selectedBrand) opt.selected = true;
                    brandSelect.appendChild(opt);
                });
            }
        }

        if (custSelect) {
            if (custSelect.value) {
                updateBrands(custSelect.value, currentBrand);
            }

            custSelect.addEventListener('change', function() {
                const custId = this.value;
                updateBrands(custId);
                
                const costingSelect = document.getElementById('costing_id');
                Array.from(costingSelect.options).forEach(opt => {
                    if (opt.value === "") return;
                    if (opt.dataset.customer == custId || custId === "") opt.style.display = "";
                    else opt.style.display = "none";
                });
            });
        }
    });
</script>

<?php include 'include/footer.php'; ?>

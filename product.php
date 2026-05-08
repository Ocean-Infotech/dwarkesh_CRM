<?php
$pageTitle = "Product";
$currentPage = "product";
$headerTitle = "Manage Products";

include 'include/header.php';

$table = "tbl_product";
$bom_table = "tbl_product_bom";
$redirection_url = "product.php";

$mode = $_REQUEST['mode'] ?? '';
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$bom_id = isset($_REQUEST['bom_id']) ? intval($_REQUEST['bom_id']) : 0;
$bom_action = $_REQUEST['bom_action'] ?? '';
$data = null;
$error = '';
$bom_error = '';
$bom_data = null;
$bom_items = [];
$material_options = [];
$selected_product = null;
$all_materials = $ai_db->aiGetQuery("SELECT id, name FROM tbl_materials WHERE status='active' AND is_deleted=0 ORDER BY name ASC");

// Ensure BOM table exists so the product-wise BOM feature works immediately.
$ai_db->aiQuery("CREATE TABLE IF NOT EXISTS `$bom_table` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_id` int(11) NOT NULL,
        `material_name` varchar(255) NOT NULL,
        `rate` decimal(10,2) DEFAULT NULL,
        `qty` decimal(10,2) DEFAULT NULL,
        `unit` varchar(50) DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `deleted_by` int(11) DEFAULT NULL,
        `deleted_at` timestamp NULL DEFAULT NULL,
        `is_deleted` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`),
        KEY `created_by` (`created_by`),
        KEY `updated_by` (`updated_by`),
        KEY `deleted_by` (`deleted_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($mode === "add" && isset($_POST['btn_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $name_escaped = addslashes($name);
    $rate = $_POST['rate'] ?? 0;
    $hsn_code = addslashes($_POST['hsn_code'] ?? '');
    $default_length = $_POST['default_length'] ?? 0;
    $default_width = $_POST['default_width'] ?? 0;
    $default_height = $_POST['default_height'] ?? 0;
    $description = addslashes($_POST['description'] ?? '');
    $mapped_material_id = intval($_POST['mapped_material_id'] ?? 0);
    $usage_qty = floatval($_POST['usage_qty'] ?? 0);
    $status = $_POST['status'] ?? 'deactive';

    if ($name === '') {
        $error = 'Product Name is required.';
    } else {
        $duplicate = $ai_db->aiGetQuery("SELECT id FROM $table WHERE name='" . $name_escaped . "' AND is_deleted=0 LIMIT 1");
        if (!empty($duplicate)) {
            $error = 'Product Name already exists.';
        }
    }

    if (empty($error)) {
        $add_qry = "INSERT INTO $table SET
                name='" . $name_escaped . "',
                rate='" . $rate . "',
                hsn_code='" . $hsn_code . "',
                default_length='" . $default_length . "',
                default_width='" . $default_width . "',
                default_height='" . $default_height . "',
                description='" . $description . "',
                mapped_material_id='" . $mapped_material_id . "',
                usage_qty='" . $usage_qty . "',
                status='" . $status . "',
                created_by='" . $_SESSION['aid'] . "'";
        $ai_db->aiQuery($add_qry);
        $ai_core->aiGoPage($redirection_url . "?msg=1");
        exit;
    }

    $data = [
        'name' => htmlspecialchars($name),
        'rate' => htmlspecialchars($_POST['rate'] ?? ''),
        'hsn_code' => htmlspecialchars($_POST['hsn_code'] ?? ''),
        'default_length' => htmlspecialchars($_POST['default_length'] ?? ''),
        'default_width' => htmlspecialchars($_POST['default_width'] ?? ''),
        'default_height' => htmlspecialchars($_POST['default_height'] ?? ''),
        'description' => htmlspecialchars($_POST['description'] ?? ''),
        'status' => $status
    ];
}

if ($mode === "edit" && isset($_POST['btn_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $name_escaped = addslashes($name);
    $rate = $_POST['rate'] ?? 0;
    $hsn_code = addslashes($_POST['hsn_code'] ?? '');
    $default_length = $_POST['default_length'] ?? 0;
    $default_width = $_POST['default_width'] ?? 0;
    $default_height = $_POST['default_height'] ?? 0;
    $description = addslashes($_POST['description'] ?? '');
    $mapped_material_id = intval($_POST['mapped_material_id'] ?? 0);
    $usage_qty = floatval($_POST['usage_qty'] ?? 0);
    $status = $_POST['status'] ?? 'deactive';

    if ($name === '') {
        $error = 'Product Name is required.';
    } else {
        $duplicate = $ai_db->aiGetQuery("SELECT id FROM $table WHERE name='" . $name_escaped . "' AND id != '" . intval($id) . "' AND is_deleted=0 LIMIT 1");
        if (!empty($duplicate)) {
            $error = 'Product Name already exists.';
        }
    }

    if (empty($error)) {
        $edit_qry = "UPDATE $table SET
                name='" . $name_escaped . "',
                rate='" . $rate . "',
                hsn_code='" . $hsn_code . "',
                default_length='" . $default_length . "',
                default_width='" . $default_width . "',
                default_height='" . $default_height . "',
                description='" . $description . "',
                mapped_material_id='" . $mapped_material_id . "',
                usage_qty='" . $usage_qty . "',
                status='" . $status . "',
                updated_by='" . $_SESSION['aid'] . "'
                WHERE id='" . intval($id) . "'";
        $ai_db->aiQuery($edit_qry);
        $ai_core->aiGoPage($redirection_url . "?msg=2");
        exit;
    }

    $data = [
        'name' => htmlspecialchars($name),
        'rate' => htmlspecialchars($_POST['rate'] ?? ''),
        'hsn_code' => htmlspecialchars($_POST['hsn_code'] ?? ''),
        'default_length' => htmlspecialchars($_POST['default_length'] ?? ''),
        'default_width' => htmlspecialchars($_POST['default_width'] ?? ''),
        'default_height' => htmlspecialchars($_POST['default_height'] ?? ''),
        'description' => htmlspecialchars($_POST['description'] ?? ''),
        'status' => $status
    ];
}

if ($mode === "delete" && $id) {
    $ai_db->aiQuery("UPDATE $table SET
            is_deleted=1,
            deleted_by='" . $_SESSION['aid'] . "',
            deleted_at=NOW()
            WHERE id='" . intval($id) . "'");
    $ai_core->aiGoPage($redirection_url . "?msg=3");
    exit;
}

if ($mode === "edit" && $id && !$error && !isset($_POST['btn_submit'])) {
    $query = "SELECT * FROM $table WHERE id='" . intval($id) . "' AND is_deleted=0 LIMIT 1";
    $result = $ai_db->aiGetQuery($query);
    $data = isset($result[0]) ? $result[0] : null;
}

if ($mode === "bom") {
    if ($id <= 0) {
        $ai_core->aiGoPage($redirection_url);
        exit;
    }

    $product_result = $ai_db->aiGetQuery("SELECT * FROM $table WHERE id='" . intval($id) . "' AND is_deleted=0 LIMIT 1");
    $selected_product = $product_result[0] ?? null;

    if (!$selected_product) {
        $ai_core->aiGoPage($redirection_url);
        exit;
    }

    $material_options = $ai_db->aiGetQuery("SELECT id, name, rate FROM tbl_materials WHERE status='active' AND is_deleted=0 ORDER BY name ASC");

    if ($bom_action === "delete" && $bom_id > 0) {
        $ai_db->aiQuery("UPDATE $bom_table SET
                is_deleted=1,
                deleted_by='" . $_SESSION['aid'] . "',
                deleted_at=NOW()
                WHERE id='" . intval($bom_id) . "'
                AND product_id='" . intval($id) . "'
                AND is_deleted=0");
        $ai_core->aiGoPage($redirection_url . "?mode=bom&id=" . intval($id) . "&msg=6");
        exit;
    }

    if (isset($_POST['btn_bom_submit'])) {
        $posted_bom_id = intval($_POST['bom_id'] ?? 0);
        $material_name = trim($_POST['material_name'] ?? '');
        $material_name_escaped = addslashes($material_name);
        $rate = $_POST['rate'] ?? '';
        $qty = $_POST['qty'] ?? '';
        $unit = trim($_POST['unit'] ?? '');
        $unit_escaped = addslashes($unit);

        if ($material_name === '' || $rate === '' || $qty === '' || $unit === '') {
            $bom_error = 'Material Name, Rate, Qty and Unit are required.';
        }

        if (empty($bom_error)) {
            if ($posted_bom_id > 0) {
                $ai_db->aiQuery("UPDATE $bom_table SET
                        material_name='" . $material_name_escaped . "',
                        rate='" . $rate . "',
                        qty='" . $qty . "',
                        unit='" . $unit_escaped . "',
                        updated_by='" . $_SESSION['aid'] . "'
                        WHERE id='" . $posted_bom_id . "'
                        AND product_id='" . intval($id) . "'
                        AND is_deleted=0");
                $ai_core->aiGoPage($redirection_url . "?mode=bom&id=" . intval($id) . "&msg=5");
                exit;
            } else {
                $ai_db->aiQuery("INSERT INTO $bom_table SET
                        product_id='" . intval($id) . "',
                        material_name='" . $material_name_escaped . "',
                        rate='" . $rate . "',
                        qty='" . $qty . "',
                        unit='" . $unit_escaped . "',
                        created_by='" . $_SESSION['aid'] . "'");
                $ai_core->aiGoPage($redirection_url . "?mode=bom&id=" . intval($id) . "&msg=4");
                exit;
            }
        }

        $bom_data = [
            'id' => $posted_bom_id,
            'material_name' => htmlspecialchars($material_name),
            'rate' => htmlspecialchars($rate),
            'qty' => htmlspecialchars($qty),
            'unit' => htmlspecialchars($unit)
        ];
    }

    if ($bom_id > 0 && !isset($_POST['btn_bom_submit'])) {
        $bom_result = $ai_db->aiGetQuery("SELECT * FROM $bom_table WHERE id='" . intval($bom_id) . "' AND product_id='" . intval($id) . "' AND is_deleted=0 LIMIT 1");
        $bom_data = $bom_result[0] ?? null;
    }

    $bom_items = $ai_db->aiGetQuery("SELECT * FROM $bom_table WHERE product_id='" . intval($id) . "' AND is_deleted=0 ORDER BY id DESC");
}

$all_data = [];
$totalRecords = 0;
$totalPages = 1;
$limit = 20;
if (!$mode) {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $limit;

    $filterSessionKey = 'product_filters';
    if (isset($_GET['action']) && $_GET['action'] === 'clear_filters') {
        unset($_SESSION[$filterSessionKey]);
        header('Location: product.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION[$filterSessionKey] = [
            'filter_name' => trim($_POST['filter_name'] ?? ''),
            'filter_status' => $_POST['filter_status'] ?? ''
        ];
        $page = 1;
        $offset = 0;
    }

    $filters = $_SESSION[$filterSessionKey] ?? [];
    $hasActiveFilters = !empty(array_filter($filters, function ($value) {
        return $value !== '' && $value !== null;
    }));

    $where_conditions = ["p.is_deleted=0"];
    if (!empty($filters['filter_name'])) {
        $where_conditions[] = "p.name LIKE '%" . addslashes($filters['filter_name']) . "%'";
    }
    if (!empty($filters['filter_status']) && $filters['filter_status'] !== '') {
        $where_conditions[] = "p.status = '" . addslashes($filters['filter_status']) . "'";
    }

    $where_clause = implode(" AND ", $where_conditions);
    $countResult = $ai_db->aiGetQuery("SELECT COUNT(*) as total FROM $table p WHERE $where_clause");
    $totalRecords = isset($countResult[0]['total']) ? intval($countResult[0]['total']) : 0;
    $totalPages = $totalRecords > 0 ? max(1, ceil($totalRecords / $limit)) : 1;
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $limit;
    }

    $all_data = $ai_db->aiGetQuery("SELECT p.*,
            (SELECT COUNT(*) FROM $bom_table pb WHERE pb.product_id = p.id AND pb.is_deleted=0) as bom_count
            FROM $table p
            WHERE $where_clause
            ORDER BY p.id DESC
            LIMIT $limit OFFSET $offset");
}

$page_heading = 'All Records';
if ($mode === 'add') {
    $page_heading = 'Add';
} elseif ($mode === 'edit') {
    $page_heading = 'Edit';
} elseif ($mode === 'bom') {
    $page_heading = 'Product BOM';
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">
            <span class="text-muted fw-light">Product /</span> <?= $page_heading ?>
        </h4>
        <?php if (!$mode) { ?>
            <div class="d-flex align-items-center gap-2">
                <button type="button"
                    class="btn btn-outline-secondary btn-sm rounded-circle btn-filter-toggle <?= !empty($hasActiveFilters) ? 'active' : '' ?>"
                    data-bs-toggle="collapse" data-bs-target="#productFilterCollapse" aria-expanded="false"
                    aria-controls="productFilterCollapse" aria-label="Toggle Filters" title="Toggle Filters">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="product.php?mode=add" class="btn btn-gold btn-sm rounded-pill px-3">
                    <i class="bi bi-plus-lg me-1"></i> Add New
                </a>
            </div>
        <?php } else { ?>
            <a href="product.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        <?php } ?>
    </div>

    <?php if (!$mode) { ?>
        <div class="collapse mb-3" id="productFilterCollapse">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="product.php" class="row g-3">
                        <div class="col-md-4">
                            <label for="filter_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="filter_name" name="filter_name"
                                value="<?= htmlspecialchars($filters['filter_name'] ?? '') ?>" placeholder="Search by Name">
                        </div>
                        <div class="col-md-4">
                            <label for="filter_status" class="form-label">Status</label>
                            <select class="form-select" id="filter_status" name="filter_status">
                                <option value="">All Status</option>
                                <option value="active" <?= ($filters['filter_status'] ?? '') === 'active' ? 'selected' : '' ?>>
                                    Active</option>
                                <option value="deactive" <?= ($filters['filter_status'] ?? '') === 'deactive' ? 'selected' : '' ?>>Deactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm me-2">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                            <a href="product.php?action=clear_filters" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-circle me-1"></i> Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="80">Sr No.</th>
                                <th>Name</th>
                                <th>Rate</th>
                                <th>HSN Code</th>
                                <th>Default Size (LxWxH)</th>
                                <th>Status</th>
                                <th width="320" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_data)) {
                                foreach ($all_data as $index => $row) { ?>
                                    <tr>
                                        <td>#<?= $offset + $index + 1 ?></td>
                                        <td><span class="fw-semibold"><?= htmlspecialchars($row['name']) ?></span></td>
                                        <td>Rs. <?= number_format($row['rate'], 2) ?></td>
                                        <td><?= htmlspecialchars($row['hsn_code']) ?></td>
                                        <td><?= $row['default_length'] ?> x <?= $row['default_width'] ?> x
                                            <?= $row['default_height'] ?> cm
                                        </td>
                                        <td>
                                            <?php if ($row['status'] == 'active') { ?>
                                                <span class="badge bg-success-subtle text-success px-3 rounded-pill">Active</span>
                                            <?php } else { ?>
                                                <span class="badge bg-danger-subtle text-danger px-3 rounded-pill">Deactive</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="table-action-group">
                                                <a href="product.php?mode=edit&id=<?= $row['id'] ?>" class="table-action-btn edit"
                                                    title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="product.php?mode=delete&id=<?= $row['id'] ?>"
                                                    class="table-action-btn delete" title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this record?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <a href="product.php?mode=bom&id=<?= $row['id'] ?>" class="table-action-btn bom"
                                                    title="BOM">
                                                    <i class="bi bi-list-check"></i>
                                                    <?php if (intval($row['bom_count']) > 0) { ?>
                                                        <span class="action-count"><?= intval($row['bom_count']) ?></span>
                                                    <?php } ?>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        No data available. Click "Add New" to get started.
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalRecords > 0) { ?>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-3">
                        <div class="text-muted small">
                            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?>
                            entries
                        </div>
                        <?php if ($totalPages > 1) { ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination mb-0">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="product.php?page=<?= $page - 1 ?>" tabindex="-1">Previous</a>
                                    </li>
                                    <?php for ($p = 1; $p <= $totalPages; $p++) {
                                        if ($p == 1 || $p == $totalPages || ($p >= $page - 2 && $p <= $page + 2)) { ?>
                                            <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                                <a class="page-link" href="product.php?page=<?= $p ?>"><?= $p ?></a>
                                            </li>
                                        <?php } elseif ($p == $page - 3 || $p == $page + 3) { ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php }
                                    } ?>
                                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="product.php?page=<?= $page + 1 ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } elseif ($mode === 'bom') { ?>
        <div class="row g-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div
                        class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <div class="text-muted small mb-1">Selected Product</div>
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($selected_product['name']) ?></h5>
                            <div class="text-muted small">
                                HSN: <?= htmlspecialchars($selected_product['hsn_code'] ?: '-') ?> |
                                Rate: Rs. <?= number_format($selected_product['rate'], 2) ?>
                            </div>
                        </div>
                        <div class="text-md-end">
                            <div class="text-muted small mb-1">Default Size</div>
                            <div class="fw-semibold"><?= $selected_product['default_length'] ?> x
                                <?= $selected_product['default_width'] ?> x <?= $selected_product['default_height'] ?> cm
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h5 class="fw-bold mb-0"><?= !empty($bom_data['id']) ? 'Edit BOM Item' : 'Add BOM Item' ?></h5>
                    </div>
                    <form class="card-body" method="post" action="product.php?mode=bom&id=<?= $id ?>">
                        <input type="hidden" name="bom_id"
                            value="<?= isset($bom_data['id']) ? intval($bom_data['id']) : 0 ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Material Name <span class="text-danger">*</span></label>
                            <select name="material_name" id="material_name" class="form-select" required>
                                <option value="">Select Material</option>
                                <?php
                                $selected_material_name = isset($bom_data['material_name']) ? trim((string) $bom_data['material_name']) : '';
                                $selected_material_found = false;
                                foreach ($material_options as $material_option) {
                                    $option_name = trim((string) ($material_option['name'] ?? ''));
                                    $is_selected = $selected_material_name !== '' && strcasecmp($selected_material_name, $option_name) === 0;
                                    if ($is_selected) {
                                        $selected_material_found = true;
                                    }
                                    ?>
                                    <option value="<?= htmlspecialchars($option_name) ?>"
                                        data-rate="<?= htmlspecialchars($material_option['rate'] ?? '') ?>" <?= $is_selected ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option_name) ?>
                                    </option>
                                <?php } ?>
                                <?php if ($selected_material_name !== '' && !$selected_material_found) { ?>
                                    <option value="<?= htmlspecialchars($selected_material_name) ?>" selected>
                                        <?= htmlspecialchars($selected_material_name) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Rate <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="rate" class="form-control"
                                placeholder="Enter Rate"
                                value="<?= isset($bom_data['rate']) ? htmlspecialchars($bom_data['rate']) : '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Qty <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="qty" class="form-control" placeholder="Enter Qty"
                                value="<?= isset($bom_data['qty']) ? htmlspecialchars($bom_data['qty']) : '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Unit <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control" placeholder="Enter Unit"
                                value="<?= isset($bom_data['unit']) ? htmlspecialchars($bom_data['unit']) : '' ?>" required>
                        </div>

                        <div class="pt-3 border-top d-flex gap-2">
                            <button type="submit" name="btn_bom_submit" class="btn btn-gold btn-sm rounded-pill px-4">
                                <i class="bi bi-check-circle me-1"></i>
                                <?= !empty($bom_data['id']) ? 'Update BOM' : 'Save BOM' ?>
                            </button>
                            <?php if (!empty($bom_data['id'])) { ?>
                                <a href="product.php?mode=bom&id=<?= $id ?>"
                                    class="btn btn-outline-secondary btn-sm rounded-pill px-4">Cancel</a>
                            <?php } ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div
                        class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">BOM Items</h5>
                        <span class="badge bg-dark-subtle text-dark rounded-pill px-3"><?= count($bom_items) ?> Items</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="80">Sr No.</th>
                                        <th>Material Name</th>
                                        <th>Rate</th>
                                        <th>Qty</th>
                                        <th>Unit</th>
                                        <th width="180" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($bom_items)) {
                                        foreach ($bom_items as $index => $item) { ?>
                                            <tr>
                                                <td>#<?= $index + 1 ?></td>
                                                <td class="fw-semibold"><?= htmlspecialchars($item['material_name']) ?></td>
                                                <td>Rs. <?= number_format($item['rate'], 2) ?></td>
                                                <td><?= htmlspecialchars($item['qty']) ?></td>
                                                <td><?= htmlspecialchars($item['unit']) ?></td>
                                                <td class="text-center">
                                                    <div class="table-action-group">
                                                        <a href="product.php?mode=bom&id=<?= $id ?>&bom_id=<?= $item['id'] ?>"
                                                            class="table-action-btn edit">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <a href="product.php?mode=bom&id=<?= $id ?>&bom_action=delete&bom_id=<?= $item['id'] ?>"
                                                            class="table-action-btn delete"
                                                            onclick="return confirm('Are you sure you want to delete this BOM item?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php }
                                    } else { ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-list-ul display-6 d-block mb-3"></i>
                                                No BOM items added for this product yet.
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h5 class="fw-bold mb-0"><?= ($mode == 'edit') ? 'Update' : 'Create' ?> Product</h5>
                    </div>
                    <form class="card-body" method="post" action="product.php?mode=<?= $mode ?>&id=<?= $id ?>"
                        enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= isset($data['id']) ? $data['id'] : '' ?>">

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter Product Name"
                                    value="<?= isset($data['name']) ? htmlspecialchars($data['name']) : '' ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Rate</label>
                                <input type="number" step="0.01" name="rate" class="form-control" placeholder="Enter Rate"
                                    value="<?= isset($data['rate']) ? $data['rate'] : '' ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">HSN Code</label>
                                <input type="text" name="hsn_code" class="form-control" placeholder="Enter HSN Code"
                                    value="<?= isset($data['hsn_code']) ? htmlspecialchars($data['hsn_code']) : '' ?>">
                            </div>

                            <div class="col-6">
                                <label class="form-label fw-bold">Default Size</label>
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <input type="number" step="0.01" name="default_length" class="form-control"
                                            placeholder="Length (cm)"
                                            value="<?= isset($data['default_length']) ? $data['default_length'] : '' ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" step="0.01" name="default_width" class="form-control"
                                            placeholder="Width (cm)"
                                            value="<?= isset($data['default_width']) ? $data['default_width'] : '' ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" step="0.01" name="default_height" class="form-control"
                                            placeholder="Height (cm)"
                                            value="<?= isset($data['default_height']) ? $data['default_height'] : '' ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="Enter Description"><?= isset($data['description']) ? htmlspecialchars($data['description']) : '' ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= (!isset($data['status']) || $data['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="deactive" <?= (isset($data['status']) && $data['status'] == 'deactive') ? 'selected' : '' ?>>Deactive</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <hr>
                                <h6 class="fw-bold mb-3 text-primary">Material Consumption (Stock Management)</h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Primary Material (for Stock Deduction)</label>
                                <select name="mapped_material_id" class="form-select select2">
                                    <option value="">None</option>
                                    <?php foreach ($all_materials as $mat) { ?>
                                        <option value="<?= $mat['id'] ?>" <?= (isset($data['mapped_material_id']) && $data['mapped_material_id'] == $mat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($mat['name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <div class="form-text">Select the main material to be deducted when this product is ordered.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Usage Qty (per 1 PCS)</label>
                                <div class="input-group">
                                    <input type="number" step="0.001" name="usage_qty" class="form-control"
                                        placeholder="0.000"
                                        value="<?= isset($data['usage_qty']) ? $data['usage_qty'] : '' ?>">
                                    <span class="input-group-text">KG</span>
                                </div>
                                <div class="form-text">Example: 2.000 KG per 1 Unit.</div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" name="btn_submit" class="btn btn-gold btn-sm rounded-pill px-4">
                                <i class="bi bi-check-circle me-1"></i>
                                <?= ($mode == 'edit') ? 'Update Product' : 'Save Product' ?>
                            </button>
                            <a href="product.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<?php if ($mode === 'bom') { ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const materialSelect = document.getElementById('material_name');
            const rateInput = document.querySelector('input[name="rate"]');
            const bomIdInput = document.querySelector('input[name="bom_id"]');

            if (!materialSelect || !rateInput) {
                return;
            }

            function applyMaterialRate() {
                const selectedOption = materialSelect.options[materialSelect.selectedIndex];
                if (!selectedOption) {
                    return;
                }

                const selectedRate = selectedOption.getAttribute('data-rate') || '';
                const isEditing = bomIdInput && bomIdInput.value !== '0';

                if (!isEditing || rateInput.value === '') {
                    rateInput.value = selectedRate;
                }
            }

            materialSelect.addEventListener('change', applyMaterialRate);
        });
    </script>
<?php } ?>

<?php include 'include/footer.php'; ?>
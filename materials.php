<?php
    $pageTitle = "Materials";
    $currentPage = "materials";
    $headerTitle = "Manage Materials";

    include 'include/header.php';

    $table = "tbl_materials";
    $redirection_url = "materials.php";

    $mode = $_REQUEST['mode'] ?? '';
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $data = null;
    $error = '';

    // Get material types for dropdown
    $material_types = $ai_db->aiGetQuery("SELECT id, name FROM tbl_material_type WHERE status='active' AND is_deleted=0 ORDER BY name");

    if ($mode === "add" && isset($_POST['btn_submit'])) {
        $material_type_id = intval($_POST['material_type_id']);
        $name = trim($_POST['name'] ?? '');
        $name_escaped = addslashes($name);
        $f_value = $_POST['f_value'] ?? 0;
        $p_value = $_POST['p_value'] ?? 0;
        $top_value = $_POST['top_value'] ?? 0;
        $rate = $_POST['rate'] ?? 0;
        $weight = $_POST['weight'] ?? 0;
        $status = $_POST['status'] ?? 'deactive';

        if ($material_type_id <= 0 || $name === '') {
            $error = 'Material Type and Name are required.';
        } else {
            $duplicate = $ai_db->aiGetQuery("SELECT id FROM $table WHERE material_type_id='" . $material_type_id . "' AND name='" . addslashes($name) . "' AND is_deleted=0 LIMIT 1");
            if (!empty($duplicate)) {
                $error = 'This material already exists for the selected material type.';
            }
        }

        if (empty($error)) {
            $add_qry = "INSERT INTO $table SET
                material_type_id='" . $material_type_id . "',
                name='" . $name_escaped . "',
                f_value='" . $f_value . "',
                p_value='" . $p_value . "',
                top_value='" . $top_value . "',
                rate='" . $rate . "',
                weight='" . $weight . "',
                status='" . $status . "',
                created_by='" . $_SESSION['aid'] . "'";
            $ai_db->aiQuery($add_qry);
            $ai_core->aiGoPage($redirection_url . "?msg=1");
            exit;
        }

        $data = [
            'material_type_id' => $material_type_id,
            'name' => htmlspecialchars($name),
            'f_value' => htmlspecialchars($_POST['f_value'] ?? ''),
            'p_value' => htmlspecialchars($_POST['p_value'] ?? ''),
            'top_value' => htmlspecialchars($_POST['top_value'] ?? ''),
            'rate' => htmlspecialchars($_POST['rate'] ?? ''),
            'weight' => htmlspecialchars($_POST['weight'] ?? ''),
            'status' => $status
        ];
    }

    if ($mode === "edit" && isset($_POST['btn_submit'])) {
        $material_type_id = intval($_POST['material_type_id']);
        $name = trim($_POST['name'] ?? '');
        $name_escaped = addslashes($name);
        $f_value = $_POST['f_value'] ?? 0;
        $p_value = $_POST['p_value'] ?? 0;
        $top_value = $_POST['top_value'] ?? 0;
        $rate = $_POST['rate'] ?? 0;
        $weight = $_POST['weight'] ?? 0;
        $status = $_POST['status'] ?? 'deactive';

        if ($material_type_id <= 0 || $name === '') {
            $error = 'Material Type and Name are required.';
        } else {
            $duplicate = $ai_db->aiGetQuery("SELECT id FROM $table WHERE material_type_id='" . $material_type_id . "' AND name='" . addslashes($name) . "' AND is_deleted=0 AND id != '" . intval($id) . "' LIMIT 1");
            if (!empty($duplicate)) {
                $error = 'This material already exists for the selected material type.';
            }
        }

        if (empty($error)) {
            $edit_qry = "UPDATE $table SET
                material_type_id='" . $material_type_id . "',
                name='" . $name_escaped . "',
                f_value='" . $f_value . "',
                p_value='" . $p_value . "',
                top_value='" . $top_value . "',
                rate='" . $rate . "',
                weight='" . $weight . "',
                status='" . $status . "',
                updated_by='" . $_SESSION['aid'] . "'
                WHERE id='" . intval($id) . "'";
            $ai_db->aiQuery($edit_qry);
            $ai_core->aiGoPage($redirection_url . "?msg=2");
            exit;
        }

        $data = [
            'material_type_id' => $material_type_id,
            'name' => htmlspecialchars($name),
            'f_value' => htmlspecialchars($_POST['f_value'] ?? ''),
            'p_value' => htmlspecialchars($_POST['p_value'] ?? ''),
            'top_value' => htmlspecialchars($_POST['top_value'] ?? ''),
            'rate' => htmlspecialchars($_POST['rate'] ?? ''),
            'weight' => htmlspecialchars($_POST['weight'] ?? ''),
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

    if ($mode === "edit" && $id && !isset($_POST['btn_submit'])) {
        $query = "SELECT * FROM $table WHERE id='" . intval($id) . "' AND is_deleted=0 LIMIT 1";
        $result = $ai_db->aiGetQuery($query);
        $data = isset($result[0]) ? $result[0] : null;
    }

    $all_data = [];
    $totalRecords = 0;
    $totalPages = 1;
    $limit = 20;
    if (!$mode) {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $limit;

        $filterSessionKey = 'materials_filters';
        if (isset($_GET['action']) && $_GET['action'] === 'clear_filters') {
            unset($_SESSION[$filterSessionKey]);
            header('Location: materials.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION[$filterSessionKey] = [
                'filter_material_type' => $_POST['filter_material_type'] ?? '',
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

        $where_conditions = ["m.is_deleted=0"];
        if (!empty($filters['filter_material_type'])) {
            $where_conditions[] = "m.material_type_id = '" . intval($filters['filter_material_type']) . "'";
        }
        if (!empty($filters['filter_name'])) {
            $where_conditions[] = "m.name LIKE '%" . addslashes($filters['filter_name']) . "%'";
        }
        if (!empty($filters['filter_status']) && $filters['filter_status'] !== '') {
            $where_conditions[] = "m.status = '" . addslashes($filters['filter_status']) . "'";
        }

        $where_clause = implode(" AND ", $where_conditions);
        $countResult = $ai_db->aiGetQuery("SELECT COUNT(*) as total FROM $table m WHERE $where_clause");
        $totalRecords = isset($countResult[0]['total']) ? intval($countResult[0]['total']) : 0;
        $totalPages = $totalRecords > 0 ? max(1, ceil($totalRecords / $limit)) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        $all_data = $ai_db->aiGetQuery("SELECT m.*, mt.name as material_type_name FROM $table m LEFT JOIN tbl_material_type mt ON m.material_type_id = mt.id WHERE $where_clause ORDER BY m.id DESC LIMIT $limit OFFSET $offset");
    }
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">
            <span class="text-muted fw-light">Materials /</span> <?= $mode ? ucfirst($mode) : 'All Records' ?>
        </h4>
        <?php if (!$mode) { ?>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle btn-filter-toggle <?= !empty($hasActiveFilters) ? 'active' : '' ?>"
                    data-bs-toggle="collapse" data-bs-target="#materialsFilterCollapse" aria-expanded="false"
                    aria-controls="materialsFilterCollapse" aria-label="Toggle Filters" title="Toggle Filters">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="materials.php?mode=add" class="btn btn-gold btn-sm rounded-pill px-3">
                    <i class="bi bi-plus-lg me-1"></i> Add New
                </a>
            </div>
        <?php } else { ?>
            <a href="materials.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        <?php } ?>
    </div>

    <?php if (!$mode) { ?>
        <?php
        // Get material types for filter dropdown
        $material_types = $ai_db->aiGetQuery("SELECT id, name FROM tbl_material_type WHERE is_deleted=0 ORDER BY name ASC");
        ?>

        <!-- Filter Section -->
        <div class="collapse mb-3" id="materialsFilterCollapse">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="materials.php" class="row g-3">
                        <div class="col-md-4">
                            <label for="filter_material_type" class="form-label">Material Type</label>
                            <select class="form-select" id="filter_material_type" name="filter_material_type">
                                <option value="">All Material Types</option>
                                <?php foreach ($material_types as $mt) { ?>
                                    <option value="<?= $mt['id'] ?>" <?= ($filters['filter_material_type'] ?? '') == $mt['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mt['name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filter_name" class="form-label">Material Name</label>
                            <input type="text" class="form-control" id="filter_name" name="filter_name" 
                                   value="<?= htmlspecialchars($filters['filter_name'] ?? '') ?>" placeholder="Search by Name">
                        </div>
                        <div class="col-md-4">
                            <label for="filter_status" class="form-label">Status</label>
                            <select class="form-select" id="filter_status" name="filter_status">
                                <option value="">All Status</option>
                                <option value="active" <?= ($filters['filter_status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="deactive" <?= ($filters['filter_status'] ?? '') === 'deactive' ? 'selected' : '' ?>>Deactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm me-2">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                            <a href="materials.php?action=clear_filters" class="btn btn-outline-secondary btn-sm">
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
                                <th>Material Type</th>
                                <th>Name</th>
                                <th>F/P/Top</th>
                                <th>Rate</th>
                                <th>Weight</th>
                                <th>Status</th>
                                <th width="200" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_data)) {
                                foreach ($all_data as $index => $row) { ?>
                                    <tr>
                                        <td>#<?= $index + 1 ?></td>
                                        <td><span class="fw-semibold"><?= htmlspecialchars($row['material_type_name']) ?></span></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= $row['f_value'] ?>/<?= $row['p_value'] ?>/<?= $row['top_value'] ?></td>
                                        <td>₹<?= number_format($row['rate'], 2) ?></td>
                                        <td><?= $row['weight'] ?> kg</td>
                                        <td>
                                            <?php if ($row['status'] == 'active') { ?>
                                                <span class="badge bg-success-subtle text-success px-3 rounded-pill">Active</span>
                                            <?php } else { ?>
                                                <span class="badge bg-danger-subtle text-danger px-3 rounded-pill">Deactive</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="materials.php?mode=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Edit">
                                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                                </a>
                                                <a href="materials.php?mode=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Delete" onclick="return confirm('Are you sure you want to delete this record?')">
                                                    <i class="bi bi-trash me-1"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
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
                            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> entries
                        </div>
                        <?php if ($totalPages > 1) { ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination mb-0">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="materials.php?page=<?= $page - 1 ?>" tabindex="-1">Previous</a>
                                    </li>
                                    <?php for ($p = 1; $p <= $totalPages; $p++) {
                                        if ($p == 1 || $p == $totalPages || ($p >= $page - 2 && $p <= $page + 2)) { ?>
                                            <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                                <a class="page-link" href="materials.php?page=<?= $p ?>"><?= $p ?></a>
                                            </li>
                                        <?php } elseif ($p == $page - 3 || $p == $page + 3) { ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php }
                                    } ?>
                                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="materials.php?page=<?= $page + 1 ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } else { ?>
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h5 class="fw-bold mb-0"><?= ($mode == 'edit') ? 'Update' : 'Create' ?> Material</h5>
                    </div>
                    <?php if (!empty($error)) { ?>
                        <div class="alert alert-danger rounded-3 mx-3 mt-3">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php } ?>
                    <form class="card-body" method="post" action="materials.php?mode=<?= $mode ?>&id=<?= $id ?>">
                        <input type="hidden" name="id" value="<?= isset($data['id']) ? $data['id'] : '' ?>">

                        <div class="row g-4">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Material Type <span class="text-danger">*</span></label>
                                <select name="material_type_id" class="form-select form-select" required>
                                    <option value="">Select Material Type</option>
                                    <?php foreach ($material_types as $type) { ?>
                                        <option value="<?= $type['id'] ?>" <?= (isset($data['material_type_id']) && $data['material_type_id'] == $type['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type['name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control" placeholder="Enter Material Name" value="<?= isset($data['name']) ? htmlspecialchars($data['name']) : '' ?>" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">F Value</label>
                                <input type="number" step="0.01" name="f_value" class="form-control form-control" placeholder="Enter F Value" value="<?= isset($data['f_value']) ? $data['f_value'] : '' ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">P Value</label>
                                <input type="number" step="0.01" name="p_value" class="form-control form-control" placeholder="Enter P Value" value="<?= isset($data['p_value']) ? $data['p_value'] : '' ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Top Value</label>
                                <input type="number" step="0.01" name="top_value" class="form-control form-control" placeholder="Enter Top Value" value="<?= isset($data['top_value']) ? $data['top_value'] : '' ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Rate</label>
                                <input type="number" step="0.01" name="rate" class="form-control form-control" placeholder="Enter Rate" value="<?= isset($data['rate']) ? $data['rate'] : '' ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Weight (kg)</label>
                                <input type="number" step="0.01" name="weight" class="form-control form-control" placeholder="Enter Weight (kg)" value="<?= isset($data['weight']) ? $data['weight'] : '' ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select form-select">
                                    <option value="active" <?= (!isset($data['status']) || $data['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="deactive" <?= (isset($data['status']) && $data['status'] == 'deactive') ? 'selected' : '' ?>>Deactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" name="btn_submit" class="btn btn-gold btn-sm rounded-pill px-4">
                                <i class="bi bi-check-circle me-1"></i> <?= ($mode == 'edit') ? 'Update Material' : 'Save Material' ?>
                            </button>
                            <a href="materials.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<?php include 'include/footer.php'; ?>

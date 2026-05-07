<?php
    $pageTitle = "Offset";
    $currentPage = "offset";
    $headerTitle = "Manage Offsets";

    include 'include/header.php';

    $table = "tbl_offset";
    $redirection_url = "offset.php";

    $mode = $_REQUEST['mode'] ?? '';
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $data = null;
    $error = '';

    if ($mode === "add" && isset($_POST['btn_submit'])) {
        $name = trim($_POST['name'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $status = $_POST['status'] ?? 'deactive';

        if ($name === '' || $contact_number === '') {
            $error = 'Name and Contact Number are required.';
        } else {
            $duplicate = $ai_db->aiGetQuery("SELECT id FROM $table WHERE contact_number='" . addslashes($contact_number) . "' AND is_deleted=0 LIMIT 1");
            if (!empty($duplicate)) {
                $error = 'This contact number is already used by another offset.';
            }
        }

        if (empty($error)) {
            $add_qry = "INSERT INTO $table SET
                name='" . addslashes($name) . "',
                contact_number='" . addslashes($contact_number) . "',
                status='" . $status . "',
                created_by='" . $_SESSION['aid'] . "'";
            $ai_db->aiQuery($add_qry);
            $ai_core->aiGoPage($redirection_url . "?msg=1");
            exit;
        }

        $data = [
            'name' => htmlspecialchars($name),
            'contact_number' => htmlspecialchars($contact_number),
            'status' => $status
        ];
    }

    if ($mode === "edit" && isset($_POST['btn_submit'])) {
        $name = trim($_POST['name'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $status = $_POST['status'] ?? 'deactive';

        if ($name === '' || $contact_number === '') {
            $error = 'Name and Contact Number are required.';
        } else {
            $duplicate = $ai_db->aiGetQuery("SELECT id FROM $table WHERE contact_number='" . addslashes($contact_number) . "' AND is_deleted=0 AND id != '" . intval($id) . "' LIMIT 1");
            if (!empty($duplicate)) {
                $error = 'This contact number is already used by another offset.';
            }
        }

        if (empty($error)) {
            $edit_qry = "UPDATE $table SET
                name='" . addslashes($name) . "',
                contact_number='" . addslashes($contact_number) . "',
                status='" . $status . "',
                updated_by='" . $_SESSION['aid'] . "'
                WHERE id='" . intval($id) . "'";
            $ai_db->aiQuery($edit_qry);
            $ai_core->aiGoPage($redirection_url . "?msg=2");
            exit;
        }

        $data = [
            'name' => htmlspecialchars($name),
            'contact_number' => htmlspecialchars($contact_number),
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

        $filterSessionKey = 'offset_filters';
        if (isset($_GET['action']) && $_GET['action'] === 'clear_filters') {
            unset($_SESSION[$filterSessionKey]);
            header('Location: offset.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION[$filterSessionKey] = [
                'filter_name' => trim($_POST['filter_name'] ?? ''),
                'filter_contact_number' => trim($_POST['filter_contact_number'] ?? ''),
                'filter_status' => $_POST['filter_status'] ?? ''
            ];
            $page = 1;
            $offset = 0;
        }

        $filters = $_SESSION[$filterSessionKey] ?? [];
        $hasActiveFilters = !empty(array_filter($filters, function ($value) {
            return $value !== '' && $value !== null;
        }));

        $where_conditions = ["is_deleted=0"];
        if (!empty($filters['filter_name'])) {
            $where_conditions[] = "name LIKE '%" . addslashes($filters['filter_name']) . "%'";
        }
        if (!empty($filters['filter_contact_number'])) {
            $where_conditions[] = "contact_number LIKE '%" . addslashes($filters['filter_contact_number']) . "%'";
        }
        if (!empty($filters['filter_status']) && $filters['filter_status'] !== '') {
            $where_conditions[] = "status = '" . addslashes($filters['filter_status']) . "'";
        }

        $where_clause = implode(" AND ", $where_conditions);
        $countResult = $ai_db->aiGetQuery("SELECT COUNT(*) as total FROM $table WHERE $where_clause");
        $totalRecords = isset($countResult[0]['total']) ? intval($countResult[0]['total']) : 0;
        $totalPages = $totalRecords > 0 ? max(1, ceil($totalRecords / $limit)) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        $all_data = $ai_db->aiGetQuery("SELECT * FROM $table WHERE $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset");
    }
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">
            <span class="text-muted fw-light">Offset /</span> <?= $mode ? ucfirst($mode) : 'All Records' ?>
        </h4>
        <?php if (!$mode) { ?>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle btn-filter-toggle <?= !empty($hasActiveFilters) ? 'active' : '' ?>"
                    data-bs-toggle="collapse" data-bs-target="#offsetFilterCollapse" aria-expanded="false"
                    aria-controls="offsetFilterCollapse" aria-label="Toggle Filters" title="Toggle Filters">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="offset.php?mode=add" class="btn btn-gold btn-sm rounded-pill px-3">
                    <i class="bi bi-plus-lg me-1"></i> Add New
                </a>
            </div>
        <?php } else { ?>
            <a href="offset.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        <?php } ?>
    </div>

    <?php if (!$mode) { ?>
        <!-- Filter Section -->
        <div class="collapse mb-3" id="offsetFilterCollapse">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="offset.php" class="row g-3">
                        <div class="col-md-4">
                            <label for="filter_name" class="form-label">Offset Name</label>
                            <input type="text" class="form-control" id="filter_name" name="filter_name" 
                                   value="<?= htmlspecialchars($filters['filter_name'] ?? '') ?>" placeholder="Search by Name">
                        </div>
                        <div class="col-md-4">
                            <label for="filter_contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="filter_contact_number" name="filter_contact_number" 
                                   value="<?= htmlspecialchars($filters['filter_contact_number'] ?? '') ?>" placeholder="Search by Contact Number">
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
                            <a href="offset.php?action=clear_filters" class="btn btn-outline-secondary btn-sm">
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
                                <th>Contact Number</th>
                                <th>Status</th>
                                <th width="200" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_data)) {
                                foreach ($all_data as $index => $row) { ?>
                                    <tr>
                                        <td>#<?= $index + 1 ?></td>
                                        <td><span class="fw-semibold"><?= htmlspecialchars($row['name']) ?></span></td>
                                        <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'active') { ?>
                                                <span class="badge bg-success-subtle text-success px-3 rounded-pill">Active</span>
                                            <?php } else { ?>
                                                <span class="badge bg-danger-subtle text-danger px-3 rounded-pill">Deactive</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="offset.php?mode=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Edit">
                                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                                </a>
                                                <a href="offset.php?mode=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Delete" onclick="return confirm('Are you sure you want to delete this record?')">
                                                    <i class="bi bi-trash me-1"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
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
                                        <a class="page-link" href="offset.php?page=<?= $page - 1 ?>" tabindex="-1">Previous</a>
                                    </li>
                                    <?php for ($p = 1; $p <= $totalPages; $p++) {
                                        if ($p == 1 || $p == $totalPages || ($p >= $page - 2 && $p <= $page + 2)) { ?>
                                            <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                                <a class="page-link" href="offset.php?page=<?= $p ?>"><?= $p ?></a>
                                            </li>
                                        <?php } elseif ($p == $page - 3 || $p == $page + 3) { ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php }
                                    } ?>
                                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="offset.php?page=<?= $page + 1 ?>">Next</a>
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
                        <h5 class="fw-bold mb-0"><?= ($mode == 'edit') ? 'Update' : 'Create' ?> Offset</h5>
                    </div>
                    <?php if (!empty($error)) { ?>
                        <div class="alert alert-danger rounded-3 mx-3 mt-3">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php } ?>
                    <form class="card-body" method="post" action="offset.php?mode=<?= $mode ?>&id=<?= $id ?>">
                        <input type="hidden" name="id" value="<?= isset($data['id']) ? $data['id'] : '' ?>">

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control" placeholder="Enter Offset Name" value="<?= isset($data['name']) ? htmlspecialchars($data['name']) : '' ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" name="contact_number" class="form-control form-control" placeholder="Enter Contact Number" value="<?= isset($data['contact_number']) ? htmlspecialchars($data['contact_number']) : '' ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select form-select">
                                    <option value="active" <?= (!isset($data['status']) || $data['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="deactive" <?= (isset($data['status']) && $data['status'] == 'deactive') ? 'selected' : '' ?>>Deactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" name="btn_submit" class="btn btn-gold btn-sm rounded-pill px-4">
                                <i class="bi bi-check-circle me-1"></i> <?= ($mode == 'edit') ? 'Update Offset' : 'Save Offset' ?>
                            </button>
                            <a href="offset.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<?php include 'include/footer.php'; ?>

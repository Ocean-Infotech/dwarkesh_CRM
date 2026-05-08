<?php
    $pageTitle = "Customer";
    $currentPage = "customer";
    $headerTitle = "Manage Customers";

    include 'include/header.php';

    $table = "tbl_customer";
    $redirection_url = "customer.php";

    $mode = $_REQUEST['mode'] ?? '';
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $data = null;
    $error = '';

    if ($mode === "add" && isset($_POST['btn_submit'])) {
        $contact_name = trim($_POST['contact_name'] ?? '');
        $phone_no = trim($_POST['phone_no'] ?? '');
        $address = addslashes($_POST['address'] ?? '');
        $city_name = addslashes($_POST['city_name'] ?? '');
        $status = $_POST['status'] ?? 'deactive';
        $brand_names = json_encode($_POST['brand_names'] ?? [], JSON_UNESCAPED_UNICODE);

        if ($contact_name === '' || $phone_no === '') {
            $error = 'Contact Name and Phone No. are required.';
        } else {
            $phone_no = addslashes($phone_no);
            $contact_name = addslashes($contact_name);
            $duplicate = $ai_db->aiGetQuery("SELECT id FROM $table WHERE phone_no='" . $phone_no . "' AND is_deleted=0 LIMIT 1");
            if (!empty($duplicate)) {
                $error = 'This phone number already exists.';
            }
        }

        if (empty($error)) {
            $add_qry = "INSERT INTO $table SET
                contact_name='" . $contact_name . "',
                phone_no='" . $phone_no . "',
                address='" . $address . "',
                city_name='" . $city_name . "',
                status='" . $status . "',
                brand_names='$brand_names',
                created_by='" . $_SESSION['aid'] . "'";
            $ai_db->aiQuery($add_qry);
            $ai_core->aiGoPage($redirection_url . "?msg=1");
            exit;
        }

        $data = [
            'contact_name' => htmlspecialchars($contact_name),
            'phone_no' => htmlspecialchars($phone_no),
            'address' => htmlspecialchars($_POST['address'] ?? ''),
            'city_name' => htmlspecialchars($_POST['city_name'] ?? ''),
            'status' => $status,
            'brand_names' => $_POST['brand_names'] ?? []
        ];
    }

    if ($mode === "edit" && isset($_POST['btn_submit'])) {
        $contact_name = trim($_POST['contact_name'] ?? '');
        $phone_no = trim($_POST['phone_no'] ?? '');
        $address = addslashes($_POST['address'] ?? '');
        $city_name = addslashes($_POST['city_name'] ?? '');
        $status = $_POST['status'] ?? 'deactive';
        $brand_names = json_encode($_POST['brand_names'] ?? [], JSON_UNESCAPED_UNICODE);

        if ($contact_name === '' || $phone_no === '') {
            $error = 'Contact Name and Phone No. are required.';
        } else {
            $phone_no = addslashes($phone_no);
            $contact_name = addslashes($contact_name);
            $duplicate = $ai_db->aiGetQuery("SELECT id FROM $table WHERE phone_no='" . $phone_no . "' AND id != '" . intval($id) . "' AND is_deleted=0 LIMIT 1");
            if (!empty($duplicate)) {
                $error = 'This phone number already exists.';
            }
        }

        if (empty($error)) {
            $edit_qry = "UPDATE $table SET
                contact_name='" . $contact_name . "',
                phone_no='" . $phone_no . "',
                address='" . $address . "',
                city_name='" . $city_name . "',
                status='" . $status . "',
                brand_names='$brand_names',
                updated_by='" . $_SESSION['aid'] . "'
                WHERE id='" . intval($id) . "'";
            $ai_db->aiQuery($edit_qry);
            $ai_core->aiGoPage($redirection_url . "?msg=2");
            exit;
        }

        $data = [
            'contact_name' => htmlspecialchars($contact_name),
            'phone_no' => htmlspecialchars($phone_no),
            'address' => htmlspecialchars($_POST['address'] ?? ''),
            'city_name' => htmlspecialchars($_POST['city_name'] ?? ''),
            'status' => $status,
            'brand_names' => $_POST['brand_names'] ?? []
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

    $all_data = [];
    $totalRecords = 0;
    $totalPages = 1;
    $limit = 20;
    if (!$mode) {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $limit;

        $filterSessionKey = 'customer_filters';
        if (isset($_GET['action']) && $_GET['action'] === 'clear_filters') {
            unset($_SESSION[$filterSessionKey]);
            header('Location: customer.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION[$filterSessionKey] = [
                'filter_contact_name' => trim($_POST['filter_contact_name'] ?? ''),
                'filter_phone_no' => trim($_POST['filter_phone_no'] ?? ''),
                'filter_city' => trim($_POST['filter_city'] ?? ''),
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

        if (!empty($filters['filter_contact_name'])) {
            $where_conditions[] = "contact_name LIKE '%" . addslashes($filters['filter_contact_name']) . "%'";
        }
        if (!empty($filters['filter_phone_no'])) {
            $where_conditions[] = "phone_no LIKE '%" . addslashes($filters['filter_phone_no']) . "%'";
        }
        if (!empty($filters['filter_city'])) {
            $where_conditions[] = "city_name LIKE '%" . addslashes($filters['filter_city']) . "%'";
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
            <span class="text-muted fw-light">Customer /</span> <?= $mode ? ucfirst($mode) : 'All Records' ?>
        </h4>
        <?php if (!$mode) { ?>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle btn-filter-toggle <?= !empty($hasActiveFilters) ? 'active' : '' ?>"
                    data-bs-toggle="collapse" data-bs-target="#customerFilterCollapse" aria-expanded="false"
                    aria-controls="customerFilterCollapse" aria-label="Toggle Filters" title="Toggle Filters">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="customer.php?mode=add" class="btn btn-gold btn-sm rounded-pill px-3">
                    <i class="bi bi-plus-lg me-1"></i> Add New
                </a>
            </div>
        <?php } else { ?>
            <a href="customer.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        <?php } ?>
    </div>

    <?php if (!$mode) { ?>
        <!-- Filter Section -->
        <div class="collapse mb-3" id="customerFilterCollapse">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="customer.php" class="row g-3">
                        <div class="col-md-3">
                            <label for="filter_contact_name" class="form-label">Contact Name</label>
                            <input type="text" class="form-control" id="filter_contact_name" name="filter_contact_name" 
                                   value="<?= htmlspecialchars($filters['filter_contact_name'] ?? '') ?>" placeholder="Search by Name">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_phone_no" class="form-label">Phone No</label>
                            <input type="text" class="form-control" id="filter_phone_no" name="filter_phone_no" 
                                   value="<?= htmlspecialchars($filters['filter_phone_no'] ?? '') ?>" placeholder="Search by Phone">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_city" class="form-label">City</label>
                            <input type="text" class="form-control" id="filter_city" name="filter_city" 
                                   value="<?= htmlspecialchars($filters['filter_city'] ?? '') ?>" placeholder="Search by City">
                        </div>
                        <div class="col-md-3">
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
                            <a href="customer.php?action=clear_filters" class="btn btn-outline-secondary btn-sm">
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
                                <th>Contact Name</th>
                                <th>Phone No.</th>
                                <th>City</th>
                                <th>Status</th>
                                <th width="200" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_data)) {
                                foreach ($all_data as $index => $row) { ?>
                                    <tr>
                                        <td>#<?= $offset + $index + 1 ?></td>
                                        <td><span class="fw-semibold"><?= htmlspecialchars($row['contact_name']) ?></span></td>
                                        <td><?= htmlspecialchars($row['phone_no']) ?></td>
                                        <td><?= htmlspecialchars($row['city_name']) ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'active') { ?>
                                                <span class="badge bg-success-subtle text-success px-3 rounded-pill">Active</span>
                                            <?php } else { ?>
                                                <span class="badge bg-danger-subtle text-danger px-3 rounded-pill">Deactive</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="customer.php?mode=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Edit">
                                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                                </a>
                                                <a href="customer.php?mode=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Delete" onclick="return confirm('Are you sure you want to delete this record?')">
                                                    <i class="bi bi-trash me-1"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
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
                                        <a class="page-link" href="customer.php?page=<?= $page - 1 ?>" tabindex="-1">Previous</a>
                                    </li>
                                    <?php for ($p = 1; $p <= $totalPages; $p++) {
                                        if ($p == 1 || $p == $totalPages || ($p >= $page - 2 && $p <= $page + 2)) { ?>
                                            <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                                <a class="page-link" href="customer.php?page=<?= $p ?>"><?= $p ?></a>
                                            </li>
                                        <?php } elseif ($p == $page - 3 || $p == $page + 3) { ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php }
                                    } ?>
                                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="customer.php?page=<?= $page + 1 ?>">Next</a>
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
                        <h5 class="fw-bold mb-0"><?= ($mode == 'edit') ? 'Update' : 'Create' ?> Customer</h5>
                    </div>
                    <form class="card-body" method="post" action="customer.php?mode=<?= $mode ?>&id=<?= $id ?>" enctype="multipart/form-data">
                        <?php if (!empty($error)) { ?>
                            <div class="alert alert-danger py-2 mb-3" role="alert">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php } ?>
                        <input type="hidden" name="id" value="<?= isset($data['id']) ? $data['id'] : '' ?>">

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contact Name <span class="text-danger">*</span></label>
                                <input type="text" name="contact_name" class="form-control form-control" placeholder="Enter Contact Name" value="<?= isset($data['contact_name']) ? htmlspecialchars($data['contact_name']) : '' ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone No. <span class="text-danger">*</span></label>
                                <input type="text" name="phone_no" class="form-control form-control" placeholder="Enter Phone Number" value="<?= isset($data['phone_no']) ? htmlspecialchars($data['phone_no']) : '' ?>" required>
                            </div>

                            <div class="col-6">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="address" class="form-control form-control" rows="3" placeholder="Enter Address"><?= isset($data['address']) ? htmlspecialchars($data['address']) : '' ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">City Name</label>
                                <input type="text" name="city_name" class="form-control form-control" placeholder="Enter City Name" value="<?= isset($data['city_name']) ? htmlspecialchars($data['city_name']) : '' ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select form-select">
                                    <option value="active" <?= (!isset($data['status']) || $data['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="deactive" <?= (isset($data['status']) && $data['status'] == 'deactive') ? 'selected' : '' ?>>Deactive</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <div class="bg-light p-3 rounded border">
                                    <label class="form-label fw-bold d-block mb-3">Brand Names</label>
                                    <div id="brandRepeater">
                                        <?php
                                        $brands = [''];
                                        if (isset($data['brand_names']) && !empty($data['brand_names'])) {
                                            if (is_string($data['brand_names'])) {
                                                $brands = json_decode($data['brand_names'], true);
                                                if (!is_array($brands)) {
                                                    $splitBrands = preg_split('/[\r\n,]+/', $data['brand_names']);
                                                    $brands = is_array($splitBrands) ? $splitBrands : [$data['brand_names']];
                                                }
                                            } elseif (is_array($data['brand_names'])) {
                                                $brands = $data['brand_names'];
                                            }
                                        }
                                        if (!is_array($brands)) {
                                            $brands = [''];
                                        }
                                        foreach($brands as $i => $brand){ ?>
                                            <div class="input-group input-group-sm mb-2 brand-item">
                                                <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                                <input type="text" name="brand_names[<?= $i ?>]" class="form-control" placeholder="Brand Name" value="<?= $brand ?? '' ?>">
                                                <button type="button" class="btn btn-danger removeBrand"><i class="bi bi-trash"></i></button>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3 mt-2" id="addBrandBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Add More
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" name="btn_submit" class="btn btn-gold btn-sm rounded-pill px-4">
                                <i class="bi bi-check-circle me-1"></i> <?= ($mode == 'edit') ? 'Update Customer' : 'Save Customer' ?>
                            </button>
                            <a href="customer.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<?php include 'include/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addBrandBtn = document.getElementById('addBrandBtn');
    if(addBrandBtn) {
        addBrandBtn.onclick = function () {
            let container = document.getElementById('brandRepeater');
            let index = container.querySelectorAll('.brand-item').length;
            let html = `
                <div class="input-group input-group-sm mb-2 brand-item">
                    <span class="input-group-text"><i class="bi bi-tag"></i></span>
                    <input type="text" name="brand_names[${index}]" class="form-control" placeholder="Brand Name">
                    <button type="button" class="btn btn-danger removeBrand"><i class="bi bi-trash"></i></button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        };
    }

    document.addEventListener('click', function(e){
        if(e.target.closest('.removeBrand')){
            const item = e.target.closest('.brand-item');
            if(document.querySelectorAll('.brand-item').length > 1) {
                item.remove();
            }
        }
    });
});
</script>

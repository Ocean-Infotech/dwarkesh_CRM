<?php
    $pageTitle = "Quotations";
    $currentPage = "quotations";
    $headerTitle = "Manage Quotations";
    $extraHead = '<link rel="stylesheet" href="assets/css/quotations.css">';

    include 'include/header.php';
    require_once 'root/schema_bootstrap.php';
    dwarkesh_ensure_core_tables($ai_db);
    // Automatic DB Fix for missing columns
    $ai_db->aiQuery("ALTER TABLE tbl_quotations MODIFY COLUMN valid_till date NULL");
    $ai_db->aiQuery("ALTER TABLE tbl_quotations ADD COLUMN IF NOT EXISTS total_taxable decimal(10,2) DEFAULT 0.00 AFTER customer_name");
    $ai_db->aiQuery("ALTER TABLE tbl_quotations ADD COLUMN IF NOT EXISTS total_amount decimal(10,2) DEFAULT 0.00 AFTER total_taxable");
    $ai_db->aiQuery("ALTER TABLE tbl_quotation_items ADD COLUMN IF NOT EXISTS taxable_amount decimal(10,2) DEFAULT 0.00 AFTER rate");
    $ai_db->aiQuery("ALTER TABLE tbl_quotation_items ADD COLUMN IF NOT EXISTS total_amount decimal(10,2) DEFAULT 0.00 AFTER taxable_amount");

    $table = "tbl_quotations";
    $items_table = "tbl_quotation_items";
    $redirection_url = "quotations.php";

    $mode = $_REQUEST['mode'] ?? '';
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $data = null;
    $error = '';

    function quotation_generate_no($ai_db, $currentId = 0)
    {
        $where = "is_deleted=0";
        if ($currentId > 0) {
            $where .= " AND id!='" . intval($currentId) . "'";
        }

        $rows = $ai_db->aiGetQuery("SELECT quotation_no FROM tbl_quotations WHERE $where ORDER BY id DESC LIMIT 1");
        $maxNumber = 0;

        if (!empty($rows)) {
            $val = trim((string)$rows[0]['quotation_no']);
            if (is_numeric($val)) {
                $maxNumber = intval($val);
            }
        }

        $nextNumber = $maxNumber + 1;
        return (string)$nextNumber;
    }

    $customers = $ai_db->aiGetQuery("SELECT id, contact_name FROM tbl_customer WHERE status='active' AND is_deleted=0 ORDER BY contact_name ASC");
    $products = $ai_db->aiGetQuery("SELECT id, name, description, rate FROM tbl_product WHERE status='active' AND is_deleted=0 ORDER BY name ASC");
    $costings = $ai_db->aiGetQuery("SELECT id, estimate_no, customer_name, product_name, sale_rate FROM tbl_costings WHERE is_deleted=0 ORDER BY id DESC LIMIT 100");

    if (($mode === "add" || $mode === "edit") && isset($_POST['btn_submit'])) {
        $quotation_date = $_POST['quotation_date'] ?? date('Y-m-d');
        $valid_till = $_POST['valid_till'] ?? '';
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $customer_name = "";
        foreach ($customers as $c) {
            if ($c['id'] == $customer_id) {
                $customer_name = $c['contact_name'];
                break;
            }
        }

        $total_taxable = floatval($_POST['total_taxable'] ?? 0);
        $total_amount = floatval($_POST['total_amount'] ?? 0);
        $remark = addslashes($_POST['remark'] ?? '');

        if ($customer_id <= 0) {
            $error = 'Customer is required.';
        }

        if (empty($error)) {
            $valid_till_val = !empty($valid_till) ? "'" . addslashes($valid_till) . "'" : "NULL";

            if ($mode === "add") {
                $quotation_no = quotation_generate_no($ai_db);
                $add_qry = "INSERT INTO $table SET
                    quotation_no='" . addslashes($quotation_no) . "',
                    quotation_date='" . addslashes($quotation_date) . "',
                    valid_till=$valid_till_val,
                    customer_id='" . $customer_id . "',
                    customer_name='" . addslashes($customer_name) . "',
                    total_taxable='" . $total_taxable . "',
                    total_amount='" . $total_amount . "',
                    remark='" . $remark . "',
                    created_by='" . $_SESSION['aid'] . "'";
                $ai_db->aiQuery($add_qry);
                $quotation_id = $ai_db->aiLastInsert();
            } else {
                $quotation_id = $id;
                $edit_qry = "UPDATE $table SET
                    quotation_date='" . addslashes($quotation_date) . "',
                    valid_till=$valid_till_val,
                    customer_id='" . $customer_id . "',
                    customer_name='" . addslashes($customer_name) . "',
                    total_taxable='" . $total_taxable . "',
                    total_amount='" . $total_amount . "',
                    remark='" . $remark . "',
                    updated_by='" . $_SESSION['aid'] . "'
                    WHERE id='" . $quotation_id . "'";
                $ai_db->aiQuery($edit_qry);
                $ai_db->aiQuery("DELETE FROM $items_table WHERE quotation_id = $quotation_id");
            }

            // Save Items
            $item_count = count($_POST['item_product_id'] ?? []);
            for ($i = 0; $i < $item_count; $i++) {
                $p_id = intval($_POST['item_product_id'][$i] ?? 0);
                if ($p_id <= 0) continue;

                $p_data = $ai_db->aiGetQuery("SELECT name FROM tbl_product WHERE id=$p_id")[0] ?? null;
                $p_name = addslashes($p_data['name'] ?? '');
                
                $p_desc = addslashes($_POST['item_description'][$i] ?? '');
                $p_qty = floatval($_POST['item_qty'][$i] ?? 0);
                $p_unit = addslashes($_POST['item_unit'][$i] ?? 'nos');
                $p_rate = floatval($_POST['item_rate'][$i] ?? 0);
                $p_taxable = floatval($_POST['item_taxable'][$i] ?? 0);
                $p_amount = floatval($_POST['item_amount'][$i] ?? 0);
                $p_costing_id = intval($_POST['item_costing_id'][$i] ?? 0);

                if ($p_name !== '') {
                    $item_qry = "INSERT INTO $items_table SET
                        quotation_id='$quotation_id',
                        product_id='$p_id',
                        product_name='$p_name',
                        description='$p_desc',
                        qty='$p_qty',
                        unit='$p_unit',
                        rate='$p_rate',
                        taxable_amount='$p_taxable',
                        total_amount='$p_amount',
                        costing_id='$p_costing_id'";
                    $ai_db->aiQuery($item_qry);
                }
            }

            $ai_core->aiGoPage($redirection_url . "?msg=" . ($mode === "add" ? "1" : "2"));
            exit;
        }
    }

    if ($mode === "delete" && $id) {
        $ai_db->aiQuery("UPDATE $table SET is_deleted=1, deleted_by='" . $_SESSION['aid'] . "', deleted_at=NOW() WHERE id='" . intval($id) . "'");
        $ai_core->aiGoPage($redirection_url . "?msg=3");
        exit;
    }

    if ($mode === "edit" && $id && !isset($_POST['btn_submit'])) {
        $data = $ai_db->aiGetQuery("SELECT * FROM $table WHERE id=$id AND is_deleted=0")[0] ?? null;
        if ($data) {
            $data['items'] = $ai_db->aiGetQuery("SELECT * FROM $items_table WHERE quotation_id=$id");
        }
    }

    $all_data = [];
    if (!$mode) {
        $all_data = $ai_db->aiGetQuery("SELECT * FROM $table WHERE is_deleted=0 ORDER BY id DESC");
    }
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">
            <span class="text-muted fw-light">Quotations /</span> <?= $mode ? ucfirst($mode) : 'All Records' ?>
        </h4>
        <?php if (!$mode) { ?>
            <a href="quotations.php?mode=add" class="btn btn-gold btn-sm rounded-pill px-3">
                <i class="bi bi-plus-lg me-1"></i> Add New Quotation
            </a>
        <?php } else { ?>
            <a href="quotations.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        <?php } ?>
    </div>

    <?php if (!$mode) { ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="80">Sr No.</th>
                                <th>Quotation No.</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_data)) {
                                foreach ($all_data as $index => $row) { ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><span class="fw-semibold text-gold"><?= htmlspecialchars($row['quotation_no']) ?></span></td>
                                        <td><?= date('d-m-Y', strtotime($row['quotation_date'])) ?></td>
                                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                        <td>Rs. <?= number_format($row['total_amount'], 2) ?></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="quotations.php?mode=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="Edit" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="quotations.php?mode=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" title="Delete" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;" onclick="return confirm('Are you sure?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <a href="quotation_print.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-circle" title="Print" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php } } else { ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">No records found.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <form class="card border-0 shadow-sm" method="post">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h5 class="fw-bold mb-0"><?= ($mode == 'edit') ? 'Update' : 'Create' ?> Quotation</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Customer (M/S) <span class="text-danger">*</span></label>
                                <select name="customer_id" id="quotation_customer_id" class="form-select select2" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $customer) { ?>
                                        <option value="<?= $customer['id'] ?>" <?= (isset($data['customer_id']) && $data['customer_id'] == $customer['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($customer['contact_name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Quotation No.</label>
                                <input type="text" class="form-control bg-light" value="<?= $data['quotation_no'] ?? quotation_generate_no($ai_db) ?>" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Date</label>
                                <input type="date" name="quotation_date" class="form-control bg-light" value="<?= $data['quotation_date'] ?? date('Y-m-d') ?>" readonly style="pointer-events: none;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Valid Till</label>
                                <input type="date" name="valid_till" class="form-control" value="<?= $data['valid_till'] ?? '' ?>" min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="row g-4 mb-4 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Product <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select id="header_product_id" class="form-select select2">
                                        <option value="">Select Product to Add</option>
                                        <?php foreach ($products as $p) { ?>
                                            <option value="<?= $p['id'] ?>" data-desc="<?= htmlspecialchars($p['description'] ?? '') ?>" data-rate="<?= $p['rate'] ?? 0 ?>">
                                                <?= htmlspecialchars($p['name']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <button type="button" class="btn btn-gold" onclick="addProductFromHeader()">Add</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-borderless align-middle" id="itemsTable">
                                <thead style="border-bottom: 2px solid #eee;">
                                    <tr class="text-muted small text-uppercase">
                                        <th width="50">No.</th>
                                        <th>Item & Description</th>
                                        <th width="100" class="text-center">Qty</th>
                                        <th width="100" class="text-center">Unit</th>
                                        <th width="120" class="text-end">Rate (₹)</th>
                                        <th width="120" class="text-end">Taxable (₹)</th>
                                        <th width="120" class="text-end">Amount (₹)</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Template Row (Hidden) -->
                                    <tr class="item-row border-bottom d-none" id="item-template">
                                        <td class="row-no text-muted">0</td>
                                        <td>
                                            <input type="hidden" name="item_product_id[]" class="item-product-select" value="0" disabled>
                                            <textarea name="item_description[]" class="form-control form-control-sm border-0 bg-transparent fw-bold" rows="1" placeholder="Item Name" style="resize: none;" readonly disabled></textarea>
                                            <input type="hidden" name="item_costing_id[]" class="item-costing-id" value="0" disabled>
                                        </td>
                                        <td><input type="number" step="0.01" name="item_qty[]" class="form-control item-qty text-center" value="1" oninput="calculateRow(this)" disabled></td>
                                        <td><input type="text" name="item_unit[]" class="form-control item-unit text-center" value="nos" disabled></td>
                                        <td><input type="number" step="0.01" name="item_rate[]" class="form-control item-rate text-end fw-bold" value="0" oninput="calculateRow(this)" disabled></td>
                                        <td><input type="number" step="0.01" name="item_taxable[]" class="form-control item-taxable border-0 bg-transparent text-end" value="0" readonly disabled></td>
                                        <td><input type="number" step="0.01" name="item_amount[]" class="form-control item-amount border-0 bg-transparent text-end fw-bold text-gold" value="0" readonly disabled></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-link text-danger p-0" onclick="removeRow(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <?php 
                                    $items = $data['items'] ?? []; 
                                    if (!empty($items)) {
                                        foreach ($items as $idx => $item) { ?>
                                            <tr class="item-row border-bottom">
                                                <td class="row-no text-muted"><?= $idx + 1 ?></td>
                                                <td>
                                                    <input type="hidden" name="item_product_id[]" class="item-product-select" value="<?= $item['product_id'] ?? 0 ?>">
                                                    <textarea name="item_description[]" class="form-control form-control-sm border-0 bg-transparent fw-bold" rows="1" placeholder="Item Name" style="resize: none;" readonly><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                                                    <input type="hidden" name="item_costing_id[]" class="item-costing-id" value="<?= $item['costing_id'] ?? 0 ?>">
                                                </td>
                                                <td><input type="number" step="0.01" name="item_qty[]" class="form-control item-qty text-center" value="<?= $item['qty'] ?? 1 ?>" oninput="calculateRow(this)"></td>
                                                <td><input type="text" name="item_unit[]" class="form-control item-unit text-center" value="<?= $item['unit'] ?? 'nos' ?>"></td>
                                                <td><input type="number" step="0.01" name="item_rate[]" class="form-control item-rate text-end fw-bold" value="<?= $item['rate'] ?? 0 ?>" oninput="calculateRow(this)"></td>
                                                <td><input type="number" step="0.01" name="item_taxable[]" class="form-control item-taxable border-0 bg-transparent text-end" value="<?= $item['taxable_amount'] ?? 0 ?>" readonly></td>
                                                <td><input type="number" step="0.01" name="item_amount[]" class="form-control item-amount border-0 bg-transparent text-end fw-bold text-gold" value="<?= $item['total_amount'] ?? 0 ?>" readonly></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-link text-danger p-0" onclick="removeRow(this)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } 
                                    } ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Quotation Summary Section -->
                        <div class="row mt-4 mb-4 g-0 border rounded-3 overflow-hidden shadow-sm">
                            <div class="col-md-7 p-4 bg-light-subtle">
                                <label class="form-label fw-bold mb-2 small text-uppercase text-muted" style="letter-spacing: 0.5px;">Total Quotation Amount in Words</label>
                                <div class="fw-bold text-dark h6 mb-0" id="amount_in_words">Zero Rupees Only</div>
                            </div>
                            <div class="col-md-5 p-4 bg-white border-start">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted small fw-bold text-uppercase">Total Taxable Value</span>
                                    <span class="fw-bold text-dark" id="summary_taxable"><?= number_format($data['total_taxable'] ?? 0, 2) ?></span>
                                    <input type="hidden" name="total_taxable" id="total_taxable" value="<?= $data['total_taxable'] ?? 0 ?>">
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-3 border-top border-2">
                                    <span class="fw-bold text-dark h5 mb-0">Grand Total (₹)</span>
                                    <span class="fw-bold h2 mb-0 text-gold" id="summary_amount"><?= number_format($data['total_amount'] ?? 0, 2) ?></span>
                                    <input type="hidden" name="total_amount" id="total_amount" value="<?= $data['total_amount'] ?? 0 ?>">
                                </div>
                            </div>
                        </div>



                        <div class="d-flex gap-2">
                            <button type="submit" name="btn_submit" class="btn btn-gold px-5 rounded-pill">Save Quotation</button>
                            <a href="quotations.php" class="btn btn-outline-secondary px-5 rounded-pill">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<?php } ?>
</div>

<!-- Costing Picker Modal -->
<div class="modal fade" id="costingPickerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pick from Costing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Est No.</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Rate</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($costings as $c) { ?>
                                <tr>
                                    <td><?= $c['estimate_no'] ?></td>
                                    <td><?= $c['customer_name'] ?></td>
                                    <td><?= $c['product_name'] ?></td>
                                    <td><?= $c['sale_rate'] ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-gold rounded-pill" onclick="pickCosting(<?= $c['id'] ?>)">Select</button>
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

<script>
let targetRow = null;
const quotationMode = "<?= htmlspecialchars($mode) ?>";

function setTargetRow(btn) {
    targetRow = btn.closest('.item-row');
}

function productChanged(select) {
    const row = select.closest('.item-row');
    const option = select.options[select.selectedIndex];
    const descTextarea = row.querySelector('textarea');
    
    if (select.value && option.dataset.desc) {
        descTextarea.value = option.dataset.desc;
    }
}

function pickCosting(costingId) {
    fetch('ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_costing_data&costing_id=' + costingId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const costing = data.data;
            targetRow.querySelector('.item-rate').value = costing.sale_rate;
            targetRow.querySelector('.item-costing-id').value = costing.id;
            
            // Set product dropdown
            const select = targetRow.querySelector('.item-product-select');
            select.value = costing.product_id;
            
            // Description formatting
            let desc = `Size: ${costing.sheet_length}x${costing.sheet_width}x${costing.sheet_height} ${costing.sheet_unit}`;
            targetRow.querySelector('textarea').value = desc;
            
            calculateRow(targetRow.querySelector('.item-qty'));
            bootstrap.Modal.getInstance(document.getElementById('costingPickerModal')).hide();
        }
    });
}

function addProductFromHeader() {
    const select = document.getElementById('header_product_id');
    const customerSelect = document.getElementById('quotation_customer_id');
    const productId = select.value;
    if (!productId) {
        alert('Please select a product first');
        return;
    }
    if (!customerSelect || !customerSelect.value) {
        alert('Please select customer first');
        return;
    }

    const customerId = customerSelect.value;
    const productName = select.options[select.selectedIndex].text;
    const productRate = parseFloat(select.options[select.selectedIndex].getAttribute('data-rate') || '0');

    fetchLastQuotationPrefill(customerId, productId)
        .then(function (prefill) {
            const row = addRow();
            if (!row) return;

            row.querySelector('.item-product-select').value = productId;
            row.querySelector('textarea[name="item_description[]"]').value = productName;
            row.querySelector('.item-costing-id').value = 0;

            if (prefill && prefill.item) {
                const quotationData = prefill.quotation || {};
                const item = prefill.item || {};

                row.querySelector('.item-qty').value = parseFloat(item.qty || 0) > 0 ? parseFloat(item.qty) : 1;
                row.querySelector('.item-unit').value = String(item.unit || 'nos');
                row.querySelector('.item-rate').value = parseFloat(item.rate || 0) > 0 ? parseFloat(item.rate) : productRate;
                row.querySelector('.item-costing-id').value = parseInt(item.costing_id || 0, 10) || 0;
                row.querySelector('textarea[name="item_description[]"]').value = String(item.description || productName || '');

                const validTillField = document.querySelector('input[name="valid_till"]');
                if (validTillField) {
                    validTillField.value = quotationData.valid_till || '';
                }

                const remarkField = document.querySelector('textarea[name="remark"]');
                if (remarkField) {
                    remarkField.value = quotationData.remark || '';
                }

                if (typeof showToast === 'function') {
                    showToast('Auto Fill', 'Last matching quotation details loaded.', 'success');
                }
            } else {
                row.querySelector('.item-qty').value = 1;
                row.querySelector('.item-unit').value = 'nos';
                row.querySelector('.item-rate').value = productRate;
            }

            calculateRow(row.querySelector('.item-qty'));
            if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
                jQuery(select).val('').trigger('change');
            } else {
                select.value = '';
            }
        })
        .catch(function () {
            const row = addRow();
            if (!row) return;

            row.querySelector('.item-product-select').value = productId;
            row.querySelector('textarea[name="item_description[]"]').value = productName;
            row.querySelector('.item-qty').value = 1;
            row.querySelector('.item-unit').value = 'nos';
            row.querySelector('.item-rate').value = productRate;
            row.querySelector('.item-costing-id').value = 0;
            calculateRow(row.querySelector('.item-qty'));

            if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
                jQuery(select).val('').trigger('change');
            } else {
                select.value = '';
            }
        });
}

function fetchLastQuotationPrefill(customerId, productId) {
    const formData = new FormData();
    formData.append('action', 'get_last_quotation_prefill');
    formData.append('customer_id', customerId);
    formData.append('product_id', productId);

    return fetch('ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(rawText => {
        let data = null;
        try {
            data = JSON.parse(rawText);
        } catch (err) {
            const jsonStart = rawText.indexOf('{');
            if (jsonStart >= 0) {
                data = JSON.parse(rawText.slice(jsonStart));
            }
        }

        if (!data || !data.success) {
            return null;
        }
        return {
            quotation: data.quotation || {},
            item: data.item || {}
        };
    })
    .catch(function () {
        return null;
    });
}

function addRow() {
    const template = document.getElementById('item-template');
    const newRow = template.cloneNode(true);
    
    newRow.id = '';
    newRow.classList.remove('d-none');
    newRow.querySelectorAll('input, textarea, select').forEach(el => {
        el.disabled = false;
    });
    
    document.querySelector('#itemsTable tbody').appendChild(newRow);
    updateRowNumbers();
    return newRow;
}

function removeRow(btn) {
    const tbody = document.querySelector('#itemsTable tbody');
    if (tbody.querySelectorAll('.item-row').length > 1) {
        btn.closest('.item-row').remove();
        updateRowNumbers();
        calculateGrandTotal();
    }
}

function updateRowNumbers() {
    let visibleRows = document.querySelectorAll('.item-row:not(.d-none)');
    visibleRows.forEach((row, i) => {
        row.querySelector('.row-no').innerText = i + 1;
    });
}

function calculateRow(input) {
    const row = input.closest('.item-row');
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const rate = parseFloat(row.querySelector('.item-rate').value) || 0;
    const taxable = qty * rate;
    const amount = taxable * 1.05; // Adding 5% GST
    
    row.querySelector('.item-taxable').value = taxable.toFixed(2);
    row.querySelector('.item-amount').value = amount.toFixed(2);
    
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let grandTaxable = 0;
    let grandTotal = 0;
    
    document.querySelectorAll('.item-row').forEach(row => {
        grandTaxable += parseFloat(row.querySelector('.item-taxable').value) || 0;
        grandTotal += parseFloat(row.querySelector('.item-amount').value) || 0;
    });
    
    document.getElementById('total_taxable').value = grandTaxable.toFixed(2);
    document.getElementById('total_amount').value = grandTotal.toFixed(2);
    
    document.getElementById('summary_taxable').innerText = grandTaxable.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('summary_amount').innerText = grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
    
    document.getElementById('amount_in_words').innerText = numberToWords(grandTotal);
}

function numberToWords(number) {
    const fraction = Math.round((number % 1) * 100);
    let fullNumber = Math.floor(number);
    
    const firstPart = convertToWords(fullNumber) + " Rupees";
    const secondPart = fraction > 0 ? " and " + convertToWords(fraction) + " Paise" : "";
    
    return (firstPart + secondPart + " Only").toUpperCase();
}

function convertToWords(number) {
    const units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    
    if (number == 0) return 'Zero';
    
    let words = '';
    
    if (Math.floor(number / 10000000) > 0) {
        words += convertToWords(Math.floor(number / 10000000)) + ' Crore ';
        number %= 10000000;
    }
    
    if (Math.floor(number / 100000) > 0) {
        words += convertToWords(Math.floor(number / 100000)) + ' Lakh ';
        number %= 100000;
    }
    
    if (Math.floor(number / 1000) > 0) {
        words += convertToWords(Math.floor(number / 1000)) + ' Thousand ';
        number %= 1000;
    }
    
    if (Math.floor(number / 100) > 0) {
        words += convertToWords(Math.floor(number / 100)) + ' Hundred ';
        number %= 100;
    }
    
    if (number > 0) {
        if (words !== '') words += 'and ';
        if (number < 20) words += units[number];
        else {
            words += tens[Math.floor(number / 10)];
            if (number % 10 > 0) words += '-' + units[number % 10];
        }
    }
    
    return words.trim();
}

// Initial calculation on page load if data exists
window.addEventListener('load', () => {
    calculateGrandTotal();
    
    // Form validation
    const form = document.querySelector('form');
    if(form) {
        form.addEventListener('submit', function(e) {
            // Customer check
            const customerId = document.querySelector('select[name="customer_id"]').value;
            if(!customerId) {
                e.preventDefault();
                alert('Please select a customer.');
                return;
            }

            // Product grid check
            const productRows = document.querySelectorAll('#itemsTable tbody tr.item-row:not(.d-none)');
            if (productRows.length === 0) {
                e.preventDefault();
                alert('Please add at least one product to the quotation.');
            }
        });
    }
});
</script>

<?php include 'include/footer.php'; ?>

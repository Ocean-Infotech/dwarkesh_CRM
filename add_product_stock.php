<?php
$pageTitle = "Add Stock";
$currentPage = "add_product_stock";
$headerTitle = "Manage Stock Entry";

include 'include/header.php';

$table = "tbl_stock_history";
$redirection_url = "add_product_stock.php";

$mode = $_REQUEST['mode'] ?? '';
$error = '';
$msg = $_GET['msg'] ?? '';

// Handle Stock Adjustment
if (isset($_POST['btn_submit'])) {
    $item_type = $_POST['item_type'] ?? '';
    $item_id = intval($_POST['item_id'] ?? 0);
    $qty = floatval($_POST['qty'] ?? 0);
    $action_type = $_POST['action_type'] ?? '';
    $remarks = addslashes($_POST['remarks'] ?? '');

    if (!$item_id || $qty <= 0 || !$action_type || !$item_type) {
        $error = "All fields are required and quantity must be greater than zero.";
    }

    // Check for negative stock if action is 'minus'
    if (empty($error) && $action_type == 'minus') {
        $main_table = ($item_type == 'product') ? 'tbl_product' : 'tbl_materials';
        $current_data = $ai_db->aiGetQuery("SELECT stock_qty FROM $main_table WHERE id = $item_id");
        $current_stock = floatval($current_data[0]['stock_qty'] ?? 0);
        if ($qty > $current_stock) {
            $error = "Insufficient stock. Only " . number_format($current_stock, 2) . " " . ($item_type == 'product' ? 'PCS' : 'KG') . " available.";
        }
    }

    if (empty($error)) {
        // Start Transaction (if supported, otherwise manual updates)
        $ai_db->aiQuery("INSERT INTO $table SET 
            item_type='$item_type',
            item_id='$item_id',
            qty='$qty',
            action_type='$action_type',
            remarks='$remarks',
            created_by='" . $_SESSION['aid'] . "'");

        // Update main table stock
        $main_table = ($item_type == 'product') ? 'tbl_product' : 'tbl_materials';
        $operator = ($action_type == 'plus') ? '+' : '-';

        $ai_db->aiQuery("UPDATE $main_table SET 
            stock_qty = stock_qty $operator $qty,
            updated_at = NOW(),
            updated_by = '" . $_SESSION['aid'] . "' 
            WHERE id = $item_id");

        // BOM Deduction Logic: If product stock is decreased, decrease BOM materials stock too
        if ($item_type == 'product' && $action_type == 'minus') {
            $bom_items = $ai_db->aiGetQuery("SELECT material_name, qty FROM tbl_product_bom WHERE product_id = $item_id AND is_deleted = 0");
            if (!empty($bom_items)) {
                foreach ($bom_items as $bom) {
                    $mat_name = addslashes($bom['material_name']);
                    $bom_qty = floatval($bom['qty']);
                    $total_mat_deduct = $bom_qty * $qty;

                    if ($total_mat_deduct > 0) {
                        // Find the material ID to record in history correctly
                        $mat_info = $ai_db->aiGetQuery("SELECT id FROM tbl_materials WHERE name = '$mat_name' AND is_deleted = 0 LIMIT 1");
                        if (!empty($mat_info)) {
                            $target_mat_id = intval($mat_info[0]['id']);

                            // Deduct from materials table
                            $ai_db->aiQuery("UPDATE tbl_materials SET 
                                stock_qty = stock_qty - $total_mat_deduct,
                                updated_at = NOW(),
                                updated_by = '" . $_SESSION['aid'] . "'
                                WHERE id = $target_mat_id");

                            // Record in history for materials
                            $ai_db->aiQuery("INSERT INTO tbl_stock_history SET 
                                item_type='material',
                                item_id='$target_mat_id',
                                qty='$total_mat_deduct',
                                action_type='minus',
                                remarks='Auto-deducted from Product BOM (PID: $item_id)',
                                created_by='" . $_SESSION['aid'] . "'");
                        }
                    }
                }
            }
        }

        $ai_core->aiGoPage($redirection_url . "?msg=1");
        exit;
    }
}

// Fetch Items for Dropdown
$products = $ai_db->aiGetQuery("SELECT id, name, stock_qty FROM tbl_product WHERE is_deleted=0 AND status='active' ORDER BY name ASC");
$materials = $ai_db->aiGetQuery("SELECT id, name, stock_qty FROM tbl_materials WHERE is_deleted=0 AND status='active' ORDER BY name ASC");

// Fetch History
$limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$history_count = $ai_db->aiGetQuery("SELECT COUNT(*) as total FROM $table");
$totalRecords = $history_count[0]['total'] ?? 0;
$totalPages = ceil($totalRecords / $limit);

$history = $ai_db->aiGetQuery("SELECT h.*, 
    CASE WHEN h.item_type = 'product' THEN p.name ELSE m.name END as item_name
    FROM $table h
    LEFT JOIN tbl_product p ON h.item_type = 'product' AND h.item_id = p.id
    LEFT JOIN tbl_materials m ON h.item_type = 'material' AND h.item_id = m.id
    ORDER BY h.id DESC 
    LIMIT $limit OFFSET $offset");
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Stock Adjustment Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="fw-bold mb-0">Add/Adjust Stock</h5>
                </div>
                <form class="card-body" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Stock Type</label>
                        <select name="item_type" id="item_type" class="form-select" required data-searchable="false">
                            <option value="product" selected>Product</option>
                            <option value="material">Materials</option>
                        </select>
                    </div>

                    <div class="mb-3" id="product_selector">
                        <label class="form-label fw-bold">Select Product</label>
                        <select id="select_product" class="form-select select2">
                            <option value="">Select Product</option>
                            <?php foreach ($products as $p) { ?>
                                <option value="<?= $p['id'] ?>" data-stock="<?= $p['stock_qty'] ?>">
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3" id="material_selector" style="display: none;">
                        <label class="form-label fw-bold">Select Material</label>
                        <select id="select_material" class="form-select select2">
                            <option value="">Select Material</option>
                            <?php foreach ($materials as $m) { ?>
                                <option value="<?= $m['id'] ?>" data-stock="<?= $m['stock_qty'] ?>">
                                    <?= htmlspecialchars($m['name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Hidden input to hold the final item_id -->
                    <input type="hidden" name="item_id" id="final_item_id" value="" required>

                    <!-- Live Stock Display -->
                    <div id="current_stock_display" class="mb-3 d-none">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <div class="text-muted small text-uppercase fw-bold mb-1">Available Stock</div>
                            <div class="h4 mb-0 fw-900 text-primary">
                                <span id="stock_val">0</span>
                                <small class="text-muted fs-6" id="stock_unit">PCS</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Action</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action_type" value="plus" checked
                                    id="plus">
                                <label class="form-check-label" for="plus">Stock In (+)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action_type" value="minus"
                                    id="minus">
                                <label class="form-check-label" for="minus">Stock Out (-)</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantity</label>
                        <input type="number" step="0.01" name="qty" class="form-control" placeholder="0.00" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"
                            placeholder="Reason for adjustment"></textarea>
                    </div>

                    <button type="submit" name="btn_submit" class="btn btn-gold w-100 fw-bold py-2 rounded-pill">
                        Update Stock
                    </button>
                </form>
            </div>
        </div>

        <!-- History Table -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div
                    class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Stock Entry History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Item Name</th>
                                    <th class="text-center">Action</th>
                                    <th class="text-end">Qty</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($history)) {
                                    foreach ($history as $h) { ?>
                                        <tr class="history-row" data-type="<?= $h['item_type'] ?>">
                                            <td>
                                                <?= date('d-m-Y H:i', strtotime($h['created_at'])) ?>
                                            </td>
                                            <td><span class="badge bg-secondary rounded-pill">
                                                    <?= ucfirst($h['item_type']) ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold">
                                                <?= htmlspecialchars($h['item_name']) ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($h['action_type'] == 'plus') { ?>
                                                    <span class="text-success fw-bold"><i class="bi bi-arrow-up"></i> IN</span>
                                                <?php } else { ?>
                                                    <span class="text-danger fw-bold"><i class="bi bi-arrow-down"></i> OUT</span>
                                                <?php } ?>
                                            </td>
                                            <td class="text-end fw-bold">
                                                <?= number_format($h['qty'], 2) ?>
                                                <small class="text-muted">
                                                    <?= ($h['item_type'] == 'product' ? 'PCS' : 'KG') ?>
                                                </small>
                                            </td>
                                            <td class="small">
                                                <?= htmlspecialchars($h['remarks']) ?>
                                            </td>
                                        </tr>
                                    <?php }
                                } else { ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">No history found.</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1) { ?>
                        <nav class="mt-4">
                            <ul class="pagination pagination-sm justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </nav>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraFooter = '
<script>
    $(document).ready(function () {
        const $itemType = $("#item_type");
        const $prodSelDiv = $("#product_selector");
        const $matSelDiv = $("#material_selector");
        const $selectProd = $("#select_product");
        const $selectMat = $("#select_material");
        const $finalId = $("#final_item_id");
        const $stockDisp = $("#current_stock_display");
        const $stockVal = $("#stock_val");
        const $stockUnit = $("#stock_unit");

        function updateStockDisplay(select) {
            const $option = $(select).find("option:selected");
            const stock = $option.data("stock");
            const type = $itemType.val();
            const val = $(select).val();

            if (val && stock !== undefined) {
                $finalId.val(val);
                $stockVal.text(parseFloat(stock).toFixed(2));
                $stockUnit.text(type === "product" ? "PCS" : "KG");
                $stockDisp.removeClass("d-none").hide().fadeIn();
            } else {
                $finalId.val("");
                $stockDisp.addClass("d-none");
            }
        }

        function toggleSelectors() {
            const type = $itemType.val();

            // Reset state
            $stockDisp.addClass("d-none");
            $finalId.val("");

            // Filter History Table
            $(".history-row").hide();
            $(`.history-row[data-type="${type}"]`).fadeIn();

            if (type === "product") {
                $prodSelDiv.show();
                $matSelDiv.hide();
                $selectMat.val(null).trigger("change");
                updateStockDisplay($selectProd);
            } else {
                $prodSelDiv.hide();
                $matSelDiv.show();
                $selectProd.val(null).trigger("change");
                updateStockDisplay($selectMat);
            }
        }

        $itemType.on("change", function () {
            toggleSelectors();
        });

        $selectProd.on("change", function () {
            if ($itemType.val() === "product") {
                updateStockDisplay(this);
            }
        });

        $selectMat.on("change", function () {
            if ($itemType.val() === "material") {
                updateStockDisplay(this);
            }
        });

        // Initial call
        toggleSelectors();
    });
</script>';
?>

<?php include 'include/footer.php'; ?>
<?php
$pageTitle = "Product Stock";
$currentPage = "product_stock";
$headerTitle = "Current Available Stock";

include 'include/header.php';

// Fetch Products with Stock
$products = $ai_db->aiGetQuery("SELECT p.*, m.name as material_name 
    FROM tbl_product p 
    LEFT JOIN tbl_materials m ON p.mapped_material_id = m.id
    WHERE p.is_deleted=0 ORDER BY p.stock_qty DESC");

// Fetch Materials with Stock
$materials = $ai_db->aiGetQuery("SELECT * FROM tbl_materials WHERE is_deleted=0 ORDER BY stock_qty DESC");
?>

<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Stock Inventory Management</h5>
                <ul class="nav nav-pills card-header-pills" id="stockTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill px-4" id="products-tab" data-bs-toggle="tab"
                            data-bs-target="#products" type="button" role="tab">Products</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill px-4" id="materials-tab" data-bs-toggle="tab"
                            data-bs-target="#materials" type="button" role="tab">Materials</button>
                    </li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="tab-content" id="stockTabsContent">
                <!-- Products Tab -->
                <div class="tab-pane fade show active" id="products" role="tabpanel" aria-labelledby="products-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product Name</th>
                                    <!-- <th>Mapped Material</th>
                                    <th class="text-center">Usage/Unit</th> -->
                                    <th class="text-end">Current Stock</th>
                                    <th class="text-end">Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p) { ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                                            <div class="small text-muted">HSN:
                                                <?= htmlspecialchars($p['hsn_code'] ?: '-') ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span
                                                class="fs-5 fw-900 <?= ($p['stock_qty'] <= 5 ? 'text-danger' : 'text-success') ?>">
                                                <?= number_format($p['stock_qty'], 0) ?>
                                            </span>
                                            <small class="text-muted">PCS</small>
                                        </td>
                                        <td class="text-end small text-muted">
                                            <?= $p['updated_at'] ? date('d-m-Y H:i', strtotime($p['updated_at'])) : '-' ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Materials Tab -->
                <div class="tab-pane fade" id="materials" role="tabpanel" aria-labelledby="materials-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Material Name</th>
                                    <th class="text-end">Current Stock</th>
                                    <th class="text-end">Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materials as $m) { ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($m['name']) ?></td>
                                        <td class="text-end">
                                            <span
                                                class="fs-5 fw-900 <?= ($m['stock_qty'] <= 10 ? 'text-danger' : 'text-primary') ?>">
                                                <?= number_format($m['stock_qty'], 2) ?>
                                            </span>
                                            <small class="text-muted">KG</small>
                                        </td>
                                        <td class="text-end small text-muted">
                                            <?= $m['updated_at'] ? date('d-m-Y H:i', strtotime($m['updated_at'])) : '-' ?>
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
</div>

<?php include 'include/footer.php'; ?>
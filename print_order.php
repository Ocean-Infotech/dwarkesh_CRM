<?php
require_once 'root/config.php';
require_once 'root/schema_bootstrap.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("Invalid Order ID");
}

// Fetch order with customer names for delivery fields
$sql = "SELECT o.*, 
        c1.contact_name as liner_delivery_name,
        c2.contact_name as duplex_delivery_name,
        c3.contact_name as printing_by_name,
        c4.contact_name as print_delivery_name,
        c5.contact_name as laminas_delivery_name,
        p.default_length as product_default_length,
        p.default_width as product_default_width,
        p.default_height as product_default_height
        FROM tbl_orders o
        LEFT JOIN tbl_customer c1 ON o.liner_delivery_id = c1.id
        LEFT JOIN tbl_customer c2 ON o.duplex_delivery_id = c2.id
        LEFT JOIN tbl_customer c3 ON o.printing_by_id = c3.id
        LEFT JOIN tbl_customer c4 ON o.print_delivery_id = c4.id
        LEFT JOIN tbl_customer c5 ON o.laminas_delivery_id = c5.id
        LEFT JOIN tbl_product p ON o.product_id = p.id
        WHERE o.id = $id AND o.is_deleted = 0";

$orderRes = $ai_db->aiGetQuery($sql);
if (empty($orderRes)) {
    die("Order not found");
}
$order = $orderRes[0];

$printProductSizeParts = [];
if (!empty($order['product_default_length']) && floatval($order['product_default_length']) > 0) {
    $printProductSizeParts[] = rtrim(rtrim(number_format((float) $order['product_default_length'], 2, '.', ''), '0'), '.');
}
if (!empty($order['product_default_width']) && floatval($order['product_default_width']) > 0) {
    $printProductSizeParts[] = rtrim(rtrim(number_format((float) $order['product_default_width'], 2, '.', ''), '0'), '.');
}
if (!empty($order['product_default_height']) && floatval($order['product_default_height']) > 0) {
    $printProductSizeParts[] = rtrim(rtrim(number_format((float) $order['product_default_height'], 2, '.', ''), '0'), '.');
}
$printProductSize = !empty($printProductSizeParts) ? implode(' x ', $printProductSizeParts) . ' inch' : '---';
$printSheetLength = (isset($order['sheet_length']) && $order['sheet_length'] !== '' && floatval($order['sheet_length']) > 0)
    ? rtrim(rtrim(number_format((float) $order['sheet_length'], 2, '.', ''), '0'), '.')
    : '---';
$printSheetWidth = (isset($order['sheet_width']) && $order['sheet_width'] !== '' && floatval($order['sheet_width']) > 0)
    ? rtrim(rtrim(number_format((float) $order['sheet_width'], 2, '.', ''), '0'), '.')
    : '---';

// Fetch items
$items = $ai_db->aiGetQuery("SELECT * FROM tbl_orders_item WHERE order_id = $id ORDER BY id ASC");
$liner_items = [];
$duplex_items = [];
foreach ($items as $item) {
    if ($item['item_group'] == 'duplex') {
        $duplex_items[] = $item;
    } else {
        $liner_items[] = $item;
    }
}

// Logo path
$logoPath = 'assets/logo/logo.png';
if (!file_exists($logoPath)) {
    $logoPath = 'https://ui-avatars.com/api/?name=Dwarkesh+Packaging&background=c5a059&color=fff&size=128';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Sheet - #<?= htmlspecialchars($order['order_no']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/print_layout.css">
</head>
<body>

    <div class="no-print-zone text-center">
        <button onclick="window.print()" class="btn btn-dark rounded-pill px-4 me-2">
            <i class="bi bi-printer-fill me-2"></i>Print Job Sheet
        </button>
        <a href="orders.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>Back to Orders
        </a>
    </div>

    <div class="print-wrapper">
        <div class="header-box">
            <div class="company-info">
                <h1>Dwarkesh Packaging</h1>
                <p>CORRUGATED BOXES & OFFSET PRINTING SPECIALISTS</p>
                <p>Ph: +91 98765 43210 | Email: info@dwarkeshpackaging.com</p>
                <p>Website: www.dwarkeshpackaging.com</p>
            </div>
            <div class="logo-box">
                <img src="<?= $logoPath ?>" alt="Logo">
            </div>
        </div>

        <div class="sheet-title">
            <h2>JOB ORDER SHEET</h2>
        </div>

        <table class="info-grid">
            <tr>
                <td class="label-cell">Order Number</td>
                <td class="value-cell" style="font-size: 14px; color: var(--primary-gold);">#<?= htmlspecialchars($order['order_no']) ?></td>
                <td class="label-cell">Order Date</td>
                <td class="value-cell"><?= date('d-M-Y', strtotime($order['order_date'])) ?></td>
            </tr>
            <tr>
                <td class="label-cell">Customer Name</td>
                <td class="value-cell" colspan="3"><?= htmlspecialchars($order['customer_name']) ?></td>
            </tr>
            <tr>
                <td class="label-cell">Brand Name</td>
                <td class="value-cell"><?= htmlspecialchars($order['brand_name']) ?></td>
                <td class="label-cell">Product / Box</td>
                <td class="value-cell"><?= htmlspecialchars($order['product_name']) ?></td>
            </tr>
            <tr>
                <td class="label-cell">Total Quantity</td>
                <td class="value-cell"><?= number_format($order['box_qty']) ?> <?= htmlspecialchars($order['box_qty_unit']) ?></td>
                <td class="label-cell">Product Size</td>
                <td class="value-cell"><?= htmlspecialchars($printProductSize) ?></td>
            </tr>
            <tr>
                <td class="label-cell">Sheet Size</td>
                <td class="value-cell">
                    Sheet Length (Decal): <?= htmlspecialchars($printSheetLength) ?><br>
                    Sheet Width (Cutting): <?= htmlspecialchars($printSheetWidth) ?>
                </td>
                <td class="label-cell">Upps Count</td>
                <td class="value-cell"><?= htmlspecialchars($order['upps']) ?> Upps</td>
            </tr>
        </table>

        <div class="row g-0 dual-detail-row">
            <div class="col-6">
                <div class="section-header"><i class="bi bi-layers-fill"></i> Liner Details</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th width="60" class="d-none">Rate</th>
                            <th width="60">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($liner_items as $li) { ?>
                            <tr>
                                <td><?= htmlspecialchars($li['material_name'] ?: $li['name']) ?></td>
                                <td class="d-none"><?= number_format($li['rate'], 2) ?></td>
                                <td><?= number_format($li['qty'] ?: $li['pcs'], 1) ?></td>
                            </tr>
                        <?php } ?>
                        <?php for($i=count($liner_items); $i<2; $i++) echo '<tr><td>&nbsp;</td><td></td><td></td></tr>'; ?>
                    </tbody>
                </table>
                <div class="mb-3 px-2">
                    <div style="font-size: 9px; font-weight: 700; color: #666;">DELIVERY TO:</div>
                    <div style="font-weight: 700;"><?= htmlspecialchars($order['liner_delivery_name'] ?: '---') ?></div>
                    <div style="font-size: 10px;"><?= htmlspecialchars($order['liner_delivery_phone']) ?></div>
                    <div class="mt-1"><span class="badge-status">Top Count: <?= htmlspecialchars($order['top_count']) ?></span></div>
                </div>
            </div>
            <div class="col-6">
                <div class="section-header"><i class="bi bi-box-seam-fill"></i> Duplex Details</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th width="60" class="d-none">Rate</th>
                            <th width="60">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($duplex_items as $di) { ?>
                            <tr>
                                <td><?= htmlspecialchars($di['material_name'] ?: $di['name']) ?></td>
                                <td class="d-none"><?= number_format($di['rate'], 2) ?></td>
                                <td><?= number_format($di['qty'] ?: $di['pcs'], 1) ?></td>
                            </tr>
                        <?php } ?>
                        <?php for($i=count($duplex_items); $i<2; $i++) echo '<tr><td>&nbsp;</td><td></td><td></td></tr>'; ?>
                    </tbody>
                </table>
                <div class="mb-3 px-2">
                    <div style="font-size: 9px; font-weight: 700; color: #666;">DELIVERY TO:</div>
                    <div style="font-weight: 700;"><?= htmlspecialchars($order['duplex_delivery_name'] ?: '---') ?></div>
                    <div style="font-size: 10px;"><?= htmlspecialchars($order['duplex_delivery_phone']) ?></div>
                </div>
            </div>
        </div>

        <div class="section-header"><i class="bi bi-printer-fill"></i> Production & Finishing Details</div>
        <table class="info-grid" style="border-top: none;">
            <tr>
                <td class="label-cell">Printing By</td>
                <td class="value-cell"><?= htmlspecialchars($order['printing_by_name'] ?: '---') ?></td>
                <td class="label-cell">Print Color</td>
                <td class="value-cell"><?= htmlspecialchars($order['print_color'] ?: '---') ?></td>
            </tr>
            <tr>
                <td class="label-cell">Print Quantity</td>
                <td class="value-cell"><?= htmlspecialchars($order['print_qty'] ?: '---') ?></td>
                <td class="label-cell">Lamination</td>
                <td class="value-cell"><?= htmlspecialchars($order['lamination_type'] ?: '---') ?> <?= $order['lamination_extra'] ? '('.$order['lamination_extra'].')' : '' ?></td>
            </tr>
            <tr>
                <td class="label-cell">Die Maker</td>
                <td class="value-cell"><?= htmlspecialchars($order['die_maker'] ?: '---') ?></td>
                <td class="label-cell">Die / C-Die Code</td>
                <td class="value-cell"><?= htmlspecialchars($order['die_code'] ?: '---') ?> / <?= htmlspecialchars($order['c_die_code'] ?: '---') ?></td>
            </tr>
            <tr>
                <td class="label-cell">Designer</td>
                <td class="value-cell"><?= htmlspecialchars($order['designer'] ?: '---') ?></td>
                <td class="label-cell">Plate Name</td>
                <td class="value-cell"><?= htmlspecialchars($order['plate'] ?: '---') ?></td>
            </tr>
        </table>

        <div class="section-header"><i class="bi bi-ui-checks"></i> Jobsheet Checklist</div>
        <div class="check-grid">
            <div class="check-item"><div class="box <?= $order['job_pesting'] ? 'active' : '' ?>"></div> Pesting</div>
            <div class="check-item"><div class="box <?= $order['job_pin'] ? 'active' : '' ?>"></div> Pin</div>
            <div class="check-item"><div class="box <?= $order['job_punching'] ? 'active' : '' ?>"></div> Punching</div>
            <div class="check-item"><div class="box <?= $order['job_side_pesting'] ? 'active' : '' ?>"></div> Side Pesting</div>
            <div class="check-item"><div class="box <?= $order['half_film'] ? 'active' : '' ?>"></div> Half Film</div>
            <div class="check-item"><div class="box <?= $order['full_film'] ? 'active' : '' ?>"></div> Full Film</div>
            <div class="check-item"><div class="box <?= $order['plate_status'] == 'Yes' ? 'active' : '' ?>"></div> Plate Ready</div>
            <div class="check-item"><div class="box <?= $order['print_status'] == 'Yes' ? 'active' : '' ?>"></div> Print Ready</div>
        </div>

        <div class="section-header"><i class="bi bi-chat-left-text-fill"></i> Additional Remarks & Billing</div>
        <table class="info-grid" style="border-top: none;">
            <tr>
                <td class="label-cell">Remark</td>
                <td class="value-cell" colspan="3" style="min-height: 40px;"><?= nl2br(htmlspecialchars($order['bill_remark'] ?: 'No additional remarks.')) ?></td>
            </tr>
        </table>

        <div class="footer-section">
            <div class="sign-placeholder">
                <div class="sign-line"></div>
                <div class="sign-text">Authorized By</div>
            </div>
            <div class="sign-placeholder">
                <div class="sign-line"></div>
                <div class="sign-text">Production Head</div>
            </div>
            <div class="sign-placeholder">
                <div class="sign-line"></div>
                <div class="sign-text">Receiver's Signature</div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px; font-size: 8px; color: #999; border-top: 1px solid #eee; padding-top: 10px;">
            This is a computer generated Job Sheet. Dwarkesh Packaging © <?= date('Y') ?>
        </div>
    </div>

    <script>
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('print')) {
                window.print();
            }
        }
    </script>
</body>
</html>

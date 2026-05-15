<?php
include 'root/config.php';

if (!function_exists('jobsheet_format_dimension')) {
    function jobsheet_format_dimension($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        $num = floatval($value);
        if ($num <= 0) {
            return '';
        }

        return rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
    }
}

$order_id = intval($_GET['order_id'] ?? 0);
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$type = $_GET['type'] ?? 'Half';

$where = ["o.is_deleted = 0"];
if ($order_id > 0) {
    $where[] = "o.id = $order_id";
}
if (!empty($from_date)) {
    $where[] = "o.order_date >= '$from_date'";
}
if (!empty($to_date)) {
    $where[] = "o.order_date <= '$to_date'";
}

$where_sql = implode(" AND ", $where);
$orders = $ai_db->aiGetQuery("SELECT o.*, p.default_length, p.default_width, p.default_height FROM tbl_orders o LEFT JOIN tbl_product p ON p.id = o.product_id WHERE $where_sql ORDER BY o.order_date DESC, o.id DESC");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Production Job Sheet - <?= SITE_TITLE ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/jobsheet_print.css">
</head>

<body onload="window.print()">

    <div class="container py-3">
        <div class="no-print mb-4 text-center">
            <button onclick="window.print()" class="btn btn-dark px-5 fw-bold">PRINT PRODUCTION SHEET</button>
            <button onclick="window.close()" class="btn btn-outline-danger ms-2">CLOSE</button>
        </div>

        <?php if (empty($orders)) { ?>
            <div class="alert alert-danger text-center fw-bold">NO ACTIVE ORDERS FOUND FOR SELECTED CRITERIA</div>
        <?php } else { ?>
            <?php foreach ($orders as $index => $order) {
                $items = $ai_db->aiGetQuery("SELECT * FROM tbl_orders_item WHERE order_id = " . $order['id']);

                $same_as_parts = [];
                $same_as_length = jobsheet_format_dimension($order['default_length'] ?? '');
                $same_as_width = jobsheet_format_dimension($order['default_width'] ?? '');
                $same_as_height = jobsheet_format_dimension($order['default_height'] ?? '');

                if ($same_as_length !== '') {
                    $same_as_parts[] = $same_as_length;
                }
                if ($same_as_width !== '') {
                    $same_as_parts[] = $same_as_width;
                }
                if ($same_as_height !== '') {
                    $same_as_parts[] = $same_as_height;
                }

                if (empty($same_as_parts)) {
                    $sheet_l = jobsheet_format_dimension($order['sheet_length'] ?? '');
                    $sheet_w = jobsheet_format_dimension($order['sheet_width'] ?? '');
                    if ($sheet_l !== '' && $sheet_w !== '') {
                        $same_as_parts[] = $sheet_l;
                        $same_as_parts[] = $sheet_w;
                    }
                }

                $same_as_size_text = !empty($same_as_parts) ? implode(' x ', $same_as_parts) . ' CM' : '-';
                ?>
                <div class="jobsheet-container <?= ($index + 1 < count($orders)) ? 'page-break' : '' ?>">

                    <div class="main-header d-flex justify-content-between align-items-start">
                        <div>
                            <div class="company-name"><?= SITE_TITLE ?></div>
                            <div class="type-badge"><?= strtoupper($type) ?> PRODUCTION</div>
                        </div>
                        <div class="text-end">
                            <div class="jobsheet-label">JOB SHEET</div>
                            <div class="order-no-large">#<?= htmlspecialchars($order['order_no']) ?></div>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-box">
                            <div class="info-title">Customer Details</div>
                            <div class="info-value"><?= htmlspecialchars($order['customer_name']) ?></div>
                            <div class="mt-1" style="font-size: 11px;">Brand: <?= htmlspecialchars($order['brand_name']) ?>
                            </div>
                        </div>
                        <div class="info-box">
                            <div class="info-title">Product Specification</div>
                            <div class="info-value">
                                <?= htmlspecialchars($order['product_name']) ?>
                                <?php if ($same_as_size_text !== '-') { ?>
                                    <span style="font-size: 14px; font-weight: 900; margin-left: 8px;">
                                        <?= htmlspecialchars($same_as_size_text) ?>
                                    </span>
                                <?php } ?>
                            </div>
                            <div class="mt-1" style="font-size: 11px;">Order Date:
                                <?= date('d-M-Y', strtotime($order['order_date'])) ?>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-3">
                            <div class="info-box text-center">
                                <div class="info-title">Quantity</div>
                                <div class="info-value"><?= htmlspecialchars($order['box_qty']) ?>
                                    <?= htmlspecialchars($order['box_qty_unit']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="info-box text-center">
                                <div class="info-title">Upps</div>
                                <div class="info-value"><?= htmlspecialchars($order['upps']) ?></div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="info-box text-center">
                                <div class="info-title">Sheet Length</div>
                                <div class="info-value"><?= htmlspecialchars($order['sheet_length']) ?></div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="info-box text-center">
                                <div class="info-title">Sheet Width</div>
                                <div class="info-value"><?= htmlspecialchars($order['sheet_width']) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="status-container">
                        <div class="status-item">
                            <span class="status-label">Plate Status</span>
                            <span class="status-val"><?= htmlspecialchars($order['plate_status'] ?: 'PENDING') ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Print Status</span>
                            <span class="status-val"><?= htmlspecialchars($order['print_status'] ?: 'PENDING') ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Die Status</span>
                            <span class="status-val"><?= htmlspecialchars($order['die_status'] ?: 'PENDING') ?></span>
                        </div>
                    </div>

                    <div class="tech-table-container">
                        <div class="fw-bold text-uppercase mb-2" style="font-size: 10px; letter-spacing: 1px;">Material
                            Specification Checklist</div>
                        <table class="tech-table">
                            <thead>
                                <tr>
                                    <th width="150">Group / Category</th>
                                    <th>Material Description / Specification</th>
                                    <th width="120" class="text-end">Required Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item) {
                                    $mat_name = !empty($item['material_name']) ? $item['material_name'] : $item['name'];
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= ucfirst(htmlspecialchars($item['item_group'])) ?></td>
                                        <td><?= htmlspecialchars($mat_name) ?></td>
                                        <td class="text-end fw-bold">
                                            <?= htmlspecialchars($item['qty'] > 0 ? $item['qty'] : $item['pcs']) ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="info-box" style="min-height: 60px;">
                                <div class="info-title">Production Remarks / Special Instructions</div>
                                <div style="color: #ccc;">
                                    ________________________________________________________________________________________________________________
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="footer-sig">
                        <div class="sig-box">Issued By / Prepared By</div>
                        <div class="sig-box">Operator / QC Check</div>
                        <div class="sig-box">Authorized Approval</div>
                    </div>

                    <div class="mt-4 text-center text-muted" style="font-size: 8px;">
                        This is a system generated Job Sheet for production use only. | Printed on: <?= date('d-m-Y H:i:s') ?>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>

</body>

</html>

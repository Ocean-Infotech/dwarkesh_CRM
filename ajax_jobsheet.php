<?php
include 'root/config.php';

if (isset($_POST['action']) && $_POST['action'] == 'generate_report') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $from_date = $_POST['from_date'] ?? '';
    $to_date = $_POST['to_date'] ?? '';
    $jobsheet_type = $_POST['jobsheet_type'] ?? 'Half';

    $where = ["is_deleted = 0"];
    if ($order_id > 0) {
        $where[] = "id = $order_id";
    }
    if (!empty($from_date)) {
        $where[] = "order_date >= '$from_date'";
    }
    if (!empty($to_date)) {
        $where[] = "order_date <= '$to_date'";
    }

    $where_sql = implode(" AND ", $where);
    $orders = $ai_db->aiGetQuery("SELECT * FROM tbl_orders WHERE $where_sql ORDER BY order_date DESC, id DESC");

    if (empty($orders)) {
        echo '<div class="report-result-container"><h5 class="text-muted">No data found for selected filters.</h5></div>';
        exit;
    }

    foreach ($orders as $order) {
        $items = $ai_db->aiGetQuery("SELECT * FROM tbl_orders_item WHERE order_id = " . $order['id']);
        
        echo '<div class="report-card mb-4 border-start border-4 border-gold shadow-sm">';
        echo '<div class="d-flex justify-content-between align-items-center mb-4">';
        echo '<div>';
        echo '<h5 class="fw-bold m-0 text-gold"><i class="bi bi-hash me-1"></i>' . htmlspecialchars($order['order_no']) . '</h5>';
        echo '<div class="text-muted small fw-semibold mt-1"><i class="bi bi-person me-1"></i>' . htmlspecialchars($order['customer_name']) . '</div>';
        echo '</div>';
        echo '<span class="badge bg-gold-subtle text-gold px-3 py-2 rounded-pill"><i class="bi bi-calendar3 me-2"></i>' . date("d M, Y", strtotime($order['order_date'])) . '</span>';
        echo '</div>';
        
        echo '<div class="row g-3 mb-4">';
        echo '<div class="col-md-3"><div class="text-muted small text-uppercase fw-bold mb-1">Product</div><div class="fw-semibold">' . htmlspecialchars($order['product_name']) . '</div></div>';
        echo '<div class="col-md-3"><div class="text-muted small text-uppercase fw-bold mb-1">Brand</div><div class="fw-semibold">' . htmlspecialchars($order['brand_name']) . '</div></div>';
        echo '<div class="col-md-2"><div class="text-muted small text-uppercase fw-bold mb-1">Box Qty</div><div class="fw-semibold">' . htmlspecialchars($order['box_qty']) . ' ' . htmlspecialchars($order['box_qty_unit']) . '</div></div>';
        echo '<div class="col-md-2"><div class="text-muted small text-uppercase fw-bold mb-1">Sheet Size</div><div class="fw-semibold text-primary">' . htmlspecialchars($order['sheet_length']) . ' x ' . htmlspecialchars($order['sheet_width']) . '</div></div>';
        echo '<div class="col-md-2"><div class="text-muted small text-uppercase fw-bold mb-1">Upps</div><div class="fw-semibold">' . htmlspecialchars($order['upps']) . '</div></div>';
        echo '</div>';

        if (!empty($items)) {
            echo '<div class="table-responsive rounded-3 overflow-hidden">';
            echo '<table class="table table-hover report-table mb-0">';
            echo '<thead><tr><th>Group</th><th>Material</th><th class="text-end d-none">Rate</th><th class="text-end">Qty / PCS</th></tr></thead>';
            echo '<tbody>';
            foreach ($items as $item) {
                $mat_name = !empty($item['material_name']) ? $item['material_name'] : $item['name'];
                echo '<tr>';
                echo '<td><span class="badge bg-light text-dark border">' . ucfirst(htmlspecialchars($item['item_group'])) . '</span></td>';
                echo '<td class="fw-semibold">' . htmlspecialchars($mat_name) . '</td>';
                echo '<td class="text-end text-muted d-none">₹ ' . number_format($item['rate'], 2) . '</td>';
                echo '<td class="text-end fw-bold">' . htmlspecialchars($item['qty'] > 0 ? $item['qty'] : $item['pcs']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
        
        echo '</div>';
    }
}
?>

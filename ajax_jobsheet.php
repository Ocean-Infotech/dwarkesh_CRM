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
        
        echo '<div class="report-card mb-4">';
        echo '<div class="d-flex justify-content-between align-items-center mb-3">';
        echo '<h5 class="fw-bold m-0 text-primary">Order #' . htmlspecialchars($order['order_no']) . ' - ' . htmlspecialchars($order['customer_name']) . '</h5>';
        echo '<span class="badge bg-info">' . htmlspecialchars($order['order_date']) . '</span>';
        echo '</div>';
        
        echo '<div class="row mb-3">';
        echo '<div class="col-md-3"><strong>Product:</strong><br>' . htmlspecialchars($order['product_name']) . '</div>';
        echo '<div class="col-md-3"><strong>Brand:</strong><br>' . htmlspecialchars($order['brand_name']) . '</div>';
        echo '<div class="col-md-2"><strong>Box Qty:</strong><br>' . htmlspecialchars($order['box_qty']) . ' ' . htmlspecialchars($order['box_qty_unit']) . '</div>';
        echo '<div class="col-md-2"><strong>Sheet Size:</strong><br>' . htmlspecialchars($order['sheet_length']) . ' x ' . htmlspecialchars($order['sheet_width']) . '</div>';
        echo '<div class="col-md-2"><strong>Upps:</strong><br>' . htmlspecialchars($order['upps']) . '</div>';
        echo '</div>';

        if (!empty($items)) {
            echo '<table class="table table-bordered table-sm report-table">';
            echo '<thead><tr><th>Group</th><th>Material</th><th>Rate</th><th>Qty</th></tr></thead>';
            echo '<tbody>';
            foreach ($items as $item) {
                $mat_name = !empty($item['material_name']) ? $item['material_name'] : $item['name'];
                echo '<tr>';
                echo '<td>' . ucfirst(htmlspecialchars($item['item_group'])) . '</td>';
                echo '<td>' . htmlspecialchars($mat_name) . '</td>';
                echo '<td>' . htmlspecialchars($item['rate']) . '</td>';
                echo '<td>' . htmlspecialchars($item['qty'] > 0 ? $item['qty'] : $item['pcs']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        
        echo '</div>';
    }
}
?>

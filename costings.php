<?php
    $pageTitle = "Costings";
    $currentPage = "costings";
    $headerTitle = "Manage Costings";
    $extraHead = '<link rel="stylesheet" href="assets/css/costings.css">';

    include 'include/header.php';
    require_once 'root/schema_bootstrap.php';
    dwarkesh_ensure_core_tables($ai_db);

    $table = "tbl_costings";
    $redirection_url = "costings.php";

    $mode = $_REQUEST['mode'] ?? '';
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $data = null;
    $error = '';

    function costing_text($value)
    {
        return trim((string) ($value ?? ''));
    }

    function costing_decimal($value)
    {
        if (is_array($value)) {
            return 0;
        }

        $normalized = str_replace([',', ' '], '', (string) $value);
        return is_numeric($normalized) ? round((float) $normalized, 2) : 0;
    }

    function costing_clean_brand_names($value)
    {
        $brands = [];
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $brands = $decoded;
            } else {
                $splitBrands = preg_split('/[\r\n,]+/', $value);
                if (is_array($splitBrands)) {
                    $brands = $splitBrands;
                } else {
                    $brands = [$value];
                }
            }
        } elseif (is_array($value)) {
            $brands = $value;
        }

        $cleaned = [];
        foreach ($brands as $brand) {
            $brand = trim((string) $brand);
            if ($brand !== '') {
                $cleaned[] = $brand;
            }
        }

        return array_values(array_unique($cleaned));
    }

    function costing_normalize_line_items($rawItems, $materialLookup)
    {
        $decoded = [];
        if (is_string($rawItems) && $rawItems !== '') {
            $decoded = json_decode($rawItems, true);
        } elseif (is_array($rawItems)) {
            $decoded = $rawItems;
        }

        if (!is_array($decoded)) {
            $decoded = [];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $materialId = intval($item['material_id'] ?? 0);
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '' && $materialId > 0 && isset($materialLookup[$materialId])) {
                $name = $materialLookup[$materialId]['name'];
            }

            $rate = costing_decimal($item['rate'] ?? 0);
            $weight = costing_decimal($item['weight'] ?? 0);
            if ($name === '' && $materialId <= 0) {
                continue;
            }

            $items[] = [
                'material_id' => $materialId,
                'name' => $name,
                'rate' => $rate,
                'weight' => $weight,
                'amount' => round($rate * $weight, 2)
            ];
        }

        return $items;
    }

    function costing_generate_estimate_no($ai_db, $estimateDate, $currentId = 0)
    {
        $where = "is_deleted=0";
        if ($currentId > 0) {
            $where .= " AND id!='" . intval($currentId) . "'";
        }

        // Fetch recent records to find the latest numeric estimate number
        $rows = $ai_db->aiGetQuery("SELECT estimate_no FROM tbl_costings WHERE $where ORDER BY id DESC LIMIT 100");
        $maxNumber = 0;

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $val = trim((string)$row['estimate_no']);
                // Check if the estimate number is purely numeric
                if (preg_match('/^\d+$/', $val)) {
                    $maxNumber = max($maxNumber, intval($val));
                }
            }
        }

        $nextNumber = $maxNumber + 1;
        // Start from 001 and increment sequentially (unlimited)
        return str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    $customers = $ai_db->aiGetQuery("SELECT id, contact_name, brand_names FROM tbl_customer WHERE status='active' AND is_deleted=0 ORDER BY contact_name ASC");
    $products = $ai_db->aiGetQuery("SELECT id, name, default_length, default_width, default_height FROM tbl_product WHERE status='active' AND is_deleted=0 ORDER BY name ASC");
    $materialTypes = $ai_db->aiGetQuery("SELECT id, name FROM tbl_material_type WHERE status='active' AND is_deleted=0 ORDER BY name ASC");
    $materials = $ai_db->aiGetQuery("
        SELECT m.id, m.name, m.rate, m.weight, mt.name AS material_type_name
        FROM tbl_materials m
        LEFT JOIN tbl_material_type mt ON m.material_type_id = mt.id
        WHERE m.status='active' AND m.is_deleted=0 AND mt.is_deleted=0
        ORDER BY mt.name ASC, m.name ASC
    ");

    $customerBrandsMap = [];
    $customerNameMap = [];
    foreach ($customers as $customer) {
        $customerNameMap[$customer['id']] = $customer['contact_name'];
        $customerBrandsMap[$customer['id']] = costing_clean_brand_names($customer['brand_names'] ?? []);
    }

    $productMap = [];
    foreach ($products as $product) {
        $productMap[$product['id']] = [
            'id' => intval($product['id']),
            'name' => $product['name'],
            'default_length' => (float) ($product['default_length'] ?? 0),
            'default_width' => (float) ($product['default_width'] ?? 0),
            'default_height' => (float) ($product['default_height'] ?? 0)
        ];
    }

    $linerMaterials = [];
    $duplexMaterials = [];
    $materialLookup = [];
    foreach ($materials as $material) {
        $materialPayload = [
            'id' => intval($material['id']),
            'name' => $material['name'],
            'rate' => (float) ($material['rate'] ?? 0),
            'weight' => (float) ($material['weight'] ?? 0),
            'material_type_name' => $material['material_type_name']
        ];

        $materialLookup[$materialPayload['id']] = $materialPayload;

        if (strcasecmp(trim((string) $material['material_type_name']), 'Liner') === 0) {
            $linerMaterials[] = $materialPayload;
        }

        if (strcasecmp(trim((string) $material['material_type_name']), 'Duplex') === 0) {
            $duplexMaterials[] = $materialPayload;
        }
    }

    if ($mode === "add" && isset($_POST['btn_submit'])) {
        $estimate_date = costing_text($_POST['estimate_date'] ?? date('Y-m-d'));
        if (!strtotime($estimate_date)) {
            $estimate_date = date('Y-m-d');
        }
        $estimate_no = costing_generate_estimate_no($ai_db, $estimate_date);

        $customer_id = intval($_POST['customer_id'] ?? 0);
        $brand_name = costing_text($_POST['brand_name'] ?? '');
        $product_id = intval($_POST['product_id'] ?? 0);
        $sheet_length = costing_decimal($_POST['sheet_length'] ?? 0);
        $sheet_width = costing_decimal($_POST['sheet_width'] ?? 0);
        $sheet_height = costing_decimal($_POST['sheet_height'] ?? 0);
        $sheet_unit = costing_text($_POST['sheet_unit'] ?? 'inch');
        $liner_items = costing_normalize_line_items($_POST['liner_items_json'] ?? '[]', $materialLookup);
        $liner_rate = 0;
        foreach ($liner_items as $liner_item) {
            $liner_rate += $liner_item['amount'];
        }
        $liner_rate = round($liner_rate, 2);

        $duplex_items = costing_normalize_line_items($_POST['duplex_items_json'] ?? '[]', $materialLookup);
        $duplex_rate = 0;
        foreach ($duplex_items as $duplex_item) {
            $duplex_rate += $duplex_item['amount'];
        }
        $duplex_rate = round($duplex_rate, 2);

        $printing = costing_decimal($_POST['printing'] ?? 0);
        $laminas_name = costing_text($_POST['laminas_name'] ?? '');
        $laminas_value = costing_decimal($_POST['laminas_value'] ?? 0);
        $laminas_unit = costing_text($_POST['laminas_unit'] ?? 'single_side');
        $pesting = costing_decimal($_POST['pesting'] ?? 0);
        $punching = costing_decimal($_POST['punching'] ?? 0);
        $pin_rate = costing_decimal($_POST['pin_rate'] ?? 0);
        $pin_qty = costing_decimal($_POST['pin_qty'] ?? 0);
        $side_pesting = costing_decimal($_POST['side_pesting'] ?? 0);
        $uv_coating = costing_decimal($_POST['uv_coating'] ?? 0);
        $rixa_bhadu = costing_decimal($_POST['rixa_bhadu'] ?? 0);
        $upps = max(costing_decimal($_POST['upps'] ?? 1), 1);
        $pin_amount = round($pin_rate * $pin_qty, 2);
        $total = round($liner_rate + $duplex_rate + $printing + $laminas_value + $pesting + $punching + $pin_amount + $side_pesting + $uv_coating + $rixa_bhadu, 2);
        $single_rate = round($total / $upps, 2);
        $profit = costing_decimal($_POST['profit'] ?? 0);
        $profit_percent = costing_decimal($_POST['profit_percent'] ?? 0);
        if ($profit > 0 && $single_rate > 0) {
            $profit_percent = round(($profit / $single_rate) * 100, 2);
        } elseif ($profit_percent > 0 && $single_rate > 0) {
            $profit = round(($single_rate * $profit_percent) / 100, 2);
        }
        $sale_rate = round($single_rate + $profit, 2);
        $remark = addslashes($_POST['remark'] ?? '');

        $customer_name = $customerNameMap[$customer_id] ?? '';
        $product_name = $productMap[$product_id]['name'] ?? '';

        if ($customer_id <= 0 || $product_id <= 0) {
            $error = 'M/S and Box Name are required.';
        }

        if (empty($error)) {
            $liner_items_json = addslashes(json_encode($liner_items, JSON_UNESCAPED_UNICODE));
            $add_qry = "INSERT INTO $table SET
                estimate_no='" . addslashes($estimate_no) . "',
                estimate_date='" . addslashes($estimate_date) . "',
                customer_id='" . $customer_id . "',
                customer_name='" . addslashes($customer_name) . "',
                brand_name='" . addslashes($brand_name) . "',
                product_id='" . $product_id . "',
                product_name='" . addslashes($product_name) . "',
                sheet_length='" . $sheet_length . "',
                sheet_width='" . $sheet_width . "',
                sheet_height='" . $sheet_height . "',
                sheet_unit='" . addslashes($sheet_unit) . "',
                liner_items='" . $liner_items_json . "',
                liner_rate='" . $liner_rate . "',
                duplex_items='" . addslashes(json_encode($duplex_items, JSON_UNESCAPED_UNICODE)) . "',
                duplex_rate='" . $duplex_rate . "',
                printing='" . $printing . "',
                laminas_name='" . addslashes($laminas_name) . "',
                laminas_value='" . $laminas_value . "',
                laminas_unit='" . addslashes($laminas_unit) . "',
                pesting='" . $pesting . "',
                punching='" . $punching . "',
                pin_rate='" . $pin_rate . "',
                pin_qty='" . $pin_qty . "',
                side_pesting='" . $side_pesting . "',
                uv_coating='" . $uv_coating . "',
                rixa_bhadu='" . $rixa_bhadu . "',
                total='" . $total . "',
                upps='" . $upps . "',
                single_rate='" . $single_rate . "',
                profit='" . $profit . "',
                profit_percent='" . $profit_percent . "',
                sale_rate='" . $sale_rate . "',
                remark='" . $remark . "',
                created_by='" . $_SESSION['aid'] . "'";
            $ai_db->aiQuery($add_qry);
            $ai_core->aiGoPage($redirection_url . "?msg=1");
            exit;
        }

        $data = [
            'estimate_no' => $estimate_no,
            'estimate_date' => $estimate_date,
            'customer_id' => $customer_id,
            'brand_name' => $brand_name,
            'product_id' => $product_id,
            'sheet_length' => $sheet_length,
            'sheet_width' => $sheet_width,
            'sheet_height' => $sheet_height,
            'sheet_unit' => $sheet_unit,
            'liner_items' => $liner_items,
            'liner_rate' => $liner_rate,
            'duplex_items' => $duplex_items,
            'duplex_rate' => $duplex_rate,
            'printing' => $printing,
            'laminas_name' => $laminas_name,
            'laminas_value' => $laminas_value,
            'laminas_unit' => $laminas_unit,
            'pesting' => $pesting,
            'punching' => $punching,
            'pin_rate' => $pin_rate,
            'pin_qty' => $pin_qty,
            'side_pesting' => $side_pesting,
            'uv_coating' => $uv_coating,
            'rixa_bhadu' => $rixa_bhadu,
            'total' => $total,
            'upps' => $upps,
            'single_rate' => $single_rate,
            'profit' => $profit,
            'profit_percent' => $profit_percent,
            'sale_rate' => $sale_rate,
            'remark' => $_POST['remark'] ?? ''
        ];
    }

    if ($mode === "edit" && isset($_POST['btn_submit'])) {
        $existingRow = $ai_db->aiGetQuery("SELECT estimate_no FROM $table WHERE id='" . intval($id) . "' AND is_deleted=0 LIMIT 1");
        $existingEstimateNo = $existingRow[0]['estimate_no'] ?? '';

        $estimate_date = costing_text($_POST['estimate_date'] ?? date('Y-m-d'));
        if (!strtotime($estimate_date)) {
            $estimate_date = date('Y-m-d');
        }
        $estimate_no = $existingEstimateNo !== '' ? $existingEstimateNo : costing_generate_estimate_no($ai_db, $estimate_date, $id);

        $customer_id = intval($_POST['customer_id'] ?? 0);
        $brand_name = costing_text($_POST['brand_name'] ?? '');
        $product_id = intval($_POST['product_id'] ?? 0);
        $sheet_length = costing_decimal($_POST['sheet_length'] ?? 0);
        $sheet_width = costing_decimal($_POST['sheet_width'] ?? 0);
        $sheet_height = costing_decimal($_POST['sheet_height'] ?? 0);
        $sheet_unit = costing_text($_POST['sheet_unit'] ?? 'inch');
        $liner_items = costing_normalize_line_items($_POST['liner_items_json'] ?? '[]', $materialLookup);
        $liner_rate = 0;
        foreach ($liner_items as $liner_item) {
            $liner_rate += $liner_item['amount'];
        }
        $liner_rate = round($liner_rate, 2);

        $duplex_items = costing_normalize_line_items($_POST['duplex_items_json'] ?? '[]', $materialLookup);
        $duplex_rate = 0;
        foreach ($duplex_items as $duplex_item) {
            $duplex_rate += $duplex_item['amount'];
        }
        $duplex_rate = round($duplex_rate, 2);

        $printing = costing_decimal($_POST['printing'] ?? 0);
        $laminas_name = costing_text($_POST['laminas_name'] ?? '');
        $laminas_value = costing_decimal($_POST['laminas_value'] ?? 0);
        $laminas_unit = costing_text($_POST['laminas_unit'] ?? 'single_side');
        $pesting = costing_decimal($_POST['pesting'] ?? 0);
        $punching = costing_decimal($_POST['punching'] ?? 0);
        $pin_rate = costing_decimal($_POST['pin_rate'] ?? 0);
        $pin_qty = costing_decimal($_POST['pin_qty'] ?? 0);
        $side_pesting = costing_decimal($_POST['side_pesting'] ?? 0);
        $uv_coating = costing_decimal($_POST['uv_coating'] ?? 0);
        $rixa_bhadu = costing_decimal($_POST['rixa_bhadu'] ?? 0);
        $upps = max(costing_decimal($_POST['upps'] ?? 1), 1);
        $pin_amount = round($pin_rate * $pin_qty, 2);
        $total = round($liner_rate + $duplex_rate + $printing + $laminas_value + $pesting + $punching + $pin_amount + $side_pesting + $uv_coating + $rixa_bhadu, 2);
        $single_rate = round($total / $upps, 2);
        $profit = costing_decimal($_POST['profit'] ?? 0);
        $profit_percent = costing_decimal($_POST['profit_percent'] ?? 0);
        if ($profit > 0 && $single_rate > 0) {
            $profit_percent = round(($profit / $single_rate) * 100, 2);
        } elseif ($profit_percent > 0 && $single_rate > 0) {
            $profit = round(($single_rate * $profit_percent) / 100, 2);
        }
        $sale_rate = round($single_rate + $profit, 2);
        $remark = addslashes($_POST['remark'] ?? '');

        $customer_name = $customerNameMap[$customer_id] ?? '';
        $product_name = $productMap[$product_id]['name'] ?? '';

        if ($customer_id <= 0 || $product_id <= 0) {
            $error = 'M/S and Box Name are required.';
        }

        if (empty($error)) {
            $liner_items_json = addslashes(json_encode($liner_items, JSON_UNESCAPED_UNICODE));
            $edit_qry = "UPDATE $table SET
                estimate_no='" . addslashes($estimate_no) . "',
                estimate_date='" . addslashes($estimate_date) . "',
                customer_id='" . $customer_id . "',
                customer_name='" . addslashes($customer_name) . "',
                brand_name='" . addslashes($brand_name) . "',
                product_id='" . $product_id . "',
                product_name='" . addslashes($product_name) . "',
                sheet_length='" . $sheet_length . "',
                sheet_width='" . $sheet_width . "',
                sheet_height='" . $sheet_height . "',
                sheet_unit='" . addslashes($sheet_unit) . "',
                liner_items='" . $liner_items_json . "',
                liner_rate='" . $liner_rate . "',
                duplex_items='" . addslashes(json_encode($duplex_items, JSON_UNESCAPED_UNICODE)) . "',
                duplex_rate='" . $duplex_rate . "',
                printing='" . $printing . "',
                laminas_name='" . addslashes($laminas_name) . "',
                laminas_value='" . $laminas_value . "',
                laminas_unit='" . addslashes($laminas_unit) . "',
                pesting='" . $pesting . "',
                punching='" . $punching . "',
                pin_rate='" . $pin_rate . "',
                pin_qty='" . $pin_qty . "',
                side_pesting='" . $side_pesting . "',
                uv_coating='" . $uv_coating . "',
                rixa_bhadu='" . $rixa_bhadu . "',
                total='" . $total . "',
                upps='" . $upps . "',
                single_rate='" . $single_rate . "',
                profit='" . $profit . "',
                profit_percent='" . $profit_percent . "',
                sale_rate='" . $sale_rate . "',
                remark='" . $remark . "',
                updated_by='" . $_SESSION['aid'] . "'
                WHERE id='" . intval($id) . "'";
            $ai_db->aiQuery($edit_qry);
            $ai_core->aiGoPage($redirection_url . "?msg=2");
            exit;
        }

        $data = [
            'id' => $id,
            'estimate_no' => $estimate_no,
            'estimate_date' => $estimate_date,
            'customer_id' => $customer_id,
            'brand_name' => $brand_name,
            'product_id' => $product_id,
            'sheet_length' => $sheet_length,
            'sheet_width' => $sheet_width,
            'sheet_height' => $sheet_height,
            'sheet_unit' => $sheet_unit,
            'liner_items' => $liner_items,
            'liner_rate' => $liner_rate,
            'duplex_items' => $duplex_items,
            'duplex_rate' => $duplex_rate,
            'printing' => $printing,
            'laminas_name' => $laminas_name,
            'laminas_value' => $laminas_value,
            'laminas_unit' => $laminas_unit,
            'pesting' => $pesting,
            'punching' => $punching,
            'pin_rate' => $pin_rate,
            'pin_qty' => $pin_qty,
            'side_pesting' => $side_pesting,
            'uv_coating' => $uv_coating,
            'rixa_bhadu' => $rixa_bhadu,
            'total' => $total,
            'upps' => $upps,
            'single_rate' => $single_rate,
            'profit' => $profit,
            'profit_percent' => $profit_percent,
            'sale_rate' => $sale_rate,
            'remark' => $_POST['remark'] ?? ''
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
        if ($data && isset($data['liner_items']) && is_string($data['liner_items'])) {
            $data['liner_items'] = costing_normalize_line_items($data['liner_items'], $materialLookup);
        }
        if ($data && isset($data['duplex_items']) && is_string($data['duplex_items'])) {
            $data['duplex_items'] = costing_normalize_line_items($data['duplex_items'], $materialLookup);
        }
    }

    if ($mode === "add" && !$error && !isset($_POST['btn_submit'])) {
        $lastResult = $ai_db->aiGetQuery("SELECT * FROM $table WHERE is_deleted=0 ORDER BY id DESC LIMIT 1");
        if (!empty($lastResult)) {
            $lastData = $lastResult[0];

            // Keep sequence/date and audit fields fresh for new entry.
            unset(
                $lastData['id'],
                $lastData['estimate_no'],
                $lastData['estimate_date'],
                $lastData['created_by'],
                $lastData['created_at'],
                $lastData['updated_by'],
                $lastData['updated_at'],
                $lastData['deleted_by'],
                $lastData['deleted_at'],
                $lastData['is_deleted']
            );

            if (isset($lastData['liner_items']) && is_string($lastData['liner_items'])) {
                $lastData['liner_items'] = costing_normalize_line_items($lastData['liner_items'], $materialLookup);
            }
            if (isset($lastData['duplex_items']) && is_string($lastData['duplex_items'])) {
                $lastData['duplex_items'] = costing_normalize_line_items($lastData['duplex_items'], $materialLookup);
            }

            $data = is_array($data) ? array_merge($data, $lastData) : $lastData;
        }
    }

    $all_data = [];
    $totalRecords = 0;
    $totalPages = 1;
    $limit = 20;
    $filters = [];
    $hasActiveFilters = false;
    if (!$mode) {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $limit;

        $filterSessionKey = 'costings_filters';
        if (isset($_GET['action']) && $_GET['action'] === 'clear_filters') {
            unset($_SESSION[$filterSessionKey]);
            header('Location: costings.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION[$filterSessionKey] = [
                'filter_estimate_no' => trim($_POST['filter_estimate_no'] ?? ''),
                'filter_customer_id' => $_POST['filter_customer_id'] ?? '',
                'filter_product_id' => $_POST['filter_product_id'] ?? '',
                'filter_date_from' => $_POST['filter_date_from'] ?? '',
                'filter_date_to' => $_POST['filter_date_to'] ?? ''
            ];
            $page = 1;
            $offset = 0;
        }

        $filters = $_SESSION[$filterSessionKey] ?? [];
        $hasActiveFilters = !empty(array_filter($filters, function ($value) {
            return $value !== '' && $value !== null;
        }));

        $where_conditions = ["is_deleted=0"];
        if (!empty($filters['filter_estimate_no'])) {
            $where_conditions[] = "estimate_no LIKE '%" . addslashes($filters['filter_estimate_no']) . "%'";
        }
        if (!empty($filters['filter_customer_id'])) {
            $where_conditions[] = "customer_id='" . intval($filters['filter_customer_id']) . "'";
        }
        if (!empty($filters['filter_product_id'])) {
            $where_conditions[] = "product_id='" . intval($filters['filter_product_id']) . "'";
        }
        if (!empty($filters['filter_date_from'])) {
            $where_conditions[] = "estimate_date >= '" . addslashes($filters['filter_date_from']) . "'";
        }
        if (!empty($filters['filter_date_to'])) {
            $where_conditions[] = "estimate_date <= '" . addslashes($filters['filter_date_to']) . "'";
        }

        $where_clause = implode(" AND ", $where_conditions);
        $countResult = $ai_db->aiGetQuery("SELECT COUNT(*) as total FROM $table WHERE $where_clause");
        $totalRecords = isset($countResult[0]['total']) ? intval($countResult[0]['total']) : 0;
        $totalPages = $totalRecords > 0 ? max(1, ceil($totalRecords / $limit)) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        $all_data = $ai_db->aiGetQuery("SELECT * FROM $table WHERE $where_clause ORDER BY estimate_date DESC, id DESC LIMIT $limit OFFSET $offset");
    }

    $estimateDateValue = (is_array($data) && isset($data['estimate_date'])) ? $data['estimate_date'] : date('Y-m-d');
    $estimateNoValue = (is_array($data) && isset($data['estimate_no'])) ? $data['estimate_no'] : costing_generate_estimate_no($ai_db, $estimateDateValue, $id);
    $brandOptions = [];
    $selectedCustomerId = (is_array($data) && isset($data['customer_id'])) ? intval($data['customer_id']) : 0;
    if ($selectedCustomerId > 0 && isset($customerBrandsMap[$selectedCustomerId])) {
        $brandOptions = $customerBrandsMap[$selectedCustomerId];
    }
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">
            <span class="text-muted fw-light">Costings /</span> <?= $mode ? ucfirst($mode) : 'All Records' ?>
        </h4>
        <?php if (!$mode) { ?>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle btn-filter-toggle <?= !empty($hasActiveFilters) ? 'active' : '' ?>"
                    data-bs-toggle="collapse" data-bs-target="#costingsFilterCollapse" aria-expanded="false"
                    aria-controls="costingsFilterCollapse" aria-label="Toggle Filters" title="Toggle Filters">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="costings.php?mode=add" class="btn btn-gold btn-sm rounded-pill px-3">
                    <i class="bi bi-plus-lg me-1"></i> Add New
                </a>
            </div>
        <?php } else { ?>
            <a href="costings.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        <?php } ?>
    </div>

    <?php if (!$mode) { ?>
        <div class="collapse mb-3" id="costingsFilterCollapse">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="costings.php" class="row g-3">
                        <div class="col-md-3">
                            <label for="filter_estimate_no" class="form-label">Estimate No.</label>
                            <input type="text" class="form-control" id="filter_estimate_no" name="filter_estimate_no"
                                   value="<?= htmlspecialchars($filters['filter_estimate_no'] ?? '') ?>" placeholder="Search estimate no">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_customer_id" class="form-label">M/S</label>
                            <select class="form-select" id="filter_customer_id" name="filter_customer_id">
                                <option value="">All Customers</option>
                                <?php foreach ($customers as $customer) { ?>
                                    <option value="<?= $customer['id'] ?>" <?= ($filters['filter_customer_id'] ?? '') == $customer['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($customer['contact_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_product_id" class="form-label">Box Name</label>
                            <select class="form-select" id="filter_product_id" name="filter_product_id">
                                <option value="">All Products</option>
                                <?php foreach ($products as $product) { ?>
                                    <option value="<?= $product['id'] ?>" <?= ($filters['filter_product_id'] ?? '') == $product['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($product['name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="filter_date_from" name="filter_date_from"
                                   value="<?= htmlspecialchars($filters['filter_date_from'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="filter_date_to" name="filter_date_to"
                                   value="<?= htmlspecialchars($filters['filter_date_to'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm me-2">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                            <a href="costings.php?action=clear_filters" class="btn btn-outline-secondary btn-sm">
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
                                <th>Estimate No.</th>
                                <th>Date</th>
                                <th>M/S</th>
                                <th>Brand</th>
                                <th>Box Name</th>
                                <th>Total</th>
                                <th>Sale Rate</th>
                                <th width="200" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_data)) {
                                foreach ($all_data as $index => $row) { ?>
                                    <tr>
                                        <td>#<?= $offset + $index + 1 ?></td>
                                        <td><span class="fw-semibold"><?= htmlspecialchars($row['estimate_no']) ?></span></td>
                                        <td><?= date('d-m-Y', strtotime($row['estimate_date'])) ?></td>
                                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                        <td><?= $row['brand_name'] !== '' ? htmlspecialchars($row['brand_name']) : '<span class="text-muted">-</span>' ?></td>
                                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                                        <td>Rs. <?= number_format((float) $row['total'], 2) ?></td>
                                        <td>Rs. <?= number_format((float) $row['sale_rate'], 2) ?></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="costings.php?mode=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Edit">
                                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                                </a>
                                                <a href="costings.php?mode=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Delete" onclick="return confirm('Are you sure you want to delete this record?')">
                                                    <i class="bi bi-trash me-1"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                            <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        No data available. Click "Add New" to create your first costing.
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
                                        <a class="page-link" href="costings.php?page=<?= $page - 1 ?>" tabindex="-1">Previous</a>
                                    </li>
                                    <?php for ($p = 1; $p <= $totalPages; $p++) {
                                        if ($p == 1 || $p == $totalPages || ($p >= $page - 2 && $p <= $page + 2)) { ?>
                                            <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                                <a class="page-link" href="costings.php?page=<?= $p ?>"><?= $p ?></a>
                                            </li>
                                        <?php } elseif ($p == $page - 3 || $p == $page + 3) { ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php }
                                    } ?>
                                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="costings.php?page=<?= $page + 1 ?>">Next</a>
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
                        <h5 class="fw-bold mb-0"><?= ($mode == 'edit') ? 'Update' : 'Create' ?> Costing</h5>
                    </div>
                    <form class="card-body" method="post" action="costings.php?mode=<?= $mode ?>&id=<?= $id ?>">
                        <?php if (!empty($error)) { ?>
                            <div class="alert alert-danger py-2 mb-3" role="alert">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php } ?>

                        <div class="estimation-sheet">
                            <div class="estimation-banner">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Estimate No.</label>
                                        <input type="text" name="estimate_no" class="form-control" value="<?= htmlspecialchars($estimateNoValue) ?>" readonly>
                                    </div>
                                    <div class="col-md-4 ms-md-auto">
                                        <label class="form-label fw-bold">Date</label>
                                        <input type="date" name="estimate_date" id="estimate_date" class="form-control" value="<?= htmlspecialchars($estimateDateValue) ?>">
                                    </div>
                                </div>
                                <h2>Estimation</h2>
                            </div>

                            <div class="estimation-body">
                                <div class="estimation-section">
                                    <div class="estimation-section-title">Basic Details</div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">M/S <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <select name="customer_id" id="customer_id" class="form-select" required>
                                                    <option value="">Select Customer</option>
                                                    <?php foreach ($customers as $customer) { ?>
                                                        <option value="<?= $customer['id'] ?>" <?= (isset($data['customer_id']) && $data['customer_id'] == $customer['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($customer['contact_name']) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                                <button type="button" class="btn btn-outline-primary mini-action-link" data-bs-toggle="modal" data-bs-target="#customerQuickAddModal">Add New</button>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Brand Name</label>
                                            <div class="input-group">
                                                <select name="brand_name" id="brand_name" class="form-select" data-selected-brand="<?= htmlspecialchars($data['brand_name'] ?? '') ?>">
                                                    <option value="">Select Brand</option>
                                                    <?php foreach ($brandOptions as $brandOption) { ?>
                                                        <option value="<?= htmlspecialchars($brandOption) ?>" <?= (($data['brand_name'] ?? '') === $brandOption) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($brandOption) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                                <button type="button" class="btn btn-outline-primary mini-action-link" data-bs-toggle="modal" data-bs-target="#brandQuickAddModal">Add New</button>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Box Name <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <select name="product_id" id="product_id" class="form-select" required>
                                                    <option value="">Select Product</option>
                                                    <?php foreach ($products as $product) { ?>
                                                        <option value="<?= $product['id'] ?>" <?= (isset($data['product_id']) && $data['product_id'] == $product['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($product['name']) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                                <button type="button" class="btn btn-outline-primary mini-action-link" data-bs-toggle="modal" data-bs-target="#productQuickAddModal">Add New</button>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Sheet Size</label>
                                            <div class="row g-2">
                                                <div class="col-3">
                                                    <input type="number" step="0.01" name="sheet_length" id="sheet_length" class="form-control" placeholder="Length" value="<?= htmlspecialchars($data['sheet_length'] ?? '') ?>">
                                                </div>
                                                <div class="col-3">
                                                    <input type="number" step="0.01" name="sheet_width" id="sheet_width" class="form-control" placeholder="Width" value="<?= htmlspecialchars($data['sheet_width'] ?? '') ?>">
                                                </div>
                                                <div class="col-3">
                                                    <input type="number" step="0.01" name="sheet_height" id="sheet_height" class="form-control" placeholder="Height" value="<?= htmlspecialchars($data['sheet_height'] ?? '') ?>">
                                                </div>
                                                <div class="col-3">
                                                    <select name="sheet_unit" id="sheet_unit" class="form-select">
                                                        <option value="inch" <?= (($data['sheet_unit'] ?? 'inch') === 'inch') ? 'selected' : '' ?>>Inch</option>
                                                        <option value="cm" <?= (($data['sheet_unit'] ?? '') === 'cm') ? 'selected' : '' ?>>CM</option>
                                                        <option value="mm" <?= (($data['sheet_unit'] ?? '') === 'mm') ? 'selected' : '' ?>>MM</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="estimation-section">
                                    <div class="estimation-section-title">Liner List</div>
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Select Liner</label>
                                            <select id="liner_material_id" class="form-select">
                                                <option value="">Select Liner</option>
                                                <?php foreach ($linerMaterials as $linerMaterial) { ?>
                                                    <option value="<?= $linerMaterial['id'] ?>">
                                                        <?= htmlspecialchars($linerMaterial['name']) ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-primary w-100 open-material-modal" data-bs-toggle="modal" data-bs-target="#materialQuickAddModal" data-material-context="liner" data-material-type-name="Liner">Add New</button>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Rate</label>
                                            <input type="number" step="0.01" id="liner_input_rate" class="form-control" placeholder="Rate">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Weight</label>
                                            <input type="number" step="0.01" id="liner_input_weight" class="form-control" placeholder="Weight">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" id="add_liner_item" class="btn btn-gold w-100">Add</button>
                                        </div>
                                    </div>

                                    <div class="table-responsive mt-3">
                                        <table class="table table-bordered table-liner mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Name</th>
                                                    <th width="140">Rate</th>
                                                    <th width="140">Weight</th>
                                                    <th width="120" class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="liner_items_table_body">
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">No liner added yet.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <input type="hidden" name="liner_items_json" id="liner_items_json" value="<?= htmlspecialchars(json_encode($data['liner_items'] ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Liner Rate</label>
                                            <input type="number" step="0.01" name="liner_rate" id="liner_rate" class="form-control" value="<?= htmlspecialchars($data['liner_rate'] ?? '0') ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="estimation-section">
                                    <div class="estimation-section-title">Duplex List</div>
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Select Duplex</label>
                                            <select id="duplex_material_id" class="form-select">
                                                <option value="">Select Duplex</option>
                                                <?php foreach ($duplexMaterials as $duplexMaterial) { ?>
                                                    <option value="<?= $duplexMaterial['id'] ?>">
                                                        <?= htmlspecialchars($duplexMaterial['name']) ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-primary w-100 open-material-modal" data-bs-toggle="modal" data-bs-target="#materialQuickAddModal" data-material-context="duplex" data-material-type-name="Duplex">Add New</button>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Rate</label>
                                            <input type="number" step="0.01" id="duplex_input_rate" class="form-control" placeholder="Rate">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Weight</label>
                                            <input type="number" step="0.01" id="duplex_input_weight" class="form-control" placeholder="Weight">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" id="add_duplex_item" class="btn btn-gold w-100">Add</button>
                                        </div>
                                    </div>

                                    <div class="table-responsive mt-3">
                                        <table class="table table-bordered table-liner mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Name</th>
                                                    <th width="140">Rate</th>
                                                    <th width="140">Weight</th>
                                                    <th width="120" class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="duplex_items_table_body">
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">No duplex added yet.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <input type="hidden" name="duplex_items_json" id="duplex_items_json" value="<?= htmlspecialchars(json_encode($data['duplex_items'] ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Duplex Rate</label>
                                            <input type="number" step="0.01" name="duplex_rate" id="duplex_rate" class="form-control" value="<?= htmlspecialchars($data['duplex_rate'] ?? '0') ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="estimation-section">
                                    <div class="estimation-section-title">Other Calculation</div>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Printing</label>
                                            <input type="number" step="0.01" name="printing" id="printing" class="form-control" placeholder="Printing" value="<?= htmlspecialchars($data['printing'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Laminas</label>
                                            <input type="text" name="laminas_name" class="form-control" placeholder="Laminas" value="<?= htmlspecialchars($data['laminas_name'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Laminas Rate</label>
                                            <input type="number" step="0.01" name="laminas_value" id="laminas_value" class="form-control" placeholder="0" value="<?= htmlspecialchars($data['laminas_value'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Type</label>
                                            <select name="laminas_unit" class="form-select">
                                                <option value="single_side" <?= (($data['laminas_unit'] ?? 'single_side') === 'single_side') ? 'selected' : '' ?>>Single Side</option>
                                                <option value="double_side" <?= (($data['laminas_unit'] ?? '') === 'double_side') ? 'selected' : '' ?>>Double Side</option>
                                                <option value="matte" <?= (($data['laminas_unit'] ?? '') === 'matte') ? 'selected' : '' ?>>Matte</option>
                                                <option value="gloss" <?= (($data['laminas_unit'] ?? '') === 'gloss') ? 'selected' : '' ?>>Gloss</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Pesting</label>
                                            <input type="number" step="0.01" name="pesting" id="pesting" class="form-control" placeholder="Pesting" value="<?= htmlspecialchars($data['pesting'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Punching</label>
                                            <input type="number" step="0.01" name="punching" id="punching" class="form-control" placeholder="Punching" value="<?= htmlspecialchars($data['punching'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Pin Rate</label>
                                            <input type="number" step="0.01" name="pin_rate" id="pin_rate" class="form-control" placeholder="Pin Rate" value="<?= htmlspecialchars($data['pin_rate'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Pin Qty</label>
                                            <input type="number" step="0.01" name="pin_qty" id="pin_qty" class="form-control" placeholder="Pin Qty" value="<?= htmlspecialchars($data['pin_qty'] ?? '') ?>">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Side Pesting</label>
                                            <input type="number" step="0.01" name="side_pesting" id="side_pesting" class="form-control" placeholder="Side Pesting" value="<?= htmlspecialchars($data['side_pesting'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">UV Coating</label>
                                            <input type="number" step="0.01" name="uv_coating" id="uv_coating" class="form-control" placeholder="UV Coating" value="<?= htmlspecialchars($data['uv_coating'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">Rixa bhadu</label>
                                            <input type="number" step="0.01" name="rixa_bhadu" id="rixa_bhadu" class="form-control" placeholder="Expense" value="<?= htmlspecialchars($data['rixa_bhadu'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="estimation-section">
                                    <div class="estimation-section-title">Calculation</div>
                                    <div class="row g-3">
                                        <div class="col-lg-3 col-md-6">
                                            <div class="costing-summary-card p-3 h-100">
                                                <div class="costing-summary-label">Total</div>
                                                <input type="number" step="0.01" name="total" id="total" class="form-control fw-bold" value="<?= htmlspecialchars($data['total'] ?? '0') ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <div class="costing-summary-card p-3 h-100">
                                                <div class="costing-summary-label">Upps</div>
                                                <input type="number" step="0.01" name="upps" id="upps" class="form-control" value="<?= htmlspecialchars($data['upps'] ?? '1') ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <div class="costing-summary-card p-3 h-100">
                                                <div class="costing-summary-label">Single Rate</div>
                                                <input type="number" step="0.01" name="single_rate" id="single_rate" class="form-control fw-bold" value="<?= htmlspecialchars($data['single_rate'] ?? '0') ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <div class="costing-summary-card p-3 h-100">
                                                <div class="costing-summary-label">Sale Rate</div>
                                                <input type="number" step="0.01" name="sale_rate" id="sale_rate" class="form-control fw-bold" value="<?= htmlspecialchars($data['sale_rate'] ?? '0') ?>" readonly>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Profit</label>
                                            <input type="number" step="0.01" name="profit" id="profit" class="form-control" placeholder="Profit" value="<?= htmlspecialchars($data['profit'] ?? '0') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Profit In (%)</label>
                                            <input type="number" step="0.01" name="profit_percent" id="profit_percent" class="form-control" placeholder="Profit in %" value="<?= htmlspecialchars($data['profit_percent'] ?? '0') ?>">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold">Remark</label>
                                            <textarea name="remark" class="form-control" rows="3" placeholder="Remark"><?= isset($data['remark']) ? htmlspecialchars($data['remark']) : '' ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="submit" name="btn_submit" class="btn btn-gold btn-sm rounded-pill px-4">
                                <i class="bi bi-check-circle me-1"></i> <?= ($mode == 'edit') ? 'Update Costing' : 'Save Costing' ?>
                            </button>
                            <a href="costings.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<?php if ($mode) { ?>
<div class="modal fade" id="customerQuickAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="customerQuickAddForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contact Name <span class="text-danger">*</span></label>
                            <input type="text" name="contact_name" class="form-control" placeholder="Enter Contact Name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone No <span class="text-danger">*</span></label>
                            <input type="text" name="phone_no" class="form-control" placeholder="Enter Phone Number" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Address</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Enter Address"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">City Name</label>
                            <input type="text" name="city_name" class="form-control" placeholder="Enter City Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="deactive">Deactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="bg-light p-3 rounded border">
                                <label class="form-label fw-bold d-block mb-3">Brand Names</label>
                                <div id="modalBrandRepeater">
                                    <div class="input-group input-group-sm mb-2 modal-brand-item">
                                        <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                        <input type="text" name="brand_names[]" class="form-control" placeholder="Brand Name">
                                        <button type="button" class="btn btn-danger removeModalBrand"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 mt-2" id="addModalBrandBtn">
                                    <i class="bi bi-plus-circle me-1"></i> Add More
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-gold" id="customerQuickAddSubmit">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="productQuickAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="productQuickAddForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Enter product name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rate</label>
                            <input type="number" step="0.01" name="rate" class="form-control" placeholder="Enter rate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">HSN Code</label>
                            <input type="text" name="hsn_code" class="form-control" placeholder="Enter HSN Code">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Default Size</label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="number" step="0.01" name="default_length" class="form-control" placeholder="L (cm)">
                                </div>
                                <div class="col-md-4">
                                    <input type="number" step="0.01" name="default_width" class="form-control" placeholder="W (cm)">
                                </div>
                                <div class="col-md-4">
                                    <input type="number" step="0.01" name="default_height" class="form-control" placeholder="H (cm)">
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Enter Description"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="deactive">Deactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-gold" id="productQuickAddSubmit">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="materialQuickAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="materialQuickAddTitle">Add Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="materialQuickAddForm">
                <input type="hidden" name="material_context" id="material_context" value="liner">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Material Type <span class="text-danger">*</span></label>
                            <select name="material_type_id" id="material_type_id" class="form-select" required>
                                <option value="">Select Material Type</option>
                                <?php foreach ($materialTypes as $materialType) { ?>
                                    <option value="<?= $materialType['id'] ?>"><?= htmlspecialchars($materialType['name']) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Enter Material Name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">F Value</label>
                            <input type="number" step="0.01" name="f_value" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">P Value</label>
                            <input type="number" step="0.01" name="p_value" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Top Value</label>
                            <input type="number" step="0.01" name="top_value" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Rate</label>
                            <input type="number" step="0.01" name="rate" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Weight (kg)</label>
                            <input type="number" step="0.01" name="weight" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="deactive">Deactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-gold" id="materialQuickAddSubmit">Save Material</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="brandQuickAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add New Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="brandQuickAddForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="brand_name" id="new_brand_name" class="form-control" placeholder="Enter brand name" required>
                        <div class="form-text text-muted small">This brand will be added to the selected customer.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-gold" id="brandQuickAddSubmit">Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<?php include 'include/footer.php'; ?>

<?php if ($mode) { ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const customerBrands = <?= json_encode($customerBrandsMap, JSON_UNESCAPED_UNICODE) ?>;
    const products = <?= json_encode($productMap, JSON_UNESCAPED_UNICODE) ?>;
    const linerMaterials = <?= json_encode($linerMaterials, JSON_UNESCAPED_UNICODE) ?>;
    const duplexMaterials = <?= json_encode($duplexMaterials, JSON_UNESCAPED_UNICODE) ?>;

    const customerSelect = document.getElementById('customer_id');
    const brandSelect = document.getElementById('brand_name');
    const productSelect = document.getElementById('product_id');
    const sheetLength = document.getElementById('sheet_length');
    const sheetWidth = document.getElementById('sheet_width');
    const sheetHeight = document.getElementById('sheet_height');
    const linerMaterialSelect = document.getElementById('liner_material_id');
    const linerRateInput = document.getElementById('liner_input_rate');
    const linerWeightInput = document.getElementById('liner_input_weight');
    const addLinerButton = document.getElementById('add_liner_item');
    const linerTableBody = document.getElementById('liner_items_table_body');
    const linerItemsJson = document.getElementById('liner_items_json');
    const linerRateField = document.getElementById('liner_rate');
    const totalField = document.getElementById('total');
    const uppsField = document.getElementById('upps');
    const singleRateField = document.getElementById('single_rate');
    const profitField = document.getElementById('profit');
    const profitPercentField = document.getElementById('profit_percent');
    const saleRateField = document.getElementById('sale_rate');
    const duplexMaterialSelect = document.getElementById('duplex_material_id');
    const duplexRateInput = document.getElementById('duplex_input_rate');
    const duplexWeightInput = document.getElementById('duplex_input_weight');
    const duplexRateField = document.getElementById('duplex_rate');
    const duplexTableBody = document.getElementById('duplex_items_table_body');
    const duplexItemsJson = document.getElementById('duplex_items_json');
    const addDuplexButton = document.getElementById('add_duplex_item');
    const customerQuickAddForm = document.getElementById('customerQuickAddForm');
    const productQuickAddForm = document.getElementById('productQuickAddForm');
    const materialQuickAddForm = document.getElementById('materialQuickAddForm');
    const customerQuickAddModal = document.getElementById('customerQuickAddModal');
    const productQuickAddModal = document.getElementById('productQuickAddModal');
    const materialQuickAddModal = document.getElementById('materialQuickAddModal');
    const brandQuickAddModal = document.getElementById('brandQuickAddModal');
    const brandQuickAddForm = document.getElementById('brandQuickAddForm');
    const materialContextField = document.getElementById('material_context');
    const materialTypeField = document.getElementById('material_type_id');
    const materialQuickAddTitle = document.getElementById('materialQuickAddTitle');
    const openMaterialModalButtons = document.querySelectorAll('.open-material-modal');

    const moneyInputs = [
        document.getElementById('printing'),
        document.getElementById('laminas_value'),
        document.getElementById('pesting'),
        document.getElementById('punching'),
        document.getElementById('pin_rate'),
        document.getElementById('pin_qty'),
        document.getElementById('side_pesting'),
        document.getElementById('uv_coating'),
        document.getElementById('rixa_bhadu')
    ];

    let profitSyncMode = 'amount';
    let linerItems = [];
    let duplexItems = [];
    let activeMaterialContext = 'liner';

    const customerModalInstance = customerQuickAddModal ? bootstrap.Modal.getOrCreateInstance(customerQuickAddModal) : null;
    const productModalInstance = productQuickAddModal ? bootstrap.Modal.getOrCreateInstance(productQuickAddModal) : null;
    const materialModalInstance = materialQuickAddModal ? bootstrap.Modal.getOrCreateInstance(materialQuickAddModal) : null;
    const brandModalInstance = brandQuickAddModal ? bootstrap.Modal.getOrCreateInstance(brandQuickAddModal) : null;

    function parseNumber(value) {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function roundValue(value) {
        return Math.round((value + Number.EPSILON) * 100) / 100;
    }

    function formatValue(value) {
        return roundValue(value).toFixed(2);
    }

    function resetQuickForm(form) {
        if (!form) {
            return;
        }

        form.reset();
        form.classList.remove('was-validated');
    }

    function toggleSubmitButton(button, isLoading, loadingText) {
        if (!button) {
            return;
        }

        if (isLoading) {
            button.dataset.originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${loadingText}`;
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    }

    function upsertOption(selectElement, value, label) {
        if (!selectElement) {
            return;
        }

        let option = Array.from(selectElement.options).find(function (item) {
            return String(item.value) === String(value);
        });

        if (!option) {
            option = document.createElement('option');
            option.value = value;
            selectElement.appendChild(option);
        }

        option.textContent = label;
    }

    function setModalMaterialTypeByName(typeName) {
        if (!materialTypeField) {
            return;
        }

        const option = Array.from(materialTypeField.options).find(function (item) {
            return item.textContent.trim().toLowerCase() === String(typeName).trim().toLowerCase();
        });

        materialTypeField.value = option ? option.value : '';
    }

    function handleAjaxError(message) {
        if (typeof showToast === 'function') {
            showToast('Error', message, 'danger');
        } else {
            alert(message);
        }
    }

    function populateBrandOptions(customerId, selectedBrand) {
        const brands = customerBrands[customerId] || [];
        const currentValue = selectedBrand || brandSelect.dataset.selectedBrand || '';
        brandSelect.innerHTML = '<option value="">Select Brand</option>';

        brands.forEach(function (brand) {
            const option = document.createElement('option');
            option.value = brand;
            option.textContent = brand;
            if (brand === currentValue) {
                option.selected = true;
            }
            brandSelect.appendChild(option);
        });

        if (currentValue && !brands.includes(currentValue)) {
            const option = document.createElement('option');
            option.value = currentValue;
            option.textContent = currentValue;
            option.selected = true;
            brandSelect.appendChild(option);
        }

        brandSelect.dataset.selectedBrand = brandSelect.value;
        if (typeof refreshSelect2Dropdown === 'function') {
            refreshSelect2Dropdown(brandSelect);
        }
    }

    function applyProductDefaults(productId, forceFill) {
        const product = products[productId];
        if (!product) {
            return;
        }

        if (forceFill || !sheetLength.value) {
            sheetLength.value = product.default_length || '';
        }
        if (forceFill || !sheetWidth.value) {
            sheetWidth.value = product.default_width || '';
        }
        if (forceFill || !sheetHeight.value) {
            sheetHeight.value = product.default_height || '';
        }
    }

    function findMaterial(materialList, materialId) {
        return materialList.find(function (material) {
            return String(material.id) === String(materialId);
        });
    }

    function fillMaterialInputs(selectElement, rateElement, weightElement, materialList, forceFill) {
        const material = findMaterial(materialList, selectElement.value);
        if (!material) {
            return;
        }

        if (forceFill || !rateElement.value) {
            rateElement.value = material.rate || '';
        }
        if (forceFill || !weightElement.value) {
            weightElement.value = material.weight || '';
        }
    }

    function appendCustomerData(customer) {
        customerBrands[customer.id] = Array.isArray(customer.brand_names) ? customer.brand_names : [];
        upsertOption(customerSelect, customer.id, customer.contact_name);
        customerSelect.value = String(customer.id);
        if (typeof refreshSelect2Dropdown === 'function') {
            refreshSelect2Dropdown(customerSelect);
        }

        const selectedBrand = customer.brand_names && customer.brand_names.length ? customer.brand_names[0] : '';
        brandSelect.dataset.selectedBrand = selectedBrand;
        populateBrandOptions(customer.id, selectedBrand);
    }

    function appendProductData(product) {
        products[product.id] = product;
        upsertOption(productSelect, product.id, product.name);
        productSelect.value = String(product.id);
        applyProductDefaults(product.id, true);
    }

    function appendMaterialData(material, context) {
        const materialPayload = {
            id: parseInt(material.id, 10) || 0,
            name: material.name,
            rate: parseNumber(material.rate),
            weight: parseNumber(material.weight),
            material_type_name: material.material_type_name
        };

        const normalizedType = String(material.material_type_name || '').trim().toLowerCase();
        const effectiveContext = normalizedType === 'duplex' ? 'duplex' : (normalizedType === 'liner' ? 'liner' : context);

        if (normalizedType === 'liner') {
            linerMaterials.push(materialPayload);
            upsertOption(linerMaterialSelect, materialPayload.id, materialPayload.name);
        }

        if (normalizedType === 'duplex') {
            duplexMaterials.push(materialPayload);
            upsertOption(duplexMaterialSelect, materialPayload.id, materialPayload.name);
        }

        if (effectiveContext === 'liner') {
            upsertOption(linerMaterialSelect, materialPayload.id, materialPayload.name);
            linerMaterialSelect.value = String(materialPayload.id);
            fillMaterialInputs(linerMaterialSelect, linerRateInput, linerWeightInput, linerMaterials, true);
        }

        if (effectiveContext === 'duplex') {
            upsertOption(duplexMaterialSelect, materialPayload.id, materialPayload.name);
            duplexMaterialSelect.value = String(materialPayload.id);
            fillMaterialInputs(duplexMaterialSelect, duplexRateInput, duplexWeightInput, duplexMaterials, true);
            recalculateTotals();
        }
    }

    function renderLinerItems() {
        if (!linerItems.length) {
            linerTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No liner added yet.</td></tr>';
        } else {
            linerTableBody.innerHTML = linerItems.map(function (item, index) {
                return `
                    <tr>
                        <td>${item.name}</td>
                        <td>${formatValue(item.rate)}</td>
                        <td>${formatValue(item.weight)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-liner-item" data-index="${index}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        linerItemsJson.value = JSON.stringify(linerItems);
        const linerTotal = linerItems.reduce(function (sum, item) {
            return sum + parseNumber(item.amount);
        }, 0);
        linerRateField.value = formatValue(linerTotal);
        recalculateTotals();
    }

    function renderDuplexItems() {
        if (!duplexItems.length) {
            duplexTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No duplex added yet.</td></tr>';
        } else {
            duplexTableBody.innerHTML = duplexItems.map(function (item, index) {
                return `
                    <tr>
                        <td>${item.name}</td>
                        <td>${formatValue(item.rate)}</td>
                        <td>${formatValue(item.weight)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-duplex-item" data-index="${index}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        duplexItemsJson.value = JSON.stringify(duplexItems);
        const duplexTotal = duplexItems.reduce(function (sum, item) {
            return sum + parseNumber(item.amount);
        }, 0);
        duplexRateField.value = formatValue(duplexTotal);
        recalculateTotals();
    }

    function recalculateTotals() {
        const duplexAmount = parseNumber(duplexRateField.value);
        const linerAmount = parseNumber(linerRateField.value);
        const printing = parseNumber(document.getElementById('printing').value);
        const laminas = parseNumber(document.getElementById('laminas_value').value);
        const pesting = parseNumber(document.getElementById('pesting').value);
        const punching = parseNumber(document.getElementById('punching').value);
        const pinAmount = roundValue(parseNumber(document.getElementById('pin_rate').value) * parseNumber(document.getElementById('pin_qty').value));
        const sidePesting = parseNumber(document.getElementById('side_pesting').value);
        const uvCoating = parseNumber(document.getElementById('uv_coating').value);
        const rixaBhadu = parseNumber(document.getElementById('rixa_bhadu').value);

        const total = roundValue(linerAmount + duplexAmount + printing + laminas + pesting + punching + pinAmount + sidePesting + uvCoating + rixaBhadu);
        totalField.value = formatValue(total);

        const upps = Math.max(parseNumber(uppsField.value), 1);
        if (parseNumber(uppsField.value) <= 0) {
            uppsField.value = '1';
        }

        const singleRate = roundValue(total / upps);
        singleRateField.value = formatValue(singleRate);

        let profit = parseNumber(profitField.value);
        let profitPercent = parseNumber(profitPercentField.value);
        if (profitSyncMode === 'percentage') {
            profit = singleRate > 0 ? roundValue((singleRate * profitPercent) / 100) : 0;
            profitField.value = formatValue(profit);
        } else {
            profitPercent = singleRate > 0 ? roundValue((profit / singleRate) * 100) : 0;
            profitPercentField.value = formatValue(profitPercent);
        }

        saleRateField.value = formatValue(singleRate + profit);
    }

    try {
        linerItems = JSON.parse(linerItemsJson.value || '[]');
        duplexItems = JSON.parse(duplexItemsJson.value || '[]');
    } catch (error) {
        linerItems = [];
        duplexItems = [];
    }

    if (profitPercentField.value && parseNumber(profitPercentField.value) > 0 && parseNumber(profitField.value) <= 0) {
        profitSyncMode = 'percentage';
    }

    populateBrandOptions(customerSelect.value, brandSelect.dataset.selectedBrand || brandSelect.value);
    renderLinerItems();
    renderDuplexItems();
    if (productSelect.value) {
        applyProductDefaults(productSelect.value, false);
    }
    recalculateTotals();

    openMaterialModalButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            activeMaterialContext = this.dataset.materialContext || 'liner';
            materialContextField.value = activeMaterialContext;
            const materialTypeName = this.dataset.materialTypeName || '';
            materialQuickAddTitle.textContent = `Add ${materialTypeName || 'Material'}`;
            resetQuickForm(materialQuickAddForm);
            setModalMaterialTypeByName(materialTypeName);
        });
    });

    if (customerQuickAddForm) {
        customerQuickAddForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!this.checkValidity()) {
                this.classList.add('was-validated');
                return;
            }

            const submitButton = document.getElementById('customerQuickAddSubmit');
            toggleSubmitButton(submitButton, true, 'Saving...');

            const formData = new FormData(this);
            formData.append('action', 'create_customer_inline');

            fetch('ajax.php', {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        handleAjaxError(data.message || 'Customer save failed.');
                        return;
                    }

                    appendCustomerData(data.customer);
                    customerModalInstance.hide();
                })
                .catch(function (error) {
                    handleAjaxError('An unexpected error occurred.');
                })
                .finally(function () {
                    toggleSubmitButton(submitButton, false);
                });
        });
    }

    if (brandQuickAddForm) {
        brandQuickAddForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const customerId = customerSelect.value;
            if (!customerId) {
                handleAjaxError('Please select a customer first.');
                return;
            }

            if (!this.checkValidity()) {
                this.classList.add('was-validated');
                return;
            }

            const submitButton = document.getElementById('brandQuickAddSubmit');
            toggleSubmitButton(submitButton, true, 'Saving...');

            const formData = new FormData(this);
            formData.append('action', 'create_brand_inline');
            formData.append('customer_id', customerId);

            fetch('ajax.php', {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        handleAjaxError(data.message || 'Brand save failed.');
                        return;
                    }

                    // Update local customerBrandsMap
                    if (data.customer_id && data.all_brands) {
                        customerBrands[data.customer_id] = data.all_brands;
                    }

                    // Repopulate brand options and select the new one
                    populateBrandOptions(data.customer_id, data.brand_name);
                    brandModalInstance.hide();
                    resetQuickForm(brandQuickAddForm);

                    if (typeof showToast === 'function') {
                        showToast('Success', data.message || 'Brand added successfully.', 'success');
                    }
                })
                .catch(function (error) {
                    handleAjaxError('An unexpected error occurred.');
                })
                .finally(function () {
                    toggleSubmitButton(submitButton, false);
                });
        });
    }

    if (productQuickAddForm) {
        productQuickAddForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!this.checkValidity()) {
                this.classList.add('was-validated');
                return;
            }

            const submitButton = document.getElementById('productQuickAddSubmit');
            toggleSubmitButton(submitButton, true, 'Saving...');

            const formData = new FormData(this);
            formData.append('action', 'create_product_inline');

            fetch('ajax.php', {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        handleAjaxError(data.message || 'Product save failed.');
                        return;
                    }

                    appendProductData(data.product);
                    productModalInstance.hide();
                    resetQuickForm(productQuickAddForm);
                    if (typeof showToast === 'function') {
                        showToast('Success', data.message || 'Product added successfully.', 'success');
                    }
                })
                .catch(function () {
                    handleAjaxError('Unable to save product right now.');
                })
                .finally(function () {
                    toggleSubmitButton(submitButton, false);
                });
        });
    }

    if (materialQuickAddForm) {
        materialQuickAddForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!this.checkValidity()) {
                this.classList.add('was-validated');
                return;
            }

            const submitButton = document.getElementById('materialQuickAddSubmit');
            toggleSubmitButton(submitButton, true, 'Saving...');

            const formData = new FormData(this);
            formData.append('action', 'create_material_inline');

            fetch('ajax.php', {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        handleAjaxError(data.message || 'Material save failed.');
                        return;
                    }

                    appendMaterialData(data.material, materialContextField.value || activeMaterialContext);
                    materialModalInstance.hide();
                    resetQuickForm(materialQuickAddForm);
                    if (typeof showToast === 'function') {
                        showToast('Success', data.message || 'Material added successfully.', 'success');
                    }
                })
                .catch(function () {
                    handleAjaxError('Unable to save material right now.');
                })
                .finally(function () {
                    toggleSubmitButton(submitButton, false);
                });
        });
    }

    jQuery(customerSelect).on('change', function () {
        brandSelect.dataset.selectedBrand = '';
        populateBrandOptions(this.value, '');
    });

    jQuery(brandSelect).on('change', function () {
        brandSelect.dataset.selectedBrand = this.value;
    });

    jQuery(productSelect).on('change', function () {
        applyProductDefaults(this.value, true);
    });

    jQuery(linerMaterialSelect).on('change', function () {
        fillMaterialInputs(linerMaterialSelect, linerRateInput, linerWeightInput, linerMaterials, true);
    });

    addLinerButton.addEventListener('click', function () {
        const selectedOption = linerMaterialSelect.options[linerMaterialSelect.selectedIndex];
        if (!linerMaterialSelect.value || !selectedOption || !selectedOption.text) {
            alert('Please select a liner.');
            return;
        }

        const rate = parseNumber(linerRateInput.value);
        const weight = parseNumber(linerWeightInput.value);

        linerItems.push({
            material_id: parseInt(linerMaterialSelect.value, 10) || 0,
            name: selectedOption.text.trim(),
            rate: roundValue(rate),
            weight: roundValue(weight),
            amount: roundValue(rate * weight)
        });

        linerMaterialSelect.value = '';
        linerRateInput.value = '';
        linerWeightInput.value = '';
        renderLinerItems();
    });

    linerTableBody.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-liner-item');
        if (!removeButton) {
            return;
        }

        const itemIndex = parseInt(removeButton.dataset.index, 10);
        linerItems.splice(itemIndex, 1);
        renderLinerItems();
    });

    jQuery(duplexMaterialSelect).on('change', function () {
        fillMaterialInputs(duplexMaterialSelect, duplexRateInput, duplexWeightInput, duplexMaterials, true);
    });

    addDuplexButton.addEventListener('click', function () {
        const selectedOption = duplexMaterialSelect.options[duplexMaterialSelect.selectedIndex];
        if (!duplexMaterialSelect.value || !selectedOption || !selectedOption.text) {
            alert('Please select a duplex.');
            return;
        }

        const rate = parseNumber(duplexRateInput.value);
        const weight = parseNumber(duplexWeightInput.value);

        duplexItems.push({
            material_id: parseInt(duplexMaterialSelect.value, 10) || 0,
            name: selectedOption.text.trim(),
            rate: roundValue(rate),
            weight: roundValue(weight),
            amount: roundValue(rate * weight)
        });

        duplexMaterialSelect.value = '';
        duplexRateInput.value = '';
        duplexWeightInput.value = '';
        renderDuplexItems();
    });

    duplexTableBody.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-duplex-item');
        if (!removeButton) {
            return;
        }

        const itemIndex = parseInt(removeButton.dataset.index, 10);
        duplexItems.splice(itemIndex, 1);
        renderDuplexItems();
    });

    [duplexRateInput, duplexWeightInput, uppsField].concat(moneyInputs).forEach(function (input) {
        if (input) {
            input.addEventListener('input', recalculateTotals);
        }
    });

    profitField.addEventListener('input', function () {
        profitSyncMode = 'amount';
        recalculateTotals();
    });

    profitPercentField.addEventListener('input', function () {
        profitSyncMode = 'percentage';
        recalculateTotals();
    });

    document.addEventListener('click', function (e) {
        // Add More Brand in Modal
        if (e.target.closest('#addModalBrandBtn')) {
            const container = document.getElementById('modalBrandRepeater');
            if (container) {
                const html = `
                    <div class="input-group input-group-sm mb-2 modal-brand-item">
                        <span class="input-group-text"><i class="bi bi-tag"></i></span>
                        <input type="text" name="brand_names[]" class="form-control" placeholder="Brand Name">
                        <button type="button" class="btn btn-danger removeModalBrand"><i class="bi bi-trash"></i></button>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', html);
            }
        }

        // Remove Brand in Modal
        if (e.target.closest('.removeModalBrand')) {
            const item = e.target.closest('.modal-brand-item');
            const container = document.getElementById('modalBrandRepeater');
            if (container && container.querySelectorAll('.modal-brand-item').length > 1) {
                item.remove();
            } else if (item) {
                const input = item.querySelector('input');
                if (input) input.value = '';
            }
        }
    });
});
</script>
<?php } ?>

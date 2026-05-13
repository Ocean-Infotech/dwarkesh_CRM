<?php
$pageTitle = "Orders";
$currentPage = "orders";
$headerTitle = "Manage Orders";

$extraHead = '<link rel="stylesheet" href="assets/css/orders.css">';

include 'include/header.php';
require_once 'root/schema_bootstrap.php';
dwarkesh_ensure_core_tables($ai_db);

$table = "tbl_orders";
$redirection_url = "orders.php";

$mode = $_REQUEST['mode'] ?? '';
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$data = null;
$error = '';

// Fetch master data
$customers = $ai_db->aiGetQuery("SELECT id, contact_name, phone_no, brand_names FROM tbl_customer WHERE status='active' AND is_deleted=0 ORDER BY contact_name ASC");
$products = $ai_db->aiGetQuery("SELECT id, customer_id, name, stock_qty, default_length, default_width, default_height FROM tbl_product WHERE status='active' AND is_deleted=0 AND customer_id IS NOT NULL AND customer_id > 0 ORDER BY name ASC");
$costings = $ai_db->aiGetQuery("SELECT id, estimate_no, customer_id FROM tbl_costings WHERE is_deleted=0 ORDER BY id DESC");
$materialTypes = $ai_db->aiGetQuery("SELECT id, name FROM tbl_material_type WHERE status='active' AND is_deleted=0 ORDER BY name ASC");

// Fixed queries with JOINs
$liners = $ai_db->aiGetQuery("SELECT m.id, m.name, m.rate, m.weight FROM tbl_materials m JOIN tbl_material_type mt ON m.material_type_id = mt.id WHERE mt.name='Liner' AND m.status='active' AND m.is_deleted=0");
$duplexes = $ai_db->aiGetQuery("SELECT m.id, m.name, m.rate, m.weight FROM tbl_materials m JOIN tbl_material_type mt ON m.material_type_id = mt.id WHERE mt.name='Duplex' AND m.status='active' AND m.is_deleted=0");
$offsets = $ai_db->aiGetQuery("SELECT id, name, contact_number FROM tbl_offset WHERE status='active' AND is_deleted=0 ORDER BY name ASC");
$laminations = $ai_db->aiGetQuery("SELECT id, name, contact_number FROM tbl_lamination WHERE status='active' AND is_deleted=0 ORDER BY name ASC");

// Helper to clean brand names from JSON or array
function clean_brand_names($value)
{
    $brands = [];
    if (is_string($value) && $value !== '') {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $brands = $decoded;
        } else {
            $splitBrands = preg_split('/[\r\n,]+/', $value);
            $brands = is_array($splitBrands) ? $splitBrands : [$value];
        }
    } elseif (is_array($value)) {
        $brands = $value;
    }
    $cleaned = [];
    foreach ($brands as $brand) {
        $brand = trim((string) $brand);
        if ($brand !== '')
            $cleaned[] = $brand;
    }
    return array_values(array_unique($cleaned));
}

function parse_order_items_json($rawJson)
{
    $decoded = json_decode((string) $rawJson, true);
    if (!is_array($decoded)) {
        return [];
    }

    $items = [];
    foreach ($decoded as $item) {
        if (!is_array($item)) {
            continue;
        }

        $material_id = intval($item['material_id'] ?? 0);
        $name = trim((string) ($item['name'] ?? ''));
        $rate = is_numeric($item['rate'] ?? null) ? round((float) $item['rate'], 2) : 0;
        $qty = is_numeric($item['qty'] ?? null) ? round((float) $item['qty'], 2) : 0;

        if ($name === '' && $material_id <= 0) {
            continue;
        }

        $items[] = [
            'material_id' => $material_id,
            'name' => $name,
            'rate' => $rate,
            'qty' => $qty
        ];
    }

    return $items;
}

$customerBrandsMap = [];
foreach ($customers as $c) {
    $customerBrandsMap[$c['id']] = clean_brand_names($c['brand_names']);
}

$order_columns = [];
$order_columns_raw = $ai_db->aiGetQuery("SHOW COLUMNS FROM $table");
foreach ((array) $order_columns_raw as $col) {
    $field_name = strtolower((string) ($col['Field'] ?? ''));
    if ($field_name !== '') {
        $order_columns[$field_name] = true;
    }
}

// Columns are now ensured via dwarkesh_ensure_core_tables in schema_bootstrap.php

$order_items_columns = [];
$order_items_columns_raw = $ai_db->aiGetQuery("SHOW COLUMNS FROM tbl_orders_item");
foreach ((array) $order_items_columns_raw as $col) {
    $field_name = strtolower((string) ($col['Field'] ?? ''));
    if ($field_name !== '') {
        $order_items_columns[$field_name] = true;
    }
}

$save_order_items = function ($order_id, $liner_items, $duplex_items) use ($ai_db, $order_items_columns) {
    $order_id = intval($order_id);
    if ($order_id <= 0) {
        return;
    }

    $ai_db->aiQuery("DELETE FROM tbl_orders_item WHERE order_id='" . $order_id . "'");

    $all_items = [];
    foreach ($liner_items as $item) {
        $item['item_group'] = 'liner';
        $all_items[] = $item;
    }
    foreach ($duplex_items as $item) {
        $item['item_group'] = 'duplex';
        $all_items[] = $item;
    }

    foreach ($all_items as $item) {
        $material_id = intval($item['material_id'] ?? 0);
        $material_name = addslashes((string) ($item['name'] ?? ''));
        $rate = is_numeric($item['rate'] ?? null) ? round((float) $item['rate'], 2) : 0;
        $qty = is_numeric($item['qty'] ?? null) ? round((float) $item['qty'], 2) : 0;
        $item_group = addslashes((string) ($item['item_group'] ?? 'other'));

        $field_parts = [];
        $field_parts[] = "order_id='" . $order_id . "'";

        if (isset($order_items_columns['item_group'])) {
            $field_parts[] = "item_group='" . $item_group . "'";
        }
        if (isset($order_items_columns['material_id'])) {
            $field_parts[] = "material_id='" . $material_id . "'";
        }
        if (isset($order_items_columns['material_name'])) {
            $field_parts[] = "material_name='" . $material_name . "'";
        } elseif (isset($order_items_columns['name'])) {
            $field_parts[] = "name='" . $material_name . "'";
        }
        if (isset($order_items_columns['rate'])) {
            $field_parts[] = "rate='" . $rate . "'";
        }
        if (isset($order_items_columns['qty'])) {
            $field_parts[] = "qty='" . $qty . "'";
        } elseif (isset($order_items_columns['pcs'])) {
            $field_parts[] = "pcs='" . $qty . "'";
        }

        if (isset($order_items_columns['created_by'])) {
            $field_parts[] = "created_by='" . intval($_SESSION['aid'] ?? 0) . "'";
        }

        $ai_db->aiQuery("INSERT INTO tbl_orders_item SET " . implode(", ", $field_parts));
    }
};

$update_bom_stock = function ($product_id, $qty, $action_type, $remarks) use ($ai_db) {
    $product_id = intval($product_id);
    $qty = floatval($qty);
    if ($product_id <= 0 || $qty == 0)
        return;

    // 2. Update BOM/Material Stock
    // Check if BOM exists for this product
    $bom_exists = $ai_db->aiGetQuery("SELECT id FROM tbl_product_bom WHERE product_id = $product_id AND is_deleted = 0 LIMIT 1");

    if (!empty($bom_exists)) {
        // Handle BOM Items
        $bom_items = $ai_db->aiGetQuery("SELECT material_name, qty FROM tbl_product_bom WHERE product_id = $product_id AND is_deleted = 0");
        foreach ((array) $bom_items as $bom) {
            $m_name = addslashes($bom['material_name']);
            $bom_qty_per_unit = floatval($bom['qty']);
            $total_m_qty = $bom_qty_per_unit * abs($qty);

            $m_res = $ai_db->aiGetQuery("SELECT id FROM tbl_materials WHERE name = '$m_name' AND is_deleted = 0 LIMIT 1");
            if (!empty($m_res)) {
                $m_id = $m_res[0]['id'];
                $op = ($action_type === 'minus') ? '-' : '+';
                $ai_db->aiQuery("UPDATE tbl_materials SET stock_qty = stock_qty $op $total_m_qty WHERE id = '$m_id'");
                $ai_db->aiQuery("INSERT INTO tbl_stock_history SET item_type='material', item_id='$m_id', qty='$total_m_qty', action_type='$action_type', remarks='$remarks', created_by='" . ($_SESSION['aid'] ?? 0) . "'");
            }
        }
    } else {
        // Legacy Primary Mapped Material Fallback
        $prod = $ai_db->aiGetQuery("SELECT mapped_material_id, usage_qty FROM tbl_product WHERE id = $product_id LIMIT 1");
        if (!empty($prod) && intval($prod[0]['mapped_material_id'] ?? 0) > 0 && floatval($prod[0]['usage_qty'] ?? 0) > 0) {
            $m_id = intval($prod[0]['mapped_material_id']);
            $usage = floatval($prod[0]['usage_qty']);
            $total_m_qty = $usage * abs($qty);
            $op = ($action_type === 'minus') ? '-' : '+';
            $ai_db->aiQuery("UPDATE tbl_materials SET stock_qty = stock_qty $op $total_m_qty WHERE id = '$m_id'");
            $ai_db->aiQuery("INSERT INTO tbl_stock_history SET item_type='material', item_id='$m_id', qty='$total_m_qty', action_type='$action_type', remarks='$remarks (Primary)', created_by='" . ($_SESSION['aid'] ?? 0) . "'");
        }
    }
};

if (isset($_POST['btn_submit'])) {
    $order_no = trim($_POST['order_no'] ?? '');
    $order_no = ltrim($order_no, '#');
    $order_date = $_POST['order_date'] ?? date('Y-m-d');
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $brand_name = trim($_POST['brand_name'] ?? '');
    $product_id = intval($_POST['product_id'] ?? 0);
    $box_qty = floatval($_POST['box_qty'] ?? 0);
    $upps = floatval($_POST['upps'] ?? 0);
    $rate = floatval($_POST['rate'] ?? 0);
    $costing_id = intval($_POST['costing_id'] ?? 0);
    $sheet_length = floatval($_POST['sheet_length'] ?? 0);
    $sheet_width = floatval($_POST['sheet_width'] ?? 0);
    $liner_delivery_id = intval($_POST['liner_delivery_id'] ?? 0);
    $liner_delivery_phone = trim($_POST['liner_delivery_phone'] ?? '');
    $top_count = trim($_POST['top_count'] ?? '');
    $duplex_delivery_id = intval($_POST['duplex_delivery_id'] ?? 0);
    $duplex_delivery_phone = trim($_POST['duplex_delivery_phone'] ?? '');
    $liner_items = parse_order_items_json($_POST['liner_items_json'] ?? '[]');
    $duplex_items = parse_order_items_json($_POST['duplex_items_json'] ?? '[]');
    $existing_offset_image = '';
    if ($mode === 'edit' && $id > 0) {
        $existingImageRes = $ai_db->aiGetQuery("SELECT offset_image FROM $table WHERE id='$id' LIMIT 1");
        $existing_offset_image = (string) ($existingImageRes[0]['offset_image'] ?? '');
    }
    $offset_image_path = $existing_offset_image;

    if ($customer_id === 0 || $box_qty <= 0) {
        $error = "Please fill all required fields.";
    } elseif ($mode === 'add') {
        $duplicate_order = $ai_db->aiGetQuery("SELECT id FROM $table WHERE order_no='" . addslashes($order_no) . "' AND is_deleted=0 LIMIT 1");
        if (!empty($duplicate_order)) {
            $error = "Order No already exists.";
        }
    }

    // Stock Validation Backend (Material Only)
    if (empty($error)) {
        $original_box_qty = 0;
        if ($mode === 'edit') {
            $old_order_res = $ai_db->aiGetQuery("SELECT box_qty, product_id FROM $table WHERE id='" . intval($id) . "' LIMIT 1");
            if (!empty($old_order_res) && intval($old_order_res[0]['product_id']) === intval($product_id)) {
                $original_box_qty = floatval($old_order_res[0]['box_qty'] ?? 0);
            }
        }
        $qty_to_check = $box_qty - $original_box_qty;

        if ($qty_to_check > 0) {
            $materials_to_validate = [];
            // 1. Try to get BOM Items
                $bom_items = $ai_db->aiGetQuery("SELECT material_name, qty FROM tbl_product_bom WHERE product_id = " . intval($product_id) . " AND is_deleted = 0");

                if (!empty($bom_items)) {
                    foreach ((array) $bom_items as $bom) {
                        $materials_to_validate[] = [
                            'name' => $bom['material_name'],
                            'needed' => floatval($bom['qty']) * $qty_to_check
                        ];
                    }
                } else {
                    // 2. Fallback ONLY if the product has NO BOM items at all (not even deleted ones)
                    $has_bom_def = $ai_db->aiGetQuery("SELECT id FROM tbl_product_bom WHERE product_id = " . intval($product_id) . " LIMIT 1");
                    if (empty($has_bom_def)) {
                        $prod_mapped = $ai_db->aiGetQuery("SELECT mapped_material_id, usage_qty FROM tbl_product WHERE id = " . intval($product_id) . " LIMIT 1");
                        if (!empty($prod_mapped) && intval($prod_mapped[0]['mapped_material_id'] ?? 0) > 0) {
                            $m_id = intval($prod_mapped[0]['mapped_material_id']);
                            $usage = floatval($prod_mapped[0]['usage_qty'] ?? 0);
                            $m_res = $ai_db->aiGetQuery("SELECT name FROM tbl_materials WHERE id = $m_id AND is_deleted = 0 LIMIT 1");
                            if (!empty($m_res)) {
                                $materials_to_validate[] = [
                                    'name' => $m_res[0]['name'],
                                    'needed' => $usage * $qty_to_check
                                ];
                            }
                        }
                    }
                }

                // 3. Perform Validation for all identified materials
                foreach ($materials_to_validate as $mat) {
                    $m_name_escaped = addslashes($mat['name']);
                    $m_res = $ai_db->aiGetQuery("SELECT name, stock_qty FROM tbl_materials WHERE name = '$m_name_escaped' AND is_deleted = 0 LIMIT 1");

                    if (!empty($m_res)) {
                        $available = floatval($m_res[0]['stock_qty'] ?? 0);
                        if ($mat['needed'] > $available) {
                            $error = "Insufficient material stock for " . htmlspecialchars($m_res[0]['name']) . ". Needed: " . number_format($mat['needed'], 2) . ", Available: " . number_format($available, 2);
                            break;
                        }
                    }
            }
        }
    }

    if (empty($error) && isset($_FILES['offset_image']) && intval($_FILES['offset_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if (intval($_FILES['offset_image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $error = "Offset image upload failed. Please try again.";
        } else {
            $upload_dir = 'assets/uploads/orders/offset';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0777, true);
            }

            $original_name = (string) ($_FILES['offset_image']['name'] ?? '');
            $tmp_file = (string) ($_FILES['offset_image']['tmp_name'] ?? '');
            $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

            if (!in_array($extension, $allowed_extensions, true)) {
                $error = "Only JPG, JPEG, PNG, WEBP, and PDF files are allowed.";
            } elseif (!is_uploaded_file($tmp_file)) {
                $error = "Invalid uploaded file.";
            } else {
                $new_file_name = 'offset_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $extension;
                $target_path = $upload_dir . '/' . $new_file_name;
                if (move_uploaded_file($tmp_file, $target_path)) {
                    $offset_image_path = $target_path;
                } else {
                    $error = "Unable to save uploaded image.";
                }
            }
        }
    }

    if (empty($error)) {
        $custRow = $ai_db->aiGetQuery("SELECT contact_name FROM tbl_customer WHERE id='$customer_id' LIMIT 1");
        $prodRow = $ai_db->aiGetQuery("SELECT name FROM tbl_product WHERE id='$product_id' LIMIT 1");
        $customer_name = $custRow[0]['contact_name'] ?? '';
        $product_name = $prodRow[0]['name'] ?? '';

        $sql_parts = [];
        $set_sql_field = function ($field, $value) use (&$sql_parts, $order_columns) {
            if (!isset($order_columns[strtolower($field)])) {
                return;
            }
            $sql_parts[] = $field . "='" . $value . "'";
        };

        $set_sql_field('order_no', addslashes($order_no));
        $set_sql_field('order_date', addslashes($order_date));
        $set_sql_field('customer_id', intval($customer_id));
        $set_sql_field('customer_name', addslashes($customer_name));
        $set_sql_field('brand_name', addslashes($brand_name));
        $set_sql_field('product_id', intval($product_id));
        $set_sql_field('product_name', addslashes($product_name));
        $set_sql_field('box_qty', $box_qty);
        $set_sql_field('box_qty_unit', addslashes($_POST['box_qty_unit'] ?? 'PCS'));
        $set_sql_field('upps', $upps);
        $set_sql_field('rate', $rate);
        $set_sql_field('costing_id', $costing_id);
        $set_sql_field('sheet_length', $sheet_length);
        $set_sql_field('sheet_width', $sheet_width);
        $set_sql_field('md_code', addslashes($_POST['md_code'] ?? ''));
        $set_sql_field('plate_status', addslashes($_POST['plate_status'] ?? 'No'));
        $set_sql_field('print_status', addslashes($_POST['print_status'] ?? 'No'));
        $set_sql_field('die_status', addslashes($_POST['die_status'] ?? 'No'));

        $set_sql_field('liner_delivery_id', $liner_delivery_id);
        $set_sql_field('liner_delivery_phone', addslashes($liner_delivery_phone));
        $set_sql_field('top_count', addslashes($top_count));
        $set_sql_field('duplex_delivery_id', $duplex_delivery_id);
        $set_sql_field('duplex_delivery_phone', addslashes($duplex_delivery_phone));

        $set_sql_field('printing_by_id', intval($_POST['printing_by_id'] ?? 0));
        $set_sql_field('offset_image', addslashes($offset_image_path));
        $set_sql_field('print_color', addslashes($_POST['print_color'] ?? ''));
        $set_sql_field('print_qty', addslashes($_POST['print_qty'] ?? ''));
        $set_sql_field('print_delivery_id', intval($_POST['print_delivery_id'] ?? 0));
        $set_sql_field('print_delivery_phone', addslashes($_POST['print_delivery_phone'] ?? ''));

        $set_sql_field('die_maker', addslashes($_POST['die_maker'] ?? ''));
        $set_sql_field('die_code', addslashes($_POST['die_code'] ?? ''));
        $set_sql_field('c_die_code', addslashes($_POST['c_die_code'] ?? ''));
        $set_sql_field('designer', addslashes($_POST['designer'] ?? ''));
        $set_sql_field('plate', addslashes($_POST['plate'] ?? ''));
        $set_sql_field('half_film', isset($_POST['half_film']) ? 1 : 0);
        $set_sql_field('full_film', isset($_POST['full_film']) ? 1 : 0);
        $set_sql_field('lamination_type', addslashes($_POST['lamination_type'] ?? ''));
        $set_sql_field('lamination_extra', addslashes($_POST['lamination_extra'] ?? ''));
        $set_sql_field('laminas_delivery_id', intval($_POST['laminas_delivery_id'] ?? 0));
        $set_sql_field('laminas_delivery_phone', addslashes($_POST['laminas_delivery_phone'] ?? ''));

        $set_sql_field('job_pesting', isset($_POST['job_pesting']) ? 1 : 0);
        $set_sql_field('job_pin', isset($_POST['job_pin']) ? 1 : 0);
        $set_sql_field('job_punching', isset($_POST['job_punching']) ? 1 : 0);
        $set_sql_field('job_side_pesting', isset($_POST['job_side_pesting']) ? 1 : 0);

        $set_sql_field('bill_design', addslashes($_POST['bill_design'] ?? ''));
        $set_sql_field('bill_plate', addslashes($_POST['bill_plate'] ?? ''));
        $set_sql_field('bill_daei', addslashes($_POST['bill_daei'] ?? ''));
        $set_sql_field('bill_photo_price', addslashes($_POST['bill_photo_price'] ?? ''));
        $set_sql_field('bill_pcs', addslashes($_POST['bill_pcs'] ?? ''));
        $set_sql_field('bill_rixa_bhadu', addslashes($_POST['bill_rixa_bhadu'] ?? ''));
        $set_sql_field('bill_borrow_charge', addslashes($_POST['bill_borrow_charge'] ?? ''));
        $set_sql_field('bill_remark', addslashes($_POST['bill_remark'] ?? ''));

        $sql_fields = implode(",\n                ", $sql_parts);

        if ($mode === 'add') {
            $ai_db->aiQuery("INSERT INTO $table SET $sql_fields, created_by='" . ($_SESSION['aid'] ?? 0) . "'");
            $new_order_id = intval($ai_db->aiLastInsert());
            $save_order_items($new_order_id, $liner_items, $duplex_items);

            // Update BOM Material Stock
            $update_bom_stock($product_id, $box_qty, 'minus', "Order Placed: #$order_no");

            $ai_core->aiGoPage($redirection_url . "?msg=1");
            exit;
        } elseif ($mode === 'edit') {
            $old_order_res = $ai_db->aiGetQuery("SELECT product_id, box_qty, order_no FROM $table WHERE id='$id' LIMIT 1");
            $old_product_id = intval($old_order_res[0]['product_id'] ?? 0);
            $old_box_qty = floatval($old_order_res[0]['box_qty'] ?? 0);
            $old_order_no = $old_order_res[0]['order_no'] ?? '';

            $ai_db->aiQuery("UPDATE $table SET $sql_fields, updated_by='" . ($_SESSION['aid'] ?? 0) . "' WHERE id='$id'");
            $save_order_items($id, $liner_items, $duplex_items);

            // Update BOM Stock
            if ($old_product_id == $product_id) {
                $diff = $box_qty - $old_box_qty;
                if ($diff != 0) {
                    $action = ($diff > 0) ? 'minus' : 'plus';
                    $abs_diff = abs($diff);
                    $update_bom_stock($product_id, $abs_diff, $action, "Order Edited: #$order_no (Qty Changed)");
                }
            } else {
                // Revert old product BOM
                $update_bom_stock($old_product_id, $old_box_qty, 'plus', "Order Product Changed: #$order_no (Old Product Revert)");
                // Deduct new product BOM
                $update_bom_stock($product_id, $box_qty, 'minus', "Order Product Changed: #$order_no (New Product Deduction)");
            }

            $ai_core->aiGoPage($redirection_url . "?msg=2");
            exit;
        }
    }

    $data = array_merge((array) $data, $_POST);
    $data['offset_image'] = $offset_image_path;
    $data['liner_items_json'] = json_encode($liner_items, JSON_UNESCAPED_UNICODE);
    $data['duplex_items_json'] = json_encode($duplex_items, JSON_UNESCAPED_UNICODE);
}

if ($mode === 'delete' && $id > 0) {
    $order_res = $ai_db->aiGetQuery("SELECT product_id, box_qty, order_no FROM $table WHERE id='$id' LIMIT 1");
    if (!empty($order_res)) {
        $p_id = intval($order_res[0]['product_id']);
        $b_qty = floatval($order_res[0]['box_qty']);
        $o_no = $order_res[0]['order_no'];

        // Revert BOM Material Stock
        $update_bom_stock($p_id, $b_qty, 'plus', "Order Deleted: #$o_no");
    }
    $ai_db->aiQuery("UPDATE $table SET is_deleted=1 WHERE id='$id'");
    $ai_core->aiGoPage($redirection_url . "?msg=3");
    exit;
}

if ($mode === 'edit' && $id > 0) {
    $res = $ai_db->aiGetQuery("SELECT * FROM $table WHERE id='$id' LIMIT 1");
    $data = $res[0] ?? null;
    if (is_array($data)) {
        $itemRows = $ai_db->aiGetQuery("SELECT * FROM tbl_orders_item WHERE order_id='" . intval($id) . "' ORDER BY id ASC");
        $liner_items = [];
        $duplex_items = [];

        foreach ((array) $itemRows as $itemRow) {
            $item = [
                'material_id' => intval($itemRow['material_id'] ?? 0),
                'name' => (string) ($itemRow['material_name'] ?? ($itemRow['name'] ?? '')),
                'rate' => (float) ($itemRow['rate'] ?? 0),
                'qty' => (float) ($itemRow['qty'] ?? ($itemRow['pcs'] ?? 0))
            ];

            $group = strtolower((string) ($itemRow['item_group'] ?? ''));
            if ($group === 'duplex') {
                $duplex_items[] = $item;
            } else {
                $liner_items[] = $item;
            }
        }

        $data['liner_items_json'] = json_encode($liner_items, JSON_UNESCAPED_UNICODE);
        $data['duplex_items_json'] = json_encode($duplex_items, JSON_UNESCAPED_UNICODE);
    }
}

$filters = [];
$hasActiveFilters = false;
$filter_from_date = '';
$filter_to_date = '';

if (!$mode) {
    $filterSessionKey = 'orders_filters';
    if (isset($_GET['action']) && $_GET['action'] === 'clear_filters') {
        unset($_SESSION[$filterSessionKey]);
        $ai_core->aiGoPage('orders.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION[$filterSessionKey] = [
            'filter_date_from' => $_POST['filter_date_from'] ?? '',
            'filter_date_to' => $_POST['filter_date_to'] ?? '',
            'filter_customer_id' => $_POST['filter_customer_id'] ?? '',
            'filter_brand_name' => $_POST['filter_brand_name'] ?? ''
        ];
    }

    $filters = $_SESSION[$filterSessionKey] ?? [];
    $hasActiveFilters = !empty(array_filter($filters, function ($value) {
        return $value !== '' && $value !== null;
    }));
    $filter_from_date = $filters['filter_date_from'] ?? '';
    $filter_to_date = $filters['filter_date_to'] ?? '';
    $filter_customer_id = $filters['filter_customer_id'] ?? '';
    $filter_brand_name = $filters['filter_brand_name'] ?? '';

    $where_conditions = ["is_deleted=0"];

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filter_from_date)) {
        $where_conditions[] = "order_date >= '" . addslashes($filter_from_date) . "'";
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filter_to_date)) {
        $where_conditions[] = "order_date <= '" . addslashes($filter_to_date) . "'";
    }
    if ($filter_customer_id !== '') {
        $where_conditions[] = "customer_id = '" . intval($filter_customer_id) . "'";
    }
    if ($filter_brand_name !== '') {
        $where_conditions[] = "brand_name = '" . addslashes($filter_brand_name) . "'";
    }

    $where_sql = implode(' AND ', $where_conditions);
    $all_data = $ai_db->aiGetQuery("SELECT * FROM $table WHERE $where_sql ORDER BY id DESC");
    $totalRecords = count($all_data);
}

if ($mode === 'add' && !isset($_POST['btn_submit'])) {
    $lastSeriesQuery = $ai_db->aiGetQuery("SELECT order_no FROM $table WHERE is_deleted=0 AND order_no REGEXP '^#?[0-9]+$' ORDER BY CAST(REPLACE(order_no, '#', '') AS UNSIGNED) DESC LIMIT 1");
    $lastSeriesNo = isset($lastSeriesQuery[0]['order_no']) ? intval(str_replace('#', '', (string) $lastSeriesQuery[0]['order_no'])) : 0;
    $nextSeriesNo = $lastSeriesNo + 1;
    $data['order_no'] = (string) $nextSeriesNo;
    $data['order_date'] = date('Y-m-d');
}

$isFormMode = ($mode === 'add' || $mode === 'edit');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">
            <span class="text-muted fw-light">Orders /</span>
            <?= $isFormMode ? ucfirst($mode) : 'All Records' ?>
        </h4>
        <?php if (!$isFormMode) { ?>
            <div class="d-flex align-items-center gap-2">
                <button type="button"
                    class="btn btn-outline-secondary btn-sm rounded-circle btn-filter-toggle <?= !empty($hasActiveFilters) ? 'active' : '' ?>"
                    data-bs-toggle="collapse" data-bs-target="#ordersFilterCollapse" aria-expanded="false"
                    aria-controls="ordersFilterCollapse" aria-label="Toggle Filters" title="Toggle Filters">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="orders.php?mode=add" class="btn btn-gold btn-sm rounded-pill px-3">
                    <i class="bi bi-plus-lg me-1"></i> Add New
                </a>
            </div>
        <?php } else { ?>
            <a href="orders.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        <?php } ?>
    </div>


    <?php if (!empty($error)) { ?>
        <div class="alert alert-danger border-0 shadow-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php } ?>

    <?php if ($isFormMode) { ?>
        <div class="order-page-card">
            <form id="order_form" method="POST" action="orders.php?mode=<?= $mode ?>&id=<?= $id ?>"
                enctype="multipart/form-data">
                <div class="row g-4">
                    <!-- Row 1 -->
                    <div class="col-md-4">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select name="customer_id" id="customer_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $c) { ?>
                                    <option value="<?= $c['id'] ?>" <?= (isset($data['customer_id']) && $data['customer_id'] == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['contact_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <button type="button" class="btn btn-theme" data-bs-toggle="modal"
                                data-bs-target="#orderCustomerQuickAddModal">Add New</button>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Box Quantity <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0.01" name="box_qty" class="form-control"
                                placeholder="PCS" value="<?= htmlspecialchars($data['box_qty'] ?? '') ?>" required>
                            <span class="input-group-text">PCS</span>
                            <select name="box_qty_unit" class="form-select">
                                <option value="PCS" <?= (isset($data['box_qty_unit']) && $data['box_qty_unit'] == 'PCS') ? 'selected' : '' ?>>XXXX</option>
                            </select>
                            <input type="number" step="0.01" min="0.01" name="upps" class="form-control" placeholder="Upps"
                                value="<?= htmlspecialchars($data['upps'] ?? '1') ?>">
                            <span class="input-group-text">Upps</span>
                        </div>
                    </div>
                    <div class="col-md-3 d-none">
                        <label class="form-label">Rate</label>
                        <input type="number" step="0.01" name="rate" class="form-control"
                            value="<?= htmlspecialchars($data['rate'] ?? '') ?>" placeholder="Rate">
                    </div>

                    <!-- Row 2 -->
                    <div class="col-md-4">
                        <label class="form-label">Brand</label>
                        <select name="brand_name" id="brand_name" class="form-select">
                            <option value="">Select Brand</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Order No</label>
                        <input type="text" name="order_no" class="form-control bg-gray-input"
                            value="<?= htmlspecialchars((string) ($data['order_no'] ?? '')) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Order Date</label>
                        <input type="date" name="order_date" class="form-control"
                            value="<?= htmlspecialchars($data['order_date'] ?? date('Y-m-d')) ?>">
                    </div>

                    <!-- Row 3 -->
                    <div class="col-md-4">
                        <label class="form-label">BOX Name</label>
                        <div class="input-group">
                            <select name="product_id" id="product_id" class="form-select">
                                <option value="">Select a Box</option>
                                <?php foreach ($products as $p) { ?>
                                    <option value="<?= $p['id'] ?>"
                                        data-customer-id="<?= intval($p['customer_id'] ?? 0) ?>"
                                        data-default-length="<?= htmlspecialchars((string) ($p['default_length'] ?? '')) ?>"
                                        data-default-width="<?= htmlspecialchars((string) ($p['default_width'] ?? '')) ?>"
                                        data-default-height="<?= htmlspecialchars((string) ($p['default_height'] ?? '')) ?>"
                                        <?= (isset($data['product_id']) && $data['product_id'] == $p['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['name']) ?> (Stock: <?= number_format($p['stock_qty'], 2) ?>)
                                    </option>
                                <?php } ?>
                            </select>
                            <button type="button" class="btn btn-theme" data-bs-toggle="modal"
                                data-bs-target="#orderProductQuickAddModal">Add New</button>
                        </div>
                        <input type="text" id="product_size_preview" style="background-color: #f1f5f9;" class="form-control mt-2"
                            placeholder="Size will appear here" readonly>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Costing Number</label>
                        <select name="costing_id" id="costing_id" class="form-select">
                            <option value="">Select Costing</option>
                            <?php foreach ($costings as $cost) { ?>
                                <option value="<?= $cost['id'] ?>" data-customer="<?= $cost['customer_id'] ?>"
                                    <?= (isset($data['costing_id']) && $data['costing_id'] == $cost['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cost['estimate_no']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Seet Size Section -->
                    <div class="col-12">
                        <div class="sheet-size-container">
                            <label class="sheet-label">Seet Size</label>
                            <div class="sheet-size-row">
                                <div class="sheet-input-box">
                                    <input type="number" step="0.01" min="0.01" name="sheet_length"
                                        class="sheet-input-field" placeholder="Length"
                                        value="<?= htmlspecialchars($data['sheet_length'] ?? '') ?>">
                                </div>
                                <div class="sheet-input-box">
                                    <input type="number" step="0.01" min="0.01" name="sheet_width" class="sheet-input-field"
                                        placeholder="Width" value="<?= htmlspecialchars($data['sheet_width'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Detail Section -->
                    <div class="col-12">
                        <h4 class="purchase-detail-header"><i class="bi bi-cart-check"></i> Purchase Detail</h4>
                        <div class="row">
                            <!-- Liner Card -->
                            <div class="col-md-6">
                                <div class="purchase-card purchase-card-liner">
                                    <div class="purchase-card-header">
                                        <i class="bi bi-layers"></i> Liner Configuration
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <select name="liner_material_id" id="liner_material_id"
                                                        class="form-select">
                                                        <option value="">Select Liner</option>
                                                        <?php foreach ($liners as $l) { ?>
                                                            <option value="<?= $l['id'] ?>"
                                                                data-rate="<?= htmlspecialchars($l['rate'] ?? '0') ?>"
                                                                data-qty="<?= htmlspecialchars($l['weight'] ?? '0') ?>">
                                                                <?= htmlspecialchars($l['name']) ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                    <button type="button" class="btn btn-theme open-order-material-modal"
                                                        data-bs-toggle="modal" data-bs-target="#orderMaterialQuickAddModal"
                                                        data-material-context="liner" data-material-type-name="Liner">Add
                                                        New</button>
                                                </div>
                                            </div>
                                            <div class="col-md-2 d-none">
                                                <input type="text" id="liner_rate_input"
                                                    class="form-control form-control-sm" placeholder="rate">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" id="liner_qty_input" class="form-control form-control-sm"
                                                    placeholder="qty">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" id="add_liner_item"
                                                    class="btn btn-theme btn-sm w-100">Add</button>
                                            </div>
                                        </div>

                                        <table class="purchase-table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th class="d-none">Rate</th>
                                                    <th>Pcs</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="liner_items_table_body">
                                                <!-- Dynamic items -->
                                            </tbody>
                                        </table>
                                        <input type="hidden" name="liner_items_json" id="liner_items_json"
                                            value="<?= htmlspecialchars($data['liner_items_json'] ?? '[]', ENT_QUOTES) ?>">

                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Liner Delivery To</label>
                                                <select name="liner_delivery_id" id="liner_delivery_id"
                                                    class="form-select form-select-sm">
                                                    <option value="">----- Select Offset -----</option>
                                                    <?php foreach ($offsets as $o) { ?>
                                                        <option value="<?= $o['id'] ?>"
                                                            data-phone="<?= htmlspecialchars($o['contact_number'] ?? '') ?>"
                                                            <?= (isset($data['liner_delivery_id']) && $data['liner_delivery_id'] == $o['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($o['name']) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Liner Delivery Phone</label>
                                                <input type="text" id="liner_delivery_phone" name="liner_delivery_phone"
                                                    class="form-control form-control-sm" placeholder="Liner Delivery Phone"
                                                    value="<?= htmlspecialchars($data['liner_delivery_phone'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label small mb-1">Top Count</label>
                                                <input type="text" name="top_count" class="form-control form-control-sm"
                                                    placeholder="top count"
                                                    value="<?= htmlspecialchars($data['top_count'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Duplex Card -->
                            <div class="col-md-6">
                                <div class="purchase-card purchase-card-duplex">
                                    <div class="purchase-card-header">
                                        <i class="bi bi-box"></i> Duplex Configuration
                                    </div>
                                    <div class="card-body p-3">
                                        <label class="form-label small mb-1">Duplex</label>
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <select name="duplex_material_id" id="duplex_material_id"
                                                        class="form-select">
                                                        <option value="">Select Duplex</option>
                                                        <?php foreach ($duplexes as $d) { ?>
                                                            <option value="<?= $d['id'] ?>"
                                                                data-rate="<?= htmlspecialchars($d['rate'] ?? '0') ?>"
                                                                data-qty="<?= htmlspecialchars($d['weight'] ?? '0') ?>">
                                                                <?= htmlspecialchars($d['name']) ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                    <button type="button" class="btn btn-theme open-order-material-modal"
                                                        data-bs-toggle="modal" data-bs-target="#orderMaterialQuickAddModal"
                                                        data-material-context="duplex" data-material-type-name="Duplex">Add
                                                        New</button>
                                                </div>
                                            </div>
                                            <div class="col-md-2 d-none">
                                                <input type="text" id="duplex_rate_input"
                                                    class="form-control form-control-sm" placeholder="rate">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" id="duplex_qty_input"
                                                    class="form-control form-control-sm" placeholder="qty">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" id="add_duplex_item"
                                                    class="btn btn-theme btn-sm w-100">Add</button>
                                            </div>
                                        </div>

                                        <table class="purchase-table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th class="d-none">Rate</th>
                                                    <th>Pcs</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="duplex_items_table_body">
                                                <!-- Dynamic items -->
                                            </tbody>
                                        </table>
                                        <input type="hidden" name="duplex_items_json" id="duplex_items_json"
                                            value="<?= htmlspecialchars($data['duplex_items_json'] ?? '[]', ENT_QUOTES) ?>">

                                        <div class="row g-3 mt-4">
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Duplex Delivery To</label>
                                                <select name="duplex_delivery_id" id="duplex_delivery_id"
                                                    class="form-select form-select-sm">
                                                    <option value="">----- Select Offset -----</option>
                                                    <?php foreach ($offsets as $o) { ?>
                                                        <option value="<?= $o['id'] ?>"
                                                            data-phone="<?= htmlspecialchars($o['contact_number'] ?? '') ?>"
                                                            <?= (isset($data['duplex_delivery_id']) && $data['duplex_delivery_id'] == $o['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($o['name']) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Duplex Delivery Phone</label>
                                                <input type="text" id="duplex_delivery_phone" name="duplex_delivery_phone"
                                                    class="form-control form-control-sm" placeholder="Duplex Delivery Phone"
                                                    value="<?= htmlspecialchars($data['duplex_delivery_phone'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="purchase-card purchase-card-liner">
                                    <div class="purchase-card-header">
                                        <i class="bi bi-printer"></i> Printing Detail
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <label class="form-label small mb-1">Printing By</label>
                                                <select name="printing_by_id" class="form-select form-select-sm">
                                                    <option value="">----- Select Offset-----</option>
                                                    <?php foreach ($offsets as $o) { ?>
                                                        <option value="<?= $o['id'] ?>" <?= (isset($data['printing_by_id']) && $data['printing_by_id'] == $o['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($o['name']) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>

                                            <div class="col-md-12">
                                                <label class="form-label small mb-1">Upload Offset Image</label>
                                                <input type="file" name="offset_image" class="form-control form-control-sm"
                                                    accept=".jpg,.jpeg,.png,.webp,.pdf">
                                                <?php if (!empty($data['offset_image'])) {
                                                    $offsetImagePath = (string) $data['offset_image'];
                                                    $offsetExt = strtolower(pathinfo($offsetImagePath, PATHINFO_EXTENSION));
                                                    ?>
                                                    <div class="mt-2">
                                                        <div class="small text-muted mb-1">Current File:</div>
                                                        <?php if ($offsetExt === 'pdf') { ?>
                                                            <a href="<?= htmlspecialchars($offsetImagePath) ?>" target="_blank"
                                                                class="btn btn-outline-secondary btn-sm">
                                                                <i class="bi bi-file-earmark-pdf me-1"></i> View PDF
                                                            </a>
                                                        <?php } else { ?>
                                                            <a href="<?= htmlspecialchars($offsetImagePath) ?>" target="_blank">
                                                                <img src="<?= htmlspecialchars($offsetImagePath) ?>"
                                                                    alt="Offset Image"
                                                                    style="max-height:140px; width:auto; border:1px solid #e2e8f0; border-radius:8px; padding:4px; background:#fff;">
                                                            </a>
                                                        <?php } ?>
                                                    </div>
                                                <?php } ?>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Print Color</label>
                                                <input type="text" name="print_color" class="form-control form-control-sm"
                                                    placeholder="Print Color"
                                                    value="<?= htmlspecialchars($data['print_color'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Print Qty</label>
                                                <input type="text" name="print_qty" class="form-control form-control-sm"
                                                    placeholder="Print Qty"
                                                    value="<?= htmlspecialchars($data['print_qty'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Print Delivery To</label>
                                                <select name="print_delivery_id" id="print_delivery_id"
                                                    class="form-select form-select-sm">
                                                    <option value="">----- Select Delivery-----</option>
                                                    <?php foreach ($offsets as $o) { ?>
                                                        <option value="<?= $o['id'] ?>"
                                                            data-phone="<?= htmlspecialchars($o['contact_number'] ?? '') ?>"
                                                            <?= (isset($data['print_delivery_id']) && $data['print_delivery_id'] == $o['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($o['name']) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Print Delivery Phone</label>
                                                <input type="text" id="print_delivery_phone" name="print_delivery_phone"
                                                    class="form-control form-control-sm" placeholder="Print Delivery Phone"
                                                    value="<?= htmlspecialchars($data['print_delivery_phone'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="purchase-card purchase-card-duplex">
                                    <div class="purchase-card-header">
                                        <i class="bi bi-pencil-square"></i> Record Detail
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Die Maker</label>
                                                <input type="text" name="die_maker" class="form-control form-control-sm"
                                                    placeholder="Die Maker"
                                                    value="<?= htmlspecialchars($data['die_maker'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1">Die Code</label>
                                                <input type="text" name="die_code" class="form-control form-control-sm"
                                                    placeholder="Die Code"
                                                    value="<?= htmlspecialchars($data['die_code'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1">C Die Code</label>
                                                <input type="text" name="c_die_code" class="form-control form-control-sm"
                                                    placeholder="C-Die Code"
                                                    value="<?= htmlspecialchars($data['c_die_code'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Designer</label>
                                                <input type="text" name="designer" class="form-control form-control-sm"
                                                    placeholder="Designer"
                                                    value="<?= htmlspecialchars($data['designer'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Plate</label>
                                                <input type="text" name="plate" class="form-control form-control-sm"
                                                    placeholder="Plate"
                                                    value="<?= htmlspecialchars($data['plate'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" id="half_film"
                                                        name="half_film" value="1" <?= !empty($data['half_film']) ? 'checked' : '' ?>>
                                                    <label class="form-check-label small" for="half_film">Half Film</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" id="full_film"
                                                        name="full_film" value="1" <?= !empty($data['full_film']) ? 'checked' : '' ?>>
                                                    <label class="form-check-label small" for="full_film">Full Film</label>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Lamination</label>
                                                <select name="lamination_type" class="form-select form-select-sm">
                                                    <option value="">Select Lamination</option>
                                                    <option value="XXXX" <?= (isset($data['lamination_type']) && $data['lamination_type'] === 'XXXX') ? 'selected' : '' ?>>XXXX
                                                    </option>
                                                    <option value="Gloss" <?= (isset($data['lamination_type']) && $data['lamination_type'] === 'Gloss') ? 'selected' : '' ?>>Gloss
                                                    </option>
                                                    <option value="Matt" <?= (isset($data['lamination_type']) && $data['lamination_type'] === 'Matt') ? 'selected' : '' ?>>Matt
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Lamination Extra</label>
                                                <input type="text" name="lamination_extra"
                                                    class="form-control form-control-sm" placeholder="Lamination Extra"
                                                    value="<?= htmlspecialchars($data['lamination_extra'] ?? '') ?>">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Laminas Delivery To</label>
                                                <select name="laminas_delivery_id" id="laminas_delivery_id"
                                                    class="form-select form-select-sm">
                                                    <option value="">----- Select Lamination -----</option>
                                                    <?php foreach ($laminations as $l) { ?>
                                                        <option value="<?= $l['id'] ?>"
                                                            data-phone="<?= htmlspecialchars($l['contact_number'] ?? '') ?>"
                                                            <?= (isset($data['laminas_delivery_id']) && $data['laminas_delivery_id'] == $l['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($l['name']) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Laminas Delivery Phone</label>
                                                <input type="text" id="laminas_delivery_phone" name="laminas_delivery_phone"
                                                    class="form-control form-control-sm"
                                                    placeholder="Laminas Delivery Phone"
                                                    value="<?= htmlspecialchars($data['laminas_delivery_phone'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="purchase-card purchase-card-duplex">
                            <div class="purchase-card-header">
                                <i class="bi bi-journal-check"></i> Jobsheet Detail
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="job_pesting"
                                                name="job_pesting" value="1" <?= !empty($data['job_pesting']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="job_pesting">Pesting</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="job_pin" name="job_pin"
                                                value="1" <?= !empty($data['job_pin']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="job_pin">Pin</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="job_punching"
                                                name="job_punching" value="1" <?= !empty($data['job_punching']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="job_punching">Punching</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="job_side_pesting"
                                                name="job_side_pesting" value="1" <?= !empty($data['job_side_pesting']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="job_side_pesting">Side Pesting</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="purchase-card purchase-card-liner">
                            <div class="purchase-card-header">
                                <i class="bi bi-receipt-cutoff"></i> Bill Detail
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">Design</label>
                                        <input type="text" name="bill_design" class="form-control form-control-sm"
                                            placeholder="Design"
                                            value="<?= htmlspecialchars($data['bill_design'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">Plate</label>
                                        <input type="text" name="bill_plate" class="form-control form-control-sm"
                                            placeholder="Plate" value="<?= htmlspecialchars($data['bill_plate'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">DAEI</label>
                                        <input type="text" name="bill_daei" class="form-control form-control-sm"
                                            placeholder="DAEI" value="<?= htmlspecialchars($data['bill_daei'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">Photo Price</label>
                                        <input type="text" name="bill_photo_price" class="form-control form-control-sm"
                                            placeholder="Photo Price"
                                            value="<?= htmlspecialchars($data['bill_photo_price'] ?? '') ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">PCS</label>
                                        <input type="text" name="bill_pcs" class="form-control form-control-sm"
                                            placeholder="PCS" value="<?= htmlspecialchars($data['bill_pcs'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">Rixa Bhadu</label>
                                        <input type="text" name="bill_rixa_bhadu" class="form-control form-control-sm"
                                            placeholder="Rixa"
                                            value="<?= htmlspecialchars($data['bill_rixa_bhadu'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">Borrow Charge</label>
                                        <input type="text" name="bill_borrow_charge" class="form-control form-control-sm"
                                            placeholder="Borrow Charge"
                                            value="<?= htmlspecialchars($data['bill_borrow_charge'] ?? '') ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">Remark</label>
                                        <textarea name="bill_remark" class="form-control form-control-sm" rows="2"
                                            placeholder="Remark"><?= htmlspecialchars($data['bill_remark'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-end">
                    <button type="submit" name="btn_submit" class="btn btn-gold btn-sm rounded-pill px-4">
                        <i class="bi bi-check-circle me-1"></i>
                        <?= ($mode == 'edit') ? 'Update Order' : 'Save Order' ?>
                    </button>
                    <a href="orders.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 ms-2">Cancel</a>
                </div>
            </form>
        </div>
    <?php } else { ?>
        <div class="collapse mb-3 <?= !empty($hasActiveFilters) ? 'show' : '' ?>" id="ordersFilterCollapse">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="orders.php" class="row g-3">
                        <div class="col-md-3">
                            <label for="filter_date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="filter_date_from" name="filter_date_from"
                                value="<?= htmlspecialchars($filter_from_date) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="filter_date_to" name="filter_date_to"
                                value="<?= htmlspecialchars($filter_to_date) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_customer_id" class="form-label">Customer</label>
                            <select class="form-select select2" id="filter_customer_id" name="filter_customer_id">
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $c) { ?>
                                    <option value="<?= $c['id'] ?>" <?= (isset($filter_customer_id) && $filter_customer_id == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['contact_name']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_brand_name" class="form-label">Brand</label>
                            <select class="form-select select2" id="filter_brand_name" name="filter_brand_name">
                                <option value="">Select Brand</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm me-2">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                            <a href="orders.php?action=clear_filters" class="btn btn-outline-secondary btn-sm">
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
                                <th>Order No.</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Brand</th>
                                <th>Box Name</th>
                                <th class="d-none">Rate</th>
                                <!-- <th>Plate</th>
                                <th>Print</th>
                                <th>Die</th>
                                <th>MD Code</th> -->
                                <th width="170" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_data)) {
                                foreach ($all_data as $index => $row) { ?>
                                    <tr>
                                        <td><span class="fw-semibold">
                                                <?= htmlspecialchars((string) ($row['order_no'] ?? '')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= date('d-m-Y', strtotime($row['order_date'])) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($row['customer_name']) ?>
                                        </td>
                                        <td>
                                            <?= $row['brand_name'] !== '' ? htmlspecialchars($row['brand_name']) : '<span class="text-muted">-</span>' ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($row['product_name']) ?>
                                        </td>
                                        <td class="d-none">Rs.
                                            <?= number_format((float) $row['rate'], 2) ?>
                                        </td>
                                        <!-- <td><?= htmlspecialchars($row['plate_status'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['print_status'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['die_status'] ?? '-') ?></td>
                                        <td><?= !empty($row['md_code']) ? htmlspecialchars($row['md_code']) : '<span class="text-muted">-</span>' ?></td> -->
                                        <td class="text-center">
                                            <div class="table-action-group">
                                                <a href="orders.php?mode=edit&id=<?= $row['id'] ?>" class="table-action-btn edit"
                                                    title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="print_order.php?id=<?= $row['id'] ?>&print=1" target="_blank"
                                                    class="table-action-btn print" title="Print">
                                                    <i class="bi bi-printer-fill"></i>
                                                </a>
                                                <a href="orders.php?mode=delete&id=<?= $row['id'] ?>"
                                                    class="table-action-btn delete" title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this order?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        No data available. Click "Add New" to create your first order.
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($totalRecords)) { ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            Showing 1 to
                            <?= $totalRecords ?> of
                            <?= $totalRecords ?> entries
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>

<?php if ($isFormMode) { ?>
    <div class="modal fade" id="orderCustomerQuickAddModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="orderCustomerQuickAddForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contact Name <span class="text-danger">*</span></label>
                                <input type="text" name="contact_name" class="form-control" placeholder="Enter Contact Name"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone No <span class="text-danger">*</span></label>
                                <input type="text" name="phone_no" class="form-control" placeholder="Enter Phone Number"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="address" class="form-control" rows="3"
                                    placeholder="Enter Address"></textarea>
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
                                    <div id="orderModalBrandRepeater">
                                        <div class="input-group input-group-sm mb-2 order-modal-brand-item">
                                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                            <input type="text" name="brand_names[]" class="form-control"
                                                placeholder="Brand Name">
                                            <button type="button" class="btn btn-danger removeOrderModalBrand"><i
                                                    class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3 mt-2"
                                        id="addOrderModalBrandBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Add More
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-gold" id="orderCustomerQuickAddSubmit">Save Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="orderProductQuickAddModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="orderProductQuickAddForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter product name"
                                    required>
                            </div>
                            <div class="col-md-6 d-none">
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
                                        <input type="number" step="0.01" name="default_length" class="form-control"
                                            placeholder="L">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="number" step="0.01" name="default_width" class="form-control"
                                            placeholder="W">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="number" step="0.01" name="default_height" class="form-control"
                                            placeholder="H">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="Enter Description"></textarea>
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
                        <button type="submit" class="btn btn-gold" id="orderProductQuickAddSubmit">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="orderMaterialQuickAddModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="orderMaterialQuickAddTitle">Add Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="orderMaterialQuickAddForm">
                    <input type="hidden" name="material_context" id="order_material_context" value="liner">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Material Type <span class="text-danger">*</span></label>
                                <select name="material_type_id" id="order_material_type_id" class="form-select" required>
                                    <option value="">Select Material Type</option>
                                    <?php foreach ($materialTypes as $materialType) { ?>
                                        <option value="<?= $materialType['id'] ?>"
                                            data-name="<?= htmlspecialchars($materialType['name']) ?>">
                                            <?= htmlspecialchars($materialType['name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter Material Name"
                                    required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">F Value</label>
                                <input type="number" step="0.01" name="f_value" class="form-control" placeholder="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">P Value</label>
                                <input type="number" step="0.01" name="p_value" class="form-control" placeholder="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Top Value</label>
                                <input type="number" step="0.01" name="top_value" class="form-control" placeholder="0">
                            </div>
                            <div class="col-md-3 d-none">
                                <label class="form-label fw-bold">Rate</label>
                                <input type="number" step="0.01" name="rate" class="form-control" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Weight</label>
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
                        <button type="submit" class="btn btn-gold" id="orderMaterialQuickAddSubmit">Save Material</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const customerBrandsMap = <?= json_encode($customerBrandsMap) ?>;
        const currentBrand = "<?= htmlspecialchars($data['brand_name'] ?? '') ?>";
        const currentMode = "<?= htmlspecialchars($mode) ?>";

        <?php if (!empty($error)) { ?>
            if (typeof showToast === 'function') {
                showToast('Validation Error', "<?= addslashes($error) ?>", 'error');
            }
        <?php } ?>

        const custSelect = document.getElementById('customer_id');
        const brandSelect = document.getElementById('brand_name');
        const productSelect = document.getElementById('product_id');
        const productSizePreview = document.getElementById('product_size_preview');
        const costingSelect = document.getElementById('costing_id');
        const customerQuickAddForm = document.getElementById('orderCustomerQuickAddForm');
        const productQuickAddForm = document.getElementById('orderProductQuickAddForm');
        const materialQuickAddForm = document.getElementById('orderMaterialQuickAddForm');
        const customerQuickAddModal = document.getElementById('orderCustomerQuickAddModal');
        const productQuickAddModal = document.getElementById('orderProductQuickAddModal');
        const materialQuickAddModal = document.getElementById('orderMaterialQuickAddModal');
        const orderForm = document.getElementById('order_form');
        const linerMaterialSelect = document.getElementById('liner_material_id');
        const filterCustSelect = document.getElementById('filter_customer_id');
        const filterBrandSelect = document.getElementById('filter_brand_name');

        // Master list of all costing options for robust filtering
        let allCostingOptions = [];
        if (costingSelect) {
            allCostingOptions = Array.from(costingSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                customer: opt.dataset.customer
            }));
        }
        let allProductOptions = [];
        if (productSelect) {
            allProductOptions = Array.from(productSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                customerId: opt.dataset.customerId || '',
                defaultLength: opt.dataset.defaultLength || '',
                defaultWidth: opt.dataset.defaultWidth || '',
                defaultHeight: opt.dataset.defaultHeight || ''
            }));
        }
        const duplexMaterialSelect = document.getElementById('duplex_material_id');
        const materialContextField = document.getElementById('order_material_context');
        const materialTypeField = document.getElementById('order_material_type_id');
        const materialQuickAddTitle = document.getElementById('orderMaterialQuickAddTitle');
        const openMaterialModalButtons = document.querySelectorAll('.open-order-material-modal');
        const linerRateInput = document.getElementById('liner_rate_input');
        const linerQtyInput = document.getElementById('liner_qty_input');
        const addLinerItemButton = document.getElementById('add_liner_item');
        const linerItemsTableBody = document.getElementById('liner_items_table_body');
        const linerItemsJson = document.getElementById('liner_items_json');
        const duplexRateInput = document.getElementById('duplex_rate_input');
        const duplexQtyInput = document.getElementById('duplex_qty_input');
        const addDuplexItemButton = document.getElementById('add_duplex_item');
        const duplexItemsTableBody = document.getElementById('duplex_items_table_body');
        const duplexItemsJson = document.getElementById('duplex_items_json');
        const linerDeliverySelect = document.getElementById('liner_delivery_id');
        const linerDeliveryPhoneInput = document.getElementById('liner_delivery_phone');
        const duplexDeliverySelect = document.getElementById('duplex_delivery_id');
        const duplexDeliveryPhoneInput = document.getElementById('duplex_delivery_phone');
        const printDeliverySelect = document.getElementById('print_delivery_id');
        const printDeliveryPhoneInput = document.getElementById('print_delivery_phone');
        const laminasDeliverySelect = document.getElementById('laminas_delivery_id');
        const laminasDeliveryPhoneInput = document.getElementById('laminas_delivery_phone');
        let linerItems = [];
        let duplexItems = [];
        let isApplyingLastOrderPrefill = false;
        let lastOrderPrefillKey = '';

        function updateBrands(custId, selectedBrand = '', targetSelect = brandSelect) {
            if (!targetSelect) return;

            // Clear and add placeholder
            targetSelect.innerHTML = '<option value="">Select Brand</option>';

            if (custId && customerBrandsMap && customerBrandsMap[custId]) {
                const brands = customerBrandsMap[custId];
                if (Array.isArray(brands)) {
                    brands.forEach(brand => {
                        const opt = document.createElement('option');
                        opt.value = brand;
                        opt.textContent = brand;
                        if (String(brand) === String(selectedBrand)) opt.selected = true;
                        targetSelect.appendChild(opt);
                    });
                }
            }

            // Refresh Select2 if it exists
            if (typeof window.refreshSelect2Dropdown === 'function') {
                window.refreshSelect2Dropdown(targetSelect);
            } else if (window.jQuery && jQuery.fn.select2 && jQuery(targetSelect).hasClass('select2-hidden-accessible')) {
                jQuery(targetSelect).trigger('change');
            }
        }

        // Initialize Filter Brand Dropdown


        function filterCostingsByCustomer(custId) {
            if (!costingSelect) return;

            const currentVal = costingSelect.value;
            const selectedOpt = costingSelect.options[costingSelect.selectedIndex];

            // Rebuild options based on customer
            costingSelect.innerHTML = '<option value="">Select Costing</option>';

            let valIsValid = false;
            allCostingOptions.forEach(opt => {
                if (opt.value === "") return;

                if (opt.customer == custId || custId === "") {
                    const newOpt = document.createElement('option');
                    newOpt.value = opt.value;
                    newOpt.textContent = opt.text;
                    newOpt.dataset.customer = opt.customer;

                    if (String(opt.value) === String(currentVal)) {
                        newOpt.selected = true;
                        valIsValid = true;
                    }
                    costingSelect.appendChild(newOpt);
                }
            });

            if (!valIsValid) {
                costingSelect.value = "";
            }

            if (typeof window.refreshSelect2Dropdown === 'function') {
                window.refreshSelect2Dropdown(costingSelect);
            }
        }

        function filterProductsByCustomer(custId) {
            if (!productSelect) return;

            const currentVal = productSelect.value;
            productSelect.innerHTML = '<option value="">Select a Box</option>';

            let valIsValid = false;
            allProductOptions.forEach(opt => {
                if (!opt.value) return;

                const belongsToCustomer = String(opt.customerId) === String(custId);
                if (custId !== '' && belongsToCustomer) {
                    const newOpt = document.createElement('option');
                    newOpt.value = opt.value;
                    newOpt.textContent = opt.text;
                    newOpt.dataset.customerId = opt.customerId;
                    newOpt.dataset.defaultLength = opt.defaultLength;
                    newOpt.dataset.defaultWidth = opt.defaultWidth;
                    newOpt.dataset.defaultHeight = opt.defaultHeight;

                    if (String(opt.value) === String(currentVal)) {
                        newOpt.selected = true;
                        valIsValid = true;
                    }
                    productSelect.appendChild(newOpt);
                }
            });

            if (!valIsValid) {
                productSelect.value = '';
            }

            if (typeof window.refreshSelect2Dropdown === 'function') {
                window.refreshSelect2Dropdown(productSelect);
            }
            updateProductSizePreview();
        }

        function upsertOption(selectElement, value, label, extraData = {}) {
            if (!selectElement) return;
            let existing = Array.from(selectElement.options).find(opt => String(opt.value) === String(value));
            if (!existing) {
                existing = document.createElement('option');
                existing.value = value;
                selectElement.appendChild(existing);
            }
            existing.textContent = label;
            Object.keys(extraData || {}).forEach(key => {
                const val = extraData[key];
                if (val !== undefined && val !== null) {
                    existing.dataset[key] = val;
                }
            });
        }

        function parseNumber(value) {
            const num = parseFloat(String(value || '').replace(/,/g, '').trim());
            return Number.isFinite(num) ? num : 0;
        }

        function formatNumber(value) {
            return parseNumber(value).toFixed(2);
        }

        function fillMaterialInputs(selectEl, rateEl, qtyEl) {
            if (!selectEl || !rateEl || !qtyEl) return;
            const selected = selectEl.options[selectEl.selectedIndex];
            if (!selected || !selectEl.value) {
                rateEl.value = '';
                qtyEl.value = '';
                return;
            }

            rateEl.value = selected.dataset.rate || '0';
            qtyEl.value = '';
        }

        function fillDeliveryPhone(selectEl, phoneInputEl) {
            if (!selectEl || !phoneInputEl) return;
            const selected = selectEl.options[selectEl.selectedIndex];
            if (!selected || !selectEl.value) {
                phoneInputEl.value = '';
                return;
            }

            phoneInputEl.value = selected.dataset.phone || '';
        }

        function formatDimension(value) {
            const num = parseFloat(value);
            if (!Number.isFinite(num) || num <= 0) {
                return '';
            }
            return num.toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
        }

        function updateProductSizePreview() {
            if (!productSizePreview) return;

            if (!productSelect || !productSelect.value) {
                productSizePreview.value = '';
                return;
            }

            const selected = productSelect.options[productSelect.selectedIndex];
            if (!selected) {
                productSizePreview.value = '';
                return;
            }

            const length = formatDimension(selected.dataset.defaultLength || '');
            const width = formatDimension(selected.dataset.defaultWidth || '');
            const height = formatDimension(selected.dataset.defaultHeight || '');
            const parts = [length, width, height].filter(Boolean);

            productSizePreview.value = parts.length ? `${parts.join(' x ')} cm` : '';
        }

        function renderLinerItems() {
            if (!linerItemsTableBody) return;
            if (!linerItems.length) {
                linerItemsTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No liner added.</td></tr>';
            } else {
                linerItemsTableBody.innerHTML = linerItems.map((item, index) => `
                    <tr>
                        <td>${item.name}</td>
                        <td class="d-none">${formatNumber(item.rate)}</td>
                        <td>${formatNumber(item.qty)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-liner-item" data-index="${index}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            if (linerItemsJson) {
                linerItemsJson.value = JSON.stringify(linerItems);
            }
        }

        function renderDuplexItems() {
            if (!duplexItemsTableBody) return;
            if (!duplexItems.length) {
                duplexItemsTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No duplex added.</td></tr>';
            } else {
                duplexItemsTableBody.innerHTML = duplexItems.map((item, index) => `
                    <tr>
                        <td>${item.name}</td>
                        <td class="d-none">${formatNumber(item.rate)}</td>
                        <td>${formatNumber(item.qty)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-duplex-item" data-index="${index}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            if (duplexItemsJson) {
                duplexItemsJson.value = JSON.stringify(duplexItems);
            }
        }

        function addLineItem(kind) {
            const isLiner = kind === 'liner';
            const selectEl = isLiner ? linerMaterialSelect : duplexMaterialSelect;
            const rateEl = isLiner ? linerRateInput : duplexRateInput;
            const qtyEl = isLiner ? linerQtyInput : duplexQtyInput;
            const target = isLiner ? linerItems : duplexItems;

            if (!selectEl || !rateEl || !qtyEl) return;
            if (!selectEl.value) {
                alert(`Please select ${isLiner ? 'liner' : 'duplex'}.`);
                return;
            }

            const selected = selectEl.options[selectEl.selectedIndex];
            target.push({
                material_id: parseInt(selectEl.value, 10) || 0,
                name: (selected.textContent || '').trim(),
                rate: parseNumber(rateEl.value),
                qty: parseNumber(qtyEl.value)
            });

            selectEl.value = '';
            rateEl.value = '';
            qtyEl.value = '';

            if (isLiner) renderLinerItems();
            else renderDuplexItems();
        }

        function toggleSubmitButton(button, isLoading, loadingText) {
            if (!button) return;
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.textContent;
            }
            button.disabled = !!isLoading;
            button.textContent = isLoading ? (loadingText || 'Saving...') : button.dataset.originalText;
        }

        function resetQuickForm(formElement) {
            if (!formElement) return;
            formElement.reset();
            formElement.classList.remove('was-validated');

            if (formElement.id === 'orderCustomerQuickAddForm') {
                const repeater = document.getElementById('orderModalBrandRepeater');
                if (repeater) {
                    repeater.innerHTML = `
                        <div class="input-group input-group-sm mb-2 order-modal-brand-item">
                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                            <input type="text" name="brand_names[]" class="form-control" placeholder="Brand Name">
                            <button type="button" class="btn btn-danger removeOrderModalBrand"><i class="bi bi-trash"></i></button>
                        </div>
                    `;
                }
            }
        }

        function setModalMaterialTypeByName(typeName) {
            if (!materialTypeField) return;
            const target = String(typeName || '').trim().toLowerCase();
            if (!target) return;
            const option = Array.from(materialTypeField.options).find(opt => String(opt.dataset.name || opt.textContent || '').trim().toLowerCase() === target);
            if (option) {
                materialTypeField.value = option.value;
            }
        }

        function hideModal(modalElement) {
            if (!modalElement || typeof bootstrap === 'undefined') return;
            bootstrap.Modal.getOrCreateInstance(modalElement).hide();
        }

        function handleAjaxError(message) {
            if (typeof showToast === 'function') {
                showToast('Error', message || 'Something went wrong.', 'error');
            } else {
                alert(message || 'Something went wrong.');
            }
        }

        function setFieldValue(fieldName, fieldValue) {
            const field = document.getElementsByName(fieldName)[0];
            if (!field) return;

            if (field.type === 'checkbox') {
                field.checked = String(fieldValue) === '1';
                return;
            }

            field.value = fieldValue ?? '';
            if (field.tagName === 'SELECT' && typeof refreshSelect2Dropdown === 'function') {
                refreshSelect2Dropdown(field);
            }
        }

        function applyLastOrderPrefill(orderData, orderItems) {
            isApplyingLastOrderPrefill = true;
            try {
                if (orderData.brand_name !== undefined) {
                    updateBrands(custSelect ? custSelect.value : '', orderData.brand_name || '');
                }

                const valueFields = [
                    'box_qty', 'box_qty_unit', 'upps', 'rate', 'costing_id',
                    'sheet_length', 'sheet_width', 'md_code',
                    'plate_status', 'print_status', 'die_status',
                    'liner_delivery_id', 'liner_delivery_phone', 'top_count',
                    'duplex_delivery_id', 'duplex_delivery_phone',
                    'printing_by_id', 'print_color', 'print_qty',
                    'print_delivery_id', 'print_delivery_phone',
                    'die_maker', 'die_code', 'c_die_code', 'designer', 'plate',
                    'lamination_type', 'lamination_extra',
                    'laminas_delivery_id', 'laminas_delivery_phone',
                    'bill_design', 'bill_plate', 'bill_daei', 'bill_photo_price',
                    'bill_pcs', 'bill_rixa_bhadu', 'bill_borrow_charge', 'bill_remark'
                ];

                valueFields.forEach(function (fieldName) {
                    setFieldValue(fieldName, orderData[fieldName] ?? '');
                });

                setFieldValue('half_film', orderData.half_film ?? 0);
                setFieldValue('full_film', orderData.full_film ?? 0);
                setFieldValue('job_pesting', orderData.job_pesting ?? 0);
                setFieldValue('job_pin', orderData.job_pin ?? 0);
                setFieldValue('job_punching', orderData.job_punching ?? 0);
                setFieldValue('job_side_pesting', orderData.job_side_pesting ?? 0);

                if (Array.isArray(orderItems)) {
                    linerItems = [];
                    duplexItems = [];

                    orderItems.forEach(function (item) {
                        const mappedItem = {
                            material_id: parseInt(item.material_id || 0, 10) || 0,
                            name: String(item.material_name || item.name || '').trim(),
                            rate: parseNumber(item.rate || 0),
                            qty: parseNumber((item.qty !== null && item.qty !== undefined && item.qty !== '') ? item.qty : item.pcs)
                        };

                        if (!mappedItem.name && mappedItem.material_id <= 0) {
                            return;
                        }

                        const group = String(item.item_group || '').toLowerCase();
                        if (group === 'duplex') {
                            duplexItems.push(mappedItem);
                        } else {
                            linerItems.push(mappedItem);
                        }
                    });

                    renderLinerItems();
                    renderDuplexItems();
                }

                updateProductSizePreview();
                if (typeof showToast === 'function') {
                    showToast('Auto Fill', 'Last matching order details loaded.', 'success');
                }
            } finally {
                isApplyingLastOrderPrefill = false;
            }
        }

        function fetchLastOrderPrefillBySelection() {
            if (currentMode !== 'add' || isApplyingLastOrderPrefill) return;
            if (!custSelect || !productSelect) return;
            if (costingSelect && String(costingSelect.value || '').trim() !== '') return;

            const customerId = String(custSelect.value || '').trim();
            const productId = String(productSelect.value || '').trim();
            if (!customerId || !productId) return;

            const requestKey = customerId + '_' + productId;
            if (requestKey === lastOrderPrefillKey) return;
            lastOrderPrefillKey = requestKey;

            const formData = new FormData();
            formData.append('action', 'get_last_order_prefill');
            formData.append('customer_id', customerId);
            formData.append('product_id', productId);

            fetch('ajax.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data || !data.success) {
                        return;
                    }
                    applyLastOrderPrefill(data.order || {}, data.items || []);
                })
                .catch(() => {
                    // Silent fail; manual entry remains available.
                });
        }

        if (custSelect) {
            const $custSelect = jQuery(custSelect);

            if (custSelect.value) {
                updateBrands(custSelect.value, currentBrand);
                filterCostingsByCustomer(custSelect.value);
                filterProductsByCustomer(custSelect.value);
            }

            $custSelect.on('change select2:select', function () {
                const custId = this.value;
                updateBrands(custId);
                filterCostingsByCustomer(custId);
                filterProductsByCustomer(custId);
                lastOrderPrefillKey = '';
                fetchLastOrderPrefillBySelection();
            });
        }

        if (costingSelect) {
            jQuery(costingSelect).on('change', function () {
                const costingId = this.value;
                if (!costingId) return;

                // Show a small loader if possible
                toggleSubmitButton(document.querySelector('button[name="btn_submit"]'), true, 'Fetching Costing...');

                const formData = new FormData();
                formData.append('action', 'get_costing_data');
                formData.append('costing_id', costingId);

                fetch('ajax.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            const data = res.data;

                            // Fill Product
                            if (productSelect && data.product_id) {
                                productSelect.value = data.product_id;
                                if (typeof window.refreshSelect2Dropdown === 'function') window.refreshSelect2Dropdown(productSelect);
                                updateProductSizePreview();
                            }

                            // Fill Brand (Refresh Brands list for the customer and select the one from costing)
                            if (brandSelect && data.brand_name) {
                                updateBrands(data.customer_id, data.brand_name);
                            }

                            // Fill Main Details
                            const mainFields = {
                                'rate': data.sale_rate,
                                'sheet_length': data.sheet_length,
                                'sheet_width': data.sheet_width,
                                'upps': data.upps || '1',
                                'printing': data.printing,
                                'laminas_value': data.laminas_value,
                                'pesting': data.pesting,
                                'punching': data.punching,
                                'pin_rate': data.pin_rate,
                                'pin_qty': data.pin_qty,
                                'side_pesting': data.side_pesting,
                                'uv_coating': data.uv_coating,
                                'rixa_bhadu': data.rixa_bhadu,
                                'bill_remark': data.remark
                            };

                            for (const [name, val] of Object.entries(mainFields)) {
                                const el = document.getElementsByName(name)[0];
                                if (el) {
                                    el.value = val || (name === 'upps' ? '1' : '');
                                    // Trigger input event to satisfy any other listeners
                                    el.dispatchEvent(new Event('input'));
                                }
                            }

                            // Fill Items
                            try {
                                linerItems = JSON.parse(data.liner_items || '[]');
                                duplexItems = JSON.parse(data.duplex_items || '[]');
                                renderLinerItems();
                                renderDuplexItems();
                            } catch (e) {
                                console.error("Error parsing costing items:", e);
                            }

                            if (typeof showToast === 'function') {
                                showToast('Costing Loaded', 'All details have been fetched from the selected costing.', 'success');
                            }
                        } else {
                            handleAjaxError(res.message);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        handleAjaxError('Failed to fetch costing data.');
                    })
                    .finally(() => {
                        toggleSubmitButton(document.querySelector('button[name="btn_submit"]'), false);
                    });
            });
        }

        try {
            linerItems = JSON.parse(linerItemsJson ? (linerItemsJson.value || '[]') : '[]');
            if (!Array.isArray(linerItems)) linerItems = [];
        } catch (error) {
            linerItems = [];
        }

        try {
            duplexItems = JSON.parse(duplexItemsJson ? (duplexItemsJson.value || '[]') : '[]');
            if (!Array.isArray(duplexItems)) duplexItems = [];
        } catch (error) {
            duplexItems = [];
        }

        renderLinerItems();
        renderDuplexItems();

        if (linerMaterialSelect) {
            jQuery(linerMaterialSelect).on('change', function () {
                fillMaterialInputs(linerMaterialSelect, linerRateInput, linerQtyInput);
            });
        }

        if (duplexMaterialSelect) {
            jQuery(duplexMaterialSelect).on('change', function () {
                fillMaterialInputs(duplexMaterialSelect, duplexRateInput, duplexQtyInput);
            });
        }

        if (productSelect) {
            jQuery(productSelect).on('change select2:select', function () {
                updateProductSizePreview();
                lastOrderPrefillKey = '';
                fetchLastOrderPrefillBySelection();
            });
            updateProductSizePreview();
        }

        if (currentMode === 'add') {
            fetchLastOrderPrefillBySelection();
        }

        if (linerMaterialSelect && linerMaterialSelect.value) {
            fillMaterialInputs(linerMaterialSelect, linerRateInput, linerQtyInput);
        }
        if (duplexMaterialSelect && duplexMaterialSelect.value) {
            fillMaterialInputs(duplexMaterialSelect, duplexRateInput, duplexQtyInput);
        }

        if (linerDeliverySelect) {
            jQuery(linerDeliverySelect).on('change', function () {
                fillDeliveryPhone(linerDeliverySelect, linerDeliveryPhoneInput);
            });
            if (linerDeliverySelect.value && linerDeliveryPhoneInput && !linerDeliveryPhoneInput.value) {
                fillDeliveryPhone(linerDeliverySelect, linerDeliveryPhoneInput);
            }
        }

        if (duplexDeliverySelect) {
            jQuery(duplexDeliverySelect).on('change', function () {
                fillDeliveryPhone(duplexDeliverySelect, duplexDeliveryPhoneInput);
            });
            if (duplexDeliverySelect.value && duplexDeliveryPhoneInput && !duplexDeliveryPhoneInput.value) {
                fillDeliveryPhone(duplexDeliverySelect, duplexDeliveryPhoneInput);
            }
        }

        if (printDeliverySelect) {
            jQuery(printDeliverySelect).on('change', function () {
                fillDeliveryPhone(printDeliverySelect, printDeliveryPhoneInput);
            });
            if (printDeliverySelect.value && printDeliveryPhoneInput && !printDeliveryPhoneInput.value) {
                fillDeliveryPhone(printDeliverySelect, printDeliveryPhoneInput);
            }
        }

        if (laminasDeliverySelect) {
            jQuery(laminasDeliverySelect).on('change', function () {
                fillDeliveryPhone(laminasDeliverySelect, laminasDeliveryPhoneInput);
            });
            if (laminasDeliverySelect.value && laminasDeliveryPhoneInput && !laminasDeliveryPhoneInput.value) {
                fillDeliveryPhone(laminasDeliverySelect, laminasDeliveryPhoneInput);
            }
        }

        if (orderForm) {
            orderForm.addEventListener('submit', function (event) {
                const cid = custSelect ? custSelect.value : '';
                const bqty = parseFloat(document.getElementsByName('box_qty')[0]?.value || 0);

                if (!cid || bqty <= 0) {
                    event.preventDefault();
                    if (typeof showToast === 'function') {
                        showToast('Missing Details', 'Please select a Customer and enter Box Quantity.', 'error');
                    } else {
                        alert('Customer and Box Quantity are required.');
                    }
                    return;
                }

                // Material stock is validated on the backend.
            });
        }

        // The rest of the script continues below inside the same DOMContentLoaded block

        if (addLinerItemButton) {
            addLinerItemButton.addEventListener('click', function () {
                addLineItem('liner');
            });
        }

        if (addDuplexItemButton) {
            addDuplexItemButton.addEventListener('click', function () {
                addLineItem('duplex');
            });
        }

        if (linerItemsTableBody) {
            linerItemsTableBody.addEventListener('click', function (event) {
                const deleteButton = event.target.closest('.remove-liner-item');
                if (!deleteButton) return;
                const index = parseInt(deleteButton.dataset.index, 10);
                if (Number.isInteger(index) && index >= 0) {
                    linerItems.splice(index, 1);
                    renderLinerItems();
                }
            });
        }

        if (duplexItemsTableBody) {
            duplexItemsTableBody.addEventListener('click', function (event) {
                const deleteButton = event.target.closest('.remove-duplex-item');
                if (!deleteButton) return;
                const index = parseInt(deleteButton.dataset.index, 10);
                if (Number.isInteger(index) && index >= 0) {
                    duplexItems.splice(index, 1);
                    renderDuplexItems();
                }
            });
        }

        if (customerQuickAddForm) {
            customerQuickAddForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!this.checkValidity()) {
                    this.classList.add('was-validated');
                    return;
                }

                const submitButton = document.getElementById('orderCustomerQuickAddSubmit');
                toggleSubmitButton(submitButton, true, 'Saving...');

                const formData = new FormData(this);
                formData.append('action', 'create_customer_inline');

                fetch('ajax.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            handleAjaxError(data.message || 'Customer save failed.');
                            return;
                        }

                        const customer = data.customer || {};
                        const customerId = String(customer.id || '');
                        if (!customerId) return;

                        customerBrandsMap[customerId] = Array.isArray(customer.brand_names) ? customer.brand_names : [];
                        upsertOption(custSelect, customerId, customer.contact_name || 'New Customer');
                        custSelect.value = customerId;
                        if (typeof refreshSelect2Dropdown === 'function') {
                            refreshSelect2Dropdown(custSelect);
                        }

                        const selectedBrand = customerBrandsMap[customerId].length ? customerBrandsMap[customerId][0] : '';
                        updateBrands(customerId, selectedBrand);
                        filterCostingsByCustomer(customerId);

                        hideModal(customerQuickAddModal);
                        resetQuickForm(customerQuickAddForm);
                    })
                    .catch(() => {
                        handleAjaxError('An unexpected error occurred.');
                    })
                    .finally(() => {
                        toggleSubmitButton(submitButton, false);
                    });
            });
        }

        if (productQuickAddForm) {
            productQuickAddForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!custSelect || !custSelect.value) {
                    handleAjaxError('Please select customer first.');
                    return;
                }
                if (!this.checkValidity()) {
                    this.classList.add('was-validated');
                    return;
                }

                const submitButton = document.getElementById('orderProductQuickAddSubmit');
                toggleSubmitButton(submitButton, true, 'Saving...');

                const formData = new FormData(this);
                formData.append('action', 'create_product_inline');
                formData.append('customer_id', String(custSelect ? (custSelect.value || '') : ''));

                fetch('ajax.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            handleAjaxError(data.message || 'Product save failed.');
                            return;
                        }

                        const product = data.product || {};
                        const productId = String(product.id || '');
                        if (!productId) return;

                        upsertOption(productSelect, productId, product.name || 'New Product', {
                            customerId: product.customer_id || (custSelect ? (custSelect.value || '0') : '0'),
                            defaultLength: product.default_length,
                            defaultWidth: product.default_width,
                            defaultHeight: product.default_height
                        });
                        allProductOptions = Array.from(productSelect.options).map(opt => ({
                            value: opt.value,
                            text: opt.textContent,
                            customerId: opt.dataset.customerId || '',
                            defaultLength: opt.dataset.defaultLength || '',
                            defaultWidth: opt.dataset.defaultWidth || '',
                            defaultHeight: opt.dataset.defaultHeight || ''
                        }));
                        filterProductsByCustomer(String(custSelect ? (custSelect.value || '') : ''));
                        productSelect.value = productId;
                        if (typeof window.refreshSelect2Dropdown === 'function') {
                            window.refreshSelect2Dropdown(productSelect);
                        }
                        updateProductSizePreview();

                        hideModal(productQuickAddModal);
                        resetQuickForm(productQuickAddForm);
                    })
                    .catch(() => {
                        handleAjaxError('Unable to save product right now.');
                    })
                    .finally(() => {
                        toggleSubmitButton(submitButton, false);
                    });
            });
        }

        if (openMaterialModalButtons.length) {
            openMaterialModalButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const context = this.dataset.materialContext || 'liner';
                    const typeName = this.dataset.materialTypeName || '';
                    if (materialContextField) materialContextField.value = context;
                    if (materialQuickAddTitle) materialQuickAddTitle.textContent = `Add ${typeName || 'Material'}`;
                    resetQuickForm(materialQuickAddForm);
                    setModalMaterialTypeByName(typeName);
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

                const submitButton = document.getElementById('orderMaterialQuickAddSubmit');
                toggleSubmitButton(submitButton, true, 'Saving...');

                const formData = new FormData(this);
                formData.append('action', 'create_material_inline');

                fetch('ajax.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            handleAjaxError(data.message || 'Material save failed.');
                            return;
                        }

                        const material = data.material || {};
                        const materialId = String(material.id || '');
                        if (!materialId) return;
                        const materialRate = material.rate !== undefined ? material.rate : 0;
                        const materialQty = material.weight !== undefined ? material.weight : 0;

                        const context = materialContextField ? materialContextField.value : 'liner';
                        if (context === 'duplex') {
                            upsertOption(duplexMaterialSelect, materialId, material.name || 'New Material', {
                                rate: materialRate,
                                qty: materialQty
                            });
                            duplexMaterialSelect.value = materialId;
                            fillMaterialInputs(duplexMaterialSelect, duplexRateInput, duplexQtyInput);
                        } else {
                            upsertOption(linerMaterialSelect, materialId, material.name || 'New Material', {
                                rate: materialRate,
                                qty: materialQty
                            });
                            linerMaterialSelect.value = materialId;
                            fillMaterialInputs(linerMaterialSelect, linerRateInput, linerQtyInput);
                        }

                        hideModal(materialQuickAddModal);
                        resetQuickForm(materialQuickAddForm);
                    })
                    .catch(() => {
                        handleAjaxError('Unable to save material right now.');
                    })
                    .finally(() => {
                        toggleSubmitButton(submitButton, false);
                    });
            });
        }

        document.addEventListener('click', function (e) {
            if (e.target.closest('#addOrderModalBrandBtn')) {
                const container = document.getElementById('orderModalBrandRepeater');
                if (!container) return;
                container.insertAdjacentHTML('beforeend', `
                    <div class="input-group input-group-sm mb-2 order-modal-brand-item">
                        <span class="input-group-text"><i class="bi bi-tag"></i></span>
                        <input type="text" name="brand_names[]" class="form-control" placeholder="Brand Name">
                        <button type="button" class="btn btn-danger removeOrderModalBrand"><i class="bi bi-trash"></i></button>
                    </div>
                `);
            }

            if (e.target.closest('.removeOrderModalBrand')) {
                const item = e.target.closest('.order-modal-brand-item');
                const container = document.getElementById('orderModalBrandRepeater');
                if (container && container.querySelectorAll('.order-modal-brand-item').length > 1) {
                    item.remove();
                } else if (item) {
                    const input = item.querySelector('input');
                    if (input) input.value = '';
                }
            }
        });
    });
</script>

<?php include 'include/footer.php'; ?>

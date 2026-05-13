<?php

include 'root/config.php';
if (!isset($_POST['action'])) {
    echo '';
    exit;
}

if ($_POST['action'] == 'create_customer_inline') {
    header('Content-Type: application/json');

    $contact_name = trim($_POST['contact_name'] ?? '');
    $phone_no = trim($_POST['phone_no'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city_name = trim($_POST['city_name'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $brand_names_post = $_POST['brand_names'] ?? [];

    if ($contact_name === '' || $phone_no === '') {
        echo json_encode(['success' => false, 'message' => 'Customer name and phone number are required.']);
        exit;
    }

    $duplicate = $ai_db->aiGetQuery("SELECT id FROM tbl_customer WHERE phone_no='" . addslashes($phone_no) . "' AND is_deleted=0 LIMIT 1");
    if (!empty($duplicate)) {
        echo json_encode(['success' => false, 'message' => 'This phone number already exists.']);
        exit;
    }

    $brand_names = [];
    if (is_array($brand_names_post)) {
        foreach ($brand_names_post as $bn) {
            $bn = trim($bn);
            if ($bn !== '') {
                $brand_names[] = $bn;
            }
        }
    }
    $brand_names_json = addslashes(json_encode(array_values(array_unique($brand_names)), JSON_UNESCAPED_UNICODE));

    $ai_db->aiQuery("INSERT INTO tbl_customer SET
        contact_name='" . addslashes($contact_name) . "',
        phone_no='" . addslashes($phone_no) . "',
        address='" . addslashes($address) . "',
        city_name='" . addslashes($city_name) . "',
        brand_names='" . $brand_names_json . "',
        status='" . addslashes($status) . "',
        created_by='" . intval($_SESSION['aid'] ?? 0) . "'");

    $insert_id = $ai_db->aiLastInsert();
    echo json_encode([
        'success' => true,
        'message' => 'Customer added successfully.',
        'customer' => [
            'id' => $insert_id,
            'contact_name' => $contact_name,
            'brand_names' => array_values(array_unique($brand_names))
        ]
    ]);
    exit;
}

if ($_POST['action'] == 'create_product_inline') {
    header('Content-Type: application/json');

    $customer_id = intval($_POST['customer_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $rate = is_numeric($_POST['rate'] ?? null) ? round((float) $_POST['rate'], 2) : 0;
    $hsn_code = trim($_POST['hsn_code'] ?? '');
    $default_length = is_numeric($_POST['default_length'] ?? null) ? round((float) $_POST['default_length'], 2) : 0;
    $default_width = is_numeric($_POST['default_width'] ?? null) ? round((float) $_POST['default_width'], 2) : 0;
    $default_height = is_numeric($_POST['default_height'] ?? null) ? round((float) $_POST['default_height'], 2) : 0;
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Product name is required.']);
        exit;
    }

    $duplicate_customer_condition = $customer_id > 0 ? "customer_id='" . $customer_id . "'" : "(customer_id IS NULL OR customer_id='0')";
    $duplicate = $ai_db->aiGetQuery("SELECT id FROM tbl_product WHERE $duplicate_customer_condition AND name='" . addslashes($name) . "' AND is_deleted=0 LIMIT 1");
    if (!empty($duplicate)) {
        echo json_encode(['success' => false, 'message' => 'Product name already exists.']);
        exit;
    }

    $ai_db->aiQuery("INSERT INTO tbl_product SET
        customer_id='" . ($customer_id > 0 ? $customer_id : 0) . "',
        name='" . addslashes($name) . "',
        rate='" . $rate . "',
        hsn_code='" . addslashes($hsn_code) . "',
        default_length='" . $default_length . "',
        default_width='" . $default_width . "',
        default_height='" . $default_height . "',
        description='" . addslashes($description) . "',
        status='" . addslashes($status) . "',
        created_by='" . intval($_SESSION['aid'] ?? 0) . "'");

    $insert_id = $ai_db->aiLastInsert();
    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully.',
        'product' => [
            'id' => $insert_id,
            'customer_id' => $customer_id,
            'name' => $name,
            'rate' => $rate,
            'hsn_code' => $hsn_code,
            'default_length' => $default_length,
            'default_width' => $default_width,
            'default_height' => $default_height
        ]
    ]);
    exit;
}

if ($_POST['action'] == 'create_material_inline') {
    header('Content-Type: application/json');

    $material_type_id = intval($_POST['material_type_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $rate = is_numeric($_POST['rate'] ?? null) ? round((float) $_POST['rate'], 2) : 0;
    $weight = is_numeric($_POST['weight'] ?? null) ? round((float) $_POST['weight'], 2) : 0;
    $f_value = is_numeric($_POST['f_value'] ?? null) ? round((float) $_POST['f_value'], 2) : 0;
    $p_value = is_numeric($_POST['p_value'] ?? null) ? round((float) $_POST['p_value'], 2) : 0;
    $top_value = is_numeric($_POST['top_value'] ?? null) ? round((float) $_POST['top_value'], 2) : 0;
    $status = $_POST['status'] ?? 'active';

    if ($material_type_id <= 0 || $name === '') {
        echo json_encode(['success' => false, 'message' => 'Material type and material name are required.']);
        exit;
    }

    $materialTypeRow = $ai_db->aiGetQuery("SELECT id, name FROM tbl_material_type WHERE id='" . $material_type_id . "' AND status='active' AND is_deleted=0 LIMIT 1");
    if (empty($materialTypeRow)) {
        echo json_encode(['success' => false, 'message' => 'Selected material type is invalid.']);
        exit;
    }

    $duplicate = $ai_db->aiGetQuery("SELECT id FROM tbl_materials WHERE material_type_id='" . $material_type_id . "' AND name='" . addslashes($name) . "' AND is_deleted=0 LIMIT 1");
    if (!empty($duplicate)) {
        echo json_encode(['success' => false, 'message' => 'Material already exists for this type.']);
        exit;
    }

    $ai_db->aiQuery("INSERT INTO tbl_materials SET
        material_type_id='" . $material_type_id . "',
        name='" . addslashes($name) . "',
        rate='" . $rate . "',
        weight='" . $weight . "',
        f_value='" . $f_value . "',
        p_value='" . $p_value . "',
        top_value='" . $top_value . "',
        status='" . addslashes($status) . "',
        created_by='" . intval($_SESSION['aid'] ?? 0) . "'");

    $insert_id = $ai_db->aiLastInsert();
    echo json_encode([
        'success' => true,
        'message' => 'Material added successfully.',
        'material' => [
            'id' => $insert_id,
            'name' => $name,
            'material_type_id' => $material_type_id,
            'material_type_name' => $materialTypeRow[0]['name'],
            'rate' => $rate,
            'weight' => $weight,
            'f_value' => $f_value,
            'p_value' => $p_value,
            'top_value' => $top_value
        ]
    ]);
    exit;
}

if ($_POST['action'] == 'create_brand_inline') {
    header('Content-Type: application/json');

    $customer_id = intval($_POST['customer_id'] ?? 0);
    $brand_name = trim($_POST['brand_name'] ?? '');

    if ($customer_id <= 0 || $brand_name === '') {
        echo json_encode(['success' => false, 'message' => 'Customer selection and brand name are required.']);
        exit;
    }

    $customerRow = $ai_db->aiGetQuery("SELECT id, brand_names FROM tbl_customer WHERE id='" . $customer_id . "' AND is_deleted=0 LIMIT 1");
    if (empty($customerRow)) {
        echo json_encode(['success' => false, 'message' => 'Selected customer is invalid.']);
        exit;
    }

    $existing_brands = [];
    $raw_brands = $customerRow[0]['brand_names'] ?? '';
    if ($raw_brands !== '') {
        $decoded = json_decode($raw_brands, true);
        if (is_array($decoded)) {
            $existing_brands = $decoded;
        } else {
            $splitBrands = preg_split('/[\r\n,]+/', $raw_brands);
            $existing_brands = is_array($splitBrands) ? $splitBrands : [$raw_brands];
        }
    }

    if (in_array($brand_name, $existing_brands)) {
        echo json_encode(['success' => false, 'message' => 'This brand already exists for the selected customer.']);
        exit;
    }

    $existing_brands[] = $brand_name;
    $brand_names_json = addslashes(json_encode(array_values($existing_brands), JSON_UNESCAPED_UNICODE));

    $ai_db->aiQuery("UPDATE tbl_customer SET brand_names='" . $brand_names_json . "' WHERE id='" . $customer_id . "'");

    echo json_encode([
        'success' => true,
        'message' => 'Brand added successfully.',
        'brand_name' => $brand_name,
        'customer_id' => $customer_id,
        'all_brands' => $existing_brands
    ]);
    exit;
}

if ($_POST['action'] == 'login') {
    header('Content-Type: application/json');

    $email = isset($_POST['email']) ? addslashes($_POST['email']) : '';
    $password = isset($_POST['password']) ? md5($_POST['password']) : '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
        exit;
    }

    $qry = "SELECT * FROM " . DB_PREFIX . "admin WHERE (email='" . $email . "' OR username='" . $email . "')";
    $row = $ai_db->aiGetQuery($qry);

    if (count($row) > 0) {
        $user = $row[0];
        if ($password == $user['password']) {
            if ($user['is_active'] == '1') {
                $_SESSION['aid'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['username'] = $user['username'];
                echo json_encode(['success' => true, 'message' => 'Login successful! Redirecting...']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Your account is not active.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
    exit;
}
if ($_POST['action'] == 'check-password') {
    $OldPassword = md5($_POST['old_pass']);
    $qry = "SELECT * FROM " . DB_PREFIX . "admin WHERE password='" . $OldPassword . "' AND id=" . $_SESSION['aid'];
    $result = $ai_db->aiGetQuery($qry);
    if (empty($result)) {
        echo 'fail';
    } else {
        echo 'success';
    }
    exit;
}

if ($_POST['action'] == 'update-password') {
    header('Content-Type: application/json');
    $old_pass = md5($_POST['old_password'] ?? '');
    $new_pass = md5($_POST['new_password'] ?? '');
    
    $check = $ai_db->aiGetQuery("SELECT id FROM " . DB_PREFIX . "admin WHERE id=" . $_SESSION['aid'] . " AND password='$old_pass'");
    if (empty($check)) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }
    
    $ai_db->aiQuery("UPDATE " . DB_PREFIX . "admin SET password='$new_pass' WHERE id=" . $_SESSION['aid']);
    echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
    exit;
}

if ($_POST['action'] == 'get_costing_data') {
    header('Content-Type: application/json');
    $costing_id = intval($_POST['costing_id'] ?? 0);
    if ($costing_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid costing ID']);
        exit;
    }
    $costing = $ai_db->aiGetQuery("SELECT * FROM tbl_costings WHERE id = $costing_id AND is_deleted = 0");
    if (empty($costing)) {
        echo json_encode(['success' => false, 'message' => 'Costing not found']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $costing[0]]);
    exit;
}

if ($_POST['action'] == 'get_last_order_prefill') {
    header('Content-Type: application/json');

    $customer_id = intval($_POST['customer_id'] ?? 0);
    $product_id = intval($_POST['product_id'] ?? 0);

    if ($customer_id <= 0 || $product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Customer and product are required.']);
        exit;
    }

    $orderRes = $ai_db->aiGetQuery("SELECT * FROM tbl_orders WHERE is_deleted=0 AND customer_id='" . $customer_id . "' AND product_id='" . $product_id . "' ORDER BY id DESC LIMIT 1");
    if (empty($orderRes)) {
        echo json_encode(['success' => false, 'message' => 'No previous order found for selected customer and product.']);
        exit;
    }

    $order = $orderRes[0];
    $order_id = intval($order['id'] ?? 0);
    $items = [];
    if ($order_id > 0) {
        $items = $ai_db->aiGetQuery("SELECT * FROM tbl_orders_item WHERE order_id='" . $order_id . "' ORDER BY id ASC");
    }

    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
    exit;
}

if ($_POST['action'] == 'get_last_costing_prefill') {
    header('Content-Type: application/json');

    $customer_id = intval($_POST['customer_id'] ?? 0);
    $product_id = intval($_POST['product_id'] ?? 0);

    if ($customer_id <= 0 || $product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Customer and product are required.']);
        exit;
    }

    $costingRes = $ai_db->aiGetQuery("SELECT * FROM tbl_costings WHERE is_deleted=0 AND customer_id='" . $customer_id . "' AND product_id='" . $product_id . "' ORDER BY id DESC LIMIT 1");
    if (empty($costingRes)) {
        echo json_encode(['success' => false, 'message' => 'No previous costing found for selected customer and product.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'costing' => $costingRes[0]
    ]);
    exit;
}

if ($_POST['action'] == 'get_last_quotation_prefill') {
    header('Content-Type: application/json');

    $customer_id = intval($_POST['customer_id'] ?? 0);
    $product_id = intval($_POST['product_id'] ?? 0);

    if ($customer_id <= 0 || $product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Customer and product are required.']);
        exit;
    }

    $matchRes = $ai_db->aiGetQuery("
        SELECT q.id AS quotation_id, qi.id AS item_id
        FROM tbl_quotations q
        INNER JOIN tbl_quotation_items qi ON qi.quotation_id = q.id
        WHERE q.is_deleted=0
          AND q.customer_id='" . $customer_id . "'
          AND qi.product_id='" . $product_id . "'
        ORDER BY q.id DESC, qi.id DESC
        LIMIT 1
    ");

    if (empty($matchRes)) {
        echo json_encode(['success' => false, 'message' => 'No previous quotation found for selected customer and product.']);
        exit;
    }

    $quotation_id = intval($matchRes[0]['quotation_id'] ?? 0);
    $item_id = intval($matchRes[0]['item_id'] ?? 0);

    $quotationRes = $ai_db->aiGetQuery("SELECT * FROM tbl_quotations WHERE id='" . $quotation_id . "' AND is_deleted=0 LIMIT 1");
    $itemRes = $ai_db->aiGetQuery("SELECT * FROM tbl_quotation_items WHERE id='" . $item_id . "' LIMIT 1");

    if (empty($quotationRes) || empty($itemRes)) {
        echo json_encode(['success' => false, 'message' => 'Matching quotation data not found.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'quotation' => $quotationRes[0],
        'item' => $itemRes[0]
    ]);
    exit;
}

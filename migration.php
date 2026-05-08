<?php
include 'root/config.php';

$queries = [
    // 1. Sempal Table (Sample File Table)
    "CREATE TABLE IF NOT EXISTS `tbl_sempal` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `image` varchar(255) DEFAULT NULL,
        `title` varchar(255) DEFAULT NULL,
        `description` text,
        `main_title` varchar(255) DEFAULT NULL,
        `icon_data` text,
        `status` enum('active','deactive') DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 2. Customer Table
    "CREATE TABLE IF NOT EXISTS `tbl_customer` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `contact_name` varchar(255) NOT NULL,
        `phone_no` varchar(20) DEFAULT NULL,
        `address` text,
        `city_name` varchar(255) DEFAULT NULL,
        `status` enum('active','deactive') DEFAULT 'active',
        `brand_names` text,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `deleted_by` int(11) DEFAULT NULL,
        `deleted_at` timestamp NULL DEFAULT NULL,
        `is_deleted` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `created_by` (`created_by`),
        KEY `updated_by` (`updated_by`),
        KEY `deleted_by` (`deleted_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 3. Product Table
    "CREATE TABLE IF NOT EXISTS `tbl_product` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `rate` decimal(10,2) DEFAULT NULL,
        `hsn_code` varchar(50) DEFAULT NULL,
        `default_length` decimal(10,2) DEFAULT NULL,
        `default_width` decimal(10,2) DEFAULT NULL,
        `default_height` decimal(10,2) DEFAULT NULL,
        `description` text,
        `status` enum('active','deactive') DEFAULT 'active',
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `deleted_by` int(11) DEFAULT NULL,
        `deleted_at` timestamp NULL DEFAULT NULL,
        `is_deleted` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `created_by` (`created_by`),
        KEY `updated_by` (`updated_by`),
        KEY `deleted_by` (`deleted_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 4. Product BOM Table
    "CREATE TABLE IF NOT EXISTS `tbl_product_bom` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_id` int(11) NOT NULL,
        `material_name` varchar(255) NOT NULL,
        `rate` decimal(10,2) DEFAULT NULL,
        `qty` decimal(10,2) DEFAULT NULL,
        `unit` varchar(50) DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `deleted_by` int(11) DEFAULT NULL,
        `deleted_at` timestamp NULL DEFAULT NULL,
        `is_deleted` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`),
        KEY `created_by` (`created_by`),
        KEY `updated_by` (`updated_by`),
        KEY `deleted_by` (`deleted_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 5. Material Type Table
    "CREATE TABLE IF NOT EXISTS `tbl_material_type` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `status` enum('active','deactive') DEFAULT 'active',
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `deleted_by` int(11) DEFAULT NULL,
        `deleted_at` timestamp NULL DEFAULT NULL,
        `is_deleted` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `created_by` (`created_by`),
        KEY `updated_by` (`updated_by`),
        KEY `deleted_by` (`deleted_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 6. Materials Table
    "CREATE TABLE IF NOT EXISTS `tbl_materials` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `material_type_id` int(11) NOT NULL,
        `name` varchar(255) NOT NULL,
        `f_value` decimal(10,2) DEFAULT NULL,
        `p_value` decimal(10,2) DEFAULT NULL,
        `top_value` decimal(10,2) DEFAULT NULL,
        `rate` decimal(10,2) DEFAULT NULL,
        `weight` decimal(10,2) DEFAULT NULL,
        `status` enum('active','deactive') DEFAULT 'active',
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `deleted_by` int(11) DEFAULT NULL,
        `deleted_at` timestamp NULL DEFAULT NULL,
        `is_deleted` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `material_type_id` (`material_type_id`),
        KEY `created_by` (`created_by`),
        KEY `updated_by` (`updated_by`),
        KEY `deleted_by` (`deleted_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 7. Offset Table
    "CREATE TABLE IF NOT EXISTS `tbl_offset` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `contact_number` varchar(20) DEFAULT NULL,
        `status` enum('active','deactive') DEFAULT 'active',
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `deleted_by` int(11) DEFAULT NULL,
        `deleted_at` timestamp NULL DEFAULT NULL,
        `is_deleted` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `created_by` (`created_by`),
        KEY `updated_by` (`updated_by`),
        KEY `deleted_by` (`deleted_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 8. Costings Table
    "CREATE TABLE IF NOT EXISTS `tbl_costings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `estimate_no` varchar(50) NOT NULL,
        `estimate_date` date DEFAULT NULL,
        `customer_id` int(11) DEFAULT NULL,
        `customer_name` varchar(255) DEFAULT NULL,
        `brand_name` varchar(255) DEFAULT NULL,
        `product_id` int(11) DEFAULT NULL,
        `product_name` varchar(255) DEFAULT NULL,
        `sheet_length` decimal(10,2) DEFAULT NULL,
        `sheet_width` decimal(10,2) DEFAULT NULL,
        `sheet_height` decimal(10,2) DEFAULT NULL,
        `sheet_unit` varchar(25) DEFAULT 'inch',
        `liner_items` longtext,
        `liner_rate` decimal(10,2) DEFAULT NULL,
        `duplex_material_id` int(11) DEFAULT NULL,
        `duplex_name` varchar(255) DEFAULT NULL,
        `duplex_input_rate` decimal(10,2) DEFAULT NULL,
        `duplex_weight` decimal(10,2) DEFAULT NULL,
        `duplex_rate` decimal(10,2) DEFAULT NULL,
        `printing` decimal(10,2) DEFAULT NULL,
        `laminas_name` varchar(255) DEFAULT NULL,
        `laminas_value` decimal(10,2) DEFAULT NULL,
        `laminas_unit` varchar(50) DEFAULT NULL,
        `pesting` decimal(10,2) DEFAULT NULL,
        `punching` decimal(10,2) DEFAULT NULL,
        `pin_rate` decimal(10,2) DEFAULT NULL,
        `pin_qty` decimal(10,2) DEFAULT NULL,
        `side_pesting` decimal(10,2) DEFAULT NULL,
        `uv_coating` decimal(10,2) DEFAULT NULL,
        `rixa_bhadu` decimal(10,2) DEFAULT NULL,
        `total` decimal(10,2) DEFAULT NULL,
        `upps` decimal(10,2) DEFAULT '1.00',
        `single_rate` decimal(10,2) DEFAULT NULL,
        `profit` decimal(10,2) DEFAULT NULL,
        `profit_percent` decimal(10,2) DEFAULT NULL,
        `sale_rate` decimal(10,2) DEFAULT NULL,
        `remark` text,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `deleted_by` int(11) DEFAULT NULL,
        `deleted_at` timestamp NULL DEFAULT NULL,
        `is_deleted` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `customer_id` (`customer_id`),
        KEY `product_id` (`product_id`),
        KEY `created_by` (`created_by`),
        KEY `updated_by` (`updated_by`),
        KEY `deleted_by` (`deleted_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 9. Orders Table
    "CREATE TABLE IF NOT EXISTS `tbl_orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_no` varchar(50) NOT NULL,
        `order_date` date DEFAULT NULL,
        `customer_id` int(11) DEFAULT NULL,
        `customer_name` varchar(255) DEFAULT NULL,
        `brand_name` varchar(255) DEFAULT NULL,
        `product_id` int(11) DEFAULT NULL,
        `product_name` varchar(255) DEFAULT NULL,
        `box_qty` decimal(10,2) DEFAULT NULL,
        `box_qty_unit` varchar(20) DEFAULT 'PCS',
        `upps` decimal(10,2) DEFAULT '1.00',
        `upps_unit` varchar(20) DEFAULT 'Upps',
        `rate` decimal(10,2) DEFAULT NULL,
        `costing_id` int(11) DEFAULT NULL,
        `sheet_length` decimal(10,2) DEFAULT NULL,
        `sheet_width` decimal(10,2) DEFAULT NULL,
        `md_code` varchar(100) DEFAULT NULL,
        `plate_status` varchar(20) DEFAULT 'No',
        `print_status` varchar(20) DEFAULT 'No',
        `die_status` varchar(20) DEFAULT 'No',
        `status` enum('active','deactive') DEFAULT 'active',
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `deleted_by` int(11) DEFAULT NULL,
        `deleted_at` timestamp NULL DEFAULT NULL,
        `is_deleted` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `customer_id` (`customer_id`),
        KEY `product_id` (`product_id`),
        KEY `costing_id` (`costing_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 10. Orders Items Table
    "CREATE TABLE IF NOT EXISTS `tbl_orders_item` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `item_group` varchar(50) DEFAULT NULL,
        `material_id` int(11) DEFAULT NULL,
        `material_name` varchar(255) DEFAULT NULL,
        `name` varchar(255) DEFAULT NULL,
        `rate` decimal(10,2) DEFAULT NULL,
        `qty` decimal(10,2) DEFAULT NULL,
        `pcs` decimal(10,2) DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `material_id` (`material_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

echo "<h2>Starting Migration...</h2>";
foreach ($queries as $sql) {
    if ($ai_db->aiQuery($sql)) {
        echo "<p style='color:green;'>SUCCESS: " . substr($sql, 0, 80) . "...</p>";
    } else {
        echo "<p style='color:red;'>FAILED: " . substr($sql, 0, 80) . "...</p>";
    }
}
echo "<h2>Migration Completed.</h2>";
?>

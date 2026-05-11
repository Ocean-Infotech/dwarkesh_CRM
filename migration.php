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

    // 7.1 Lamination Table
    "CREATE TABLE IF NOT EXISTS `tbl_lamination` (
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

    // 9. Quotations Table
    "CREATE TABLE IF NOT EXISTS `tbl_quotations` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `quotation_no` varchar(50) NOT NULL,
        `quotation_date` date DEFAULT NULL,
        `valid_till` date DEFAULT NULL,
        `customer_id` int(11) DEFAULT NULL,
        `customer_name` varchar(255) DEFAULT NULL,
        `total_taxable` decimal(10,2) DEFAULT '0.00',
        `total_amount` decimal(10,2) DEFAULT '0.00',
        `remark` text,
        `status` enum('pending','accepted','rejected','expired') DEFAULT 'pending',
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `deleted_by` int(11) DEFAULT NULL,
        `deleted_at` timestamp NULL DEFAULT NULL,
        `is_deleted` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `customer_id` (`customer_id`),
        KEY `created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 9.1 Quotation Items Table
    "CREATE TABLE IF NOT EXISTS `tbl_quotation_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `quotation_id` int(11) NOT NULL,
        `product_id` int(11) DEFAULT NULL,
        `product_name` varchar(255) DEFAULT NULL,
        `description` text,
        `qty` decimal(10,2) DEFAULT '1.00',
        `unit` varchar(50) DEFAULT 'nos',
        `rate` decimal(10,2) DEFAULT '0.00',
        `taxable_amount` decimal(10,2) DEFAULT '0.00',
        `total_amount` decimal(10,2) DEFAULT '0.00',
        `costing_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `quotation_id` (`quotation_id`),
        KEY `product_id` (`product_id`),
        KEY `costing_id` (`costing_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 10. Orders Table
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 11. Stock History Table (New Module)
    "CREATE TABLE IF NOT EXISTS `tbl_stock_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `item_type` enum('product','material') NOT NULL,
        `item_id` int(11) NOT NULL,
        `qty` decimal(10,2) NOT NULL,
        `action_type` enum('plus','minus') NOT NULL,
        `remarks` text,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `item_type_id` (`item_type`, `item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // --- ALTER TABLE QUERIES FOR MODULE UPDATES ---

    // Sync tbl_product for Inventory Module
    "ALTER TABLE `tbl_product` ADD COLUMN IF NOT EXISTS `mapped_material_id` int(11) DEFAULT NULL AFTER `description` ",
    "ALTER TABLE `tbl_product` ADD COLUMN IF NOT EXISTS `usage_qty` decimal(10,2) DEFAULT '0.00' AFTER `mapped_material_id` ",
    "ALTER TABLE `tbl_product` ADD COLUMN IF NOT EXISTS `stock_qty` decimal(10,2) DEFAULT '0.00' AFTER `usage_qty` ",

    // Sync tbl_materials for Inventory Module
    "ALTER TABLE `tbl_materials` ADD COLUMN IF NOT EXISTS `stock_qty` decimal(10,2) DEFAULT '0.00' AFTER `weight` ",

    // Sync tbl_costings
    "ALTER TABLE `tbl_costings` ADD COLUMN IF NOT EXISTS `duplex_items` longtext AFTER `liner_rate` ",

    // Sync tbl_orders (Advanced Order Module)
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `liner_delivery_id` int(11) DEFAULT NULL AFTER `die_status` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `liner_delivery_phone` varchar(50) DEFAULT NULL AFTER `liner_delivery_id` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `top_count` varchar(100) DEFAULT NULL AFTER `liner_delivery_phone` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `duplex_delivery_id` int(11) DEFAULT NULL AFTER `top_count` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `duplex_delivery_phone` varchar(50) DEFAULT NULL AFTER `duplex_delivery_id` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `printing_by_id` int(11) DEFAULT NULL AFTER `duplex_delivery_phone` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `offset_image` varchar(255) DEFAULT NULL AFTER `printing_by_id` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `print_color` varchar(255) DEFAULT NULL AFTER `offset_image` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `print_qty` varchar(255) DEFAULT NULL AFTER `print_color` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `print_delivery_id` int(11) DEFAULT NULL AFTER `print_qty` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `print_delivery_phone` varchar(50) DEFAULT NULL AFTER `print_delivery_id` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `die_maker` varchar(255) DEFAULT NULL AFTER `print_delivery_phone` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `die_code` varchar(100) DEFAULT NULL AFTER `die_maker` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `c_die_code` varchar(100) DEFAULT NULL AFTER `die_code` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `designer` varchar(255) DEFAULT NULL AFTER `c_die_code` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `plate` varchar(255) DEFAULT NULL AFTER `designer` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `half_film` tinyint(1) DEFAULT 0 AFTER `plate` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `full_film` tinyint(1) DEFAULT 0 AFTER `half_film` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `lamination_type` varchar(100) DEFAULT NULL AFTER `full_film` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `lamination_extra` varchar(255) DEFAULT NULL AFTER `lamination_type` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `laminas_delivery_id` int(11) DEFAULT NULL AFTER `lamination_extra` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `laminas_delivery_phone` varchar(50) DEFAULT NULL AFTER `laminas_delivery_id` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `job_pesting` tinyint(1) DEFAULT 0 AFTER `laminas_delivery_phone` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `job_pin` tinyint(1) DEFAULT 0 AFTER `job_pesting` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `job_punching` tinyint(1) DEFAULT 0 AFTER `job_pin` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `job_side_pesting` tinyint(1) DEFAULT 0 AFTER `job_punching` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `bill_design` varchar(255) DEFAULT NULL AFTER `job_side_pesting` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `bill_plate` varchar(255) DEFAULT NULL AFTER `bill_design` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `bill_daei` varchar(255) DEFAULT NULL AFTER `bill_plate` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `bill_photo_price` varchar(255) DEFAULT NULL AFTER `bill_daei` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `bill_pcs` varchar(255) DEFAULT NULL AFTER `bill_photo_price` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `bill_rixa_bhadu` varchar(255) DEFAULT NULL AFTER `bill_pcs` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `bill_borrow_charge` varchar(255) DEFAULT NULL AFTER `bill_rixa_bhadu` ",
    "ALTER TABLE `tbl_orders` ADD COLUMN IF NOT EXISTS `bill_remark` text AFTER `bill_borrow_charge` ",
    "ALTER TABLE `tbl_quotations` ADD COLUMN IF NOT EXISTS `valid_till` date DEFAULT NULL AFTER `quotation_date` "
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
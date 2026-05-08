<?php

if (!function_exists('dwarkesh_ensure_core_tables')) {
    function dwarkesh_ensure_core_tables($ai_db)
    {
        static $schema_ready = false;

        if ($schema_ready || !$ai_db) {
            return;
        }

        $queries = [
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
                `duplex_items` longtext,
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

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
                `liner_delivery_id` int(11) DEFAULT NULL,
                `liner_delivery_phone` varchar(50) DEFAULT NULL,
                `top_count` varchar(100) DEFAULT NULL,
                `duplex_delivery_id` int(11) DEFAULT NULL,
                `duplex_delivery_phone` varchar(50) DEFAULT NULL,
                `printing_by_id` int(11) DEFAULT NULL,
                `offset_image` varchar(255) DEFAULT NULL,
                `print_color` varchar(255) DEFAULT NULL,
                `print_qty` varchar(255) DEFAULT NULL,
                `print_delivery_id` int(11) DEFAULT NULL,
                `print_delivery_phone` varchar(50) DEFAULT NULL,
                `die_maker` varchar(255) DEFAULT NULL,
                `die_code` varchar(100) DEFAULT NULL,
                `c_die_code` varchar(100) DEFAULT NULL,
                `designer` varchar(255) DEFAULT NULL,
                `plate` varchar(255) DEFAULT NULL,
                `half_film` tinyint(1) DEFAULT 0,
                `full_film` tinyint(1) DEFAULT 0,
                `lamination_type` varchar(100) DEFAULT NULL,
                `lamination_extra` varchar(255) DEFAULT NULL,
                `laminas_delivery_id` int(11) DEFAULT NULL,
                `laminas_delivery_phone` varchar(50) DEFAULT NULL,
                `job_pesting` tinyint(1) DEFAULT 0,
                `job_pin` tinyint(1) DEFAULT 0,
                `job_punching` tinyint(1) DEFAULT 0,
                `job_side_pesting` tinyint(1) DEFAULT 0,
                `bill_design` varchar(255) DEFAULT NULL,
                `bill_plate` varchar(255) DEFAULT NULL,
                `bill_daei` varchar(255) DEFAULT NULL,
                `bill_photo_price` varchar(255) DEFAULT NULL,
                `bill_pcs` varchar(255) DEFAULT NULL,
                `bill_rixa_bhadu` varchar(255) DEFAULT NULL,
                `bill_borrow_charge` varchar(255) DEFAULT NULL,
                `bill_remark` text,
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];

        // Ensure stock columns exist
        $ai_db->aiQuery("ALTER TABLE `tbl_product` ADD COLUMN IF NOT EXISTS `mapped_material_id` int(11) DEFAULT NULL AFTER `description` ");
        $ai_db->aiQuery("ALTER TABLE `tbl_product` ADD COLUMN IF NOT EXISTS `usage_qty` decimal(10,2) DEFAULT '0.00' AFTER `mapped_material_id` ");
        $ai_db->aiQuery("ALTER TABLE `tbl_product` ADD COLUMN IF NOT EXISTS `stock_qty` decimal(10,2) DEFAULT '0.00' AFTER `usage_qty` ");
        $ai_db->aiQuery("ALTER TABLE `tbl_materials` ADD COLUMN IF NOT EXISTS `stock_qty` decimal(10,2) DEFAULT '0.00' AFTER `weight` ");

        foreach ($queries as $query) {
            $ai_db->aiQuery($query);
        }

        // Ensure new columns exist for existing tables
        $columns = $ai_db->aiGetQuery("SHOW COLUMNS FROM `tbl_costings` LIKE 'duplex_items'");
        if (empty($columns)) {
            $ai_db->aiQuery("ALTER TABLE `tbl_costings` ADD `duplex_items` longtext AFTER `liner_rate` ");
        }

        $order_columns_list = [
            'liner_delivery_id' => "int(11) DEFAULT NULL",
            'liner_delivery_phone' => "varchar(50) DEFAULT NULL",
            'top_count' => "varchar(100) DEFAULT NULL",
            'duplex_delivery_id' => "int(11) DEFAULT NULL",
            'duplex_delivery_phone' => "varchar(50) DEFAULT NULL",
            'printing_by_id' => "int(11) DEFAULT NULL",
            'offset_image' => "varchar(255) DEFAULT NULL",
            'print_color' => "varchar(255) DEFAULT NULL",
            'print_qty' => "varchar(255) DEFAULT NULL",
            'print_delivery_id' => "int(11) DEFAULT NULL",
            'print_delivery_phone' => "varchar(50) DEFAULT NULL",
            'die_maker' => "varchar(255) DEFAULT NULL",
            'die_code' => "varchar(100) DEFAULT NULL",
            'c_die_code' => "varchar(100) DEFAULT NULL",
            'designer' => "varchar(255) DEFAULT NULL",
            'plate' => "varchar(255) DEFAULT NULL",
            'half_film' => "tinyint(1) DEFAULT 0",
            'full_film' => "tinyint(1) DEFAULT 0",
            'lamination_type' => "varchar(100) DEFAULT NULL",
            'lamination_extra' => "varchar(255) DEFAULT NULL",
            'laminas_delivery_id' => "int(11) DEFAULT NULL",
            'laminas_delivery_phone' => "varchar(50) DEFAULT NULL",
            'job_pesting' => "tinyint(1) DEFAULT 0",
            'job_pin' => "tinyint(1) DEFAULT 0",
            'job_punching' => "tinyint(1) DEFAULT 0",
            'job_side_pesting' => "tinyint(1) DEFAULT 0",
            'bill_design' => "varchar(255) DEFAULT NULL",
            'bill_plate' => "varchar(255) DEFAULT NULL",
            'bill_daei' => "varchar(255) DEFAULT NULL",
            'bill_photo_price' => "varchar(255) DEFAULT NULL",
            'bill_pcs' => "varchar(255) DEFAULT NULL",
            'bill_rixa_bhadu' => "varchar(255) DEFAULT NULL",
            'bill_borrow_charge' => "varchar(255) DEFAULT NULL",
            'bill_remark' => "text"
        ];

        $existing_cols = [];
        $res = $ai_db->aiGetQuery("SHOW COLUMNS FROM `tbl_orders` ");
        foreach ($res as $r) {
            $existing_cols[strtolower($r['Field'])] = true;
        }

        foreach ($order_columns_list as $col => $def) {
            if (!isset($existing_cols[strtolower($col)])) {
                $ai_db->aiQuery("ALTER TABLE `tbl_orders` ADD `$col` $def");
            }
        }

        $schema_ready = true;
    }
}

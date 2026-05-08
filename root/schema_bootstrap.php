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

        $schema_ready = true;
    }
}

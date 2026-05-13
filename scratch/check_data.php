<?php
include 'root/config.php';
$materials = $ai_db->aiGetQuery("SELECT id, name FROM tbl_materials WHERE is_deleted = 0");
echo "All Active Materials:\n";
print_r($materials);
?>

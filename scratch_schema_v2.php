<?php
include 'root/config.php';
function dump($table) {
    global $ai_db;
    echo "--- $table ---\n";
    $res = $ai_db->aiGetQuery("SHOW COLUMNS FROM $table");
    if($res) {
        foreach($res as $row) echo $row['Field'] . " (" . $row['Type'] . ")\n";
    } else {
        echo "Table $table not found\n";
    }
    echo "\n";
}
dump('tbl_product');
dump('tbl_materials');
dump('tbl_orders');
?>

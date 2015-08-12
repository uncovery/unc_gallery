<?php

$file_raw = dirname(__FILE__);
$file = substr($file_raw, 0, stripos($file_raw, "wp-content"));
require_once($file . "/wp-load.php");

global $WPG_CONFIG;
$dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];

$count = count($_FILES["userImage"]["name"]);
if ($count < 1) {
    echo "No images found to upload";
    return false;
}
echo "Processing $count image(s)....<br>";

//array(1) {
//    ["userImage"]=> array(5) {
//        ["name"]=> array(1) { [0]=> string(23) "2013-11-02 21.00.38.jpg" }
//        ["type"]=> array(1) { [0]=> string(10) "image/jpeg" }
//        ["tmp_name"]=> array(1) { [0]=> string(14) "/tmp/phptgNK2k" }
//        ["error"]=> array(1) { [0]=> int(0) }
//        ["size"]=> array(1) { [0]=> int(213485) }
//    }
//}

$F = $_FILES["userImage"];

for ($i=0; $i<$count; $i++){
    if (!in_array($F["type"][$i], $WPG_CONFIG['valid_filetypes'])){
        echo "Invalid file type :" . $F["type"][$i];
        continue;
    }
    if ($F["error"][$i] > 0){
        echo "File Error : " . $F["error"][$i]  . "<br />";
        continue;
    }
    $filename = $_FILES['userImage']['name'][$i];
    $check_file_exit = $dirPath . '/' .$filename;
    if (file_exists($check_file_exit)) {
        echo "<br>" . $F['name'][$i] . " Filename alredy exit in folder";
    } else {
        if (is_uploaded_file($F['tmp_name'][$i])) {
            $sourcePath = $F['tmp_name'][$i];
            if (move_uploaded_file($sourcePath, $check_file_exit)) {
                echo "<br>" . $F['name'][$i] . " moved successfully";
            }
        }
    }
}
// delete image record start
if (isset($_REQUEST['delete_id'])){
    $table_name = $wpdb->prefix . 'image_info';
    $pageposts = $wpdb->get_results("SELECT * from $table_name WHERE image_id=" . $_REQUEST['delete_id']);
    if ($pageposts) {
        foreach ($pageposts as $post) {
            $remove_image = $dirPath . '/' . $post->image_name;
            unlink($remove_image);
        }
    } else {
        echo "Image Not found in folder";
    }
    $delete = $wpdb->query("DELETE FROM $table_name WHERE image_id = '" . $_REQUEST['delete_id'] . "'");
}
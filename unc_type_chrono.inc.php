<?php
/**
 * This file handles all chrono-type shortcodes variables and
 * output.
 * 
 * this is meant to create a timeline output. unfinished.
 * 
 */

if (!defined('WPINC')) {
    die;
}

function unc_chrono_var_init($a) {
    global $UNC_GALLERY;

    $chrono_str = $a['chrono'];
    if ($chrono_str == '') {
        $chrono_arr = false;
    } else {
        $chrono_arr = explode("|", $chrono_str);
    }

    $UNC_GALLERY['display']['chrono_arr'] = $chrono_arr;
    $UNC_GALLERY['display']['files'] = unc_chrono_files($chrono_arr) ;
    $UNC_GALLERY['display']['file'] = false;
    return true;
}

function unc_chrono_data($chrono_arr) {
    global $UNC_GALLERY, $wpdb;

    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $count_sql = "SELECT count(`id`) as `counter` FROM $img_table_name;";
    $counter = $wpdb->get_results($count_sql, 'ARRAY_A');
    $row_count = $counter[0]['counter'];
    $out = "";

    $limit = 49;

    if ($row_count > $limit) {
        $next_page = $chrono_arr[0] + 1;
        $display_page = $next_page;
        $next_page_link = " <a class=\"next_page_link\" onclick=\"chrono_select($next_page)\">Next Page</a>";
        $out .= "<br>More than $limit images found, showing page $display_page ($row_count results)." . $next_page_link;
    }

    return $out;

}

function unc_chrono_files($chrono_arr) {
    global $UNC_GALLERY, $wpdb;

    $img_table_name = $wpdb->prefix . "unc_gallery_img";

    $limit = 49;
    $offset_str = '';

    // $last_val = count($chrono_arr) - 1;
    $page = $chrono_arr[0];

    if ($page > 0) {
        $offset = $page * $limit;
        $offset_str = " OFFSET $offset";
    }

    $sql = "SELECT `file_path`, `file_time` FROM $img_table_name
        ORDER BY $img_table_name.file_time DESC
        LIMIT $limit $offset_str;";

    $files = $wpdb->get_results($sql, 'ARRAY_A');
    $myfiles = array();
    foreach ($files as $file) {
        $file_path = $file['file_path'];
        $file_date = $file['file_time'];
        $F = unc_image_info_read($file_path);
        $F['featured'] = false;
        $myfiles[$file_date] = $F;
    }

    krsort($myfiles);
    return $myfiles;
}

/**
 * This is the function called by Ajax when the filter dropdown on the website is updated
 *
 * @global type $wpdb
 * @global type $UNC_GALLERY
 * @return type
 */
function unc_chrono_update(){
    global $UNC_GALLERY;

    $page_raw = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);

    $page = intval($page_raw);

    $chrono_str = "$page";

    // the following line has ECHO = FALSE because we do the echo here
    unc_gallery_display_var_init(array('type' => 'chrono', 'chrono' => $chrono_str, 'ajax_show' => 'all'));
    ob_clean();
    echo unc_gallery_display_page();
    wp_die();
}
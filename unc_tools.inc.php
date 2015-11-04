<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Take a date string and create the respective folders
 *
 * @global type $WPG_CONFIG
 * @param type $i
 * @param type $date_str
 * @return type
 */
function unc_date_folder_create($date_str) {
    global $WPG_CONFIG;

    $date_folders = array(false, "Y", "m", "d");
    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];
    $date_obj = unc_datetime($date_str);
    // substract 12 hours to get the correct date
    $date_obj->modify($WPG_CONFIG['offset']);

    $path_arr = array($WPG_CONFIG['photos'], $WPG_CONFIG['thumbnails']);
    foreach ($path_arr as $img_folder) {
        $base_folder = $dirPath . $img_folder;
        foreach ($date_folders as $date_folder) {
            if ($date_folder) {
                $date_element = $date_obj->format($date_folder);
                $base_folder .= DIRECTORY_SEPARATOR . "$date_element";
            }
            if (!file_exists($base_folder)) {
                $mkdir_chk = mkdir($base_folder);
                if (!$mkdir_chk) {
                    echo "ERROR, could not create folder $base_folder<br>";
                    return false;
                } else {
                    echo "Created folder $base_folder<br>";
                }
            }
        }
    }
    return $date_obj;
}


/*
 * returns a date-time object with todays timezone
 * get a MySQL timestamp with $now = $date_now->format("Y-m-d H:i:s");
 */
function unc_datetime($date = NULL) {
    global $WPG_CONFIG;

    //if ($date != NULL) {
    //    $date .= "+08:00"; // incoming timezones are already HKT
    //}
    $date_new = new DateTime($date);
    $date_new->setTimezone(new DateTimeZone($WPG_CONFIG['timezone']));
    return $date_new;
}

function unc_display_fix_timezones($dates) {
    $new_dates = array();
    foreach ($dates as $date => $details) {
        $date_obj = unc_datetime($date);
        // change timezone to UTC
        $date_obj->setTimezone(new DateTimeZone('UTC'));
        $date_str = $date_obj->format("Y-m-d");
        $new_dates[$date_str] = $details;
    }
    return $new_dates;
}

function unc_array_iterate_compact($array) {
    if (!is_array(($array))) {
        return $array;
    }
    $out = "\n<ul>";
    foreach ($array as $element => $content) {
        $out .= "\n<li>$element \n";
        $out .= unc_array_iterate_compact($content, true);
        $out .= "</li>";
    }
    $out .= "</ul>";
    return $out;
}

/**
 * recurse a folder and apply a custom function to the contents
 *
 * @param type $base_folder
 * @param type $function
 * @return type
 */
function unc_gallery_recurse_files($base_folder, $function) {
    $out = '';
    // safety net
    if (strstr($base_folder, './') || strstr($base_folder, '../')) {
        die("Error, recusive path! $base_folder");
    }
    foreach (glob($base_folder.DIRECTORY_SEPARATOR."*") as $file) {
        if (is_dir($file)) {
            $out .= unc_gallery_recurse_files($file, $function);
        } else {
            // working on $file in folder $main
            $out .= $function($file);
        }
    }
    return $out;
}
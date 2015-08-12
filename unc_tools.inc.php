<?php

if (!defined('WPINC')) {
    die;
}

/*
 * returns a date-time object with todays timezone
 * get a MySQL timestamp with $now = $date_now->format("Y-m-d H:i:s");
 */
function unc_gallery_datetime($date = NULL) {
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
    foreach ($dates as $date) {
        $date_obj = unc_datetime($date);
        // change timezone to UTC
        $date_obj->setTimezone(new DateTimeZone('UTC'));
        $date_str = $date_obj->format("Y-m-d");
        $new_dates[] = $date_str;
    }
    return $new_dates;
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
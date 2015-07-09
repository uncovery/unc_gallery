<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}


function unc_gallery_display_page($content, $date = false, $gallery = false) {
    global $WPG_CONFIG;
    $out = "This is a gallery page for date $date and gallery $gallery";
    remove_filter( 'the_content', 'wpautop' );
    $photo_folder = $WPG_CONFIG['gallery_path'] . $WPG_CONFIG['photos'];
    $folder_list = unc_display_folder_list($photo_folder);

    ksort($folder_list);
    $all_dates = array_keys($folder_list);
    // the above dates are local timezone, we need the same date in UTC
    $new_dates = unc_display_fix_timezones($all_dates);
    $date_json = 'var availableDates = ["' . implode("\",\"", $new_dates) . '"];';
    $s_get = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
    $images = '';
    if (isset($s_get['date'])) {
        // validate if this is a proper date
        $date_check = date_create($s_get['date']);
        if (!$date_check) {
            return "ERROR: Date not found";
        }
        $date_obj = unc_datetime($s_get['date'] . " 00:00:00");
        if ($date_obj) {
            $date_str = $date_obj->format("Y/m/d");
            if (file_exists($photo_folder . "/" . $date_str)) {
                $images = unc_display_folder_images($date_str);
            } else {
                return "ERROR: Date not found (folder error) $photo_folder/$date_str";
            }
        } else {
            return "ERROR: Date not found (object error)";
        }
        $latest_date = $s_get['date'];
    } else {
        $latest_date = unc_display_find_latest();
    }


    $out = "\n        <script>
        $date_json
        jQuery(document).ready(function($) {
            jQuery( \"#datepicker\" ).datepicker({
                dateFormat: \"yy-mm-dd\",
                defaultDate: \"$latest_date\",
                beforeShowDay: available,
                onSelect: openlink,
            });
        });
        </script>
        <div class=\"photopage\">
            <div id=\"datepicker\"></div>
            $images
        </div>";

    // remove the page tag from the original content and insert the new content
    $pattern = '/(\[unc_gallery.*\])/';
    $new_content = preg_replace($pattern, $out, $content);
    return $new_content;
}


/*
 * returns a date-time object with todays timezone
 * get a MySQL timestamp with $now = $date_now->format("Y-m-d H:i:s");
 */
function unc_gallery_datetime($date = NULL) {
    global $WPG_CONFIG;

    if ($date != NULL) {
        $date .= "+08:00"; // incoming timezones are already HKT
    }
    $date_new = new DateTime($date);
    $date_new->setTimezone(new DateTimeZone($WPG_CONFIG['timezone']));
    return $date_new;
}

/**
 * recurse a folder and return filecount
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

function unc_display_folder_list($base_folder) {
    global $WPG_CONFIG;
    $global_base = $WPG_CONFIG['gallery_path'] . $WPG_CONFIG['photos'];
    $base_length = strlen($global_base) + 1;

    $dates = array();
    foreach (glob($base_folder.DIRECTORY_SEPARATOR."*") as $current_path) {
        $file = basename($current_path);
        // get current date from subfolder
        if (is_dir($current_path)) { // we have a directory
            $cur_date = str_replace(DIRECTORY_SEPARATOR, "-", substr($current_path, $base_length));
            if (strlen($cur_date) == 10) { // we have a full date, add to array
                $dates[$cur_date] = 0;
            }
            // go one deeper
            $new_dates = unc_display_folder_list($current_path);
            if (count($new_dates) > 0) {
                $dates = array_merge($dates, $new_dates);
            }
        } else { // we have a file
            $cur_date = str_replace(DIRECTORY_SEPARATOR, "-", substr($base_folder, $base_length));
            $dates[$cur_date][] = $file;
        }
    }
    return $dates;
}

function unc_display_folder_images($date_str) {
    global $WPG_CONFIG;
    // $photo_folder = $WPG_CONFIG['gallery_path'] . $WPG_CONFIG['photos'];
    $thumb_folder = $WPG_CONFIG['gallery_path'] . $WPG_CONFIG['thumbnails'];

    // $curr_photo_folder = $photo_folder . "/" . $date_str;
    $curr_thumb_folder = $thumb_folder . "/" . $date_str;

    $date_iso = str_replace("/", "-", $date_str);
    foreach (glob($curr_thumb_folder.DIRECTORY_SEPARATOR."*") as $file) {
        $filename = basename($file);
        if ($file != '.' && $file != '..') {
            $photo_url = plugins_url("galleries/default/photos/$date_str/$filename", __FILE__ );
            $thumb_url = plugins_url("galleries/default/thumbs/$date_str/$filename", __FILE__ );
            $out .= "    <div class=\"photobox\">\n"
                . "        <a href=\"$photo_url\" data-lightbox=\"$date_iso\">\n"
                . "            <img alt=\"$filename\" src=\"$thumb_url\">\n"
                . "        </a>\n"
                . "    </div>\n";
        }
    }
    return $out;
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

function unc_display_find_latest() {
    global $WPG_CONFIG;
    $date_obj = unc_datetime();
    $date_str = $date_obj->format("Y/m/d");

    $base_folder = $WPG_CONFIG['gallery_path'] . $WPG_CONFIG['photos'];

    // this could be improved by going back first years, then months, then days
    while (!file_exists($base_folder . "/". $date_str)) {
        $date_obj->modify("-1 day");
        $date_str = $date_obj->format("Y/m/d");
    }
    $return_str = $date_obj->format("Y-m-d");
    return $return_str;
}
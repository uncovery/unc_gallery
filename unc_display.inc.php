<?php

if (!defined('WPINC')) {
    die;
}

/**
 * main function. Checks for the keyword in the content and switches that define
 * the content further. Then calls the function that creates the actual content
 * and returns the modified content
 *
 * @param type $content
 * @return type
 */
function unc_gallery($content) {
    $pattern = '/\[(?\'activator\'unc_gallery)( date="(?\'date\'[0-9-]{10})")?( gallery_name="(?\'gallery\'[a-z_-]*)")?\]/';
    $matches = false;
    preg_match($pattern, $content, $matches);

    if (!isset($matches['activator'])) {
        return $content;
    }

    $date = false;
    if (isset($matches['date'])) {
        $date = $matches['date'];
    }
    if (isset($matches['gallery'])) {
        $gallery = $matches['gallery'];
    }

    $content_new = unc_gallery_display_page($content, $date, $gallery);

    return $content_new;
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
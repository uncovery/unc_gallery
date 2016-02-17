<?php

if (!defined('WPINC')) {
    die;
}

function unc_images_display() {
    echo "<h2>Uncovery Gallery: All Images</h2>\n";
    $content_new = unc_gallery_display_page('[unc_gallery]', false, false, "?page=unc_gallery_admin_view&");
    echo $content_new;
}

/**
 * Displays content through keyword replacement
 * Checks for the keyword in the content and switches that define
 * the content further. Then calls the function that creates the actual content
 * and returns the modified content
 *
 * @param string $content
 * @return string
 */
function unc_gallery($content) {
    // this is the REGEX to check only for the activator, other stuff is done later
    $pattern_activator = "/\[(?'activator'unc_gallery) (?'modifiers'.*)\]/";
    $base_matches = false;
    preg_match($pattern_activator, $content, $base_matches);
    
    // if we cannot even find unc_gallery just return back the content
    if (!isset($base_matches['activator'])) {
        return $content;
    }
    
    // get all the individual key="value1 value2" patterns:
    $modifier_matches = false;
    $groups_pattern = '/( [a-z="]*)/gi';
    preg_match($groups_pattern, $base_matches['modifiers'], $modifier_matches);
    
    // now we split them into key=array(value1, value2)
    $sub_group_pattern = "/(?'key'[\w]*)=\"(?'values'[ \w]*)\"/";
    $settings = array();
    foreach ($modifier_matches as $match) {
        $final_matches = false;
        preg_match($sub_group_pattern, $match, $final_matches);
        $temp_key = $final_matches['key'];
        $temp_values = explode(" ", $final_matches['values']);
        $settings[$temp_key] = $temp_values;
    }

    // options for displays
    $keywords = array(
        'type' => array(
            'gallery' => array('datepicker'), // shows a single date's gallery, optional date picker
            'image' => array('link'), // only one image, requires file addon unless random or latest
            'icon' => array('link'), // only the icon of one image, requires file addon unless random or latest
        ), 
        'date' => array('random', 'latest'),  // whichdate to chose
        'file', // in case of image or icon type, you can chose one filename 
    );    
    
    // type, we defauly to 'day' if not given or invalid token
    if (!isset($settings['type']) || isset($settings['type'], $keywords['type'])) {
        $type = 'day';
    } else {
        $type = $settings['type'];
    }

    // icon or image?
    $thumb = false;
    if ($type == 'icon') {
        $thumb = true;
    }
    
    // date, we default to latest
    if (!isset($settings['date'])) {
        $date = 'latest';
    } else if (in_array($settings['date'], $keywords['date'])) {
        $date = $keywords['date']; // either latest or random
    } else { //if none of the defaults, we assume date
        $datetime = new DateTime($settings['date']);
        if (!$datetime) { // invalid date, fallback to latest
            $date = 'latest'; // TODO: this should throw an error
        } else { // otherwise accept it, format it again to be sure
            $date = $datetime->format("Y-m-d");
        }
    }
    // get the latest or a random date if required
    if ($date == 'latest') {
        $date = unc_tools_date_latest();
    } elseif ($date == 'random') {
        $date = unc_tools_date_random();
    }

    // options
    $possible_type_options = $keywords['type'][$type];
    if (!isset($settings['options']) || in_array($settings['options'], $possible_type_options)) {
        $error = "You have an invalid option for the display type $type in your tag";
        return $error;
    }
    
    $link = false;
    if (in_array('link', $settings['options'])) {
        $link = true;
    }
    
    $datepicker = false;
    if (in_array('datepicker', $settings['options'])) {
        $datepicker = true;
    }
    
    // date
    if (!isset($settings['date'])) {
        return false;
    }
    if ($settings['date'] == 'random') {
        $date = unc_tools_date_random();
    } else if ($settings['date'] == 'random') {
        $date = unc_tools_date_latest();
    } else {
        $date = unc_tools_date_validate($date);
    }
    
    if ($type == 'day') {
        $content_new = unc_gallery_display_page($content, $date, $datepicker);
    } else {
        $content_new = unc_gallery_display_image($content, $date, $thumb, $link, $settings['file']);
    }
    
    // now we got the variables, let's get the actual content
    $content_new = unc_gallery_display_page($content, $date);
    return $content_new;
}

function unc_gallery_display_page($content, $date = false) {
    global $WPG_CONFIG;

    // do not let wp manipulate linebreaks
    remove_filter('the_content', 'wpautop');

    $photo_folder =  WP_CONTENT_DIR . $WPG_CONFIG['upload'] . $WPG_CONFIG['photos'];

    $folder_list = unc_display_folder_list($photo_folder);

    krsort($folder_list);
    // the above dates are local timezone, we need the same date in UTC
    $all_dates = unc_display_fix_timezones($folder_list);

    $new_dates = array_keys($all_dates);

    $date_json = 'var availableDates = ["' . implode("\",\"", $new_dates) . '"];';

    $s_get = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
    $images = '';
    $out = "Showing ";
    // show the selected date
    if ($date) {
        $date_check = date_create($date);
        if (!$date_check) {
            return "ERROR: Date not found";
        }
        $requested_date = $date_check;
    } else if (isset($s_get['unc_date'])) {
        // validate if this is a proper date
        $date_check = date_create($s_get['unc_date']);
        if (!$date_check) {
            return "ERROR: Date not found";
        }
        $requested_date = $s_get['unc_date'];
        $out .= "date $requested_date ";
    } else {
        // show the latest date
        $requested_date = unc_tools_date_latest();
        $out .= "most recent date ";
    }

    $date_obj = unc_datetime($requested_date . " 00:00:00");
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

    $out .= "$requested_date"; //and gallery $gallery
    // get a json datepicker
    $out .= "\n        <script>
        $date_json
        jQuery(document).ready(function($) {
            jQuery( \"#datepicker\" ).datepicker({
                dateFormat: \"yy-mm-dd\",
                defaultDate: \"$requested_date\",
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
    $photo_folder =  WP_CONTENT_DIR . $WPG_CONFIG['upload'] . $WPG_CONFIG['photos'];
    $base_length = strlen($photo_folder) + 1;

    $dates = array();
    foreach (glob($base_folder . DIRECTORY_SEPARATOR . "*") as $current_path) {
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

/**
 * Open a folder of a certain date and display all the images in there
 * 
 * @global type $WPG_CONFIG
 * @param type $date_str
 * @return string
 */
function unc_display_folder_images($date_str) {
    global $WPG_CONFIG;
    // $photo_folder = $WPG_CONFIG['gallery_path'] . $WPG_CONFIG['photos'];
    $thumb_folder =  WP_CONTENT_DIR . $WPG_CONFIG['upload'] .  $WPG_CONFIG['thumbnails'];

    // $curr_photo_folder = $photo_folder . "/" . $date_str;
    $curr_thumb_folder = $thumb_folder . DIRECTORY_SEPARATOR . $date_str;

    foreach (glob($curr_thumb_folder.DIRECTORY_SEPARATOR."*") as $file) {
        $filename = basename($file);
        if ($file != '.' && $file != '..') {
            $photo_url = content_url($WPG_CONFIG['upload'] . $WPG_CONFIG['photos'] . "/$date_str/$filename");
            $thumb_url = content_url($WPG_CONFIG['upload'] . $WPG_CONFIG['thumbnails'] . "/$date_str/$filename");
            $out .= "    <div class=\"photobox\">\n"
                . "        <a href=\"$photo_url\" class=\"thickbox\" rel=\"gallery\">\n"
                . "            <img alt=\"$filename\" src=\"$thumb_url\">\n"
                . "        </a>\n"
                . "    </div>\n";
        }
    }
    return $out;
}
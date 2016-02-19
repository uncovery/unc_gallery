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
 * @param string $atts
 * @return string
 */
function unc_gallery_apply($atts) {
    unc_gallery_add_css_and_js();
    $a = shortcode_atts( array(
        'type' => 'day',
        'date' => 'latest',
        'file' => 'latest',
        'options' => array(),
    ), $atts );

    $type = $a['type'];
    $date = $a['date'];
    $file = $a['file'];
    // there can be several options, separated by space
    $options = explode(" ", $a['options']);

    // options for displays
    $keywords = array(
        'type' => array(
            'day' => array('datepicker'), // shows a single date's gallery, optional date picker
            'image' => array('link'), // only one image, requires file addon unless random or latest
            'icon' => array('link'), // only the icon of one image, requires file addon unless random or latest
        ),
        'date' => array('random', 'latest'),  // whichdate to chose
        'file', // in case of image or icon type, you can chose one filename
    );

    // icon or image?
    $thumb = false;
    if ($type == 'icon') {
        $thumb = true;
    }

    if (!in_array($date, $keywords['date'])) {
        // lets REGEX
        $pattern = '/[\d]{4}-[\d]{2}-[\d\d]{2}/';
        if (preg_match($pattern, $date) == 0) {
            return unc_tools_errormsg("Your date needs to be in the format '2016-01-31'");
        }
        $datetime = new DateTime($date);
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
    } else {
        $date = unc_tools_date_validate($date);
    }

    if (!$date) {
        // there are no pictures
        return unc_tools_errormsg("No pictures found!");
    }

    // options
    $possible_type_options = $keywords['type'][$type];
    foreach ($options as $option) {
        if (!in_array($option, $possible_type_options)) {
            $error = unc_tools_errormsg("You have an invalid option for the display type \"$type\" in your tag."
                . "<br>Valid options are: " . implode(",", $keywords['type'][$type]));
            return $error;
        }
    }

    $link = false;
    if (in_array('link', $options)) {
        $link = true;
    }

    $datepicker = false;
    if (in_array('datepicker', $options)) {
        $datepicker = true;
    }

    if ($type == 'day') {
        $content_new = unc_gallery_display_page($date, $datepicker);
    } else {
        $content_new = unc_gallery_display_image($date, $datepicker, $thumb, $link, $file);
    }
    return $content_new;
}

function unc_gallery_display_page($date, $datepicker = false, $thumb = false, $link = false, $file = false) {
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

    $date_obj = unc_datetime($date . " 00:00:00");
    if ($date_obj) {
        $format = implode(DIRECTORY_SEPARATOR, array('Y', 'm', 'd'));
        $date_str = $date_obj->format($format);
        if (file_exists($photo_folder . DIRECTORY_SEPARATOR . $date_str)) {
            $images = unc_display_folder_images($date_str);
        } else {
            return unc_tools_errormsg("Date not found (folder error) $photo_folder/$date_str");
        }
    } else {
        return unc_tools_errormsg("Date not found (object error)");
    }

    // get a json datepicker
    $datepicker_div = "<span id=\"photodate\">Showing $date</span>";
    if ($datepicker) {
        $out .= "\n     <script type=\"text/javascript\">
        $date_json
        var ajaxurl = '" . admin_url('admin-ajax.php') . "';
        jQuery(document).ready(function($) {
            datepicker_ready('$date');
        });
        </script>";
        $datepicker_div = "Date: <input type=\"text\" id=\"datepicker\" value=\"$date\">";
    }
    $out .= "
        <div class=\"photopage\">
            $datepicker_div
            <div id=\"photobox\">
                $images
            </div>
        </div>
        <span style=\"clear:both;\"></span>";

    // remove the page tag from the original content and insert the new content
    return $out;
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
function unc_display_folder_images($date_str = false) {
    global $WPG_CONFIG;
    $echo = false;
    if (!$date_str) {
        $echo = true;
        $date_wrong = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
        $date_str = str_replace("-", DIRECTORY_SEPARATOR, $date_wrong);
    }

    // $photo_folder = $WPG_CONFIG['gallery_path'] . $WPG_CONFIG['photos'];
    $thumb_folder =  WP_CONTENT_DIR . $WPG_CONFIG['upload'] .  $WPG_CONFIG['thumbnails'];

    // $curr_photo_folder = $photo_folder . "/" . $date_str;
    $curr_thumb_folder = $thumb_folder . DIRECTORY_SEPARATOR . $date_str;
    $out = '';
    foreach (glob($curr_thumb_folder.DIRECTORY_SEPARATOR."*") as $file) {
        $filename = basename($file);
        if ($file != '.' && $file != '..') {
            $photo_url = content_url($WPG_CONFIG['upload'] . $WPG_CONFIG['photos'] . "/$date_str/$filename");
            $thumb_url = content_url($WPG_CONFIG['upload'] . $WPG_CONFIG['thumbnails'] . "/$date_str/$filename");
            $out .= "        <a href=\"$photo_url\" class=\"thickbox\" rel=\"gallery\">\n"
                . "            <img alt=\"$filename\" src=\"$thumb_url\">\n"
                . "        </a>\n";
        }
    }
    if ($echo) {
        ob_clean();
        echo $out;
        wp_die();
    } else {
        return $out;
    }
}
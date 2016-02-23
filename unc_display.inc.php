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
function unc_gallery_apply($atts = array()) {
    unc_gallery_add_css_and_js();
    $a = shortcode_atts( array(
        'type' => 'day',
        'date' => 'latest',
        'file' => 'latest',
        'featured' => false,
        'options' => array(),
    ), $atts );

    $type = $a['type'];
    $date = $a['date'];
    $file = sanitize_file_name($a['file']); // wp function to sanitze filnames
    $featured = $a['featured'];
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
    $date_desc = true;
    if ($date == 'latest') {
        $date = unc_tools_date_latest();
    } elseif ($date == 'random') {
        $date = unc_tools_date_random();
    } else {
        $date_desc = false;
        $date = unc_tools_date_validate($date);
    }

    if (!$date) {
        // there are no pictures
        return unc_tools_errormsg("No pictures found, please upload some images first!");
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

    $out = '';
    if ($type == 'day') {
        if (is_admin()) {
            $date_split = explode("-", $date);
            $date_path = implode(DIRECTORY_SEPARATOR, $date_split);
            $out .= " <a class=\"delete_folder_link\" href=\"?page=unc_gallery_admin_view&amp;folder_del=$date_path\">Delete Date: $date</a>";
        }
        $out .= unc_gallery_display_page($date, $datepicker, $date_desc, $featured);
    } else {
        $out .= unc_gallery_display_image($date, $datepicker, $date_desc, $featured);
    }
    return $out;
}

function unc_gallery_display_page($date, $datepicker, $date_desc, $featured_image) {
    global $UNC_GALLERY;

    // do not let wp manipulate linebreaks
    remove_filter('the_content', 'wpautop');

    $photo_folder =  WP_CONTENT_DIR . $UNC_GALLERY['upload'] . $UNC_GALLERY['photos'];

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
    $datepicker_div = '';
    if ($date_desc) {
        $datepicker_div = "<span id=\"photodate\">Showing $date</span>";
    }
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
    $single_photo = '';
    if ($featured_image) {
        $single_photo = unc_display_single_image($date, $featured_image);
    }
    $out .= "
        <div class=\"photopage\">
            $datepicker_div
            <div id=\"photobox\">
                $single_photo
                $images
            </div>
        </div>
        <span style=\"clear:both;\"></span>";

    // remove the page tag from the original content and insert the new content
    return $out;
}

/**
 * return a single file from a date
 * 
 * @global type $UNC_GALLERY
 * @param type $date_str
 * @param type $file_name
 * @return boolean
 */
function unc_display_single_image($date_str, $file_name) {
    global $UNC_GALLERY;
    $photo_folder =  WP_CONTENT_DIR . $UNC_GALLERY['upload'] . $UNC_GALLERY['photos'];
    $curr_photo_file = $photo_folder . DIRECTORY_SEPARATOR . $date_str. DIRECTORY_SEPARATOR . $file_name;
    if (file_exists($curr_photo_file)) {
        $out = "<img class=\"featured_image\" alt=\"$curr_photo_file\" src=\"$curr_photo_file\">\n";
        return $out;
    }
    return false;
}

/**
 * 
 * 
 * @global type $UNC_GALLERY
 * @param type $base_folder
 * @return type
 */
function unc_display_folder_list($base_folder) {
    global $UNC_GALLERY;
    $photo_folder =  WP_CONTENT_DIR . $UNC_GALLERY['upload'] . $UNC_GALLERY['photos'];
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
 * @global type $UNC_GALLERY
 * @param type $date_str
 * @return string
 */
function unc_display_folder_images($date_str = false) {
    global $UNC_GALLERY;
    $echo = false;
    if (!$date_str) {
        $echo = true;
        $date_wrong = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
        $date_str = str_replace("-", DIRECTORY_SEPARATOR, $date_wrong);
    }

    // $photo_folder = $UNC_GALLERY['gallery_path'] . $UNC_GALLERY['photos'];
    $thumb_folder =  WP_CONTENT_DIR . $UNC_GALLERY['upload'] .  $UNC_GALLERY['thumbnails'];

    // $curr_photo_folder = $photo_folder . "/" . $date_str;
    $curr_thumb_folder = $thumb_folder . DIRECTORY_SEPARATOR . $date_str;
    $out = '';
    foreach (glob($curr_thumb_folder.DIRECTORY_SEPARATOR."*") as $file) {
        $filename = basename($file);
        if ($file != '.' && $file != '..') {
            $photo_url = content_url($UNC_GALLERY['upload'] . $UNC_GALLERY['photos'] . "/$date_str/$filename");
            $thumb_url = content_url($UNC_GALLERY['upload'] . $UNC_GALLERY['thumbnails'] . "/$date_str/$filename");
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
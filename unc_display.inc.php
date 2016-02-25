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
        'options' => false, // we cannot set it to an array here
        'start_time' => false,
        'end_time' => false,
    ), $atts );

    $type = $a['type'];
    $date = $a['date'];
    $featured_image = $a['featured']; // wp function to sanitze filnames
    $file = sanitize_file_name($a['file']);
    // there can be several options, separated by space
    if (!$a['options']) {
        $options = array();
    } else {
        $options = explode(" ", $a['options']);
    }

    // range
    $range = array('start_time' => false, 'end_time' => false);
    foreach ($range as $key => $value) {
        if ($a[$key]) {
            $range[$key] = $a[$key];
        }
    }


    // options for displays
    $keywords = array(
        'type' => array(
            'day' => array('datepicker', 'datelist'), // shows a single date's gallery, optional date picker
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
            $error = unc_tools_errormsg("You have an invalid option for the display type \"option\" in your tag."
                . "<br>Valid options are: " . implode(",", $keywords['type'][$type]));
            return $error;
        }
    }

    $link = false;
    if (in_array('link', $options)) {
        $link = true;
    }

    $date_selector = false;
    if (in_array('datepicker', $options)) {
        $date_selector = 'datepicker';
    } else if (in_array('datelist', $options)) {
        $date_selector = 'datelist';
    }

    $out = '';
    if ($type == 'day' && is_admin()) {
        $date_split = explode("-", $date);
        $date_path = implode(DIRECTORY_SEPARATOR, $date_split);
        $out .= " <a class=\"delete_folder_link\" href=\"?page=unc_gallery_admin_view&amp;folder_del=$date_path\">Delete Date: $date</a>";
    }
    $out .= unc_gallery_display_page($date, $date_selector, $date_desc, $featured_image, $range);
    return $out;
}

function unc_gallery_display_page($date, $date_selector, $date_desc, $featured_image, $range) {
    global $UNC_GALLERY;

    // do not let wp manipulate linebreaks
    remove_filter('the_content', 'wpautop');

    $photo_folder =  WP_CONTENT_DIR . $UNC_GALLERY['upload'] . $UNC_GALLERY['photos'];

    $date_obj = unc_datetime($date . " 00:00:00");
    if ($date_obj) {
        $format = implode(DIRECTORY_SEPARATOR, array('Y', 'm', 'd'));
        $date_str = $date_obj->format($format);
        if (file_exists($photo_folder . DIRECTORY_SEPARATOR . $date_str)) {
            $images = unc_display_folder_images($date_str, $featured_image, $range);
        } else {
            return unc_tools_errormsg("Date not found (folder error) $photo_folder/$date_str");
        }
    } else {
        return unc_tools_errormsg("Date not found (object error)");
    }

    // get a json datepicker
    $datepicker_div = '';
    $out = '';
    if ($date_desc) {
        $datepicker_div = "<span id=\"photodate\">Showing $date</span>";
    }
    if ($date_selector == 'datepicker') {
        $avail_dates = unc_tools_folder_list($photo_folder);

        $out .= "\n     <script type=\"text/javascript\">
        var availableDates = [\"" . implode("\",\"", array_keys($avail_dates)) . "\"];
        var ajaxurl = \"" . admin_url('admin-ajax.php') . "\";
        jQuery(document).ready(function($) {
            datepicker_ready('$date');
        });
        </script>";
        $datepicker_div = "Date: <input type=\"text\" id=\"datepicker\" value=\"$date\">";
    } else if ($date_selector == 'dateselector') {
        $folder_list = unc_tools_folder_list($photo_folder);
        $out .= "<select name=\"date_select\">";
        foreach ($folder_list as $date => $files) {
            $counter = count($files);
            $out .= "<option value=\"\">$date ($counter)</option";
        }
        $out .="</select>";
    }
    $single_photo = '';
    if ($featured_image) {
        $file_date = unc_tools_image_exif_date($date_str, $featured_image);
        $single_photo = unc_display_single_image($date_str, $featured_image, false, $file_date);
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
 * Open a folder of a certain date and display all the images in there
 *
 * @global type $UNC_GALLERY
 * @param type $date_str
 * @param type $skip_file
 * @return string
 */
function unc_display_folder_images($date_str, $skip_file, $range) {
    global $UNC_GALLERY;
    $echo = false;
    if (!$date_str) {
        $echo = true;
        $date_wrong = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
        $date_str = str_replace("-", DIRECTORY_SEPARATOR, $date_wrong);
    }

    $photo_folder =  WP_CONTENT_DIR . $UNC_GALLERY['upload'] .  $UNC_GALLERY['photos'] ;
    $curr_photo_folder = $photo_folder . DIRECTORY_SEPARATOR . $date_str;

    $out = '';

    $files = array();

    $skip_files = array($skip_file, '.', '..');

    foreach (glob($curr_photo_folder . DIRECTORY_SEPARATOR . "*") as $file_path) {
        $file_name = basename($file_path);
        if (in_array($file_name, $skip_files)) {
            continue;
        }
        if ($file_name != '.' && $file_name != '..') {
            $file_date = unc_tools_image_exif_date($date_str, $file_name);

            // range
            if (($range['start_time'] && "$date_str {$range['start_time']}" < $file_date) ||
                ($range['end_time'] && "$date_str {$range['end_time']}" > $file_date)) {
                continue;
            }
            $files[$file_date] = $file_name;
        }
    }

    // sort the files by date / time
    ksort($files);

    foreach ($files as $file_date => $file_name) {
        $out .= unc_display_single_image($date_str, $file_name, true, $file_date);
    }

    if ($echo) {
        ob_clean();
        echo $out;
        wp_die();
    } else {
        return $out;
    }
}

/**
 * return a single file from a date & filename
 * assumes the file exists
 *
 * @global type $UNC_GALLERY
 * @param type $date_str
 * @param type $file_name
 * @param bool $show_thumb
 * @param string $file_date
 * @return boolean
 */
function unc_display_single_image($date_str, $file_name, $show_thumb, $file_date) {
    global $UNC_GALLERY;

    $photo_url = content_url($UNC_GALLERY['upload'] . $UNC_GALLERY['photos'] . "/$date_str/$file_name");
    $thumb_url = content_url($UNC_GALLERY['upload'] . $UNC_GALLERY['thumbnails'] . "/$date_str/$file_name");

    if ($show_thumb) {
        $shown_image = $thumb_url;
        $class = 'thickbox';
    } else {
        $shown_image = $photo_url;
        $class = 'featured_image thickbox';
    }

    $rel_date = str_replace(DIRECTORY_SEPARATOR, "_", $date_str);
    $description = "$file_name, taken $file_date";
    $out = "        <a href=\"$photo_url\" title=\"$description\" class=\"$class\" rel=\"gallery_$rel_date\">\n"
        . "            <img alt=\"$file_name\" src=\"$shown_image\">\n"
        . "         </a>\n";
    return $out;
}

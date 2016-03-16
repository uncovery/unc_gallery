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
 * displays folder images while getting values from AJAX
 *
 * @return type
 */
function unc_display_ajax_folder() {
    // we get the date from the GET value
    $date_str = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
    unc_gallery_display_var_init(array('date' => $date_str, 'echo' => true));
    return unc_display_folder_images();
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
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

    unc_gallery_add_css_and_js();
    $check = unc_gallery_display_var_init($atts);
    if ($check) {
        return unc_gallery_display_page();
    }
}

/**
 * Process and validate $UNC_GALLERY['display'] settings
 *
 * @global type $UNC_GALLERY
 * @param type $atts
 * @return type
 */
function unc_gallery_display_var_init($atts = array()) {
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

    $a = shortcode_atts( array(
        'type' => 'day',    // display type
        'date' => 'latest', // which date?
        'file' => false,    // specifix file?
        'featured' => false,  // is there a featured file?
        'options' => false, // we cannot set it to an array here
        'start_time' => false, // time of the day when we start displaying this date
        'end_time' => false, // time of the day when we stop displaying this date
        'description' => false, // description for the whole day
        'details' => false, // description for individual files
        'echo' => false, // internal variable, used by AJAX call
    ), $atts);

    $type = $a['type'];
    $date = $a['date'];
    $UNC_GALLERY['display']['featured_image'] = $a['featured']; // TODO: Sanitize & verify filename

    // there can be several options, separated by space
    if (!$a['options']) {
        $options = array();
    } else {
        $options = explode(" ", $a['options']);
    }
    $UNC_GALLERY['options'] = $options;
    $UNC_GALLERY['display']['echo'] = $a['echo'];

    // icon or image?
    $thumb = false;
    if ($type == 'icon') {
        $thumb = true;
    }

    $UNC_GALLERY['display']['description'] = $a['description'];

    $keywords = $UNC_GALLERY['keywords'];
    if (!in_array($date, $keywords['date'])) {
        // lets REGEX
        $pattern = '/[\d]{4}-[\d]{2}-[\d\d]{2}/';
        if (preg_match($pattern, $date) == 0) {
            echo unc_tools_errormsg("Your date needs to be in the format '2016-01-31'");
            return false;
        }
        $datetime = new DateTime($date);
        if (!$datetime) { // invalid date, fallback to latest
            $date = 'latest'; // TODO: this should throw an error
        } else { // otherwise accept it, format it again to be sure
            $date = $datetime->format("Y-m-d");
        }
    }

    // get the latest or a random date if required
    $UNC_GALLERY['display']['date_description'] = true;
    if ($date == 'latest') {
        $date = unc_tools_date_latest();
    } elseif ($date == 'random') {
        $date = unc_tools_date_random();
    } else {
        $UNC_GALLERY['display']['date_description'] = false;
        $date = unc_tools_date_validate($date);
    }

    if (!$date) {
        // there are no pictures
        echo unc_tools_errormsg("No pictures found, please upload some images first!");
        return false;
    }
    $UNC_GALLERY['display']['date'] = $date;

    // range
    $UNC_GALLERY['display']['range'] = array('start_time' => false, 'end_time' => false);
    foreach ($UNC_GALLERY['display']['range'] as $key => $value) {
        if ($a[$key]) {
            $dtime = DateTime::createFromFormat("Y-m-d G:i:s", $a[$key]);
            $UNC_GALLERY['display']['range'][$key] = $dtime->getTimestamp();
        }
    }

    $details_raw = $a['details'];
    $UNC_GALLERY['display']['details'] = false;
    if ($details_raw) {
        // explode by colon
        $file_details = explode(";", $details_raw);
        if (count($file_details) == 0) {
            echo unc_tools_errormsg("File details are invalid!");
            return;
        }
        foreach ($file_details as $file_detail) {
            $tmp_arr = explode(":", $file_detail);
            if (count($tmp_arr) !== 2) {
                echo unc_tools_errormsg("File details are invalid!");
                return;
            }
            $details_filename = trim($tmp_arr[0]);
            $details_description = trim($tmp_arr[1]);
            $UNC_GALLERY['display']['details'][$details_filename] = $details_description;
        }
    }

    // options
    if (!isset($keywords['type'][$type])) {
        echo unc_tools_errormsg("You have an invalid type value in your tag."
            . "<br>Valid options are: " . implode(", ", $keywords['type']));
        return false;
    }
    $possible_type_options = $keywords['type'][$type];
    foreach ($UNC_GALLERY['options'] as $option) {
        if (!in_array($option, $possible_type_options)) {
            echo unc_tools_errormsg("You have an invalid option for the display type \"option\" in your tag."
                . "<br>Valid options are: " . implode(", ", $keywords['type'][$type]));
            return false;
        }
    }
    $UNC_GALLERY['display']['type'] = $a['type'];

    $UNC_GALLERY['display']['link'] = false;
    if (in_array('link', $UNC_GALLERY['options'])) {
        $UNC_GALLERY['display']['link'] = true;
    }

    $UNC_GALLERY['display']['date_selector'] = false;
    if (in_array('datepicker', $options)) {
        $UNC_GALLERY['display']['date_selector'] = 'datepicker';
    } else if (in_array('datelist', $options)) {
        $UNC_GALLERY['display']['date_selector'] = 'datelist';
    }

    $UNC_GALLERY['display']['file'] = unc_tools_filename_validate($a['file']);
    return true;
}

function unc_gallery_display_page() {
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

    $D = $UNC_GALLERY['display'];

    // do not let wp manipulate linebreaks
    remove_filter('the_content', 'wpautop');

    $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];

    // get a json datepicker
    $datepicker_div = '';
    $out = '';
    if ($D['date_description']) {
        $datepicker_div = "<span id=\"photodate\">Showing {$D['date']}</span>";
    }
    if ($D['date_selector'] == 'datepicker') {
        $avail_dates = unc_tools_folder_list($photo_folder);

        $out .= "\n     <script type=\"text/javascript\">
        var availableDates = [\"" . implode("\",\"", array_keys($avail_dates)) . "\"];
        var ajaxurl = \"" . admin_url('admin-ajax.php') . "\";
        jQuery(document).ready(function($) {
            datepicker_ready('{$D['date']}');
        });
        </script>";
        $datepicker_div = "Date: <input type=\"text\" id=\"datepicker\" value=\"{$D['date']}\">";
    } else if ($D['date_selector'] == 'datelist') {
        $folder_list = unc_tools_folder_list($photo_folder);
        $datepicker_div = "<select id=\"datepicker\" onchange=\"datelist_change()\">\n";
        foreach ($folder_list as $folder_date => $folder_files) {
            $counter = count($folder_files);
            $datepicker_div .= "<option value=\"$folder_date\">$folder_date ($counter)</option>\n";
        }
        $datepicker_div .="</select>\n";
    }

    $date_path = unc_tools_date_path($D['date']);
    if (!$date_path) {
        return;
    }

    if ($D['type'] == 'image' || $D['type'] == 'thumb') {
        $thumb = false;
        if ($D['type'] == 'thumb') {
            $thumb = true;
        }
        if ($D['file'] == 'random') {
            $file = unc_tools_file_random($date_path);
        } else if ($D['file'] == 'latest') {
            $file = unc_tools_file_latest($date_path);
        } else {
            $file = $D['file'];
        }
        $out = unc_display_single_image($date_path, $file, $thumb);
    } else {
        $images = unc_display_folder_images();

        $single_photo = '';
        if ($D['featured_image']) {
            $single_photo = "<div class=\"featured_photo\">\n"
                . unc_display_single_image($date_path, $D['featured_image'], false)
                . "</div>\n";
        }
        $delete_link = '';
        $out .= "
            <div class=\"unc_gallery\">
                $datepicker_div
                $delete_link
                <div id=\"photos\">
        $single_photo
        $images
                </div>
            </div>
            <span style=\"clear:both;\"></span>";
    }
    // remove the page tag from the original content and insert the new content
    return $out;
}

/**
 * Open a folder of a certain date and display all the images in there
 *
 * @global type $UNC_GALLERY
 * @return string
 */
function unc_display_folder_images() {
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

    $D = $UNC_GALLERY['display'];

    $date_str = $D['date'];
    $date_path = str_replace("-", DIRECTORY_SEPARATOR, $date_str);

    $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];
    $curr_photo_folder = $photo_folder . DIRECTORY_SEPARATOR . $date_path;

    $out = '';
    if (is_admin()) {
        $out .= "
        <span class=\"delete_folder_link\">
            Sample shortcode for this day: <input type=\"text\" value=\"[unc_gallery date=&quot;$date_str&quot;]\">
            <a href=\"?page=unc_gallery_admin_view&amp;folder_del=$date_str\">
                Delete Date: $date_str
            </a>
        </span>\n";
    }

    $files = array();

    $dirs = array('.', '..');
    if ($D['featured_image']) {
        $skip_files = array_merge(array($D['featured_image']), $dirs);
    } else {
        $skip_files = $dirs;
    }

    foreach (glob($curr_photo_folder . DIRECTORY_SEPARATOR . "*") as $file_path) {
        $file_name = basename($file_path);
        if (in_array($file_name, $skip_files)) {
            continue;
        }
        if ($file_name != '.' && $file_name != '..') {
            $file_path = unc_tools_image_path($date_path, $file_name);
            $file_date = unc_tools_image_date($file_path);
            $dtime = DateTime::createFromFormat("Y-m-d G:i:s", $file_date);
            $file_stamp = $dtime->getTimestamp();
            // range
            if (($D['range']['end_time'] && $D['range']['start_time']) && // only if both are set
                    ($D['range']['end_time'] < $D['range']['start_time'])) { // AND the end is before the start
                if (($D['range']['end_time'] < $file_stamp)
                        && ($file_stamp < $D['range']['start_time'])) {  // then skip over the files inbetween end and start
                    continue;
                }
            } else if (($D['range']['start_time'] && ($file_stamp < $D['range']['start_time'])) || // if there is a start and the file is earlier
                ($D['range']['end_time'] && ($D['range']['end_time'] < $file_stamp))) { // or if there is an end and the file is later then skip
                continue;
            }
            $files[$file_date] = $file_name;
        }
    }

    // sort the files by date / time
    ksort($files);

    foreach ($files as $file_date => $file_name) {
        $out .= "<div class=\"one_photo\">\n"
            . unc_display_single_image($date_path, $file_name, true, $file_date)
            . "</div>\n";
    }

    if ($D['echo']) {
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
 * @return boolean
 */
function unc_display_single_image($date_path, $file_name, $show_thumb, $file_date = false) {
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

    $D = $UNC_GALLERY['display'];

    $photo_url = content_url($UNC_GALLERY['upload'] . "/" . $UNC_GALLERY['photos'] . "/$date_path/$file_name");
    $thumb_url = content_url($UNC_GALLERY['upload'] . "/" . $UNC_GALLERY['thumbnails'] . "/$date_path/$file_name");

    if ($show_thumb) {
        $shown_image = $thumb_url;
        $class = '';
    } else {
        $shown_image = $photo_url;
        $class = 'featured_image';
    }

    if (!$file_date) {
        $file_path = unc_tools_image_path($date_path, $file_name);
        $file_date = unc_tools_image_date($file_path);
    }

    $date_str = str_replace(DIRECTORY_SEPARATOR, "-", $date_path);
    if (isset($D['details'][$file_name])) {
        $description_full = $D['details'][$file_name] . " ($file_name / $file_date)";
    } else if ($D['description']) {
        $description_full = $D['description'] . " ($file_name / $file_date)";
    } else {
        $description_full = "File Name: $file_name Date: $file_date";
    }
    $out = "        <a href=\"$photo_url\" data-lightbox=\"gallery_$date_str\" title=\"$description_full\">\n"
         . "            <img alt=\"$file_name\" src=\"$shown_image\">\n"
         . "        </a>\n";
    if (is_admin()) {
        $out .= "         <button class=\"delete_image_link\" title=\"Delete Image\" onClick=\"delete_image('$file_name','$date_str')\">&#9851;</button>";
    }
    return $out;
}
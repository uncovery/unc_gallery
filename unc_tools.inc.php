<?php

if (!defined('WPINC')) {
    die;
}

/**
 * This is called by Javascript on a timer when we upload images
 * this gets the current status from a session variable
 *
 * @global type $UNC_GALLERY
 * @param type $process_name
 */
function unc_tools_progress_get($process_name = false) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    if (!$process_name) {
        $process_name = filter_input(INPUT_POST, 'process_id', FILTER_SANITIZE_STRING);
    }


    ob_start();
    session_start();
    session_write_close();

    if (!isset($_SESSION[$process_name])) {
        echo json_encode(false);
        wp_die();
    }

    if (isset($_SESSION[$process_name . "_percentage"]) ){
        $percentage = intval($_SESSION[$process_name . "_percentage"]);
    } else {
        $percentage = 0;
    }

    $result = array('text' => $_SESSION[$process_name], 'percent' => $percentage);
    echo json_encode($result);
    wp_die();
}

/**
 * This is used by the system to store process status during background
 * processes such as image upload processing.
 *
 * @global type $UNC_GALLERY
 * @param type $process_name
 * @param type $text
 * @param type $percentage
 */
function unc_tools_progress_update($process_name, $text, $percentage = false, $line_id = false) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    session_start();
    if ($line_id) { // replace previous text
        $_SESSION[$process_name][$line_id] = $text;
    } else {
        $_SESSION[$process_name][] = $text;
    }

    if ($percentage) {
        $_SESSION[$process_name . "_percentage"] = $percentage;
    }
    session_write_close();
    // we send back the line number of the last added text so that it can be replaced instead
    // of a new one added if need be.
    return count($_SESSION[$process_name]) - 1;
}

/**
 * Take a date string (with time!) and create the respective folders
 *
 * @global type $UNC_GALLERY
 * @param type $i
 * @param type $date_str
 * @return type
 */
function unc_date_folder_create($date_str) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    if (!current_user_can('manage_options')) {
        return false;
    }
    global $UNC_GALLERY;

    // these are the format strings for $date->format
    // the 'false' is to create the root folder
    $date_folders = array(false, "Y", "m", "d");
    // let's create a date object for the given date
    $date_obj = new DateTime($date_str);
    // both folders, photo and thumbnail are created together
    $path_arr = array($UNC_GALLERY['photos'], $UNC_GALLERY['thumbnails'], $UNC_GALLERY['file_data']);
    // iterate them
    foreach ($path_arr as $img_folder) {
        // create the complete folder
        $base_folder = $UNC_GALLERY['upload_path'] . "/" . $img_folder;
        // iterate the date strings y m d
        foreach ($date_folders as $date_folder) {
            // if it's not the root folder, we format the date to reflect he element
            if ($date_folder) {
                $date_element = $date_obj->format($date_folder);
                $base_folder .= "/$date_element";
            }
            // take the final folder string and check if already exists
            if (!file_exists($base_folder)) {
                $mkdir_chk = mkdir($base_folder);
                if (!$mkdir_chk) {
                    echo unc_display_errormsg("could not create folder $base_folder");
                    return false;
                } else {
                    // echo "Created folder $base_folder<br>";
                }
            }
        }
    }
    return $date_obj;
}

/**
 * Delete a date folder and all it's contents, images AND thumbs.
 * we validate that the $date_str is a valid date
 *
 * @param type $date_str
 */
function unc_date_folder_delete($date_str) {
    if (!is_admin() === true) {
        return false;
    }
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $date_obj = new DateTime($date_str);
    if (!$date_obj) {
        return unc_display_errormsg("Invalid date folder!");
    }
    // convert date to folder string
    $fstr = "/";
    $out = "";
    $date_folder = date_format($date_obj, "Y{$fstr}m{$fstr}d");

    // we have 2 paths, images adn thumbs
    $path_arr = array($UNC_GALLERY['photos'], $UNC_GALLERY['thumbnails'], $UNC_GALLERY['file_data']);
    // iterate both
    foreach ($path_arr as $img_folder) {
        // now let's get the path of that date
        $base_folder = $UNC_GALLERY['upload_path'] . "/" . $img_folder . "/" . $date_folder;
        if (!file_exists($base_folder)) {
            // the folder does not exist, so let's not delete anything
            return unc_display_errormsg("Folder $base_folder could not be deleted!");
        }
        $out .= "Deleting folder $img_folder/$date_folder:<br>";
        $it = new RecursiveDirectoryIterator($base_folder, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()){
                //$out .= " $file... <br>";
                rmdir($file->getRealPath());
            } else {
                //$out .= " $file... <br>";
                unlink($file->getRealPath());
            }
        }
        // $out .= " /$base_folder... <br>";
        rmdir($base_folder);
        // now we iterate the tree and make sure we delete all leftover empty months & year folders.
        unc_tools_folder_delete_empty($UNC_GALLERY['upload_path'] . "/" . $img_folder);
    }
    return $out;
}

/**
 * Take a folder and delete all empty subfolders
 *
 * @param type $path
 * @return type
 */
function unc_tools_folder_delete_empty($path) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    if (!is_admin() === true) {
        echo "You are not admin!";
    }

    $empty = true;
    $path_wildcard = $path . "/*";
    foreach (@glob($path_wildcard) as $file) {
        if (is_dir($file)) { // recurse lower directory
           if (!unc_tools_folder_delete_empty($file)) {
               $empty = false;
           }
        } else {
           $empty = false;
        }
    }
    if ($empty) {
        rmdir($path);
    }
    return $empty;
}

/**
 * Make a list of all images in folder and subfolder
 *
 * @param type $path
 * @return type
 */
function unc_tools_import_enumerate($path) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    if (!current_user_can('manage_options')) {
        return false;
    }
    foreach (glob($path . "/*") as $file) {
        if (is_dir($file)) { // recurse lower directory
           unc_tools_import_enumerate($file);
        } else {
           $UNC_GALLERY['import']['tmp_name'][] = $file;
           $UNC_GALLERY['import']['type'][] = mime_content_type($file);
           $UNC_GALLERY['import']['name'][] = basename($file);
           $UNC_GALLERY['import']['error'][] = 0;
        }
    }
}

/**
 * this converts an array of dates to UTC
 * TODO need to check if this is obsolete. This was used in unc_gallery_display_page
 * to fix the dates from unc_tools_folder_list, but they never had times with them
 *
 * @param type $dates
 * @return type
 */
function unc_display_fix_timezones($dates) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $new_dates = array();
    foreach ($dates as $date => $details) {
        $date_obj = new DateTime($date);
        // change timezone to UTC
        $date_obj->setTimezone(new DateTimeZone('UTC'));
        $date_str = $date_obj->format("Y-m-d");
        $new_dates[$date_str] = $details;
    }
    return $new_dates;
}



/**
 * Assemble a file description from EXIF values
 *
 * @param type $F
 */
function unc_tools_file_desc($F) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__);}
    $out = '';
    $code_sets = array('exif', 'xmp', 'ipct');
    // we iterate the 3 information sets
    foreach ($code_sets as $set_name) {
        // we get the configured settings to know which parts we take
        $set = $UNC_GALLERY['show_'.$set_name.'_data'];
        // if we do not have any settings in this set, continue
        if (!is_array($set)) {
            continue;
        }
        // lets go through the configs of the current section
        foreach ($set as $key => $desc) {
            // only look if it's actually set. This should not be needed
            if (isset($F[$set_name][$key])) {
                // arrays should be exploded
                if (is_array($F[$set_name][$key])) {
                    $text = implode(",&nbsp;", $F[$set_name][$key]);
                } else {
                    $text = $F[$set_name][$key];
                }
                // write the code. This could be improved for CSS
                $out .= "<b>$desc:</b>&nbsp;$text; ";
            }
        }
    }

    return $out;
}


/**
 * recurse a folder and apply a custom function to the files
 *
 * @param type $base_folder
 * @param type $function
 * @return array
 */
function unc_tools_recurse_files($base_folder, $file_function, $dir_function) {
    global $TMP_FOLDERS, $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // safety net
    if (strpos($base_folder, './')) {
        die("Error, recursive path! $base_folder");
    }
    if (!file_exists($base_folder)) {
        return false;
    }
    foreach (glob($base_folder . "/*") as $file) {
        if (is_dir($file)) {
            $TMP_FOLDERS[] = unc_tools_recurse_files($file, $file_function, $dir_function);
        } else {
            // working on $file in folder $main
            $TMP_FOLDERS[] = $file_function($file);
        }
    }
    $TMP_FOLDERS[] = $dir_function($base_folder);
    return $TMP_FOLDERS;
}

/**
 * Recursively scan directories and make a list of the deepest folders
 *
 * @global type $TMP_FOLDERS
 * @param type $base_folder
 * @return type
 */
function unc_tools_recurse_folders($base_folder) {
    global $TMP_FOLDERS, $UNC_GALLERY;
    // if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__);}
    if (strpos($base_folder, './')) {
        die("Error, recursive path! $base_folder");
    }
    $has_subfolder = false;
    if (!file_exists($base_folder)) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("Base folder does not exist: ", $base_folder);}
        return false;
    }
    foreach (glob($base_folder . "/*") as $folder) {
        // found a sub-folder, go deeper
        if (is_dir($folder)) {
            unc_tools_recurse_folders($folder);
            $has_subfolder = true;
        }
    }
    if (!$has_subfolder) {
        $path_arr = explode("/", $base_folder);
        $date_elements = array_slice($path_arr, -3, 3);
        $date_string = implode("/", $date_elements);
        $TMP_FOLDERS[$date_string] = $base_folder;
    }
    return $TMP_FOLDERS;
}

/**
 * returns the latest file from a folder
 *
 * @param type $date_path
 * @return type
 */
function unc_tools_file_latest($date_path) {
    global $UNC_GALLERY, $UNC_FILE_DATA;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $base_folder = $UNC_GALLERY['upload_path'] . "/" . $UNC_GALLERY['file_data'] . "/" . $date_path;
    $folder_files = array();

    foreach (glob($base_folder . "/*") as $file_path) {
        // found a sub-folder, go deeper
        if (!is_dir($file_path)) {
            require_once($file_path);
            $file_name = basename($file_path, ".php");
            $file_code = md5($date_path . "/" . $file_name . ".php");
            $file_timestamp = $UNC_FILE_DATA[$file_code]['time_stamp'];
            $folder_files[$file_timestamp] = $UNC_FILE_DATA[$file_code];
        }
    }
    krsort($folder_files);
    $latest_path = array_shift($folder_files);
    $latest_file = $latest_path['file_name'];
    return $latest_file;
}


/**
 * returns a random file from a day
 *
 * @param type $date_path
 * @return type
 */
function unc_tools_file_random($date_path) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $base_folder = $UNC_GALLERY['upload_path'] . "/" . $UNC_GALLERY['photos'] . "/" . $date_path;
    $files = array();
    foreach (glob($base_folder . "/*") as $file) {
        // found a sub-folder, go deeper
        if (!is_dir($file)) {
            $files[] = $file;
        }
    }
    // get random file
    $count = count($files);
    $rnd = random_int(0, $count - 1);
    $rnd_path = $files[$rnd];
    $rnd_file = basename($rnd_path);
    return $rnd_file;
}

/**
 * takes a folder and returns the date of the folder as a date-string
 *
 * @param type $folder
 * @return type
 */
function unc_tools_folder_date($folder) {
    // global $UNC_GALLERY;
    // if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $path_arr = explode("/", $folder);
    // get last 3 elements
    $new_date_arr = array_slice($path_arr, -3, 3);
    $new_date_str = implode("-", $new_date_arr);
    return $new_date_str;
}

/**
 * convert ini_get values in M/G values to bytes for JS comparison
 *
 * @param type $ini_val
 * @return int
 */
function unc_tools_bytes_get($ini_val) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $val = trim($ini_val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

/**
 * Generate a folder from a date-path / filename combination
 *
 * @global array $UNC_GALLERY
 * @param type $date_path
 * @param type $file_name
 * @return string
 */
function unc_tools_image_path($date_path, $file_name) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $photo_folder =  $UNC_GALLERY['upload_path'] . "/" . $UNC_GALLERY['photos'] ;
    $curr_photo_folder = $photo_folder . "/" . $date_path;
    $file_path = $curr_photo_folder . "/" . $file_name;
    return $file_path;
}



/**
 * Enumerate the fodlers with images to display the datepicker properly.
 *
 * @global type $UNC_GALLERY
 * @param type $base_folder
 * @return type
 */
function unc_tools_folder_list() {
    global $UNC_GALLERY, $wpdb;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $att_table_name = $wpdb->prefix . "unc_gallery_att";
    $sql = "SELECT att_value as date_str, count(att_id) as counter
        FROM `$att_table_name`
        WHERE att_name='date_str'
        GROUP BY att_value
        ORDER BY `att_value`  ASC";
    $date_search = $wpdb->get_results($sql, 'ARRAY_A');
    $dates = array();
    foreach ($date_search as $D) {
        $dates[$D['date_str']] = $D['counter'];
    }
    return $dates;
}

/**
 * Delete an image
 *
 * @global array $UNC_GALLERY
 */
function unc_tools_image_delete() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    if (!is_admin() === true) {
        ob_clean();
        echo "You are not admin!";
        wp_die();
    }

    $file_name_raw = filter_input(INPUT_GET, 'file_name', FILTER_SANITIZE_STRING);
    if (!$file_name = unc_tools_filename_validate($file_name_raw)) {
        ob_clean();
        echo "File name $file_name_raw is not allowed!";
        wp_die();
    }

    $date_wrong = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
    $date_str = str_replace("-", "/", $date_wrong);


    $paths = array(
        $UNC_GALLERY['photos'] => $file_name,
        $UNC_GALLERY['thumbnails'] => $file_name,
    );

    foreach ($paths as $path => $del_file_name) {
        $full_path = $UNC_GALLERY['upload_path'] . "/" . $path . "/" . $date_str . "/" . $del_file_name;
        unc_image_info_exiftool($full_path);
        wp_die();
        if (file_exists($full_path)) {
            $check = unlink($full_path);
            if ($check) {
                ob_clean();
                echo "File Deleted!";
            } else {
                ob_clean();
                echo "File delete failed!";
                wp_die();
            }
        } else {
            // we cannot stop at an error so there are no leftover files
            echo "File name $full_path could not be found!";
        }
    }

    // delete file data
    $check = unc_image_info_delete($file_name, $date_str);
    if (!$check) {
        ob_clean();
        echo "File data could not be deleted: $file_name $date_str";
        wp_die();
    }

    unc_tools_folder_delete_empty($UNC_GALLERY['upload_path']);
    unc_display_ajax_folder();
}

/**
 * Make sure a filename has only valid letters in it
 *
 * @global array $UNC_GALLERY
 * @param type $file_name
 * @return boolean
 */
function unc_tools_filename_validate($file_name) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    if (strpbrk($file_name, "\\/?%*:|\"<>") === FALSE) {
        return $file_name;
    } else {
        return false;
    }
}

/**
 * converts a 2013-12-12 to 2013/12/12 and checks if the file exists
 *
 * @global type $UNC_GALLERY
 * @param type $date
 * @return type
 */
function unc_tools_date_path($date) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $date_obj = new DateTime($date . " 00:00:00");
    if ($date_obj) {
        $format = implode("/", array('Y', 'm', 'd'));
        $date_str = $date_obj->format($format);
        $photo_folder =  $UNC_GALLERY['upload_path'] . "/" . $UNC_GALLERY['photos'];
        if (!file_exists($photo_folder . "/" . $date_str)) {
            if ($UNC_GALLERY['no_image_alert'] == 'error') {
                $UNC_GALLERY['errors'][] = unc_display_errormsg("No images found for this date!");
            } else if ($UNC_GALLERY['no_image_alert'] == 'not_found') {
                $UNC_GALLERY['errors'][] = "No images available for $date";
            }
            return false;
        }
    } else {
        $UNC_GALLERY['errors'][] = unc_display_errormsg("Date not found (invalid date)");
        return false;
    }
    return $date_str;
}

/**
 * This takes a string such as '2/10' and evaluates the result as a mathematical function.
 * @param type $string
 */
function unc_tools_divide_string($string) {
    $f = explode("/", $string);
    $result = $f[0] / $f[1];
    return number_format($result, 1);
}

/**
 * analyses arrays for differences
 *
 * @param type $array1
 * @param type $array2
 * @return type
 */
function unc_tools_array_analyse($array1, $array2) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $only_1 = array_diff($array1, $array2);
    $only_2 = array_diff($array2, $array1);
    $section = array_intersect($array1, $array2);
    $union = array_merge($only_1, $only_2, $section);

    $out = array(
        'only_in_1' => $only_1,
        'only_in_2' => $only_2,
        'common' => $section,
        'complete_set' => $union,
    );
    return $out;
}
<?php

if (!defined('WPINC')) {
    die;
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
    if (!is_admin()) {
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
        $base_folder = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $img_folder;
        // iterate the date strings y m d
        foreach ($date_folders as $date_folder) {
            // if it's not the root folder, we format the date to reflect he element
            if ($date_folder) {
                $date_element = $date_obj->format($date_folder);
                $base_folder .= DIRECTORY_SEPARATOR . "$date_element";
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
    if (!current_user_can('manage_sites')) {
        return false;
    }
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $date_obj = new DateTime($date_str);
    if (!$date_obj) {
        return unc_display_errormsg("Invalid date folder!");
    }
    // convert date to folder string
    $fstr = DIRECTORY_SEPARATOR;
    $out = "";
    $date_folder = date_format($date_obj, "Y{$fstr}m{$fstr}d");

    // we have 2 paths, images adn thumbs
    $path_arr = array($UNC_GALLERY['photos'], $UNC_GALLERY['thumbnails'], $UNC_GALLERY['file_data']);
    // iterate both
    foreach ($path_arr as $img_folder) {
        // now let's get the path of that date
        $base_folder = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $img_folder . DIRECTORY_SEPARATOR . $date_folder;
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
        unc_tools_folder_delete_empty($UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $img_folder);
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

    if (!current_user_can('manage_sites')) {
        return false;
    }
    $empty = true;
    foreach (glob($path . DIRECTORY_SEPARATOR . "*") as $file) {
        if (is_dir($file)) { // recurse lower directory
           if (!unc_tools_folder_delete_empty($file)) {
               $empty = false;
           }
        } else {
           $empty = false;
        }
    }
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace('empty folder', $empty);}
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

    if (!current_user_can('manage_sites')) {
        return false;
    }
    foreach (glob($path . DIRECTORY_SEPARATOR . "*") as $file) {
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
 * create a list of all dates between 2 dates
 *
 * @param string $date1 (date_str format)
 * @param string $date2
 * @return array
 */
function unc_tools_date_span($date1, $date2) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // we try to sort the dates
    if ($date1 < $date2) {
        $early = $date1;
        $later = $date2;
    } else if ($date1 == $date2) {
        return array($date1);
    } else {
        $early = $date2;
        $later = $date1;
    }

    if (strlen($later) == 10) {
        $later .= " 23:59:59";
    }

    $dates_arr = new DatePeriod(
         new DateTime($early),
         new DateInterval('P1D'),
         new DateTime($later)
    );
    $date_str_arr = array();
    foreach($dates_arr as $date_obj) {
        $date_str_arr[] = $date_obj->format("Y-m-d");
    }
    return $date_str_arr;
}

/**
 * Iterate all files in a folder and make a list of all the images with all the info
 * for them
 *
 * @global type $UNC_GALLERY
 * @param type $folder
 * @return array
 */
function unc_tools_images_list($D = false) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    if (!$D) {
        $D = $UNC_GALLERY['display'];
    }

    $dates = $D['dates'];

    $files = array();
    $featured_list = array();

    foreach ($dates as $date_str) {
        // translate date string to folder
        $date_path = str_replace("-", DIRECTORY_SEPARATOR, $date_str);
        $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];
        $folder = $photo_folder . DIRECTORY_SEPARATOR . $date_path;
        foreach (glob($folder . DIRECTORY_SEPARATOR . "*") as $file_path) {
            $F = unc_image_info_read($file_path, $D);
            if (($D['range']['end_time'] && $D['range']['start_time']) && // only if both are set
                    ($D['range']['end_time'] < $D['range']['start_time'])) { // AND the end is before the start
                if (($D['range']['end_time'] < $F['time_stamp'])
                        && ($F['time_stamp'] < $D['range']['start_time'])) {  // then skip over the files inbetween end and start
                    continue;
                }
            } else if (($D['range']['start_time'] && ($F['time_stamp'] < $D['range']['start_time'])) || // if there is a start and the file is earlier
                ($D['range']['end_time'] && ($D['range']['end_time'] < $F['time_stamp']))) { // or if there is an end and the file is later then skip
                continue;
            }
            if (in_array($F['file_name'], $D['featured_image'])) {
                $F['featured'] = true;
                $featured_list[] = $F;
            } else {
                $F['featured'] = false;
                $files[$F['file_date']] = $F;
            }
        }
    }
    ksort($files);
    foreach ($featured_list as $feat) {
        array_unshift($files, $feat);
    }
    return $files;
}

/**
 * Assemble a file description from EXIF values
 *
 * @param type $F
 */
function unc_tools_file_desc($F) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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
function unc_gallery_recurse_files($base_folder, $file_function, $dir_function) {
    global $TMP_FOLDERS, $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // safety net
    if (strpos($base_folder, '.' . DIRECTORY_SEPARATOR)) {
        die("Error, recursive path! $base_folder");
    }
    foreach (glob($base_folder . DIRECTORY_SEPARATOR . "*") as $file) {
        if (is_dir($file)) {
            $TMP_FOLDERS[] = unc_gallery_recurse_files($file, $file_function, $dir_function);
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    if (strpos($base_folder, '.' . DIRECTORY_SEPARATOR)) {
        die("Error, recursive path! $base_folder");
    }
    $has_subfolder = false;
    foreach (glob($base_folder . DIRECTORY_SEPARATOR . "*") as $folder) {
        // found a sub-folder, go deeper
        if (is_dir($folder)) {
            unc_tools_recurse_folders($folder);
            $has_subfolder = true;
        }
    }
    if (!$has_subfolder) {
        $path_arr = explode(DIRECTORY_SEPARATOR, $base_folder);
        $date_elements = array_slice($path_arr, -3, 3);
        $date_string = implode(DIRECTORY_SEPARATOR, $date_elements);
        $TMP_FOLDERS[$date_string] = $base_folder;
    }
    return $TMP_FOLDERS;
}

/**
 * returns the latest date
 *
 * @global type $UNC_GALLERY
 * @return type
 */
function unc_tools_date_latest() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];
    $folders = unc_tools_recurse_folders($photo_folder);
    if (count($folders) == 1 ) {
        $val = reset($folders);
        if ($val == $photo_folder) {
            return false;
        }
    }
    rsort($folders);

    $my_folder = $folders[0];
    $new_date_str = unc_tools_folder_date($my_folder);
    return $new_date_str;
}

/**
 * returns a random date
 *
 * @global type $UNC_GALLERY
 * @return type
 */
function unc_tools_date_random() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];
    $folders = unc_tools_recurse_folders($photo_folder);
    if (count($folders) == 0) {
        return false;
    }
    $count = count($folders);
    $rnd = random_int (0, $count - 1);
    $my_folder = $folders[$rnd];
    // split by path
    $new_date_str = unc_tools_folder_date($my_folder);
    return $new_date_str;
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
    $base_folder = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['file_data'] . DIRECTORY_SEPARATOR . $date_path;
    $folder_files = array();

    foreach (glob($base_folder . DIRECTORY_SEPARATOR . "*") as $file_path) {
        // found a sub-folder, go deeper
        if (!is_dir($file_path)) {
            require_once($file_path);
            $file_name = basename($file_path, ".php");
            $file_code = md5($date_path . DIRECTORY_SEPARATOR . $file_name . ".php");
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
 * returns a random file from a folder
 *
 * @param type $date_path
 * @return type
 */
function unc_tools_file_random($date_path) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $base_folder = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'] . DIRECTORY_SEPARATOR . $date_path;
    $files = array();
    foreach (glob($base_folder . DIRECTORY_SEPARATOR . "*") as $file) {
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
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $path_arr = explode(DIRECTORY_SEPARATOR, $folder);
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
    $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'] ;
    $curr_photo_folder = $photo_folder . DIRECTORY_SEPARATOR . $date_path;
    $file_path = $curr_photo_folder . DIRECTORY_SEPARATOR . $file_name;
    return $file_path;
}



/**
 * Enumerate the fodlers with images to display the datepicker properly.
 *
 * @global type $UNC_GALLERY
 * @param type $base_folder
 * @return type
 */
function unc_tools_folder_list($base_folder) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];
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
            $new_dates = unc_tools_folder_list($current_path);
            if (count($new_dates) > 0) {
                $dates = array_merge($dates, $new_dates);
            }
        } else { // we have a file
            $cur_date = str_replace(DIRECTORY_SEPARATOR, "-", substr($base_folder, $base_length));
            $dates[$cur_date][] = $file;
        }
    }
    krsort($dates);
    // the above dates are local timezone, we need the same date in UTC
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

    if (!current_user_can('manage_sites')) {
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
    $date_str = str_replace("-", DIRECTORY_SEPARATOR, $date_wrong);


    $paths = array(
        $UNC_GALLERY['thumbnails'] => $file_name,
        $UNC_GALLERY['photos'] => $file_name,
        $UNC_GALLERY['file_data'] => $file_name . ".php",
    );

    foreach ($paths as $path => $del_file_name) {
        $full_path = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $date_str . DIRECTORY_SEPARATOR . $del_file_name;
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
 * Validate Datestr
 *
 * @param type $date_str
 * @return boolean
 */
function unc_tools_validate_date($date_str) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $pattern = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
    if (preg_match($pattern, $date_str)) {
        return $date_str;
    }else{
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
        $format = implode(DIRECTORY_SEPARATOR, array('Y', 'm', 'd'));
        $date_str = $date_obj->format($format);
        $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];
        if (!file_exists($photo_folder . DIRECTORY_SEPARATOR . $date_str)) {
            echo unc_display_errormsg("Date not found (folder does not exist) $photo_folder/$date_str");
            return false;
        }
    } else {
        echo unc_display_errormsg("Date not found (invalid date)");
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
 * Get all possible information about a single file
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @param type $D

function unc_tools_image_info_get($file_path, $D = false) {
    global $UNC_GALLERY;

    $I = unc_image_info_read($file_path);

    $file_date = unc_image_date($file_path); // get image date from EXIF/IPCT
    $dtime = DateTime::createFromFormat("Y-m-d G:i:s", $file_date);
    $time_stamp = $dtime->getTimestamp(); // time stamp is easier to compare
    $folder_info = pathinfo($file_path);
    $date_str = unc_tools_folder_date($folder_info['dirname']);
    $date_path = str_replace("-", DIRECTORY_SEPARATOR, $date_str);
    $file_name = $folder_info['basename'];
    $exif = unc_exif_get($file_path);

    $orientation = 'landscape';
    if ($exif['file_width'] < $exif['file_height']) {
        $orientation = 'portrait';
    }

    $photo_url = content_url($UNC_GALLERY['upload'] . "/" . $UNC_GALLERY['photos'] . "/$date_path/$file_name");
    $thumb_url = content_url($UNC_GALLERY['upload'] . "/" . $UNC_GALLERY['thumbnails'] . "/$date_path/$file_name");
    $file = array(
        'file_name' => $file_name,
        'file_path' => $file_path,
        'thumb_url' => $thumb_url,
        'file_url' => $photo_url,
        'time_stamp' => $time_stamp, // linux time stamp
        'file_date' => $file_date, // full date including time
        'date_str' => substr($file_date, 0, 10), // only the day 0000-00-00
        'orientation' => $orientation,
    );

    $out = array_merge($file, $exif);
    return $out;
}
 *
 */
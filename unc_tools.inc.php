<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Take a date string and create the respective folders
 *
 * @global type $WPG_CONFIG
 * @param type $i
 * @param type $date_str
 * @return type
 */
function unc_date_folder_create($date_str) {
    global $WPG_CONFIG;

    // these are the format strings for $date->format
    // the 'false' is to create the root folder
    $date_folders = array(false, "Y", "m", "d");
    // we get the base folder from config
    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];
    // let's create a date object for the given date
    $date_obj = unc_datetime($date_str);
    // substract 12 hours to get the correct date
    $date_obj->modify($WPG_CONFIG['offset']);

    // both folders, photo and thumbnail are created together
    $path_arr = array($WPG_CONFIG['photos'], $WPG_CONFIG['thumbnails']);
    // iterate them
    foreach ($path_arr as $img_folder) {
        // create the complete folder
        $base_folder = $dirPath . $img_folder;
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
                    echo "ERROR, could not create folder $base_folder<br>";
                    return false;
                } else {
                    echo "Created folder $base_folder<br>";
                }
            }
        }
    }
    return $date_obj;
}

/**
 * Delete a date folder and all it's contents, images AND thumbs.
 * we need to validate that the $date_str is a valid date
 *
 * @param type $date_str
 */
function unc_date_folder_delete($date_str) {
    global $WPG_CONFIG;

    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];
    $date_obj = unc_datetime($date_str);
    if (!$date_obj) {
        return "Invalid date folder!";
    }
    // convert date to folder string
    $fstr = DIRECTORY_SEPARATOR;
    $out = "";
    $date_folder = date_format($date_obj, "Y{$fstr}m{$fstr}d");

    $path_arr = array($WPG_CONFIG['photos'], $WPG_CONFIG['thumbnails']);
    foreach ($path_arr as $img_folder) {
        $base_folder = $dirPath . $img_folder . DIRECTORY_SEPARATOR . $date_folder;
        if (!file_exists($base_folder)) {
            return "Folder $base_folder could not be deleted!";
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
    }
    return $out;
}

/**
 * returns a date-time object with todays timezone
 * get a MySQL timestamp with $now = $date_now->format("Y-m-d H:i:s");
 *
 * @global type $WPG_CONFIG
 * @param type $date
 * @return \DateTime
 */
function unc_datetime($date = NULL) {
    global $WPG_CONFIG;

    //if ($date != NULL) {
    //    $date .= "+08:00"; // incoming timezones are already HKT
    //}
    $date_new = new DateTime($date);
    $date_new->setTimezone(new DateTimeZone($WPG_CONFIG['timezone']));
    return $date_new;
}

/**
 * this converts an array of dates to UTC
 *
 * @param type $dates
 * @return type
 */
function unc_display_fix_timezones($dates) {
    $new_dates = array();
    foreach ($dates as $date => $details) {
        $date_obj = unc_datetime($date);
        // change timezone to UTC
        $date_obj->setTimezone(new DateTimeZone('UTC'));
        $date_str = $date_obj->format("Y-m-d");
        $new_dates[$date_str] = $details;
    }
    return $new_dates;
}

/**
 * this display a multi-dimensional array as an HTML list
 *
 * @param type $array
 * @param string $path
 * @return string
 */
function unc_array_iterate_compact($array, $path = '') {
    if (!is_array(($array))) {
        return "$array";
    }
    $out = "\n<ul>";
    foreach ($array as $element => $content) {
        $out .= "\n<li>$element \n";
        $path .= "/" . $element;
        $out .= unc_array_iterate_compact($content, $path);
        $out .= "</li>";
    }
    $out .= "</ul>";
    return $out;
}

/**
 * recurse a folder and apply a custom function to the files
 *
 * @param type $base_folder
 * @param type $function
 * @return array
 */
function unc_gallery_recurse_files($base_folder, $function) {
    $out = array();
    // safety net
    if (strpos($base_folder, '.' . DIRECTORY_SEPARATOR)) {
        die("Error, recursive path! $base_folder");
    }
    foreach (glob($base_folder . DIRECTORY_SEPARATOR . "*") as $file) {
        if (is_dir($file)) {
            $out[] = unc_gallery_recurse_files($file, $function);
        } else {
            // working on $file in folder $main
            $out[] = $function($file);
        }
    }
    return $out;
}

function unc_tools_recurse_folders($base_folder) {
    global $TMP_FOLDERS;
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
        $TMP_FOLDERS[] = $base_folder;
    }
    return $TMP_FOLDERS;
}

/**
 * returns the latest date
 *
 * @global type $WPG_CONFIG
 * @return type
 */
function unc_tools_date_latest() {
    global $WPG_CONFIG;
    $photo_folder =  WP_CONTENT_DIR . $WPG_CONFIG['upload'] . $WPG_CONFIG['photos'];
    $folders = unc_tools_recurse_folders($photo_folder);

    rsort($folders);

    $my_folder = $folders[0];
    $new_date_str = unc_tools_folder_date($my_folder);
    return $new_date_str;
}

/**
 * returns a random date
 *
 * @global type $WPG_CONFIG
 * @return type
 */
function unc_tools_date_random() {
    global $WPG_CONFIG;
    $photo_folder =  WP_CONTENT_DIR . $WPG_CONFIG['upload'] . $WPG_CONFIG['photos'];
    $folders = unc_tools_recurse_folders($photo_folder);

    $count = count($folders);
    $rnd = random_int (0, $count - 1);
    $my_folder = $folders[$rnd];
    // split by path
    $new_date_str = unc_tools_folder_date($my_folder);
    return $new_date_str;
}

/**
 * checks if a date is valid and sends it back
 *
 * @param type $date
 * @return type
 */
function unc_tools_date_validate($date) {

    $newdate = $date;
    return $newdate;
}

/**
 * takes a folder and returns the date of the folder.
 *
 * @param type $folder
 * @return type
 */
function unc_tools_folder_date($folder) {
    $path_arr = explode(DIRECTORY_SEPARATOR, $folder);
    $folder_count = count($path_arr);
    // get last 3 elements
    $new_date_arr = array($path_arr[$folder_count - 3], $path_arr[$folder_count - 2], $path_arr[$folder_count - 1]);
    $new_date_str = implode("-", $new_date_arr);
    return $new_date_str;
}
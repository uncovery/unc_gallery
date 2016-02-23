<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Take a date string and create the respective folders
 *
 * @global type $UNC_GALLERY
 * @param type $i
 * @param type $date_str
 * @return type
 */
function unc_date_folder_create($date_str) {
    global $UNC_GALLERY;

    // these are the format strings for $date->format
    // the 'false' is to create the root folder
    $date_folders = array(false, "Y", "m", "d");
    // we get the base folder from config
    $dirPath =  WP_CONTENT_DIR . $UNC_GALLERY['upload'];
    // let's create a date object for the given date
    $date_obj = unc_datetime($date_str);
    // substract 12 hours to get the correct date
    $date_obj->modify($UNC_GALLERY['time_offset']);
    echo "Date after adjustment ({$UNC_GALLERY['time_offset']}): " . $date_obj->format("Y-m-d") . "<br>";

    // both folders, photo and thumbnail are created together
    $path_arr = array($UNC_GALLERY['photos'], $UNC_GALLERY['thumbnails']);
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
                    echo unc_tools_errormsg("could not create folder $base_folder");
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
 * we validate that the $date_str is a valid date
 *
 * @param type $date_str
 */
function unc_date_folder_delete($date_str) {
    global $UNC_GALLERY;

    $dirPath =  WP_CONTENT_DIR . $UNC_GALLERY['upload'];
    $date_obj = unc_datetime($date_str);
    if (!$date_obj) {
        return unc_tools_errormsg("Invalid date folder!");
    }
    // convert date to folder string
    $fstr = DIRECTORY_SEPARATOR;
    $out = "";
    $date_folder = date_format($date_obj, "Y{$fstr}m{$fstr}d");

    // we have 2 paths, images adn thumbs
    $path_arr = array($UNC_GALLERY['photos'], $UNC_GALLERY['thumbnails']);
    // iterate both
    foreach ($path_arr as $img_folder) {
        // now let's get the path of that date
        $base_folder = $dirPath . $img_folder . DIRECTORY_SEPARATOR . $date_folder;
        if (!file_exists($base_folder)) {
            // the folder does not exist, so let's not delete anything
            return unc_tools_errormsg("Folder $base_folder could not be deleted!");
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
        unc_tools_folder_delete_empty($dirPath . $img_folder);
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
    $empty = true;
    foreach (glob($path . DIRECTORY_SEPARATOR . "*") as $file) {
        if (!is_dir($file)) {
            XMPP_ERROR_trigger("$file is not a directory!");
        }
        $empty &= is_dir($file) && unc_tools_folder_delete_empty($file);
    }
    return $empty && rmdir($path);
}

/**
 * returns a date-time object with todays timezone
 * get a MySQL timestamp with $now = $date_now->format("Y-m-d H:i:s");
 *
 * @global type $UNC_GALLERY
 * @param type $date
 * @return \DateTime
 */
function unc_datetime($date = NULL) {
    global $UNC_GALLERY;

    //if ($date != NULL) {
    //    $date .= "+08:00"; // incoming timezones are already HKT
    //}
    $date_new = new DateTime($date);
    // $date_new->setTimezone(new DateTimeZone($UNC_GALLERY['timezone']));
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
function unc_gallery_recurse_files($base_folder, $file_function, $dir_function) {
    global $TMP_FOLDERS;
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
 * @global type $UNC_GALLERY
 * @return type
 */
function unc_tools_date_latest() {
    global $UNC_GALLERY;
    $photo_folder =  WP_CONTENT_DIR . $UNC_GALLERY['upload'] . $UNC_GALLERY['photos'];
    $folders = unc_tools_recurse_folders($photo_folder);
    if (count($folders) == 1 && $folders[0] == $photo_folder) {
        return false;
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
    $photo_folder =  WP_CONTENT_DIR . $UNC_GALLERY['upload'] . $UNC_GALLERY['photos'];
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

function unc_tools_errormsg($error) {
    return "<div class=\"unc_gallery_error\">ERROR: $error</div>";
}
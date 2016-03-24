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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
    if (!is_admin()) {
        return false;
    }
    global $UNC_GALLERY;

    // these are the format strings for $date->format
    // the 'false' is to create the root folder
    $date_folders = array(false, "Y", "m", "d");
    // let's create a date object for the given date
    $date_obj = new DateTime($date_str);
    // substract 12 hours to get the correct date
    $date_obj->modify($UNC_GALLERY['time_offset']);
    // echo "Date after adjustment ({$UNC_GALLERY['time_offset']}): " . $date_obj->format("Y-m-d") . "<br>";

    // both folders, photo and thumbnail are created together
    $path_arr = array($UNC_GALLERY['photos'], $UNC_GALLERY['thumbnails']);
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
                    echo unc_tools_errormsg("could not create folder $base_folder");
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
    if (!is_admin()) {
        return false;
    }
    global $UNC_GALLERY;

    $date_obj = new DateTime($date_str);
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
        $base_folder = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $img_folder . DIRECTORY_SEPARATOR . $date_folder;
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

    if (!is_admin()) {
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
    $UNC_GALLERY['debug'][]['$empty'] = $empty;
    if ($empty) {
        rmdir($path);
    }
    return $empty;
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
        $date_obj = new DateTime($date);
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
 * Iterate all files in a folder and make a list of all the images with all the info
 * for them
 * 
 * @global type $UNC_GALLERY
 * @param type $folder
 * @return array
 */
function unc_tools_images_list($date_str) {
    global $UNC_GALLERY;
    
    // translate date string to folder
    $date_path = str_replace("-", DIRECTORY_SEPARATOR, $date_str);
    $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];
    $folder = $photo_folder . DIRECTORY_SEPARATOR . $date_path;
    
    $D = $UNC_GALLERY['display'];
    
    $files = array();
    $featured = false;
    foreach (glob($folder . DIRECTORY_SEPARATOR . "*") as $file_path) {
        $F = unc_tools_image_info_get($file_path);
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
        if ($F['file_name'] == $D['featured_image']){ 
            $F['featured'] = true;
            $featured = $F;
        } else {
            $F['featured'] = false;
            $files[$F['file_date']] = $F;
        }
    }
    ksort($files);
    if ($featured) {
        array_unshift($files, $featured);
    }
    
    return $files;
}

/**
 * Get all possible information about a single file
 * 
 * @global type $UNC_GALLERY
 * @param type $file_path
 */
function unc_tools_image_info_get($file_path) {
    global $UNC_GALLERY;
    $file_date = unc_tools_image_date($file_path); // get image date from EXIF/IPCT
    $dtime = DateTime::createFromFormat("Y-m-d G:i:s", $file_date);
    $time_stamp = $dtime->getTimestamp(); // time stamp is easier to compare
    $folder_info = pathinfo($file_path);
    $date_str = unc_tools_folder_date($folder_info['dirname']);
    $date_path = str_replace("-", "/", $date_str);
    $file_name = $folder_info['basename'];
    
    $D = $UNC_GALLERY['display'];
    if (isset($D['details'][$file_name])) {
        $description = $D['details'][$file_name] . " ($file_name / $file_date)";
    } else if ($D['description']) {
        $description = $D['description'] . " ($file_name / $file_date)";
    } else {
        $description = "File Name: $file_name Date: $file_date";
    }

    $photo_url = content_url($UNC_GALLERY['upload'] . "/" . $UNC_GALLERY['photos'] . "/$date_path/$file_name");
    $thumb_url = content_url($UNC_GALLERY['upload'] . "/" . $UNC_GALLERY['thumbnails'] . "/$date_path/$file_name");
    $image_size = getimagesize($file_path);
    $file  = array(
        'file_name' => $file_name,
        'file_width' => $image_size[0],
        'file_height' => $image_size[1],
        'file_path' => $file_path,
        'thumb_url' => $thumb_url,
        'file_url' => $photo_url,
        'time_stamp' => $time_stamp, // linux time stamp
        'file_date' => $file_date, // full date including time
        'date_str' => substr($file_date, 0, 10), // only the day 0000-00-00
        'description' => $description,
    );
    return $file;
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

    $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];
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
    global $UNC_GALLERY;
    $base_folder = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'] . DIRECTORY_SEPARATOR . $date_path;
    $paths = array();
    foreach (glob($base_folder . DIRECTORY_SEPARATOR . "*") as $file) {
        // found a sub-folder, go deeper
        if (!is_dir($file)) {
            $file_date = unc_tools_image_date($file);
            $paths[$file_date] = $file;
        }
    }

    krsort($paths);
    $latest_path = array_shift($paths);
    $latest_file = basename($latest_path);
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
    $path_arr = explode(DIRECTORY_SEPARATOR, $folder);
    // get last 3 elements
    $new_date_arr = array_slice($path_arr, -3, 3);
    $new_date_str = implode("-", $new_date_arr);
    return $new_date_str;
}

function unc_tools_errormsg($error) {
    return "<div class=\"unc_gallery_error\">ERROR: $error</div>";
}

/**
 * convert ini_get values in M/G values to bytes for JS comparison
 *
 * @param type $ini_val
 * @return int
 */
function unc_tools_bytes_get($ini_val) {
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
    $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'] ;
    $curr_photo_folder = $photo_folder . DIRECTORY_SEPARATOR . $date_path;
    $file_path = $curr_photo_folder . DIRECTORY_SEPARATOR . $file_name;
    return $file_path;
}

/**
 * Get the date of an image, first EXIF, then IPCT
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @return boolean
 */
function unc_tools_image_date($file_path) {
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
    $exif = unc_tools_image_exif_date($file_path);
    if (!$exif) {
        $UNC_GALLERY['debug'][]["image date check"] = "exif failed, getting ipct";
        $ipct = unc_tools_image_ipct_date($file_path);
        if ($ipct) {
            return $ipct;
        } else {
            $UNC_GALLERY['debug'][]["image date check"] = "ipct failed, bail!";
            return false;
        }
    } else {
        return $exif;
    }
}

/**
 * Get the EXIF date of a file based on date & filename only
 *
 * @global type $UNC_GALLERY
 * @param type $date_path
 * @param type $file_name
 * @return type
 */
function unc_tools_image_exif_date($file_path) {
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
    $exif_data = exif_read_data($file_path);
    // if EXIF Invalid, try IPICT
    if (!$exif_data || !isset($exif_data['DateTimeOriginal'])) {
        return false;
    }
    $file_date = $exif_data['DateTimeOriginal'];
    $search_pattern = '/(\d\d\d\d):(\d\d):(\d\d \d\d:\d\d:\d\d)/';
    $replace_pattern = '$1-$2-$3';
    $fixed_date = preg_replace($search_pattern, $replace_pattern, $file_date);
    return $fixed_date;
}


/**
 * Get the IPCT date of an image
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @return boolean
 */
function unc_tools_image_ipct_date($file_path) {
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
    $ipct_obj = new IPTC($file_path);

    $ipct_date = $ipct_obj->get(IPTC_CREATED_DATE); //  '20160220',
    $ipct_time = $ipct_obj->get(IPTC_CREATED_TIME); //  '235834',
    $UNC_GALLERY['debug'][]["IPCT Dump"] = $ipct_obj->dump();
    if (strlen($ipct_date . $ipct_time) != 14) {
        return false;
    }
    $search_pattern = '/(\d\d\d\d)(\d\d)(\d\d) (\d\d)(\d\d)(\d\d)/';
    $replace_pattern = '$1-$2-$3 $4:$5:$6';
    $fixed_date = preg_replace($search_pattern, $replace_pattern, "$ipct_date $ipct_time");
    return $fixed_date;
}

/**
 * Write the IPCT date to an image
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @param type $date_str
 */
function unc_tools_image_ipct_date_write($file_path, $date_str) {
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
    // convert date_str to IPCT
    $search_pattern = '/(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)/';
    $date_pattern = '$1$2$3';
    $ipct_date = preg_replace($search_pattern, $date_pattern, $date_str);
    $time_pattern = '$4$5$6';
    $ipct_time = preg_replace($search_pattern, $time_pattern, $date_str);

    $UNC_GALLERY['debug'][]["wirting IPCT"] = "$ipct_date / $ipct_time";
    // write IPICT Date / time
    $taget_ipct_obj = new iptc($file_path);
    $taget_ipct_obj->set(IPTC_CREATED_DATE, $ipct_date);
    $taget_ipct_obj->set(IPTC_CREATED_TIME, $ipct_time);
    $taget_ipct_obj->write();
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
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
    $all_dates = unc_display_fix_timezones($dates);
    return $all_dates;
}

/**
 * Delete an image
 *
 * @global array $UNC_GALLERY
 */
function unc_tools_image_delete() {
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
    if (!is_admin()) {
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
        $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['thumbnails'] . DIRECTORY_SEPARATOR . $date_str . DIRECTORY_SEPARATOR . $file_name,
        $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'] . DIRECTORY_SEPARATOR . $date_str . DIRECTORY_SEPARATOR . $file_name,
    );
    foreach ($paths as $path) {
        if (file_exists($path)) {
            unlink($path);
        } else {
            echo "File name $path could not be found!";
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
    $date_obj = new DateTime($date . " 00:00:00");
    if ($date_obj) {
        $format = implode(DIRECTORY_SEPARATOR, array('Y', 'm', 'd'));
        $date_str = $date_obj->format($format);
        $photo_folder =  $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];
        if (!file_exists($photo_folder . DIRECTORY_SEPARATOR . $date_str)) {
            echo unc_tools_errormsg("Date not found (folder does not exist) $photo_folder/$date_str");
            return false;
        }
    } else {
        echo unc_tools_errormsg("Date not found (invalid date)");
        return false;
    }
    return $date_str;
}


define("IPTC_CREATED_DATE", "055");
define("IPTC_CREATED_TIME", "060");
/**
 * Class to write IPTC data to a file
 * Source: http://php.net/manual/en/function.iptcembed.php
 */
class iptc {
    var $meta=Array();
    var $hasmeta=false;
    var $file=false;

    function iptc($filename) {
        global $UNC_GALLERY;
        $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
        $info = false;
        getimagesize($filename, $info);
        $this->hasmeta = isset($info["APP13"]);
        if ($this->hasmeta) {
            $this->meta = iptcparse($info["APP13"]);
        }
        $this->file = $filename;
    }

    function set($tag, $data) {
        global $UNC_GALLERY;
        $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
        $this->meta ["2#$tag"]= Array( $data );
        $this->hasmeta=true;
    }

    function get($tag) {
        return isset($this->meta["2#$tag"]) ? $this->meta["2#$tag"][0] : false;
    }

    function dump() {
        return var_export($this->meta, true);
    }

    function binary() {
        $iptc_new = '';
        foreach (array_keys($this->meta) as $s) {
            $tag = str_replace("2#", "", $s);
            $iptc_new .= $this->iptc_maketag(2, $tag, $this->meta[$s][0]);
        }
        return $iptc_new;
    }

    function iptc_maketag($rec,$dat,$val) {
        $len = strlen($val);
        if ($len < 0x8000) {
            return chr(0x1c).chr($rec).chr($dat)
                . chr($len >> 8)
                . chr($len & 0xff)
                . $val;
        } else {
            return chr(0x1c).chr($rec).chr($dat)
                . chr(0x80).chr(0x04)
                . chr(($len >> 24) & 0xff)
                . chr(($len >> 16) & 0xff)
                . chr(($len >> 8 ) & 0xff)
                . chr(($len ) & 0xff)
                . $val;
        }
    }
    function write() {
        global $UNC_GALLERY;
        $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
        if(!function_exists('iptcembed')) {
            $UNC_GALLERY['debug'][]['iptcembed'] = "Does not exist!!";
            return false;
        }
        $mode = 0;
        $content = iptcembed($this->binary(), $this->file, $mode);
        $filename = $this->file;

        @unlink($filename); #delete if exists

        $fp = fopen($filename, "w");
        fwrite($fp, $content);
        fclose($fp);
    }

    #requires GD library installed
    function removeAllTags() {
        $this->hasmeta=false;
        $this->meta=Array();
        $img = imagecreatefromstring(implode(file($this->file)));
        @unlink($this->file); #delete if exists
        imagejpeg($img,$this->file,100);
    }
}
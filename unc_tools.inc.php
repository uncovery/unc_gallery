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
 * @param type $line_id
 */
function unc_tools_progress_update($process_name, $text, $percentage = false, $line_id = false) {
    global $UNC_GALLERY;

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
    $path_arr = array($UNC_GALLERY['photos'], $UNC_GALLERY['thumbnails']);
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
    global $wpdb, $UNC_GALLERY;

    if (!is_admin() === true) {
        return false;
    }

    $date_obj = new DateTime($date_str);
    if (!$date_obj) {
        return unc_display_errormsg("Invalid date folder!");
    }
    // convert date to folder string
    $fstr = "/";
    $out = "";
    $date_folder = date_format($date_obj, "Y{$fstr}m{$fstr}d");

    // delete images from DB
    $table_name = $wpdb->prefix . "unc_gallery_img";
    $sql = "DELETE FROM $table_name WHERE file_time LIKE '$date_str%';";

    $wpdb->get_results($sql);
    // now clean up all image info
    $sql_cleanup = 'SELECT * FROM `wp_unc_gallery_att`
        LEFT JOIN wp_unc_gallery_img ON file_id=id WHERE id = NULL';
    return;



    // we have 2 paths, images and thumbs
    $path_arr = array($UNC_GALLERY['photos'], $UNC_GALLERY['thumbnails']);

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
    return true;
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
 * Assemble a file description from EXIF & config values
 *
 * @param type $F
 */
function unc_tools_file_desc($F) {
    global $UNC_GALLERY;
    $out = '';
    $code_sets = array('exif', 'xmp', 'iptc', 'other');
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
                $data = $F[$set_name][$key];
            } else if (isset($F[$key])) {
                $data = $F[$key];
            } else {
                echo "<!-- $set_name / $key is not set! \n" . var_export($F, true) . " -->\n";
                continue;
            }
            
            // arrays should be exploded
            if (is_array($data)) {
                $text = implode(",&nbsp;", $data);
            } else {
                $text = $data;
            }
            
            // write the code. This could be improved for CSS
            $out .= "<b>$desc:</b>&nbsp;$text; ";

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
    global $TMP_FOLDERS;
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
    global $TMP_FOLDERS;
    if (strpos($base_folder, './')) {
        die("Error, recursive path! $base_folder");
    }
    $has_subfolder = false;
    if (!file_exists($base_folder)) {
        
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
    global $UNC_GALLERY;
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
    $val = trim($ini_val);
    $last = strtolower($val[strlen($val)-1]);
    $intval = intval($val);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $intval *= 1024000000;
        case 'm':
            $intval *= 1024000;
        case 'k':
            $intval *= 1024;
    }
    return $intval;
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
    $check = unc_image_info_delete($file_name, $date_wrong);
    if (!$check) {
        ob_clean();
        echo "File data could not be deleted: $file_name $date_wrong";
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
    if (!strstr($string, "/")) {
        return $string;
    }
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

/**
 * This checks what permissions we have on a folder
 * 
 * @param type $path
 */
function unc_tools_folder_access_check($path) {
    // first of all, let's trim the trailing /
    $last_letter = substr($path, -1);
    if ($last_letter == "/") {
        $path = substr($path, 0, -1);
    }
    $report = '<br>';
    // clear the cache to make sure we have the latest data:
    clearstatcache();
    // check if we have read perissions on the complete path:
    $check_read = is_readable($path);
    if (!$check_read) {
        $path_elements = explode("/", $path);
        $new_path = "";
        foreach ($path_elements as $folder) {
            if ($new_path == '/') {
                $new_path .= "$folder";
            } else {
                $new_path .= "/$folder";
            }
            $check_read_subfolder = is_readable($new_path);
            if ($check_read_subfolder) {
                $report .= "$new_path: OK!<br>";
            } else {
                $fileperms = unc_tools_fileperms_text($new_path);
                $report .= "$new_path: $fileperms<br>";
            }
        }
    } else {
         $report .= "$path: OK!<br>";
    }
    return $report;
}

function unc_tools_fileperms_text($folder) { 
    $perms = fileperms($folder);
    // return the actual numeric file permissions
    $num_perms = $perms;
    $fileperms_numeric = substr(sprintf('%o', $num_perms), -4);

    switch ($perms & 0xF000) {
        case 0xC000: // socket
            $info = 's';
            break;
        case 0xA000: // symbolic link
            $info = 'l';
            break;
        case 0x8000: // regular
            $info = 'r';
            break;
        case 0x6000: // block special
            $info = 'b';
            break;
        case 0x4000: // directory
            $info = 'd';
            break;
        case 0x2000: // character special
            $info = 'c';
            break;
        case 0x1000: // FIFO pipe
            $info = 'p';
            break;
        default: // unknown
            $info = 'u';
    }

    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
                (($perms & 0x0800) ? 's' : 'x' ) :
                (($perms & 0x0800) ? 'S' : '-'));

    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
                (($perms & 0x0400) ? 's' : 'x' ) :
                (($perms & 0x0400) ? 'S' : '-'));

    // World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
                (($perms & 0x0200) ? 't' : 'x' ) :
                (($perms & 0x0200) ? 'T' : '-'));

    return "$fileperms_numeric $info";
}


function unc_tools_debug_write() {
    global $UNC_GALLERY;
    
    $debug_setting = $UNC_GALLERY['debug'];
    
    switch($debug_setting) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'no':
            return;
        case 'yes':
            break;
    }
    
    if (count($UNC_GALLERY['debug_log']) == 0) {
        return;
    }
    
    $ip = filter_input(INPUT_SERVER, "REMOTE_ADDR");
    
    // HTML header for the error reports
    $msg_text = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
        <head>
            <title>DEBUG ERROR Report for IP ' . $ip . '</title>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        </head>
        <style type="text/css">
            * {padding: 0px; margin: 0px; font-family: arial; font-size: 12px;}
            div {border: 1px solid #00DDFF; margin: 10px; padding: 10px; background-color:#DDFFFF;}
            ol {margin-left:30px;}
            li {margin: 2px;padding: 2px;}
            h2, h1 {background-color:#00DDFF;padding: 5px;margin-bottom: 10px;}
            #footer {font-size:70%;padding: 3px;text-align:right;}
            .std_error ol {padding: 5px;background-color: white;}
        </style>
        <body>' . "\n";    
    
    $path = plugin_dir_path(__FILE__) . "logs";
    
    foreach ($UNC_GALLERY['debug_log'] as $title => $text) {
        $msg_text .= "<div class=\"data_block\"><h2>$title:</h2>\n" . unc_tools_array2text($text) . "</div>\n";
    }
    
    $today = unc_tools_microtime2string();
    $filename = $path . "/log_{$today}_$ip.html";
    $msg_text .= "\n    </body>\n</html>";
    file_put_contents($filename, $msg_text,  FILE_APPEND);
}

function unc_tools_debug_trace($type, $data = '') {
    global $UNC_GALLERY;
    if (is_array($type)) {
        $type = var_export($type, true);
    }
    $time = unc_tools_microtime2string();
    // we loop until we have a unset time
    if (isset($UNC_GALLERY['debug_log'][$time])) {
        unc_tools_debug_trace($type, $data);
    } else {
        $UNC_GALLERY['debug_log'][$time][$type] = $data;
        // error_log("UNC Gallery $type / $data");
    }
}


function unc_tools_microtime2string($microtime = false, $format = 'Y-m-d H-i-s-u') {
    if (!$microtime) {
        $microtime = microtime(true);
    }
    $date_obj = DateTime::createFromFormat('0.u00 U', microtime());
    // wordpress stores named & numbered timezones differently, see here:
    // https://wordpress.stackexchange.com/questions/8400/how-to-get-wordpress-time-zone-setting#8404
    $timezone = get_option('timezone_string');
    if ($timezone == '') {
        $offset = get_option('gmt_offset');
        $timezone = sprintf("%+'05g", $offset * 100) . "\n";
        // this returns a string like 2 or -2. need to convert to +0200 or -0200
    }
    $date_obj->setTimezone(new DateTimeZone($timezone));
   
    $time_str = $date_obj->format($format) . substr((string)$microtime, 1, 8);
    return $time_str;
}

function unc_tools_array2text($variable) {
    $string = '';

    switch(gettype($variable)) {
        case 'boolean':
            $string .= $variable ? 'true' : 'false';
            break;
        case 'integer':
        case 'double':
            $string .= $variable;
            break;
        case 'resource':
            $string .= '[Resource]';
            break;
        case 'NULL':
            $string .= "NULL";
            break;
        case 'unknown type':
            $string .= '??? (unkonwn var type)';
            break;
        case 'string':
            $string .= '"' . nl2br(htmlentities($variable), false) . '"';
            break;
        case 'object':
            $string .= nl2br(var_export($variable, true));
            break;
        case 'array':
            $string .= " <ol>\n";
            foreach ($variable as $key => $elem){
                $class = '';
                if (strstr($key, 'XMPP')) {
                    $class = "std_error";
                } else {
                    $class = "details";
                }
                $string .= "<li class=\"$class\"><span>$key</span> &rArr; ";
                if (count($elem) == 0) {
                    $elem_string = "array()</li>\n";
                } else {
                    $elem_string = unc_tools_array2text($elem) . "</li>\n";
                }
                $string .= $elem_string;
            }
            $string .= "</ol>\n";
            break;
    }

    return $string;
}
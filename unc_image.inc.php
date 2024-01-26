<?php
/**
 * This file hosts all the functions that deal with the EXIF/XMP/IPTC data of images
 */

if (!defined('WPINC')) {
    die;
}

global $UNC_GALLERY;

/**
 * Get the information of one image from file or database and return it
 *
 * @global type $UNC_GALLERY
 * @global array $UNC_FILE_DATA
 * @global type $wpdb
 * @param type $file_path
 * @return boolean
 */
function unc_image_info_read($file_path) {
    global $UNC_FILE_DATA, $wpdb;
    if (!file_exists($file_path)) {
        return false;
    }

    // lets get the file date from the folder structure
    $folder_info = pathinfo($file_path);
    $date_str = unc_tools_folder_date($folder_info['dirname']);
    $date_path = str_replace("-", "/", $date_str);
    $file_name = $folder_info['basename'];

    // check in the database if we have identical data (mathc wiht filename/date pair) there as well
    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $att_table_name = $wpdb->prefix . "unc_gallery_att";
    $sql = "SELECT `att_group`, `att_name`, `att_value`, $img_table_name.id FROM $img_table_name
        LEFT JOIN $att_table_name ON id=file_id
        WHERE file_name = '$file_name' AND file_time LIKE '$date_str%';";
    $file_data = $wpdb->get_results($sql);

    // if there is no data in the database, write the current file image to database
    if (count($file_data) == 0) {
        // write the image data to the datbase and to the $UNC_FILE_DATA array
        $check = unc_image_info_write($file_path);
        if (!$check) {

        }
        $file_code = md5($date_path . "/" . $file_name . ".php");
        // we are done writing the database, return the image data as set by the unc_image_info_write() function
        return $UNC_FILE_DATA[$file_code];
    }

    // if we found the data in the DB, we iterate it and set the $UNC_FILE_DATA array and return it as well
    $F = array();

    foreach ($file_data as $D) {
        $F['file_id'] = $D->id;
        $field = $D->att_name;
        $group = $D->att_group;
        $value = $D->att_value;
        if ($group == 'default') {
            if (isset($F[$field])) {
                if (is_array($F[$field])) {
                    $F[$field][] = $value;
                } else {
                    $F[$field] = array($F[$field], $value);
                }
            } else {
                $F[$field] = $value;
            }
        } else {
            if (isset($F[$group][$field])) {
                if (is_array($F[$group][$field])) {
                    $F[$group][$field][] = $value;
                } else {
                    $F[$group][$field] = array($F[$group][$field], $value);
                }
            } else {
                $F[$group][$field] = $value;
            }
        }
    }

    if (count($F) == 0) {
        return false;
    }

    $file_code = md5($date_path . "/" . $file_name . ".php");
    $UNC_FILE_DATA[$file_code] = $F;
    return $F;
}

/**
 * On upload, get all information from a file and write it to database
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @return boolean
 */
function unc_image_info_write($file_path) {
    global $UNC_GALLERY, $UNC_FILE_DATA, $wpdb;
    if (!file_exists($file_path)) {
        return false;
    }

    $all_data = unc_image_info_exiftool($file_path);
    if (!$all_data) {
        return false;
    }
    $exif_raw = $all_data['exif'];
    $xmp_raw = $all_data['xmp'];
    $iptc_raw = $all_data['iptc'];

    // fix all the data sets
    $exif = unc_exif_fix($exif_raw);
    $xmp = unc_xmp_fix($xmp_raw);
    $iptc = unc_iptc_fix($iptc_raw);

    // error_log(var_export($exif, true));
    // error_log(var_export($iptc, true));
    // error_log(var_export($iptc, true));

    if (!isset($exif['created'])) {
        $file_date = unc_iptc_convert_date($iptc['created_date'], $iptc['created_time']);
    } else {
        $file_date = $exif['created'];
    }

    $dtime = DateTime::createFromFormat("Y-m-d G:i:s", $file_date);
    if (!$dtime || is_null($dtime)) {
        error_log("Eror creating Timestamp from File date: $file_date, doe not match format");
    }

    $time_stamp = $dtime->getTimestamp(); // time stamp is easier to compare
    $folder_info = pathinfo($file_path);
    $date_str = unc_tools_folder_date($folder_info['dirname']);
    $date_path = str_replace("-", "/", $date_str);
    $file_name = $folder_info['basename'];

    $orientation = 'landscape';
    if ($exif['file_width'] < $exif['file_height']) {
        $orientation = 'portrait';
    }

    // remove existing file info
    $check = unc_image_info_delete($file_name, $file_date);
    if (!$check) {
        error_log("Error deleting old file $file_name, $file_date");
    }

    $photo_url = content_url($UNC_GALLERY['upload_folder'] . "/" . $UNC_GALLERY['photos'] . "/$date_path/$file_name");
    $thumb_url = content_url($UNC_GALLERY['upload_folder'] . "/" . $UNC_GALLERY['thumbnails'] . "/$date_path/$file_name");

    $file_code = md5($date_path . "/" . $file_name . ".php");
    // insert file into DB
    $wpdb->insert(
        $wpdb->prefix . "unc_gallery_img",
        array(
            'file_time' => $file_date,
            'file_name' => $file_name,
            'file_path' => $file_path,
        )
    );

    $insert_id = $wpdb->insert_id;
    if ($insert_id == 0) {
        error_log("Could not insert image into gallery DB: $file_path");
        return false;
    }

    // we have some base data that we dont consider EXIT/XMP/etc
    $default = array(
        'data_version' => $UNC_GALLERY['data_version'],
        'file_name' => $file_name,
        'file_path' => $file_path,
        'thumb_url' => $thumb_url,
        'file_url' => $photo_url,
        'time_stamp' => $time_stamp, // linux time stamp
        'file_date' => $file_date, // full date including time
        'date_str' => substr($file_date, 0, 10), // only the day 0000-00-00
        'orientation' => $orientation,
        'permalink' => "<a href=\'$photo_url\'>click here</a>",
        'dimensions' => $exif['file_width'] . "px/" . $exif['file_height'] . "px",
    );
    $data_sets = array(
        'default' => $default,
        'exif' => $exif,
        'xmp' => $xmp,
        'iptc' => $iptc,
    );

    $keywords = '';
    $location = '';
    foreach ($data_sets as $set_name => $set) {
        foreach ($set as $name => $value) {
            // insert into DB
            if (is_array($value)) {
                // TODO: Create a single SQL line instead of entering every line by itself
                // should make the process much faster
                foreach ($value as $arr_value) {
                    $data_arr = array(
                        'file_id' => $insert_id,
                        'att_group' => $set_name,
                        'att_name' => $name,
                        'att_value' => $arr_value,
                        // get some values for the import status update
                    );
                    if ($name =='keywords') {
                        $keywords = $arr_value;
                    } else if ($name =='location') {
                        $location = $arr_value;
                    }
                    $wpdb->insert(
                        $wpdb->prefix . "unc_gallery_att",
                        $data_arr
                    );
                    $insert_id2 = $wpdb->insert_id;
                    if ($insert_id2 == 0) {
                        error_log("Could not insert image attributes arrays into gallery DB: $file_path");
                        return false;
                    }
                }
            } else {
                $data_arr = array(
                    'file_id' => $insert_id,
                    'att_group' => $set_name,
                    'att_name' => $name,
                    'att_value' => $value,
                );
                $wpdb->insert(
                    $wpdb->prefix . "unc_gallery_att",
                    $data_arr
                );
                $insert_id2 = $wpdb->insert_id;
                if ($insert_id2 == 0) {
                    error_log("Could not insert image attributes value into gallery DB: $file_path");
                    error_log(var_export($data_arr, true));
                    return false;
                }
                // TODO remove the REPLACE here since it's labor intensive.
                // rather use ON DUPLICATE KEY UPDATE
                // https://stackoverflow.com/questions/2366813/on-duplicate-key-ignore#4920619
                if ($insert_id2 == 0) {
                    $wpdb->replace(
                        $wpdb->prefix . "unc_gallery_att",
                        $data_arr
                    );
                    $replace_id = $wpdb->insert_id;
                    if ($replace_id == 0) {
                        error_log("Could not replace image attributes data into gallery DB: $file_path");
                        error_log(var_export($data_arr, true));
                        return false;
                    }
                }
            }
            // insert into global var
            // we split it like this since that was the legacy format.
            if ($set_name == 'default') {
                $UNC_FILE_DATA[$file_code][$name] = $value;
            } else {
                $UNC_FILE_DATA[$file_code][$set_name][$name] = $value;
            }
        }
    }
    return array('keywords' => $keywords, 'location' => $location);
}

/**
 * User EXIFTOOL (Perl) to get information from the file
 *
 * @global array $UNC_GALLERY
 * @param type $file_path
 * @return type
 */
function unc_image_info_exiftool($file_path) {
    global $UNC_GALLERY;

    $output = '';
    $command = 'exiftool -s -g -a -json -struct -xmp:all -iptc:all -exif:all -file:all "'.$file_path.'"'; //  -composite:all
    exec($command, $output);

    // the result are many lines of json, so let's combine it into one string
    $metadata_json = implode('', $output);
    // now let's decode the json into an array
    $metadata_array = json_decode($metadata_json, true);
    if (is_null($metadata_array)) { // json error, exiftool does not exist etc.
        return false;
    }

    $D = $metadata_array[0];

    // copy "FILE" section into Exif
    foreach ($D['File'] as $key => $value) {
        $D['EXIF'][$key] = $value;
    }

    // the data is grouped by sub-arrays:
    $data_sets = array('exif' => 'EXIF', 'xmp' => 'XMP', 'iptc' => 'IPTC');
    // iterate data types
    $file_data = array();
    foreach ($data_sets as $our_set => $json_set) {
        // let's iterate our codes and see if they exist in the JSON
        foreach ($UNC_GALLERY['codes'][$our_set] as $code_key => $code_setting) {
            // we check our code contains a key and if it is 'true'
            if (isset($code_setting['key']) && $code_setting['key']) {
                // then check if it's in the JSON
                $key = $code_setting['key'];
                if (is_array($key)) { // for key arrays we just get the individual elements and assemble the stuff in the _fix function
                    foreach ($key as $key_name => $key_value) {
                        if (!isset($D[$json_set][$key_name])) { // we only get this if all sub-tags exist
                            continue 2; // continue to the next file in the outer loop
                        }
                        $file_data[$our_set][$key_name] = $D[$json_set][$key_name];
                    }
                } else if (isset($D[$json_set][$key])) {
                    $file_data[$our_set][$key] = $D[$json_set][$key];
                }
            }
        }
    }
    return $file_data;
}

/**
 * collects the file IDs of the images to be deleted and runs the deleting command
 *
 * @global type $wpdb
 * @global array $UNC_GALLERY
 * @param type $file_name
 * @param type $file_date
 * @return boolean
 */
function unc_image_info_delete($file_name, $file_date) {
    global $wpdb;
    // remove existing file info

    $file_date_sql = esc_sql($file_date);
    $file_name_sql = esc_sql($file_name);
    $sql = "SELECT id FROM " . $wpdb->prefix . "unc_gallery_img WHERE file_time LIKE '$file_date_sql%' AND file_name='$file_name_sql';";
    $check_data =  $wpdb->get_results($sql, 'ARRAY_A');

    // we count first so that we can send back a false in case we don't find any files
    if (count($check_data) > 0) {
        foreach ($check_data as $row) {
            unc_tools_delete_image_data($row['id']);
        }
        return true;
    } else {
        return false;
    }
}


/**
 * Get the date of an image, first EXIF, then IPTC
 * This function is only used when uploading images.
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @return boolean
 */
function unc_image_date($file_path) {
    $exif = unc_exif_date($file_path);
    if (is_null($exif)) {

        $iptc = unc_iptc_date($file_path);
        if ($iptc) {
            return $iptc;
        } else {

            return false;
        }
    } else if (!$exif) {

        return false;
    } else {
        return $exif;
    }
}


/**
 * assemble an array of the ; key=>descriptions
 * of exif codes that are used in the config
 *
 * @global array $var
 * @return type
 */
function unc_image_options_array($var) {
    global $UNC_GALLERY;
    $out = array();
    $set = $UNC_GALLERY['codes'][$var];
    foreach ($set as $key => $D) {
        $out[$key] = $D['description'];
    }
    return $out;
}

function umc_image_id2path($id) {
    global $wpdb;

    // safety convert to int
    $int_id = intval($id);

    $img_table = $wpdb->prefix . "unc_gallery_img";
    $sql = "SELECT file_path FROM $img_table WHERE id=$int_id;";
    $file_path = $wpdb->get_var($sql);

    if ($file_path) {
        return $file_path;
    } else {
        return false;
    }
}

/**
 * checck if a file exists in the database, otherwise fail
 * @global type $wpdb
 * @param type $path
 * @return bool
 */
function umc_image_exists_in_img_table($path) {
    global $wpdb;

    $img_table = $wpdb->prefix . "unc_gallery_img";
    $sql = "SELECT count(file_path) FROM $img_table WHERE file_path='$path';";
    $file_path = $wpdb->get_var($sql);

    if ($file_path) {
        return true;
    } else {
        return false;
    }
}
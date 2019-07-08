<?php

if (!defined('WPINC')) {
    die;
}

global $UNC_GALLERY;
// detailed info on EXIF Codes
// http://www.exiv2.org/tags.html
$UNC_GALLERY['codes']['exif'] = array(
    'file_height' => array(
        'hex' => false,
        'key' => 'file_height',
        'conversion' => false,
        'unit' => false,
        'description' => 'File Height',
        'type' => 'integer',
    ),
    'file_width' => array(
        'hex' => false,
        'key' => 'file_width',
        'conversion' => false,
        'unit' => false,
        'description' => 'File Width',
        'type' => 'integer',
    ),        
    'camera_manuf' => array(
        'hex' => '0x010F',
        'key' => 'Make',
        'conversion' => false,
        'unit' => false,
        'description' => 'Make',
        'type' => 'text',
    ),
    'camera_model' => array(
        'hex' => '0x0110',
        'key' => 'Model',
        'conversion' => false,
        'unit' => false,
        'description' => 'Model',
        'type' => 'text',
    ),
    'exposure_time' => array(
        'hex' => '0x829A',
        'key' => 'ExposureTime',
        'conversion' => false,
        'unit' => 'sec.',
        'description' => 'Exposure Time',
        'type' => 'text',
    ),
    'f' => array(
        'hex' => '0x829D',
        'key' => 'FNumber',
        'conversion' => 'unc_tools_divide_string',
        'unit' => false,
        'description' => 'F-Stop',
        'type' => 'text',
    ),
    'iso' => array(
        'hex' => '0x8827',
        'key' => 'ISOSpeedRatings',
        'conversion' => false,
        'unit' => false,
        'description' => 'ISO',
        'alternates' => array('ISO'),
        'type' => 'text',
    ),
    'focal_length' => array(
        'hex' => '0x920A',
        'key' => 'FocalLength',
        'conversion' => 'unc_tools_divide_string',
        'unit' => 'mm',
        'description' => 'Focal Length',
        'type' => 'text',
    ),
    'lens' => array(
        'hex' => '0xA434',
        'key' => 'LensModel',
        'conversion' => false,
        'unit' => false,
        'description' => 'Lens',
        'type' => 'text',
    ),
    'created' => array(
        'hex' => '0x9003',
        'key' => 'DateTimeOriginal',
        'conversion' => 'unc_exif_convert_date',
        'unit' => false,
        'description' => 'Created',
        'type' => 'date',
    ),
    'gps_lat' => array(
        'hex' => false,
        'key' => array('GPSLatitudeRef' => 'Hemisphere', 'GPSLatitude' => 'Coordinates', 'GPSVersion' => 'Version'),
        'conversion' => 'unc_exif_convert_gps',
        'unit' => false,
        'description' => 'GPS Latitude',
        'type' => 'integer',
    ),
    'gps_lon' => array(
        'hex' => false,
        'key' => array('GPSLongitudeRef' => 'Hemisphere', 'GPSLongitude' => 'Coordinates', 'GPSVersion' => 'Version'),
        'conversion' => 'unc_exif_convert_gps',
        'unit' => false,
        'description' => 'GPS Longitude',
        'type' => 'integer',
    ),
    'gps' => array(
        'hex' => false,
        'key' => array('GPSLatitudeRef' => 'GPSLatitudeRef', 'GPSLatitude' =>'GPSLatitude', 'GPSLongitudeRef' => 'GPSLongitudeRef', 'GPSLongitude' => 'GPSLongitude', 'GPSVersion' => 'Version'),
        'conversion' => 'unc_exif_convert_gps_combo',
        'unit' => false,
        'description' => 'GPS Coordinates',
        'type' => 'integer',
    ),
    'gps_link' => array(
        'hex' => false,
        'key' => array('GPSLatitudeRef' => 'GPSLatitudeRef', 'GPSLatitude' =>'GPSLatitude', 'GPSLongitudeRef' => 'GPSLongitudeRef', 'GPSLongitude' => 'GPSLongitude', 'GPSVersion' => 'Version'),
        'conversion' => 'unc_exif_convert_gps_link',
        'unit' => false,
        'description' => 'Map Link',
        'type' => 'integer',
    )
);


/**
 * Get data from the EXIF values, convert it
 *
 * @param type $image_path
 * @return string
 */
function unc_exif_get($image_path) {
    global $UNC_GALLERY;
    // we need to apply a custom error handler to catch 'illegal IFD size' errors
    set_error_handler('unc_exif_catch_errors', E_WARNING);
    // we are setting this ariable to have a bridge into the error handling function
    $exif = exif_read_data($image_path);

    restore_error_handler();

    $exif['file_width'] = $exif['COMPUTED']['Width'];
    $exif['file_height'] = $exif['COMPUTED']['Height'];

    return $exif;
}

/**
 * Perform our proprietary changes to the EXIF Data
 *
 * @global array $UNC_GALLERY
 * @param type $exif
 * @return type
 */
function unc_exif_fix($exif) {
    global $UNC_GALLERY;

    $data = array();
    // we only take the EXIF data we need
    foreach ($UNC_GALLERY['codes']['exif'] as $code => $C) {
        $hex_tag =  'UndefinedTag:' . $C['hex'];
        if (is_array($C['key'])) { // gps for example is made out of multiple keys
            $val = array();
            foreach ($C['key'] as $key_name => $key_value) {
                if (!isset($exif[$key_name])) { // we only get this if all sub-tags exist
                    continue 2; // continue to the next file in the outer loop
                }
                $val[$key_value] = $exif[$key_name];
            }
        } else if (isset($exif[$C['key']])) {
            $val = $exif[$C['key']];
        } else if (isset($exif[$hex_tag])) { // lens model for example is stored as an undefined key
            $val = $exif[$hex_tag];
        } else {
            // value is not set
            continue;
        }
        // run the conversion function
        if ($C['conversion']) {
            $func = $C['conversion'];
            $val_conv = $func($val);
        } else {
            $val_conv = $val;
        }
        if ($C['unit']) {
            $val_conv .= $C['unit'];
        }
        $data[$code] = $val_conv;
    }
    return $data;
}

/**
 * Creates an HTML link to google maps coordinates from
 * EXIF GPS Data
 *
 * @param type $gps_arr
 * @return type
 */
function unc_exif_convert_gps_link($gps_arr) {
    global $UNC_GALLERY;
    $str = unc_exif_convert_gps_combo($gps_arr);
    $link = "<a href='http://www.google.com/maps/place/$str' target='_blank'>Link</a>";
    return $link;
}


function unc_exif_convert_gps_combo($gps_arr) {
    global $UNC_GALLERY;
    // 'GPSLatitudeRef', 'GPSLatitude', 'GPSLongitudeRef', 'GPSLongitude'
    $lat_coords = unc_exif_convert_gps(array('Hemisphere' => $gps_arr['GPSLatitudeRef'], 'Coordinates' => $gps_arr['GPSLatitude'], 'Version' => $gps_arr['Version']));
    $lon_coords = unc_exif_convert_gps(array('Hemisphere' => $gps_arr['GPSLongitudeRef'], 'Coordinates' => $gps_arr['GPSLongitude'], 'Version' => $gps_arr['Version']));
    return "$lat_coords,$lon_coords";
}

/**
 * Converts EXIF GPS Data to decimal coordinates
 *
 * @global array $UNC_GALLERY
 * @param type $gps_arr
 * @return type
 */
function unc_exif_convert_gps($gps_arr) {
    global $UNC_GALLERY;
    // $gps array elelemt 1 is the reference, 0 is the coordinate
    if (!isset($gps_arr['Coordinates'])) {

    }
    if (!isset($gps_arr['Hemisphere'])) {

    }

    if (!isset($gps_arr['Version'])) {

        return;
    }

    $coord = $gps_arr['Coordinates'];
    $hemi = $gps_arr['Hemisphere'];
    $gps_version = $gps_arr['Version'];

    if ($gps_arr['Version'] == '2.2.0.0') {
        $matches = false;
        preg_match("/(?'degree'[0-9]*) deg (?'minutes'[0-9]*)' (?'seconds'[\.0-9]*)\"/", $coord, $matches, PREG_OFFSET_CAPTURE);
        $degrees = $matches['degree'][0];
        $minutes = $matches['minutes'][0];
        $seconds =  $matches['seconds'][0];
    } else {
        $degrees = count($coord) > 0 ? unc_exif_convert_gps_2_Num($coord[0]) : 0;
        $minutes = count($coord) > 1 ? unc_exif_convert_gps_2_Num($coord[1]) : 0;
        $seconds = count($coord) > 2 ? unc_exif_convert_gps_2_Num($coord[2]) : 0;
    }

    $flip = (in_array($hemi, array('W', 'West')) || in_array($hemi, array('S', 'South'))) ? -1 : 1;
    $out = $flip * ($degrees + $minutes / 60 + $seconds / 3600);

    return $out;
}

/**
 * Helper function for unc_exif_convert_gps()
 *
 * @global array $UNC_GALLERY
 * @param type $coordPart
 * @return int
 */
function unc_exif_convert_gps_2_Num($coordPart) {
    global $UNC_GALLERY;
    $parts = explode('/', $coordPart);

    if (count($parts) <= 0) {
        return 0;
    }

    if (count($parts) == 1) {
        return $parts[0];
    }

    return floatval($parts[0]) / floatval($parts[1]);
}

/**
 * Get the EXIF date of a file based on date & filename only
 *
 * @global type $UNC_GALLERY
 * @param type $date_path
 * @param type $file_name
 * @return type
 */
function unc_exif_date($file_path) {
    global $UNC_GALLERY;

    set_error_handler('unc_exif_catch_errors', E_WARNING);
    $exif_data = exif_read_data($file_path);
    restore_error_handler();

    // did the EXIF data return an error?
    if (isset($UNC_GALLERY['errors'][$file_path])) {
        return false;
    }

    if (!$exif_data || !isset($exif_data['DateTimeOriginal'])) {
        return NULL;
    }
    $file_date = unc_exif_convert_date($exif_data['DateTimeOriginal']);
    return $file_date;
}

/**
 * Take an EXIF date format and convert it to a date / time string
 *
 * @param type $date
 * @return type
 */
function unc_exif_convert_date($date) {
    global $UNC_GALLERY;
    $search_pattern = '/(\d\d\d\d):(\d\d):(\d\d \d\d:\d\d:\d\d)/';
    $replace_pattern = '$1-$2-$3';
    $fixed_date = preg_replace($search_pattern, $replace_pattern, $date);
    return $fixed_date;
}


/**
 * catch warnings during EXIF file data retreival so we know which
 * files have issues and can report back to the user.
 *
 * @global array $UNC_GALLERY
 * @param type $errno
 * @param type $errstr
 * @param type $errfile
 * @param type $errline
 */
function unc_exif_catch_errors($errno, $errstr, $errfile, $errline) {
    global $UNC_GALLERY;
    $UNC_GALLERY['errors'][] = array('error' => $errstr, 'filename' => $errfile);
}



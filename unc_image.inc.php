<?php
/**
 * This file hosts all the runctions that deal with the EXIF/XMP/IPTC data of images
 */

if (!defined('WPINC')) {
    die;
}

global $UNC_GALLERY;
// detailed info on EXIF Codes
// http://www.exiv2.org/tags.html
$UNC_GALLERY['codes']['exif'] = array(
    'file_name' => array(
        'hex' => false,
        'key' => false,
        'conversion' => false,
        'unit' => false,
        'description' => 'File Name',
    ),
    'file_height' => array(
        'hex' => false,
        'key' => 'file_height',
        'conversion' => false,
        'unit' => false,
        'description' => 'File Height',
    ),
    'file_width' => array(
        'hex' => false,
        'key' => 'file_width',
        'conversion' => false,
        'unit' => false,
        'description' => 'File Width',
    ),
    'camera_manuf' => array(
        'hex' => '0x010F',
        'key' => 'Make',
        'conversion' => false,
        'unit' => false,
        'description' => 'Make',
    ),
    'camera_model' => array(
        'hex' => '0x0110',
        'key' => 'Model',
        'conversion' => false,
        'unit' => false,
        'description' => 'Model',
    ),
    'exposure_time' => array(
        'hex' => '0x829A',
        'key' => 'ExposureTime',
        'conversion' => false,
        'unit' => 'sec.',
        'description' => 'Exposure Time',
    ),
    'f' => array(
        'hex' => '0x829D',
        'key' => 'FNumber',
        'conversion' => 'unc_tools_divide_string',
        'unit' => false,
        'description' => 'F-Stop',
    ),
    'iso' => array(
        'hex' => '0x8827',
        'key' => 'ISOSpeedRatings',
        'conversion' => false,
        'unit' => false,
        'description' => 'ISO',
    ),
    'focal_length' => array(
        'hex' => '0x920A',
        'key' => 'FocalLength',
        'conversion' => 'unc_tools_divide_string',
        'unit' => 'mm',
        'description' => 'Focal Length',
    ),
    'lens' => array(
        'hex' => '0xA434',
        'key' => 'LensModel',
        'conversion' => false,
        'unit' => false,
        'description' => 'Lens',
    ),
    'created' => array(
        'hex' => '0x9003',
        'key' => 'DateTimeOriginal',
        'conversion' => 'unc_exif_convert_date',
        'unit' => false,
        'description' => 'Created',
    ),
    'gps_lat' => array(
        'hex' => false,
        'key' => array('GPSLatitudeRef' => 'Hemisphere', 'GPSLatitude' => 'Coordinates'),
        'conversion' => 'unc_exif_convert_gps',
        'unit' => false,
        'description' => 'GPS Latitude',
    ),
    'gps_lon' => array(
        'hex' => false,
        'key' => array('GPSLongitudeRef' => 'Hemisphere', 'GPSLongitude' => 'Coordinates'),
        'conversion' => 'unc_exif_convert_gps',
        'unit' => false,
        'description' => 'GPS Longitude',
    ),
    'gps' => array(
        'hex' => false,
        'key' => array('GPSLatitudeRef' => 'GPSLatitudeRef', 'GPSLatitude' =>'GPSLatitude', 'GPSLongitudeRef' => 'GPSLongitudeRef', 'GPSLongitude' => 'GPSLongitude'),
        'conversion' => 'unc_exif_convert_gps_combo',
        'unit' => false,
        'description' => 'GPS Coordinates',
    ),
    'gps_link' => array(
        'hex' => false,
        'key' => array('GPSLatitudeRef' => 'GPSLatitudeRef', 'GPSLatitude' =>'GPSLatitude', 'GPSLongitudeRef' => 'GPSLongitudeRef', 'GPSLongitude' => 'GPSLongitude'),
        'conversion' => 'unc_exif_convert_gps_link',
        'unit' => false,
        'description' => 'Map',
    )
);

// https://surniaulula.com/2013/04/09/read-adobe-xmp-xml-in-php/
$UNC_GALLERY['codes']['xmp'] = array(
    'email' => array(
        'description' => 'Creator Email',
        'regex' => '<Iptc4xmpCore:CreatorContactInfo[^>]+?CiEmailWork="([^"]*)"',
        'key' => array("CreatorContactInfo" => "CiEmailWork"),
    ),
    'name' => array(
        'description' => 'Owner Name',
        'regex' => '<rdf:Description[^>]+?aux:OwnerName="([^"]*)"',
    ),
    'creation_date' => array(
        'description' => 'Creation Date',
        'regex' => '<rdf:Description[^>]+?xmp:CreateDate="([^"]*)"',
        'key' => 'CreateDate',
    ),
    'modification_date' => array(
        'description' => 'Modification Date',
        'regex' => '<rdf:Description[^>]+?xmp:ModifyDate="([^"]*)"',
        'key' => 'ModifyDate',
    ),
    'label' => array(
        'description' => 'Label',
        'regex' => '<rdf:Description[^>]+?xmp:Label="([^"]*)"',
        'key' => 'Label',
    ),
    'credit' => array(
        'description' => 'Credit',
        'regex' => '<rdf:Description[^>]+?photoshop:Credit="([^"]*)"',
        'key' => 'Credit',
    ),
    'source' => array(
        'description' => 'Source',
        'regex' => '<rdf:Description[^>]+?photoshop:Source="([^"]*)"',
        'key' => 'Source',
    ),
    'headline' => array(
        'description' => 'Headline',
        'regex' => '<rdf:Description[^>]+?photoshop:Headline="([^"]*)"',
        'key' => 'Headline',
    ),
    'city' => array(
        'description' => 'City',
        'regex' => '<rdf:Description[^>]+?photoshop:City="([^"]*)"',
        'key' => 'City',
    ),
    'state' => array(
        'description' => 'State',
        'regex' => '<rdf:Description[^>]+?photoshop:State="([^"]*)"',
        'key' => 'State',
    ),
    'country' => array(
        'description' => 'Country',
        'regex' => '<rdf:Description[^>]+?photoshop:Country="([^"]*)"',
        'key' => 'Country',
    ),
    'country_code' => array(
        'description' => 'Country Code',
        'regex' => '<rdf:Description[^>]+?Iptc4xmpCore:CountryCode="([^"]*)"',
        'key' => 'CountryCode',
    ),
    'location' => array(
        'description' => 'Location',
        'regex' => '<rdf:Description[^>]+?Iptc4xmpCore:Location="([^"]*)"',
        'key' => 'Location',
    ),
    'title' => array(
        'description' => 'Title',
        'regex' => '<dc:title>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:title>',
        'key' => 'Title',
    ),
    'description' => array(
        'description' => 'Description',
        'regex' => '<dc:description>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:description>',
        'key' => 'Description',
    ),
    'creator' => array(
        'description' => 'Creator',
        'regex' => '<dc:creator>\s*<rdf:Seq>\s*(.*?)\s*<\/rdf:Seq>\s*<\/dc:creator>',
        'key' => 'Creator',
    ),
    'keywords' => array(
        'description' => 'Keywords',
        'regex' => '<dc:subject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/dc:subject>',
        'key' => 'Subject',
    ),
    'hierarchicalh_keywords' => array(
        'description' => 'Hierarchical Keywords',
        'regex' => '<lr:hierarchicalSubject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/lr:hierarchicalSubject>',
        'key' => 'HierarchicalSubject',
    ),
);

$UNC_GALLERY['codes']['iptc'] = array(
    // full spec of codes: http://www.iptc.org/std/IIM/4.1/specification/IIMV4.1.pdf
    'object_name' => array('code' => '005', 'description' => 'Object Name', 'key' => false),
    'edit_status' => array('code' => '007', 'description' => 'Edit Ststus', 'key' => false),
    'priority' => array('code' => '010', 'description' => 'Priority', 'key' => false),
    'category' => array('code' => '015', 'description' => 'Category', 'key' => false),
    'supplemental_category' => array('code' => '020', 'description' => 'Supplemental Category', 'key' => false),
    'fixture_identifier' => array('code' => '022', 'description' => 'Fixture Identifier', 'key' => false),
    'keywords' => array('code' => '025', 'description' => 'Keywords', 'key' => 'Keywords'),
    'release_date' => array('code' => '030', 'description' => 'Release Date', 'key' => false),
    'release_time' => array('code' => '035', 'description' => 'Release Time', 'key' => false),
    'special_instructions' => array('code' => '040', 'description' => 'Special Instructions', 'key' => false),
    'reference_service' => array('code' => '045', 'description' => 'Reference Service', 'key' => false),
    'reference_date' => array('code' => '047', 'description' => 'Reference Date', 'key' => false),
    'reference_number' => array('code' => '050', 'description' => 'Reference Number', 'key' => false),
    'created_date' => array('code' => '055', 'description' => 'Created Date', 'key' => 'DateCreated'),
    'created_time' => array('code' => '060', 'description' => 'Created Time', 'key' => 'TimeCreated'),
    'originating_program' => array('code' => '065', 'description' => 'Originating Program', 'key' => false),
    'program_version' => array('code' => '070', 'description' => 'Program Version', 'key' => false),
    'object_cycle' => array('code' => '075', 'description' => 'Object Cycle', 'key' => false),
    'byline' => array('code' => '080', 'description' => 'Byline', 'key' => 'By-line'),
    'byline_title' => array('code' => '085', 'description' => 'Byline Title', 'key' => false),
    'city' => array('code' => '090', 'description' => 'City', 'key' => 'City'),
    'sublocation' => array('code' => '092', 'description' => 'Location', 'key' => 'Sub-location'),
    'province_state' => array('code' => '095', 'description' => 'State', 'key' => 'Province-State'),
    'country_code' => array('code' => '100', 'description' => 'Country Code', 'key' => false),
    'country' => array('code' => '101', 'description' => 'Country', 'key' => 'Country-PrimaryLocationName'),
    'original_transmission_reference' => array('code' => '103', 'description' => 'Original Transmission Reference', 'key' => false),
    'headline' => array('code' => '105', 'description' => 'Headline', 'key' => false),
    'credit' => array('code' => '110', 'description' => 'Credit', 'key' => false),
    'source' => array('code' => '115', 'description' => 'Source', 'key' => false),
    'copyright_string' => array('code' => '116', 'description' => 'Copyright String', 'key' => false),
    'caption' => array('code' => '120', 'description' => 'Caption', 'key' => false),
    'local_caption' => array('code' => '100', 'description' => 'Local Caption', 'key' => false),
);

function unc_image_info_read($file_path) {
    global $UNC_GALLERY, $UNC_FILE_DATA, $wpdb;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, $file_path);}

    if (!file_exists($file_path)) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trigger("tried to read info for non-existing file!");}
        return false;
    }

    $folder_info = pathinfo($file_path);
    $date_str = unc_tools_folder_date($folder_info['dirname']);
    $date_path = str_replace("-", "/", $date_str);
    $file_name = $folder_info['basename'];

    $img_table_name = $wpdb->prefix . "unc_gallery_img";
    $att_table_name = $wpdb->prefix . "unc_gallery_att";
    $sql = "SELECT `att_group`, `att_name`, `att_value` FROM $img_table_name
        LEFT JOIN $att_table_name ON id=file_id
        WHERE file_name = '$file_name' AND file_time LIKE '$date_str%';";
    $file_data = $wpdb->get_results($sql);

    // TODO: check if the file exists 2x for sanity check, here or somewhere else

    if (count($file_data) == 0) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("File not found in DB, reading from file");}
        $check = unc_image_info_write($file_path);
        if (!$check) {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trigger("could not write file data to database!");}
        }
        $file_code = md5($date_path . "/" . $file_name . ".php");
        return $UNC_FILE_DATA[$file_code];
    }

    $F = array();
    foreach ($file_data as $D) {
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
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trigger("did not read any information from file!");}
    }

    $file_code = md5($date_path . "/" . $file_name . ".php");
    $UNC_FILE_DATA[$file_code] = $F;
    return $F;
}

/**
 * On upload, get all information from a file and write it to a PHP file
 * TODO: Move this to MySQL / SQLite
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @return boolean
 */
function unc_image_info_write($file_path) {
    global $UNC_GALLERY, $UNC_FILE_DATA, $wpdb;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    if (!file_exists($file_path)) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trigger("tried to write info for non-existing file!", $file_path);}
        return false;
    }

    if ($UNC_GALLERY['image_data_method'] == 'exiftool') {
        $all_data = unc_image_info_exiftool($file_path);
        if (!$all_data) {
            return false;
        }
        $exif_raw = $all_data['exif'];
        $xmp_raw = $all_data['xmp'];
        $iptc_raw = $all_data['iptc'];
    } else {
        $exif_raw = unc_exif_get($file_path);
        $xmp_raw = unc_xmp_get($file_path);
        $iptc_raw = unc_iptc_get($file_path);
    }

    XMPP_ERROR_trigger("test exiftool");

    // fix all the data sets
    $exif = unc_exif_fix($exif_raw);
    $xmp = unc_xmp_fix($xmp_raw);
    $iptc = unc_iptc_fix($iptc_raw);

    XMPP_ERROR_trace("exif final", $exif);

    if (!isset($exif['created'])) {
        $file_date = unc_iptc_convert_date($iptc['created_date'], $iptc['created_time']);
    } else {
        $file_date = $exif['created'];
    }

    XMPP_ERROR_trace("next step, file date: ", $file_date);

    $dtime = DateTime::createFromFormat("Y-m-d G:i:s", $file_date);
    if (!$dtime) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("Daet could not be converted", $file_date);}
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
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("Data did not exist in DB, nothing to delete", $file_path);}
    }

    $photo_url = content_url($UNC_GALLERY['upload_folder'] . "/" . $UNC_GALLERY['photos'] . "/$date_path/$file_name");
    $thumb_url = content_url($UNC_GALLERY['upload_folder'] . "/" . $UNC_GALLERY['thumbnails'] . "/$date_path/$file_name");

    XMPP_ERROR_trace("Inserting file into DB", "$file_date, $file_name, $file_path");
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
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("tried to write info for file, already exists in database", $file_path);}
        return false;
    }

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
    );
    $data_sets = array(
        'default' => $default,
        'exif' => $exif,
        'xmp' => $xmp,
        'iptc' => $iptc,
    );

    XMPP_ERROR_trace("Writing file data to DB", $data_sets);
    foreach ($data_sets as $set_name => $set) {
        foreach ($set as $name => $value) {
            // insert into DB
            if (is_array($value)) {
                // TODO: Create a single SQL line instead of entering every line by itself
                // should make the process much faster
                foreach ($value as $arr_value) {
                    $wpdb->insert(
                        $wpdb->prefix . "unc_gallery_att",
                        array(
                            'file_id' => $insert_id,
                            'att_group' => $set_name,
                            'att_name' => $name,
                            'att_value' => $arr_value,
                        )
                    );
                    $insert_id2 = $wpdb->insert_id;
                    if ($insert_id2 == 0) {
                        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("tried to write array info for attributes, already exists in database", $file_path);}
                        return false;
                    }
                }
            } else {
                $wpdb->insert(
                    $wpdb->prefix . "unc_gallery_att",
                    array(
                        'file_id' => $insert_id,
                        'att_group' => $set_name,
                        'att_name' => $name,
                        'att_value' => $value,
                    )
                );
                $insert_id2 = $wpdb->insert_id;
                if ($insert_id2 == 0) {
                    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("tried to write string info for attributes, already exists in database", $file_path);}
                    return false;
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("finished image info write");}
    return true;
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $output = '';
    $command = 'exiftool -s -g -a -json -struct -xmp:all -iptc:all -exif:all -file:all "'.$file_path.'"'; //  -composite:all
    exec($command, $output);
    $metadata_json = implode('', $output);
    $metadata_array = json_decode($metadata_json, true);
    if (is_null($metadata_array)) { // json error, exiftool does not exist etc.
        return false;
    }
    XMPP_ERROR_trace("JSON", $metadata_array);

    $D = $metadata_array[0];

    $data_sets = array('exif' => 'EXIF', 'xmp' => 'XMP', 'iptc' => 'IPTC');

    // iterate data types
    $file_data = array();
    foreach ($data_sets as $our_set => $json_set) {
        // let's iterate our codes and see if they exist in the JSON
        foreach ($UNC_GALLERY['codes'][$our_set] as $code_key => $code_setting) {
            //XMPP_ERROR_trace("$json_set", $code_key);
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
                    $file_data[$our_set][$code_key] = $D[$json_set][$key];
                }
            }
        }
    }

    // those two are special and manually converted
    $file_data['exif']['file_width'] = $D['File']['ImageWidth'];
    $file_data['exif']['file_height'] = $D['File']['ImageHeight'];

    // convert date from 2016:10:01 to 2016-10:01
    $pattern = '/(\d\d\d\d):(\d\d):(\d\d \d\d:\d\d:\d\d)/';
    $replace_pattern = '$1-$2-$3';
    $correct_date = preg_replace($pattern, $replace_pattern, $D['EXIF']['CreateDate']);
    $file_data['exif']['created'] = $correct_date;

    // XMPP_ERROR_trace("EXIFTOOL", $file_data);
    return $file_data;
}

/**
 * Delete the information of one file from the database
 * returns false if the data did not exist
 *
 * @global type $wpdb
 * @global array $UNC_GALLERY
 * @param type $file_name
 * @param type $file_date
 * @return boolean
 */
function unc_image_info_delete($file_name, $file_date) {
    global $wpdb, $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // remove existing file info

    $sql = "SELECT id FROM " . $wpdb->prefix . "unc_gallery_img WHERE file_time LIKE %s AND file_name=%s;";

    $file_date_sql = esc_sql($file_date);
    $file_name_sql = esc_sql($file_name);
    $sql = "SELECT id FROM " . $wpdb->prefix . "unc_gallery_img WHERE file_time LIKE '$file_date_sql%' AND file_name='$file_name_sql';";
    XMPP_ERROR_trace("File data delete sql", $sql);
    $check_data =  $wpdb->get_results($sql, 'ARRAY_A');
    //$check_data = $wpdb->get_results($wpdb->prepare($sql, "$file_date%", $file_name), 'ARRAY_A');
    if (count($check_data) > 0) {
        foreach ($check_data as $row) {
            $id = $row['id'];
            $wpdb->delete($wpdb->prefix . "unc_gallery_img", array('id' => $id));
            $wpdb->delete($wpdb->prefix . "unc_gallery_att", array('file_id' => $id));
        }
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
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $exif = unc_exif_date($file_path);
    if (is_null($exif)) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "exif empty, getting iptc");}
        $iptc = unc_iptc_date($file_path);
        if ($iptc) {
            return $iptc;
        } else {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "iptc & EXIF failed, bail!");}
            return false;
        }
    } else if (!$exif) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "EXIF Data invalid!");}
        return false;
    } else {
        return $exif;
    }
}


/**
 * Code to read XMP file contents from files
 * source: https://surniaulula.com/2013/04/09/read-adobe-xmp-xml-in-php/
 *
 * @global type $UNC_GALLERY
 * @param type $filepath
 * @return boolean
 */
function unc_xmp_get($filepath) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $max_size = 1240000; // maximum size read (1MB)
    $chunk_size = 65536; // read 64k at a time
    $start_tag = '<x:xmpmeta';
    $end_tag = '</x:xmpmeta>';
    $xmp_raw = null;

    $file_fh = fopen($filepath, 'rb');

    // check if we can get the file data
    if ($file_fh) {
        $chunk = '';
        // get the file size
        $file_size = filesize($filepath);
        // as long as we are not exceeding the max size or the file size, operate
        while (($file_pos = ftell($file_fh)) < $file_size  && $file_pos < $max_size ) {
            // read a chunk of the file
            $chunk .= fread( $file_fh, $chunk_size );
            // check if we can find the end_tag
            if (($end_pos = strpos($chunk, $end_tag)) !== false) {
                if (($start_pos = strpos( $chunk, $start_tag)) !== false) {
                    $xmp_raw = substr($chunk, $start_pos, $end_pos - $start_pos + strlen($end_tag));
                }
                break;  // stop reading after finding the end tag of the xmp data
            }
        }
        fclose($file_fh);
        // convert to php
        $xmp_arr = unc_xmp_get_array($xmp_raw);
        return $xmp_arr;
    } else {
        return false;
    }
}


/**
 * convert raw XMP data into a PHP array
 *
 * @param type $xmp_raw
 * @return type
 */
function unc_xmp_get_array($xmp_raw) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $xmp_arr = array();
    foreach ($UNC_GALLERY['codes']['xmp'] as $key => $D ) {
        $match = false;
        // get a single text string
        $regex = $D['regex'];
        preg_match( "/$regex/is", $xmp_raw, $match);
        if (isset($match[1]) && $match[1] != '') {
            $xmp_arr[$key] = $match[1];
        } else { // no match, next one;
            continue;
        }

        // if string contains a list, then re-assign the variable as an array with the list elements
        $xmp_arr[$key] = preg_match_all( "/<rdf:li[^>]*>([^>]*)<\/rdf:li>/is", $xmp_arr[$key], $match ) ? $match[1] : $xmp_arr[$key];
    }
    return $xmp_arr;
}

/**
 * perform our proprietary XMP fixes
 *
 * @param type $xmp_raw
 * @return type
 */
function unc_xmp_fix($xmp_raw) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // custom location string
    $val_array = array('country', 'state', 'city', 'location');
    $loc_arr = array();
    foreach ($val_array as $loc_type) {
        if (isset($xmp_raw[$loc_type])) {
            $loc_arr[$loc_type] = $xmp_raw[$loc_type];
        } else {
            $loc_arr[$loc_type] = 'n/a';
            $xmp_raw[$loc_type] = 'n/a';
        }
    }
    $loc_str = implode("|", $loc_arr);
    $xmp_raw['loc_str'] = $loc_str;

    // custom flat keyword, hierarchical keywords need to be split into a third dimension
    if (isset($xmp_raw['Hierarchical Keywords'])) {
        foreach ($xmp_raw['Hierarchical Keywords'] as $li => $val) {
            $final_val = explode( '|', $val );
            $xmp_raw['Hierarchical Keywords'][$li] = $final_val;
        }
        unset($li, $val);
    }
    return $xmp_raw;
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $out = array();
    $set = $UNC_GALLERY['codes'][$var];
    foreach ($set as $key => $D) {
        $out[$key] = $D['description'];
    }
    return $out;
}

/**
 * Get data from the EXIF values, convert it
 *
 * @param type $image_path
 * @return string
 */
function unc_exif_get($image_path) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $data = array();
    // we only take the EXIF data we need
    foreach ($UNC_GALLERY['codes']['exif'] as $code => $C) {
        // we artificially added the filename as an EXIF info to make the admin menu easier
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $str = unc_exif_convert_gps_combo($gps_arr);
    $link = "<a href='http://www.google.com/maps/place/$str' target='_blank'>Link</a>";
    return $link;
}


function unc_exif_convert_gps_combo($gps_arr) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // 'GPSLatitudeRef', 'GPSLatitude', 'GPSLongitudeRef', 'GPSLongitude'
    $lat_coords = unc_exif_convert_gps(array('Hemisphere' => $gps_arr['GPSLatitudeRef'], 'Coordinates' => $gps_arr['GPSLatitude']));
    $lon_coords = unc_exif_convert_gps(array('Hemisphere' => $gps_arr['GPSLongitudeRef'], 'Coordinates' => $gps_arr['GPSLongitude']));
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // $gps array elelemt 1 is the reference, 0 is the coordinate
    if (!isset($gps_arr['Coordinates'])) {
        XMPP_ERROR_trigger("GPS Coord not set!");
    }
    if (!isset($gps_arr['Hemisphere'])) {
        XMPP_ERROR_trigger("GPS Hemisphere not set!");
    }
    $coord = $gps_arr['Coordinates'];
    $hemi = $gps_arr['Hemisphere'];

    $degrees = count($coord) > 0 ? unc_exif_convert_gps_2_Num($coord[0]) : 0;
    $minutes = count($coord) > 1 ? unc_exif_convert_gps_2_Num($coord[1]) : 0;
    $seconds = count($coord) > 2 ? unc_exif_convert_gps_2_Num($coord[2]) : 0;

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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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


/**
 * Get all IPTC tags
 *
 * @param type $file_path
 * @return type
 */
function unc_iptc_get($file_path) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $iptc_obj = new IPTC($file_path);
    return $iptc_obj->dump();
}

/**
 * Fix IPTC data with our proprietary data types
 *
 * @param type $iptc_raw
 * @return type
 */
function unc_iptc_fix($iptc_raw) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // location info
    $val_array = array('country', 'province_state', 'city', 'sublocation');
    $loc_arr = array();
    foreach ($val_array as $loc_type) {
        if (isset($iptc_raw[$loc_type])) {
            $loc_arr[$loc_type] = $iptc_raw[$loc_type];
        } else {
            $loc_arr[$loc_type] = 'n/a';
            $iptc_raw[$loc_type] = 'n/a';
        }
    }
    $loc_str = implode("|", $loc_arr);
    $iptc_raw['loc_str'] = $loc_str;
    return $iptc_raw;
}

/**
 * Get the IPTC date of an image
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @return boolean
 */
function unc_iptc_date($file_path) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $iptc_obj = new IPTC($file_path);

    $iptc_date = $iptc_obj->get('created_date'); //  '20160220',
    $iptc_time = $iptc_obj->get('created_time'); //  '235834',
    if (strlen($iptc_date . $iptc_time) != 14) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "iptc length wrong: $iptc_date / $iptc_time");}
        return false;
    }
    $search_pattern = '/(\d\d\d\d)(\d\d)(\d\d) (\d\d)(\d\d)(\d\d)/';
    $replace_pattern = '$1-$2-$3 $4:$5:$6';
    $fixed_date = preg_replace($search_pattern, $replace_pattern, "$iptc_date $iptc_time");
    return $fixed_date;
}

/**
 * Take an EXIF date format and convert it to a date / time string
 *
 * @param type $date
 * @return type
 */
function unc_iptc_convert_date($date, $time) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $fulldate = $date . " " . $time;
    $search_pattern = '/(\d\d\d\d)(\d\d)(\d\d) (\d\d)(\d\d)(\d\d)/';
    $replace_pattern = '$1-$2-$3 $4:$5:$6';
    $fixed_date = preg_replace($search_pattern, $replace_pattern, $fulldate);
    return $fixed_date;
}


/**
 * Write the IPTC date to an image
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @param type $date_str
 */
function unc_iptc_date_write($file_path, $date_str) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // convert date_str to IPTC
    $search_pattern = '/(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)/';
    $date_pattern = '$1$2$3';
    $iptc_date = preg_replace($search_pattern, $date_pattern, $date_str);
    $time_pattern = '$4$5$6';
    $iptc_time = preg_replace($search_pattern, $time_pattern, $date_str);

    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace('will write IPTC date in format', "$iptc_date / $iptc_time");}
    // write IPTC Date / time
    $taget_iptc_obj = new iptc($file_path);
    $taget_iptc_obj->set('created_date', $iptc_date);
    $taget_iptc_obj->set('created_time', $iptc_time);
    $taget_iptc_obj->write();
}


/**
 * Class to write IPTC data to a file
 * Source: http://php.net/manual/en/function.iptcembed.php
 */
class iptc {
    var $meta=Array();
    var $hasmeta=false;
    var $file=false;
    function iptc($filename) {
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
        $id = $UNC_GALLERY['codes']['iptc'][$tag]['code'];
        $this->meta ["2#$id"]= Array( $data );
        $this->hasmeta=true;
    }

    function get($tag) {
        global $UNC_GALLERY;
        $id = $UNC_GALLERY['codes']['iptc'][$tag]['code'];
        if (isset($this->meta["2#$id"])) {
            return $this->meta["2#$id"][0];
        } else {
            return false;
        }
    }

    function dump() {
        global $UNC_GALLERY;
        $out = array();
        foreach ($UNC_GALLERY['codes']['iptc'] as $code => $D) {
            $id = $D['code'];
            if (isset($this->meta["2#$id"])) {
                $out[$code] = $this->meta["2#$id"][0];
            }
        }
        return $out;
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
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
        if(!function_exists('iptcembed')) {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "iptcembed Does not exist!!");}
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

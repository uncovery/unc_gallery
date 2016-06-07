<?php

global $UNC_GALLERY;
// detailed info on EXIF Codes
// http://www.exiv2.org/tags.html
$UNC_GALLERY['codes']['exif'] = array(
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
);

$UNC_GALLERY['codes']['xmp'] = array(
    'email' => array(
        'description' => 'Creator Email',
        'regex' => '<Iptc4xmpCore:CreatorContactInfo[^>]+?CiEmailWork="([^"]*)"',
    ),
    'name' => array(
        'description' => 'Owner Name',
        'regex' => '<rdf:Description[^>]+?aux:OwnerName="([^"]*)"',
    ),
    'creation_date' => array(
        'description' => 'Creation Date',
        'regex' => '<rdf:Description[^>]+?xmp:CreateDate="([^"]*)"',
    ),
    'modification_date' => array(
        'description' => 'Modification Date',
        'regex' => '<rdf:Description[^>]+?xmp:ModifyDate="([^"]*)"',
    ),
    'label' => array(
        'description' => 'Label',
        'regex' => '<rdf:Description[^>]+?xmp:Label="([^"]*)"',
    ),
    'credit' => array(
        'description' => 'Credit',
        'regex' => '<rdf:Description[^>]+?photoshop:Credit="([^"]*)"',
    ),
    'source' => array(
        'description' => 'Source',
        'regex' => '<rdf:Description[^>]+?photoshop:Source="([^"]*)"',
    ),
    'headline' => array(
        'description' => 'Headline',
        'regex' => '<rdf:Description[^>]+?photoshop:Headline="([^"]*)"',
    ),
    'city' => array(
        'description' => 'City',
        'regex' => '<rdf:Description[^>]+?photoshop:City="([^"]*)"',
    ),
    'state' => array(
        'description' => 'State',
        'regex' => '<rdf:Description[^>]+?photoshop:State="([^"]*)"',
    ),
    'city' => array(
        'description' => 'City',
        'regex' => '<rdf:Description[^>]+?photoshop:City="([^"]*)"',
    ),
    'country' => array(
        'description' => 'Country',
        'regex' => '<rdf:Description[^>]+?photoshop:Country="([^"]*)"',
    ),
    'countryc_code' => array(
        'description' => 'Country Code',
        'regex' => '<rdf:Description[^>]+?Iptc4xmpCore:CountryCode="([^"]*)"',
    ),
    'location' => array(
        'description' => 'Location',
        'regex' => '<rdf:Description[^>]+?Iptc4xmpCore:Location="([^"]*)"',
    ),
    'title' => array(
        'description' => 'Title',
        'regex' => '<dc:title>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:title>',
    ),
    'description' => array(
        'description' => 'Description',
        'regex' => '<dc:description>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:description>',
    ),
    'creator' => array(
        'description' => 'Creator',
        'regex' => '<dc:creator>\s*<rdf:Seq>\s*(.*?)\s*<\/rdf:Seq>\s*<\/dc:creator>',
    ),
    'keywords' => array(
        'description' => 'Keywords',
        'regex' => '<dc:subject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/dc:subject>',
    ),
    'hierarchicalh_keywords' => array(
        'description' => 'Hierarchical Keywords',
        'regex' => '<lr:hierarchicalSubject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/lr:hierarchicalSubject>',
    ),
);

$UNC_GALLERY['codes']['ipct'] = array(
    'object_name' => array('code' => '005', 'description' => 'Object Name'),
    'edit_status' => array('code' => '007', 'description' => 'Edit Ststus'),
    'priority' => array('code' => '010', 'description' => 'Priority'),
    'category' => array('code' => '015', 'description' => 'Category'),
    'supplemental_category' => array('code' => '020', 'description' => 'Supplemental Category'),
    'fixture_identifier' => array('code' => '022', 'description' => 'Fixture Identifier'),
    'keywords' => array('code' => '025', 'description' => 'Keywords'),
    'release_date' => array('code' => '030', 'description' => 'Release Date'),
    'release_time' => array('code' => '035', 'description' => 'Release Time'),
    'special_instructions' => array('code' => '040', 'description' => 'Special Instructions'),
    'reference_service' => array('code' => '045', 'description' => 'Reference Service'),
    'reference_date' => array('code' => '047', 'description' => 'Reference Date'),
    'reference_number' => array('code' => '050', 'description' => 'Reference Number'),
    'created_date' => array('code' => '055', 'description' => 'Created Date'),
    'created_time' => array('code' => '060', 'description' => 'Created Time'),
    'originating_program' => array('code' => '065', 'description' => 'Originating Program'),
    'program_version' => array('code' => '070', 'description' => 'Program Version'),
    'object_cycle' => array('code' => '075', 'description' => 'Object Cycle'),
    'byline' => array('code' => '080', 'description' => 'Byline'),
    'byline_title' => array('code' => '085', 'description' => 'Byline Title'),
    'city' => array('code' => '090', 'description' => 'City'),
    'province_state' => array('code' => '095', 'description' => 'Province / State'),
    'country_code' => array('code' => '100', 'description' => 'Country Code'),
    'country' => array('code' => '101', 'description' => 'Country'),
    'country_code' => array('code' => '100', 'description' => 'Country Code'),
    'original_transmission_reference' => array('code' => '103', 'description' => 'Original Transmission Reference'),
    'headline' => array('code' => '105', 'description' => 'Headline'),
    'credit' => array('code' => '110', 'description' => 'Credit'),
    'source' => array('code' => '115', 'description' => 'Source'),
    'copyright_string' => array('code' => '116', 'description' => 'Copyright String'),
    'caption' => array('code' => '120', 'description' => 'Caption'),
    'local_caption' => array('code' => '100', 'description' => 'Local Caption'),
);

function unc_image_info_read($file_path, $D = false) {
    global $UNC_GALLERY, $UNC_FILE_DATA;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $folder_info = pathinfo($file_path);
    $date_str = unc_tools_folder_date($folder_info['dirname']);
    $date_path = str_replace("-", DIRECTORY_SEPARATOR, $date_str);
    $file_name = $folder_info['basename'];

    $data_path = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['file_data'] . DIRECTORY_SEPARATOR . $date_path . DIRECTORY_SEPARATOR . $file_name . ".php";

    // in case the data is missing, write a new file
    if (!file_exists($data_path)){
        XMPP_ERROR_trigger($data_path . " Not found!");
        unc_image_info_write($file_path);
    }
    $file_code = md5($date_path . DIRECTORY_SEPARATOR . $file_name . ".php");
    // reset the data so we re-read it from file
    $UNC_FILE_DATA[$file_code] = false;
    require($data_path);
    if ($UNC_FILE_DATA[$file_code] == false) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace("File data failed", $file_name);}
    }

    if (!$D) {
        $D = $UNC_GALLERY['display'];
    }
    $file_date = $UNC_FILE_DATA[$file_code]['file_date'];
    if (isset($D['details'][$file_name])) {
        $description = $D['details'][$file_name] . " ($file_name / $file_date)";
    } else if (isset($D['description']) && $D['description']) {
        $description = $D['description'] . " ($file_name / $file_date)";
    } else {
        $description = "<b>File Name:</b> $file_name; <b>Date:</b> $file_date;";
    }
    $UNC_FILE_DATA[$file_code]['description'] = $description;

    return $UNC_FILE_DATA[$file_code];
}

/**
 * On upload, get all information from a file and write it to a PHP file
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @return boolean
 */
function unc_image_info_write($file_path) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $exif = unc_exif_get($file_path);
    $xmp = unc_xmp_get($file_path);
    $ipct = unc_ipct_get($file_path);

    if (!isset($exif['created'])) {
        $file_date = unc_ipct_convert_date($ipct['created_date'], $ipct['created_time']);
    } else {
        $file_date = $exif['created'];
    }

    $dtime = DateTime::createFromFormat("Y-m-d G:i:s", $file_date);
    $time_stamp = $dtime->getTimestamp(); // time stamp is easier to compare
    $folder_info = pathinfo($file_path);
    $date_str = unc_tools_folder_date($folder_info['dirname']);
    $date_path = str_replace("-", DIRECTORY_SEPARATOR, $date_str);
    $file_name = $folder_info['basename'];

    $orientation = 'landscape';
    if ($exif['file_width'] < $exif['file_height']) {
        $orientation = 'portrait';
    }

    unc_date_folder_create($date_str);

    $photo_url = content_url($UNC_GALLERY['upload_folder'] . "/" . $UNC_GALLERY['photos'] . "/$date_path/$file_name");
    $thumb_url = content_url($UNC_GALLERY['upload_folder'] . "/" . $UNC_GALLERY['thumbnails'] . "/$date_path/$file_name");

    $data_path = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['file_data'] . DIRECTORY_SEPARATOR . $date_path . DIRECTORY_SEPARATOR . $file_name . ".php";

    $data = array(
        'file_name' => $file_name,
        'file_path' => $file_path,
        'thumb_url' => $thumb_url,
        'file_url' => $photo_url,
        'time_stamp' => $time_stamp, // linux time stamp
        'file_date' => $file_date, // full date including time
        'date_str' => substr($file_date, 0, 10), // only the day 0000-00-00
        'orientation' => $orientation,
        'exif' => $exif,
        'xmp' => $xmp,
        'ipct' => $ipct,
        'errors' => $UNC_GALLERY['errors'][$file_path],
    );

    // write the file
    $file_code = md5($date_path . DIRECTORY_SEPARATOR . $file_name . ".php");
    unc_array2file($data, 'UNC_FILE_DATA', $data_path, $file_code);

    global $UNC_FILE_DATA;
    $UNC_FILE_DATA[$file_code] = $data;

    return true;
}


/**
 * Get the date of an image, first EXIF, then IPCT
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
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "exif empty, getting ipct");}
        $ipct = unc_ipct_date($file_path);
        if ($ipct) {
            return $ipct;
        } else {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "ipct & EXIF failed, bail!");}
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

    if ($file_fh) { // let's get the data
        $chunk = '';
        $file_size = filesize( $filepath );
        while (($file_pos = ftell( $file_fh ) ) < $file_size  && $file_pos < $max_size ) {
            $chunk .= fread( $file_fh, $chunk_size );
            if (($end_pos = strpos($chunk, $end_tag)) !== false) {
                if (($start_pos = strpos( $chunk, $start_tag)) !== false) {
                    $xmp_raw = substr($chunk, $start_pos, $end_pos - $start_pos + strlen($end_tag));
                }
                break;  // stop reading after finding the xmp data
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

        // hierarchical keywords need to be split into a third dimension
        if (!empty($xmp_arr[$key]) && $key == 'Hierarchical Keywords') {
            foreach ($xmp_arr[$key] as $li => $val) {
                $final_val = explode( '|', $val );
                $xmp_arr[$key][$li] = $final_val;
            }
            unset($li, $val);
        }
    }
    return $xmp_arr;
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
    $UNC_GALLERY['exif_get_file'] = $image_path;
    $exif = exif_read_data($image_path);
    restore_error_handler();

    $data = array(
        'file_width' => $exif['COMPUTED']['Width'],
        'file_height' => $exif['COMPUTED']['Height'],
    );

    foreach ($UNC_GALLERY['codes']['exif'] as $code => $C) {
        $hex_tag =  'UndefinedTag:' . $C['hex'];
        if (isset($exif[$C['key']])) {
            $val = $exif[$C['key']];
        } else if (isset($exif[$hex_tag])) {
            $val = $exif[$hex_tag];
        } else {
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

    $UNC_GALLERY['exif_get_file'] = $file_path;
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
    $filename = $UNC_GALLERY['exif_get_file'];
    $UNC_GALLERY['errors'][$filename][] = $errstr;
}


/**
 * Get all IPCT tags
 *
 * @param type $file_path
 * @return type
 */
function unc_ipct_get($file_path) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $ipct_obj = new IPTC($file_path);
    return $ipct_obj->dump();
}

/**
 * Get the IPCT date of an image
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @return boolean
 */
function unc_ipct_date($file_path) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $ipct_obj = new IPTC($file_path);

    $ipct_date = $ipct_obj->get('created_date'); //  '20160220',
    $ipct_time = $ipct_obj->get('created_time'); //  '235834',
    if (strlen($ipct_date . $ipct_time) != 14) {
        if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, "ipct length wrong: $ipct_date / $ipct_time");}
        return false;
    }
    $search_pattern = '/(\d\d\d\d)(\d\d)(\d\d) (\d\d)(\d\d)(\d\d)/';
    $replace_pattern = '$1-$2-$3 $4:$5:$6';
    $fixed_date = preg_replace($search_pattern, $replace_pattern, "$ipct_date $ipct_time");
    return $fixed_date;
}

/**
 * Take an EXIF date format and convert it to a date / time string
 *
 * @param type $date
 * @return type
 */
function unc_ipct_convert_date($date, $time) {
    $fulldate = $date . " " . $time;
    $search_pattern = '/(\d\d\d\d)(\d\d)(\d\d) (\d\d)(\d\d)(\d\d)/';
    $replace_pattern = '$1-$2-$3 $4:$5:$6';
    $fixed_date = preg_replace($search_pattern, $replace_pattern, $fulldate);
    return $fixed_date;
}


/**
 * Write the IPCT date to an image
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @param type $date_str
 */
function unc_ipct_date_write($file_path, $date_str) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // convert date_str to IPCT
    $search_pattern = '/(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)/';
    $date_pattern = '$1$2$3';
    $ipct_date = preg_replace($search_pattern, $date_pattern, $date_str);
    $time_pattern = '$4$5$6';
    $ipct_time = preg_replace($search_pattern, $time_pattern, $date_str);

    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace('will write IPCT date in format', "$ipct_date / $ipct_time");}
    // write IPICT Date / time
    $taget_ipct_obj = new iptc($file_path);
    $taget_ipct_obj->set('created_date', $ipct_date);
    $taget_ipct_obj->set('created_time', $ipct_time);
    $taget_ipct_obj->write();
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
        $id = $UNC_GALLERY['codes']['ipct'][$tag]['code'];
        $this->meta ["2#$id"]= Array( $data );
        $this->hasmeta=true;
    }

    function get($tag) {
        global $UNC_GALLERY;
        $id = $UNC_GALLERY['codes']['ipct'][$tag]['code'];
        if (isset($this->meta["2#$id"])) {
            return $this->meta["2#$id"][0];
        } else {
            return false;
        }
    }

    function dump() {
        global $UNC_GALLERY;
        $out = array();
        foreach ($UNC_GALLERY['codes']['ipct'] as $code => $D) {
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

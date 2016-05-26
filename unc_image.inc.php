<?php


function unc_image_info_read($file_path) {
    global $UNC_GALLERY, $UNC_FILE_DATA;
    
    $folder_info = pathinfo($file_path);
    $date_str = unc_tools_folder_date($folder_info['dirname']);
    $date_path = str_replace("-", DIRECTORY_SEPARATOR, $date_str);
    $file_name = $folder_info['basename'];
    
    $data_path = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['file_data'] . DIRECTORY_SEPARATOR . $date_path . DIRECTORY_SEPARATOR . $file_name . ".php";
  
    // in case the data is missing, write a new file
    if (!file_exists($data_path)){
        unc_image_info_write($file_path);
    }
    
    $file_data = false;
    include_once($data_path);
    
    $UNC_FILE_DATA[$file_name] = $file_data;
    return true;
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
    
    $file_date = unc_image_date($file_path); // get image date from EXIF/IPCT
    $dtime = DateTime::createFromFormat("Y-m-d G:i:s", $file_date);
    $time_stamp = $dtime->getTimestamp(); // time stamp is easier to compare
    $folder_info = pathinfo($file_path);
    $date_str = unc_tools_folder_date($folder_info['dirname']);
    $date_path = str_replace("-", DIRECTORY_SEPARATOR, $date_str);
    $file_name = $folder_info['basename'];

    $exif = unc_exif_get($file_path);
    $xmp = unc_xmp_get($file_path);
    $ipct = unc_ipct_get($file_path);
    
    $orientation = 'landscape';
    if ($exif['file_width'] < $exif['file_height']) {
        $orientation = 'portrait';
    }
    
    unc_date_folder_create($date_str);

    $photo_url = content_url($UNC_GALLERY['upload'] . "/" . $UNC_GALLERY['photos'] . "/$date_path/$file_name");
    $thumb_url = content_url($UNC_GALLERY['upload'] . "/" . $UNC_GALLERY['thumbnails'] . "/$date_path/$file_name");
    
    $data_path = $UNC_GALLERY['upload_path'] . DIRECTORY_SEPARATOR . $UNC_GALLERY['file_data'] . DIRECTORY_SEPARATOR . $date_path . DIRECTORY_SEPARATOR . $file_name . ".php";
    
    $data = array();
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
    );
    
    // write the file
    unc_array2file($data, 'file_data', $data_path);
    
    return true;
}


/**
 * Get the date of an image, first EXIF, then IPCT
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @return boolean
 */
function unc_image_date($file_path) {
    global $UNC_GALLERY;
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
    $exif = unc_exif_date($file_path);
    if (!$exif) {
        $UNC_GALLERY['debug'][]["image date check"] = "exif failed, getting ipct";
        $ipct = unc_ipct_date($file_path);
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
 * Code to read XMP file contents from files
 * source: https://surniaulula.com/2013/04/09/read-adobe-xmp-xml-in-php/
 *
 * @global type $UNC_GALLERY
 * @param type $filepath
 * @return boolean
 */
function unc_xmp_get($filepath) {
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
    $key_arr = array(
        'Creator Email' => '<Iptc4xmpCore:CreatorContactInfo[^>]+?CiEmailWork="([^"]*)"',
        'Owner Name'    => '<rdf:Description[^>]+?aux:OwnerName="([^"]*)"',
        'Creation Date' => '<rdf:Description[^>]+?xmp:CreateDate="([^"]*)"',
        'Modification Date'     => '<rdf:Description[^>]+?xmp:ModifyDate="([^"]*)"',
        'Label'         => '<rdf:Description[^>]+?xmp:Label="([^"]*)"',
        'Credit'        => '<rdf:Description[^>]+?photoshop:Credit="([^"]*)"',
        'Source'        => '<rdf:Description[^>]+?photoshop:Source="([^"]*)"',
        'Headline'      => '<rdf:Description[^>]+?photoshop:Headline="([^"]*)"',
        'City'          => '<rdf:Description[^>]+?photoshop:City="([^"]*)"',
        'State'         => '<rdf:Description[^>]+?photoshop:State="([^"]*)"',
        'Country'       => '<rdf:Description[^>]+?photoshop:Country="([^"]*)"',
        'Country Code'  => '<rdf:Description[^>]+?Iptc4xmpCore:CountryCode="([^"]*)"',
        'Location'      => '<rdf:Description[^>]+?Iptc4xmpCore:Location="([^"]*)"',
        'Title'         => '<dc:title>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:title>',
        'Description'   => '<dc:description>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:description>',
        'Creator'       => '<dc:creator>\s*<rdf:Seq>\s*(.*?)\s*<\/rdf:Seq>\s*<\/dc:creator>',
        'Keywords'      => '<dc:subject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/dc:subject>',
        'Hierarchical Keywords' => '<lr:hierarchicalSubject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/lr:hierarchicalSubject>'
    );

    $xmp_arr = array();
    foreach ($key_arr as $key => $regex ) {
        $match = false;
        // get a single text string
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
 * Get data from the EXIF values, convert it
 *
 * @param type $image_path
 * @return string
 */
function unc_exif_get($image_path) {
    global $UNC_GALLERY;
    $exif_codes = $UNC_GALLERY['exif_codes'];

    // detailed info on EXIF Codes
    // http://www.exiv2.org/tags.html
    $exif_codes_full = array(
        'camera_manuf' => array(
            'hex' => '0x010F',
            'key' => 'Make',
            'conversion' => false,
            'unit' => false,
        ),
        'camera_model' => array(
            'hex' => '0x0110',
            'key' => 'Model',
            'conversion' => false,
            'unit' => false,
        ),
        'exposure_time' => array(
            'hex' => '0x829A',
            'key' => 'ExposureTime',
            'conversion' => false,
            'unit' => 'sec.',
        ),
        'f' => array(
            'hex' => '0x829D',
            'key' => 'FNumber',
            'conversion' => 'unc_tools_divide_string',
            'unit' => false,
        ),
        'iso' => array(
            'hex' => '0x8827',
            'key' => 'ISOSpeedRatings',
            'conversion' => false,
            'unit' => false,
        ),
        'focal_length' => array(
            'hex' => '0x920A',
            'key' => 'FocalLength',
            'conversion' => 'unc_tools_divide_string',
            'unit' => 'mm',
        ),
        'lens' => array(
            'hex' => '0xA434',
            'key' => 'LensModel',
            'conversion' => false,
            'unit' => false,
        ),
    );

    $exif = exif_read_data($image_path);

    $data = array(
        'file_width' => $exif['COMPUTED']['Width'],
        'file_height' => $exif['COMPUTED']['Height'],
    );
    foreach ($exif_codes as $code) {
        if (!isset($exif_codes_full[$code])) {
            continue; // TODO: return proper error in case invalid EXIF is queried
        }
        $C = $exif_codes_full[$code];
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
 * Get all IPCT tags
 *
 * @param type $file_path
 * @return type
 */
function unc_ipct_get($file_path) {
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();
    $ipct_obj = new IPTC($file_path);

    $ipct_date = $ipct_obj->get('IPTC_CREATED_DATE'); //  '20160220',
    $ipct_time = $ipct_obj->get('IPTC_CREATED_TIME'); //  '235834',
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
function unc_ipct_date_write($file_path, $date_str) {
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
    $taget_ipct_obj->set('IPTC_CREATED_DATE', $ipct_date);
    $taget_ipct_obj->set('IPTC_CREATED_TIME', $ipct_time);
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

    var $ipct_data = array(
    'IPTC_CREATED_DATE' => '055',
    'IPTC_CREATED_TIME' => '060',
    'IPTC_OBJECT_NAME' => '005',
    'IPTC_EDIT_STATUS' => '007',
    'IPTC_PRIORITY' => '010',
    'IPTC_CATEGORY' => '015',
    'IPTC_SUPPLEMENTAL_CATEGORY' => '020',
    'IPTC_FIXTURE_IDENTIFIER' => '022',
    'IPTC_KEYWORDS' => '025',
    'IPTC_RELEASE_DATE' => '030',
    'IPTC_RELEASE_TIME' => '035',
    'IPTC_SPECIAL_INSTRUCTIONS' => '040',
    'IPTC_REFERENCE_SERVICE' => '045',
    'IPTC_REFERENCE_DATE' => '047',
    'IPTC_REFERENCE_NUMBER' => '050',
    'IPTC_CREATED_DATE' => '055',
    'IPTC_CREATED_TIME' => '060',
    'IPTC_ORIGINATING_PROGRAM' => '065',
    'IPTC_PROGRAM_VERSION' => '070',
    'IPTC_OBJECT_CYCLE' => '075',
    'IPTC_BYLINE' => '080',
    'IPTC_BYLINE_TITLE' => '085',
    'IPTC_CITY' => '090',
    'IPTC_PROVINCE_STATE' => '095',
    'IPTC_COUNTRY_CODE' => '100',
    'IPTC_COUNTRY' => '101',
    'IPTC_ORIGINAL_TRANSMISSION_REFERENCE' => '103',
    'IPTC_HEADLINE' => '105',
    'IPTC_CREDIT' => '110',
    'IPTC_SOURCE' => '115',
    'IPTC_COPYRIGHT_STRING' => '116',
    'IPTC_CAPTION' => '120',
    'IPTC_LOCAL_CAPTION' => '121',
);

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
        global $ipct_data;
        $id = $ipct_data[$tag];
        $this->meta ["2#$id"]= Array( $data );
        $this->hasmeta=true;
    }

    function get($tag) {
        global $ipct_data;
        $id = $ipct_data[$tag];
        if (isset($this->meta["2#$id"])) {
            return $this->meta["2#$id"][0];
        } else {
            return false;
        }
    }

    function dump() {
        global $ipct_data;
        $out = array();
        foreach ($this->ipct_data as $code => $id) {
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

<?php


if (!defined('WPINC')) {
    die;
}

global $UNC_GALLERY;

$UNC_GALLERY['codes']['iptc'] = array(
    // full spec of codes: http://www.iptc.org/std/IIM/4.1/specification/IIMV4.1.pdf
    'object_name' => array('code' => '005', 'description' => 'Object Name', 'key' => false, 'type' => 'text'),
    'edit_status' => array('code' => '007', 'description' => 'Edit Ststus', 'key' => false, 'type' => 'text'),
    'priority' => array('code' => '010', 'description' => 'Priority', 'key' => false, 'type' => 'text'),
    'category' => array('code' => '015', 'description' => 'Category', 'key' => false, 'type' => 'text'),
    'supplemental_category' => array('code' => '020', 'description' => 'Supplemental Category', 'key' => false, 'type' => 'text'),
    'fixture_identifier' => array('code' => '022', 'description' => 'Fixture Identifier', 'key' => false, 'type' => 'text'),
    'keywords' => array('code' => '025', 'description' => 'Keywords', 'key' => 'Keywords', 'type' => 'text'),
    'release_date' => array('code' => '030', 'description' => 'Release Date', 'key' => false, 'type' => 'date'),
    'release_time' => array('code' => '035', 'description' => 'Release Time', 'key' => false, 'type' => 'date'),
    'special_instructions' => array('code' => '040', 'description' => 'Special Instructions', 'key' => false, 'type' => 'text'),
    'reference_service' => array('code' => '045', 'description' => 'Reference Service', 'key' => false, 'type' => 'text'),
    'reference_date' => array('code' => '047', 'description' => 'Reference Date', 'key' => false, 'type' => 'date'),
    'reference_number' => array('code' => '050', 'description' => 'Reference Number', 'key' => false, 'type' => 'integer'),
    'created_date' => array('code' => '055', 'description' => 'Created Date', 'key' => 'DateCreated', 'type' => 'date'),
    'created_time' => array('code' => '060', 'description' => 'Created Time', 'key' => 'TimeCreated', 'type' => 'date'),
    'originating_program' => array('code' => '065', 'description' => 'Originating Program', 'key' => false, 'type' => 'text'),
    'program_version' => array('code' => '070', 'description' => 'Program Version', 'key' => false, 'type' => 'text'),
    'object_cycle' => array('code' => '075', 'description' => 'Object Cycle', 'key' => false, 'type' => 'text'),
    'byline' => array('code' => '080', 'description' => 'Byline', 'key' => 'By-line', 'type' => 'text'),
    'byline_title' => array('code' => '085', 'description' => 'Byline Title', 'key' => false, 'type' => 'text'),
    'city' => array('code' => '090', 'description' => 'City', 'key' => 'City', 'type' => 'location'),
    'sublocation' => array('code' => '092', 'description' => 'Location', 'key' => 'Sub-location', 'type' => 'location'),
    'province_state' => array('code' => '095', 'description' => 'State', 'key' => 'Province-State', 'type' => 'location'),
    'country_code' => array('code' => '100', 'description' => 'Country Code', 'key' => false, 'type' => 'location'),
    'country' => array(
        'code' => '101',
        'description' => 'Country',
        'key' => 'Country-PrimaryLocationName',
        'type' => 'location'),
    'original_transmission_reference' => array('code' => '103', 'description' => 'Original Transmission Reference', 'key' => false, 'type' => 'text'),
    'headline' => array('code' => '105', 'description' => 'Headline', 'key' => false, 'type' => 'text'),
    'credit' => array('code' => '110', 'description' => 'Credit', 'key' => false, 'type' => 'text'),
    'source' => array('code' => '115', 'description' => 'Source', 'key' => false, 'type' => 'text'),
    'copyright_string' => array('code' => '116', 'description' => 'Copyright String', 'key' => false, 'type' => 'text'),
    'caption' => array('code' => '120', 'description' => 'Caption', 'key' => false, 'type' => 'text'),
    'local_caption' => array('code' => '100', 'description' => 'Local Caption', 'key' => false, 'type' => 'text'),
);


/**
 * Fix IPTC data with our proprietary data types
 *
 * @param type $iptc_raw
 * @return type
 */
function unc_iptc_fix($iptc_raw) {
    global $UNC_GALLERY;

    $data_out = array();

    foreach ($UNC_GALLERY['codes']['iptc'] as $key => $C) {
        $iptc_key = $C['key'];
        if (!isset($C['key']) || !isset($iptc_raw[$iptc_key])) {
            continue;
        }
        if ($C['type'] == 'array') {
            $data_out[$key] = implode(", ", $iptc_raw[$iptc_key]);
        } else {
            $data_out[$key] = $iptc_raw[$iptc_key];
        }
    }

    // location info
    $val_array = array('country','province_state','city','sublocation');
    $loc_arr = array();
    foreach ($val_array as $loc_id) {
        if (isset($data_out[$loc_id])) {
            $loc_arr[$loc_id] = $data_out[$loc_id];
        } else {
            $loc_arr[$loc_id] = 'n/a';
            $data_out[$loc_id] = 'n/a';
        }
    }
    $loc_str = implode("|", $loc_arr);
    $data_out['loc_str'] = $loc_str;

    return $data_out;
}

/**
 * Get the IPTC date of an image
 *
 * @global type $UNC_GALLERY
 * @param type $file_path
 * @return boolean
 */
function unc_iptc_date($file_path) {

    $iptc_obj = new IPTC($file_path);

    $iptc_date = $iptc_obj->get('created_date'); //  '20160220',
    $iptc_time = $iptc_obj->get('created_time'); //  '235834',
    if (strlen($iptc_date . $iptc_time) != 14) {

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

    $fulldate = $date . " " . $time;
    $search_pattern = '/(\d\d\d\d).?(\d\d).?(\d\d) (\d\d).?(\d\d).?(\d\d)/';
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

    // convert date_str to IPTC
    $search_pattern = '/(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)/';
    $date_pattern = '$1$2$3';
    $iptc_date = preg_replace($search_pattern, $date_pattern, $date_str);
    $time_pattern = '$4$5$6';
    $iptc_time = preg_replace($search_pattern, $time_pattern, $date_str);

    // write IPTC Date / time
    $target_iptc_obj = new iptc($file_path);
    $target_iptc_obj->set('created_date', $iptc_date);
    $target_iptc_obj->set('created_time', $iptc_time);
    $target_iptc_obj->write();
}


/**
 * Class to read & write IPTC data to a file
 * Source: http://php.net/manual/en/function.iptcembed.php
 *
 * TODO: Upgrade to PHP7 (PHP 4 constructors are now deprecated)
 */
class iptc {
    var $meta=Array();
    var $hasmeta=false;
    var $file=false;

    function __construct($filename) {
        $info = false;
        $this->hasmeta = isset($info["APP13"]);
        if ($this->hasmeta) {
            $this->meta = iptcparse($info["APP13"]);
        }
        $this->file = $filename;
    }

    // set a specific tag and translate it to the right HEX code
    function set($tag, $data) {
        global $UNC_GALLERY;
        $id = $UNC_GALLERY['codes']['iptc'][$tag]['code'];
        $this->meta ["2#$id"]= Array( $data );
        $this->hasmeta=true;
    }

    // get a specific tag
    function get($tag) {
        global $UNC_GALLERY;
        $id = $UNC_GALLERY['codes']['iptc'][$tag]['code'];
        if (isset($this->meta["2#$id"])) {
            return $this->meta["2#$id"][0];
        } else {
            return false;
        }
    }

    // dump all tags
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

    // write tags to file
    function write() {
        global $UNC_GALLERY;
        if(!function_exists('iptcembed')) {

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

    // requires GD library installed
    // remove all tags from a file
    function removeAllTags() {
        $this->hasmeta=false;
        $this->meta=Array();
        $img = imagecreatefromstring(implode(file($this->file)));
        @unlink($this->file); #delete if exists
        imagejpeg($img,$this->file,100);
    }
}

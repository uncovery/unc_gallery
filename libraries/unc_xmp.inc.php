<?php


if (!defined('WPINC')) {
    die;
}

global $UNC_GALLERY;

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

/**
 * perform our proprietary XMP fixes
 *
 * @param type $xmp_raw
 * @return type
 */
function unc_xmp_fix($xmp_raw) {
    // custom location string
    $val_array = array(
        'country', 'state', 'city', 'location',
    );
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


<?php


if (!defined('WPINC')) {
    die;
}

global $UNC_GALLERY;

// https://surniaulula.com/2013/04/09/read-adobe-xmp-xml-in-php/
$UNC_GALLERY['codes']['xmp'] = array(
//    'email' => array(
//        'description' => 'Creator Email',
//        'regex' => '<Iptc4xmpCore:CreatorContactInfo[^>]+?CiEmailWork="([^"]*)"',
//        'key' => "CreatorContactInfo",
//        'type' => 'text',
//    ),
    'event' => array(
        'description' => 'Event',
        'regex' => '<Iptc4xmpExt:Event>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/Iptc4xmpExt:Event>',
        'key' => "Event",
        'type' => 'text',
    ),
    'persons_in_image' => array(
        'description' => 'PersonInImage',
        'regex' => '<Iptc4xmpExt:PersonInImage>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/Iptc4xmpExt:PersonInImage>',
        'key' => "PersonInImage",
        'type' => 'array',
    ),
//    'name' => array(
//        'description' => 'Owner Name',
//        'regex' => '<rdf:Description[^>]+?aux:OwnerName="([^"]*)"',
//        'type' => 'array',
//        'key' => 'creator',
//    ),
    'creation_date' => array(
        'description' => 'Creation Date',
        'regex' => '<rdf:Description[^>]+?xmp:CreateDate="([^"]*)"',
        'key' => 'CreateDate',
        'type' => 'date',
    ),
    'modification_date' => array(
        'description' => 'Modification Date',
        'regex' => '<rdf:Description[^>]+?xmp:ModifyDate="([^"]*)"',
        'key' => 'ModifyDate',
        'type' => 'date',
    ),
    'label' => array(
        'description' => 'Label',
        'regex' => '<rdf:Description[^>]+?xmp:Label="([^"]*)"',
        'key' => 'Label',
        'type' => 'text',
    ),
    'credit' => array(
        'description' => 'Credit',
        'regex' => '<rdf:Description[^>]+?photoshop:Credit="([^"]*)"',
        'key' => 'Credit',
        'type' => 'text',
    ),
    'source' => array(
        'description' => 'Source',
        'regex' => '<rdf:Description[^>]+?photoshop:Source="([^"]*)"',
        'key' => 'Source',
        'type' => 'text',
    ),
    'headline' => array(
        'description' => 'Headline',
        'regex' => '<rdf:Description[^>]+?photoshop:Headline="([^"]*)"',
        'key' => 'Headline',
        'type' => 'text',
    ),
    'city' => array(
        'description' => 'City',
        'regex' => '<rdf:Description[^>]+?photoshop:City="([^"]*)"',
        'key' => 'City',
        'type' => 'location',
    ),
    'state' => array(
        'description' => 'State',
        'regex' => '<rdf:Description[^>]+?photoshop:State="([^"]*)"',
        'key' => 'State',
        'type' => 'location',
    ),
    'country' => array(
        'description' => 'Country',
        'regex' => '<rdf:Description[^>]+?photoshop:Country="([^"]*)"',
        'key' => 'Country',
        'type' => 'location',
    ),
    'country_code' => array(
        'description' => 'Country Code',
        'regex' => '<rdf:Description[^>]+?Iptc4xmpCore:CountryCode="([^"]*)"',
        'key' => 'CountryCode',
        'type' => 'location',
    ),
    'location' => array(
        'description' => 'Location',
        'regex' => '<rdf:Description[^>]+?Iptc4xmpCore:Location="([^"]*)"',
        'key' => 'Location',
        'type' => 'location',
    ),
    'title' => array(
        'description' => 'Title',
        'regex' => '<dc:title>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:title>',
        'key' => 'Title',
        'type' => 'text',
    ),
    'description' => array(
        'description' => 'Description',
        'regex' => '<dc:description>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:description>',
        'key' => 'Description',
        'type' => 'text',
    ),
    'creator' => array(
        'description' => 'Creator',
        'regex' => '<dc:creator>\s*<rdf:Seq>\s*(.*?)\s*<\/rdf:Seq>\s*<\/dc:creator>',
        'key' => 'Creator',
        'type' => 'array',
    ),
    'keywords' => array(
        'description' => 'Keywords',
        'regex' => '<dc:subject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/dc:subject>',
        'key' => 'Subject',
        'type' => 'array',
    ),
    'hierarchical_keywords' => array(
        'description' => 'Hierarchical Keywords',
        'regex' => '<lr:hierarchicalSubject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/lr:hierarchicalSubject>',
        'key' => 'HierarchicalSubject',
        'type' => 'array',
    ),
);


/**
 * perform our proprietary XMP fixes
 *
 * @param type $xmp_raw
 * @return type
 */
function unc_xmp_fix($xmp_raw) {
    global $UNC_GALLERY;

    $data_out = array();
    foreach ($UNC_GALLERY['codes']['xmp'] as $key => $C) {
        $xmp_key = $C['key'];
        if (!isset($xmp_raw[$xmp_key])) {
            continue;
        }
        if ($C['type'] == 'array') {
            $data_out[$key] = implode(", ", $xmp_raw[$xmp_key]);
        } else {
            $data_out[$key] = $xmp_raw[$xmp_key];
        }
    }

    return $data_out;
}
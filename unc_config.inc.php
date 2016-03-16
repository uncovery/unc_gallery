<?php

if (!defined('WPINC')) {
    die;
}

global $UNC_GALLERY;

// upload folders below above folder
// these configs should be moved to database settings
// TODO remove the directory-separators here and insert them only in the code since
// URLS need different ones from the Paths.
$UNC_GALLERY['upload'] = DIRECTORY_SEPARATOR . "unc_gallery";
$UNC_GALLERY['photos'] = DIRECTORY_SEPARATOR . "photos";
$UNC_GALLERY['thumbnails'] = DIRECTORY_SEPARATOR . "thumbs";
$UNC_GALLERY['base_url'] = plugins_url();
$UNC_GALLERY['gallery_url'] = $UNC_GALLERY['base_url'] . DIRECTORY_SEPARATOR . "galleries";
$UNC_GALLERY['settings_prefix'] = 'unc_gallery_';
$UNC_GALLERY['display'] = array(); // ignore this

// options for displays
$UNC_GALLERY['keywords'] = array(
    'type' => array(
        'day' => array('datepicker', 'datelist'), // shows a single date's gallery, optional date picker
        'image' => array('link'), // only one image, requires file addon unless random or latest
        'thumb' => array('link'), // only the icon of one image, requires file addon unless random or latest
    ),
    'date' => array('random', 'latest'),  // whichdate to chose
    'file' => array('random', 'latest'), // in case of image or icon type, you can chose one filename
);

// file & mime-types
$UNC_GALLERY['thumbnail_ext'] = 'jpeg'; // do not change this, PNG has issues with IPCT
$UNC_GALLERY['valid_filetypes'] = array(
    "image/gif" => 'gif',
    "image/jpeg" => 'jpeg',
    "image/png" => 'png',
);

// This is used to automatically / dynamically create the settings menu
$UNC_GALLERY['user_settings'] = array(
    'thumbnail_height' => array(
        'help' => 'The desired thumbnail height in pixels. Applies only for new uploads. Use the "Rebuild Thumbnails" function in the "Maintenance" tab to re-generate all tumbnails after changing this.',
        'default' => '120',
        'type' => 'text',
    ),
    'picture_long_edge' => array(
        'help' => 'Shrink the full-size images so that the long edge will be this long (in pixels, 0 for disable)',
        'default' => '0',
        'type' => 'text',
    ),
    'time_offset' => array(
        'help' => 'If you take photos after midnight and don\'t want them show on the next day,
        add an offset here in one of <a href="http://php.net/manual/en/datetime.formats.relative.php">these</a> formats.',
        'default' => '-6 hours',
        'type' => 'text',
    ),
    'admin_date_selector' => array(
        'help' => 'Chose if you want to have a calendar or a dropdown list for the Manage Images Admin page',
        'default' => 'datepicker',
        'type' => 'dropdown',
        'options' => array('datepicker' => 'Calendar', 'datelist' => 'Date List'),
    ),
);
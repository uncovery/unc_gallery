<?php

if (!defined('WPINC')) {
    die;
}

global $UNC_GALLERY;

$UNC_GALLERY['upload'] = "unc_gallery";
$UNC_GALLERY['upload_path'] = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $UNC_GALLERY['upload'];
$UNC_GALLERY['photos'] = "photos";
$UNC_GALLERY['thumbnails'] = "thumbs";
$UNC_GALLERY['settings_prefix'] = 'unc_gallery_';

// options for displays
$UNC_GALLERY['keywords'] = array(
    'type' => array(
        'day' => array('calendar', 'datelist'), // shows a single date's gallery, optional date picker
        'image' => array('link'), // only one image, requires file addon unless random or latest
        'thumb' => array('link'), // only the icon of one image, requires file addon unless random or latest
    ),
    'date' => array('random', 'latest'),  // whichdate to chose
    'file' => array('random', 'latest'), // in case of image or icon type, you can chose one filename
);

// file & mime-types
$UNC_GALLERY['thumbnail_ext'] = 'jpeg'; // do not change this, PNG has issues with IPCT
$UNC_GALLERY['valid_filetypes'] = array(
    "image/jpeg" => 'jpeg',
    // "image/png" => 'png', // cannot use png since it does not support IPCT/EXIF
    // "image/gif" => 'gif', // cannot use gif since it does not support IPCT/EXIF
);

// This is used to automatically / dynamically create the settings menu
$UNC_GALLERY['user_settings'] = array(
    'thumbnail_height' => array(
        'help' => 'The desired thumbnail height in pixels. Applies only for new uploads. Use the "Rebuild Thumbnails" function in the "Maintenance" tab to re-generate all tumbnails after changing this.',
        'default' => '120',
        'type' => 'text',
    ),
    'thumbnail_quality' => array(
        'help' => 'The desired thumbnail quality. Has to be a number between 1 (worst, smallest file) to 100 (best, largest file).',
        'default' => '60',
        'type' => 'text',
    ),
    'picture_long_edge' => array(
        'help' => 'Shrink the full-size images so that the long edge will be this long (in pixels, 0 for disable). Warning: Resizing will remove all photo meta-data except for the date.',
        'default' => '0',
        'type' => 'text',
    ),
    'picture_quality' => array(
        'help' => 'The desired thumbnail quality. Has to be a number between 1 (worst, smallest file) to 100 (best, largest file). This applies only if the images are resized with the above setting.',
        'default' => '75',
        'type' => 'text',
    ),
    'featured_size' => array(
        'help' => 'When featuring an image, how many rows should it cover in height?',
        'default' => '4',
        'type' => 'dropdown',
        'options' => array('2' => '2 Rows', '3' => '3 Rows', '4' => '4 Rows', '5' => '5 Rows'),
    ),
    'admin_date_selector' => array(
        'help' => 'Chose if you want to have a calendar or a dropdown list for the Manage Images Admin page.',
        'default' => 'calendar',
        'type' => 'dropdown',
        'options' => array('calendar' => 'Calendar', 'datelist' => 'Date List'),
    ),
    'image_view_method' => array(
        'help' => 'Do you want to use photoswipe (mobile enabled) or lightbox, or just an image link to view images?',
        'default' => 'photoswipe',
        'type' => 'dropdown',
        'options' => array('photoswipe' => 'Photoswipe', 'lightbox' => 'Lightbox', 'none' => 'Direct image link'),
    ),
    'show_exif_data' => array(
        'help' => 'Do you want to show EXIF data for shutter speed, aperture etc?',
        'default' => 'yes',
        'type' => 'dropdown',
        'options' => array('yes' => 'Yes', 'no' => 'No'),
    ),
);

// exif data to be looked for
// codes from http://www.exiv2.org/tags.html
$UNC_GALLERY['exif_codes'] = array(
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
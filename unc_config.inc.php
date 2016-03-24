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
    'time_offset' => array(
        'help' => 'If you take photos after midnight and don\'t want them show on the next day,
        add an offset here in one of <a href="http://php.net/manual/en/datetime.formats.relative.php">these</a> formats.',
        'default' => '-6 hours',
        'type' => 'text',
    ),
    'admin_date_selector' => array(
        'help' => 'Chose if you want to have a calendar or a dropdown list for the Manage Images Admin page.',
        'default' => 'calendar',
        'type' => 'dropdown',
        'options' => array('calendar' => 'Calendar', 'datelist' => 'Date List'),
    ),
    'image_view_type' => array(
        'help' => 'Do you want to use photoswipe or lightbox to view images?',
        'default' => 'photoswipe',
        'type' => 'dropdown',
        'options' => array('photoswipe' => 'Photoswipe', 'lightbox' => 'Lightbox'),
    ),
);
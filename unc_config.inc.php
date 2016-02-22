<?php

if (!defined('WPINC')) {
    die;
}

global $UNC_GALLERY;

// upload folders below above folder
// these configs should be moved to database settings
$UNC_GALLERY['upload'] = "/unc_gallery";
$UNC_GALLERY['photos'] = "/photos";
$UNC_GALLERY['thumbnails'] = "/thumbs";
$UNC_GALLERY['base_url'] = plugins_url();
$UNC_GALLERY['gallery_url'] = $UNC_GALLERY['base_url'] . "/galleries";

// file & mime-types
$UNC_GALLERY['thumbnail_ext'] = 'PNG';
$UNC_GALLERY['valid_filetypes'] = array(
    "image/gif" => 'gif',
    "image/jpeg" => 'jpg',
    "image/png" => 'png',
);

$UNC_GALLERY['user_settings'] = array(
    'timezone' => array(
        'help' => 'Your local timezone, must be a value from the <a href="http://php.net/manual/en/timezones.php">this list</a>.',
        'default' => 'Asia/Hong_Kong',
    ),
    'thumbnail_height' => array(
        'help' => 'The desired thumbnail height in pixels. Applies only for new uploads',
        'default' => '120',
    ),
    'time_offset' => array(
        'help' => 'If you take photos after midnight and don\'t want them show on the next day,
        add an offset here in one of <a href="http://php.net/manual/en/datetime.formats.relative.php">these</a> formats.',
        'default' => '-6 hours',
    ),
);
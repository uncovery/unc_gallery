<?php

if (!defined('WPINC')) {
    die;
}

global $WPG_CONFIG;

// upload folders below above folder
// these configs should be moved to database settings
$WPG_CONFIG['upload'] = "/unc_gallery";
$WPG_CONFIG['photos'] = "/photos";
$WPG_CONFIG['thumbnails'] = "/thumbs";
$WPG_CONFIG['base_url'] = plugins_url();
$WPG_CONFIG['gallery_url'] = $WPG_CONFIG['base_url'] . "/galleries";

// file & mime-types
$WPG_CONFIG['thumbnail_ext'] = 'PNG';
$WPG_CONFIG['valid_filetypes'] = array(
    "image/gif" => 'gif',
    "image/jpeg" => 'jpg',
    "image/png" => 'png',
);

$WPG_CONFIG['timezone'] = 'Asia/Hong_Kong';
$WPG_CONFIG['thumbnail_height'] = 150;


$WPG_CONFIG['offset'] = "-12 hours";

$WPG_CONFIG['user_settings'] = array(
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
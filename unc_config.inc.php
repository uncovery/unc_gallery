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

$WPG_CONFIG['timezone'] = 'Asia/Hong_Kong';
$WPG_CONFIG['thumbnail_height'] = 150;
$WPG_CONFIG['thumbnail_ext'] = 'PNG';

$WPG_CONFIG['valid_filetypes'] = array(
    "image/gif" => 'gif', 
    "image/jpeg" => 'jpg', 
    "image/png" => 'png',
);

$WPG_CONFIG['offset'] = "-12 hours";
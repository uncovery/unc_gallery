<?php

if (!defined('WPINC')) {
    die;
}

global $WPG_CONFIG;

// thse should be transfered to the database so they can be changed in the admin settings.
$WPG_CONFIG['gallery_path'] = plugin_dir_path( __FILE__ ) . "galleries/default";
// upload folders below above folder
$WPG_CONFIG['upload'] = "/unc_gallery";

$WPG_CONFIG['photos'] = "/photos";
$WPG_CONFIG['invalid'] = "/invalid";
$WPG_CONFIG['thumbnails'] = "/thumbs";

$WPG_CONFIG['base_url'] = plugins_url();
$WPG_CONFIG['gallery_url'] = $WPG_CONFIG['base_url'] . "/galleries";

$WPG_CONFIG['timezone'] = 'Asia/Hong_Kong';
$WPG_CONFIG['thumbnail_size'] = 300;
$WPG_CONFIG['thumbnail_ext'] = 'PNG';

$WPG_CONFIG['valid_filetypes'] = array("image/gif", "image/jpeg", "image/png",);
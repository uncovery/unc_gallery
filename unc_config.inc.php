<?php
/*
 * ATTENTION: These settings are not to be changed by the user.
 * These are simply constants and other items used by the plugin.
 * Use the config in the admin screen instead.
 */

if (!defined('WPINC')) {
    die;
}

global $UNC_GALLERY;

$UNC_GALLERY['upload'] = "unc_gallery";
$UNC_GALLERY['upload_path'] = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $UNC_GALLERY['upload'];
$UNC_GALLERY['photos'] = "photos";                  // subfolder of upload_path where the photos go in
$UNC_GALLERY['thumbnails'] = "thumbs";              // subfolder of upload_path where the thumbs go in
$UNC_GALLERY['file_data'] = "file_data";            // subfolder of upload_path where the file data goes in
$UNC_GALLERY['settings_prefix'] = 'unc_gallery_';   // internal prefix for the config storage.

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
        'help' => 'The desired picture quality. Has to be a number between 1 (worst, smallest file) to 100 (best, largest file). This applies only if the images are resized with the above setting.',
        'default' => '75',
        'type' => 'text',
    ),
    'featured_size' => array(
        'help' => 'When featuring an image, how many rows should it cover in height? Chose "dynamic" if you want a orientation-specific size instead.',
        'default' => '4',
        'type' => 'dropdown',
        'options' => array('2' => '2 Rows', '3' => '3 Rows', '4' => '4 Rows', '5' => '5 Rows', 'dynamic' => 'dynamic'),
    ),
    'featured_size_for_portrait' => array(
        'help' => 'When featuring an image, how many image rows should it cover in height in case it is higher than wide? You need to set "Featured Size" to "dynamic" to enable this.',
        'default' => '4',
        'type' => 'dropdown',
        'options' => array('2' => '2 Rows', '3' => '3 Rows', '4' => '4 Rows', '5' => '5 Rows'),
    ),
    'featured_size_for_landscape' => array(
        'help' => 'When featuring an image, how many image rows should it cover in height in case it is wider than high? You need to set "Featured Size" to "dynamic" to enable this.',
        'default' => '3',
        'type' => 'dropdown',
        'options' => array('2' => '2 Rows', '3' => '3 Rows', '4' => '4 Rows', '5' => '5 Rows'),
    ),
    'featured_size_for_mixed_sizes' => array(
        'help' => 'When featuring several images of different orientations, how many image rows should they cover? If you do not want to show them all the same size, choose "dynamic".',
        'default' => '3',
        'type' => 'dropdown',
        'options' => array('2' => '2 Rows', '3' => '3 Rows', '4' => '4 Rows', '5' => '5 Rows', 'dynamic' => 'dynamic'),
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
        'help' => 'Which EXIF data do you want to show in image descriptions?',
        'default' => array('exposure_time', 'f', 'iso'),
        'type' => 'multiple',
        'options' => array('camera_manuf', 'camera_model', 'exposure_time', 'f', 'iso', 'focal_length' ,'lens'),
    ),
    'show_keywords' => array(
        'help' => 'Did you assign keywords to your phots (in Lightroom for example) and want to show them?',
        'default' => 'yes',
        'type' => 'dropdown',
        'options' => array('yes' => 'Yes', 'no' => 'No'),
    ),
    'settings_location' => array(
        'help' => 'Do you want the admin screen of this plugin to be shown as a menu entry in the sidebar or a sub-menu of the settings menu?',
        'default' => 'sidebar',
        'type' => 'dropdown',
        'options' => array('sidebar' => 'Show in Sidebar', 'submenu' => 'Show in the Settings'),
    ),
    'uninstall_deletes_images' => array(
        'help' => 'Do you want your images removed from the server when you uninstall the plugin?',
        'default' => 'yes',
        'type' => 'dropdown',
        'options' => array('yes' => 'Yes, delete!', 'no' => 'No, keep the images!'),
    ),
);

// These are the exif codes we will display for the description
// TODO: Convert that to a setting so we can switch them on and off
$UNC_GALLERY['exif_codes'] = array(
    'camera_manuf',
    'camera_model',
    'exposure_time',
    'f',
    'iso',
    'focal_length',
    'lens',
);

// These are the XMP codes we will display for the description
// TODO: Convert that to a setting so we can switch them on and off
$UNC_GALLERY['xmp_codes'] = array(
    'Keywords',
);

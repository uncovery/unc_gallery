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

$UNC_GALLERY['upload_folder'] = "unc_gallery";
$UNC_GALLERY['upload_path'] = WP_CONTENT_DIR . "/" . $UNC_GALLERY['upload_folder'];
$UNC_GALLERY['photos'] = "photos";                  // subfolder of upload_path where the photos go in
$UNC_GALLERY['thumbnails'] = "thumbs";              // subfolder of upload_path where the thumbs go in
$UNC_GALLERY['settings_prefix'] = 'unc_gallery_';   // internal prefix for the config storage.

// options for displays
$UNC_GALLERY['keywords'] = array(
    'type' => array(
        // main type   // valid options
        'day' => array('calendar', 'datelist'), //, 'slideshow'), // shows a single date's gallery, optional date picker
        'image' => array('link','link_post'), // only one image, requires file addon unless random or latest
        'thumb' => array('link','link_post'), // only the icon of one image, requires file addon unless random or latest
        'filter' => array('dropdown', 'list', 'link_list', 'map', 'tagged_posts'),
        'chrono' => array(),
    ),
    'date' => array('random', 'latest'),  // whichdate to chose
    'file' => array('random', 'latest'), // in case of image or icon type, you can chose one filename
    'featured' => array('random', 'latest'),
    'limt_rows' => 'intval',
    'limit_images' => 'intval',
);

// file & mime-types
$UNC_GALLERY['thumbnail_ext'] = 'jpeg'; // do not change this, PNG has issues with IPTC
$UNC_GALLERY['valid_filetypes'] = array( // allows only these for uploads
    "image/jpeg" => 'jpeg',
    // "image/png" => 'png', // cannot use png since it does not support IPTC/EXIF
    // "image/gif" => 'gif', // cannot use gif since it does not support IPTC/EXIF
);

// This is used to automatically / dynamically create the settings menu
$UNC_GALLERY['user_settings'] = array(
    'thumbnail_height' => array(
        'help' => 'The desired thumbnail height in pixels. Applies only for new uploads. Use the "Rebuild Thumbnails" function in the "Maintenance" tab to re-generate all tumbnails after changing this.',
        'default' => '120',
        'type' => 'text',
        'title' => 'Thumbnail Height',
    ),
    'thumbnail_quality' => array(
        'help' => 'The desired thumbnail quality. Has to be a number between 1 (worst, smallest file) to 100 (best, largest file).',
        'default' => '60',
        'type' => 'text',
        'title' => 'Thumbnail Quality',
    ),
    'thumbnail_format' => array(
        'help' => 'Crop the thumbnails to a specific format for easier layouts.',
        'default' => 'max_height',
        'type' => 'dropdown',
        'options' => array('max_height' => 'Do not crop', 'square' => 'Square (like facebook/instagram)'),
        'title' => 'Thumbnail Format',
    ),
    'picture_long_edge' => array(
        'help' => 'Shrink the full-size images so that the long edge will be this long (in pixels, 0 for disable). Warning: Resizing will remove all photo meta-data except for the date.',
        'default' => '0',
        'type' => 'text',
        'title' => 'Picture size (Long Edge)',
    ),
    'picture_quality' => array(
        'help' => 'The desired picture quality. Has to be a number between 1 (worst, smallest file) to 100 (best, largest file). This applies only if the images are resized with the above setting.',
        'default' => '75',
        'type' => 'text',
        'title' => 'Picture Quality',
    ),
    'no_image_alert' => array(
        'help' => 'What to display if there is no image for a given date?',
        'default' => 'not_found',
        'type' => 'dropdown',
        'options' => array('not_found' => 'A fiendly "No images available"', 'error' => 'A red Error mesage', 'nothing' => 'Nothing'),
        'title' => 'No image found alert',
    ),
    'limit_results' => array(
        'help' => 'When displaying images by location or keyword, you should have a maximum limit or results to display. Set to 0 to disable the limit',
        'default' => '50',
        'type' => 'text',
        'title' => 'Limit filter results',
    ),
    'featured_size' => array(
        'help' => 'When featuring an image, how many rows should it cover in height? Chose "dynamic" if you want a orientation-specific size instead.',
        'default' => '4',
        'type' => 'dropdown',
        'options' => array('2' => '2 Rows', '3' => '3 Rows', '4' => '4 Rows', '5' => '5 Rows', 'dynamic' => 'dynamic'),
        'title' => 'Featured Image Size',
    ),
    'featured_size_for_portrait' => array(
        'help' => 'When featuring an image, how many image rows should it cover in height in case it is higher than wide? You need to set "Featured Size" to "dynamic" to enable this.',
        'default' => '4',
        'type' => 'dropdown',
        'options' => array('2' => '2 Rows', '3' => '3 Rows', '4' => '4 Rows', '5' => '5 Rows'),
        'title' => 'Featured Image Size (Portrait Format)',
    ),
    'featured_size_for_landscape' => array(
        'help' => 'When featuring an image, how many image rows should it cover in height in case it is wider than high? You need to set "Featured Size" to "dynamic" to enable this.',
        'default' => '3',
        'type' => 'dropdown',
        'options' => array('2' => '2 Rows', '3' => '3 Rows', '4' => '4 Rows', '5' => '5 Rows'),
        'title' => 'Featured Image Size (Landscape Format)',
    ),
    'featured_size_for_mixed_sizes' => array(
        'help' => 'When featuring several images of different orientations, how many image rows should they cover? If you do not want to show them all the same size, choose "dynamic".',
        'default' => '3',
        'type' => 'dropdown',
        'options' => array('2' => '2 Rows', '3' => '3 Rows', '4' => '4 Rows', '5' => '5 Rows', 'dynamic' => 'dynamic'),
        'title' => 'Featured Image Size (Mixed Formats)',
    ),
    'image_view_method' => array(
        'help' => 'Do you want to use photoswipe (mobile enabled) or lightbox, or just an image link to view images?',
        'default' => 'photoswipe',
        'type' => 'dropdown',
        'options' => array('photoswipe' => 'Photoswipe', 'lightbox' => 'Lightbox', 'none' => 'Direct image link'),
        'title' => 'Image view method',
    ),
    'show_other_data' => array(
        'help' => 'Which basic data do you want to show in image descriptions?',
        'default' => array('permalink'),
        'type' => 'multiple',
        'options' => array('permalink' => 'Image Permalink', 'file_name' => 'Filename', 'dimensions' => 'Dimensions'), // this function just returns an array
        'title' => 'Description Other Data choices',
    ),      
    'show_exif_data' => array(
        'help' => 'Which EXIF data do you want to show in image descriptions?',
        'default' => array('exposure_time', 'f', 'iso'),
        'type' => 'multiple',
        'options' => unc_image_options_array('exif'), // this function just returns an array
        'title' => 'Description EXIF Data choices',
    ),
    'show_xmp_data' => array(
        'help' => 'Which XMP data do you want to show in image descriptions?',
        'default' => array('keywords'),
        'type' => 'multiple',
        'options' => unc_image_options_array('xmp'), // this function just returns an array
        'title' => 'Description XMP Data choices',
    ),
    'show_iptc_data' => array(
        'help' => 'Which IPTC data do you want to show in image descriptions?',
        'default' => array('byline'),
        'type' => 'multiple',
        'options' => unc_image_options_array('iptc'), // this function just returns an array
        'title' => 'Description IPTC Data choices',
    ),  
    'image_data_method' => array(
        'help' => 'What method do you want to use to retrieve image data? ExifTool requires PHP\'s exec() and the <a href="http://www.sno.phy.queensu.ca/~phil/exiftool/install.html">Exiftool</a>.',
        'default' => 'internal',
        'type' => 'dropdown',
        'options' => array(
            'internal' => 'Internal code (slow)',
            'exiftool' => 'ExifTool (faster)',
        ),
        'title' => 'Image data access method',
    ),
    'post_keywords' => array(
        'help' => 'Do you want to automatically add missing keywords from photos to posts? This will not remove any tags from posts, only create & add new ones.',
        'default' => 'none',
        'type' => 'dropdown',
        'options' => array(
            'none' => 'Do not auto-tag',
            'xmp' => 'XMP Keywords',
            'xmp_force' => 'XMP Keywords, remove other tags',
            'iptc' => 'IPTC Keywords',
            'iptc_force' => 'IPTC Keywords, remove other tags'
        ),
        'title' => 'Auto-Tag posts with Keywords',
    ),
    'tag_default_description' => array(
        'help' => 'When adding a new keyword (see above), do you want to have a default text description applied?',
        'default' => '',
        'type' => 'longtext',
        'title' => 'Default Keyword description',
    ),     
    'post_categories' => array(
        'help' => 'Do you want to automatically add XMP-data location-based hierarchical categories to posts? This will not remove manually added categories, only create and add new ones',
        'default' => 'none',
        'type' => 'dropdown',
        'options' => array(
            'none' => 'Do not auto-categorize',
            'xmp_country_state_city_location' => 'XMP: Country - State - City - Location',
            'xmp_city_location' => 'XMP: City - Location',
            'iptc_country_state_city' => 'IPtc: Country - State - City',
        ),
        'title' => 'Auto-Categorize posts by Location',
    ),
    'category_default_description' => array(
        'help' => 'When adding a new location category (see above), do you want to have a default text description applied?',
        'default' => '',
        'type' => 'longtext',
        'title' => 'Default Category description',
    ),    
    'google_api_key' => array(
        'help' => 'Your google API key to display maps.',
        'default' => '',
        'type' => 'text',
        'title' => 'Google API key',
    ),
    'google_maps_type' => array(
        'help' => 'What type of map do you want to display?',
        'default' => 'ROADMAP',
        'type' => 'dropdown',
        'options' => array(
            'HYBRID' => 'Hybrid: Photographic map + roads and city names',
            'ROADMAP' => 'Roadmap: Normal, default 2D map',
            'SATELLITE' => 'Satellite: Photographic map',
            'TERRAIN' => 'Terrain: Map with mountains, rivers, etc.',
        ),
        'title' => 'Google Map type',
    ),
    'google_maps_markerstyle' => array(
        'help' => 'Do you want to click through map layers (country,province, city etc) or show all at once (clustered, splitting out on zoom)?',
        'default' => 'cluster',
        'type' => 'dropdown',
        'options' => array(
            'cluster' => 'Show all markers, cluster nearby ones',
            'layer' => 'Click through layers',
        ),
        'title' => 'Google Map Marker Style',
    ),   
    'google_maps_resultstyle' => array(
        'help' => 'Do you want to show photos or posts when clicking on a map point? Posts works only if you have "Auto-Categorize posts by Location" enabled.',
        'default' => 'posts',
        'type' => 'dropdown',
        'options' => array(
            'photos' => 'Show photos',
            'posts' => 'Show posts',
        ),
        'title' => 'Google Map Result type',
    ), 
    'gps_round_data' => array(
        'help' => 'Do you want to round the GPS accuracy? This is recommended to group locations together.',
        'default' => '5',
        'type' => 'dropdown',
        'options' => array(
            'false' => 'Don\t round',
            '0' => 'Zero digits after the comma',
            '1' => '1 digits after the comma',
            '2' => '2 digits after the comma',
            '3' => '3 digits after the comma',
            '4' => '4 digits after the comma',
            '5' => '5 digits after the comma',
            '6' => '6 digits after the comma',
        ),
        'title' => 'Google Map Result type',
    ),    
    'settings_location' => array(
        'help' => 'Do you want the admin screen of this plugin to be shown as a menu entry in the sidebar or a sub-menu of the settings menu?',
        'default' => 'sidebar',
        'type' => 'dropdown',
        'options' => array('sidebar' => 'Show in Sidebar', 'submenu' => 'Show in the Settings'),
        'title' => 'Admin menu location',
    ),
    'admin_date_selector' => array(
        'help' => 'Chose if you want to have a calendar or a dropdown list for the Manage Images Admin page.',
        'default' => 'calendar',
        'type' => 'dropdown',
        'options' => array('calendar' => 'Calendar', 'datelist' => 'Date List'),
        'title' => 'Admin date selector format',
    ),
    'uninstall_deletes_images' => array(
        'help' => 'Do you want your images removed from the server when you uninstall the plugin?',
        'default' => 'yes',
        'type' => 'dropdown',
        'options' => array('yes' => 'Delete all images!', 'no' => 'Keep all images!'),
        'title' => 'Uninstall behavior',
    ),
);
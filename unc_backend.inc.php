<?php
// Admin functions

if (!defined('WPINC')) {
    die;
}

/**
 * create the admin menu optionally in the admin bar or the settings benu
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_menu() {
    global $UNC_GALLERY;
    // the main page where we manage the options

    // in the settings page
    if (isset($UNC_GALLERY['settings_location']) && $UNC_GALLERY['settings_location'] == 'submenu') {
        $main_options_page_hook_suffix = add_options_page(
            'Uncovery Gallery Options',
            'Uncovery Gallery',
            'manage_options',
            'unc_gallery_admin_menu',
            'unc_gallery_admin_settings'
        );
    } else {  // main admin menu
        $main_options_page_hook_suffix = add_menu_page(
            'Uncovery Gallery Options', // $page_title,
            'Uncovery Gallery', // $menu_title,
            'manage_options', // $capability,
            'unc_gallery_admin_menu', // $menu_slug,
            'unc_gallery_admin_settings' // $function, $icon_url, $position
        );
    }
    add_action('admin_print_scripts-' . $main_options_page_hook_suffix, 'unc_gallery_enqueue_css_and_js');
}


/**
 * This adds the Wordpress features for the admin pages
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_init() {
    global $UNC_GALLERY;

    add_settings_section(
        'unc_gallery_pluginPage_section',
        __('Settings', 'wordpress'),
        'unc_gallery_settings_section_callback',
        'unc_gallery_settings_page' // need to match menu_slug
    );

    // we iterate the plugin settings and creat the menus dynamically from there
    foreach ($UNC_GALLERY['user_settings'] as $setting => $D) {
        $prefix = $UNC_GALLERY['settings_prefix'];
        register_setting('unc_gallery_settings_page', $prefix . $setting);
        $setting_value = get_option($prefix . $setting, $D['default']);
        $args = array(
            'setting' => $prefix . $setting,
            'value'=> $setting_value,
            'help'=> $D['help'],
            'default' => $D['default'],
        );
        if ($D['type'] == 'text') {
            $callback = 'unc_gallery_setting_text_field_render';
        } else if ($D['type'] == 'longtext') {
            $callback = 'unc_gallery_setting_longtext_field_render';
        } else if ($D['type'] == 'dropdown') {
            $callback = 'unc_gallery_setting_drodown_render';
            $args['options'] = $D['options'];
        } else if ($D['type'] == 'multiple'){
            $callback = 'unc_gallery_setting_multiple_render';
            $args['options'] = $D['options'];
        } else if ($UNC_GALLERY['debug']) {

        }
        add_settings_field(
            $prefix . $setting,
            __($D['title'], 'wordpress'),
            $callback,
            'unc_gallery_settings_page',
            'unc_gallery_pluginPage_section',
            $args
        );
    }

    // check if the upload folder exists:
    $dirPath =  $UNC_GALLERY['upload_path'];
    if (!file_exists($dirPath)) {
        echo unc_display_errormsg("The upload folder $dirPath does not exist!");
        unc_gallery_plugin_activate();
    }
}

/**
 * Generic function to render a text input for WP settings dialogues
 * called by unc_gallery_admin_init
 * @param type $A
 */
function unc_gallery_setting_text_field_render($A) {
    $def_text = str_replace(" ", '&nbsp;', $A['default']);
    $out = "<input class='textinput' type='text' name='{$A['setting']}' value='{$A['value']}'></td><td>{$A['help']} <strong>Default:</strong>&nbsp;'$def_text'\n";
    echo $out;
}

/**
 * Generic function to render a long text input for WP settings dialogues
 * called by unc_gallery_admin_init
 * @param type $A
 */
function unc_gallery_setting_longtext_field_render($A) {
    $def_text = str_replace(" ", '&nbsp;', $A['default']);
    $out = "<textarea name='{$A['setting']}' rows=4>{$A['value']}</textarea></td><td>{$A['help']} <strong>Default:</strong>&nbsp;'$def_text'\n";
    echo $out;
}

/**
 * Generic function to render a dropdown input for WP settings dialogues
 * called by unc_gallery_admin_init
 * @param type $A
 */
function unc_gallery_setting_drodown_render($A) {
    $out = "<select name=\"{$A['setting']}\">\n";
    foreach ($A['options'] as $option => $text) {
        $sel = '';
        if ($option == $A['value']) {
            $sel = 'selected';
        }
        $out .= "<option value=\"$option\" $sel>$text</option>\n";
    }
    $def_text = str_replace(" ", '&nbsp;', $A['options'][$A['default']]);
    $out .= "</select></td><td>{$A['help']} <strong>Default:</strong>&nbsp;'$def_text'\n";
    echo $out;
}

/**
 * Generic function to render a checkkbox input for WP settings dialogues
 * called by unc_gallery_admin_init
 * @param type $A
 */
function unc_gallery_setting_multiple_render($A) {
    global $UNC_GALLERY;
    $out = '';
    if (!is_array($A['value'])) {
        $A['value'] = $A['default'];
    }
    asort($A['options']);
    foreach ($A['options'] as $option => $text) {
        $sel = '';
        if (in_array($text, $A['value'])) {
            $sel = 'checked="checked"';
        }
        $out .= "<input type=\"checkbox\" name=\"{$A['setting']}[$option]\" value=\"$text\" $sel>&nbsp;$text<br>\n";
    }
    $def_arr = array();
    foreach ($A['default'] as $def) {
        $def_arr[] = $A['options'][$def];
    }
    $defaults = implode("', '", $def_arr);
    $def_text = str_replace(" ", '&nbsp;', $defaults);
    $out .= "</td><td>{$A['help']} <strong>Default:</strong>&nbsp;'$def_text'\n";
    echo $out;
}


/**
 * Callback for the Settings-section. Since we have only one, no need to use this
 * Called in unc_gallery_admin_init
 */
function unc_gallery_settings_section_callback() {
    // echo __( 'Basic Settings', 'wordpress' );
}

/**
 * this will manage the settings
 */
function unc_gallery_admin_settings() {
    global $UNC_GALLERY;
    remove_filter('the_content', 'wpautop');
    echo '<div class="wrap unc_gallery unc_gallery_admin">
    <h2>Uncovery Gallery</h2>
    <script type="text/javascript">
        jQuery(document).ready(function() {
        // Initialize jquery-ui tabs
        jQuery(\'.unc_jquery_tabs\').tabs();
        // Fade in sections that we wanted to pre-render
        jQuery(\'.unc_fade_in\').fadeIn(\'fast\');
        });
    </script>';

    if (!function_exists('exif_read_data')) {
        echo unc_display_errormsg("EXIF Library does not exist! This plugin will not work properly! See <a href=\"http://php.net/manual/en/book.exif.php\">http://php.net/manual/en/book.exif.php</a>");
    }

    echo '<div class="unc_jquery_tabs unc_fade_in">
    <ul>' . "\n";

    # Set up tab titles
    echo "<li><a href='#tab1'><span>Settings</span></a></li>
        <li><a href='#tab2'><span>Upload</span></a></li>
        <li><a href='#tab3'><span>Code Creator</span></a></li>
        <li><a href='#tab4'><span>Manage Images</span></a></li>
        <li><a href='#tab5'><span>Maintenance</span></a></li>
        <li><a href='#tab6'><span>Data Integrity</span></a></li>
        <li><a href='#tab7'><span>Documentation</span></a></li>";
    if ($UNC_GALLERY['debug']) {
        echo "<li><a href='#tab8'><span>Debug Logs</span></a></li>\n";
    }
    echo "</ul>\n";

    echo "<div id='tab1'>
        <form method=\"post\" action=\"options.php\">\n";
    settings_fields('unc_gallery_settings_page');
    do_settings_sections('unc_gallery_settings_page');
    submit_button();
    echo "</form>
        </div>
        <div id='tab2'>\n";
    echo unc_uploads_form();
    echo "</div>
        <div id='tab3'>\n";
    echo unc_gallery_admin_shortcode_creator();
    echo "</div>
        <div id='tab4'>\n";
    echo unc_gallery_admin_display_images();
    echo "</div>
        <div id='tab5'>\n";
    echo unc_gallery_admin_maintenance();
    echo "</div>
        <div id='tab6'>\n";
    echo unc_gallery_data_integrity();
    echo "</div>
        <div id='tab7'>\n";
    echo unc_gallery_admin_show_documentation();
    echo "</div>";

    //if ($UNC_GALLERY['debug'] == 'yes') {
        echo "<div id='tab8'>\n";
        echo unc_gallery_admin_show_debuglogs();
        echo "</div>\n";
    //}
    echo "</div>";
}

/**
 * displays the complete image catalog for the admin
 * and then provide buttons for AJAX-loading of older content
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_display_images() {
    global $UNC_GALLERY;

    $out = "<h2>Manage Images</h2>\n";
    // check first if there is a folder to delete:
    $folder_del = filter_input(INPUT_GET, 'folder_del');
    if (!is_null($folder_del)) {
        // TODO: the return here should be in a notifcation area
        $out .= unc_date_folder_delete($folder_del);
    }

    // get a standard short-tag output for latest date with datepicker
    $out .= unc_gallery_apply(array('date'=> 'latest', 'options'=> $UNC_GALLERY['admin_date_selector']));
    echo $out;
}


/**
 * Displays a dialogue to perform maintenance operations
 * @return string
 */
function unc_gallery_admin_maintenance() {
    $out = '<h2>Maintenance</h2>

        <div class="admin_section">
            <button class="button button-primary" onclick="
                unc_gallery_generic_ajax_progress(
                    \'unc_gallery_admin_rebuild_thumbs\',
                    \'maintenance_target_div\',
                    \'Are you sure?\nThis can take a while for the whole database!\',
                    true
                )">
            Rebuild Thumbnails
            </button>
        </div>

        <div class="admin_section">
            <button class="button button-primary" onclick="
                unc_gallery_generic_ajax_progress(
                    \'unc_gallery_admin_remove_data\',
                    \'maintenance_target_div\',
                    \'Are you sure?\nYou will have to rebuild the data with the next button!\',
                    true,
                    \'maintenance-process-progress-bar\',
                    \'Remove\'
                )
                ">
                Erase all image data
            </button>
            This will only remove the image data from the database so it can be re-built with the next button.
        </div>

        <div class="admin_section">
            <button class="button button-primary" onclick="
                unc_gallery_generic_ajax_progress(
                    \'unc_gallery_admin_rebuild_data\',
                    \'maintenance_target_div\',
                    \'Are you sure?\nThis can take a while!\',
                    true,
                    \'maintenance-process-progress-bar\',
                    \'Rebuild\'
                )
                ">
                Re-build missing image data from files.
            </button>
            This will go through all files and read all EXIF, IPTC, XMP etc data. Since re-loading all of the data can take a long time (depending on how many images you have), this process might not finish 100%.
        </div>

        <div class="admin_section">
            <button class="button button-primary" onclick="unc_gallery_generic_ajax_progress(\'unc_gallery_delete_everything\', \'maintenance_target_div\', \'Are you sure?\nThis will delete ALL photos!\', true)">
                Delete all pictures
            </button>
            This will delete ALL images and thumbnails. Use with caution!
        </div>

        <div class="admin_section">
            <button class="button button-primary" onclick="unc_gallery_generic_ajax_progress(\'unc_gallery_admin_orphaned_files\', \'maintenance_target_div\', \'Are you sure?\nThis will delete orphaned images!\', true)">
                Delete orphaned pictures
            </button>
            This will delete orphaned images and thumbnails and check if they are in the database. Files not in the database will be deleted.
        </div>

        <div class="progress-div">
            <div id="maintenance-process-progress-bar">0%</div>
        </div>
        <div id="maintenance_target_div"></div>';
    return $out;
}

function unc_gallery_admin_shortcode_creator() {

    $out = '<h2>Shortcode Creator</h2>
            <p>This tool helps you create complex filters to display exactly the images you want to see</p>
            <form id="shortcode_form" method="POST">
            <div id="add_shortcode_form">';

    // show the form (return it instead of echo
    $out .= unc_gallery_shortcode_form(true);

    $out .= '</form></div>';

    return $out;
}



/**
 * Show the documentation my parsing the README.md file through a markdown parser
 * We are using https://github.com/erusev/parsedown
 */
function unc_gallery_admin_show_documentation() {
    require_once(__DIR__  . '/libraries/Parsedown.php');

    $markdown_docs = file_get_contents(__DIR__  . '/README.md');
    $markdown_fixed = str_replace('/images/', plugins_url( '/images/', __FILE__ ), $markdown_docs);
    $Parsedown = new Parsedown();
    return $Parsedown->text($markdown_fixed);
}

function unc_gallery_data_integrity() {
    $out = "<h2>Data Integrity checks</h2>";

    global $wpdb;

    // check for locations where we have more than one GPS data.
    $check_sql = "SELECT loc_table.att_value as location, gps_table.att_value as gps, count(gps_table.att_value) as filecount
        FROM `wp_unc_gallery_att` as loc_table
        LEFT JOIN wp_unc_gallery_att as gps_table ON loc_table.file_id=gps_table.file_id
        WHERE loc_table.att_name='loc_str' AND gps_table.att_name = 'gps' AND loc_table.att_group='xmp'
        GROUP BY gps_table.att_value";
    $records = $wpdb->get_results($check_sql);
    $locations = array();
    $has_duplicate_gps = false;
    foreach ($records as $line) {
        // split GPS by comma
        $gps_array = explode(",", $line->gps);
        $gps_one = unc_filter_gps_round($gps_array[0]);
        $gps_two = unc_filter_gps_round($gps_array[1]);
        $gps_new_string = $gps_one . "," . $gps_two;

        if (isset($locations[$line->location][$gps_new_string])) {
            $has_duplicate_gps = true;
        }
        $locations[$line->location][$gps_new_string] = "[$line->gps] ([$line->filecount] images)";
    }

    if ($has_duplicate_gps) {
        // now remove all single GPS locations from the array
        $out .= "<h2>You have identical XMP locations with several different GPS data:</h2>
            The Google Map display won't work for XMP locations where you have several GPS data sets.
            <ul>\n";
        foreach ($locations as $loc_name => $loc_data) {
            if (count($loc_data) > 1) {
                $out .= "<li>$loc_name\n<ul>\n";
                foreach ($loc_data as $gps) {
                    $out .= "<li>$gps</li>\n";
                }
                $out .= "</ul>\n</li>\n";
            }
        }
        $out .= "</ul>\n";

    } else {
        $out .= "No gps Integrity issues found";
    }

    $data_no_file_sql = "SELECT file_id
        FROM `wp_unc_gallery_att`
        LEFT JOIN wp_unc_gallery_img ON file_id=id
        WHERE id IS NULL
        group by file_id";
    $data_no_file = $wpdb->get_results($data_no_file_sql, ARRAY_A);

    $count = count($data_no_file);
    $out .= "Found $count datasets without file!";
    foreach ($data_no_file as $D) {
        $wpdb->delete($wpdb->prefix . "unc_gallery_att", array('file_id' => $D['file_id']));
        $out .= "x";
    }

    $duplicate_file_path = "SELECT count(file_path) as counter, file_path FROM `wp_unc_gallery_img` group by file_path having counter > 1";
    $duplicate_file_path_data = $wpdb->get_results($duplicate_file_path, ARRAY_A);
    $count = count($duplicate_file_path_data);
    $out .= "<br>Found $count datasets without duplicate file paths: <br>";
    foreach ($duplicate_file_path_data as $D) {
        $out .= $D['file_path'] . "<br>";
    }



 /*
 *
 *
 *
   // this is another check trying to find out if we have 2 different location names on the same GPS location

    $check_2_sql = "SELECT locations.att_value as location, count(locations.att_value) as locations_counter, gps_data.att_value as gps
        FROM wp_unc_gallery_att AS locations
        LEFT JOIN wp_unc_gallery_att AS gps_data ON locations.file_id = gps_data.file_id
        WHERE locations.att_name = 'loc_str' AND locations.att_group = 'xmp' AND gps_data.att_name = 'gps'
        GROUP BY locations.att_value";

    $records2 = $wpdb->get_results($check_sql);
    $locations2 = array();
    $has_duplicate_gps2 = false;
    foreach ($records2 as $line) {
        $location = $line->location;
        $gps = $line->gps;
        if (isset($locations[$location])) {
            $has_duplicate_gps = true;
        }
        $filecount = $line->filecount;
        $locations[$location][] = "$gps ($filecount images)";
    }

    if ($has_duplicate_gps) {
        // now remove all single GPS locations from the array
        $out .= "<h2>You have identical XMP locations with several different GPS data:</h2>
            The Google Map display won't work for XMP locations where you have several GPS data sets.
            <ul>\n";
        foreach ($locations as $loc_name => $loc_data) {
            if (count($loc_data) > 1) {
                $out .= "<li>$loc_name\n<ul>\n";
                foreach ($loc_data as $gps) {
                    $out .= "<li>$gps</li>\n";
                }
                $out .= "</ul>\n</li>\n";
            }
        }
        $out .= "</ul>\n";

    } else {
        $out .= "No gps Integrity issues found";
    }
*/
    return $out;

    // TODO: create a query that deletes orphaned attachment entries where the file is gone
    // DELETE `wp_unc_gallery_att` FROM `wp_unc_gallery_att` LEFT JOIN
    //    wp_unc_gallery_img ON wp_unc_gallery_att.file_id=wp_unc_gallery_img.id
    //    WHERE file_name IS NULL
}

function unc_gallery_admin_show_debuglogs() {
    $path = plugin_dir_path(__FILE__) . "logs";
    $files = glob($path.'/*.html');

    $out = '<h2>Debug Logs</h2>';
    if (count($files) == 0) {
        $out . "No debug logfiles found.";
        return $out;
    }

    $out .= '<button class="button button-primary" onclick="
            unc_gallery_generic_ajax_progress(
                \'unc_gallery_admin_remove_logs\',
                \'debug_logs_target_div\',
                \'Are you sure?\nThis will erase all logfiles!\',
                true,
                \'maintenance-process-progress-bar\',
                \'Remove\'
            )
            ">
            Erase all debug logs
        </button>
        <div id="debug_logs_target_div">';
    $out .= "<ol>";

    foreach($files as $file) {
        $path = pathinfo($file);
        $basename = $path['basename'];
        $file_url = plugin_dir_url( __FILE__ ) . "logs/$basename";
        $out .= "<li><a href=\"$file_url\" target=\"_blank\">$basename</a></li>";
    }
    $out .= "</ol></div>";
    return $out;
}

/**
 * function to re-build all thumbnails
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_rebuild_thumbs() {
    global $UNC_GALLERY;
    ob_clean();
    if (!current_user_can('manage_options') || !is_admin()) {
        echo "Cannot rebuild Thumbs, you are not admin!";
        wp_die();
    }
    $dirPath = $UNC_GALLERY['upload_path'];
    // cleanup empty folders first
    unc_tools_folder_delete_empty($dirPath);

    $thumb_root = $dirPath . "/" . $UNC_GALLERY['thumbnails'];
    // iterate all image folders
    $photo_folder = $dirPath . "/" . $UNC_GALLERY['photos'];

    // delete all thumbnails
    unc_tools_recurse_files($thumb_root, 'unlink', 'rmdir');

    $process_id = filter_input(INPUT_POST, 'process_id');
    unc_tools_progress_update($process_id, "Cleared existing thumbnails");

    $target_folders = unc_tools_recurse_folders($photo_folder);
    unc_tools_progress_update($process_id, "Got a list of all folders");

    // create thumbnaisl
    foreach ($target_folders as $date => $folder) {
        // construct the thumb folder where we put the thumbnails
        $thumb_folder = $thumb_root . "/" . $date;
        $text = "Processing $date: ";
        unc_date_folder_create($date);

        // enumerate all the files in the source folder
        foreach (glob($folder . "/*") as $image_file) {
            if (!is_dir($image_file)) {
                $filename = basename($image_file);
                $thumb_filename = $thumb_folder . "/" . $filename;
                unc_import_image_resize(
                    $image_file,
                    $thumb_filename,
                    $UNC_GALLERY['thumbnail_height'],
                    $UNC_GALLERY['thumbnail_ext'],
                    $UNC_GALLERY['thumbnail_quality'],
                    $UNC_GALLERY['thumbnail_format']
                );
                $text .= ".";
            }
        }
        unc_tools_progress_update($process_id, $text);
    }
    unc_tools_progress_update($process_id, "Done!");
    wp_die();
}

/**
 * build the missing data
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_rebuild_data() {
    global $UNC_GALLERY, $wpdb;
    ob_clean();

    // $max_time = ini_get('max_execution_time');

    if (!current_user_can('manage_options')) {
        echo "Cannot rebuild data, you are not admin!";
        wp_die();
    }

    // let's count the number of files in the database so
    // we get an image how much work is to do
    $count_files_sql = "SELECT count(id) AS counter FROM " . $wpdb->prefix . "unc_gallery_img;";
    $file_counter = $wpdb->get_results($count_files_sql, 'ARRAY_A');
    $count = $file_counter[0]['counter'];

    // get the Process ID for the Ajax live update
    $process_id = filter_input(INPUT_POST, 'process_id');
    // send the first update
    $process_step_id = unc_tools_progress_update($process_id, "Cleared existing data");

    // calculate progress update percentages
    $overall_one_percent = 100 / $count;
    $overall_percentage = 0;

    $text = '';
    $file_no = 0;
    foreach ($target_folders as $date => $folder) {
        $process_step_id++;
        $text = "Processing $date: <span class=\"file_progress\" style=\"width:0%\">0 % ($file_no files done)</span>";
        $process_step_id = unc_tools_progress_update($process_id, $text, $overall_percentage);
        // iterate all files in a folder, write file info to DB
        $folder_files = glob($folder . "/*");
        $folder_file_count = count($folder_files);
        $file_one_percent = 100 / $folder_file_count;
        $folder_percentage = 0;
        foreach ($folder_files as $image_file) {
            if (!is_dir($image_file)) { // && stristr($image_file, '2018') TODO YEar filter
                // TODO: ERror in case the info cannot be written
                unc_image_info_write($image_file);
                $folder_percentage += $file_one_percent;
                $overall_percentage += $overall_one_percent;
                $file_no++;
            }
            $folder_percentage_text = intval($folder_percentage);
            $text = "Processing $date: <span class=\"file_progress\" style=\"width:$folder_percentage_text%\">$folder_percentage_text % ($file_no files done)</span>";
            unc_tools_progress_update($process_id, $text, $overall_percentage, $process_step_id);
        }
        $text = "Processing $date: <span class=\"file_progress\" style=\"width:100%\">100 %</span>";
        unc_tools_progress_update($process_id, $text, $overall_percentage, $process_step_id);
    }
    unc_tools_progress_update($process_id, "Done!", 100);
    // this signals to the JS function that we can terminate the process_get loop
    unc_tools_progress_update($process_id, false);
    wp_die();
}

/**
 * build the missing data
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_convert_image_format() {
    global $UNC_GALLERY, $wpdb;
    ob_clean();

    // $max_time = ini_get('max_execution_time');

    if (!current_user_can('manage_options')) {
        echo "Cannot convert data, you are not admin!";
        wp_die();
    }

    // let's count the number of files in the database so
    // we get an impression how much work is to do
    $count_files_sql = "SELECT count(id) AS counter FROM " . $wpdb->prefix . "unc_gallery_img WHERE file_name LIKE '%.jpeg';";
    $file_counter = $wpdb->get_results($count_files_sql, 'ARRAY_A');
    $count = $file_counter[0]['counter'];

    // get the Process ID for the Ajax live update
    $process_id = filter_input(INPUT_POST, 'process_id');
    // send the first update
    $process_step_id = unc_tools_progress_update($process_id, "Cleared existing data");

    // calculate progress update percentages
    $overall_one_percent = 100 / $count;
    $overall_percentage = 0;

    // lets get all the data
    $count_files_sql = "SELECT file_path FROM " . $wpdb->prefix . "unc_gallery_img WHERE file_name LIKE '%.jpeg';";

    $att_fields_to_replace = array(
        'permalink', 'file_url', 'thumb_url', 'file_path', 'file_name'
    );

    $text = '';
    $file_no = 0;
    foreach ($target_folders as $date => $folder) {
        $process_step_id++;
        $text = "Processing $date: <span class=\"file_progress\" style=\"width:0%\">0 % ($file_no files done)</span>";
        $process_step_id = unc_tools_progress_update($process_id, $text, $overall_percentage);
        // iterate all files in a folder, write file info to DB
        $folder_files = glob($folder . "/*");
        $folder_file_count = count($folder_files);
        $file_one_percent = 100 / $folder_file_count;
        $folder_percentage = 0;
        foreach ($folder_files as $image_file) {
            if (!is_dir($image_file)) { // && stristr($image_file, '2018') TODO YEar filter
                // TODO: ERror in case the info cannot be written
                unc_image_info_write($image_file);
                $folder_percentage += $file_one_percent;
                $overall_percentage += $overall_one_percent;
                $file_no++;
            }
            $folder_percentage_text = intval($folder_percentage);
            $text = "Processing $date: <span class=\"file_progress\" style=\"width:$folder_percentage_text%\">$folder_percentage_text % ($file_no files done)</span>";
            unc_tools_progress_update($process_id, $text, $overall_percentage, $process_step_id);
        }
        $text = "Processing $date: <span class=\"file_progress\" style=\"width:100%\">100 %</span>";
        unc_tools_progress_update($process_id, $text, $overall_percentage, $process_step_id);
    }
    unc_tools_progress_update($process_id, "Done!", 100);
    // this signals to the JS function that we can terminate the process_get loop
    unc_tools_progress_update($process_id, false);
    wp_die();
}

/**
 * Function to delete all contents, including files
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_delete_everything() {
    global $UNC_GALLERY;
    ob_clean();
    if (!current_user_can('manage_options')) {
        echo "Cannot delete all, you are not admin!";
    } else {
        // delete all images
        unc_tools_recurse_files($UNC_GALLERY['upload_path'], 'unlink', 'rmdir');

        // delete all the data
        unc_gallery_admin_remove_data();

        echo "Done!";
    }
    wp_die();
}

/**
 * Wipes all data from the system databases
 *
 * @global type $UNC_GALLERY
 * @global type $wpdb
 */
function unc_gallery_admin_remove_data() {
    global $wpdb;
    ob_clean();

    if (!current_user_can('manage_options')) {
        echo "Cannot remove data, you are not admin!";
        wp_die();
    }
    // delete all existing data to make sure
    $sql1 = "TRUNCATE " . $wpdb->prefix . "unc_gallery_img";
    $wpdb->get_results($sql1);
    $sql2 = "TRUNCATE " . $wpdb->prefix . "unc_gallery_att";
    $wpdb->get_results($sql2);

    // get the Process ID for the Ajax live update
    $process_id = filter_input(INPUT_POST, 'process_id');
    unc_tools_progress_update($process_id, "Cleared existing data!", 100);
    unc_tools_progress_update($process_id, false);
    wp_die();
}

function unc_gallery_admin_remove_logs() {
    ob_clean();

    if (!current_user_can('manage_options')) {
        echo "Cannot remove logs, you are not admin!";
        wp_die();
    }
    // delete all existing logfiles

    $path = plugin_dir_path(__FILE__) . "logs";
    $files = glob($path.'/*.html');
    if (count($files) == 0) {
        $out = "No debug logfiles found.";
    } else {
        foreach($files as $file) {
            unlink($file);
        }
        $out = "Cleared existing data!";
    }

    // get the Process ID for the Ajax live update
    $process_id = filter_input(INPUT_POST, 'process_id');
    unc_tools_progress_update($process_id, $out, 100);
    unc_tools_progress_update($process_id, false);
    wp_die();
}

function unc_gallery_admin_orphaned_files() {
    global $UNC_GALLERY;
    ob_clean();
    if (!current_user_can('manage_options')) {
        echo "Cannot delete all, you are not admin!";
    } else {
        // delete orphaned files
        unc_tools_recurse_files($UNC_GALLERY['photos'], 'unc_gallery_admin_orphaned_files_check', false);

        // delete orphaned thumbnails
        unc_tools_recurse_files($UNC_GALLERY['thumbnails'], 'unc_gallery_admin_orphaned_thumbs_check', false);

        echo "Done!";
    }
    wp_die();
}

function unc_gallery_admin_orphaned_files_check($path) {
    global $wpdb;

    $sql = "SELECT `id` FROM `wp_unc_gallery_img` WHERE `file_path` = '$path';";
    $files = $wpdb->get_results($sql);
    if (count($files) == 0) {
        unlink($path);
        return true;
    } else {
        return false;
    }

}
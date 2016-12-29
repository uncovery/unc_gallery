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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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
    add_action('admin_print_scripts-' . $main_options_page_hook_suffix, 'unc_gallery_add_css_and_js');
}


/**
 * This adds the Wordpress features for the admin pages
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_init() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

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
        } else if ($D['type'] == 'dropdown') {
            $callback = 'unc_gallery_setting_drodown_render';
            $args['options'] = $D['options'];
        } else if ($D['type'] == 'multiple'){
            $callback = 'unc_gallery_setting_multiple_render';
            $args['options'] = $D['options'];
        } else if ($UNC_GALLERY['debug']) {
            if ($UNC_GALLERY['debug']) {XMPP_ERROR_trigger("Illegal option type ". $D['type']);}
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
 * @param type $A
 */
function unc_gallery_setting_text_field_render($A) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    $def_text = str_replace(" ", '&nbsp;', $A['default']);
    $out = "<input type='text' name='{$A['setting']}' value='{$A['value']}'></td><td>{$A['help']} <strong>Default:</strong>&nbsp;'$def_text'\n";
    echo $out;
}

/**
 * Generic function to render a dropdown input for WP settings dialogues
 * @param type $A
 */
function unc_gallery_setting_drodown_render($A) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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
 * @param type $A
 */
function unc_gallery_setting_multiple_render($A) {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    remove_filter('the_content', 'wpautop');
    echo '<div class="wrap">
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
    echo "<li><a href='#tab1'><span>Settings</span></a></li>\n"
        . "<li><a href='#tab2'><span>Upload</span></a></li>\n"
        . "<li><a href='#tab3'><span>Manage Images</span></a></li>\n"
        . "<li><a href='#tab4'><span>Maintenance</span></a></li>\n"
        . "<li><a href='#tab5'><span>Documentation</span></a></li>\n"
        . "</ul>\n";

    echo "<div id='tab1'>\n";
    echo '<form method="post" action="options.php">'. "\n";
    settings_fields('unc_gallery_settings_page');
    do_settings_sections( 'unc_gallery_settings_page');
    submit_button();
    echo "</form>\n";
    echo "</div>\n";

    echo "<div id='tab2'>\n";
    echo unc_uploads_form();
    echo "</div>\n";

    echo "<div id='tab3'>\n";
    echo unc_gallery_admin_display_images();
    echo "</div>\n";

    echo "<div id='tab4'>\n";
    echo unc_gallery_admin_maintenance();
    echo "</div>\n";

    echo "<div id='tab5'>\n";
    echo unc_gallery_admin_show_documentation();
    echo "</div>\n";

    echo "</div>";
}

/**
 * displayes the complete image catalogue for the admin
 * and then provide buttons for AJAX-loading of older content
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_display_images() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}

    $out = "<h2>Manage Images</h2>\n";
    // check first if there is a folder to delete:
    $folder_del = filter_input(INPUT_GET, 'folder_del', FILTER_SANITIZE_STRING);
    if (!is_null($folder_del)) {
        // TODO: the return here should be in a notifcation area
        $out .= unc_date_folder_delete($folder_del);
    }

    // get a standard short-tag output for latest date with datepicker
    $out .= unc_gallery_apply(array('date'=> 'latest', 'options'=> $UNC_GALLERY['admin_date_selector']));
    echo $out;
}


/**
 * Displays a dilogue to perform maintenance operations
 * @return string
 */
function unc_gallery_admin_maintenance() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    // unc_gallery_generic_ajax(action, target_div, confirmation_message, post, progress_div, progress_text)
    $out = '<h2>Maintenance</h2>
        <div class="admin_section"><button class="button button-primary" onclick="unc_gallery_generic_ajax(\'unc_gallery_thumbnails_rebuild\', \'maintenance_target_div\', \'Are you sure?\nThis can take a while for the whole database!\', true)">
            Rebuild Thumbnails</div>
        <div class="admin_section"><button class="button button-primary" onclick="
            unc_gallery_generic_ajax(
                \'unc_gallery_admin_remove_data\',
                \'maintenance_target_div\',
                \'Are you sure?\nYou will have to rebuild the data with the next button!\',
                true,
                \'maintenance-process-progress-bar\',
                \'Remove\'
            )
            ">
            Erase all image data
        </button> This will only remove the image data from the database so it can be re-built with the next button.</div>
        <div class="admin_section"><button class="button button-primary" onclick="
            unc_gallery_generic_ajax(
                \'unc_gallery_admin_rebuild_data\',
                \'maintenance_target_div\',
                \'Are you sure?\nThis can take a while!\',
                true,
                \'maintenance-process-progress-bar\',
                \'Rebuild\'
            )
            ">
            Re-build missing image data from files.
        </button> This will go through all files and read all EXIF, IPTC, XMP etc data. Since re-loading all of the data can take a long time (depending on how many images you have), this process might not finish 100%.</div>
        <div class="admin_section"><button class="button button-primary" onclick="unc_gallery_generic_ajax(\'unc_gallery_delete_everything\', \'maintenance_target_div\', \'Are you sure?\nThis will delete ALL photos!\', true)">
            Delete all pictures
        </button> This will delete ALL images and thumbnails. Use with caution!</div>
        <div class="progress-div">
            <div id="maintenance-process-progress-bar">0%</div>
        </div>
        <div id="maintenance_target_div"></div>';
    return $out;
}

/**
 * Show the documentation my parsing the README.md file through a markdown parser
 * We are using https://github.com/erusev/parsedown
 */
function unc_gallery_admin_show_documentation() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    require_once(__DIR__  . '/libraries/Parsedown.php');

    $markdown_docs = file_get_contents(__DIR__  . '/README.md');
    $markdown_fixed = str_replace('/images/', plugins_url( '/images/', __FILE__ ), $markdown_docs);
    $Parsedown = new Parsedown();
    return $Parsedown->text($markdown_fixed);
}

/**
 * function to re-build all thumbnails
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_rebuild_thumbs() {
    global $UNC_GALLERY;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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

    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace('target folders:', $target_folders);}

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

function unc_gallery_admin_remove_data() {
    global $UNC_GALLERY, $wpdb;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
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


/**
 * build the missing data
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_rebuild_data() {
    global $UNC_GALLERY, $wpdb;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    ob_clean();

    // $max_time = ini_get('max_execution_time');


    if (!current_user_can('manage_options')) {
        echo "Cannot rebuild data, you are not admin!";
        wp_die();
    }
    $dirPath = $UNC_GALLERY['upload_path'];

    // let's count the number of files in the database so
    // we get an image how much work is to do
    $count_files_sql = "SELECT count(id) AS counter FROM " . $wpdb->prefix . "unc_gallery_img;";
    $file_counter = $wpdb->get_results($count_files_sql, 'ARRAY_A');
    $count = $file_counter[0]['counter'];

    // get the Process ID for the Ajax live update
    $process_id = filter_input(INPUT_POST, 'process_id');
    // send the first update
    $process_step_id = unc_tools_progress_update($process_id, "Cleared existing data");

    // TODO Check where do we delete empty folders?
    // unc_tools_folder_delete_empty($data_folder);

    // iterate all image folders
    $photo_folder = $dirPath . "/" . $UNC_GALLERY['photos'];
    $target_folders = unc_tools_recurse_folders($photo_folder);

    // calculate progress update percentages
    $overall_one_percent = 100 / $count;
    $overall_percentage = 0;

    $text = '';
    foreach ($target_folders as $date => $folder) {
        $process_step_id++;
        $text = "Processing $date: <span class=\"file_progress\" style=\"width:0%\">0 %</span>";
        $process_step_id = unc_tools_progress_update($process_id, $text, $overall_percentage);
        // iterate all files in a folder, write file info to DB
        $folder_files = glob($folder . "/*");
        $folder_file_count = count($folder_files);
        $file_one_percent = 100 / $folder_file_count;
        $folder_percentage = 0;
        foreach ($folder_files as $image_file) {
            if (!is_dir($image_file)) {
                // TODO: ERror in case the info cannot be written
                unc_image_info_write($image_file);
                $folder_percentage += $file_one_percent;
                $overall_percentage += $overall_one_percent;
            }
            $folder_percentage_text = intval($folder_percentage);
            $text = "Processing $date: <span class=\"file_progress\" style=\"width:$folder_percentage_text%\">$folder_percentage_text %</span>";
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
 * Function to delte all contents
 * @global type $UNC_GALLERY
 */
function unc_gallery_admin_delete_everything() {
    global $UNC_GALLERY, $wpdb;
    if ($UNC_GALLERY['debug']) {XMPP_ERROR_trace(__FUNCTION__, func_get_args());}
    ob_clean();
    if (!current_user_can('manage_options')) {
        echo "Cannot delete all, you are not admin!";
    } else {
        // delete all images
        unc_tools_recurse_files($UNC_GALLERY['upload_path'], 'unlink', 'rmdir');


        // delete all data
        $sql1 = "TRUNCATE " . $wpdb->prefix . "unc_gallery_img";
        $wpdb->get_results($sql1);
        $sql2 = "TRUNCATE " . $wpdb->prefix . "unc_gallery_att";
        $wpdb->get_results($sql2);

        echo "Done!";
    }
    wp_die();
}
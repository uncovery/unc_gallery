<?php
// Admin functions

if (!defined('WPINC')) {
    die;
}

function unc_gallery_admin_menu() {
    // the main page where we manage the options
    $main_options_page_hook_suffix = add_menu_page(
        'Uncovery Gallery Options', // $page_title,
        'Uncovery Gallery', // $menu_title,
        'manage_options', // $capability,
        'unc_gallery_admin_menu', // $menu_slug,
        'unc_gallery_admin_settings' // $function, $icon_url, $position
    );
    add_action('admin_print_scripts-' . $main_options_page_hook_suffix, 'unc_gallery_add_css_and_js');
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

    foreach ($UNC_GALLERY['user_settings'] as $setting => $D) {
        $prefix = $UNC_GALLERY['settings_prefix'];
        register_setting('unc_gallery_settings_page', $prefix . $setting);
        $setting_value = get_option($prefix . $setting, $D['default']);
        $args = array('setting' => $prefix . $setting, 'value'=> $setting_value, 'help'=> $D['help'], 'default' => $D['default']);
        if ($D['type'] == 'text') {
            $callback = 'unc_gallery_setting_text_field_render';
        } else {
            $callback = 'unc_gallery_setting_drodown_render';
            $args['options'] = $D['options'];
        }
        add_settings_field(
            $prefix . $setting,
            __(ucwords(str_replace("_", " ", $setting)), 'wordpress'),
            $callback,
            'unc_gallery_settings_page',
            'unc_gallery_pluginPage_section',
            $args
        );
    }

    //add_settings_field( 'field-one', 'Field One', 'unc_gallery_backend_image_upload', 'unc_gallery', 'basic_settings');
    // check if the upload folder exists:
    $dirPath =  $UNC_GALLERY['upload_path'];
    if (!file_exists($dirPath)) {
        echo unc_tools_errormsg("The upload folder $dirPath does not exist!");
        unc_gallery_plugin_activate();
    }
}

/**
 * Generic function to render a text input for WP settings dialogues
 * @param type $A
 */
function unc_gallery_setting_text_field_render($A) {
    $out = "<input type='text' name='{$A['setting']}' value='{$A['value']}'> {$A['help']} <strong>Default:</strong> '{$A['default']}'\n";
    echo $out;
}

/**
 * Generic function to render a dropdown input for WP settings dialogues
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
    $out .= "</select> {$A['help']} <strong>Default:</strong> '{$A['options'][$A['default']]}'\n";
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
    unc_gallery_add_css_and_js();
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
    </script>
    <div class="unc_jquery_tabs unc_fade_in">
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
    echo unc_gallery_admin_upload();
    echo "</div>\n";

    echo "<div id='tab3'>\n";
    echo unc_gallery_admin_display_images();
    echo "</div>\n";

    echo "<div id='tab4'>\n";
    echo unc_gallery_admin_maintenance();
    echo "</div>\n";

    echo "<div id='tab5'>\n";
    require_once(__DIR__ .  DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'Parsedown.php');

    $markdown_docs = file_get_contents(__DIR__ .  DIRECTORY_SEPARATOR . 'README.md');
    $markdown_fixed = str_replace('(/images/', plugins_url( '/images/', __FILE__ ), $markdown_docs);
    $Parsedown = new Parsedown();
    echo $Parsedown->text($markdown_fixed);
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
    $UNC_GALLERY['debug'][][__FUNCTION__] = func_get_args();

    $out = "<h2>Manage Images</h2>\n";
    // check first if there is a folder to delete:
    $folder_del = filter_input(INPUT_GET, 'folder_del', FILTER_SANITIZE_STRING);
    if (!is_null($folder_del)) {
        // TODO: the return here should be in a notifcation area
        $out .= unc_date_folder_delete($folder_del);
    }

    // get a standard short-tag output for latest date with datepicker
    $out .= unc_gallery_apply(array('options'=> $UNC_GALLERY['admin_date_selector']));
    echo $out;
}


/**
 * Displays a dilogue to perform maintenance operations
 * @return string
 */
function unc_gallery_admin_maintenance() {
    $out = '<h2>Maintenance</h2>
        <button class="button button-primary" onclick="unc_gallery_generic_ajax(\'unc_gallery_thumbnails_rebuild\', \'rebuild_thumbs_result\', \'Are you sure?\nThis can take a while for the whole database!\')">
            Rebuild Thumbnails
        </button> This will re-generate all thumbnails. Use this if after you changed the size of the thumbnails in the settings.<br>
        <div id="rebuild_thumbs_result"></div><br>
        <button class="button button-primary" onclick="unc_gallery_generic_ajax(\'unc_gallery_delete_everything\', \'delete_all_result\', \'Are you sure?\nThis will delete ALL photos!\')">
            Delete all pictures
        </button> This will delete ALL images and thumbnails. Use with caution!<br>
        <div id="delete_all_result"></div><br>';
    return $out;
}

function unc_gallery_admin_rebuild_thumbs() {
    global $UNC_GALLERY;
    ob_clean();
    if (!is_admin()) {
        echo "You are not admin!";
        wp_die();
    }
    $dirPath = $UNC_GALLERY['upload_path'];
    // cleanup empty folders first
    unc_tools_folder_delete_empty($dirPath);

    $thumb_root = $dirPath . DIRECTORY_SEPARATOR . $UNC_GALLERY['thumbnails'];
    // iterate all image folders
    $photo_folder = $dirPath . DIRECTORY_SEPARATOR . $UNC_GALLERY['photos'];
    $target_folders = unc_tools_recurse_folders($photo_folder);

    // create thumbnaisl
    foreach ($target_folders as $date => $folder) {
        // construct the thumb folder where we put the thumbnails
        $thumb_folder = $thumb_root . DIRECTORY_SEPARATOR . $date;
        echo "Processing $date: ";

        // enumerate all the files in the source folder
        foreach (glob($folder . DIRECTORY_SEPARATOR . "*") as $image_file) {
            if (!is_dir($image_file)) {
                echo ".";
                $filename = basename($image_file);
                $thumb_filename = $thumb_folder . DIRECTORY_SEPARATOR . $filename;
                unc_import_image_resize($image_file, $thumb_filename, $UNC_GALLERY['thumbnail_height'], 'height', $UNC_GALLERY['thumbnail_ext'], $UNC_GALLERY['thumbnail_quality']);
            }
        }
        echo "<br>";
    }
    echo "Done!";
    wp_die();
}

function unc_gallery_admin_delete_everything() {
    global $UNC_GALLERY;
    ob_clean();
    if (!is_admin()) {
        echo "You are not admin!";
    } else {
        // delete all images
        unc_gallery_recurse_files($UNC_GALLERY['upload_path'], 'unlink', 'rmdir');
        echo "Done!";
    }
    wp_die();
}
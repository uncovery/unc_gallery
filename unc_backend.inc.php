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
    // let's rename that
    add_submenu_page(
        'unc_gallery_admin_menu',
        'Uncovery Gallery Settings',
        'Settings & Upload',
        'read',
        'unc_gallery_admin_menu',
        'unc_gallery_admin_settings'
    );
    // where we list up all the images
    $view_page_hook_suffix = add_submenu_page(
        'unc_gallery_admin_menu', // $parent_slug
        'View Images',  // $page_title
        'View Images', // $menu_title
        'manage_options', // capability, manage_options is the default
        'unc_gallery_admin_view', // menu_slug
        'unc_gallery_admin_display_images' // function
    );
    add_action('admin_print_scripts-' . $view_page_hook_suffix, 'unc_gallery_add_css_and_js');
}

/**
 * displayes the complete image catalogue for the admin
 * TODO: the content should only show the past few dates and collapse the rest
 * and then provide buttons for AJAX-loading of older content
 *
 * @global type $WPG_CONFIG
 */
function unc_gallery_admin_display_images() {
    global $WPG_CONFIG;

    $out = "<h2>Uncovery Gallery: All Images</h2>\n";

    // check first if there is a folder to delete:
    $folder_del = filter_input(INPUT_GET, 'folder_del', FILTER_SANITIZE_STRING);
    if (!is_null($folder_del)) {
        // TODO: the return here should be in a notifcation area
        $out .= unc_date_folder_delete($folder_del);
    }

    // we do not want to convert linebreaks
    remove_filter('the_content', 'wpautop');

    $photo_folder =  WP_CONTENT_DIR . $WPG_CONFIG['upload'] . $WPG_CONFIG['photos'];
    // let's get the all image folders
    $folder_list = unc_display_folder_list($photo_folder);
    if (count($folder_list) == 0) {
        echo $out . "There are no images uploaded!";
        return;
    }
    // sort by date, reversed (latest first)
    krsort($folder_list);

    // the above dates are local timezone, we need the same date in UTC
    $new_dates = unc_display_fix_timezones($folder_list);

    $dates_arr = array();

    foreach ($new_dates as $date => $details) {
        $date_split = explode("-", $date);
        $date_path = implode(DIRECTORY_SEPARATOR, $date_split);
        $dates_arr[$date_path] = $date;
    }

    $out .= "<div class=\"photopage adminpage\">\n";
    foreach ($dates_arr as $text => $date_str) {
        $delete_link = " <a class=\"delete_folder_link\" href=\"?page=unc_gallery_admin_view&amp;folder_del=$text\">Delete Folder</a>";
        $images = unc_display_folder_images($text);
        $out .= "<h3>$date_str:$delete_link</h3>\n" . $images . "<br>";
    }
    $out .= "</div>\n";
    echo $out;
}

/**
 * This adds the Wordpress features for the admin pages
 *
 * @global type $WPG_CONFIG
 */
function unc_gallery_admin_init() {
    global $WPG_CONFIG;
    add_settings_section('basic_settings', 'Basic Settings', 'unc_gallery_admin_settings', 'unc_gallery');
    foreach ($WPG_CONFIG['user_settings'] as $setting => $D) {
        register_setting('unc_gallery_settings_group', $setting);
    }

    //add_settings_field( 'field-one', 'Field One', 'unc_gallery_backend_image_upload', 'unc_gallery', 'basic_settings');
    // check if the upload folder exists:
    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];
    if (!file_exists($dirPath)) {
        echo unc_tools_errormsg("There was an error creating the upload folder $dirPath!");
    }
    wp_register_script('jquery-form', '/wp-includes/js/jquery/jquery.form.js');
    wp_register_script('jquery-ui-datepicker', '/wp-includes/js/jquery/ui/jquery.ui.datepicker.min.js');
}

/**
 * this will manage the settings
 */
function unc_gallery_admin_settings() {
    echo "<h2>Uncovery Gallery Settings</h2>\n";
    echo '<div class="wrap">';
    echo unc_gallery_user_options();
    echo unc_gallery_admin_upload();
    echo "</div>";
}

function unc_gallery_user_options() {
    global $WPG_CONFIG;
    echo '<form method="post" action="options.php">'. "\n";
    settings_fields('unc_gallery_settings_group');
    do_settings_sections( 'unc_gallery_settings_group');
    echo "\n<table>\n";
    foreach ($WPG_CONFIG['user_settings'] as $setting => $D) {
        $default = $D['default'];
        $help = $D['help'];
        $set_value = esc_attr(get_option($setting, $default));
        $description = ucwords(str_replace("_", " ", $setting));
        echo "<tr><td><label>$description:</label></td><td><input type=\"text\" name=\"$setting\" value=\"$set_value\"></td><td>$help <strong>Default:</strong> '$default'.</td></tr>\n";
    }
    echo "</table>\n";
    submit_button();
    echo "</form>\n";
}
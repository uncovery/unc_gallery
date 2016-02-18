<?php
// Admin functions

if (!defined('WPINC')) {
    die;
}

add_shortcode('unc_gallery', 'unc_gallery_apply');
// initialize the plugin, create the upload folder
add_action('admin_init', 'unc_gallery_admin_init');
// add an admin menu
add_action('admin_menu', 'unc_gallery_admin_menu');
// this activates the returns without header & footer on upload Ajax POST
add_action('wp_ajax_unc_gallery_uploads', 'unc_uploads_iterate_files');

function unc_gallery_admin_menu() {
    // the main page where we manage the options
    add_menu_page(
        'Uncovery Gallery Options', // $page_title,
        'Uncovery Gallery', // $menu_title,
        'manage_options', // $capability,
        'unc_gallery_admin_menu', // $menu_slug,
        'unc_gallery_options' // $function, $icon_url, $position
    );
    // where we upload images
    $upload_page_hook_suffix = add_submenu_page(
        'unc_gallery_admin_menu', // $parent_slug
        'Upload Images',  // $page_title
        'Upload Images', // $menu_title
        'manage_options', // capability, manage_options is the default
        'unc_gallery_admin_upload', // menu_slug
        'unc_uploads_form' // function
    );
    add_action('admin_print_scripts-' . $upload_page_hook_suffix, 'unc_gallery_admin_add_css_and_js');
    // where we list up all the images
    $view_page_hook_suffix = add_submenu_page(
        'unc_gallery_admin_menu', // $parent_slug
        'View Images',  // $page_title
        'View Images', // $menu_title
        'manage_options', // capability, manage_options is the default
        'unc_gallery_admin_view', // menu_slug
        'unc_gallery_admin_display_images' // function
    );
    add_action('admin_print_scripts-' . $view_page_hook_suffix, 'unc_gallery_admin_add_css_and_js');
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
    register_setting('unc_gallery_settings_group', 'unc_gallery_setting');
    add_settings_section('basic_settings', 'Basic Settings', 'unc_gallery_admin_settings', 'unc_gallery');
    //add_settings_field( 'field-one', 'Field One', 'unc_gallery_backend_image_upload', 'unc_gallery', 'basic_settings');
    // check if the upload folder exists:
    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];
    if (!file_exists($dirPath)) {
        echo "There was an error creating the upload folder $dirPath!";
    }
    wp_register_script('jquery-form', '/wp-includes/js/jquery/jquery.form.js');
    wp_register_script('jquery-ui-datepicker', '/wp-includes/js/jquery/ui/jquery.ui.datepicker.min.js');
}

/**
 * add additional CSS and JS
 */
function unc_gallery_admin_add_css_and_js() {
    wp_enqueue_script('jquery-ui');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-form');
    wp_enqueue_script('thickbox', null, array('jquery'));
    wp_enqueue_style('thickbox.css', '/'.WPINC.'/js/thickbox/thickbox.css', null, '1.0');

    wp_enqueue_style('bootstrap-css', plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css');
    wp_enqueue_style('magnific-popup-css', plugin_dir_url( __FILE__ ) . 'css/magnific-popup.css');

    wp_enqueue_script('unc_gallery_js', plugin_dir_url( __FILE__ ) . 'js/unc_gallery.js');
    wp_enqueue_style('unc_gallery_css', plugin_dir_url( __FILE__ ) . 'css/gallery.css');
    wp_enqueue_style('jquery_ui_css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css');
}

/**
 * this will manage the settings
 */
function unc_gallery_admin_settings() {
    echo "test";
}
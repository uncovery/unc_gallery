<?php
// Admin functions

if (!defined('WPINC')) {
    die;
}

// add filter to scan content for activating
add_filter('the_content', 'unc_gallery', 0);
// initialize the plugin, create the upload folder
add_action('admin_init', 'unc_gallery_admin_init');
// add an admin menu
add_action('admin_menu', 'unc_gallery_admin_menu');

function unc_gallery_admin_menu() {
    add_menu_page(
        'Uncovery Gallery Options', // $page_title,
        'Uncovery Gallery', // $menu_title,
        'manage_options', // $capability,
        'unc_gallery_admin_menu', // $menu_slug,
        'unc_gallery_options' // $function, $icon_url, $position
    );
    $upload_page_hook_suffix = add_submenu_page(
        'unc_gallery_admin_menu', // $parent_slug
        'Upload Images',  // $page_title
        'Upload Images', // $menu_title
        'manage_options', // capability, manage_options is the default
        'unc_gallery_admin_upload', // menu_slug
        'unc_uploads_form' // function
    );
    add_action('admin_print_scripts-' . $upload_page_hook_suffix, 'unc_gallery_admin_add_css_and_js');
    $view_page_hook_suffix = add_submenu_page(
        'unc_gallery_admin_menu', // $parent_slug
        'View Images',  // $page_title
        'View Images', // $menu_title
        'manage_options', // capability, manage_options is the default
        'unc_gallery_admin_view', // menu_slug
        'unc_images_display' // function
    );
    add_action('admin_print_scripts-' . $view_page_hook_suffix, 'unc_gallery_admin_add_css_and_js');
    if (isset($_FILES["userImage"])) {
        add_filter('admin_footer_text', 'unc_remove_footer_admin');
    }

}

function unc_gallery_admin_init() {
    global $WPG_CONFIG;
    register_setting('unc_gallery_settings_group', 'unc_gallery_setting');
    add_settings_section('basic_settings', 'Basic Settings', 'unc_gallery_backend_basic_settings', 'unc_gallery');
    //add_settings_field( 'field-one', 'Field One', 'unc_gallery_backend_image_upload', 'unc_gallery', 'basic_settings');
    // check if the upload folder exists:
    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];
    if (!file_exists($dirPath)) {
        echo "There was an error creating the upload folder $dirPath!";
    }
    wp_register_script('jquery-form');
    wp_register_script('jquery-ui-datepicker');
}

/**
 * add additional CSS and JS
 */
function unc_gallery_admin_add_css_and_js() {
    wp_enqueue_script('jquery-ui');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-form');
    wp_enqueue_style('bootstrap-css', plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css');
    wp_enqueue_style('magnific-popup-css', plugin_dir_url( __FILE__ ) . 'css/magnific-popup.css');
    wp_enqueue_style('plugin_style-css', plugin_dir_url( __FILE__ ) . 'css/plugin_style.css');
    wp_enqueue_style('plugin_style-css', plugin_dir_url( __FILE__ ) . 'css/style1.css');

    wp_enqueue_script('unc_gallery_js', plugin_dir_url( __FILE__ ) . 'js/unc_gallery.js');
    wp_enqueue_style('unc_gallery_css', plugin_dir_url( __FILE__ ) . 'css/gallery.css');
    wp_enqueue_style('jquery_ui_css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css');
}

function unc_gallery_backend_basic_settings() {

    echo "test";
}

// this is just a dummy function to remove the footer for AJAX return when the
// images are uploaded
function unc_remove_footer_admin() {
    return '';
}


//// delete image record start
//if (isset($_REQUEST['delete_id'])){
//    $table_name = $wpdb->prefix . 'image_info';
//    $pageposts = $wpdb->get_results("SELECT * from $table_name WHERE image_id=" . $_REQUEST['delete_id']);
//    if ($pageposts) {
//        foreach ($pageposts as $post) {
//            $remove_image = $dirPath . '/' . $post->image_name;
//            unlink($remove_image);
//        }
//    } else {
//        echo "Image Not found in folder";
//    }
//    $delete = $wpdb->query("DELETE FROM $table_name WHERE image_id = '" . $_REQUEST['delete_id'] . "'");
//}
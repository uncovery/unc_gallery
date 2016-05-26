<?php
/*
Plugin Name: Uncovery Gallery
Plugin URI:  https://uncovery.net/about
Description: A simple, self-generating, date-based gallery with bulk uploading
Version:     4.0
Author:      Uncovery
Author URI:  http://uncovery.net
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $UNC_FILE_DATA;

require_once( plugin_dir_path( __FILE__ ) . "unc_config.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_backend.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_upload.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_display.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_tools.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_image.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_arrays.inc.php");

global $XMPP_ERROR;
if (file_exists('/home/includes/xmpp_error/xmpp_error.php')) {
    require_once('/home/includes/xmpp_error/xmpp_error.php');
    $XMPP_ERROR['config']['project_name'] = 'unc_gallery';
    $XMPP_ERROR['config']['enabled'] = true;
    $XMPP_ERROR['config']['include_warnings'] = 'unc_gallery';
    $XMPP_ERROR['config']['track_globals'] = array('UNC_GALLERY', 'UNC_FILE_DATA');
}
// actions on activating and deactivating the plugin
register_activation_hook( __FILE__, 'unc_gallery_plugin_activate');
register_deactivation_hook( __FILE__, 'unc_gallery_plugin_deactivate');
register_uninstall_hook( __FILE__, 'unc_gallery_plugin_uninstall');

if (is_admin()){ // admin actions
    add_action('admin_init', 'unc_gallery_admin_init');
    // add an admin menu
    add_action('admin_menu', 'unc_gallery_admin_menu');
}
// shortcode for the [unc_gallery] replacements
add_shortcode('unc_gallery', 'unc_gallery_apply');
// this activates the returns without header & footer on upload Ajax POST
add_action('wp_ajax_unc_gallery_uploads', 'unc_uploads_iterate_files');
add_action('wp_ajax_unc_gallery_import_images', 'unc_uploads_iterate_files');
add_action('wp_ajax_nopriv_unc_gallery_datepicker', 'unc_display_ajax_folder');
add_action('wp_ajax_unc_gallery_datepicker', 'unc_display_ajax_folder');
add_action('wp_ajax_unc_gallery_image_delete', 'unc_tools_image_delete');
add_action('wp_ajax_unc_gallery_images_refresh', 'unc_gallery_images_refresh');
add_action('wp_ajax_unc_gallery_thumbnails_rebuild', 'unc_gallery_admin_rebuild_thumbs');
add_action('wp_ajax_unc_gallery_admin_rebuild_data', 'unc_gallery_admin_rebuild_data');
add_action('wp_ajax_unc_gallery_delete_everything', 'unc_gallery_admin_delete_everything');

add_action( 'wp_enqueue_scripts', 'unc_gallery_add_css_and_js' );

// execute shortcodes in the excerpts
add_filter('the_excerpt', 'do_shortcode');
add_filter('the_excerpt_rss', 'do_shortcode');

// get the settings from the system and set the global variables
// this iterates the user settings that are supposed to be in the wordpress config
// and gets them from there, setting the default if not available
// inserts them into the global
global $UNC_GALLERY;
foreach ($UNC_GALLERY['user_settings'] as $setting => $D) {
    $UNC_GALLERY[$setting] = get_option($UNC_GALLERY['settings_prefix'] . $setting, $D['default']);
}

/**
 * standard wordpress function to activate the plugin.
 * creates the uploads folder
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_plugin_activate() {
    global $UNC_GALLERY;
    if (!file_exists($UNC_GALLERY['upload_path'])) {
        $result = mkdir($UNC_GALLERY['upload_path'], 0755);
        // check success
        if (!$result) {
            echo unc_display_errormsg("There was an error creating the upload folder {$UNC_GALLERY['upload_path']}!");
        }
    }
}

/**
 * standard wordpress function to deactivate the plugin.
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_plugin_deactivate() {
    global $UNC_GALLERY;
    // deactivate all settings
    $prefix = $UNC_GALLERY['settings_prefix'];
    foreach ($UNC_GALLERY['user_settings'] as $setting => $D) {
        unregister_setting('unc_gallery_settings_page', $prefix . $setting);
    }
}

function unc_gallery_plugin_uninstall() {
    global $UNC_GALLERY;
    // delete all images optional

    if ($UNC_GALLERY['uninstall_deletes_images'] == 'yes') {
        unc_gallery_recurse_files($UNC_GALLERY['upload_path'], 'unlink', 'rmdir');
    }

    //delete all settings properly
    $prefix = $UNC_GALLERY['settings_prefix'];
    foreach ($UNC_GALLERY['user_settings'] as $setting => $D) {
        delete_option($prefix . $setting);
    }
    // register_uninstall_hook($file, $callback)
}

/**
 * function that includes all the CSS and JS that are needed.
 *
 */
function unc_gallery_add_css_and_js() {
    global $UNC_GALLERY;
    // jquery etc
    wp_enqueue_script('jquery-ui');
    wp_enqueue_script('jquery-form');
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery_ui_css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css');

    // unc_gallery custom
    wp_enqueue_script('unc_gallery_js', plugin_dir_url( __FILE__ ) . 'js/unc_gallery.js');
    wp_enqueue_style('unc_gallery_css', plugin_dir_url( __FILE__ ) . 'css/gallery.css');

    if ($UNC_GALLERY['image_view_method'] == 'lightbox') {
        // lightbox
        wp_register_script('unc_gallery_lightbox_js', plugin_dir_url( __FILE__ ) . 'js/lightbox.min.js', array(), '2.8.2', true);
        wp_enqueue_script('unc_gallery_lightbox_js');
        wp_enqueue_style('unc_gallery_lightbox_css', plugin_dir_url( __FILE__ ) . 'css/lightbox.min.css');
    } else if ($UNC_GALLERY['image_view_method'] == 'photoswipe') {
        //photoswipe
        wp_register_script('unc_gallery_photoswipe_ui_js', plugin_dir_url( __FILE__ ) . 'js/photoswipe-ui-default.min.js', array(), '4.1.1', true);
        wp_enqueue_script('unc_gallery_photoswipe_ui_js');
        wp_register_script('unc_gallery_photoswipe_js', plugin_dir_url( __FILE__ ) . 'js/photoswipe.min.js', array(), '4.1.1', true);
        wp_enqueue_script('unc_gallery_photoswipe_js');
        wp_enqueue_style('unc_gallery_photoswipe_css', plugin_dir_url( __FILE__ ) . 'css/photoswipe.css');
        wp_enqueue_style('unc_gallery_photoswipe_skin_css', plugin_dir_url( __FILE__ ) . 'css/default-skin.css');
        add_action('wp_footer', 'unc_display_photoswipe');
        add_action('admin_footer', 'unc_display_photoswipe');
    }
}

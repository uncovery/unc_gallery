<?php
/*
Plugin Name: Uncovery Gallery
Plugin URI:  https://github.com/uncovery/unc_gallery
Description: A simple, self-generating, date-based gallery
Version:     0.1
Author:      Uncovery
Author URI:  http://uncovery.net
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

require_once( plugin_dir_path( __FILE__ ) . "unc_config.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_backend.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_upload.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_display.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_tools.inc.php");

global $XMPP_ERROR;
require_once('/home/includes/xmpp_error/xmpp_error.php');
$XMPP_ERROR['config']['project_name'] = 'unc_gallery';
$XMPP_ERROR['config']['enabled'] = true;

// actions on activating and deactivating the plugin
register_activation_hook( __FILE__, 'unc_gallery_plugin_activate');
register_deactivation_hook( __FILE__, 'unc_gallery_plugin_deactivate');

if (is_admin()){ // admin actions
    add_action('admin_init', 'unc_gallery_admin_init');
    // add an admin menu
    add_action('admin_menu', 'unc_gallery_admin_menu');
}
// shortcode for the [unc_gallery] replacements
add_shortcode('unc_gallery', 'unc_gallery_apply');
// initialize the plugin, create the upload folder
// this activates the returns without header & footer on upload Ajax POST
add_action('wp_ajax_unc_gallery_uploads', 'unc_uploads_iterate_files');
add_action('wp_ajax_nopriv_unc_gallery_datepicker', 'unc_display_folder_images');
add_action('wp_ajax_unc_gallery_datepicker', 'unc_display_folder_images');

/**
 * standard wordpress function to activate the plugin.
 * creates the uploads folder
 *
 * @global type $WPG_CONFIG
 */
function unc_gallery_plugin_activate() {
    global $WPG_CONFIG;
    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];
    if (!file_exists($dirPath)) {
        $result = mkdir($dirPath, 0755);
        // check success
        if (!$result) {
            echo "There was an error creating the upload folder $dirPath!";
        }
    }
}

/**
 * standard wordpress function to deactivate the plugin.
 * removes the download folder.
 *
 * @global type $WPG_CONFIG
 */
function unc_gallery_plugin_deactivate() {
    global $WPG_CONFIG;
    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];
    if (file_exists($dirPath)) {
        $result = rmdir($dirPath);
        if (!$result) {
            echo "There was an error deleting the upload folder $dirPath!";
        }
    }
    // remove all settings
    foreach ($WPG_CONFIG['user_settings'] as $setting => $D) {
        unregister_setting('unc_gallery_settings_group', $setting);
    }
}

/**
 * function that includes all the CSS and JS that are needed.
 *
 */
function unc_gallery_add_css_and_js() {
    wp_enqueue_script('jquery-ui');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-form');
    wp_enqueue_script('thickbox', null, array('jquery'));
    wp_enqueue_style('thickbox.css', '/'.WPINC.'/js/thickbox/thickbox.css', null, '1.0');
    wp_enqueue_script('unc_gallery_js', plugin_dir_url( __FILE__ ) . 'js/unc_gallery.js');
    wp_enqueue_style('unc_gallery_css', plugin_dir_url( __FILE__ ) . 'css/gallery.css');
    wp_enqueue_style('jquery_ui_css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css');
}

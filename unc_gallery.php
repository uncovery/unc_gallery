<?php
/*
Plugin Name: Uncovery Gallery
Plugin URI:  https://uncovery.net/about
Description: A simple, self-generating, date-based gallery with bulk uploading
Version:     5.0
Author:      Uncovery
Author URI:  http://uncovery.net
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * This is the main file of the plugin and contains the most basic plugin-handling
 * functions that are relevant for wordpress in handling this plugin.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

global $UNC_FILE_DATA, $UNC_GALLERY;
$UNC_GALLERY['debug'] = false;

if (!isset($UNC_GALLERY['start_time'])) {
    $UNC_GALLERY['start_time'] = microtime(true);
}

$UNC_GALLERY['data_version'] = 1; // increase this number when you change the format!

require_once( plugin_dir_path( __FILE__ ) . "unc_backend.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_upload.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_display.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_type_day.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_type_filter.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_type_chrono.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_tools.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_image.inc.php");
// co nfig has to be last because it runs function in unc_image.inc.php
require_once( plugin_dir_path( __FILE__ ) . "unc_config.inc.php");

require_once( plugin_dir_path( __FILE__ ) . "/libraries/unc_exif.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "/libraries/unc_ipct.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "/libraries/unc_xmp.inc.php");

// actions on activating and deactivating the plugin
register_activation_hook( __FILE__, 'unc_gallery_plugin_activate');
register_deactivation_hook( __FILE__, 'unc_gallery_plugin_deactivate');
register_uninstall_hook( __FILE__, 'unc_gallery_plugin_uninstall');

if (is_admin() === true){ // admin actions
    add_action('admin_init', 'unc_gallery_admin_init');
    // add an admin menu
    add_action('admin_menu', 'unc_gallery_admin_menu');
}

// shortcode for the [unc_gallery] replacements
add_shortcode('unc_gallery', 'unc_gallery_apply');

// this activates the returns without header & footer on upload Ajax POST
if (is_admin() === true) {
    add_action('wp_ajax_unc_gallery_uploads', 'unc_uploads_iterate_files');
    add_action('wp_ajax_unc_gallery_import_images', 'unc_uploads_iterate_files');
    add_action('wp_ajax_nopriv_unc_gallery_datepicker', 'unc_display_ajax_folder');
    add_action('wp_ajax_unc_gallery_datepicker', 'unc_display_ajax_folder');
    add_action('wp_ajax_nopriv_unc_filter_update', 'unc_filter_update');
    add_action('wp_ajax_unc_tools_progress_get', 'unc_tools_progress_get');
    add_action('wp_ajax_nopriv_unc_tools_progress_get', 'unc_tools_progress_get');
    add_action('wp_ajax_unc_filter_update', 'unc_filter_update');
    add_action('wp_ajax_unc_chrono_update', 'unc_chrono_update');
    add_action('wp_ajax_unc_gallery_image_delete', 'unc_tools_image_delete');
    add_action('wp_ajax_unc_gallery_images_refresh', 'unc_gallery_images_refresh');
    add_action('wp_ajax_unc_gallery_admin_rebuild_thumbs', 'unc_gallery_admin_rebuild_thumbs');
    add_action('wp_ajax_unc_gallery_admin_rebuild_data', 'unc_gallery_admin_rebuild_data');
    add_action('wp_ajax_unc_gallery_admin_remove_data',  'unc_gallery_admin_remove_data');
    add_action('wp_ajax_unc_gallery_delete_everything', 'unc_gallery_admin_delete_everything');
    add_action('wp_ajax_unc_gallery_admin_remove_logs', 'unc_gallery_admin_remove_logs');
}

add_action( 'wp_enqueue_scripts', 'unc_gallery_enqueue_css_and_js' );

// get the AjaxURL in the frontend
add_action('wp_head','pluginname_ajaxurl');
function pluginname_ajaxurl() {
    echo "<script type=\"text/javascript\">var ajaxurl='" .  admin_url('admin-ajax.php') . "'</script>";
}

// execute shortcodes in the excerpts
add_filter('the_excerpt', 'do_shortcode');
add_filter('the_excerpt_rss', 'do_shortcode');

// get the settings from the system and set the global variables
// this iterates the user settings that are supposed to be in the wordpress config
// and gets them from there, setting the default if not available
// inserts them into the global

foreach ($UNC_GALLERY['user_settings'] as $setting => $D) {
    $UNC_GALLERY[$setting] = get_option($UNC_GALLERY['settings_prefix'] . $setting, $D['default']);
}

// debug run
if ($UNC_GALLERY['debug'] == 'yes') {
    $UNC_GALLERY['debug_log'] = array();
    register_shutdown_function("unc_tools_debug_write");
}


/**
 * Apply default tag/category texts if set in the configuration
 * TODO: Find out why this has sometimes only one parameter instead of two.
 * 
 * @global type $UNC_GALLERY
 * @param type $description
 * @param type $taxonomy
 * @return array
 */
function unc_gallery_default_term_description($description = '', $taxonomy = 'post_tag') {
    global $UNC_GALLERY;
    if ($description) {
        return $description;
    }

    switch ( $taxonomy ) {
        case 'category':
            $description = $UNC_GALLERY['category_default_description'];
            break;
        case 'post_tag':
            $description = $UNC_GALLERY['tag_default_description'];
            break;
    }
    return $description;    
}
add_action('pre_term_description', 'unc_gallery_default_term_description');

/**
 * standard wordpress function to activate the plugin.
 * creates the uploads folder
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_plugin_activate() {
    global $UNC_GALLERY;
    // we check if the upload path exists (from config) and create if not
    if (!file_exists($UNC_GALLERY['upload_path'])) {
        $result = mkdir($UNC_GALLERY['upload_path'], 0755);
        // check success
        if (!$result) {
            echo unc_display_errormsg("There was an error creating the upload folder {$UNC_GALLERY['upload_path']}!");
        }
    }
    // create the DB structure
    unc_mysql_db_create();
}

function unc_mysql_db_create() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $table_name_img = $wpdb->prefix . "unc_gallery_img";
    $sql_img = "CREATE TABLE $table_name_img (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        file_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        file_name varchar(128) NOT NULL,
        file_path varchar(256) NOT NULL,
        UNIQUE KEY `id` (`id`),
        UNIQUE KEY `file_time` (`file_time`,`file_name`)
    ) $charset_collate;";
    dbDelta($sql_img);

    $table_name_att = $wpdb->prefix . "unc_gallery_att";
    $sql_att = "CREATE TABLE $table_name_att (
        `att_id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `file_id` mediumint(9) NOT NULL,
        `att_group` ENUM('default','iptc','exif','xmp') NOT NULL,
        `att_name` varchar(25) NOT NULL,
        `att_value` varchar(255) NOT NULL,
        UNIQUE KEY `att_id` (`att_id`),
        KEY `att_name` (`att_name`),
        KEY `file_id` (`file_id`)
    ) $charset_collate;";

    dbDelta($sql_att);
    
    $table_name_att = $wpdb->prefix . "unc_gallery_cat_links";
    $sql_link = "CREATE TABLE $table_name_att (
        `location_code` varchar(128) NOT NULL,
        `category_id` mediumint(9) NOT NULL,
        UNIQUE KEY `category_id` (`category_id`),
        UNIQUE KEY `location_code` (`location_code`)
    ) $charset_collate;";    
    
    dbDelta($sql_link);

    add_option( "unc_gallery_db_version", "2.3" );
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

/**
 * Unistalling the plugin
 *
 * @global type $UNC_GALLERY
 */
function unc_gallery_plugin_uninstall() {
    global $UNC_GALLERY;
    // delete all images optional

    if ($UNC_GALLERY['uninstall_deletes_images'] == 'yes') {
        unc_tools_recurse_files($UNC_GALLERY['upload_path'], 'unlink', 'rmdir');
    }
    // TODO: check and remove databases

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
function unc_gallery_enqueue_css_and_js() {
    global $UNC_GALLERY, $post;

    //we load the code only if we are actually using it.
    // the issue is that we don't know at this point if we are 
    // a) showing only the excerpt or also the content
    // b) if we are using the map
    // since a lot of the code has to be in the header, we need to enqueue the scripts here.
    if (!is_null($post) && !has_shortcode($post->post_content, 'unc_gallery') ) {
        return;
    }
   
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
        wp_register_script('unc_gallery_photoswipe_ui_js', plugin_dir_url( __FILE__ ) . 'js/photoswipe-ui-default.min.js', array(), '4.1.2', true);
        wp_register_script('unc_gallery_photoswipe_js', plugin_dir_url( __FILE__ ) . 'js/photoswipe.min.js', array(), '4.1.2', true);
        wp_enqueue_script('unc_gallery_photoswipe_ui_js');
        wp_enqueue_script('unc_gallery_photoswipe_js');
        wp_enqueue_style('unc_gallery_photoswipe_css', plugin_dir_url( __FILE__ ) . 'css/photoswipe.css');
        wp_enqueue_style('unc_gallery_photoswipe_skin_css', plugin_dir_url( __FILE__ ) . 'css/default-skin.css');
        // these two add the HTML from function unc_display_photoswipe() to the page footer
        // without this, photoswipe does not work (duh).
        add_action('wp_footer', 'unc_display_photoswipe');
        add_action('admin_footer', 'unc_display_photoswipe');
    }
    // load google maps if it's set
    if (strlen($UNC_GALLERY['google_api_key']) > 1) {
        $api_key = $UNC_GALLERY['google_api_key'];
        wp_register_script('unc_gallery_google_maps', "https://maps.googleapis.com/maps/api/js?key=$api_key", array(), '', false);
        wp_register_script('unc_gallery_makerwithlabel_js', plugin_dir_url( __FILE__ ) . 'js/markerwithlabel.js', array(), '', false);
        wp_register_script('unc_gallery_makercluster_js', plugin_dir_url( __FILE__ ) . 'js/markerclusterer.js', array(), '', false);
        wp_enqueue_script('unc_gallery_google_maps');
        wp_enqueue_script('unc_gallery_makerwithlabel_js');
        wp_enqueue_script('unc_gallery_makercluster_js');           
    }
}
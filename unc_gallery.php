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
require_once( plugin_dir_path( __FILE__ ) . "unc_display.inc.php");
require_once( plugin_dir_path( __FILE__ ) . "unc_tools.inc.php");

// actions on activating and deactivating the plugin
register_activation_hook( __FILE__, 'unc_gallery_plugin_activate');
register_deactivation_hook( __FILE__, 'unc_gallery_plugin_deactivate');

/*
 * Helptext for the settings menu
 */
function umc_gallery_settings_callback() {
    echo "Please set your options:";
}

function unc_gallery_options() {
    ?>
    <div class="wrap">
        <h2>Uncovery Gallery Options</h2>
        <form action="options.php" method="POST">
            <?php settings_fields( 'unc_gallery_settings_group' ); ?>
            <?php do_settings_sections( 'unc_gallery' ); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

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

function unc_gallery_plugin_deactivate() {
    global $wpdb, $WPG_CONFIG;
    //$table_name = $wpdb->prefix . 'unc_gallery_images';
    //$sql = "DROP TABLE IF EXISTS $table_name;";
    //$wpdb->query($sql);

    $dirPath =  WP_CONTENT_DIR . $WPG_CONFIG['upload'];
    if (file_exists($dirPath)) {
        $result = rmdir($dirPath);
        if (!$result) {
            echo "There was an error deleting the upload folder $dirPath!";
        }
    }
}

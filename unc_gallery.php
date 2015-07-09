<?php
/*
Plugin Name: UNC Gallery
Plugin URI:  https://github.com/uncovery/unc_gallery
Description: Wordpress-plugin for a simple, self-generating, date-based gallery
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

global $WPG_CONFIG;

$WPG_CONFIG['gallery_path'] = plugin_dir_path( __FILE__ ) . "galleries/default";
// upload folders below above folder
$WPG_CONFIG['upload'] = "/upload";

$WPG_CONFIG['photos'] = "/photos";
$WPG_CONFIG['invalid'] = "/invalid";
$WPG_CONFIG['thumbnails'] = "/thumbs";

$WPG_CONFIG['base_url'] = plugins_url();
$WPG_CONFIG['gallery_url'] = $WPG_CONFIG['base_url'] . "/galleries";

$WPG_CONFIG['timezone'] = 'Asia/Hong_Kong';
$WPG_CONFIG['thumbnail_size'] = 300;
$WPG_CONFIG['thumbnail_ext'] = 'PNG';

require_once( plugin_dir_path( __FILE__ ) . "/unc_gallery_display.php");

// add filter to scan content for activating
add_filter('the_content', 'unc_gallery', 0);
// add an admin menu
add_action('admin_menu', 'unc_gallery_admin_menu');

add_action('admin_init', 'unc_gallery_admin_init');

add_action( 'wp_enqueue_scripts', 'unc_gallery_add_css_and_js' );
/**
 * main function. Checks for the keyword in the content and switches that define
 * the content further. Then calls the function that creates the actual content
 * and returns the modified content
 *
 * @param type $content
 * @return type
 */
function unc_gallery($content) {
    $pattern = '/\[(?\'activator\'unc_gallery)( date="(?\'date\'[0-9-]{10})")?( gallery_name="(?\'gallery\'[a-z_-]*)")?\]/';
    $matches = false;
    preg_match($pattern, $content, $matches);

    if (!isset($matches['activator'])) {
        return $content;
    }

    $date = false;
    if (isset($matches['date'])) {
        $date = $matches['date'];
    }
    if (isset($matches['gallery'])) {
        $gallery = $matches['gallery'];
    }

    $content_new = unc_gallery_display_page($content, $date, $gallery);

    return $content_new;
}

function unc_gallery_admin_menu() {
    add_options_page(
        'Uncovery Gallery Options', // page title
        'Uncovery Gallery',  // menu title
        'manage_options', // capability, manage_options is the default
        'unc_gallery', // menu_slug
        'unc_gallery_options' // callback
    );
}


function unc_gallery_admin_init() {
    register_setting( 'unc_gallery_settings_group', 'unc_gallery_setting' );
    add_settings_section( 'basic_settings', 'Folder Settings', 'umc_gallery_settings_callback', 'unc_gallery' );
    add_settings_field( 'field-one', 'Field One', 'unc_gallery_field_one_callback', 'unc_gallery', 'basic_settings' );
}

/*
 * Helptext for the settings menu
 */
function umc_gallery_settings_callback() {
    wp_enqueue_media();
    echo "Please set your options:";
}

function unc_gallery_field_one_callback() {
    $settings = (array) get_option( 'unc_gallery_setting' );
    $color = esc_attr( $settings['color'] );
    echo "<input type='text' name='unc_gallery_setting[color]' value='$color' />";

    ?>
        <label for="image_url">Upload photos</label>
        <input type="text" name="image_url" id="image_url" class="regular-text">
        <input type="button" name="upload-btn" id="upload-btn" class="button-secondary" value="Upload Image">
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('#upload-btn').click(function(e) {
            e.preventDefault();
            var image = wp.media({
                title: 'Upload Images',
                // mutiple: true if you want to upload multiple files at once
                multiple: true
            }).open()
            .on('select', function(e){
                // This will return the selected image from the Media Uploader, the result is an object
                var uploaded_image = image.state().get('selection').first();
                // We convert uploaded_image to a JSON object to make accessing it easier
                // Output to the console uploaded_image
                console.log(uploaded_image);
                var image_url = uploaded_image.toJSON().url;
                // Let's assign the url value to the input field
                $('#image_url').val(image_url);
            });
        });
    });
    </script>

    <?php
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

/**
 * add additional CSS and JS
 */
function unc_gallery_add_css_and_js() {
    wp_enqueue_style('lightbox', plugins_url('css/lightbox.css', __FILE__ ));
    wp_enqueue_script('lightbox_js', plugins_url('js/lightbox.min.js', __FILE__ ), array(), false, true); // in footer
    wp_enqueue_script('unc_gallery_js', plugins_url('js/unc_gallery.js', __FILE__ ) );
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-style',  plugins_url('css/jquery-ui.css', __FILE__ ));
    wp_enqueue_style('unc_Gallery_css', plugins_url('css/gallery.css', __FILE__ ));
}

function unc_datetime($date = NULL) {
    global $WPG_CONFIG;

    $date_new = new DateTime($date);
    $date_new->setTimezone(new DateTimeZone($WPG_CONFIG['timezone']));
    return $date_new;
}


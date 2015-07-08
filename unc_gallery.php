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

// add filter to scan content for activating
add_filter('the_content', 'unc_gallery', 0);
// add an admin menu
add_action('admin_menu', 'unc_gallery_admin_menu');

add_action('admin_init', 'unc_gallery_admin_init');
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
    
    $content_new = unc_gallery_create_page($content, $date, $gallery);
    
    return $content_new;
}

function unc_gallery_create_page($content, $date = false, $gallery = false) {
    
    
    $out = "This is a gallery page for date $date and gallery $gallery";

    // replace the whole activator code in the content
    $pattern = '/(\[unc_gallery.*\])/';
    $new_content = preg_replace($pattern, $out, $content);
    return $new_content;
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
    add_settings_section( 'basic_settings', 'Basic Settings', 'umc_gallery_settings_callback', 'unc_gallery' );
    add_settings_field( 'field-one', 'Field One', 'unc_gallery_field_one_callback', 'unc_gallery', 'basic_settings' );
}

function umc_gallery_settings_callback() { 
    echo "settings callback";
}

function unc_gallery_field_one_callback() {
    $setting = esc_attr(get_option('unc_gallery_setting'));
    echo "<input type='text' name='unc_gallery_setting' value='$setting' />";
}

function unc_gallery_options() {
    ?>
    <div class="wrap">
        <h2>My Plugin Options</h2>
        <form action="options.php" method="POST">
            <?php settings_fields( 'unc_gallery_settings_group' ); ?>
            <?php do_settings_sections( 'unc_gallery' ); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}